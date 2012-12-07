<?php
/*
Plugin Name: Banner
Version: 0.1
Description: Add Custom Banner Using Bootstrap Transition & Carousel jQuery Script
Author: Etienne Tremel
*/

if ( ! class_exists( 'Banner' ) ) {
	class Banner {
		public function __construct() {
			/* REGISTER CUSTOM POST TYPE */
			add_action( 'init', array( $this, 'banner_register' ) );

			/* CHANGE DEFAULT CUSTOM TITLE (Change placeholder value when editing/adding a new post) */
			add_filter( 'enter_title_here', array( $this, 'banner_change_title' ) );

			/* DISPLAY CUSTOM FIELDS */
			add_action( 'add_meta_boxes', array( $this, 'banner_meta_box' ) );

			/* ON PAGE UPDATE/PUBLISH, SAVE CUSTOM DATA IN DATABASE */
			add_action( 'save_post', array( $this, 'save_banner' ) ); 

			/* CUSTOMISE THE COLUMNS TO SHOW IN ADMIN AREA */
			//Define visible fields:
			add_filter( 'manage_edit-banner_columns', array( $this, 'banner_edit_columns' ) );  
			//Associate datas to fields:
			add_action( 'manage_banner_posts_custom_column',  array( $this, 'banner_custom_columns' ), 10, 2 ); 

			/* REGISTER SCRIPTS & STYLE */
			add_action( 'admin_init', array( $this, 'register_banner_admin_scripts' ) );
			add_action('admin_enqueue_scripts', array( $this, 'enqueue_banner_admin_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_banner_frontend_scripts' ) );

			/* GENERATE SHORT CODE */
			add_shortcode('banner', array( $this, 'shortcode_banner' ) );
		}

		public function banner_register() {
			$labels = array(
				'name'					=> __( 'Banners' ),
				'singular_name'			=> __( 'Banner' ),
				'add_new_item'			=> __( 'Add New Banner' ),
				'edit_item'				=> __( 'Edit Banner' ),
				'new_item'				=> __( 'New Banner' ),
				'view_item'				=> __( 'View Banner' ),
				'search_items'			=> __( 'Search Banners' ),
				'not_found'				=> __( 'No banners found' ),
				'not_found_in_trash'	=> __( 'No banners found in trash' ),
				'menu_name'				=> __( 'Banners' )
			);

			$args = array(
				'label' 				=> __( 'Banners' ),
				'labels' 				=> $labels,
				'public' 				=> true,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'hierarchical' 			=> false,
				'exclude_from_search' 	=> true,
				'supports' 				=> array( 'title' )
			   );  
		
			register_post_type( 'banner' , $args );
		}

		public function banner_change_title( $title ) {
			$screen = get_current_screen();
			if ( 'banner' == $screen->post_type ) $title = 'Enter banner here';
			return $title;
		}

		public function banner_meta_box(){
			add_meta_box( 'banner-items', 'Banner Items', array( $this, 'banner_metas' ), 'banner', 'normal', 'low' );
		}  
	
		public function banner_metas( $post ){
			global $post;
			$post_id = $post->ID;
	        
			//Get datas from DB:
			$items = get_post_meta( $post_id, 'banner', true );
			?>
			<div id="banner" data-post-id="<?php echo $post_id; ?>">
				<input type="hidden" name="banner_info_nonce" value="<?php echo 'banner_info_nonce' . $post_id; ?>" />
	            <div class="shortcode">
		            <p>Copy this code and paste it into your post, page or text widget content.</p>
		            <p class="sc">[banner id="<?php echo $post_id; ?>"]</p>
	            </div>

				<div class="items">
					<?php if ( $items ): foreach ( $items as $key => $item ): ?>
					<div class="item">
		            	<div class="image">
		                    <div class="thumb"><?php echo wp_get_attachment_image( $item['image_id'] ); ?></div>
		                    <div class="field"><p><label>Image <?php echo $key+1; ?></label></p><p><input type="hidden" name="images_id[]" value="<?php echo $item['image_id']; ?>" /> <button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>
		                </div>
		                <div class="metas">
		                    <p><label for="text">Text:</label></p>
		                    <p><textarea name="texts[]" id="text" cols="50" rows="5"><?php echo ( isset( $item['text'] ) ) ? $item['text'] : ''; ?></textarea></p>
		                </div>
					</div>
					<?php endforeach; else : ?>
					<div class="item">
		            	<div class="image">
		                    <div class="thumb"></div>
		                    <div class="field"><p><label>Image 1</label></p><p><input type="hidden" name="images_id[]" value="" /> <button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>
		                </div>
		                <div class="metas">
		                    <p><label for="text">Text:</label></p>
		                    <p><textarea name="texts[]" id="text" cols="50" rows="5"></textarea></p>
		                </div>
					</div>
					<?php endif; ?>
				</div>
				<div class="clear"></div>
	   			<button class="add-new-item button button-highlighted button-primary" type="button">Add a new item</button>
			</div>
	        <?php
		}

		public function save_banner( $post_id ){
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;
		
			//Security check, data comming from the right form:
			if ( ! isset( $_POST['banner_info_nonce'] ) || ! 'banner_info_nonce' . $post_id == $_POST['banner_info_nonce'] )
				return $post_id;
			
			//Date stored in variable using "if" condition (short method)
			$images_id 	= ( isset( $_REQUEST['images_id'] ) ) ? $_REQUEST['images_id'] : '';
			$texts 		= ( isset( $_REQUEST['texts'] ) ) ? $_REQUEST['texts'] : '';

			$items = array();
			foreach ( $images_id as $key => $image_id ) {
				if ( $image_id ) {
					$items[] = array(
						'image_id'	=> $image_id,
						'text'		=> $texts[ $key ]
					);
				}
			}

			//Insert data in DB:
			update_post_meta( $post_id, "banner", $items );
		}

		public function banner_edit_columns( $columns ){
			return array(
				'cb' 		=> '<input type="checkbox" />',
				'title' 	=> __( 'Banner name' ),
				'items' 	=> __( 'Items' )
			);
		}

		public function banner_custom_columns( $col, $post_id ){
			$items = get_post_meta( $post_id, 'banner', true );
			
			switch ( $col ) {
				case 'title':
					the_title();
					break;
				case 'items':
					echo sizeof( $items );
					break;
			}
		}

		public function register_banner_admin_scripts() {
			wp_register_script( 'banner_admin_script', TP_PLUGIN_DIRECTORY_WWW . '/banner/assets/banner-admin.js',  array('media-upload', 'thickbox', 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-draggable','jquery-ui-droppable'));
			wp_register_style( 'banner_admin_style', TP_PLUGIN_DIRECTORY_WWW . '/banner/assets/banner-admin.css' );
		}
	
		public function enqueue_banner_admin_scripts() {
			wp_enqueue_script( 'banner_admin_script' );
			wp_enqueue_style( 'banner_admin_style' );
			wp_enqueue_style( 'thickbox' );
		}

		public function enqueue_banner_frontend_scripts() {
		    wp_register_script( 'banner_frontend_script', TP_PLUGIN_DIRECTORY_WWW . '/banner/assets/banner-frontend.js',  array( 'jquery' ));
			wp_register_style( 'banner_frontend_style', TP_PLUGIN_DIRECTORY_WWW . '/banner/assets/banner-frontend.css' );

		    wp_enqueue_script( 'banner_frontend_script' );
		    wp_enqueue_style( 'banner_frontend_style' );
		}

		public function shortcode_banner( $atts ) {
			global $post;
			
			//Extract attributes and set default value if not set
			extract( shortcode_atts( array(
				'id' 			=> '',
				'navigation' 	=> true //Display navigation or not
			), $atts ) );
			
			//Generate Query:
			$args = array(
	            'post_type' 		=> 'banner',
	            'page_id'			=> $id,
	            'post_status' 		=> 'publish',
				'posts_per_page'	=> -1,
				'order' 			=> 'ASC'
	        );
	        $query = new WP_Query( $args );
			
			if ( $query->have_posts() ) :
				$output .= '<div id="carousel_' . $post->ID . '" class="carousel slide">
								<div class="carousel-inner">';

	        	while ($query->have_posts()):
					$query->the_post();
			
					//Get meta datas from DB:
					$items = get_post_meta( $post->ID, 'banner', true );

					if ( $items ): foreach ( $items as $key => $item ) :

						$output .= '<div class="' . ( ( $key ) ? '' : 'active ' ) . 'item">
										<div class="wrapper">
											' . $item['text'] . '
										</div>
										<div class="image">
											<img src="' . wp_get_attachment_url( $item['image_id'] ) . '" border="0" />
										</div>
									</div>';

					endforeach; endif;
						
				endwhile;

				$output .= '	</div>
							</div>';
			else:
				
				$ouput .= '<p>' . __( 'Woops! No banner items available.' ) . '</p>'; 

			endif;

			wp_reset_query();
			
			return $output;
		}
	}
}
?>