<?php
/**
 * Class WooCommerce Importer
 * Author: Etienne Tremel
 */
if ( ! class_exists( 'WooCommerce_Product_Importer' ) ) {
	class WooCommerce_Product_Importer {

		private $datas;
		public $errors = array();
		public $notices = array();
		public $options = array(
			'category_delimiter'	=> '|',
			'subcategory_delimiter'	=> '>',
			'images_delimiter'		=> '|',
			'tags_delimiter'		=> '|'
		);

		function __construct( $datas = array(), $options = array() ) {
			if ( is_array( $datas ) ) {
				$this->datas = $datas;
				$this->prepare_product();

				$this->options = array_merge( $this->options, $options );
			} else {
				$this->errors[] = 'Parameter should be an array';
			}
		}

		public function prepare_product() {
			global $user_ID, $wpdb;

			$post_type = 'product';
			$cat_taxonomy_type = 'product_cat';

			if ( post_type_exists( $post_type ) )

			foreach ( $this->datas as $data ) {

				//Prepare new product data to insert
				$product_data = array(
					'post_author' 		=> $user_ID,
					'post_title' 		=> $data['name'],
					'post_status' 		=> 'draft',
					'comment_status' 	=> 'closed',
					'ping_status' 		=> 'closed',
					'post_type' 		=> $post_type,
					'post_content' 		=> $data['description'],
					'post_excerpt' 		=> $data['excerpt']
				);

				//Check if the product is already in the DB
				$product_id_in_db = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $data['sku'] ) );

				if ( $product_id_in_db )
					$product_data['ID'] = $product_id_in_db;

				$product_id = wp_insert_post( $product_data );


				//Updates metas:

				//SKU
				if ( isset( $data['sku'] ) && $data['sku'] ) 
					update_post_meta( $product_id, '_sku', $data['sku'] );

				//Quantity in stock
				if ( isset( $data['quantity'] ) && $data['quantity'] ) 
					update_post_meta( $product_id, '_stock', $data['quantity'] );

				//Sale price
				if ( isset( $data['sale_price'] ) && $data['sale_price'] ) 
					update_post_meta( $product_id, '_price', $data['sale_price'] );

				//Regular price
				if ( isset( $data['regular_price'] ) && $data['regular_price'] ) 
					update_post_meta( $product_id, '_regular_price', $data['regular_price'] );

				//Weight
				if ( isset( $data['weight'] ) && $data['weight'] ) 
					update_post_meta( $product_id, '_weight', $data['weight'] );

				//Length
				if ( isset( $data['length'] ) && $data['length'] ) 
					update_post_meta( $product_id, '_length', $data['length'] );

				//Width
				if ( isset( $data['width'] ) && $data['width'] ) 
					update_post_meta( $product_id, '_width', $data['width'] );

				//Height
				if ( isset( $data['height'] ) && $data['height'] ) 
					update_post_meta( $product_id, '_height', $data['height'] );

				//Tags
				if ( isset( $data['tags'] ) )
					wp_set_object_terms( $product_id, explode( $this->options['tags_delimiter'], $data['tags'] ), 'product_tag', true );

				//Images
				if ( isset( $data['images'] ) ) {
					$images = explode( $this->options['images_delimiter'], $data['images'] );
					foreach ( $images as $index => $file ) {

						$size = getimagesize( $file );
						$image = @file_get_contents( $file );
						$extension = image_type_to_extension( $size[2] );
						$image_type = image_type_to_mime_type( $size[2] );

						$extension = ( '.jpeg' == $extension ) ? '.jpg' : $extension;

						if ( false !== $image ) {
							$wp_upload_dir = wp_upload_dir();

							$filename = sanitize_title_with_dashes( $data['name'] ) . $extension;
							$file_url = $wp_upload_dir['url'] . '/' . $filename;
							$file_path = $wp_upload_dir['path'] . '\\' . $filename;
							
							if ( file_put_contents( $file_path, $image ) ) {
								$wp_filetype = wp_check_filetype( $image, null );
								$attachment = array(
									'guid' 				=> $file_url, 
									'post_mime_type' 	=> $image_type,
									'post_title' 		=> $data['name'],
									'post_content' 		=> '',
									'post_status' 		=> 'inherit'
								);
								$image_id = wp_insert_attachment( $attachment, $file_url, $product_id );

								require_once( ABSPATH . 'wp-admin/includes/image.php' );
								$image_metas = wp_generate_attachment_metadata( $image_id, $file_url );
								wp_update_attachment_metadata( $image_id, $image_metas );

								//Set product thumbnail:
								if ( 0 == $index )
									set_post_thumbnail( $product_id, $image_id );
							}
						}
					}
				}

				//Category
				$categories = explode ( $this->options['category_delimiter'], $data['categories'] );
				foreach ( $categories as $category ) {
					$taxonomies = explode( $this->options['subcategory_delimiter'], $category );
					$parent = false;
					foreach ( $taxonomies as $taxonomy) {
						$new_category = term_exists( $taxonomy, $cat_taxonomy_type );

						//Create category if not already existing:
						if ( ! is_array( $new_category ) ) {
							$cat_args = array(
								'slug' 			=> $taxonomy,
								'parent'		=> $parent
							);

							//Add description to category if defined
							if ( isset( $data['category_description'] ) && $data['category_description'] )
								$cat_args['description'] = $data['category_description'];

							$new_category = wp_insert_term(	$taxonomy, $cat_taxonomy_type, $cat_args );
						}
						
						wp_set_object_terms( $product_id, (int)$new_category['term_id'], $cat_taxonomy_type, true );
						$parent = $new_category['term_id'];
						
						delete_option( $cat_taxonomy_type . '_children' );
					}
					unset( $parent );	
				}

				$this->notices[] = 'New product added: ' . $data['sku'];
			}
		}

		public function get_notices() {
			return $this->notices;
		}

		public function get_errors() {
			if ( sizeof( $this->errors ) )
				return $this->errors;
			else
				return false;
		}
	}
}
?>