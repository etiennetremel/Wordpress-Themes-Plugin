<?php
/*
Plugin Name: Project
Version: 0.1
Description: Add project management.
Shortcode: [projects id="11"] | [project post_per_page="6"]
Widget: Recent Work
Author: Etienne Tremel
*/

if ( ! class_exists( 'Project' ) ) {
	class Project {

		private $name = 'project';
		private $name_plurial, $label, $label_plurial;

		public function __construct() {

			/* INITIALIZE VARIABLES */
			$this->name_plurial = $this->name . 's';
			$this->label = ucwords( preg_replace( '/[_.-]+/', ' ', $this->name ) );
			$this->label_plurial = ucwords( preg_replace( '/[_.-]+/', ' ', $this->name_plurial ) );

			/* REGISTER CUSTOM POST TYPE & TAXONOMY */
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
			add_action( 'manage_' . $this->name . '_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );

			/* GENERATE SHORT CODE */
			add_shortcode( 'projects', array( $this, 'shortcode' ) );

			/* WIDGET INIT */
			add_action( 'widgets_init', array( $this, 'widget_init' ) );
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
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'hierarchical' 			=> false,
				'exclude_from_search' 	=> true,
				'supports' 				=> array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' )
			);
			register_post_type( $this->name, $args );

			// Add new taxonomy TAG type
			$tags_labels = array(
				'name' 							=> __( $this->label . ' Tags'),
				'singular_name' 				=> __( $this->label . ' Tag' ),
				'search_items' 					=> __( 'Search ' . $this->label . ' Tags' ),
				'popular_items' 				=> __( 'Popular ' . $this->label . ' Tags' ),
				'all_items' 					=> __( 'All ' . $this->label . ' Tags' ),
				'edit_item' 					=> __( 'Edit ' . $this->label . ' Tag' ),
				'update_item' 					=> __( 'Update ' . $this->label . ' Tag' ),
				'add_new_item' 					=> __( 'Add New ' . $this->label . ' Tag' ),
				'new_item_name' 				=> __( 'New ' . $this->label . ' Tag Name' ),
				'separate_items_with_commas' 	=> __( 'Separate ' . $this->label . ' Tags with commas' ),
				'add_or_remove_items' 			=> __( 'Add or remove ' . $this->label . ' Tag' ),
				'choose_from_most_used' 		=> __( 'Choose from the most used ' . $this->label . ' Tags' )
			);
			register_taxonomy( $this->name . '_tag', $this->name, array(
				'hierarchical' 	=> false,
				'labels' 		=> $tags_labels,
				'show_ui' 		=> true,
				'query_var' 	=> true
			));

		}

		public function change_title( $title ) {
			$screen = get_current_screen();
			if ( $this->name == $screen->post_type ) $title = 'Enter project name here';
			return $title;
		}

		public function meta_box(){
			add_meta_box( $this->name . '-informations', 'Extra Informations', array( $this, 'meta' ), $this->name, 'normal', 'low' );
		}  
	
		public function meta( $post ) {
			global $post;
			$post_id = $post->ID;
	        
			//Get datas from DB:
			$metas = get_post_meta( $post_id, $this->name, true );
			if( $metas )
				extract( $metas );

			?>
			<style>
				label { vertical-align: middle; }
			</style>

			<?php wp_nonce_field( plugin_basename( __FILE__ ), $this->name . '_nonce' ); ?>

			<table class="form-table">
				<tr valign="top"><th scope="row"><label for="url">Project URL</label></th><td><input type="text" value="<?php echo ( isset( $url ) ) ? $url : ''; ?>" name="url" id="url" /></td></tr>
	            <tr valign="top"><th scope="row"><label for="published_date">Project published date</label></th><td><input type="text" value="<?php echo ( isset( $published_date ) ) ? $published_date : ''; ?>" name="published_date" id="published_date" /></td></tr>
			</table>

	        <?php
		}

		public function save( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;

			//Security check, data comming from the right form:
	        if ( ! isset( $_POST[ $this->name . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ $this->name . '_nonce' ], plugin_basename( __FILE__ ) ) )
      			return;

      		//Check permission:
			if ( ! current_user_can( 'edit_posts' ) ) 
	        	return;
			
			//Date stored in variable using "if" condition (short method)
			$url 				= ( isset( $_REQUEST['url'] ) ) ? $_POST['url'] : '';
			$published_date 	= ( isset( $_REQUEST['published_date'] ) ) ? $_REQUEST['published_date'] : '';

			//Datas stored as an array:
			$metas = array(
				'url' 				=> $url,
				'published_date' 	=> $published_date
			);

			//Insert data in DB:
			update_post_meta( $post_id, $this->name, $metas );
		}
	
		public function edit_columns( $columns ) {
			return array(
				'cb' 				=> '<input type="checkbox" />',
				'title' 			=> __( 'Project name' ),
				'url' 				=> __( 'URL' ),
				'published_date'	=> __( 'Published Date' ),
				'project_tag' 		=> __( 'Tags' )
			);
		}
	
		public function custom_columns( $col, $post_id ) {
			$metas = extract( get_post_meta( $post_id, $this->name, true ) );
			
			switch ( $col ) {
				case 'title':
					the_title();
					break;
				case 'url':
					echo ( isset( $url ) ) ? $url : '';
					break;
				case 'published_date':
					echo ( isset( $published_date ) ) ? $published_date : '';
					break;
				case 'project_tag':
					$terms = get_the_terms( $post_id, $this->name . '_tag' );
					if ( $terms ) {
						$tags = array();

						foreach ( $terms as $term )
							$tags[] = $term->name;

						echo implode( ", ", $tags );
					} else { 
						echo 'No Tags';
					}

					break;
			}
		}
	
		public function shortcode( $atts ) {
			global $post, $wp_query;
			$wp_query_temp = $wp_query;
			
			//Extract attributes and set default value if not set
			extract( shortcode_atts( array(
				'id' 			=> '',
				'post_per_page'	=> 6
			), $atts ) );

			//Navigation
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			
			//Generate Query:
			$args = array(
	            'post_type' 		=> $this->name,
	            'post_status' 		=> 'publish',
				'posts_per_page'	=> $post_per_page,
				'paged'				=> $paged,
				'max_num_pages'		=> 0,
				'order' 			=> 'DESC',
				'orderby' 			=> 'date'
	        );
	        $wp_query = new WP_Query( $args );
							
			if ( $wp_query->have_posts() ) :

				$output = '<ul class="projects">';

				//Return term with no space to be used in html:
				function sanitize_term_value( $term ) {
					return sanitize_title( $term );
				}

				while ($wp_query->have_posts()) :
					$wp_query->the_post();
			
					//Get meta datas from DB:
					$metas = extract( get_post_meta( $post->ID, 'project', true ) );    
					
					//Generate Output:
					$title 		= get_the_title();

					$content = get_the_content();
					if ( ! empty( $content ) )
						$content = '<div class="element element2 description">' . $content . '</div>';
					else
						$content = '';

					if( has_post_thumbnail( $post->ID ) )
						$thumbnail = get_the_post_thumbnail( $post->ID, array( 343, 180 ) );
					else
						$thumbnail = '<img src="'. get_bloginfo( 'template_url' ) .'/images/img-default.png" />';

					$attr = array();
					
					if ( isset( $url ) && ! empty( $url ) )
						$attr[] = str_replace( 'http://', '', $url );
					
					if ( isset( $published_date ) && ! empty( $published_date ) )
						$attr[] = $published_date;

					$terms = get_the_terms( $post->ID, $this->name . '_tag' );
					$tags = array();
					if ( $terms ) {
						foreach ( $terms as $term )
							$tags[] = $term->name;

						$attr[] = implode( ", ", $tags );
					}

					$date = $this->find_date( $published_date );
					$date = $date['year'] . $date['month'] . $date['day'];

					$projects[] = array(
						'thumbnail'			=> $thumbnail,
						'title'				=> $title,
						'content'			=> $content,
						'published_date'	=> $date,
						'tags'				=> implode( ' ', array_map( 'sanitize_term_value', $tags ) ),
						'attr'				=> implode( ' | ', $attr ),
						'url'				=> $url
					);
				endwhile;

				//Sort projets by date:
				function sort_projects( $a, $b ) {
					return $a['published_date'] < $b['published_date'];
				}
				//Sort projets by date:
				usort( $projects, "sort_projects" );

	        	foreach ( $projects as $project ) {

					$output .= '<li data-tags="' . $project['tags'] . '">
									<div class="thumb">' . $project['thumbnail'] . '</div>
									<div class="content">
										<div class="element element1"><h2>' . $project['title'] . '</h2></div>
										' . $project['content'] . '
										<div class="element element3"><p>' . $project['attr'] . '</p></div>
										<div class="element element4"><a class="button" href="' . $project['url']. '" target="_blank">Launch the project</a></div>
									</div>
								</li>';
				}
				$output .= '</ul>';
				$output .= '<div class="clear"></div>';

				/* Display navigation to next/previous pages when applicable */
		        if ( $wp_query->max_num_pages > 1 ) :
		            $output .= '<div id="nav-below" class="navigation">
		            				<div class="nav-previous">' . get_previous_posts_link( '<span class="meta-nav">&laquo;</span> Older projects' ) . '</div>
		            				<div class="nav-next">' . get_next_posts_link( 'Newer projects <span class="meta-nav">&raquo;</span>' ) . '</div>
		            			</div>';
		        endif;
			else:
				
				$ouput .= '<p>' . __( 'Woops! No ' . $this->label . ' availables.' ) . '</p>'; 

			endif;

			$wp_query = $wp_query_temp;

			return $output;
		}

		public function widget_init() {
			register_widget( 'project_widget' );
		}

		/* FIND DATE IN STRING */
		/* More info: https://github.com/etiennetremel/PHP-Find-Date-in-String */
		private function find_date( $string ) {

			//Define month name:
			$month_names = array( 
				"january",
				"february",
				"march",
				"april",
				"may",
				"june",
				"july",
				"august",
				"september",
				"october",
				"november",
				"december"
			);

			$month_number=$month=$matches_year=$year=$matches_month_number=$matches_month_word=$matches_day_number="";
			
			//Match dates: 01/01/2012 or 30-12-11 or 1 2 1985
			preg_match( '/([0-9]?[0-9])[\.\-\/ ]?([0-1]?[0-9])[\.\-\/ ]?([0-9]{2,4})/', $string, $matches );
			if ( $matches ) {
				if ( $matches[1] )
					$day = $matches[1];

				if ( $matches[2] )
					$month = $matches[2];

				if ( $matches[3] )
					$year = $matches[3];
			}

			//Match month name:
			preg_match( '/(' . implode( '|', $month_names ) . ')/i', $string, $matches_month_word );

			if ( $matches_month_word ) {
				if ( $matches_month_word[1] )
					$month = array_search( strtolower( $matches_month_word[1] ),  $month_names ) + 1;
			}

			//Match 5th 1st day:
			preg_match( '/([0-9]?[0-9])(st|nd|th)/', $string, $matches_day );
			if ( $matches_day ) {
				if ( $matches_day[1] )
					$day = $matches_day[1];
			}

			//Match Year if not already setted:
			if ( empty( $year ) ) {
				preg_match( '/[0-9]{4}/', $string, $matches_year );
				if ( $matches_year[0] )
					$year = $matches_year[0];
			}
			if ( ! empty ( $day ) && ! empty ( $month ) && empty( $year ) ) {
				preg_match( '/[0-9]{2}/', $string, $matches_year );
				if ( $matches_year[0] )
					$year = $matches_year[0];
			}

			//Leading 0
			if ( 1 == strlen( $day ) )
				$day = '0' . $day;

			//Leading 0
			if ( 1 == strlen( $month ) )
				$month = '0' . $month;

			//Check year:
			if ( 2 == strlen( $year ) && $year > 20 )
				$year = '19' . $year;
			else if ( 2 == strlen( $year ) && $year < 20 )
				$year = '20' . $year;

			$date = array(
				'year' 	=> $year,
				'month' => $month,
				'day' 	=> $day
			);

			//Return false if nothing found:
			if ( empty( $year ) && empty( $month ) && empty( $day ) )
				return false;
			else
				return $date;
		}
	}
}

if ( ! class_exists( 'Project_Widget' ) ) {

	class Project_Widget extends WP_Widget {
		function Project_Widget() {
			$widget_ops = array(
				'classname'		=> 'project_widget',
				'description'   => __( 'Display recent project' )
			);

			parent::__construct( 'project-widget', __( 'Widget Recent Project' ), $widget_ops );
		}

		function widget( $args, $instance ) {
			extract( $args );

			$title		= $instance['title'];
			$number		= $instance['number'];
			$page_id  	= $instance['page_id'];
			$excerpt	= $instance['excerpt'];

			echo $before_widget;

			$args = array(
				'numberposts'	=> ( empty( $number ) ) ? '-1' : $number,
				'post_type'		=> 'project',
				'post_status'	=> 'publish'
			);

			$projects = get_posts( $args );
			if ( $projects ) {
				?>
				<ul>
					<?php
					foreach( $projects as $project ) {
						?>
						<li>
							<h3 class="title"><?php echo $title; ?></h3>
							<div class="thumb"><?php echo get_the_post_thumbnail( $project->ID, 'thumbnail' ); ?></div>
								<span class="title"><?php echo $project->post_title; ?></span>
								<?php echo ( $excerpt ) ? '<br />' . strip_tags( $project->post_excerpt ) : ''; ?>
							<div class="button_more"><a href="<?php echo get_permalink( $page_id ); ?>">MORE</span></a></div>
						</li>
						<?php
					}
					?>
				</ul>
				<?php
			} else {
				echo "<li>No recent Project</li>";
			}
				 
			echo $after_widget;
		}

		function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'number' => '5', 'page_id' => '', 'excerpt' => '0' ) );
			
			$title 		= esc_attr( isset( $instance['title'] ) ? $instance['title'] : '' );
			$number   	= esc_attr( isset( $instance['number'] ) ? $instance['number'] : '' );
			$page_id	= esc_attr( isset( $instance['page_id'] ) ? $instance['page_id'] : '' );
			$excerpt	= esc_attr( isset( $instance['excerpt'] ) ? $instance['excerpt'] : '' );
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of projects to display:' ) ?></label> <em>(keep empty to display all projects)</em>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $number; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'page_id' ); ?>"><?php _e( 'Link to page:' ) ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'page_id' ); ?>" name="<?php echo $this->get_field_name( 'page_id' ); ?>">
					<?php
					$args = array(
						'public'	=> true,
						'post_type'	=> 'page'
					);
					$pages = get_posts( $args );
					foreach ( $pages as $page ) {
						$selected = ( $page_id == $page->ID ) ? 'selected' : '';
						echo '<option value="' . $page->ID . '" ' . $selected . '>' . $page->post_title . '</option>';
					}
					?>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'excerpt' ); ?>"><?php _e( 'Display excerpt:' ) ?></label>
				<input type="radio" id="<?php echo $this->get_field_id( 'excerpt' ); ?>" name="<?php echo $this->get_field_name( 'excerpt' ); ?>" value="1" <?php echo ( $excerpt ) ? 'checked="checked"' : ''; ?> /> YES
				<input type="radio" id="<?php echo $this->get_field_id( 'excerpt' ); ?>" name="<?php echo $this->get_field_name( 'excerpt' ); ?>" value="0" <?php echo ( ! $excerpt ) ? 'checked="checked"' : ''; ?> /> NO
			</p>
			<?php
		}

		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] 		= strip_tags( $new_instance['title'] );
			$instance['number'] 	= strip_tags( $new_instance['number'] );
			$instance['page_id'] 	= strip_tags( $new_instance['page_id'] );
			$instance['excerpt'] 	= strip_tags( $new_instance['excerpt'] );
			return $instance;
		}
	}
}
?>