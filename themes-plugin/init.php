<?php
    /**
     * THEMES PLUGIN
     * Version: 0.1 Beta
     * Author: Etienne Tremel
     * Extend themes functionnality by adding custom plugins inside the theme folder.
     * Usage: Check the repository on GitHub: https://github.com/etiennetremel/Wordpress-Themes-Plugin
     */


    /**
     * DEFINE GLOBALS
     */
    if ( ! defined( "TP_DIRECTORY" ) )
        define( 'TP_DIRECTORY', dirname(__FILE__) );

    if ( ! defined( "TP_BASE" ) )
        define( 'TP_BASE', get_template_directory_uri() . '/' . basename( dirname( __FILE__) ) );

    if ( ! defined( "TP_PLUGIN_DIRECTORY_WWW" ) )
        define( 'TP_PLUGIN_DIRECTORY_WWW', TP_BASE . '/plugins' );

    if ( ! defined( "TP_LIB_DIRECTORY_WWW" ) )
        define( 'TP_LIB_DIRECTORY_WWW', TP_BASE . '/lib' );


    /**
     * AUTOLOAD CLASSES & PLUGINS
     */
    function themes_plugin_autoloader( $name ) {
        $name = str_replace( '_', '-', strtolower( $name ) );
        $class_file = TP_DIRECTORY . '/lib/class-' . $name . '.php';

        if ( file_exists( $class_file ) )
            require_once $class_file;

        $plugin_file = TP_DIRECTORY . '/plugins/' . $name . '/' . $name . '.php';
        if ( file_exists( $plugin_file ) )
            require_once $plugin_file;
    }
    spl_autoload_register( 'themes_plugin_autoloader' );


    //Add Themes Plugin Manager
    require_once( TP_DIRECTORY . '/themes-plugin-manager.php' );
    new Themes_Plugin_Manager();
?>