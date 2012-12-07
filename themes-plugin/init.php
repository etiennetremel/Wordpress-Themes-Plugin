<?php
	/**
	 * THEMES PLUGIN
	 * Version: 0.1 Beta
	 * Author: Etienne Tremel
	 * Extend themes functionnality by adding custom plugins inside the theme folder.
	 * Usage: Check the repository on GitHub: https://github.com/etiennetremel/Wordpress-Themes-Plugin
	 */


	/*
	 * DEFINE GLOBALS
	 */
	if ( ! defined( "TP_DIRECTORY" ) )
		define( 'THEME_PLUGINS_DIRECTORY', dirname(__FILE__) );

	if ( ! defined( "TP_BASE" ) )
		define( 'TP_BASE', get_template_directory_uri() . '/' . basename( dirname( __FILE__) ) );

	if ( ! defined( "TP_PLUGIN_DIRECTORY_WWW" ) )
		define( 'TP_PLUGIN_DIRECTORY_WWW', TP_BASE . '/plugins' );

	if ( ! defined( "TP_LIB_DIRECTORY_WWW" ) )
		define( 'TP_LIB_DIRECTORY_WWW', TP_BASE . '/lib' );


	/**
	 * AUTOLOAD CLASSES
	 */
	function __autoload( $name ) {
		$name = str_replace( '_', '-', strtolower( $name ) );
		$class_file = THEME_PLUGINS_DIRECTORY . '/lib/class-' . $name . '.php';
		if ( file_exists( $class_file ) )
	    	require_once $class_file;

	    $plugin_file = THEME_PLUGINS_DIRECTORY . '/plugins/' . $name . '/' . $name . '.php';
		if ( file_exists( $plugin_file ) )
	    	require_once $plugin_file;
	}


	/*
	 * ACTIVATE THEMES PLUGIN
	 */
	add_action( 'after_setup_theme', 'register_themes_plugin', 1 );

	function register_themes_plugin() {
		global $notification;

		$plugins = get_option( 'themes_active_plugin' );
		if ( $plugins ) {
			foreach ( $plugins as $plugin ) {
				$plugin = str_replace( ' ', '_', ucwords( $plugin ) );
				new $plugin();
			}
		}
	}


	/*
	 * CREATE THEMES PLUGIN SETTINGS PAGE AS SUBPAGE OF APPEARANCE MENU
	 */
	function register_themes_plugin_settings_init() {
	   
		add_theme_page( 
			'Themes Plugin Settings',
			'Plugin Settings',
			'edit_themes',
			'themes-plugin-settings',
			'themes_plugin_settings'
		);		

		add_action( 'admin_init', 'register_themes_plugin_settings' );
	}
	add_action('admin_menu', 'register_themes_plugin_settings_init');

	/**
	 * REGISTER DATA AS OPTION IN THE DATABASE
	 */
	function register_themes_plugin_settings() {
		if ( ! current_user_can('edit_themes') ) 
	        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	    global $notification;

	    if ( isset( $_GET['page'] ) && $_GET['page'] == 'themes-plugin-settings' ) {
			
			if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'save' && isset( $_REQUEST['plugins'] ) ) {			
				
				$options = $_REQUEST['plugins'];

				if( update_option( 'themes_active_plugin', $options ) ) {
					$notification = 'Settings saved.';

					//Reload to make plugin works:
					header( 'Location: themes.php?page=themes-plugin-settings&saved=true' );
				} else {
					$notification = 'Problem when updating the settings.';
				}
			} else {
				return;
			}

		}
	}
	
	
	function themes_plugin_settings() {
		global $notification;
		?>
    
		<div class="wrap columns-1">
			<?php screen_icon(); ?><h2>Themes Plugin Settings</h2>
        </div>
        <?php
		if ( isset ( $notification ) ) echo '<div id="message" class="updated fade"><p>' . $notification . '</p></div>';
		?>
        
        <div class="maindesc">
			<p>In this area, you can manage themes plugins.</p>
        </div>
        <div class="wrap">

        	<form method="post">
        		<?php
				/*
				 * AUTO-ADD THEMES PLUGIN
				 */
				$theme_custom_folder = THEME_PLUGINS_DIRECTORY . '\plugins';
				
				if ( $handle = opendir( $theme_custom_folder ) ) {
					$plugins = array();
					while ( false !== ( $entry = readdir( $handle ) ) ) {
						//Read plugin folder
						if ( $entry != "." && $entry != ".." && is_dir( $theme_custom_folder . '\\' . $entry ) ) {
							//Find plugin init file
							$file = $theme_custom_folder . '\\' . $entry . '\\' . $entry . '.php';
							if ( file_exists( $file ) ) {

								//Get meta-data from php file:
								$metas = array( 
									'name' 			=> 'Plugin Name',
									'plugin_uri' 	=> 'Plugin URI',
									'description' 	=> 'Description',
									'version' 		=> 'Version',
									'author' 		=> 'Author',
									'author_uri' 	=> 'Author URI',
									'shortcode'		=> 'Shortcode'
								);
								$file_data = get_file_data( $file, $metas );

								$plugins[] = $file_data;

							}
						}
					}
					closedir($handle);

					if ( $plugins ) {
						echo '<table class="wp-list-table widefat plugins" cellspacing="0">
								<thead>
									<tr>
									<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></th>
										<th scope="col" class="manage-column column-name">Plugin</th>
										<th scope="col" class="manage-column column-name">Description</th>
									</tr>
								</thead>
								<tbody id="the-list">';

						foreach ( $plugins as $plugin ) {

							$extra = array();
							if ( ! empty( $plugin['plugin_uri'] ) )
								$extra[] = 'Plugin URI: <a href="' . $plugin['plugin_uri'] . '">' . $plugin['plugin_uri'] . '</a>';

							if ( ! empty( $plugin['version'] ) )
								$extra[] = 'Version: ' . $plugin['version'];

							if ( ! empty( $plugin['author'] ) )
								$extra[] = 'Author: <a href="' . $plugin['author_uri'] . '">' . $plugin['author'] . '</a>';

							$description = $plugin['description'] . '<br />' . implode( ' | ', $extra );

							if ( ! empty( $plugin['shortcode'] ) )
								$description .= '<br />Shortcode: ' . $plugin['shortcode'];

							$active_plugins = get_option( 'themes_active_plugin' );
							if ( $active_plugins && in_array( $plugin['name'], $active_plugins ) )
								$active = true;
							else
								$active = false;
							
							echo '<tr>
									<th valign="top" class="check-column ' . ( $active ? 'active' : 'inactive' ) . '" scope="row"><input type="checkbox" name="plugins[]" value="' . $plugin['name'] . '" ' . ( $active ? 'checked="checked"' : '' ) . ' /></th>
								 	<td valign="top" class="plugin-title ' . ( $active ? 'active' : 'inactive' ) . '"><strong>' . $plugin['name'] . '</strong></td>
								 	<td class="column-description desc ' . ( $active ? 'active' : 'inactive' ) . '">' . $description . '</td>
								 </tr>';
						}
							echo '</tbody>';
						echo '</table>';
					}
				}
					
				?>
                <input type="hidden" name="action" value="save" />
                <?php submit_button(); ?>
            </form>
        </div>
        
        <?php
	}
?>