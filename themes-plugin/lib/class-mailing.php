<?php
/*
Class Mailing
Version: 0.1
Description: Send emails using template with tags. Tags as: `Dear {{name}},`
Author: Etienne Tremel
*/
if ( ! class_exists( 'Mailing' ) ) {
    class Mailing {

        private $template, $default_args, $mail_template_directory;

        // Init
        public function __construct() {
            $this->mail_template_directory = get_template_directory() . '/theme-email';

            $this->default_args = array(
                'template_name'     => 'default',
                'name_to'           => '',
                'email_to'          => '',
                'name_from'         => get_bloginfo( 'name' ),
                'email_from'        => get_bloginfo( 'admin_email' ),
                'reply_to'          => get_bloginfo( 'admin_email' ),
                'subject'           => 'Hello!',
                'tags'              => array()
            );
        }

        public function send_email( $args ) {

            // Extract arguments
            extract( wp_parse_args( $args, $this->default_args ) );

            // Required datas validation
            if ( empty( $email_to ) )
                return 'email_to not defined.';

            if ( empty( $subject ) )
                return 'subject not defined.';


            // Define email header
            //$headers  = 'To: ' . trim( $name_to ) . ' <' . trim( $email_to ) . '>' . "\r\n";
            $headers = 'From: '. trim( $name_from ) . ' <' . trim( $email_from ) . ">\r\n";
            $headers .= 'Reply-To: ' . trim( $reply_to ) . "\r\n";
            $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
            $headers .= 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

            // Check if template already loaded
            if ( $template_name != $this->template['template_name'] ) {
                //Get email template:
                $template_file = $this->mail_template_directory . '/' . $template_name . '.php';

                if ( ! file_exists( $template_file ) )
                    return 'Template file do not exist: ' . $template_file;

                $this->template['datas']         = file_get_contents( $template_file );
                $this->template['template_name'] = $template_name;
            }

            $template = $this->template['datas'];

            if ( ! $template )
                return 'Problem while loading template file.';

            // Replace shortcode in template:
            $has_shortcode = preg_match_all( '/\{\{([^}]+)\}\}/i', $template, $matches );

            if ( $has_shortcode ) {
                for ( $i = 0; $i < sizeof( $matches[0] ); $i++ ) {
                    $replace_by = isset( $tags[ $matches[1][ $i ] ] ) ? $tags[ $matches[1][ $i ] ] : '';
                    $template = str_replace( $matches[0][ $i ], html_entity_decode( $replace_by ), $template );
                }
            }


            //Send email
            if ( wp_mail( $email_to, $subject, $template, $headers ) )
                return true;
            else
                return false;
        }
    }
}
?>