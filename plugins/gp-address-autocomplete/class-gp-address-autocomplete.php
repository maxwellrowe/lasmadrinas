<?php

use GP_Address_Autocomplete\Compat_GPEP;
use GP_Address_Autocomplete\Utils;

if ( ! class_exists( 'GP_Plugin' ) ) {
	return;
}

class GP_Address_Autocomplete extends GP_Plugin {

	private static $instance = null;

	/**
	 * Marks which scripts/styles have been localized to avoid localizing multiple times with
	 * Gravity Forms' scripts 'callback' property.
	 *
	 * @var array
	 */
	protected $_localized = array();

	protected $_version     = GP_ADDRESS_AUTOCOMPLETE_VERSION;
	protected $_path        = 'gp-address-autocomplete/gp-address-autocomplete.php';
	protected $_full_path   = __FILE__;
	protected $_slug        = 'gp-address-autocomplete';
	protected $_title       = 'Gravity Wiz Address Autocomplete';
	protected $_short_title = 'Autocomplete';

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gp_address_autocomplete';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gp_address_autocomplete';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gp_address_autocomplete_uninstall';

	/**
	 * Defines the capabilities needed for GP Address Autocomplete
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gp_address_autocomplete', 'gp_address_autocomplete_uninstall' );

	/**
	 * @var Compat_GPEP
	 */
	public $compat_gpep;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function minimum_requirements() {
		return array(
			'gravityforms' => array(
				'version' => '2.3-rc-1',
			),
			'wordpress'    => array(
				'version' => '4.8',
			),
		);
	}

	public function pre_init() {
		parent::pre_init();
		require plugin_dir_path( __FILE__ ) . 'class-gp-map-field.php';
	}

	public function init() {
		parent::init();

		$this->compat_gpep = new Compat_GPEP();

		load_plugin_textdomain( 'gp-address-autocomplete', false, basename( dirname( __file__ ) ) . '/languages/' );

		// Filters
		add_filter( 'gform_register_init_scripts', array( $this, 'add_init_script' ), 10, 2 );
		add_filter( 'gform_entry_meta', array( $this, 'register_coords_meta' ), 10, 2 );

		// Actions
		add_action( 'gperk_field_settings', array( $this, 'field_settings_ui' ) );
		add_filter( 'gform_entry_post_save', array( $this, 'store_coords_as_meta' ), 5, 2 ); // Lower priority so the meta is available to Feed Add-ons (priority 10)

		add_filter( 'gform_field_content', array( $this, 'add_coord_input' ), 10, 5 );

		add_action( 'gform_field_standard_settings_1400', array( $this, 'editor_field_standard_settings' ) );

		add_filter( 'gform_submission_values_pre_save', array( $this, 'add_coords_to_draft_submission' ), 10, 2 );
	}

	public function editor_field_standard_settings( $form_id ) {
		if ( ! is_admin() ) {
			return;
		}

		?>

		<li class="gpaa-map-attached-field-setting field_setting gp-field-setting">

			<div class="gp-row">

				<label for="gpaa-map-attached-address-field" class="section_label">
					<?php _e( 'Attached Address Field', 'gp-nested-forms' ); ?>
					<?php gform_tooltip( 'gpaa_map_attached_address_field' ); ?>
				</label>

				<select
					id="gpaa-map-attached-address-field"
					class="fieldwidth-3"
				>
				</select>

			</div>

		</li>

		<?php
	}

	public function init_admin() {
		parent::init_admin();

		GravityPerks::enqueue_field_settings();
	}
	public function get_google_api_key_constant() {
		return defined( 'GPAA_GOOGLE_API_KEY' ) && GPAA_GOOGLE_API_KEY ? GPAA_GOOGLE_API_KEY : false;
	}

	public function get_google_api_key() {
		$constant = $this->get_google_api_key_constant();

		if ( $constant ) {
			return $constant;
		}

		return $this->get_plugin_setting( 'gpaa_google_api_key' );
	}

	public function scripts() {
		$google_api_key = $this->get_google_api_key();

		$scripts = array(
			array(
				'handle'    => 'gp-address-autocomplete',
				'src'       => $this->get_base_url() . '/js/built/gp-address-autocomplete.js',
				'version'   => $this->_version,
				'in_footer' => true,
				'enqueue'   => array(
					array( $this, 'should_enqueue_frontend' ),
				),
				'deps'      => array( 'gform_gravityforms', 'jquery' ),
				'callback'  => array( $this, 'localize_frontend_scripts' ),
			),
			array(
				'handle'    => 'gp-address-autocomplete-google',
				'src'       => sprintf( 'https://maps.googleapis.com/maps/api/js?key=%s&libraries=places,marker&loading=async&callback=gpaaInit', $google_api_key ),
				'version'   => $this->_version,
				'deps'      => array( 'gp-address-autocomplete' ),
				'in_footer' => true,
				'async'     => true,
				'enqueue'   => array(
					array( $this, 'should_enqueue_frontend' ),
				),
			),
			array(
				'handle'    => 'gp-address-autocomplete-form-editor',
				'src'       => $this->get_base_url() . '/js/built/gp-address-autocomplete-form-editor.js',
				'version'   => $this->_version,
				'deps'      => array( 'gform_gravityforms', 'jquery', 'wp-i18n' ),
				'in_footer' => true,
				'enqueue'   => array(
					array( 'admin_page' => array( 'form_editor' ) ),
				),
				'callback'  => array( $this, 'load_form_editor_translations' ),
			),
			array(
				'handle'    => 'gp-address-autocomplete-map-field',
				'src'       => $this->get_base_url() . '/js/built/gp-map-field.js',
				'version'   => $this->_version,
				'deps'      => array( 'gform_gravityforms', 'jquery' ),
				'in_footer' => true,
				'enqueue'   => array(
					array( $this, 'should_enqueue_frontend' ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );

	}

	public function styles() {
		$styles = array(
			array(
				'handle'  => 'gp-address-autocomplete',
				'src'     => $this->get_base_url() . '/css/gp-address-autocomplete.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( $this, 'should_enqueue_frontend' ),
				),
			),
			array(
				'handle'  => 'gp-address-autocomplete-admin',
				'src'     => $this->get_base_url() . '/css/gp-address-autocomplete-admin.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'admin_page' => array( 'form_editor' ) ),
				),
			),
		);

		return array_merge( parent::styles(), $styles );

	}

	/**
	 * @param $form
	 *
	 * @return GF_Field[] List of fields with Address Autocomplete enabled.
	 */
	public function get_autocomplete_fields( $form ) {
		if ( empty( $form['fields'] ) ) {
			return array();
		}

		$fields = array();

		foreach ( $form['fields'] as $field ) {
			if ( rgar( $field, 'gpaaEnable' ) ) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	public function load_form_editor_translations() {
		wp_set_script_translations( 'gp-address-autocomplete-form-editor', 'gp-address-autocomplete', plugin_dir_path( __FILE__ ) . 'languages/' );
	}

	/**
	 * Store value from coordinates hidden input as entry meta to provide an accurate location for maps.
	 *
	 * @param array $entry The submitted entry.
	 * @param array $form The current form.
	 *
	 * @return array
	 */
	public function store_coords_as_meta( $entry, $form ) {
		foreach ( $this->get_autocomplete_fields( $form ) as $autocomplete_field ) {
			$key = "gpaa_place_{$autocomplete_field->id}";

			if ( ! rgpost( $key ) ) {
				continue;
			}

			$output = self::maybe_decode_json( rgpost( $key ) );
			$coords = $output['geometry']['location'];

			if ( ! rgar( $coords, 'lat' ) || ! rgar( $coords, 'lng' ) ) {
				gform_delete_meta( $entry['id'], "gpaa_lat_{$autocomplete_field->id}" );
				gform_delete_meta( $entry['id'], "gpaa_lng_{$autocomplete_field->id}" );

				if ( isset( $entry[ "gpaa_lat_{$autocomplete_field->id}" ] ) ) {
					unset( $entry[ "gpaa_lat_{$autocomplete_field->id}" ] );
				}

				if ( isset( $entry[ "gpaa_lng_{$autocomplete_field->id}" ] ) ) {
					unset( $entry[ "gpaa_lng_{$autocomplete_field->id}" ] );
				}

				continue;
			}

			gform_update_meta( $entry['id'], "gpaa_lat_{$autocomplete_field->id}", $coords['lat'] );
			gform_update_meta( $entry['id'], "gpaa_lng_{$autocomplete_field->id}", $coords['lng'] );
			gform_update_meta( $entry['id'], "gpaa_place_{$autocomplete_field->id}", rgpost( "gpaa_place_{$autocomplete_field->id}" ) );

			$entry[ "gpaa_lat_{$autocomplete_field->id}" ]    = $coords['lat'];
			$entry[ "gpaa_lng_{$autocomplete_field->id}" ]    = $coords['lng'];
			$entry[ "gpaa_place_{$autocomplete_field->id}" ] = rgpost( "gpaa_place_{$autocomplete_field->id}" );
		}

		return $entry;
	}

	public function add_coords_to_draft_submission( $submitted_values, $form ) {
		foreach ( $this->get_autocomplete_fields( $form ) as $field ) {
			$coords_input_id = sprintf( 'gpaa_place_%d', $field->id );

			if ( rgpost( $coords_input_id ) ) {
				$submitted_values[ $coords_input_id ] = rgpost( $coords_input_id );
			}
		}

		return $submitted_values;
	}

	/**
	 * Register entry meta for latitude and longitude.
	 *
	 * @param array $entry_meta
	 * @param int $form_id
	 */
	public function register_coords_meta( $entry_meta, $form_id ) {
		foreach ( $this->get_autocomplete_fields( GFAPI::get_form( $form_id ) ) as $autocomplete_field ) {
			$label = GFCommon::get_label( $autocomplete_field );

			$entry_meta[ "gpaa_lat_{$autocomplete_field->id}" ] = array(
				// translators: placeholder is the label of the field with Address Autocomplete enabled.
				'label'                      => sprintf( __( '%s (Latitude)', 'gp-address-autocomplete' ), $label ),
				'is_numeric'                 => true,
				/**
				 * @see store_coords_as_meta
				 */
				'update_entry_meta_callback' => null,
				'is_default_column'          => false,
				'filter'                     => true, // Allow conditional logic
			);

			$entry_meta[ "gpaa_lng_{$autocomplete_field->id}" ] = array(
				// translators: placeholder is the label of the field with Address Autocomplete enabled.
				'label'                      => sprintf( __( '%s (Longitude)', 'gp-address-autocomplete' ), $label ),
				'is_numeric'                 => true,
				/**
				 * @see store_coords_as_meta
				 */
				'update_entry_meta_callback' => null,
				'is_default_column'          => false,
				'filter'                     => true, // Allow conditional logic
			);

			$entry_meta[ "gpaa_place_{$autocomplete_field->id}" ] = array(
				// translators: placeholder is the label of the field with Address Autocomplete enabled.
				'label'                      => sprintf( __( '%s (Place)', 'gp-address-autocomplete' ), $label ),
				'is_numeric'                 => false,
				'update_entry_meta_callback' => null,
				'is_default_column'          => false,
			);
		}

		return $entry_meta;
	}

	public function add_coord_input( $markup, $field, $value, $entry_id, $form_id ) {
		if ( $field->gpaaEnable && ! $field->is_form_editor() ) {
			$name   = sprintf( 'gpaa_place_%d', $field->id );
			$coords = rgpost( $name );

			if ( empty( $coords ) ) {
				$prepopulate_coords = Utils::get_prepopulate_coords( $field->id, $entry_id );

				if ( $prepopulate_coords ) {
					$coords = $prepopulate_coords;

					if ( is_array( $coords ) ) {
						$coords = json_encode( $coords );
					}
				}
			}

			/**
			 * Filter the coordinates used on initial load.
			 *
			 * @param string $coords The coordinates in JSON format.
			 * @param \GF_Field $field The current Address field.
			 *
			 * @since 1.2.9
			 */
			$coords = gf_apply_filters( array( 'gpaa_place', $form_id, $field->id ), $coords, $field );

			$encoded_coords = esc_attr( $coords );

			$markup .= sprintf( '<input type="hidden" name="%s" class="gform_hidden" value="%s">', $name, $encoded_coords );
		}

		return $markup;
	}

	/**
	 * @param $form
	 *
	 * @return bool
	 */
	public function should_enqueue_frontend( $form ) {
		if ( GFCommon::is_form_editor() ) {
			return false;
		}

		return count( $this->get_autocomplete_fields( $form ) ) > 0;
	}

	public function should_load_admin_css() {
		return is_admin();
	}

	public function is_localized( $item ) {
		return in_array( $item, $this->_localized, true );
	}

	public function localize_frontend_scripts( $form ) {
		/**
		 * If a script is enqueued in the footer with in_footer, this script will
		 * be called multiple times and we need to guard against localizing multiple times.
		 */
		if ( $this->is_localized( 'gp-address-autocomplete' ) ) {
			return;
		}

		wp_localize_script( 'gp-address-autocomplete', 'GP_ADDRESS_AUTOCOMPLETE_CONSTANTS', array(
			'allowed_countries' => $this->get_plugin_setting( 'gpaa_countries' ),
			'countries'         => $this->get_countries(),
		) );

		$this->_localized[] = 'gp-address-autocomplete';
	}

	public function register_init_script( $form ) {
		$this->add_init_script( $form );
	}

	public function add_init_script( $form ) {
		$autocomplete_fields = $this->get_autocomplete_fields( $form );

		if ( empty( $autocomplete_fields ) ) {
			return $form;
		}

		// Must manually require since plugins like Partial Entries and Nested Forms call gform_pre_render outside of the rendering context.
		require_once( GFCommon::get_base_path() . '/form_display.php' );

		foreach ( $autocomplete_fields as $field ) {
			$form_id = $field['formId'];
			$id      = $field['id'];

			/**
			 * Filter the args to initialize Address Autocomplete on the frontend.
			 *
			 * @param array     $args   Arguments used to initialize the JavaScript instance of GP_Address_Autocomplete.
			 * @param GF_Field  $field  The current field.
			 * @param array     $form   The current form.
			 *
			 * @since 1.0
			 */
			$args = gf_apply_filters( array( 'gpaa_init_args', $form_id, $id ), array(
				'fieldId'        => $id,
				'formId'         => $form_id,
				'inputSelectors' => array(
					'autocomplete'  => sprintf( '#input_%d_%d_%d', $form_id, $id, 1 ), // Default to Address Line 1
					'address1'      => sprintf( '#input_%d_%d_%d', $form_id, $id, 1 ),
					'address2'      => sprintf( '#input_%d_%d_%d', $form_id, $id, 2 ),
					'postalCode'    => sprintf( '#input_%d_%d_%d', $form_id, $id, 5 ),
					'city'          => sprintf( '#input_%d_%d_%d', $form_id, $id, 3 ),
					'stateProvince' => sprintf( '#input_%d_%d_%d', $form_id, $id, 4 ),
					'country'       => sprintf( '#input_%d_%d_%d', $form_id, $id, 6 ),
				),
				'addressType'    => $field->addressType,
			), $field, $form );

			$script = 'new GP_Address_Autocomplete( ' . json_encode( $args ) . ' );';
			$slug   = 'gp_address_autocomplete_' . $form_id . '_' . $id;

			GFFormDisplay::add_init_script( $form_id, $slug, GFFormDisplay::ON_PAGE_RENDER, $script );
		}

		return $form;
	}

	## Admin Field Settings

	public function field_settings_ui( $position ) {
		?>

		<li class="gpaa-field-setting field_setting" style="display:none;">
			<input type="checkbox" value="1" id="gpaa-enable" onchange="SetFieldProperty( 'gpaaEnable', this.checked );"/>
			<label for="gpaa-enable" class="inline">
				<?php _e( 'Enable Google Address Autocomplete' ); ?>
				<?php gform_tooltip( $this->_slug . '_enable' ); ?>
			</label>
		</li>

		<?php
	}

	public function tooltips( $tooltips ) {
		$template = '<h6>%s</h6> %s';

		$tooltips[ $this->_slug . '_enable' ] = sprintf(
			$template,
			__( 'GP Address Autocomplete', 'gp-address-autocomplete' ),
			__( 'Enable autocompletion of address inputs using the Google Places API.', 'gp-address-autocomplete' )
		);

		$tooltips['gpaa_map_attached_address_field'] = sprintf(
			$template,
			__( 'Address Autocomplete', 'gp-address-autocomplete' ),
			__( 'Select the field that should be used to populate the map location.', 'gp-address-autocomplete' )
		);

		return $tooltips;
	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return 'gform-icon--place';
	}

	/**
	 * List out countries in associative array format with abbreviation and full country name.
	 *
	 * @returns array Associative array of countries. Keys are abbreviations for the country.
	 */
	public function get_countries() {
		$fake_address_field = new GF_Field_Address();

		if ( method_exists( $fake_address_field, 'get_default_countries' ) ) {
			$countries = $fake_address_field->get_default_countries();
		} else {
			$countries = array();
		}

		return $countries;
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		$country_choices = array_map( function ( $country_name, $country_code ) {
			return array(
				'label' => $country_name,
				'value' => $country_code,
			);
		}, $this->get_countries(), array_keys( $this->get_countries() ) );

		$fields = array();

		if ( ! $this->get_google_api_key_constant() ) {
			$fields[] = array(
				'name'        => 'gpaa_google_api_key',
				'tooltip'     => __( '<strong>Google API Key</strong>Enter your Google API key. This key must have access to the Places API and Maps JavaScript API.', 'gp-address-autocomplete' ),
				'label'       => esc_html__( 'Google API Key', 'gp-address-autocomplete' ),
				'description' => __( 'For autocomplete to function, you must have a Google API key set up in the Google Cloud Platform.', 'gp-address-autocomplete' ) . '
							<br />
							<ol style="list-style: disc;padding: 0 10px;">
								<li><a href="https://gravitywiz.com/documentation/gravity-forms-address-autocomplete/#generating-a-google-api-key" target="_blank">Setup Guide</a></li>
								<li><a href="https://gravitywiz.com/documentation/gravity-forms-address-autocomplete/#security-types" target="_blank">Security Tips</a></li>
							</ol>',
				'type'        => 'text',
				'class'       => 'medium',
				'input_type'  => 'password',
			);
		}

		$fields[] = array(
			'name'        => 'gpaa_countries[]',
			'tooltip'     => __( '<strong>Countries</strong>Select the countries you wish to restrict the autocomplete results to. If no countries are selected, results will not be restricted.', 'gp-address-autocomplete' ),
			'label'       => esc_html__( 'Countries', 'gp-address-autocomplete' ),
			'description' => __( 'Restrict the results of the address autocomplete to specific countries for Address fields using the "International" Address Type. If no countries are selected, results will not be restricted. Autocomplete will bias results based on the location of the user.', 'gp-address-autocomplete' ),
			'type'        => 'select',
			'enhanced_ui' => true,
			'multiple'    => true,
			'choices'     => $country_choices,
		);

		return array(
			array(
				'title'  => esc_html__( 'Google Address Autocomplete', 'gp-address-autocomplete' ),
				'fields' => $fields,
			),
		);
	}

}

function gp_address_autocomplete() {
	return GP_Address_Autocomplete::get_instance();
}

GFAddOn::register( 'GP_Address_Autocomplete' );
