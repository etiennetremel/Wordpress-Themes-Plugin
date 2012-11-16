Wordpress-Themes-Plugin
=======================

Extend themes functionnality by adding custom plugins inside the theme folder.

Usage
-----
1. Copy the "/themes-plugin" folder into your wordpress theme directory (ie: "/wp-content/themes/my-custom-theme/")

2. Insert the following code into "/wp-content/themes/my-custom-theme/functions.php" file:
	include_once( get_template_directory() . '/themes-plugin/init.php' );
	
3. Start to develop your own plugin inspired by the "/plugin-bootstrap/plugin-base.php" file.

