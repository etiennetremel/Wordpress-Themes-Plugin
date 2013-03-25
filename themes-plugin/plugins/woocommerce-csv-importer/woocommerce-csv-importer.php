<?php
/*
Plugin Name: WooCommerce CSV Importer
Version: 0.1
Description: Import product in WooCommerce from a CSV file.
Author: Etienne Tremel
*/
if ( ! class_exists( 'WooCommerce_CSV_Importer' ) ) {

    include_once( 'class-woocommerce-product-importer.php' );

    class WooCommerce_CSV_Importer {
        private $notifications = array();
        private $layout;
        private $woocommerce_default_fields = array(
            'sku'                     => 'SKU',
            'name'                    => 'Product Name',
            'description'             => 'Description',
            'excerpt'                 => 'Excerpt',
            'type'                    => 'Type',
            'visibility'              => 'Visibility',
            'featured'                => 'Featured',
            'permalink'               => 'Permalink',
            'regular_price'           => 'Regular Price',
            'sale_price'              => 'Sale Price',
            'weight'                  => 'Weight',
            'length'                  => 'Length',
            'width'                   => 'Width',
            'height'                  => 'Height',
            'images'                  => 'Images',
            'categories'              => 'Categories',
            'category_description'    => 'Category Description',
            'tags'                    => 'Tags',
            'tax_status'              => 'Tax Status',
            'tax_class'               => 'Tax Class',
            'manage_stock'            => 'Manage Stock',
            'quantity'                => 'Quantity in stock',
            'stock_status'            => 'Stock Status',
            'allow_backorders'        => 'Allow Backorders',
            'sort'                    => 'Sort Order',
            'product_url'             => 'Product URL',
            'customizable'            => 'Customizable',
            'status'                  => 'Status',
            'comment_status'          => 'Comment Status'
        );

        public function __construct() {
            /* SAVE SETTINGS, ADD IT TO APPEARANCE MENU */
            add_action( 'admin_init', array( $this, 'init' ) );

            /* ADD MENU TO APPEARANCE TAB */
            add_action( 'admin_menu', array( $this, 'add_menu' ) );
        }

        public function init() {

            if ( ! current_user_can( 'edit_themes' ) )
                return;

            //Initialise notifications:
            $this->notifications = array();

            $step = ( isset( $_REQUEST['step'] ) ) ? $_REQUEST['step'] : 'start';

            switch( $step ) {
                case 'upload':
                    $this->notifications[] = array( 'notice' => 'Uploaded.', 'type' => 'success' );

                    $args = array(
                        'delimiter' => htmlentities( $_REQUEST['field_delimiter'] )
                    );

                    $csv_datas = new CSV_Importer( $_FILES['file'], $args );

                    set_transient( 'tp_woocommerce_importer_csv', $csv_datas->get_datas(), 60*60 ); //Store data as Transient for 1hour

                    $errors = $csv_datas->get_errors();

                    if ( $errors ) {
                        $this->notifications[] = array( 'notice' => $errors.join('<br />'), 'type' => 'error' );
                    } else {
                        $this->notifications[] = array(
                            'notice' => array(
                                'File uploaded: ' . $csv_datas->get_file_name(),
                                'Size: ' . $csv_datas->get_file_size(),
                                'Number of items: ' . $csv_datas->get_number_rows()
                            ),
                            'type' => 'info'
                        );

                        // get existing values form DB and associate it to the field:
                        $associated_datas = get_option( 'tp_woocommerce_importer' );

                        $fields = '';
                        foreach( $this->woocommerce_default_fields as $key => $value ) {
                            
                            $csv_fields = '<option></option>';
                            foreach( $csv_datas->get_header() as $csv_field ) {
                                $selected = ( $key == $csv_field || $value == $csv_field || ( isset( $associated_datas[ $key ] ) && $associated_datas[ $key ] == $csv_field ) ) ? 'selected' : '';
                                $csv_fields .= '<option value="' . $csv_field . '" ' . $selected . '>' . $csv_field . '</option>';
                            }

                            $fields .= '<div class="form-field">
                                            <div class="source field-group">
                                                <select name="field[' . $key . ']">' . $csv_fields . '</select>
                                            </div>
                                            &raquo;
                                            <div class="target field-group">
                                                <input name="' . $key . '" value="' . $value . '" />
                                            </div>
                                        </div>';
                        }

                        $this->layout = '<form method="post">
                                            <div class="section_title"><em>Associate fields to target WooCommerce fields:</em></div>
                                             ' . $fields . '
                                             <input type="hidden" name="step" value="add_products" />
                                            <input type="submit" name="submit" id="submit" class="button-primary" value="Add Products" />
                                        </form>';
                    }

                    break;

                case 'add_products':
                    $associated_datas = $_REQUEST['field'];

                    //Update options in DB:
                       update_option( 'tp_woocommerce_importer', $associated_datas );
                       if ( false === get_transient( 'tp_woocommerce_importer_csv' ) )
                           $this->notifications[] = array( 'notice' => 'No datas provided.', 'type' => 'error' );
                       else
                           $csv_datas = get_transient( 'tp_woocommerce_importer_csv' );

                       //Prepare data before import:
                       $prepared_datas = array();
                       foreach ( $csv_datas as $index => $data) {
                           foreach ( $associated_datas as $woocommerce_key => $csv_key ) {
                               if ( $csv_key )
                                   $prepared_datas[ $index ][ $woocommerce_key ] = $data[ $csv_key ];
                               else
                                   $prepared_datas[ $index ][ $woocommerce_key ] = '';
                           }
                       }
                    
                    $options = array(
                        'category_delimiter'    => htmlentities( $_REQUEST['category_delimiter'] ),
                        'subcategory_delimiter'    => htmlentities( $_REQUEST['subcategory_delimiter'] ),
                        'images_delimiter'        => htmlentities( $_REQUEST['images_delimiter'] ),
                        'tags_delimiter'        => htmlentities( $_REQUEST['tags_delimiter'] )
                    );

                    $importer = new WooCommerce_Product_Importer( $prepared_datas, $options );
                    $errors = $importer->get_errors();

                    $this->layout = '';

                    if ( $errors ) {
                        $this->notifications[] = array( 'notice' => $errors.join('<br />'), 'type' => 'error' );
                    } else {
                        $this->notifications[] = array(
                            'notice'     => $importer->get_notices(),
                            'type'        => 'info'
                        );
                    }

                    break;
                case 'start':
                default:
                    $csv_importer_fields = array(
                        array( 
                            'type'             => 'section',
                            'label'         => 'File'
                        ), array(
                            'type'            => 'file',
                            'label'            => 'CSV File',
                            'name'            => 'file',
                            'description'    => 'CSV File to import. (Maximum size: ' . (int)(ini_get('upload_max_filesize')) . 'MB)'
                        ), array(
                            'type'            => 'text',
                            'label'            => 'Field delimiter',
                            'name'            => 'field_delimiter',
                            'default_value'    => ',',
                            'description'    => 'Delimiter character separating each cell in the CSV. Usually the "," (coma) character.'
                        ), array(
                            'type'            => 'text',
                            'label'            => 'Category delimiter',
                            'name'            => 'category_delimiter',
                            'default_value'    => '|',
                            'description'    => 'Delimiter character separating categories, default: "|" (pipe)'
                        ), array(
                            'type'            => 'text',
                            'label'            => 'Subcategory delimiter',
                            'name'            => 'subcategory_delimiter',
                            'default_value'    => '>',
                            'description'    => 'Delimiter character separating subcategory, default: ">" (right arrow)'
                        ), array(
                            'type'            => 'text',
                            'label'            => 'Image delimiter',
                            'name'            => 'image_delimiter',
                            'default_value'    => '|',
                            'description'    => 'Delimiter character separating images, default: "|" (pipe)'
                        ), array(
                            'type'            => 'text',
                            'label'            => 'Tags delimiter',
                            'name'            => 'tags_delimiter',
                            'default_value'    => '|',
                            'description'    => 'Delimiter character separating tags, default: "|" (pipe)'
                        )
                    );
                    $csv_importer_form = new Custom_Form();

                    $this->layout = '<form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="woocommerce-importer_nonce" value="' . wp_create_nonce( 'woocommerce-importer_nonce' ) . '" />
                                ' . $csv_importer_form->get_fields( $csv_importer_fields ) . '
                                <input type="hidden" name="step" value="upload" />
                                <input type="submit" name="submit" id="submit" class="button-primary" value="Import File and Define Columns" />
                            </form>';
            }
        }

        public function add_menu() {
            add_management_page( 'WooCommerce CSV Importer', 'WooCommerce Importer', 'manage_options', 'woocommerce-importer', array( $this, 'woocommerce_importer' ) );
        }

        public function woocommerce_importer() {
            ?>
            <div class="wrap columns-1">
                <?php screen_icon(); ?><h2>Theme Settings</h2>

                <div class="maindesc">
                    <p>Import CSV file into products for WooCommerce.</p>
                </div>

                <?php $this->display_notifications(); ?>
                
                <div class="options_wrap">
                    <?php echo $this->layout; ?>
                </div>
            </div>
            <?php
        }

        function display_notifications() {            
            if ( sizeof( $this->notifications ) ) {
                echo '<div id="notifications" class="fade">';
                foreach( $this->notifications as $notification ) {
                    if ( ! empty( $notification['notice'] ) ) {
                        if ( is_array( $notification['notice'] ) )
                            echo '<p class="notice ' . $notification['type'] . '">' . implode( '<br />', $notification['notice'] ) .'</p>';
                        else
                            echo '<p class="notice ' . $notification['type'] . '">' . $notification['notice'] . '</p>';
                    }
                }
                echo '</div>';
            }
        }
    }
}