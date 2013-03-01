<?php
/* 
Plugin Name: Theme Setting
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
        'description'   => 'Use get_theme_setting("ga_code");',
        'default_value' => ''
    ), array(
        'type'          => 'text',
        'label'         => __( 'Footer Copyright Text' ),
        'name'          => 'footer_copyright',
        'description'   => 'Use get_theme_setting("footer_copyright");',
        'default_value' => '© ' . date( 'Y' ) . ' Copyright ' . get_bloginfo( 'name' )
    )
);

if ( ! class_exists( 'Theme_Setting' ) ) {
    class Theme_Setting {

        private $name = 'theme_setting';
        private $name_plurial, $label, $label_plurial;

        public function __construct() {
            /* SAVE SETTINGS, ADD IT TO APPEARANCE MENU */
            add_action( 'admin_init', array( $this, 'init' ) );

            /* ADD MENU TO APPEARANCE TAB */
            add_action( 'admin_menu', array( $this, 'add_menu' ) );

            /* REGISTER SCRIPTS & STYLE */
            add_action( 'admin_init', array( $this, 'register_scripts' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        }

        public function init() {

            if ( ! current_user_can( 'edit_themes' ) )
                return;

            global $notification, $theme_options;

            if( isset( $_GET['page'] ) && $_GET['page'] == $this->name ) {

                $options = array();

                //Do not include following fields in DB:
                $not_included_fields = array(
                    'page',
                    'action',
                    'submit'
                );
                
                if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'save' ) {
                    
                    //Security check, data comming from the right form:
                    if ( ! isset( $_POST[ $this->name . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ $this->name . '_nonce' ], plugin_basename( __FILE__ ) ) )
                        return;

                    //Get datas on save, do not include fields we don't want:
                    foreach( $_REQUEST as $field_name => $field_value ) {
                        if( ! in_array( $field_name, $not_included_fields ) )
                            $options[ $field_name ] = $field_value;
                    }

                    //Display notification if success.
                    if( update_option( $this->name, $options ) )
                        $notification = 'Settings saved.';

                } else {

                     //Get datas, do not include fields we don't want:
                    foreach( $_REQUEST as $field_name => $field_value )
                        if( ! in_array( $field_name, $not_included_fields ) )
                            $options[ $field_name ] = $field_value;
                }
            } else {
                //init plugin: add default option in db if not already available:
                $options = get_option( $this->name );
                
                if( ! $options ) {
                    $values = array();
                    foreach( $theme_options as $setting )
                        $values[ $setting['name'] ] = $setting['default_value'];

                    //Update options in DB:
                    update_option( $this->name, $values );
                }
            }
        }

        public function add_menu() {
            add_theme_page( 'Theme Settings', 'Theme Settings', 'edit_themes', $this->name, array( $this, 'theme_options' ) );
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
                    <?php wp_nonce_field( plugin_basename( __FILE__ ), $this->name . '_nonce' ); ?>
                    
                    <?php
                    //Get options from DB
                    $settings = get_option( $this->name );
                   
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

        public function register_scripts() {
            wp_register_style( $this->name . '_style', TP_PLUGIN_DIRECTORY_WWW . '/' . $this->name . '/assets/admin.css' );
        }
        
        public function enqueue_scripts() {
            wp_enqueue_style( $this->name . '_style' );
        }
    }
}

/* FUNCTION TO GET THE VALUE FROM THE TEMPLATE */
if ( ! function_exists( 'get_theme_setting' ) ) {
    function get_theme_setting( $name ) {
        $settings = get_option( 'theme_setting' ) ? get_option( 'theme_setting' ) : array();
        if ( array_key_exists( $name, $settings ) )
            return stripslashes( $settings[ $name ] );
    }
}
?>