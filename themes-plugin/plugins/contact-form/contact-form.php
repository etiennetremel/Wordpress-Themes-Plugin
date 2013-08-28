<?php
/*
Plugin Name: Contact Form
Version: 0.1
Description: Contact Form
Shortcode: [contact-form name="Form 1"]
Author: Etienne Tremel
*/

if ( ! class_exists( 'Contact_Form' ) ) {
    class Contact_Form extends base {

        private $name = 'contact-form';
        private $name_plurial, $label, $label_plurial;
        private $default_values, $db_table, $notification;

        public function __construct() {
            // Execute base class constructor:
            parent::__construct();

            /* DEFAULT VALUES USED FOR EACH FIELDS */
            $this->default_values = array(
                'form_name'     => 'Form 1',
                'form_html'     => '<form class="contact form-horizontal" role="form">' . "\n" .
                                   '    <div class="form-group">' . "\n" .
                                   '        <label for="field-name" class="col-lg-2">Name</label>' . "\n" .
                                   '        <div class="col-lg-10">' . "\n" .
                                   '            <input type="text" id="field-name" name="name" placeholder="Name" class="form-control required" />' . "\n" .
                                   '        </div>' . "\n" .
                                   '    </div>' . "\n" .
                                   '    <div class="form-group">' . "\n" .
                                   '        <label for="field-email" class="col-lg-2">Email</label>' . "\n" .
                                   '        <div class="col-lg-10">' . "\n" .
                                   '            <input type="email" id="field-email" name="email" placeholder="your@email.com" class="form-control required" />' . "\n" .
                                   '        </div>' . "\n" .
                                   '    </div>' . "\n" .
                                   '    <div class="form-group">' . "\n" .
                                   '        <label for="field-message" class="col-lg-2">Message</label>' . "\n" .
                                   '        <div class="col-lg-10">' . "\n" .
                                   '            <textarea name="message" id="field-message" class="form-control" placeholder="Message"></textarea>' . "\n" .
                                   '        </div>' . "\n" .
                                   '    </div>' . "\n" .
                                   '    <div class="col-lg-offset-2 col-lg-10">' . "\n" .
                                   '        <button type="submit" class="btn btn-default submit">Submit <span class="spinner"></span></button>' . "\n" .
                                   '    </div>' . "\n" .
                                   '    <div class="notifications col-lg-12" style="display:none;"></div>' . "\n" .
                                   '</form>',
                'email_to'      => get_bloginfo( 'admin_email' ),
                'email_subject' => 'Enquiry',
                'email_body'    => 'Name: [name]' . "\n" .
                                   'Email: [email]' . "\n" .
                                   'Message:' . "\n" .
                                   '[message]'
            );

            /* INITIALIZE VARIABLES */
            $this->name_plurial  = $this->name . 's';
            $this->label         = ucwords( preg_replace( '/[_.-]+/', ' ', $this->name ) );
            $this->label_plurial = ucwords( preg_replace( '/[_.-]+/', ' ', $this->name_plurial ) );

            /* CREATE DB TABLE IF DOESN'T EXIST */
            add_action( 'init', array( $this, 'init' ), 1 );
            add_action( 'switch_blog', array( $this, 'init' ) );

            /* SAVE SETTINGS */
            add_action( 'admin_init', array( $this, 'save' ) );

            /* ADD MENU TO SETTINGS TAB */
            add_action( 'admin_menu', array( $this, 'add_menu' ) );

            /* AJAX */
            add_action( 'wp_ajax_nopriv_contact-form-submit', array( $this, 'submit' ) );
            add_action( 'wp_ajax_contact-form-submit', array( $this, 'submit' ) );

            /* REGISTER SCRIPTS & STYLE */
            add_action( 'admin_init', array( $this, 'register_admin_scripts' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

            /* SHORTCODE */
            add_shortcode( 'contact-form', array( $this, 'shortcode' ) );

            /* FIX WIDGET_TEXT GENERATE SHORTCODE */
            add_filter( 'widget_text', 'do_shortcode' );
        }

        /* CREATE DB IF DOESN'T EXIST */
        public function init() {
            global $wpdb;
            global $charset_collate;

            // Set table name
            $this->db_table = $wpdb->prefix . 'contact_forms';

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

            $sql_create_table = "CREATE TABLE {$this->db_table} (
                id bigint(20) unsigned NOT NULL auto_increment,
                form_name varchar(127),
                form_datas longtext,
                submitted_from varchar(15),
                submitted_date datetime NOT NULL default '0000-00-00 00:00:00',
                PRIMARY KEY  (id)
                ) $charset_collate; ";

            dbDelta( $sql_create_table );
        }

        /* SET COLUMNS FORMAT */
        public function db_get_columns() {
            return array(
                'form_name'      => '%s',
                'form_datas'     => '%s',
                'submitted_from' => '%s',
                'submitted_date' => '%s'
            );
        }

        /* INSERT DATAS IN DB */
        public function db_insert( $datas = array() ) {
            global $wpdb;

            if ( ! $datas )
                return false;

            $datas['submitted_date'] = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ), true );

            $wpdb->insert( $this->db_table, $datas, $this->db_get_columns() );
            return $wpdb->insert_id;
        }

        /* DELETE DATAS FROM DB */
        public function db_delete( $id ) {
            global $wpdb;

            $id = absint( $id );

            if ( empty( $id ) || ! current_user_can( 'manage_options' ) )
                return false;

            $sql = $wpdb->prepare( "DELETE from {$this->db_table} WHERE id = %d", $id );

            if( ! $wpdb->query( $sql ) )
                 return false;

             return true;
        }

        /* GET DATAS FROM DB */
        public function db_get( $query = array() ) {
            global $wpdb;

            // Parse defaults
            $defaults = array(
                'fields'     => array(),
                'orderby'    => 'datetime',
                'order'      => 'desc',
                'id'         => false,
                'since'      => false,
                'until'      => false,
                'number'     => 10,
                'offset'     => 0
            );
            extract( wp_parse_args( $query, $defaults ) );

            // Clean fields
            $allowed_fields = $this->db_get_columns();
            if ( is_array( $fields ) ) {
                $fields = array_map( 'strtolower', $fields );
                $fields = array_intersect( $fields, $allowed_fields );
            } else {
                $fields = strtolower( $fields );
            }

            /* Create query */

            // Select
            if( empty( $fields ) ) {
                $select_sql = "SELECT * FROM {$this->db_table}";
            } elseif ( 'count' == $fields ) {
                $select_sql = "SELECT COUNT(*) FROM {$this->db_table}";
            } else {
                $select_sql = "SELECT " . implode( ',', $fields ) . " FROM {$this->db_table}";
            }

            // Where
            $where_sql = 'WHERE 1=1';

            if ( ! empty( $id ) )
                $where_sql .=  $wpdb->prepare( ' AND id=%d', $id );

            $since = absint( $since );
            $until = absint( $until );

            if ( ! empty( $since) )
                $where_sql .=  $wpdb->prepare( ' AND activity_date >= %s', date_i18n( 'Y-m-d H:i:s', $since, true ) );

            if ( ! empty( $until ) )
                $where_sql .=  $wpdb->prepare( ' AND activity_date <= %s', date_i18n( 'Y-m-d H:i:s', $until, true ) );

            // Order
            $order = strtoupper( $order );
            $order = ( 'ASC' == $order ? 'ASC' : 'DESC' );
            switch( $orderby ){
                case 'id':
                    $order_sql = "ORDER BY id $order";
                    break;
                case 'name':
                    $order_sql = "ORDER BY form_name $order";
                    break;
                case 'from':
                    $order_sql = "ORDER BY submitted_from $order";
                    break;
                case 'datetime':
                default:
                    $order_sql = "ORDER BY submitted_date $order";
            }

            // Limit
            $offset = absint( $offset );
            if( $number == -1 ) {
                $limit_sql = "";
            } else {
                $number = absint( $number );
                $limit_sql = " LIMIT $offset, $number";
            }

            // Do SQL
            $sql = "$select_sql $where_sql $order_sql $limit_sql";
            if( 'count' == $fields )
                return $wpdb->get_var( $sql );

            $datas = $wpdb->get_results( $sql );

            return $datas;
        }

        /**
         * ADD ADMIN MENU
         */
        public function add_menu() {
            add_menu_page( 'Dashboard', 'Contacts', 'manage_options', $this->name, array( $this, 'dashboard' ), '', 58 );
            add_submenu_page( $this->name, 'Settings', 'Settings', 'manage_options', $this->name . '-settings', array( $this, 'settings' ) );
        }

        /**
         * SAVE DATAS FROM SETTING PAGE
         */
        public function save() {
            if ( ! current_user_can( 'manage_options' ) )
                return;

            if ( isset( $_GET['page'] ) ) {
                switch ( $_GET['page'] ) {
                    case $this->name . '-settings':

                        if( isset( $_POST['action'] )
                            && $_POST['action'] == 'save' ) {

                            //Security check, data comming from the right form:
                            if ( ! isset( $_POST[ $this->name . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ $this->name . '_nonce' ], plugin_basename( __FILE__ ) ) )
                                return;

                            if ( array_key_exists( 'forms', $_POST ) )
                                $datas = $_POST['forms'];
                            else
                                $datas = '';

                            //Update options & display notification if success.
                            if( update_option( $this->name, $datas ) )
                                $this->notification = 'Settings saved.';

                        }
                        break;
                    case $this->name:

                        if( isset( $_GET['action'] )
                            && $_GET['action'] == 'delete'
                            && isset( $_GET['contact_id'] )
                            && is_numeric( $_GET['contact_id'] ) ) {

                            //Security check, data comming from the right form:
                            if ( ! isset( $_GET[ '_nonce' ] ) || ! wp_verify_nonce( $_GET[ '_nonce' ], plugin_basename( __FILE__ ) ) )
                                return;

                            if ( $this->db_delete( $_GET['contact_id'] ) )
                                $this->notification = 'Contact deleted.';
                            else
                                $this->notification = 'Cannot delete this contact.';
                        }
                        break;
                }
            }
        }

        /**
         * DISPLAY DASHBOARD PAGE
         */
        public function dashboard() {
            $datas = $this->db_get();
            ?>
            <div class="wrap">
                <?php screen_icon(); ?><h2>Dashboard</h2>

                <?php if ( isset ( $this->notification ) ): ?>
                    <div id="notification" class="fade">
                        <div class="notice info"><?php echo $this->notification; ?></div>
                    </div>
                <?php endif; ?>
                <div class="maindesc">
                    <p>In this area, you can manage stored datas submited via contact form.</p>
                </div>
                <table class="wp-list-table widefat fixed pages" cellspacing="0">
                    <tr>
                        <th class="manage-column">ID</th>
                        <th class="manage-column">Form Name</th>
                        <th class="manage-column">Form Datas</th>
                        <th class="manage-column">From</th>
                        <th class="manage-column">Date</th>
                        <th class="manage-column">Action</th>
                    </tr>
                    <?php foreach ( $datas as $data ): ?>
                    <tr>
                        <td><?php echo $data->id; ?></td>
                        <td><?php echo $data->form_name; ?></td>
                        <td><?php echo $data->form_datas; ?></td>
                        <td><?php echo $data->submitted_from; ?></td>
                        <td><?php echo $data->submitted_date; ?></td>
                        <td><a href="?page=<?php echo $this->name; ?>&action=delete&contact_id=<?php echo $data->id; ?>&_nonce=<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ); ?>" class="button">Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php
        }

        public function settings() {
            // Get template
            $admin_form_tpl = file_get_contents( dirname( __FILE__ ) . '/admin-form.php' );
            ?>
            <div class="wrap">
                <?php screen_icon(); ?><h2>Contact Form Settings</h2>

                <?php if ( isset ( $this->notification ) ): ?>
                    <div id="notification" class="fade">
                        <div class="notice info"><?php echo $this->notification; ?></div>
                    </div>
                <?php endif; ?>

                <div class="maindesc">
                    <p>In this area, you can manage settings of the contact form.</p>
                    <h4>Validation:</h4>
                    <ul>
                        <li>Using <kbd>&lt;input type="email" /&gt;</kbd> automatically validate email address.</li>
                        <li>Using <kbd>&lt;input class="required" /&gt;</kbd> automatically validate if value empty.</li>
                    </ul>
                </div>

                <form method="post">
                    <?php
                    wp_nonce_field( plugin_basename( __FILE__ ), $this->name . '_nonce' );

                    //Get options from DB
                    $forms = get_option( $this->name );
                    ?>
                    <input type="hidden" name="action" value="save" />
                    <table class="form-table">
                        <tr>
                            <td align="right">
                                <button class="add-form button">Add New Form</button> <button type="submit" class="button button-primary">Save</button>
                            </td>
                        </tr>
                    </table>

                    <div id="forms">
                        <?php

                        if ( ! $forms )
                            $forms[] = $this->default_values;

                        foreach ( $forms as $index => $datas ):
                            $defaults = array(
                                'form_name'      => '',
                                'form_html'      => '',
                                'email_to'       => '',
                                'email_subject'  => '',
                                'email_body'     => '',
                                'index'          => $index
                            );
                            $datas = wp_parse_args( $datas, $defaults );

                            echo $this->render_tpl( $admin_form_tpl, $datas );

                        endforeach;
                        ?>
                    </div>

                    <?php // Handlebar Template ?>
                    <script id="contact-form-default" type="text">
                        <?php echo $this->render_tpl( $admin_form_tpl, $this->default_values ); ?>
                    </script>

                    <table class="form-table">
                        <tr>
                            <td align="right">
                                <button class="add-form button">Add New Form</button> <button type="submit" class="button button-primary">Save</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            <?php
        }


        /**
         * SUBMIT EMAIL & STORE DATA IN DB
         */
        public function submit() {
            $errors = array();

            // Check for nonce security
            $nonce = $_POST['nonce'];
            if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) )
                $this->output( array(
                    'status'    => false,
                    'error'     => 'Nonce incorrect'
                ) );

            //Extract values
            $posted_datas = wp_parse_args( $_POST );

            if ( ! isset( $posted_datas['form_id'] ) )
                $this->output( array(
                    'status'    => false,
                    'error'     => 'Something goes wrong.'
                ) );

            //Get Form
            $form = $this->get_form( $posted_datas['form_id'] );

            if ( ! $form )
                $this->output( array(
                    'status'    => false,
                    'error'     => 'Something goes wrong.'
                ) );

            $email_body = nl2br( $form['email_body'] );

            //Get tags & replace by values
            preg_match_all( '/\[([^\]]+)\]/', $email_body, $email_body_tags );

            if ( ! $email_body_tags )
                $this->output( array(
                    'status'    => false,
                    'error'     => 'Something goes wrong.'
                ) );

            foreach( $email_body_tags[0] as $index => $email_body_tag )
                $email_body = str_replace( $email_body_tag, nl2br( strip_tags( $posted_datas[ $email_body_tags[1][ $index ] ] ) ), $email_body );


            //Check form valid
            $errors = $this->validate_form( stripslashes( $form['form_html'] ), $posted_datas );
            if ( $errors ) {
                $this->output( array(
                    'status'    => false,
                    'error'     => 'One or many fields are not valid: <br />- ' . implode( '<br />- ', $errors )
                ) );
            } else {
                $from    = '';
                $subject = $form['email_subject'];

                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

                $message = '<table style="border-color: #666;" cellpadding="10">';
                $message .= '<tr><td>' . $email_body . '</td></tr>';
                $message .= '</table>';

                $this->db_insert( array(
                    'form_name'      => $form['form_name'],
                    'form_datas'     => $email_body ,
                    'submitted_from' => $_SERVER['REMOTE_ADDR']
                ) );

                $to_list = explode( ',', $form['email_to'] );

                $errors = array();
                foreach ( $to_list as $to ) {
                    $errors[] = $this->mailing->send_email( array(
                        'email_to' => trim( $to ),
                        'subject'  => $subject,
                        'tags'     => array(
                            'title'     => $subject,
                            'content'   => $message
                    ) ) );
                }

                if ( in_array( true, $errors, true ) ) {
                    $this->output( array(
                        'status'    => true,
                        'message'   => 'Mail sent!'
                    ) );
                } else {
                    $this->output( array(
                        'status'    => false,
                        'error'     => 'Problem when sending the mail.'
                    ) );
                }
            }
        }

        /**
         * Validate form
         * Email validation and required values
         */
        private function validate_form( $form, $datas ) {
            $errors = array();

            // Input fields
            preg_match_all( '/<input[^>]+>/', $form, $inputs );
            foreach ( $inputs[0] as $input ) {

                // Get field name
                preg_match( '/name=[\'"]([A-Za-z0-9-_ ]+)+[\'"]/', $input, $name );

                // Get field type
                preg_match( '/type=[\'"]([A-Za-z0-9-_]+)+[\'"]/', $input, $type );

                // Get field class
                preg_match( '/class=[\'"]([A-Za-z0-9-_ ]+)+[\'"]/', $input, $class );

                // Get field placeholder
                preg_match( '/placeholder=[\'"]([A-Za-z0-9-_ ]+)+[\'"]/', $input, $placeholder );


                // Required fields validation:
                if ( preg_match( '/required/', $class[1] ) && empty( $datas[ $name[1] ] ) )
                    $errors[] = 'Field ' . $placeholder[1] . ' required.';

                if ( $type[1] == 'email' && ! is_email( $datas[ $name[1] ] ) )
                    $errors[] = 'Email address not valid.';

            }

            if ( count( $errors ) )
                return $errors;
            else
                return false;
        }


        /**
         * Get Form from DB
         * @param  String    $form   String: Form name or MD5: Form name as MD5
         * @return boolean           Return form or false if not found
         */
        private function get_form( $form ) {
            $forms = get_option( $this->name );

            foreach ( $forms as $v )
                if ( $form == md5( $v['form_name'] ) || $form == $v['form_name'] )
                    return $v;

            return false;
        }


        /**
         * Register Admin Script
         */
        public function register_admin_scripts() {
            wp_register_script( $this->name . '_admin_script', TP_PLUGIN_DIRECTORY_WWW . '/' . $this->name . '/assets/admin.js',  array( 'jquery' ) );
            wp_register_style( $this->name . '_admin_style', TP_PLUGIN_DIRECTORY_WWW . '/' . $this->name . '/assets/admin.css' );
        }

        /**
         * Enqueue Admin Scripts
         */
        public function enqueue_admin_scripts() {
            if( isset( $_GET['page'] ) && ( $_GET['page'] == $this->name || $_GET['page'] == $this->name . '-settings' ) ) {
                wp_enqueue_script( $this->name . '_admin_script' );
                wp_enqueue_style( $this->name . '_admin_style' );
            }
        }

        /**
         * Generate Shortcode
         */
        public function shortcode( $atts ) {

            extract( shortcode_atts( array(
                'name' => ''
            ), $atts ) );

            if ( empty( $name ) )
                return;

            // Get form
            //$form = $this->db_get( "form_name=$name" );
            $forms = get_option( $this->name );

            if ( ! $forms )
                return;

            foreach ( $forms as $f ) {
                if ( $name == $f['form_name'] ) {

                    //Inject form ID as hidden input field and inject "contact-form" class to the form tag
                    $pattern = '/<form([\W]+)class=(["\']?)([^"\']+)["\']?([^>]+)>/i';
                    $replacement = '<form data-remote="true" data-reset="true" ' . "$1" . ' class=' . "$2" . 'contact-form ' . "$3" . "$4" . "$5" . '>' . "\n" .
                                   '<input type="hidden" name="action" value="contact-form-submit" />' . "\n" .
                                   '<input type="hidden" name="form_id" value="' . md5( $f['form_name'] ) . '" />';

                    return preg_replace( $pattern, $replacement, stripslashes( $f['form_html'] ) );
                }
            }
        }
    }
}