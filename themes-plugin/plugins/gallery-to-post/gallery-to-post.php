<?php
/*
Plugin Name: Gallery To Post
Version: 0.1
Description: Add Gallery Management to post.
Shortcode: [gallery_to_post]
Author: Etienne Tremel
*/

if ( ! class_exists( 'Gallery_To_Post' ) ) {
	class Gallery_To_Post {
		public function __construct() {
			/* DISPLAY CUSTOM FIELDS */
			add_action( 'add_meta_boxes', array( $this, 'gallery_to_post_meta_box' ) );

			/* ON PAGE UPDATE/PUBLISH, SAVE CUSTOM DATA IN DATABASE */
			add_action( 'save_post', array( $this, 'save_gallery_to_post' ) );

			/* REGISTER SCRIPTS & STYLE */
			add_action( 'admin_init', array( $this, 'register_gallery_to_post_admin_scripts' ) );
			add_action('admin_enqueue_scripts', array( $this, 'enqueue_gallery_to_post_admin_scripts' ) );

			/* GENERATE SHORT CODE */
			add_shortcode('gallery_to_post', array( $this, 'shortcode_gallery_to_post' ) );
		}

		public function gallery_to_post_meta_box(){
			add_meta_box( 'gallery_to_post-items', 'Gallery Items', array( $this, 'gallery_to_post_metas'), 'page', 'normal', 'low' );
			add_meta_box( 'gallery_to_post-items', 'Gallery Items', array( $this, 'gallery_to_post_metas'), 'post', 'normal', 'low' );
		}  
	
		public function gallery_to_post_metas( $post ) {
			global $post;

			$post_id = $post->ID;
	        
			//Get datas from DB:
			$items = get_post_meta( $post_id, 'gallery_to_post', true );
			?>
			<div id="gallery_to_post" data-post-id="<?php echo $post_id; ?>">
				<input type="hidden" name="gallery_to_post_info_nonce" value="<?php echo 'gallery_to_post_info_nonce' . $post_id; ?>" />
	            <div class="shortcode">
		            <p>Copy this code and paste it into where you would like to display the gallery.</p>
		            <p class="sc">[gallery_to_post post_id="<?php echo $post_id; ?>"]</p>
	            </div>

				<div class="items">
					<?php if ( $items ): foreach ( $items as $key => $item ): ?>
					<div class="item">
		            	<div class="image">
		                    <div class="thumb"><?php echo wp_get_attachment_image( $item['image_id'] ); ?></div>
		                    <div class="field"><p><label>Image <?php echo $key+1; ?></label></p><p><input type="hidden" name="images_id[]" value="<?php echo $item['image_id']; ?>" /> <button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>
		                </div>
		                <div class="metas">
		                    <p><label>Caption:</label></p>
		                    <p><textarea name="captions[]" cols="50" rows="5"><?php echo ( isset( $item['caption'] ) ) ? $item['caption'] : ''; ?></textarea></p>
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
		                    <p><textarea name="captions[]" id="text" cols="50" rows="5"></textarea></p>
		                </div>
					</div>
					<?php endif; ?>
				</div>
				<div class="clear"></div>
	   			<button class="add-new-item button button-highlighted button-primary" type="button">Add a new item</button>
			</div>
	        <?php
		}

		public function save_gallery_to_post( $post_id ){
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;
		
			//Security check, data comming from the right form:
			if ( ! isset( $_POST['gallery_to_post_info_nonce'] ) || ! 'gallery_to_post_info_nonce' . $post_id == $_POST['gallery_to_post_info_nonce'] )
				return $post_id;
			
			//Date stored in variable using "if" condition (short method)
			$images_id 	= ( isset( $_REQUEST['images_id'] ) ) ? $_REQUEST['images_id'] : '';
			$captions 	= ( isset( $_REQUEST['captions'] ) ) ? $_REQUEST['captions'] : '';

			if ( $images_id ) {
				$items = array();
				foreach ( $images_id as $key => $image_id ) {
					if ( $image_id ) {
						$items[] = array(
							'image_id'	=> $image_id,
							'caption'	=> $captions[ $key ]
						);
					}
				}

				//Insert data in DB:
				update_post_meta( $post_id, "gallery_to_post", $items );
			}

			//Insert data in DB:
			update_post_meta( $post_id, "gallery_to_post", $items );
		}
	
		public function register_gallery_to_post_admin_scripts() {
			wp_register_script( 'gallery_to_post_admin_script', TP_PLUGIN_DIRECTORY_WWW . '/gallery-to-post/assets/gallery-to-post-admin.js',  array('media-upload', 'thickbox', 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-draggable','jquery-ui-droppable'));
			wp_register_style( 'gallery_to_post_admin_style', TP_PLUGIN_DIRECTORY_WWW . '/gallery-to-post/assets/gallery-to-post-admin.css' );
		}
	
		public function enqueue_gallery_to_post_admin_scripts() {
			wp_enqueue_script( 'gallery_to_post_admin_script' );
			wp_enqueue_style( 'gallery_to_post_admin_style' );
			wp_enqueue_style( 'thickbox' );
		}

		public function shortcode_gallery_to_post( $atts ) {
			global $post;

			//Extract attributes and set default value if not set
			extract( shortcode_atts( array(
				'post_id' 	=> $post->ID
			), $atts ) );

			//Get meta datas from DB:
			$items = get_post_meta( $post_id, 'gallery_to_post', true );
			
			$output = '<div id="gallery_to_post-' . $post_id . '" class="gallery_to_post">';

			if ( $items ): foreach ( $items as $key => $item ) :

				$thumb = wp_get_attachment_image_src( $item['image_id'], 'thumbnail' );
				$large = wp_get_attachment_image_src( $item['image_id'], 'large' );

				$output .= '<div class="item">
								<div class="caption">
									' . $item['caption'] . '
								</div>
								<div class="image">
									<a href="' . $large[0] . '"><img src="' . $thumb[0] . '" border="0" /></a>
								</div>
							</div>';

			endforeach; endif;

			$output .= '</div>';
			
			return $output;
		}
	}
}
?>