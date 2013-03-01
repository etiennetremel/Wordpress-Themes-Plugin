<?php
/*
Plugin Name: Team
Version: 0.1
Description: Add Staff Management
Shortcode: [team] | [team staff_id="54"] | [team staff_per_page="5"] | [team order="DESC"]
Author: Etienne Tremel
*/

if ( ! class_exists( 'Team' ) ) {
	class Team {

		private $name = 'team';
		private $name_plurial, $label, $label_plurial;

		public function __construct() {
			/* REGISTER CUSTOM POST TYPE */
			add_action( 'init', array( $this, 'register' ) );

			/* CHANGE DEFAULT CUSTOM TITLE (Change placeholder value when editing/adding a new post) */
			add_filter( 'enter_title_here', array( $this, 'change_title' ) );

			/* DISPLAY CUSTOM FIELDS */
			add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );

			/* ON PAGE UPDATE/PUBLISH, SAVE CUSTOM DATA IN DATABASE */
			add_action( 'save_post', array( $this, 'save_staff' ) );

			/* CUSTOMISE THE COLUMNS TO SHOW IN ADMIN AREA */
			//Define visible fields:
			add_filter( 'manage_edit-staff_columns', array( $this, 'edit_columns' ) );

			//Associate datas to fields:
			add_action( 'manage_staff_posts_custom_column',  array( $this, 'custom_columns' ), 10, 2 );

			/* GENERATE SHORT CODE */
			add_shortcode('team', array( $this, 'shortcode' ) );
		}

		public function staff_register() {
			$labels = array(
				'name'					=> __( $this->label_plurial ),
				'singular_name'			=> __( $this->label ),
				'add_new_item'			=> __( 'Add New ' . $this->label ),
				'edit_item'				=> __( 'Edit ' . $this->label ),
				'new_item'				=> __( 'New ' . $this->label ),
				'view_item'				=> __( 'View ' . $this->label ),
				'search_items'			=> __( 'Search ' . $this->label_plurial ),
				'not_found'				=> __( 'No ' . $this->label_plurial . ' found' ),
				'not_found_in_trash'	=> __( 'No ' . $this->label_plurial . ' found in trash' ),
				'menu_name'				=> __( $this->label_plurial )
			);

			$args = array(
				'label' 				=> __( $this->label_plurial ),
				'labels' 				=> $labels,
				'public' 				=> true,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'hierarchical' 			=> false,
				'exclude_from_search' 	=> true,
				'supports' 				=> array( 'title', 'editor', 'thumbnail', 'page-attributes' )
			   );  
		
			register_post_type( $this->name, $args );
		}

		public function change_title( $title ) {
			$screen = get_current_screen();
			if ( $this->name == $screen->post_type ) $title = 'Enter staff name here';
			return $title;
		}

		public function meta_box() {
			add_meta_box( $this->name . '-items', $this->label . ' Informations', array( $this, 'meta' ), $this->name, 'normal', 'low' );
		}  
		
		public function meta( $post ) {
			global $post;
			$post_id = $post->ID;
	        
			//Get datas from DB:
			$metas = get_post_meta( $post_id, $this->name, true );
			if( $metas )
				extract( $metas );

			?>
			<style>
				label { vertical-align: middle; }
			</style>
			<?php wp_nonce_field( plugin_basename( __FILE__ ), $this->name . '_nonce' ); ?>
			<table class="form-table">
				<tr valign="top"><th scope="row"><label for="position">Position</label></th><td><input type="text" value="<?php echo ( isset( $position ) ) ? $position : ''; ?>" name="position" id="position" /></td></tr>
	            <tr valign="top"><th scope="row"><label for="email">Email</label></th><td><input type="text" value="<?php echo ( isset( $email ) ) ? $email : ''; ?>" name="email" id="email" /></td></tr>
			</table>
	        <?php
		}

		public function save( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;

			//Security check, data comming from the right form:
	        if ( ! isset( $_POST[ $this->name . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ $this->name . '_nonce' ], plugin_basename( __FILE__ ) ) )
      			return;

      		//Check permission:
			if ( ! current_user_can( 'edit_posts' ) ) 
	        	return;
			
			//Date stored in variable using "if" condition (short method)
			$position 	= ( isset( $_REQUEST['position'] ) ) ? $_REQUEST['position'] : '';
			$email 		= ( isset( $_REQUEST['email'] ) ) ? $_REQUEST['email'] : '';

			$metas = array(
				'position'	=> $position,
				'email'		=> $email
			);

			//Insert data in DB:
			update_post_meta( $post_id, $this->name, $metas );
		}

		public function edit_columns( $columns ) {
			return array(
				'cb' 		=> '<input type="checkbox" />',
				'title' 	=> __( 'Name' ),
				'position' 	=> __( 'Position' )
			);
		}

		public function custom_columns( $col, $post_id ) {
			$metas = get_post_meta( $post_id, $this->name, true );

			if ( $metas )
				$metas = extract( $metas );
			
			switch ( $col ) {
				case 'title':
					the_title();
					break;
				case 'position':
					( isset( $position ) ) ? $position : 'n/a';
					break;
			}
		}

		public function shortcode( $atts ) {
			global $post;
			
			//Extract attributes and set default value if not set
			extract( shortcode_atts( array(
				'staff_id' 			=> '',
				'staff_per_page'	=> -1,
				'order' 			=> 'ASC'
			), $atts ) );
			
			//Generate Query:
			$args = array(
	            'post_type' 		=> $this->name,
	            'page_id'			=> $staff_id,
	            'post_status' 		=> 'publish',
				'posts_per_page'	=> $staff_per_page,
				'order' 			=> $order
	        );
	        $query = new WP_Query( $args );
			
			if ( $query->have_posts() ) :
				$output = '<div id="team">';

	        	while ( $query->have_posts() ): $query->the_post();
			
					//Get meta datas from DB:
					$metas = extract( get_post_meta( $post->ID, $this->label, true ) );

					$name 		= '<p class="name">' . get_the_title() . '</p>';
					$position 	= ( isset( $position ) ) 	? '<p class="position">' . $position . '</p>' : '';
					$email 		= ( isset( $email ) ) 		? '<p class="email"><a href="mailto:' . $email . '">' . $email . '</a></p>' : '';

					$output .= '<div class="staff">
									<div class="thumb">
									' . ( ( has_post_thumbnail( $post->ID ) ) ? get_the_post_thumbnail( $post->ID ) : '' ) . '
									</div>
									<div class="metas">
										' . $name . '
										' . $position. '
										' . $email . '
									</div>
								</div>';
						
				endwhile;

				$output .= '</div>';
			else:
				
				$ouput .= '<p>' . __( 'Woops! No ' . $this->label . ' found.' ) . '</p>'; 

			endif;

			wp_reset_query();
			
			return $output;
		}
	}
}
?>