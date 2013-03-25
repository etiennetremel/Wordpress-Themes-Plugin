<?php
/*
Plugin Name: Image Widget
Version: 0.1
Description: Widget add an image with a link
Author: Etienne Tremel
*/

if ( ! class_exists( 'Image_Widget' ) ) {
	class Image_Widget {
		public function __construct() {
			/* INIT WIDGET */
			add_action( 'widgets_init', array( $this, 'image_widget_init' ) );
		}

		public function image_widget_init() {
			register_widget( 'Image_Widget_Constructor' );
		}
	}
}

if ( ! class_exists( 'Image_Widget_Constructor' ) ) {
	class Image_Widget_Constructor extends WP_Widget {
		function Image_Widget_Constructor() {
			$widget_ops = array(
				'classname'		=> 'image-widget',
				'description'   => __( 'Add image and link' )
			);

			parent::__construct( 'image-widget', __( 'Image Widget' ), $widget_ops );

            global $pagenow;
			if ( 'widgets.php' == $pagenow )
				add_action( 'admin_print_scripts', array( &$this, "enqueue_assets" ) );
		}

		function enqueue_assets() {
			wp_enqueue_media();
			
			wp_enqueue_script(
				'image-widget_script',
				TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/admin.js',
				array(
					'jquery',
					'media-upload',
					'thickbox',
					'jquery-ui-core'
				)
			);
		}

		function widget( $args, $instance ) {
			extract( $args );

			$image_id     		= $instance['image_id'];
			$title				= $instance['title'];
			$link				= $instance['link'];
			$external_link		= $instance['external_link'];
			$link_target 		= $instance['link_target'];

			$link = ( empty( $link ) ) ? $external_link : get_permalink( $link );

			echo $before_widget;

			if( ! empty( $image_id ) ):
				$image = wp_get_attachment_image_src( $image_id, 'original' );
				?>
				<div class="image">
					<a href="<?php echo $link; ?>" target="<?php echo $link_target; ?>"><img src="<?php echo $image[0]; ?>" alt="<?php echo $title; ?>" border="0" /></a>
				</div>
				<?php if ( ! empty( $title ) ) : ?>
					<?php echo $before_title . $title . $after_title; ?>
				<?php endif; ?>
				<?php
			endif;
				 
			echo $after_widget;
		}

		function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 'image_id' => '', 'link' => '', 'external_link' => '', 'link_target' => '' ) );
			
			$image_id   		= esc_attr( isset( $instance['image_id'] ) ? $instance['image_id'] : 0 );
			$title 				= esc_attr( isset( $instance['title'] ) ? $instance['title'] : '' );
			$link 				= esc_attr( isset( $instance['link'] ) ? $instance['link'] : '' );
			$external_link		= esc_attr( isset( $instance['external_link'] ) ? $instance['external_link'] : '' );
			$link_target 		= esc_attr( isset( $instance['link_target'] ) ? $instance['link_target'] : '' );
			?>
			<div>
				<label for="<?php echo $this->get_field_id( 'image_id' ); ?>"><?php _e( 'Image:' ); ?></label>
				<div class="image">
					<?php echo wp_get_attachment_image( $image_id ); ?>
				</div>
				<input type="hidden" name="<?php echo $this->get_field_name( 'image_id' ); ?>" id="<?php echo $this->get_field_id( 'image_id' ); ?>" value="<?php echo $image_id; ?>" />
				<button class="browse-image button button-highlighted">Choose an image</button>
			</div>
			<hr />
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label> <em>(not visible if empty)</em>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'link' ); ?>"><?php _e( 'Link to:' ) ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'link' ); ?>" name="<?php echo $this->get_field_name( 'link' ); ?>">
					<option value="">-</option>
					<?php
					$args = array(
						'numberposts'	=> -1,
						'post_status'	=> 'publish'
					);
					$list_pages = get_pages( $args );
					foreach ( $list_pages as $page ) {
						$selected = ( $link == $page->ID ) ? 'selected' : '';
						echo '<option value="' . $page->ID . '" ' . $selected . '>' . $page->post_title . '</option>';
					}
					?>
				</select>
			</p>
			OR
			<p>
				<label for="<?php echo $this->get_field_id( 'external_link' ); ?>"><?php _e( 'External link to:' ) ?></label> <em>(http://...)</em>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'external_link' ); ?>" name="<?php echo $this->get_field_name( 'external_link' ); ?>" value="<?php echo $external_link; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'link_target' ); ?>"><?php _e( 'Target:' ); ?>
					<select id="<?php echo $this->get_field_id( 'link_target' ); ?>" name="<?php echo $this->get_field_name( 'link_target' ); ?>">
						<option value="_self" <?php if ( '_self' == $link_target ) echo 'selected'; ?>>_self</option>
						<option value="_blank" <?php if ( '_blank' == $link_target ) echo 'selected'; ?>>_blank</option>
						<option value="_new" <?php if ( '_new' == $link_target ) echo 'selected'; ?>>_new</option>
						<option value="_parent" <?php if ( '_parent' == $link_target ) echo 'selected'; ?>>_parent</option>
						<option value="_top" <?php if ( '_top' == $link_target ) echo 'selected'; ?>>_top</option>
					</select>
				</label>
			</p>
			<?php
		}

		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['image_id'] 			= intval( $new_instance['image_id'] );
			$instance['title'] 				= strip_tags( $new_instance['title'] );
			$instance['link'] 				= strip_tags( $new_instance['link'] );
			$instance['external_link'] 		= strip_tags( $new_instance['external_link'] );
			$instance['link_target'] 		= strip_tags( $new_instance['link_target'] );
			return $instance;
		}
	}
}

?>