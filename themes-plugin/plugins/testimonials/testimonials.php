<?php
/* 
Plugin Name: Testimonials
Version: 0.1
Description: Add customer feedback including comment, date, company name and website.
Author: Etienne Tremel
*/

if ( ! class_exists( 'Testimonials' ) ) {
	class Testimonials {
		public function __construct() {
			/* REGISTER CUSTOM POST TYPE */
			add_action( 'init', array( $this, 'testimonials_register' ) );

			/* CHANGE DEFAULT CUSTOM TITLE (Change placeholder value when editing/adding a new post) */
			add_filter( 'enter_title_here', array( $this, 'testimonials_change_title' ) );

			/* DISPLAY CUSTOM FIELDS */
			add_action( 'add_meta_boxes', array( $this, 'testimonials_meta_box' ) );

			/* ON PAGE UPDATE/PUBLISH, SAVE CUSTOM DATA IN DATABASE */
			add_action( 'save_post', array( $this, 'save_testimonials' ) );


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
			add_filter( 'manage_edit-testimonials_columns', array( $this, 'testimonials_edit_columns' ) );

			/* ASSOCIATE DATAS TO FIELDS */
			add_action( 'manage_testimonials_posts_custom_column',  array( $this, 'testimonials_custom_columns' ), 10, 2 ); 


			/* GENERATE SHORT CODE */
			/*
				In the post content shortcode to insert: [testimonials from='0%' to='50%']
				Percentages used here to manage columns such as :
					- Column 1 : [testimonials from='0%' to='33%']
					- Column 2 : [testimonials from='33%' to='66%']
					- Column 3 : [testimonials from='66%' to='100%']
			*/
			add_shortcode('testimonials', array( $this, 'shortcode_testimonials' ) );
		}

		public function testimonials_register() {

			/*
			Double underscore used for translation
			http://codex.wordpress.org/Translating_WordPress#Localization_Technology
			*/

			$labels = array(
				'name'					=> __( 'Testimonials' ),
				'singular_name'			=> __( 'Testimonial' ),
				'add_new_item'			=> __( 'Add New Testimonial' ),
				'edit_item'				=> __( 'Edit Testimonial' ),
				'new_item'				=> __( 'New Testimonial' ),
				'view_item'				=> __( 'View Testimonial' ),
				'search_items'			=> __( 'Search Testimonials' ),
				'not_found'				=> __( 'No testimonials found' ),
				'not_found_in_trash'	=> __( 'No testimonials found in trash' ),
				'menu_name'				=> __( 'Testimonials' )
			);

			$args = array(
				'label' 				=> __( 'Testimonials' ),
				'labels' 				=> $labels,
				'public' 				=> true,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'hierarchical' 			=> false,
				'exclude_from_search' 	=> true,
				'supports' 				=> array( 'title' )
			   );  
		
			register_post_type( 'testimonials' , $args );
		}

		public function testimonials_change_title( $title ) {
			$screen = get_current_screen();
			if ( 'testimonials' == $screen->post_type ) $title = 'Enter customer name here';
			return $title;
		}

		public function testimonials_meta_box(){
			add_meta_box( 'testimonials-options', 'Testimonials', 'testimonials_meta', 'testimonials', 'normal', 'low' );
		}

		public function testimonials_meta( $post ){
			global $post;
			$post_id = $post->ID;
	        
			//Get datas from DB:
			/*
				Extract function: set array key as variable name and associate values
				Example:
					$testimonials 		= get_post_meta($post_id, 'testimonials', true);
					$date 				= $testimonials['date'];

				is equivalent to 
					extract( get_post_meta($post_id, 'testimonials', true) );
			*/
			$metas = get_post_meta( $post_id, 'testimonials', true );
			if( $metas )
				extract( $metas );

			?>
			<style>
				label { vertical-align: middle; }
			</style>
			<input type="hidden" name="testimonials_info_nonce" value="<?php echo wp_create_nonce( 'testimonials_info_nonce' ); ?>" />
			<table class="form-table">
				<tr valign="top"><th scope="row"><label for="date">Date of the review</label></th><td><input type="text" value="<?php echo ( isset( $date ) ) ? $date : ''; ?>" name="date" id="date" /></td></tr>
				<tr valign="top"><th scope="row"><label for="comment">Comment</label></th><td><textarea name="comment" id="comment" cols="100" rows="5"><?php echo ( isset( $comment ) ) ? $comment : ''; ?></textarea></td></tr>
	            <tr valign="top"><th scope="row"><label for="company">Company</label></th><td><input type="text" value="<?php echo ( isset( $company ) ) ? $company : ''; ?>" name="company" id="company" /></td></tr>
	            <tr valign="top"><th scope="row"><label for="website">Website</label></th><td><input type="text" value="<?php echo ( isset( $website ) ) ? $website : ''; ?>" name="website" id="website" /></td></tr>
			</table>

	        <?php
		}

		public function save_testimonials( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;
		
			//Security check, data comming from the right form:
	        if ( ! isset( $_REQUEST['testimonials_info_nonce'] ) || ( isset( $_REQUEST['testimonials_info_nonce'] ) && ! wp_verify_nonce( $_REQUEST['testimonials_info_nonce'], 'testimonials_info_nonce' ) ) )
	        	return $post_id;
			
			//Date stored in variable using "if" condition (short method)
			$date 		= ( isset( $_REQUEST['date'] ) ) ? $_POST['date'] : '';
			$comment 	= ( isset( $_REQUEST['comment'] ) ) ? $_REQUEST['comment'] : '';
			$company	= ( isset( $_REQUEST['company'] ) ) ? $_REQUEST['company'] : '';
			$website 	= ( isset( $_REQUEST['website'] ) ) ? $_REQUEST['website'] : '';
			

			//Datas stored as an array:
			$testimonials = array(
				'date' 		=> $date,
				'comment' 	=> $comment,
				'company' 	=> $company,
				'website' 	=> $website
			);

			//Insert data in DB:
			update_post_meta( $post_id, "testimonials", $testimonials );
		}

		//Define visible fields:
		public function testimonials_edit_columns( $columns ) {
			return array(
				'cb' 		=> '<input type="checkbox" />',
				'title' 	=> __( 'Customer name' ),
				'comment' 	=> __( 'Comments' ),
				'company' 	=> __( 'Company' )
			);
		}

		//Associate datas to fields:
		public function testimonials_custom_columns( $col, $post_id ) {
			$testimonials = extract( get_post_meta( $post_id, 'testimonials', true ) );
			
			switch ( $col ) {
				case 'title':
					the_title();
					break;
				case 'comment':
					echo ( isset( $testimonials ) ) ? $testimonials : '';
					break;
				case 'company':
					echo ( isset( $company ) ) ? $company : '';
					break;
			}
		}

		public function shortcode_testimonials( $atts ) {
			global $post;
			
			//Extract attributes and set default value if not set
			extract( shortcode_atts( array(
				'from' 	=> '0%', //0, 50%, 10
				'to' 	=> '100%' //50%, 100%, 100
			), $atts ) );
			
			//Get number of testimonials available:
			$total_testimonials = wp_count_posts( 'testimonials' );
			
			//Generate Query:
			$args = array(
	            'post_type' 		=> 'testimonials',
	            'post_status' 		=> 'publish',
				'offset' 			=> ( strpos( $from, '%' ) ) ? intval ($total_testimonials->publish*str_replace( '%', '', $from ) / 100 ) : $from,
				'posts_per_page'	=> ( strpos( $to, '%' ) ) ? intval( $total_testimonials->publish*str_replace( '%', '', $to ) / 100 ) : $to,
				'order' 			=> 'ASC',
				'orderby' 			=> 'date'
	        );
	        $query = new WP_Query( $args );
			

			$output = '<div class="testimonials">';
					
			if ( $query->have_posts() ) :
	        	while ($query->have_posts()):
					$query->the_post();
			
					//Get meta datas from DB:
					$testimonials = extract( get_post_meta( $post->ID, 'testimonials', true ) );    
					
					//Generate Output:
					$title 		= ( ! empty( $title ) ) 	? '&mdash; <span class="name"> ' . get_the_title() . '</span>' : '';
					$date  		= ( ! empty( $date ) ) 		? '<span class="date">on ' . $date . '</span>' : '';
					$company 	= ( ! empty( $company ) ) 	? ', <span class="company"> ' . $company . '</span>' : '';
					$website 	= ( ! empty( $website ) ) 	? ' - <span class="website"> ' . $website . '</span>' : '';

					$output .= '<div class="testimonial">
									<div class="comment">' . $comment . '</div>
										' . $comment . '
										' . $date . '
										' . $company . '
										' . $website . '
								</div>';
						
				endwhile;
			else:
				
				$ouput .= '<p>' . __( 'Woops! No testimonials availables.' ) . '</p>'; 

			endif;
			
			$output .= '</div>';
			
			wp_reset_query();
			
			return $output;
		}
	}
}
?>