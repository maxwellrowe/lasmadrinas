<?php

namespace GP_Address_Autocomplete;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class Compat_GPEP {
	public function __construct() {
		if ( ! function_exists( 'gp_easy_passthrough' ) ) {
			return;
		}

		add_filter( 'gpep_source_form_field_map', array( $this, 'add_coords_to_source_form_field_map' ), 10, 3 );
		add_filter( 'gform_field_map_choices', array( $this, 'add_coords_to_field_map_choices' ), 10, 4 );
		add_filter( 'gpep_field_values', array( $this, 'handle_coords_value' ), 10, 4 );
		add_filter( 'gpaa_coords', array( $this, 'populate_coords' ), 10, 2 );
	}

	/**
	 * Add coordinates input to the source field map.
	 *
	 * @param array $field_map The field map.
	 * @param array $field The current field.
	 * @param array $source_form The source form.
	 */
	public function add_coords_to_source_form_field_map( $field_map, $field, $source_form ) {
		if ( $field->type !== 'address' || ! rgar( $field, 'gpaaEnable' ) ) {
			return $field_map;
		}

		$field_map[] = array(
			'name'  => 'gpaa_coords_' . $field['id'],
			'label' => \GFCommon::get_label( $field ) . ' (' . esc_html__( 'Coordinates', 'gp-address-autocomplete' ) . ')',
		);

		return $field_map;
	}

	/**
	 * Add coordinates to the field map choices for Address fields
	 *
	 * @param array $choices
	 * @param int $form_id
	 * @param string $field_type
	 * @param array $exclude_field_types
	 *
	 * @return array
	 */
	public function add_coords_to_field_map_choices( $choices, $form_id, $field_type, $exclude_field_types ) {
		/*
		 * This filter is hooked to gform_field_map_choices as gform_{$slug}_field_map_choices is no longer in use.
		 */
		if ( rgget( 'subview' ) !== 'gp-easy-passthrough' ) {
			return $choices;
		}

		if ( empty( $choices['fields'] ) || empty( $choices['fields']['choices'] ) ) {
			return $choices;
		}

		$fields_with_coords = array();

		foreach ( $choices['fields']['choices'] as $choice_index => $choice ) {
			$field_id   = absint( $choice['value'] );
			$input_bits = explode( '.', $choice['value'] );

			if ( rgar( $choice, 'type' ) !== 'address' ) {
				continue;
			}

			$field = \GFAPI::get_field( $form_id, $field_id );

			if ( ! rgar( $field, 'gpaaEnable' ) ) {
				continue;
			}

			// Skip until the input is the Country input (.6) so it's appended after it.
			if ( $field_id == $choice['value'] || rgar( $input_bits, 1 ) !== '6' ) {
				continue;
			}

			// Do not add coordinates input if it's already added.
			if ( in_array( $field_id, $fields_with_coords, true ) ) {
				continue;
			}

			$fields_with_coords[] = $field_id;

			// Find desired index using wp_list_pluck() since the foreach index will be inaccurate.
			$choice_index = array_search( $choice['value'], wp_list_pluck( $choices['fields']['choices'], 'value' ), true );

			array_splice( $choices['fields']['choices'], $choice_index + 1, 0, array(
				array(
					'label' => \GFCommon::get_label( $field ) . ' (' . esc_html__( 'Coordinates', 'gp-address-autocomplete' ) . ')',
					'value' => 'gpaa_coords_' . $field_id,
					'type'  => 'address',
				),
			) );
		}

		return $choices;
	}

	/**
	 * Handle setting the target field with the coords JSON value.
	 */
	public function handle_coords_value( $values, $form_id, $mapping, $source_entry ) {
		foreach ( $mapping as $source_input_id => $target_input_id ) {
			// Conversion to _ to . happens in gp_easy_passthrough()->get_field_values(). Convert it back for now.
			$source_input_id = str_replace( '.', '_', $source_input_id );

			if ( strpos( $source_input_id, 'gpaa_coords_' ) !== 0 ) {
				continue;
			}

			$source_field = \GFAPI::get_field( $form_id, str_replace( 'gpaa_coords_', '', $source_input_id ) );

			if ( ! rgar( $source_field, 'gpaaEnable' ) ) {
				continue;
			}

			$coords = array(
				'lat' => rgar( $source_entry, 'gpaa_lat_' . $source_field['id'] ),
				'lng' => rgar( $source_entry, 'gpaa_lng_' . $source_field['id'] ),
			);

			$values [ $target_input_id ] = json_encode( $coords );
		}

		return $values;
	}

	/**
	 * Populates the coords hidden inputs with the value from the source field.
	 *
	 * @param array $coords The coordinates in JSON.
	 * @param array $field The current field.
	 */
	public function populate_coords( $coords, $field ) {
		// If there are already coords, use them instead.
		if ( ! empty( $coords ) ) {
			return $coords;
		}

		$field_values = gp_easy_passthrough()->get_field_values( $field['formId'] );
		$gpep_coords  = rgar( $field_values, 'gpaa_coords_' . $field['id'] );

		if ( $gpep_coords ) {
			return $gpep_coords;
		}

		return $coords;
	}


}
