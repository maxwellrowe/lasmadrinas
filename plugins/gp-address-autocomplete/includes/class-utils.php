<?php

namespace GP_Address_Autocomplete;

use GFFormsModel;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class Utils {
	/**
	 * From GFFormDisplay::get_form()
	 */
	public static function get_save_and_continue_values( $token ) {
		$incomplete_submission_info = GFFormsModel::get_draft_submission_values( $token );

		if ( $incomplete_submission_info ) {
			$submission_details_json = $incomplete_submission_info['submission'];
			$submission_details      = json_decode( $submission_details_json, true );

			return $submission_details['submitted_values'];
		}

		return array();
	}

	/**
	 * Get the prepopulate coordinates for the map field.
	 * Attempts to get the values in the following precendence:
	 *
	 * 1. From "save and continue" values. This is applicable when the user is using "save and continue" functionality
	 *    or the form is a multi-page form.
	 * 2. From the entry meta table. This is applicable when the user is editing an entry.
	 *
	 * @param int $address_field_id The address field ID.
	 * @param int   $entry_id The entry ID.
	 *
	 * @return array
	*/
	public static function get_prepopulate_coords( $address_field_id, $entry_id = null ) {
		// Save & Continue
		$saved_values = self::get_save_and_continue_values( rgar( $_REQUEST, 'gf_token' ) );

		$prepopulate_coords = gp_address_autocomplete()::maybe_decode_json(
			rgar( $saved_values, sprintf( 'gpaa_coords_%d', $address_field_id ) )
		);

		if ( ! empty( $prepopulate_coords ) ) {
			return $prepopulate_coords;
		}

		if ( empty( $entry_id ) ) {
			return null;
		}

		$lat = gform_get_meta( $entry_id, sprintf( 'gpaa_lat_%s', $address_field_id ) );
		$lng = gform_get_meta( $entry_id, sprintf( 'gpaa_lng_%s', $address_field_id ) );

		if ( ! empty( $lat ) && ! empty( $lng ) ) {
			$prepopulate_coords = array(
				'lat' => floatval( $lat ),
				'lng' => floatval( $lng ),
			);
		}

		return $prepopulate_coords;
	}

}
