<?php
/**
 * DEFINE SETTING FIELDS
 */

global $theme_options;
$theme_options = array(
    array(
        'type'          => 'section',
        'label'         => 'Website Settings'
    ), array(
        'type'          => 'text',
        'label'         => __( 'Footer Copyright Text' ),
        'name'          => 'footer_copyright',
        'description'   => 'Use the_theme_setting("footer_copyright");',
        'default_value' => '© ' . date( 'Y' ) . ' Copyright ' . get_bloginfo( 'name' )
    ), array(
        'type'          => 'section',
        'label'         => 'SEO'
    ), array(
        'type'          => 'image',
        'label'         => __( 'Website Thumbnail' ),
        'name'          => 'website_thumbnail',
        'description'   => 'Website preview used when url posted on social networks. Size at least 300x300px. Use the_theme_setting( \'website_thumbnail\' );',
        'default_value' => ''
    ), array(
        'type'          => 'textarea',
        'label'         => __( 'Google Analytics Code' ),
        'name'          => 'ga_code',
        'description'   => 'Use the_theme_setting( \'ga_code\' );',
        'default_value' => ''
    ), array(
        'type'          => 'text',
        'label'         => __( 'Website Description' ),
        'name'          => 'twitter_description',
        'description'   => 'Description to be used on twitter post. Use the_theme_setting( \'twitter_description\' );',
        'default_value' => ''
    ), array(
        'type'          => 'section',
        'label'         => 'Social Network Settings'
    ), array(
        'type'          => 'fieldset_start',
        'label'         => 'Windows'
    ), array(
        'type'          => 'text',
        'label'         => __( 'Windows 8 background color' ),
        'name'          => 'win8_bg_color',
        'description'   => 'Background color used on Windows 8 / IE10. Use the_theme_setting( \'win8_bg_color\' );',
        'default_value' => ''
    ), array(
        'type'          => 'image',
        'label'         => __( 'Windows 8 Icon' ),
        'name'          => 'win8_icon',
        'description'   => 'Icon used on Windows 8 / IE10. Use the_theme_setting( \'win8_icon\');',
        'default_value' => ''
    ), array(
        'type'          => 'fieldset_end'
    ), array(
        'type'          => 'fieldset_start',
        'label'         => 'Geo Localisation'
    ), array(
        'type'          => 'text',
        'label'         => __( 'Region' ),
        'name'          => 'geo_region',
        'description'   => 'Use the_theme_setting( \'geo_region\' );',
        'default_value' => ''
    ), array(
        'type'          => 'text',
        'label'         => __( 'Placename' ),
        'name'          => 'geo_placename',
        'description'   => 'Use the_theme_setting( \'geo_placename\' );',
        'default_value' => ''
    ), array(
        'type'          => 'text',
        'label'         => __( 'Latitude' ),
        'name'          => 'geo_latitude',
        'description'   => 'Use the_theme_setting( \'geo_latitude\' );',
        'default_value' => ''
    ), array(
        'type'          => 'text',
        'label'         => __( 'Longitude' ),
        'name'          => 'geo_longitude',
        'description'   => 'Use the_theme_setting( \'geo_longitude\' );',
        'default_value' => ''
    ), array(
        'type'          => 'fieldset_end'
    ), array(
        'type'          => 'fieldset_start',
        'label'         => 'Twitter'
    ), array(
        'type'          => 'text',
        'label'         => __( 'Website Twitter Account' ),
        'name'          => 'twitter_site_username',
        'description'   => 'Twitter account of the website (@username). Use the_theme_setting( \'twitter_site_username\' );',
        'default_value' => ''
    ), array(
        'type'          => 'text',
        'label'         => __( 'Creator Twitter Account' ),
        'name'          => 'twitter_creator_username',
        'description'   => 'Twitter account of the website creator. Use the_theme_setting( \'twitter_creator_username\' );',
        'default_value' => ''
    ), array(
        'type'          => 'fieldset_end'
    ), array(
        'type'          => 'fieldset_start',
        'label'         => 'Google Plus'
    ), array(
        'type'          => 'text',
        'label'         => __( 'Google Plus Author url' ),
        'name'          => 'googleplus_author',
        'description'   => 'Use the_theme_setting( \'googleplus_author\' );',
        'default_value' => ''
    ), array(
        'type'          => 'fieldset_end'
    )
);
?>