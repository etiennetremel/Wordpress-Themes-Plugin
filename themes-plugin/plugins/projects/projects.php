<?php
/*
Plugin Name: Projects
Version: 0.1
Description: Add project management.
Shortcode: [projects id="11"] | [project post_per_page="6"]
Widget: Recent Work
Author: Etienne Tremel
*/

if ( ! class_exists( 'Projects' ) ) {
	class Projects {
		public function __construct() {
			/* REGISTER CUSTOM POST TYPE & TAXONOMY */
			add_action( 'init', array( $this, 'project_register' ) );

			/* CHANGE DEFAULT CUSTOM TITLE (Change placeholder value when editing/adding a new post) */
			add_filter( 'enter_title_here', array( $this, 'project_change_title' ) );

			/* DISPLAY CUSTOM FIELDS */
			add_action( 'add_meta_boxes', array( $this, 'project_meta_box' ) );

			/* ON PAGE UPDATE/PUBLISH, SAVE CUSTOM DATA IN DATABASE */
			add_action( 'save_post', array( $this, 'save_project' ) );

			/* CUSTOMISE THE COLUMNS TO SHOW IN ADMIN AREA */
			//Define visible fields:
			add_filter( 'manage_edit-project_columns', array( $this, 'project_edit_columns' ) );

			//Associate datas to fields:
			add_action( 'manage_project_posts_custom_column', array( $this, 'project_custom_columns' ), 10, 2 );

			/* GENERATE SHORT CODE */
			add_shortcode('projects', array( $this, 'shortcode_project' ) );

			/* WIDGET INIT */
			add_action( 'widgets_init', array( $this, 'projects_widget_init' ) );
		}

		public function project_register() {

			$labels = array(
				'name'					=> __( 'Projects' ),
				'singular_name'			=> __( 'Project' ),
				'add_new_item'			=> __( 'Add New Project' ),
				'edit_item'				=> __( 'Edit Project' ),
				'new_item'				=> __( 'New Project' ),
				'view_item'				=> __( 'View Project' ),
				'search_items'			=> __( 'Search Projects' ),
				'not_found'				=> __( 'No projects found' ),
				'not_found_in_trash'	=> __( 'No projects found in trash' ),
				'menu_name'				=> __( 'Projects' )
			);

			$args = array(
				'labels' 				=> $labels,
				'public' 				=> true,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'hierarchical' 			=> false,
				'exclude_from_search' 	=> true,
				'supports' 				=> array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' )
			);
			register_post_type( 'project' , $args );

			// Add new taxonomy TAG type
			$tags_labels = array(
				'name' 							=> __( 'Project Tags' ),
				'singular_name' 				=> __( 'Project Tag' ),
				'search_items' 					=> __( 'Search Project Tags' ),
				'popular_items' 				=> __( 'Popular Project Tags' ),
				'all_items' 					=> __( 'All Project Tags' ),
				'edit_item' 					=> __( 'Edit Project Tag' ),
				'update_item' 					=> __( 'Update Project Tag' ),
				'add_new_item' 					=> __( 'Add New Project Tag' ),
				'new_item_name' 				=> __( 'New Project Tag Name' ),
				'separate_items_with_commas' 	=> __( 'Separate Project Tags with commas' ),
				'add_or_remove_items' 			=> __( 'Add or remove Project Tag' ),
				'choose_from_most_used' 		=> __( 'Choose from the most used Project Tags' )
			);
			register_taxonomy( 'project_tag', 'project', array(
				'hierarchical' 	=> false,
				'labels' 		=> $tags_labels,
				'show_ui' 		=> true,
				'query_var' 	=> true
			));

		}

		public function project_change_title( $title ) {
			$screen = get_current_screen();
			if ( 'project' == $screen->post_type ) $title = 'Enter project name here';
			return $title;
		}

		public function project_meta_box(){
			add_meta_box( 'project-extra-informations', 'Extra Informations', array( $this, 'project_meta' ), 'project', 'normal', 'low' );
		}  
	
		public function project_meta( $post ){
			global $post;
			$post_id = $post->ID;
	        
			//Get datas from DB:
			$metas = get_post_meta( $post_id, 'project', true );
			if( $metas )
				extract( $metas );

			?>
			<style>
				label { vertical-align: middle; }
			</style>
			<input type="hidden" name="project_info_nonce" value="<?php echo wp_create_nonce( 'project_info_nonce' ); ?>" />
			<table class="form-table">
				<tr valign="top"><th scope="row"><label for="url">Project URL</label></th><td><input type="text" value="<?php echo ( isset( $url ) ) ? $url : ''; ?>" name="url" id="url" /></td></tr>
	            <tr valign="top"><th scope="row"><label for="published_date">Project published date</label></th><td><input type="text" value="<?php echo ( isset( $published_date ) ) ? $published_date : ''; ?>" name="published_date" id="published_date" /></td></tr>
			</table>

	        <?php
		}

		public function save_project( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;

			//Security check, data comming from the right form:
	        if ( ! isset( $_REQUEST['project_info_nonce'] ) || ( isset( $_REQUEST['project_info_nonce'] ) && ! wp_verify_nonce( $_REQUEST['project_info_nonce'], 'project_info_nonce' ) ) )
	        	return $post_id;
			
			//Date stored in variable using "if" condition (short method)
			$url 				= ( isset( $_REQUEST['url'] ) ) ? $_POST['url'] : '';
			$published_date 	= ( isset( $_REQUEST['published_date'] ) ) ? $_REQUEST['published_date'] : '';

			//Datas stored as an array:
			$metas = array(
				'url' 				=> $url,
				'published_date' 	=> $published_date
			);

			//Insert data in DB:
			update_post_meta( $post_id, 'project', $metas );
		}
	
		public function project_edit_columns( $columns ) {
			return array(
				'cb' 				=> '<input type="checkbox" />',
				'title' 			=> __( 'Project name' ),
				'url' 				=> __( 'URL' ),
				'published_date'	=> __( 'Published Date' ),
				'project_tag' 				=> __( 'Tags' )
			);
		}
	
		public function project_custom_columns( $col, $post_id ) {
			$metas = extract( get_post_meta( $post_id, 'project', true ) );
			
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
					$terms = get_the_terms( $post_id, 'project_tag' );
					if ( $terms ) {
						$tags = array();
						foreach ( $terms as $term ) {
							$tags[] = $term->name;
						}
						echo implode( ", ", $tags );
					} else { 
						echo 'No Tags';
					}

					break;
			}
		}
	
		public function shortcode_project( $atts ) {
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
	            'post_type' 		=> 'project',
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

					$terms = get_the_terms( $post->ID, 'project_tag' );
					$tags = array();
					if ( $terms ) {
						foreach ( $terms as $term ) {
							$tags[] = $term->name;
						}
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
				
				$ouput .= '<p>' . __( 'Woops! No testimonials availables.' ) . '</p>'; 

			endif;

			$wp_query = $wp_query_temp;

			return $output;
		}

		public function projects_widget_init() {
			register_widget( 'projects_widget' );
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

if ( ! class_exists( 'Projects_Widget' ) ) {

	class Projects_Widget extends WP_Widget {
		function Projects_Widget() {
			$widget_ops = array(
				'classname'		=> 'projects_widget',
				'description'   => __( 'Display recent project' )
			);

			parent::__construct( 'Projects_Widget', __( 'Widget Recent Project' ), $widget_ops );
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