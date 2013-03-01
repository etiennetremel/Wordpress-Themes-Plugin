<?php
/*
Plugin Name: List Post
Version: 0.1
Description: Shortcode and Widget to display post depending of its type (page, post, ...)
Shortcode: [list-posts posttype="page" order="ASC"], [list-posts parentid="5" orderby="post_date"], [list-posts number="5"]
Author: Etienne Tremel
*/

/**
 * LIST POST SHORTCODE
 */
if ( ! class_exists( 'List_Post' ) ) {
	class List_Post {

		private $name = 'list-post';
		private $name_plurial, $label, $label_plurial;

		public function __construct() {
			/* INIT */
			add_action( 'widgets_init', array( $this, 'init' ) );

			/* GENERATE SHORT CODE */
			add_shortcode('list-posts', array( $this, 'shortcode' ) );
		}

		public function init() {
			register_widget( 'List_Post_Widget' );
		}

		public function shortcode( $atts ) {
			//Extract attributes and set default value if not set
			extract( shortcode_atts( array(
				'number'	=> '-1',
				'posttype' 	=> 'page',
				'parentid'	=> '',
				'orderby'	=> 'title',
				'order'		=> 'ASC',
				'exclude'	=> ''
			), $atts ) );
			
			$output = '<div id="list_posts-' . $post_id . '" class="list_posts">';
			$args = array(
				'numberposts'	=> $number,
				'post_type'		=> $posttype,
				'post_parent'	=> $parentid,
				'post_status'	=> 'publish',
				'orderby'		=> $orderby,
				'order'			=> $order,
				'exclude'		=> $exclude
			);

			$posts = get_posts( $args );
			if ( $posts ) :
				$output .= '<ul>';
					foreach( $posts as $post ) {
						$output .= '<li><a href="' . get_permalink( $post->ID ) . '" title="' . $post->post_title . '">' . $post->post_title . '</a></li>';
					}
				$output .= '</ul>';
			endif;

			$output .= '</div>';
			
			return $output;
		}
	}
}


/**
 * LIST POST WIDGET
 */
if ( ! class_exists( 'List_Post_Widget' ) ) {

	class List_Post_Widget extends WP_Widget {
		function List_Post_Widget() {
			$widget_ops = array(
				'classname'		=> 'list_posts_widget',
				'description'   => __( 'List posts as list' )
			);

			parent::__construct( 'list-posts-widget', __( 'List Post' ), $widget_ops );
		}

		function widget( $args, $instance ) {
			extract( $args );

			$parentid  	= $instance['parentid'];
			$posttype	= $instance['posttype'];
			$number		= $instance['number'];

			echo $before_widget;

			$args = array(
				'numberposts'	=> ( empty( $number ) ) ? '-1' : $number,
				'post_type'		=> ( empty( $posttype ) ) ? 'page' : $posttype,
				'post_parent'	=> ( empty( $parentid ) ) ? '' : $parentid,
				'post_status'	=> 'publish'
			);

			$posts = get_posts( $args );
			if ( $posts ) {
				?>
				<ul>
					<?php
					foreach( $posts as $post ) {
						?>
						<li><a href="<?php echo get_permalink( $post->ID ); ?>" title="<?php echo $post->post_title; ?>"><?php echo $post->post_title; ?></a></li>
						<?php
					}
					?>
				</ul>
				<?php
			}
				 
			echo $after_widget;
		}

		function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 'number' => '5', 'posttype' => '', 'parentid' => '' ) );
			
			$number   		= esc_attr( isset( $instance['number'] ) ? $instance['number'] : '' );
			$posttype 		= esc_attr( isset( $instance['posttype'] ) ? $instance['posttype'] : '' );
			$parentid		= esc_attr( isset( $instance['parentid'] ) ? $instance['parentid'] : '' );
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to display:' ) ?></label> <em>(keep empty to display all posts)</em>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $number; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'posttype' ); ?>"><?php _e( 'Post type:' ) ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'posttype' ); ?>" name="<?php echo $this->get_field_name( 'posttype' ); ?>">
					<?php
					$args = array(
						'public'	=> true
					);
					$post_types = get_post_types( $args );
					foreach ( $post_types as $type_name ) {
						$selected = ( $posttype == $type_name ) ? 'selected' : '';
						echo '<option value="' . $type_name . '" ' . $selected . '>' . $type_name . '</option>';
					}
					?>
				</select>
			</p>
			OR
			<p>
				<label for="<?php echo $this->get_field_id( 'parentid' ); ?>"><?php _e( 'Post parent ID:' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'parentid' ); ?>" name="<?php echo $this->get_field_name( 'parentid' ); ?>" value="<?php echo $parentid; ?>" />
			</p>
			<?php
		}

		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['number'] 	= strip_tags( $new_instance['number'] );
			$instance['posttype'] 	= strip_tags( $new_instance['posttype'] );
			$instance['parentid'] 	= strip_tags( $new_instance['parentid'] );
			return $instance;
		}
	}
}

?>