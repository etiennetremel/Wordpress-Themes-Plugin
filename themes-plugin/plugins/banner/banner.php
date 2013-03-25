<?php
/*
Plugin Name: Banner
Version: 0.1
Description: Add Custom Banner Using Bootstrap Transition & Carousel jQuery Script
Shortcode: [banner id="45"] | [banner controls="false"] | [banner indicators="false"]
Author: Etienne Tremel
*/

if ( ! class_exists( 'Banner' ) ) {
	class Banner {

		private $name = 'banner';
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
			add_filter( 'manage_edit-banner_columns', array( $this, 'edit_columns' ) );  
			//Associate datas to fields:
			add_action( 'manage_banner_posts_custom_column',  array( $this, 'custom_columns' ), 10, 2 ); 

			/* REGISTER SCRIPTS & STYLE */
			add_action( 'admin_init', array( $this, 'register_admin_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );

			/* GENERATE SHORT CODE */
			add_shortcode( 'banner', array( $this, 'shortcode' ) );
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
				'publicly_queryable'	=> false,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'hierarchical' 			=> false,
				'exclude_from_search' 	=> true,
				'supports' 				=> array( 'title' )
			   );  
		
			register_post_type( $this->name, $args );
		}

		public function change_title( $title ) {
			$screen = get_current_screen();
			if ( $this->name == $screen->post_type ) $title = 'Enter banner name here';
			return $title;
		}

		public function meta_box() {
			add_meta_box( $this->name . '-options', $this->label_plurial, array( $this, 'meta' ), $this->name, 'normal', 'low' );
		}
	
		public function meta( $post ) {
			global $post;
			$post_id = $post->ID;
	        
			//Get datas from DB:
			$items = get_post_meta( $post_id, $this->name, true );

			?>
			<div id="banner" data-post-id="<?php echo $post_id; ?>">

				<?php wp_nonce_field( plugin_basename( __FILE__ ), $this->name . '_nonce' ); ?>
				
	            <div class="shortcode">
		            <p>Copy this code and paste it into your post, page or text widget content.</p>
		            <p class="sc">[banner id="<?php echo $post_id; ?>"]</p>
		            <p>
		            	<strong><em>Attributes availables:</em></strong>
		            	<ul>
		            		<li>controls: true or false - Display controls</li>
		            		<li>indicators: true or false - Display indicators</li>
		            	</ul>
		            </p>
	            </div>

				<div class="items">
					<?php if ( $items ): foreach ( $items as $index => $item ): ?>
					<div class="item">
		            	<div class="image">
		                    <div class="thumb"><?php echo wp_get_attachment_image( $item['image_id'] ); ?></div>
		                    <div class="field"><p><label>Image <?php echo $index+1; ?></label></p><p><input type="hidden" name="images_id[]" value="<?php echo $item['image_id']; ?>" /> <button class="browse-image button button-highlighted" type="button">Browse</button> <button class="delete-image button button-highlighted" type="button">Delete</button></p></div>
		                </div>
		                <div class="metas">
		                    <p><label for="text">Text:</label></p>
		                    <p>
		                    <?php wp_editor( stripslashes( $item['text'] ), 'text_' . $i, array( 
						 		'textarea_name' => 'text[]', 
						 		'textarea_rows' => 5,
						 		'media_buttons'	=> true,
						 		'tinymce' => array(
						          'theme_advanced_buttons1' => 'bold,italic,underline,formatselect' ,
						          'theme_advanced_buttons2'	=> ''
						    	),
						    	'quicktags' => false,
						 		'wpautop' => true ) ); ?>
						 	</p>
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
			$texts 		= ( isset( $_REQUEST['text'] ) ) ? $_REQUEST['text'] : '';

			$items = array();
			foreach ( $images_id as $index => $image_id ) {
				if ( $image_id ) {
					$items[] = array(
						'image_id'	=> $image_id,
						'text'		=> $texts[ $index ]
					);
				}
			}

			//Insert data in DB:
			update_post_meta( $post_id, $this->name, $items );
		}

		public function edit_columns( $columns ) {
			return array(
				'cb' 		=> '<input type="checkbox" />',
				'title' 	=> __( 'Banner name' ),
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

		public function enqueue_frontend_scripts() {
		    wp_register_script( $this->name . '_frontend_script', TP_PLUGIN_DIRECTORY_WWW . '/' . $this->name . '/assets/frontend.js',  array( 'jquery' ));
			wp_register_style( $this->name . '_frontend_style', TP_PLUGIN_DIRECTORY_WWW . '/' . $this->name . '/assets/frontend.css' );

		    wp_enqueue_script( $this->name . '_frontend_script' );
		    wp_enqueue_style( $this->name . '_frontend_style' );
		}

		public function shortcode( $atts ) {
			global $post;
			
			//Extract attributes and set default value if not set
			extract( shortcode_atts( array(
				'id' 			=> '',
				'controls' 		=> true, 	//Display controls
				'indicators' 	=> true 	//Display indicators
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
			
			if ( $query->have_posts() ) : while ($query->have_posts()): $query->the_post();

				$carousel_id = 'carousel_' . $post->ID;

				//Get meta datas from DB:
				$items = get_post_meta( $post->ID, $this->name, true );

				if ( $items ):

					$carousel_indicators = $slides = "";

					foreach ( $items as $index => $item ) :

						$active =  ( $index ) ? '' : 'active' ;

						$carousel_indicators .= '<li data-target="#' . $carousel_id . '" data-slide-to="' . $index . '" class="' . $active . '"></li>';

						$slides .= '<div class="item ' . $active . '">
										<img src="' . wp_get_attachment_url( $item['image_id'] ) . '" alt="" />';

						if ( ! empty( $item['text'] ) ) :
							$slides .= '	<div class="carousel-caption">
												' . $item['text'] . '
											</div>';
						endif;

						$slides .= '</div>';

					endforeach; 

					$carousel_controls = '<a class="carousel-control left" href="#' . $carousel_id . '" data-slide="prev"><span class="arrow">&lsaquo;</span></a>
										<a class="carousel-control right" href="#' . $carousel_id . '" data-slide="next"><span class="arrow">&rsaquo;</span></a>';

					$output .= '<div id="' . $carousel_id . '" class="carousel slide">';

					//Display indicators
					if ( 'true' == $indicators && sizeof( $items ) > 1 )
						$output .= '<ol class="carousel-indicators">' . $carousel_indicators . '</ol>';


					$output .= '<div class="carousel-inner">' . $slides . '</div>';

					//Display controls
					if ( 'true' == $controls && sizeof( $items ) > 1 )
						$output .= $carousel_controls;
					
					$output .= '</div>';

				endif;
						
			endwhile; else:
				
				$ouput .= '<p>' . __( 'Woops! No ' . $this->label . ' items available.' ) . '</p>'; 

			endif;

			wp_reset_query();
			
			return $output;
		}
	}
}
?>