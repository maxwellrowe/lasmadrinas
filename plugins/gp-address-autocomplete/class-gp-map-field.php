<?php

use GP_Address_Autocomplete\Utils;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class GP_Map_Field extends GF_Field {

	public $type = 'map';

	public static function get_base_url() {
		return plugins_url( '', __FILE__ );
	}

	public function get_form_editor_field_title() {
		return esc_attr__( 'Map', 'gp-nested-forms' );
	}

	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title(),
			'icon'  => $this->get_form_editor_field_icon(),
		);
	}

	public function get_form_editor_field_icon() {
		return gp_address_autocomplete()->get_base_url() . '/images/map-icon.svg';

	}

	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'admin_label_setting',
			'visibility_setting',
			'description_setting',
			'label_placement_setting',
			'css_class_setting',
			'gpaa-map-attached-field-setting',
		);
	}

	public function get_form_inline_script_on_page_render( $form ) {
		$address_field_id = $this->get_fields_address_field_id( $this );
		// $entry_id will be present in the requeset within the context of editing a GPNF entry. We pass it to $prepopulate_coords() so that we can lookup entry meta.
		$entry_id           = rgar( $_REQUEST, 'gpnf_entry_id' );
		$prepopulate_coords = Utils::get_prepopulate_coords( $address_field_id, $entry_id );

		$args = array(
			'formId'            => $this->formId,
			'fieldId'           => $this->id,
			'addressFieldId'    => $address_field_id,
			'prepopulateCoords' => $prepopulate_coords,
		);

		return 'window.gp_address_autocomplete_map_field_' . $form['id'] . '= new window.GP_Address_Autocomplete_Map_Field(' . json_encode( $args ) . ');';
	}

	public function get_fields_address_field_id( $field ) {
		$address_field_id = intval( $field['gpaaMapAttachedAddressField'] );

		if ( empty( $address_field_id ) ) {
			$address_field_id = null;
		}

		return $address_field_id;
	}


	public function get_field_input( $form, $value = '', $entry = null ) {
		$id = $this->id;

		if ( ! $this->is_form_editor() ) {
			return "<div class='ginput_container gpaa_map_container gform-theme__disable' id='gpaa_map_container_{$id}'></div>";
		}

		$border_color = ( version_compare( GFForms::$version, '2.5.0', '>=' ) ) ? '#ddd' : '#D2E0EB';

		return sprintf(
			// '<div id="gpaa_admin_map_field_message_container_%s" class="ginput_container"><p style="border: 1px dashed %s; border-radius: 3px; padding: 1rem; background: #fff; "><strong style="color: #ca4a1f;"></strong><br></p></div>',
			'<div id="gpaa_admin_map_field_message_container_%s" class="ginput_container gpaa_map_field_setting_container"></div>',
			$id,
			$border_color
		);
	}

}

GF_Fields::register( new GP_Map_Field() );
