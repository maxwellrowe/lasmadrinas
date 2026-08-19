<?php
/**
 * Ball reservation API payload mapping and delivery.
 *
 * @package GFBallReservationForm
 */

defined( 'ABSPATH' ) || exit;

class GF_Ball_Reservation_API {

	/** @var int Parent Ball Reservation form ID. */
	const PARENT_FORM_ID = 30;

	/** @var int Nested Ball Guest Details form ID. */
	const CHILD_FORM_ID = 31;

	/** @var int Nested Forms field ID on the parent form. */
	const NESTED_FORM_FIELD_ID = 59;

	/**
	 * Registers API hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'gform_confirmation_' . self::PARENT_FORM_ID, array( __CLASS__, 'append_test_payload_to_confirmation' ), 10, 4 );
		add_action( 'gform_after_submission_' . self::PARENT_FORM_ID, array( __CLASS__, 'send_live_payload' ), 20, 2 );
	}

	/**
	 * Appends the mapped JSON payload to message confirmations while in test mode.
	 *
	 * @param string|array $confirmation Confirmation message or configuration.
	 * @param array        $form         Form object.
	 * @param array        $entry        Submitted entry.
	 * @param bool         $ajax         Whether this is an AJAX submission.
	 * @return string|array
	 */
	public static function append_test_payload_to_confirmation( $confirmation, $form, $entry, $ajax ) {
		if ( 'test' !== self::get_mode() ) {
			return $confirmation;
		}

		$payload = self::build_payload( $entry );
		$json    = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		$debug   = '<hr><h3>' . esc_html__( 'API Payload Preview (Test Mode)', 'gf-ball-reservation-form' ) . '</h3><pre><code>' . esc_html( $json ) . '</code></pre>';

		if ( is_array( $confirmation ) && isset( $confirmation['message'] ) ) {
			$confirmation['message'] .= $debug;
		} elseif ( is_string( $confirmation ) ) {
			$confirmation .= $debug;
		}

		return $confirmation;
	}

	/**
	 * Sends the mapped payload only when the integration is explicitly Live.
	 *
	 * @param array $entry Submitted parent entry.
	 * @param array $form  Parent form object.
	 * @return void
	 */
	public static function send_live_payload( $entry, $form ) {
		if ( 'live' !== self::get_mode() ) {
			return;
		}

		$endpoint = self::get_setting( 'ball_reservation_api_endpoint' );
		$api_key  = self::get_setting( 'ball_reservation_api_key' );
		$payload  = self::build_payload( $entry );

		if ( ! filter_var( $endpoint, FILTER_VALIDATE_URL ) || '' === $api_key ) {
			self::store_delivery_result( $entry, $payload, 0, 'API endpoint or API key is not configured.' );

			return;
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type' => 'application/json',
					'X-API-Key'    => $api_key,
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			self::store_delivery_result( $entry, $payload, 0, $response->get_error_message() );

			return;
		}

		self::store_delivery_result(
			$entry,
			$payload,
			absint( wp_remote_retrieve_response_code( $response ) ),
			wp_remote_retrieve_body( $response )
		);
	}

	/**
	 * Maps Form 30 and linked Form 31 entries to the LMB API schema.
	 *
	 * @param array $parent_entry Form 30 entry.
	 * @return array
	 */
	public static function build_payload( $parent_entry ) {
		$details = array(
			self::build_detail(
				self::get_value( $parent_entry, '93' ),
				self::get_value( $parent_entry, '6' ),
				self::get_payer_name( $parent_entry, '64' ),
				self::get_value( $parent_entry, '81' ),
				self::get_value( $parent_entry, '9' ),
				false,
				self::get_raw_address( $parent_entry, '5' )
			),
		);

		foreach ( self::get_child_entries( $parent_entry ) as $child_entry ) {
			$details[] = self::build_detail(
				self::get_value( $child_entry, '15' ),
				self::get_value( $child_entry, '3' ),
				self::get_payer_name( $child_entry, '13' ),
				self::get_value( $child_entry, '14' ),
				self::get_value( $child_entry, '6' ),
				'' !== self::get_value( $child_entry, '16.1' ),
				self::get_raw_address( $child_entry, '4' )
			);
		}

		return array(
			'header'  => array(
				'contactEmail'         => self::limit( self::get_value( $parent_entry, '1' ), 150 ),
				'contactName'          => self::limit( self::get_name_value( $parent_entry, '88' ), 40 ),
				'debutanteName'        => self::limit( self::get_name_value( $parent_entry, '70' ), 40 ),
				'overflowContactName'  => self::limit( self::get_name_value( $parent_entry, '72' ), 40 ),
				'alsoSendTableCards'   => 0 === strpos( self::get_value( $parent_entry, '73' ), 'Yes,' ),
				'guestSpecialAccess'   => self::limit( self::get_value( $parent_entry, '74' ), 255 ),
			),
			'details' => $details,
		);
	}

	/**
	 * Creates one API detail record.
	 *
	 * @param string $formal_name Formal name.
	 * @param string $nicknames Nicknames.
	 * @param string $payer_name Payer name.
	 * @param mixed  $count Reservation count.
	 * @param string $relation Relationship to debutante.
	 * @param bool   $do_not_solicit Do not solicit flag.
	 * @param string $raw_address Complete address.
	 * @return array
	 */
	private static function build_detail( $formal_name, $nicknames, $payer_name, $count, $relation, $do_not_solicit, $raw_address ) {
		return array(
			'formalName'          => self::limit( $formal_name, 75 ),
			'nicknames'           => self::limit( $nicknames, 25 ),
			'payerName'           => self::limit( $payer_name, 40 ),
			'reservationsCount'   => absint( $count ),
			'relationToDebutante' => self::limit( $relation, 25 ),
			'doNotSolicit'        => (bool) $do_not_solicit,
			'rawAddress'          => self::limit( $raw_address, 500 ),
		);
	}

	/**
	 * Gets the child entries linked to the Nested Forms field on Form 30.
	 *
	 * @param array $parent_entry Parent entry.
	 * @return array
	 */
	private static function get_child_entries( $parent_entry ) {
		if ( class_exists( 'GPNF_Entry' ) ) {
			$nested_entry = new GPNF_Entry( $parent_entry );
			$entries      = $nested_entry->get_child_entries( self::NESTED_FORM_FIELD_ID );

			return is_array( $entries ) ? $entries : array();
		}

		$entry_ids = array_filter( array_map( 'absint', explode( ',', self::get_value( $parent_entry, (string) self::NESTED_FORM_FIELD_ID ) ) ) );
		$entries   = array();

		foreach ( $entry_ids as $entry_id ) {
			$entry = GFAPI::get_entry( $entry_id );
			if ( ! is_wp_error( $entry ) && self::CHILD_FORM_ID === absint( rgar( $entry, 'form_id' ) ) ) {
				$entries[] = $entry;
			}
		}

		return $entries;
	}

	/**
	 * Builds the raw address expected by the API.
	 *
	 * @param array  $entry Entry data.
	 * @param string $field_id Address field ID.
	 * @return string
	 */
	private static function get_raw_address( $entry, $field_id ) {
		$city_state_zip = trim( self::get_value( $entry, $field_id . '.3' ) . ', ' . self::get_value( $entry, $field_id . '.4' ) . ' ' . self::get_value( $entry, $field_id . '.5' ) );
		$city_state_zip = trim( $city_state_zip, ', ' );
		$lines          = array_filter(
			array(
				self::get_value( $entry, $field_id . '.1' ),
				self::get_value( $entry, $field_id . '.2' ),
				$city_state_zip,
			)
		);

		return implode( "\n", $lines );
	}

	/**
	 * Gets a formatted Gravity Forms Name field value.
	 *
	 * @param array  $entry Entry data.
	 * @param string $field_id Name field ID.
	 * @return string
	 */
	private static function get_name_value( $entry, $field_id ) {
		$parts = array();

		foreach ( array( '2', '3', '4', '6', '8' ) as $input_id ) {
			$value = self::get_value( $entry, $field_id . '.' . $input_id );
			if ( '' !== $value ) {
				$parts[] = $value;
			}
		}

		return implode( ' ', $parts );
	}

	/**
	 * Gets a payer name, including a custom Other choice.
	 *
	 * @param array  $entry Entry data.
	 * @param string $field_id Payer field ID.
	 * @return string
	 */
	private static function get_payer_name( $entry, $field_id ) {
		$payer = self::get_value( $entry, $field_id );

		if ( 'gf_other_choice' === $payer || 'Other' === $payer ) {
			$other_value = self::get_value( $entry, $field_id . '_other' );
			return '' !== $other_value ? $other_value : 'Other';
		}

		return $payer;
	}

	/**
	 * Gets a sanitized scalar entry value.
	 *
	 * @param array  $entry Entry data.
	 * @param string $key Entry key.
	 * @return string
	 */
	private static function get_value( $entry, $key ) {
		$value = rgar( $entry, $key );

		return is_scalar( $value ) ? trim( wp_strip_all_tags( (string) $value ) ) : '';
	}

	/**
	 * Limits a string to the API's documented maximum length.
	 *
	 * @param string $value String to limit.
	 * @param int    $length Maximum length.
	 * @return string
	 */
	private static function limit( $value, $length ) {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}

	/**
	 * Retrieves an API plugin setting.
	 *
	 * @param string $key Settings key.
	 * @return string
	 */
	private static function get_setting( $key ) {
		$addon    = function_exists( 'gf_ball_reservation_form' ) ? gf_ball_reservation_form() : null;
		$settings = $addon ? $addon->get_plugin_settings() : array();

		return is_array( $settings ) && isset( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';
	}

	/**
	 * Gets the configured delivery mode. Test mode is the safe default.
	 *
	 * @return string
	 */
	private static function get_mode() {
		return 'live' === self::get_setting( 'ball_reservation_api_mode' ) ? 'live' : 'test';
	}

	/**
	 * Stores API diagnostics alongside the parent entry.
	 *
	 * @param array  $entry Entry data.
	 * @param array  $payload API payload.
	 * @param int    $status_code HTTP status code.
	 * @param string $response_body Response or error detail.
	 * @return void
	 */
	private static function store_delivery_result( $entry, $payload, $status_code, $response_body ) {
		$entry_id = absint( rgar( $entry, 'id' ) );

		if ( $entry_id && function_exists( 'gform_update_meta' ) ) {
			gform_update_meta( $entry_id, '_gf_ball_reservation_api_payload', wp_json_encode( $payload ) );
			gform_update_meta( $entry_id, '_gf_ball_reservation_api_status', $status_code );
			gform_update_meta( $entry_id, '_gf_ball_reservation_api_response', self::limit( $response_body, 2000 ) );
		}

		if ( class_exists( 'GFCommon' ) ) {
			GFCommon::log_debug( sprintf( '%s(): API delivery for entry %d returned status %d.', __METHOD__, $entry_id, $status_code ) );
		}
	}
}
