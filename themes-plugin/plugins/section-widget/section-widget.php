<?php
/*
Plugin Name: Section Widget
Version: 0.1
Description: Widget add Image, Title, Content and Read More Button - Depend of the Widget Image Field Plugin
Author: Etienne Tremel
*/

add_action('widgets_init', 'section_widget_init');
function section_widget_init() {
	register_widget( 'Section_Widget' );
}

if ( ! class_exists( 'Section_Widget' ) ) {

	class Section_Widget extends WP_Widget {
		function Section_Widget() {
			$widget_ops = array(
				'classname'		=> 'section_widget',
				'description'   => __( 'Add Image, Title, Content and Read More Button' )
			);

			$control_ops = array(
				'width' => 560, 
				'height' => 400
			);

			parent::__construct( 'Section_Widget', __('Section Widget'), $widget_ops, $control_ops );

            global $pagenow;
			if ( 'widgets.php' == $pagenow )
				add_action('admin_print_scripts', array(&$this, "enqueue_assets"));
		}

		function enqueue_assets() {
			wp_enqueue_script(
				'section-widget_script',
				TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/section-widget.js',
				array(
					'jquery',
					'media-upload',
					'thickbox',
					'jquery-ui-core'
				)
			);
		}

		function widget( $args, $instance ) {
			extract($args);

			$image_id     		= $instance['image_id'];
			$title 				= apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
			$content 			= apply_filters( 'widget_text', empty( $instance['content'] ) ? '' : $instance['content'], $instance );
			$more_button_title 	= $instance['more_button_title'];
			$link				= $instance['link'];
			$external_link		= $instance['external_link'];
			$link_target 		= $instance['link_target'];

			$link = ( empty( $link ) ) ? $external_link : get_permalink( $link );

			echo $before_widget;

			if( ! empty( $image_id ) ) {
				$image = wp_get_attachment_image_src( $image_id, 'original' );
				?>
				<div class="image">
					<a href="<?php echo $link; ?>" target="<?php echo $link_target; ?>"><img src="<?php echo $image[0]; ?>" alt="<?php echo $title; ?>" border="0" /></a>
				</div>
				<?php
			}

			if ( ! empty( $title ) )
				echo $before_title . '<a href="' . $link . '" target="' . $link_target . '">' . $title . '</a>' . $after_title;

			echo '<div class="content">' . ( ! empty( $instance['filter'] ) ? wpautop( $content ) : $content ) . '</div>';

			if( ! empty( $more_button_title ) || ! empty( $link ))
				echo '<a href="' . $link . '" target="' . $link_target . '" title="' . $more_button_title. '"><div class="more">' . $more_button_title . ' <img src="' . get_bloginfo('template_url') . '/images/arrow-right.gif" alt=">" /></div></a>';
				 
			echo $after_widget;
		}

		function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 'image_id' => '', 'title' => '', 'content' => '', 'more_button_title' => '', 'link' => '', 'link_target' => '' ) );
			
			$image_id   		= esc_attr( isset( $instance['image_id'] ) ? $instance['image_id'] : 0 );
			$title 				= strip_tags($instance['title']);
			$content 			= esc_textarea( isset( $instance['content'] ) ? $instance['content'] : '' );
			$more_button_title 	= esc_attr( isset( $instance['more_button_title'] ) ? $instance['more_button_title'] : '' );
			$link 				= esc_attr( isset( $instance['link'] ) ? $instance['link'] : '' );
			$external_link		= esc_attr( isset( $instance['external_link'] ) ? $instance['external_link'] : '' );
			$link_target 		= esc_attr( isset( $instance['link_target'] ) ? $instance['link_target'] : '' );
			?>
			<div>
				<label for="<?php echo $this->get_field_id('image_id'); ?>"><?php _e('Image:'); ?></label>
				<div class="image">
					<?php echo wp_get_attachment_image($image_id); ?>
				</div>
				<input type="hidden" name="<?php echo $this->get_field_name('image_id'); ?>" id="<?php echo $this->get_field_id('image_id'); ?>" value="<?php echo $image_id; ?>" />
				<button class="browse-image button button-highlighted">Choose</button>
			</div>
			<hr />
			
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
			</p>
			<hr />
			
			<p>
				<label for="<?php echo $this->get_field_id('content'); ?>"><?php _e('Content:') ?></label>
				 <?php wp_editor($content, $this->get_field_id('content'), array( 'textarea_name' => $this->get_field_name('content'), 'textarea_rows' => 5 )) ?>
			</p>
			<hr />

			<p>
				<label for="<?php echo $this->get_field_id('more_button_title'); ?>"><?php _e('More Button Title:') ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id('more_button_title'); ?>" name="<?php echo $this->get_field_name('more_button_title'); ?>" value="<?php echo $more_button_title; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('link'); ?>"><?php _e('Link to:') ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id('link'); ?>" name="<?php echo $this->get_field_name('link'); ?>">
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
				<label for="<?php echo $this->get_field_id('external_link'); ?>"><?php _e('External link to:') ?></label> <em>(http://...)</em>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id('external_link'); ?>" name="<?php echo $this->get_field_name('external_link'); ?>" value="<?php echo $external_link; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'link_target' ); ?>"><?php _e( 'Target:' ); ?>
					<select id="<?php echo $this->get_field_id( 'link_target' ); ?>" name="<?php echo $this->get_field_name( 'link_target' ); ?>">
						<option value="_self" <?php if($link_target=="_self") echo "selected"; ?>>_self</option>
						<option value="_blank" <?php if($link_target=="_blank") echo "selected"; ?>>_blank</option>
						<option value="_new" <?php if($link_target=="_new") echo "selected"; ?>>_new</option>
						<option value="_parent" <?php if($link_target=="_parent") echo "selected"; ?>>_parent</option>
						<option value="_top" <?php if($link_target=="_top") echo "selected"; ?>>_top</option>
					</select>
				</label>
			</p>
			<?php
		}

		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['image_id'] 			= intval( strip_tags( $new_instance['image_id'] ) );
			$instance['title'] 				= strip_tags( $new_instance['title'] );
			$instance['content'] 			= strip_tags( $new_instance['content'] );
			$instance['link_target'] 		= strip_tags( $new_instance['link_target'] );
			$instance['more_button_title']	= strip_tags( $new_instance['more_button_title'] );
			$instance['link'] 				= strip_tags( $new_instance['link'] );
			$instance['external_link'] 		= strip_tags( $new_instance['external_link'] );
			return $instance;
		}
	}
}

?>