<?php
/*
Plugin Name: List Posts
Version: 0.1
Description: Shortcode and Widget to display post via post-type
Shortcode: [listposts posttype="page" order="ASC"], [listposts parentid="5" orderby="post_date"], [listposts number="5"]
Author: Etienne Tremel
*/

if ( ! class_exists( 'List_Posts' ) ) {
	class List_Posts {
		public function __construct() {
			/* INIT */
			add_action( 'widgets_init', array( $this, 'list_posts_init' ) );

			/* GENERATE SHORT CODE */
			add_shortcode('listposts', array( $this, 'shortcode_listposts' ) );
		}

		public function list_posts_init() {
			register_widget( 'List_Posts_Constructor' );
		}

		public function shortcode_listposts( $atts ) {
			//Extract attributes and set default value if not set
			extract( shortcode_atts( array(
				'number'	=> '-1',
				'posttype' 	=> 'page',
				'parentid'	=> '',
				'orderby'	=> 'post_title',
				'order'		=> 'DESC'
			), $atts ) );
			
			$output = '<div id="list_posts-' . $post_id . '" class="list_posts">';
			$args = array(
				'numberposts'	=> ( ! isset( $number ) || empty( $number ) ) ? '-1' : $number,
				'post_type'		=> ( ! isset( $posttype ) || empty( $posttype ) ) ? 'page' : $posttype,
				'post_parent'	=> ( ! isset( $parentid ) || empty( $parentid ) ) ? '' : $parentid,
				'post_status'	=> 'publish',
				'orderby'		=> ( ! isset( $orderby ) || empty( $orderby ) ) ? '' : $orderby,
				'order'			=> ( ! isset( $order ) || empty( $order ) ) ? '' : $order
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

if ( ! class_exists( 'List_Posts_Constructor' ) ) {

	class List_Posts_Constructor extends WP_Widget {
		function List_Posts_Constructor() {
			$widget_ops = array(
				'classname'		=> 'list_posts_constructor',
				'description'   => __( 'List posts as list' )
			);

			parent::__construct( 'List_Posts_Constructor', __( 'List Posts' ), $widget_ops );
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