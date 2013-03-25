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

		private $name = 'gallery-to-post';
		private $name_plurial, $label, $label_plurial;

		public function __construct() {

			/* INITIALIZE VARIABLES */
			$this->name_plurial = $this->name . 's';
			$this->label = ucwords( preg_replace( '/[_.-]+/', ' ', $this->name ) );
			$this->label_plurial = ucwords( preg_replace( '/[_.-]+/', ' ', $this->name_plurial ) );

			/* DISPLAY CUSTOM FIELDS */
			add_action( 'add_meta_boxes', array( $this, 'meta_box' ) );

			/* ON PAGE UPDATE/PUBLISH, SAVE CUSTOM DATA IN DATABASE */
			add_action( 'save_post', array( $this, 'save' ) );

			/* REGISTER SCRIPTS & STYLE */
			add_action( 'admin_init', array( $this, 'register_admin_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			/* GENERATE SHORT CODE */
			add_shortcode( 'gallery_to_post', array( $this, 'shortcode' ) );
		}

		public function meta_box() {
			$screens = array( 'post', 'page' );
			foreach ( $screens as $screen )
				add_meta_box( $this->name . '-items', 'Gallery Items', array( $this, 'meta' ), $screen );
		}  
	
		public function meta( $post ) {
			global $post;

			$post_id = $post->ID;
	        
			//Get datas from DB:
			$items = get_post_meta( $post_id, $this->name, true );
			?>
			<div id="gallery_to_post" data-post-id="<?php echo $post_id; ?>">

				<?php wp_nonce_field( plugin_basename( __FILE__ ), $this->name . '_nonce' ); ?>

	            <div class="shortcode">
		            <p>Copy this code and paste it into where you would like to display the gallery.</p>
		            <p class="sc">[gallery_to_post post_id="<?php echo $post_id; ?>"]</p>
	            </div>

				<div class="items">
					<?php if ( $items ): foreach ( $items as $index => $item ): ?>
					<div class="item">
		            	<div class="image">
		                    <div class="thumb"><?php echo wp_get_attachment_image( $item['image_id'] ); ?></div>
		                    <div class="field"><p><label>Image <?php echo $index+1; ?></label></p><p><input type="hidden" name="images_id[]" value="<?php echo $item['image_id']; ?>" /> <button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>
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
			$images_id 	= ( isset( $_REQUEST['images_id'] ) ) ? $_REQUEST['images_id'] : '';
			$captions 	= ( isset( $_REQUEST['captions'] ) ) ? $_REQUEST['captions'] : '';

			if ( $images_id ) {
				$items = array();
				foreach ( $images_id as $index => $image_id ) {
					if ( $image_id ) {
						$items[] = array(
							'image_id'	=> $image_id,
							'caption'	=> $captions[ $index ]
						);
					}
				}
			}

			//Insert data in DB:
			update_post_meta( $post_id, $this->name, $items );
		}
	
		public function register_admin_scripts() {
			wp_register_script( $this->name . '_admin_script', TP_PLUGIN_DIRECTORY_WWW . '/' . $this->name . '/assets/admin.js',  array('media-upload', 'thickbox', 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-draggable','jquery-ui-droppable'));
			wp_register_style( $this->name . '_admin_style', TP_PLUGIN_DIRECTORY_WWW . '/' . $this->name . '/assets/admin.css' );
		}
	
		public function enqueue_admin_scripts() {
			global $post_type;
			if ( $this->name == $post_type ) {
				wp_enqueue_media();

				wp_enqueue_script( $this->name . '_admin_script' );
				wp_enqueue_style( $this->name . '_admin_style' );
				wp_enqueue_style( 'thickbox' );
			}
		}

		public function shortcode( $atts ) {
			global $post;

			//Extract attributes and set default value if not set
			extract( shortcode_atts( array(
				'post_id' 	=> $post->ID
			), $atts ) );

			//Get meta datas from DB:
			$items = get_post_meta( $post_id, $this->name, true );
			
			$output = '<div id="gallery_to_post-' . $post_id . '" class="gallery_to_post">';

			if ( $items ): foreach ( $items as $key => $item ) :

				$thumb = wp_get_attachment_image_src( $item['image_id'], 'thumbnail' );
				$large = wp_get_attachment_image_src( $item['image_id'], 'large' );

				$output .= '<div class="item">
								<div class="caption">
									' . $item['caption'] . '
								</div>
								<div class="image">
									<a href="' . $large[0] . '" class="colorbox"><img src="' . $thumb[0] . '" border="0" /></a>
								</div>
							</div>';

			endforeach; endif;

			$output .= '</div>';
			
			return $output;
		}
	}
}
?>