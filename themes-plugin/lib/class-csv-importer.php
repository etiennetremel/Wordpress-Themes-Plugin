<?php
/**
 * Class CSV Importer
 * Author: Etienne Tremel
 */

if ( ! class_exists( 'CSV_Importer' ) ) {
	class CSV_Importer {
		private $file;

		public $datas;
		public $args = array(
			'allowedTypes'	=> array( "text/comma-separated-values", "application/vnd.ms-excel", "text/csv" ),
			'allowedExts'	=> array( "csv" ),
			'max_file_size'	=> 20000,
			'delimiter'		=> ","
		);
		public $errors = array();

		function __construct( $file, $args = array() ) {

			$this->file = $file;

			$this->args = array_merge( $this->args, $args );
			$extension = end( explode( '.', $file['name'] ) );

			if ( in_array( $file['type'], $this->args['allowedTypes'] ) && in_array( $extension, $this->args['allowedExts'] ) && ( $file['size'] < $this->args['max_file_size'] ) ) {

				if ( $file['error'] > 0 ) {
			    	$this->errors[] = 'Return Code: ' . $file['error'];
			    } else {
				    $csv = array();

				    if ( ( $handle = fopen( $file["tmp_name"], "r") ) !== FALSE ) {

			            $rowCounter = 0;
			            while ( ( $rowData = fgetcsv( $handle, 0, $this->args['delimiter'] ) ) !== FALSE ) {
			                if( 0 === $rowCounter) {
			                    $headerRecord = $rowData;
			                } else {
			                    foreach( $rowData as $key => $value ) {
			                        $csv[ $rowCounter - 1][ $headerRecord[ $key] ] = $value;  
			                    }
			                }
			                $rowCounter++;
			            }
			            fclose( $handle );
			        }

				    $this->datas = $csv;
				}
			}
		}

		public function get_datas() {
			return $this->datas;
		}

		public function get_number_rows() {
			return sizeof( $this->datas );
		}

		public function get_file_size() {
			return ( $this->file['size'] / 1024 ) . 'kb';
		}

		public function get_file_name() {
			return $this->file['name'];
		}

		public function get_errors() {
			if ( $this->errors )
				return $this->errors;
			else
				return false;
		}

		public function get_header() {
			return array_keys( $this->datas[0] );
		}

		public function display_csv() {
			echo '<table border="0" cellpadding="0" cellspacing="5">';

			echo '<tr>';
			foreach ( $this->get_header() as $column ) {
				echo '<th>' . $column . '</th>';
			}
			echo '</tr>';
			foreach ( $this->get_datas() as $row ) {
				echo '<tr>';
				foreach ( $row as $column ) {
					echo '<td>' . $column . '</td>';
				}
				echo '</tr>';
			}
			echo '</table>';
		}
	}
}
?>