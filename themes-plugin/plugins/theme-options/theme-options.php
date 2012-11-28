<?php
/* 
Plugin Name: Theme Settings
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


    /* SAVE SETTINGS, ADD IT TO APPEARANCE MENU */
    add_action('admin_init', 'theme_options_init');

    function theme_options_init() {        
    
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
                foreach( $_REQUEST as $field_name => $field_value ) {
                    if( ! in_array( $field_name, $not_included_fields ) )
                        $options[ $field_name ] = $field_value;
                }
                if( update_option( 'theme_settings', $options ) )
                    $notification = 'Settings saved.';
            } else {
                foreach( $_REQUEST as $field_name => $field_value ) {
                    if( ! in_array( $field_name, $not_included_fields ) )
                        $options[ $field_name ] = $field_value;
                }
            }
        } else {
            //init plugin: add default option in db if not already available:
            $options = get_option( 'theme_settings' ) ? get_option( 'theme_settings' ) : array();
            $values = array();
            foreach( $theme_options as $setting ) {
                if ( ! in_array( $setting['name'], $options ) )
                    $values[ $setting['name'] ] = $setting['default_value'];
                else
                    $values[ $setting['name'] ] = $options[ $setting['name'] ];
            }
            update_option( 'theme_settings', $values );
        }
    }

    add_action( 'admin_menu', 'theme_settings_add_menu');
    function theme_settings_add_menu() {
        add_theme_page( 'Theme Settings', 'Theme Settings', 'edit_themes', 'theme-options', 'theme_options');
    }

    function theme_options(){
        global $notification, $theme_options;     
        ?>
    
        <div class="wrap columns-1">
            <?php screen_icon(); ?><h2>Theme Settings</h2>
        </div>
        <?php
        if ( isset ( $notification ) ) echo '<div class="info" class="updated fade"><p>' . $notification . '</p></div>';
        ?>
        
        <div class="maindesc">
            <p>In this area, you can manage the theme to whatever you like.</p>
        </div>
        <div class="options_wrap">
            <form method="post">
                <?php
                $settings = get_option( 'theme_options' );
                $theme_options_form = new Custom_Form();

                if ( $settings ) {
                    foreach( $theme_options as &$field ) {
                        if( isset( $settings[ $field['name'] ] ) )
                            $field['default_value'] = $settings[ $field['name'] ];
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

    /* FUNCTION TO GET THE VALUE FROM THE TEMPLATE */
    function get_theme_options( $name ) {
        $options = get_option( 'theme_settings' ) ? get_option( 'theme_settings' ) : array();
        if ( array_key_exists( $name, $options ) )
            return $options[ $name ];
    }

    /* REGISTER SCRIPTS & STYLE */
    add_action( 'admin_init', 'register_theme_settings_scripts' );
    function register_theme_settings_scripts() {
        wp_register_style( 'theme_settings_style', TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/theme-options.css' );
    }
    
    add_action('admin_enqueue_scripts', 'enqueue_theme_settings_scripts' );
    function enqueue_theme_settings_scripts() {
        wp_enqueue_style( 'theme_settings_style' );
    }
?>