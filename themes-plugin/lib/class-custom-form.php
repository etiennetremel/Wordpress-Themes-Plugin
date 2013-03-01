<?php

/**
 * CUSTOM FORM CLASS
 * Author: Etienne Tremel
 * Date: 11/10/2012
 * Description: Generate form with field
 * Usage:
 *   $settings = array(
 *       'form_id'           => '',
 *       'form_name'         => '',
 *       'form_action_url'   => '',
 *       'form_method'       => 'POST',
 *       'fields'            => array(
 *           array(
 *               'type'              => '', //section, fieldset, text, submit
 *               'name'              => '',
 *               'label'             => '',
 *               'default_value'     => '', 
 *               'description'       => '',
 *               'attributes'              => '', //array of additional attributes Key = attribute name and Value = attribute value
 *               'options'           => ''  //for select field only, array of options with array( array( 'label' => 'My label', 'value' => 'value' ) )
 *           )
 *       );
 *   );
 *
 *   $form = new Custom_Form( $settings );
 */

if ( ! class_exists( 'Custom_Form' ) ) {
    class Custom_Form {

        public $form    = array(
            'form_id'         => 'form',
            'form_name'       => '',
            'form_action_url' => '',
            'form_method'     => 'POST'
        );
        public $field   = array();
        
        public function __construct ( $settings = array() ) {
            
            if ( ! empty ( $settings ) ) {
                //Open tags
                $custom_form =  $this->start_main() .
                                $this->start_form();

                //Output fields
                foreach ( $this->fields as $field ) {
                    return $this->get_field(
                        $field->type,
                        $field->name,
                        $field->label,
                        $field->default_value,
                        $field->description,
                        $field->attributes,
                        $field->options,
                        $field->level
                    );
                }

                //Close tags
                $custom_form .= $this->end_form() .
                                $this->end_main();
            }
        }

        public function start_main() {
            return '<div class="options_wrap">';
        }

        public function end_main() {
            return '</div>';
        }

        public function start_form() {
            return '<form id="' . $this->form_id . '" name="' . $this->form_name . '" method="' . $this->form_method . '" action="' . $this->form_action_url . '">
                        <table class="form-table">';
        }

        public function end_form() {
            return '    </table>
                    </form>';
        }

        public function before_field( $name = '' ) {
            return '<p class="form-field ' . ( ( ! empty( $name ) ) ? 'fied-' . $name : '' ) . '">';
        }

        public function after_field() {
            return '</p>';
        }

        public function get_label( $name, $label ) {
            return '<label for="' . $name . '">' . $label . '</label>';
        }

        public function get_description( $description ) {
            return '<span class="short-description ">
                        <em>' . $description . '</em>
                    </span>';
        }

        public function get_fields( $fields ) {
            $output = "";
            if ( ! $fields ) return 'No fields set.';
            foreach( $fields as $field ) {
                $output .= $this->get_field( $field );
            }
            return $output;
        }

        public function get_field( $field ) {
            if ( is_array( $field ) ) {
                $type           = isset( $field['type'] ) ? $field['type'] : '';
                $name           = isset( $field['name'] ) ? $field['name'] : '';
                $label          = isset( $field['label'] ) ? $field['label'] : '';
                $default_value  = isset( $field['default_value'] ) ? $field['default_value'] : '';
                $description    = isset( $field['description'] ) ? $field['description'] : '';
                $attributes     = isset( $field['attributes'] ) ? $field['attributes'] : array();
                $options        = isset( $field['options'] ) ? $field['options'] : array();

                $attr = "";
                if ( $attributes ) {
                    foreach ( $attributes as $attr_name => $value ) {
                        $attr .= $attr_name . '="' . $value . '" ';
                    }
                }

                switch( $type ) {
                    case "section":
                        //append default class to attributes if existing:
                        $attr = preg_replace( '/class="([^"]+)"/', 'class="section_title $1"', $attr );
                        if( empty( $attr ) )
                            $attr = 'class="section_title"';
                        $output = '<p ' . $attr . '>' . $label . '</p>';
                        break;
                    case "paragraphe":
                        $attr = preg_replace( '/class="([^"]+)"/', 'class="section_title $1"', $attr );
                        if( empty( $attr ) )
                            $attr = 'class="paragraphe"';
                        $id = ( $name ) ? 'id="' . $name . '"' : '';
                        $output = '<p '. $id . ' ' . $attr . '>' . $label . '</p>';
                         break;
                    case "fieldset_start":
                        $id = ( $name ) ? 'id="' . $name . '"' : '';
                        $output =   '<fieldset '. $id . ' ' . $attr . '>';
                        $output .=  ( isset( $label ) ) ? '<legend>' . $label . '</legend>' : '';
                        $output .=  ( isset( $default_value ) ) ? $default_value : '';
                        break;
                    case "fieldset_end":
                        $output =   '</fieldset>';
                        break;
                    case "text":
                        $output =   $this->before_field( $name ) .
                                    $this->get_label( $name, $label ) .
                                    '<input type="text" value="' . $default_value . '" name="' . $name . '" id="' . $name . '" ' . $attr . ' />' .
                                    $this->get_description( $description ) .
                                    $this->after_field();
                        break;
                    case "file":
                        $output =   $this->before_field( $name ) .
                                    $this->get_label( $name, $label ) .
                                    '<input type="file" value="' . $default_value . '" name="' . $name . '" id="' . $name . '" ' . $attr . ' />' .
                                    $this->get_description( $description ) .
                                    $this->after_field();
                        break;
                    case "checkbox":
                        $selected = ( $options['checked'] ) ? 'checked="checked"' : '';
                        $output =   $this->before_field( $name ) .
                                    '<input type="checkbox" value="' . $default_value . '" name="' . $name . '" id="' . $name . '" ' . $attr . ' ' . $selected . ' />' .
                                    $this->get_label( $name, $label ) .
                                    $this->get_description( $description ) .
                                    $this->after_field();
                        break;
                    case "radio":
                        $radio_group = '<span class="field-group">';
                        foreach ( $options as $option ) {
                            $selected = ( $option['value'] == $default_value ) ? 'checked="checked"' : '';
                            $radio_group .=  '<input type="radio" value="' . $option['value'] . '" name="' . $name . '" id="' . $name . '" ' . $attr . ' ' . $selected . ' /> <span class="label">' . $option['label'] . '</span> ';
                        }
                        $radio_group .= '</span>'; 

                        $output =   $this->before_field( $name ) .
                                    $this->get_label( $name, $label ) .
                                    $radio_group .
                                    $this->get_description( $description ) .
                                    $this->after_field();
                        break;
                    case "password":
                        $output =   $this->before_field( $name ) .
                                    $this->get_label( $name, $label ) .
                                    '<input type="password" value="' . $default_value . '" name="' . $name . '" id="' . $name . '" ' . $attr . ' />' .
                                    $this->get_description( $description ) .
                                    $this->after_field();
                        break;
                    case "textarea":
                        $output =   $this->before_field( $name ) .
                                    $this->get_label( $name, $label ) .
                                    '<textarea name="' . $name . '" id="' . $name . '" ' . $attr . ' >' . $default_value . '</textarea>' .
                                    $this->get_description( $description ) .
                                    $this->after_field();
                        break;
                    case "select":
                        $default_value = "";

                        foreach ( $options as $option ) {
                            $selected = ( $option['value'] == $default_value ) ? 'selected="selected"' : '';
                            $default_value .= '<option value="' . $option['value'] . '" ' . $selected . '>' . $option['label'] . '</option>';
                        }

                        $output =   $this->before_field( $name ) .
                                    $this->get_label( $name, $label ) .
                                    '<select name="' . $name . '" id="' . $name . '" ' . $attr . ' >' . 
                                        $default_value .
                                    '</select>' .
                                    $this->get_description( $description ) .
                                    $this->after_field();
                        break;
                    case "hidden":
                        $output =   '<input type="hidden" value="' . $default_value . '" name="' . $name . '" id="' . $name . '" ' . $attr . ' />';
                        break;
                    case 'button':
                        $output =   $this->before_field( $name ) .
                                    '<button name="' . $name . '" id="' . $name . '" ' . $attr . '>' . $label . '</button>' .
                                    $this->get_description( $description ) .
                                    $this->after_field();
                        break;
                    case 'submit':
                        $output =   $this->before_field( $name ) .
                                    '<input type="submit" name="' . $name . '" id="' . $name . '" value="' . $default_value . '" ' . $attr . ' />' .
                                    $this->after_field();
                        break;
                }
            } else {
                $output = "No field type setted.";
            }

            return $output;
        }

    }
}
?>