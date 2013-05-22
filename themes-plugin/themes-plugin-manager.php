<?php
/*
Plugin Name: Themes Plugin Manager
Version: 0.1
Description: Themes Plugin Management, Appearance > Plugin Settings
Author: Etienne Tremel
*/

if ( ! class_exists( 'Themes_Plugin_Manager' ) ) {
	class Themes_Plugin_Manager {

	    public function __construct() {

	    	/* ACTIVATE THEMES PLUGIN */
	    	add_action( 'after_setup_theme', array( $this, 'register' ), 1 );

	    	/* ADMIN MENU: PLUGIN SETTINGS PAGE AS SUBPAGE OF APPEARANCE MENU */
	    	add_action('admin_menu', array( $this, 'admin_menu' ) );

	    	/* REGISTER STYLE */
		    add_action( 'admin_init', array( $this, 'register_style' ) );
		    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_style' ) );
	    }

	    /**
	     * AUTO-LOAD EVERY PLUGINS
	     */
	    public function register() {
	        global $notification;

	        $plugins = get_option( 'themes_active_plugin' );

	        if ( $plugins ) {
	            foreach ( $plugins as $plugin ) {
	                $plugin = str_replace( ' ', '_', ucwords( $plugin ) );
	                if ( class_exists( $plugin ) )
	                    new $plugin();
	            }
	        }
	    }

	    public function admin_menu() {

	        add_theme_page(
	            'Themes Plugin Manager',
	            'Plugins Manager',
	            'edit_themes',
	            'themes-plugin-manager',
	            array( $this, 'plugin_manager' )
	        );

	        add_action( 'admin_init', array( $this, 'save' ) );
	    }

	    public function save() {
	        if ( ! current_user_can('edit_themes') )
	            return;

	        global $notification;

	        if ( isset( $_GET['page'] ) && $_GET['page'] == 'themes-plugin-manager' ) {

	            if( isset( $_POST['action'] ) && $_POST['action'] == 'save' ) {

	                //Empty array if no plugins selected
	                if ( isset( $_POST['plugins'] ) )
	                    $new_options = $_POST['plugins'];
	                else
	                    $new_options = array();

	                if ( get_option( 'themes_active_plugin' ) != $new_options ) {
					    $status = update_option( 'themes_active_plugin', $new_options );
					} else {
					    $deprecated = ' ';
					    $autoload = 'yes';
					    $status = add_option( 'themes_active_plugin', $new_options, $deprecated, $autoload );
					}

	                if( $status ) {
	                    $notification = 'Settings saved.';

	                    //Reload to make plugin works:
	                    header( 'Location: themes.php?page=themes-plugin-manager' );
	                } else {
	                    $notification = 'Problem while updating the settings.';
	                }
	            } else {
	                return;
	            }

	        }
	    }

	    public function plugin_manager() {
	        global $notification;
	        ?>

	        <div class="wrap columns-1">
	            <?php screen_icon(); ?><h2>Themes Plugin Manager</h2>
	        </div>
	        <?php
	        if ( isset ( $notification ) ) echo '<div id="message" class="updated fade"><p>' . $notification . '</p></div>';
	        ?>

	        <div class="maindesc">
	            <p>In this area, you can manage themes plugin.</p>
	        </div>
	        <div class="wrap">

	            <form method="post">
	                <?php
	                /**
	                 * AUTO-ADD THEMES PLUGIN
	                 */
	                $theme_custom_folder = TP_DIRECTORY . DIRECTORY_SEPARATOR . 'plugins';

	                if ( $handle = opendir( $theme_custom_folder ) ) {
	                    $plugins = array();
	                    while ( false !== ( $entry = readdir( $handle ) ) ) {

	                        //Fetch plugins by reading folders
	                        if ( $entry != "." && $entry != ".." && is_dir( $theme_custom_folder . DIRECTORY_SEPARATOR . $entry ) ) {
	                            //Find plugin init file
	                            $file = $theme_custom_folder . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . $entry . '.php';
	                            if ( file_exists( $file ) ) {

	                                //Get meta-data from php file:
	                                $metas = array(
	                                    'name'           => 'Plugin Name',
	                                    'plugin_uri'     => 'Plugin URI',
	                                    'description'    => 'Description',
	                                    'version'        => 'Version',
	                                    'author'         => 'Author',
	                                    'author_uri'     => 'Author URI',
	                                    'shortcode'      => 'Shortcode'
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

	    public function register_style() {
	        wp_register_style( 'tp_admin_style', TP_BASE . '/assets/style.css' );
	    }

	    public function enqueue_style() {
	        wp_enqueue_style( 'tp_admin_style' );
	    }
	}
}
?>