Wordpress-Themes-Plugin
=======================

_Be aware that this is a Beta version, so you may find some bugs, please report it via GitHub!_

_Versioning is not used yet until everything works properly_

_Activing every plugins and widget can slowdown your Wordpress site, so just activate needed plugin_

Extend themes functionnality by adding custom plugins inside the theme folder.

Usage
-----
1. Copy the "/themes-plugin" folder into your wordpress theme directory (ie: "/wp-content/themes/my-custom-theme/")

2. Insert the following code into "/wp-content/themes/my-custom-theme/functions.php" file:
    include_once( get_template_directory() . '/themes-plugin/init.php' );
    
3. Start to develop your own plugin inspired by the "/plugin-bootstrap/plugin-base.php" file.

Available plugins
-----------------
+ **Banner** _Add Custom Banner Using Bootstrap Transition & Carousel jQuery Script_
+ **Columnizer** _Shortcode to add columns in the content_
+ **Image Scroller** _Add a horizontal image scroller_
+ **Gallery to Post** _Add Gallery Management to post_
+ **List Posts** _Shortcode and Widget to display post via post-type_
+ **Projects** _Add project management_
+ **Team** _Add Staff Management_
+ **Testimonials** _Add customer feedback including comment, date, company name and website_
+ **Theme Settings** _Theme Settings Management_
+ **WooCommerce CSV Importer** _WooCommerce Product Importer from CSV File_

Available widgets
-----------------
+ **Image Widget** _Widget add an image with a link_
+ **Section Widget** _Widget add Image, Title, Content and Read More Button_
+ **Twitter Widget** _Widget add Twitter feed_
+ **Instagram Widget** _Widget add Instagram feed_