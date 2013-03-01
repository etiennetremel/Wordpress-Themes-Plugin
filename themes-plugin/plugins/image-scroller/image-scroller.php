<?php
/*
Plugin Name: Image Scroller
Version: 0.1
Description: Add a horizontal image scroller
shortcode: [image-scroller id="2"]
Author: Etienne Tremel
*/

if ( ! class_exists( 'Image_Scroller' ) ) {
	class Image_Scroller {

		private $name = 'image-scroller';
		private $name_plurial, $label, $label_plurial;

		public function __construct() {
			/* INITIALIZE VARIABLES */
			$this->name_plurial = $this->name . 's';
			$this->label = ucwords( preg_replace( '/[_.-]+/', ' ', $this->name ) );
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
			//Define visible fields:
			add_filter( 'manage_edit-' . $this->name . '_columns', array( $this, 'edit_columns' ) );  
			//Associate datas to fields:
			add_action( 'manage_' . $this->name . '_posts_custom_column',  array( $this, 'custom_columns' ), 10, 2 ); 

			/* REGISTER SCRIPTS & STYLE */
			add_action( 'admin_init', array( $this, 'register_admin_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );

			/* GENERATE SHORT CODE */
			add_shortcode( $this->name, array( $this, 'shortcode' ) );
		}

		public function register() {
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
				'supports' 				=> array( 'title' )
			   );  
		
			register_post_type( $this->name , $args );
		}

		public function change_title( $title ) {
			$screen = get_current_screen();
			if ( $this->name == $screen->post_type ) $title = 'Enter title here';
			return $title;
		}

		public function meta_box() {
			add_meta_box( $this->name . '-items', $this->label_plurial . ' Items', array( $this, 'metas' ), $this->name, 'normal', 'low' );
		}  
	
		public function metas( $post ) {
			global $post;
			$post_id = $post->ID;
	        
			//Get datas from DB:
			$items = get_post_meta( $post_id, $this->name, true );
			?>
			<div id="<?php echo $this->name; ?>" data-post-id="<?php echo $post_id; ?>">

				<?php wp_nonce_field( plugin_basename( __FILE__ ), $this->name . '_nonce' ); ?>

	            <div class="shortcode">
		            <p>Copy this code and paste it into your post, page or text widget content.</p>
		            <p class="sc">[<?php echo $this->name; ?> id="<?php echo $post_id; ?>"]</p>
	            </div>

				<div class="items">
					<?php if ( $items ): foreach ( $items as $key => $item ): ?>
					<div class="item">
		            	<div class="image">
		                    <div class="thumb"><?php echo wp_get_attachment_image( $item['image_id'] ); ?></div>
		                    <div class="field"><p><label>Image <?php echo $key+1; ?></label></p><p><input type="hidden" name="images_id[]" value="<?php echo $item['image_id']; ?>" /> <button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>
		                </div>
		                <div class="metas">
		                    <p><label for="link_to">Link To:</label></p>
		                    <p><input type="text" name="link_to[]" id="link_to" value="<?php echo ( isset( $item['link_to'] ) ) ? $item['link_to'] : ''; ?>" /></p>
		                </div>
					</div>
					<?php endforeach; else : ?>
					<div class="item">
		            	<div class="image">
		                    <div class="thumb"></div>
		                    <div class="field"><p><label>Image 1</label></p><p><input type="hidden" name="images_id[]" value="" /> <button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>
		                </div>
		                <div class="metas">
		                    <p><label for="link_to">Link To:</label></p>
		                    <p><input type="text" name="link_to[]" id="link_to" value="" /></p>
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

			//Check permission:
			if ( ! current_user_can( 'edit_posts' ) ) 
	        	return;

	        //Security check, data comming from the right form:
	        if ( ! isset( $_POST[ $this->name . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ $this->name . '_nonce' ], plugin_basename( __FILE__ ) ) )
      			return;
			
			//Date stored in variable using "if" condition (short method)
			$images_id 	= ( isset( $_REQUEST['images_id'] ) ) ? $_REQUEST['images_id'] : '';
			$link_to 	= ( isset( $_REQUEST['link_to'] ) ) ? $_REQUEST['link_to'] : '';

			$items = array();
			foreach ( $images_id as $key => $image_id ) {
				if ( $image_id ) {
					$items[] = array(
						'image_id'	=> $image_id,
						'link_to'	=> $link_to[ $key ]
					);
				}
			}

			//Insert data in DB:
			update_post_meta( $post_id, $this->name, $items );
		}

		public function edit_columns( $columns ) {
			return array(
				'cb' 		=> '<input type="checkbox" />',
				'title' 	=> __( $this->label ),
				'items' 	=> __( 'Items' )
			);
		}

		public function custom_columns( $col, $post_id ) {
			$items = get_post_meta( $post_id, $this->name, true );
			
			switch ( $col ) {
				case 'title':
					the_title();
					break;
				case 'items':
					echo sizeof( $items );
					break;
			}
		}

		public function register_admin_scripts() {
			wp_register_script( $this->name . '_admin_script', TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/admin.js',  array('media-upload', 'thickbox', 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-draggable','jquery-ui-droppable'));
			wp_register_style( $this->name . '_admin_style', TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/admin.css' );
		}
	
		public function enqueue_admin_scripts() {
			wp_enqueue_script( $this->name . '_admin_script' );
			wp_enqueue_style( $this->name . '_admin_style' );
			wp_enqueue_style( 'thickbox' );
		}

		public function enqueue_frontend_scripts() {
		    wp_register_script( $this->name . '_frontend_script', TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/frontend.js',  array( 'jquery' ));
			wp_register_style( $this->name . '_frontend_style', TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/frontend.css' );

		    wp_enqueue_script( $this->name . '_frontend_script' );
		    wp_enqueue_style( $this->name . '_frontend_style' );
		}

		public function shortcode( $atts ) {
			global $post;
			
			//Extract attributes and set default value if not set
			extract( shortcode_atts( array(
				'id' 			=> '',
				'navigation' 	=> true //Display navigation or not
			), $atts ) );
			
			//Generate Query:
			$args = array(
	            'post_type' 		=> $this->name,
	            'page_id'			=> $id,
	            'post_status' 		=> 'publish',
				'posts_per_page'	=> -1,
				'order' 			=> 'ASC'
	        );
	        $query = new WP_Query( $args );

	        $output = '';
			
        	while ($query->have_posts()):
				$query->the_post();

				$output .= '<div id="hscroll_' . $post->ID . '" class="hscroll slide">';
				if ( $post->post_title ) $output .= '<h2>' . $post->post_title . '</h2>';
				$output .= '	<div class="hscroll-inner">';
		
				//Get meta datas from DB:
				$items = get_post_meta( $post->ID, $this->name, true );

				$link_to = ( $items['link_to'] ) ? $items['link_to'] : '#';

				if ( $items ): foreach ( $items as $key => $item ) :
					$image = wp_get_attachment_image_src( $item['image_id'], 'thumbnail' );
					$output .= '<div class="item">';

					$output .= ( $items['link_to'] ) ? '<a href="' . $items['link_to'] . '" target="_blank">' : '';
					$output .= '<img src="' . $image[0] . '" border="0" />';
					$output .= ( $items['link_to'] ) ? '</a>' : '';
					
					$output .= '</div>';

				endforeach; endif;

			$output .= '	</div>
							<!--<a class="control left" href="#hscroll_' . $post->ID . '" data-slide="prev">&lsaquo;</a>
								<a class="control right" href="#hscroll_' . $post->ID . '" data-slide="next">&rsaquo;</a>-->
						</div>';
			endwhile;

			wp_reset_query();
			
			return $output;
		}
	}
}
?>