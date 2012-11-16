<?php
/*
Plugin Name: Team
Version: 0.1
Description: Add Staff Management
Shortcode: [team] | [team staff_id="54"] | [team staff_per_page="5"] | [team order="DESC"]
Author: Etienne Tremel
*/

	/* REGISTER CUSTOM POST TYPE */
	add_action('init', 'staff_register');  

	function staff_register() {
		$labels = array(
			'name'					=> __( 'Staff' ),
			'singular_name'			=> __( 'Staff' ),
			'add_new_item'			=> __( 'Add New Staff' ),
			'edit_item'				=> __( 'Edit Staff' ),
			'new_item'				=> __( 'New Staff' ),
			'view_item'				=> __( 'View Staff' ),
			'search_items'			=> __( 'Search Staff' ),
			'not_found'				=> __( 'No Staff found' ),
			'not_found_in_trash'	=> __( 'No Staff found in trash' ),
			'menu_name'				=> __( 'Team' )
		);

		$args = array(
			'labels' 				=> $labels,
			'public' 				=> true,
			'show_ui' 				=> true,
			'capability_type' 		=> 'post',
			'hierarchical' 			=> false,
			'exclude_from_search' 	=> true,
			'supports' 				=> array( 'title', 'editor', 'thumbnail', 'page-attributes' )
		   );  
	
		register_post_type( 'staff' , $args );
	}

	
	/* CHANGE DEFAULT CUSTOM TITLE (Change placeholder value when editing/adding a new post) */
	add_filter( 'enter_title_here', 'staff_change_title' );
	
	function staff_change_title( $title ) {
		$screen = get_current_screen();
		if ( 'staff' == $screen->post_type ) $title = 'Enter staff name here';
		return $title;
	}

	
	/* DISPLAY CUSTOM FIELDS */
	add_action( 'add_meta_boxes', 'staff_meta_box' );   

	function staff_meta_box(){
		add_meta_box( 'staff-items', 'Staff Informations', 'staff_metas', 'staff', 'normal', 'low' );
	}  
	
	function staff_metas( $post ){
		global $post;
		$post_id = $post->ID;
        
		//Get datas from DB:
		$metas = get_post_meta( $post_id, 'staff', true );
		if( $metas )
			extract( $metas );

		?>
		<style>
			label { vertical-align: middle; }
		</style>
		<input type="hidden" name="staff_info_nonce" value="<?php echo 'staff_info_nonce' . $post_id; ?>" />
		<table class="form-table">
			<tr valign="top"><th scope="row"><label for="position">Position</label></th><td><input type="text" value="<?php echo ( isset( $position ) ) ? $position : ''; ?>" name="position" id="position" /></td></tr>
            <tr valign="top"><th scope="row"><label for="email">Email</label></th><td><input type="text" value="<?php echo ( isset( $email ) ) ? $email : ''; ?>" name="email" id="email" /></td></tr>
		</table>
        <?php
	}

	
	/* ON PAGE UPDATE/PUBLISH, SAVE CUSTOM DATA IN DATABASE */
	add_action( 'save_post', 'save_staff' ); 

	function save_staff( $post_id ){
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
	
		//Security check, data comming from the right form:
		if ( ! isset( $_POST['staff_info_nonce'] ) || ! $_POST['staff_info_nonce'] = 'staff_info_nonce' . $post_id ) return $post_id;
		
		//Date stored in variable using "if" condition (short method)
		$position 	= ( isset( $_REQUEST['position'] ) ) ? $_REQUEST['position'] : '';
		$email 		= ( isset( $_REQUEST['email'] ) ) ? $_REQUEST['email'] : '';

		$metas = array(
			'position'	=> $position,
			'email'		=> $email
		);

		//Insert data in DB:
		update_post_meta( $post_id, "staff", $metas );
	}
	
	
	/* CUSTOMISE THE COLUMNS TO SHOW IN ADMIN AREA */
	//Define visible fields:
	add_filter( 'manage_edit-staff_columns', 'staff_edit_columns' );  

	function staff_edit_columns( $columns ){
		return array(
			'cb' 		=> '<input type="checkbox" />',
			'title' 	=> __( 'Name' ),
			'position' 	=> __( 'Position' )
		);
	}
	
	//Associate datas to fields:
	add_action( 'manage_staff_posts_custom_column',  'staff_custom_columns', 10, 2 ); 

	function staff_custom_columns( $col, $post_id ){
		$metas = extract( get_post_meta( $post_id, 'staff', true ) );
		
		switch ( $col ) {
			case 'title':
				the_title();
				break;
			case 'position':
				$position;
				break;
		}
	}

	/* GENERATE SHORT CODE */
	add_shortcode('team', 'shortcode_staff');

	function shortcode_staff( $atts ) {
		global $post;
		
		//Extract attributes and set default value if not set
		extract( shortcode_atts( array(
			'staff_id' 			=> '',
			'staff_per_page'	=> -1,
			'order' 			=> 'ASC'
		), $atts ) );
		
		//Generate Query:
		$args = array(
            'post_type' 		=> 'staff',
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
				$metas = extract( get_post_meta( $post->ID, 'staff', true ) );

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
			
			$ouput .= '<p>' . __( 'Woops! No staff found.' ) . '</p>'; 

		endif;

		wp_reset_query();
		
		return $output;
	}
?>