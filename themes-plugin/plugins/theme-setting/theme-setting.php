<?php
/*
Plugin Name: Theme Setting
Version: 0.1
Description: Theme Settings Management
Author: Etienne Tremel
*/

if ( ! class_exists( 'Theme_Setting' ) ) {

    //Get fields:
    require( 'fields.php' );

    class Theme_Setting {

        private $name = 'theme-setting';
        private $name_plurial, $label, $label_plurial;
        private $notification;

        public function __construct() {
            /* SAVE SETTINGS, ADD IT TO APPEARANCE MENU */
            add_action( 'admin_init', array( $this, 'init' ) );

            /* ADD MENU TO APPEARANCE TAB */
            add_action( 'admin_menu', array( $this, 'add_menu' ) );

            /* REGISTER SCRIPTS & STYLE */
            add_action( 'admin_init', array( $this, 'register_admin_scripts' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        }

        public function init() {

            if ( ! current_user_can( 'edit_themes' ) )
                return;

            global $theme_options;

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
                        $this->notification = 'Settings saved.';

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
            global $theme_options;
            ?>

            <div class="wrap columns-1">
                <?php screen_icon(); ?><h2>Theme Settings</h2>

                <?php if ( isset ( $this->notification ) ): ?>
                    <div id="notification" class="fade">
                        <div class="notice info"><?php echo $this->notification; ?></div>
                    </div>
                <?php endif; ?>

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
                                if( isset( $field['name'] ) && isset( $settings[ $field['name'] ] ) )
                                    $field['default_value'] = stripslashes( $settings[ $field['name'] ] );
                            }
                        }

                        echo $theme_options_form->get_fields( $theme_options );
                        ?>
                        <input type="hidden" name="action" value="save" />
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
            <?php
        }

        public function register_admin_scripts() {
            wp_register_script( $this->name . '_admin_script', TP_PLUGIN_DIRECTORY_WWW . '/' . $this->name . '/assets/admin.js',  array('media-upload', 'thickbox', 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-draggable','jquery-ui-droppable'));
            wp_register_style( $this->name . '_admin_style', TP_PLUGIN_DIRECTORY_WWW . '/' . $this->name . '/assets/admin.css' );
        }

        public function enqueue_admin_scripts() {
            if ( isset( $_GET['page'] ) && 'theme-setting' == $_GET['page'] ) {
                wp_enqueue_media();

                wp_enqueue_script( $this->name . '_admin_script' );
                wp_enqueue_style( $this->name . '_admin_style' );
                wp_enqueue_style( 'thickbox' );
            }
        }
    }
}

/* FUNCTION TO GET THE VALUE FROM THE TEMPLATE */
if ( ! function_exists( 'get_theme_setting' ) ) {
    function get_theme_setting( $name ) {
        $settings = get_option( 'theme-setting' ) ? get_option( 'theme-setting' ) : array();
        if ( array_key_exists( $name, $settings ) )
            return stripslashes( $settings[ $name ] );
        else
            return false;
    }
}
if ( ! function_exists( 'the_theme_setting' ) ) {
    function the_theme_setting( $name ) {
        $settings = get_option( 'theme-setting' ) ? get_option( 'theme-setting' ) : array();
        if ( array_key_exists( $name, $settings ) )
            echo stripslashes( $settings[ $name ] );
    }
}
?>