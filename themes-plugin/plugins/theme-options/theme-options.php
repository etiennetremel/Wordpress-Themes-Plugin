<?php
/* 
Plugin Name: Theme Options
Version: 0.1
Description: Theme Settings Management
Author: Etienne Tremel
*/

 //Define Fields
global $theme_options;
$theme_options = array(
    array(
        'type'          => 'textarea',
        'label'         => __( 'Google Analytics Code' ),
        'name'          => 'ga_code',
        'default_value' => ''
    ), array(
        'type'          => 'text',
        'label'         => __( 'Footer Copyright Text' ),
        'name'          => 'footer_copyright',
        'default_value' => '© ' . date( 'Y' ) . ' Copyright ' . get_bloginfo( 'name' )
    )
);

if ( ! class_exists( 'Theme_Options' ) ) {
    class Theme_Options {
        public function __construct() {
            /* SAVE SETTINGS, ADD IT TO APPEARANCE MENU */
            add_action( 'admin_init', array( $this, 'theme_options_init' ) );

            /* ADD MENU TO APPEARANCE TAB */
            add_action( 'admin_menu', array( $this, 'theme_options_add_menu' ) );

            /* REGISTER SCRIPTS & STYLE */
            add_action( 'admin_init', array( $this, 'register_theme_options_scripts' ) );
            add_action('admin_enqueue_scripts', array( $this, 'enqueue_theme_options_scripts' ) );
        }

        public function theme_options_init() {        
    
            if ( ! current_user_can( 'edit_themes' ) )
                wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

            global $notification, $theme_options;

            if( isset( $_GET['page'] ) && $_GET['page'] == 'theme-options' ) {

                $options = array();

                //Do not include following fields in DB:
                $not_included_fields = array(
                    'page',
                    'action',
                    'submit'
                );
                
                if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'save' ) {
                    
                    //Security check, data comming from the right form:
                    if ( ! isset( $_REQUEST['theme_options_info_nonce'] ) || ( isset( $_REQUEST['theme_options_info_nonce'] ) && ! wp_verify_nonce( $_REQUEST['theme_options_info_nonce'], 'theme_options_info_nonce' ) ) )
                        wp_die( __( 'You do not have sufficient permissions' ) );

                    //Get datas on save, do not include fields we don't want:
                    foreach( $_REQUEST as $field_name => $field_value ) {
                        if( ! in_array( $field_name, $not_included_fields ) )
                            $options[ $field_name ] = $field_value;
                    }

                    //Display notification if success.
                    if( update_option( 'theme_options', $options ) )
                        $notification = 'Settings saved.';

                } else {

                     //Get datas, do not include fields we don't want:
                    foreach( $_REQUEST as $field_name => $field_value ) {
                        if( ! in_array( $field_name, $not_included_fields ) )
                            $options[ $field_name ] = $field_value;
                    }
                }
            } else {
                //init plugin: add default option in db if not already available:
                $options = get_option( 'theme_options' ) ? get_option( 'theme_options' ) : array();
                
                $values = array();
                foreach( $theme_options as $setting ) {
                    if ( ! in_array( $setting['name'], $options ) )
                        $values[ $setting['name'] ] = $setting['default_value'];
                    else
                        $values[ $setting['name'] ] = $options[ $setting['name'] ];
                }

                //Update options in DB:
                update_option( 'theme_options', $values );
            }
        }

        public function theme_options_add_menu() {
            add_theme_page( 'Theme Settings', 'Theme Settings', 'edit_themes', 'theme-options', array( $this, 'theme_options' ) );
        }

        public function theme_options(){
            global $notification, $theme_options;     
            ?>
        
            <div class="wrap columns-1">
                <?php screen_icon(); ?><h2>Theme Settings</h2>
            </div>
            <?php
            //Display notifications if needed
            if ( isset ( $notification ) ) echo '<div class="info" class="updated fade"><p>' . $notification . '</p></div>';
            ?>
            
            <div class="maindesc">
                <p>In this area, you can manage the theme to whatever you like.</p>
            </div>
            <div class="options_wrap">
                <form method="post">
                    <input type="hidden" name="theme_options_info_nonce" value="<?php echo wp_create_nonce( 'theme_options_info_nonce' ); ?>" />
                    <?php
                    //Get options from DB
                    $settings = get_option( 'theme_options' );
                   
                    $theme_options_form = new Custom_Form();

                    //If data in DB, overwrite default value of fields in the form:
                    if ( $settings ) {
                        foreach( $theme_options as &$field ) {
                            if( isset( $settings[ $field['name'] ] ) )
                                $field['default_value'] = stripslashes( $settings[ $field['name'] ] );
                        }
                    }

                    echo $theme_options_form->get_fields( $theme_options );
                    ?>
                    <input type="hidden" name="action" value="save" />
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        public function register_theme_options_scripts() {
            wp_register_style( 'theme_options_style', TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/theme-options.css' );
        }
        
        public function enqueue_theme_options_scripts() {
            wp_enqueue_style( 'theme_options_style' );
        }
    }
}

/* FUNCTION TO GET THE VALUE FROM THE TEMPLATE */
function get_theme_options( $name ) {
    $options = get_option( 'theme_options' ) ? get_option( 'theme_options' ) : array();
    if ( array_key_exists( $name, $options ) )
        return stripslashes( $options[ $name ] );
}
?>