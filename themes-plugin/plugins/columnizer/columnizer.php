<?php
/*
Plugin Name: Columnizer
Version: 0.1
Description: Shortcode to display column in a post, wrap the content and use class .row, .span-{size you choosed}
Shortcode: [row][/row], [span size="4"]Content here[/span]
Author: Etienne Tremel
*/

if ( ! class_exists( 'Columnizer' ) ) {
    class Columnizer {
        public function __construct() {
            /* GENERATE SHORT CODES */
            add_shortcode( 'span', array( $this, 'shortcode_span' ) );
            add_shortcode( 'row', array( $this, 'shortcode_row' ) );
        }

        public function shortcode_row( $atts, $content='' ) {
            return '<div class="row">' . do_shortcode( $content ) . '</div>';
        }

        public function shortcode_span( $atts, $content='' ) {

            //Extract attributes and set default value if not set
            extract( shortcode_atts( array(
                'size'    => '1'
            ), $atts ) );

            return '<div class="span-' . $size . '">' . do_shortcode( $content ) . '</div>';
        }
    }
}
?>