<?php
/**
 * Gravity Forms Add-On settings and frontend assets.
 *
 * @package GFBallReservationForm
 */

defined( 'ABSPATH' ) || exit;

class GF_Ball_Reservation_Settings extends GFAddOn {

	/** @var GF_Ball_Reservation_Settings|null */
	private static $instance = null;

	/** @var string */
	protected $_version = GF_BALL_RESERVATION_FORM_VERSION;

	/** @var string */
	protected $_slug = 'gf-ball-reservation-form';

	/** @var string */
	protected $_path = 'gf-ball-reservation-form/gf-ball-reservation-form.php';

	/** @var string */
	protected $_full_path = GF_BALL_RESERVATION_FORM_FILE;

	/** @var string */
	protected $_title = 'GF Ball Reservation Form';

	/** @var string */
	protected $_short_title = 'Ball Reservation';

	/** @var string */
	protected $_capabilities_settings_page = 'gravityforms_edit_settings';

	/** @var array */
	protected $_capabilities = array( 'gravityforms_edit_settings' );

	/**
	 * Gets the singleton instance.
	 *
	 * @return GF_Ball_Reservation_Settings
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Defines the Forms > Settings > Ball Reservation page.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Ball Reservation', 'gf-ball-reservation-form' ),
				'fields' => array(
					array(
						'name'          => 'ball_reservation_form_id',
						'label'         => esc_html__( 'Ball Reservation Form', 'gf-ball-reservation-form' ),
						'type'          => 'select',
						'choices'       => $this->get_form_choices(),
						'default_value' => $this->get_default_form_id(),
						'validation_callback' => array( $this, 'is_valid_form_setting' ),
						'description'   => esc_html__( 'Select the Gravity Form that should receive Ball Reservation-specific functionality.', 'gf-ball-reservation-form' ),
					),
				),
			),
		);
	}

	/**
	 * Validates the select value before the Add-On Framework saves it.
	 *
	 * @param string $value Submitted setting value.
	 * @return bool
	 */
	public function is_valid_form_setting( $value ) {
		if ( '' === $value ) {
			return true;
		}

		if ( ! is_scalar( $value ) || ! ctype_digit( (string) $value ) ) {
			return false;
		}

		$form_id = absint( $value );

		return $form_id > 0 && $this->form_exists( $form_id );
	}

	/**
	 * Registers the frontend script only when the selected form is rendered.
	 *
	 * @return array
	 */
	public function scripts() {
		$script_path    = plugin_dir_path( GF_BALL_RESERVATION_FORM_FILE ) . 'assets/js/address-normalization.js';
		$script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : $this->_version;

		$scripts = array(
			array(
				'handle'    => 'gf-ball-reservation-address-normalization',
				'src'       => $this->get_base_url() . '/assets/js/address-normalization.js',
				'version'   => $script_version,
				'in_footer' => true,
				'deps'      => array( 'gform_gravityforms', 'gp-address-autocomplete' ),
				'enqueue'   => array(
					array( $this, 'should_enqueue_address_normalization' ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Determines whether the current form is the selected Ball Reservation form.
	 *
	 * @param array $form The form currently being rendered.
	 * @return bool
	 */
	public function should_enqueue_address_normalization( $form ) {
		if ( ! is_array( $form ) || ! isset( $form['id'] ) ) {
			return false;
		}

		$configured_form_id = $this->get_configured_form_id();

		return $configured_form_id && $configured_form_id === absint( $form['id'] );
	}

	/**
	 * Returns the saved form ID, or the Form 30 fallback when it exists.
	 *
	 * An invalid saved ID deliberately disables the functionality rather than
	 * silently applying it to a different form.
	 *
	 * @return int
	 */
	public function get_configured_form_id() {
		$settings = $this->get_plugin_settings();

		// array_key_exists() lets an administrator deliberately select the blank option.
		if ( is_array( $settings ) && array_key_exists( 'ball_reservation_form_id', $settings ) ) {
			$form_id = absint( $settings['ball_reservation_form_id'] );

			return $form_id && $this->form_exists( $form_id ) ? $form_id : 0;
		}

		return $this->get_default_form_id();
	}

	/**
	 * Builds safe select options from the site's available Gravity Forms.
	 *
	 * @return array
	 */
	private function get_form_choices() {
		$choices = array(
			array(
				'label' => esc_html__( 'Select a form', 'gf-ball-reservation-form' ),
				'value' => '',
			),
		);

		foreach ( GFAPI::get_forms() as $form ) {
			$form_id = isset( $form['id'] ) ? absint( $form['id'] ) : 0;

			if ( ! $form_id ) {
				continue;
			}

			$title = isset( $form['title'] ) ? wp_strip_all_tags( $form['title'] ) : '';
			$choices[] = array(
				'label' => sprintf( '%1$s (ID: %2$d)', $title ? $title : esc_html__( 'Untitled Form', 'gf-ball-reservation-form' ), $form_id ),
				'value' => (string) $form_id,
			);
		}

		return $choices;
	}

	/**
	 * Returns Form 30 only when it is available as the initial default.
	 *
	 * @return int|string
	 */
	private function get_default_form_id() {
		return $this->form_exists( 30 ) ? 30 : '';
	}

	/**
	 * Checks that a form ID resolves to an existing Gravity Form.
	 *
	 * @param int $form_id Gravity Form ID.
	 * @return bool
	 */
	private function form_exists( $form_id ) {
		return is_array( GFAPI::get_form( $form_id ) );
	}
}
