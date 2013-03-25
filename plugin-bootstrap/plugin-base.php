<?php
/* 
Plugin Name: Testimonial
Version: 0.1
Description: Add customer feedback including comment, date, company name and website.
Shortcode: [testimonial] [testimonial from='0%' to='33%'] [testimonial from='33%' to='100%']
Author: Etienne Tremel
*/

if ( ! class_exists( 'Testimonial' ) ) {
    class Testimonial {

        private $name = 'testimonial';
        private $name_plurial, $label, $label_plurial;

        public function __construct() {
            
            /* INITIALIZE VARIABLES */
            $this->name_plurial  = $this->name . 's';
            $this->label         = ucwords( preg_replace( '/[_.-]+/', ' ', $this->name ) );
            $this->label_plurial = ucwords( preg_replace( '/[_.-]+/', ' ', $this->name_plurial ) );

            /* REGISTER CUSTOM POST TYPE */
            add_action( 'init', array( $this, 'register' ) );

            /* CHANGE DEFAULT CUSTOM TITLE (Change placeholder value when editing/adding a new post) */
            add_filter( 'enter_title_here', array( $this, 'change_title' ) );

            /* DISPLAY CUSTOM FIELDS */
            add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );

            /* ON PAGE UPDATE/PUBLISH, SAVE CUSTOM DATA IN DATABASE */
            add_action( 'save_post', array( $this, 'save' ) );


            /* CUSTOMISE THE COLUMNS TO SHOW IN ADMIN AREA */
            /*
                Filter should always be "manage_edit-{$post-type}_columns"
                    - add_filter( "manage_edit-testimonials_columns", "testimonials_edit_columns" );
                Same thing for Action:
                    - add_action( "manage_{$post-type}_posts_custom_column", "testimonials_custom_columns" ); 

                Details & Usage:
                    - Filter: http://codex.wordpress.org/Plugin_API/Filter_Reference/manage_edit-post_type_columns
                    - Action: http://codex.wordpress.org/Plugin_API/Action_Reference/manage_posts_custom_column
            */

            /* DEFINE VISIBLE FIELDS */
            add_filter( 'manage_edit-' . $this->name . '_columns', array( $this, 'edit_columns' ) );

            /* ASSOCIATE DATAS TO FIELDS */
            add_action( 'manage_' . $this->name_plurial . '_posts_custom_column',  array( $this, 'custom_columns' ), 10, 2 ); 


            /* GENERATE SHORT CODE */
            /*
                In the post content shortcode to insert: [testimonial from='0%' to='50%']
                Percentages used here to manage columns such as :
                    - Column 1 : [testimonial from='0%' to='33%']
                    - Column 2 : [testimonial from='33%' to='66%']
                    - Column 3 : [testimonial from='66%' to='100%']
            */
            add_shortcode( $this->name, array( $this, 'shortcode' ) );
        }

        public function register() {

            /*
            Double underscore used for translation
            http://codex.wordpress.org/Translating_WordPress#Localization_Technology
            */
            $labels = array(
                'name'                => __( $this->label_plurial ),
                'singular_name'       => __( $this->label ),
                'add_new_item'        => __( 'Add New ' . $this->label ),
                'edit_item'           => __( 'Edit ' . $this->label ),
                'new_item'            => __( 'New ' . $this->label ),
                'view_item'           => __( 'View ' . $this->label ),
                'search_items'        => __( 'Search ' . $this->label_plurial ),
                'not_found'           => __( 'No ' . $this->label_plurial . ' found' ),
                'not_found_in_trash'  => __( 'No ' . $this->label_plurial . ' found in trash' ),
                'menu_name'           => __( $this->label_plurial )
            );

            $args = array(
                'label'               => __( $this->label_plurial ),
                'labels'              => $labels,
                'show_ui'             => true,
                'capability_type'     => 'post',
                'hierarchical'        => false,
                'exclude_from_search' => true,
                'supports'            => array( 'title' )
               );  
        
            register_post_type( $this->name , $args );
        }

        public function change_title( $title ) {
            $screen = get_current_screen();
            if ( $this->name == $screen->post_type )
                $title = 'Enter customer name here';

            return $title;
        }

        public function meta_box() {
            add_meta_box( $this->name . '-options', $this->label_plurial, array( $this, 'meta' ), $this->name, 'normal', 'low' );
        }

        public function meta( $post ) {
            global $post;
            $post_id = $post->ID;
            
            //Get datas from DB:
            /*
                Extract function: set array key as variable name and associate values
                Example:
                    $datas         = get_post_meta($post_id, $this->name, true);
                    $date         = $datas['date'];

                is equivalent to 
                    extract( get_post_meta($post_id, $this->name, true) );
            */
            $metas = get_post_meta( $post_id, $this->name, true );
            if( $metas )
                extract( $metas );

            ?>
            <style>
                label { vertical-align: middle; }
            </style>
            <?php wp_nonce_field( plugin_basename( __FILE__ ), $this->name . '_nonce' ); ?>
            <table class="form-table">
                <tr valign="top"><th scope="row"><label for="date">Date of the review</label></th><td><input type="text" value="<?php echo ( isset( $date ) ) ? $date : ''; ?>" name="date" id="date" /></td></tr>
                <tr valign="top"><th scope="row"><label for="comment">Comment</label></th><td><textarea name="comment" id="comment" cols="100" rows="5"><?php echo ( isset( $comment ) ) ? $comment : ''; ?></textarea></td></tr>
                <tr valign="top"><th scope="row"><label for="company">Company</label></th><td><input type="text" value="<?php echo ( isset( $company ) ) ? $company : ''; ?>" name="company" id="company" /></td></tr>
                <tr valign="top"><th scope="row"><label for="website">Website</label></th><td><input type="text" value="<?php echo ( isset( $website ) ) ? $website : ''; ?>" name="website" id="website" /></td></tr>
            </table>

            <?php
        }

        public function save( $post_id ) {

            //Define auto-save:
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
                return $post_id;

            //Security check, data comming from the right form:
            if ( ! isset( $_POST[ $this->name . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ $this->name . '_nonce' ], plugin_basename( __FILE__ ) ) )
                return;

              //Check permission:
            if ( ! current_user_can( 'edit_posts' ) ) 
                return;
            
            //Date stored in variable using "if" condition (short method)
            $date    = ( isset( $_REQUEST['date'] ) ) ? $_POST['date'] : '';
            $comment = ( isset( $_REQUEST['comment'] ) ) ? $_REQUEST['comment'] : '';
            $company = ( isset( $_REQUEST['company'] ) ) ? $_REQUEST['company'] : '';
            $website = ( isset( $_REQUEST['website'] ) ) ? $_REQUEST['website'] : '';
            

            //Datas stored as an array:
            $metas = array(
                'date'    => $date,
                'comment' => $comment,
                'company' => $company,
                'website' => $website
            );

            //Insert data in DB:
            update_post_meta( $post_id, $this->name, $metas );
        }

        //Define visible fields:
        public function edit_columns( $columns ) {
            return array(
                'cb'      => '<input type="checkbox" />',
                'title'   => __( 'Customer name' ),
                'comment' => __( 'Comments' ),
                'company' => __( 'Company' )
            );
        }

        //Associate datas to fields:
        public function custom_columns( $col, $post_id ) {
            $metas = extract( get_post_meta( $post_id, $this->name, true ) );
            
            switch ( $col ) {
                case 'title':
                    the_title();
                    break;
                case 'comment':
                    echo ( isset( $metas ) ) ? $metas : '';
                    break;
                case 'company':
                    echo ( isset( $company ) ) ? $company : '';
                    break;
            }
        }

        public function shortcode( $atts ) {
            global $post;
            
            //Extract attributes and set default value if not set
            extract( shortcode_atts( array(
                'from'   => '0%', //0, 50%, 10
                'to'     => '100%' //50%, 100%, 100
            ), $atts ) );
            
            //Get number of testimonials available:
            $total_posts = wp_count_posts( $this->name );
            
            //Generate Query:
            $args = array(
                'post_type'      => $this->name,
                'post_status'    => 'publish',
                'offset'         => ( strpos( $from, '%' ) ) ? intval( $total_posts->publish * str_replace( '%', '', $from ) / 100 ) : $from,
                'posts_per_page' => ( strpos( $to, '%' ) ) ? intval( $total_posts->publish * str_replace( '%', '', $to ) / 100 ) : $to,
                'order'          => 'ASC',
                'orderby'        => 'date'
            );
            $query = new WP_Query( $args );
            
            $output = '<div class="' . $this->name_plurial . '">';
                    
            if ( $query->have_posts() ) :
                while ($query->have_posts()):
                    $query->the_post();
            
                    //Get meta datas from DB:
                    extract( get_post_meta( $post->ID, $this->name, true ) );    
                    
                    //Generate Output:
                    $title   = ( ! empty( $title ) )   ? '&mdash; <span class="name"> ' . get_the_title() . '</span>' : '';
                    $date    = ( ! empty( $date ) )    ? '<span class="date">on ' . $date . '</span>' : '';
                    $company = ( ! empty( $company ) ) ? ', <span class="company"> ' . $company . '</span>' : '';
                    $website = ( ! empty( $website ) ) ? ' - <span class="website"> ' . $website . '</span>' : '';

                    $output .= '<div class="' . $this->name . '">
                                    <div class="comment">' . $comment . '</div>
                                        ' . $comment . '
                                        ' . $date . '
                                        ' . $company . '
                                        ' . $website . '
                                </div>';
                        
                endwhile;
            else:
                
                $ouput .= '<p>' . __( 'Woops! No ' . $this->label . ' availables.' ) . '</p>'; 

            endif;
            
            $output .= '</div>';
            
            wp_reset_query();
            
            return $output;
        }
    }
}
?>