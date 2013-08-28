<?php
/*
Class Name: base
Version: 0.1
Author: Etienne Tremel
*/

if ( ! class_exists( 'base' ) ) {
    class base {

        public $mailing;

        // Init
        public function __construct() {
            $this->mailing = new Mailing();
        }

        /**
         * Return template part as variable
         */
        public function load_template_part( $template_name, $part_name=null ) {
            ob_start();
            get_template_part( $template_name, $part_name );
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }

        /**
         * RENDER TEMPLATE
         * Render each {{tag_name}} by associated key in $datas: array( 'tag_name' => 'value' );
         */
        public function render_tpl( $template, $datas = array(), $htmlentities = true ) {
            preg_match_all( '/\{\{([^}]+)\}\}/', $template, $tags );
            if ( $tags ) {
                foreach( $tags[0] as $index => $tag ) {
                    if ( array_key_exists( $tags[ 1 ][$index ], $datas ) ) {

                        $value = stripslashes( $datas[ $tags[ 1 ][$index ] ] );

                        if ( $htmlentities )
                            $value = htmlentities( $value );

                        $template = str_replace( $tag, $value, $template );
                    }
                }
            }
            return $template;
        }

        /**
         * Generate JSON datas
         */
        public function output( $datas ) {
            header('Content-type: application/json');
            $datas['nonce'] = wp_create_nonce( 'ajax-nonce' );
            echo json_encode( $datas );
            exit;
        }
    }
}
?>