<?php

GFForms::include_payment_addon_framework();

class GF_NMI extends GFPaymentAddOn {

	protected $_version = GF_NMI_VERSION;
	protected $_min_gravityforms_version = '2.3.0';

	protected $_slug = 'nmi-gateway';
	protected $_path = GF_NMI_PLUGIN_PATH;

	protected $_full_path = __FILE__;
	protected $_url = 'https://wpgateways.com/products/nmi-gateway-gravity-forms/';

	protected $_title = 'NMI Add-On';
	protected $_short_title = 'NMI';

	protected $_requires_credit_card = true;

	const ENDPOINT_URL_TRANSACT = 'https://secure.nmi.com/api/transact.php';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_nmi-gateway', 'gravityforms_nmi-gateway_uninstall', 'gravityforms_nmi-gateway_plugin_page' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_nmi-gateway';
	protected $_capabilities_form_settings = 'gravityforms_nmi-gateway';
	protected $_capabilities_uninstall = 'gravityforms_nmi-gateway_uninstall';
	protected $_capabilities_plugin_page = 'gravityforms_nmi-gateway_plugin_page';

	protected $_has_settings_renderer;
	protected $_input_prefix = '';
	protected $_input_container_prefix = '';

	private static $_instance = null;

	private $tokenization_key;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GF_NMI();
		}

		return self::$_instance;
	}

	public function init() {
		if ( $this->is_collect_js_enabled() ) {
			$this->_supports_frontend_feeds = true;
		}

		$this->tokenization_key = $this->get_plugin_setting( 'tokenization_key' );

		add_filter( 'gform_register_init_scripts', array( $this, 'register_init_scripts' ), 9, 3 );
		add_filter( 'gform_field_validation', array( $this, 'pre_validation' ), 10, 4 );
		add_filter( 'gform_pre_submission', array( $this, 'populate_credit_card_last_four' ) );

		// Set UI prefixes depending on settings renderer availability.
		$this->_has_settings_renderer  = $this->is_gravityforms_supported( '2.5-beta' );
		$this->_input_prefix           = $this->_has_settings_renderer ? '_gform_setting' : '_gaddon_setting';
		$this->_input_container_prefix = $this->_has_settings_renderer ? 'gform_setting_' : 'gaddon-setting-row-';
		parent::init();
	}

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array The scripts to be enqueued.
	 * @uses GFPaymentAddOn::scripts()
	 * @uses GFAddOn::get_base_url()
	 * @uses GFAddOn::get_short_title()
	 * @uses GF_NMI::$_version
	 * @uses GFCommon::get_base_url()
	 * @uses GF_NMI::frontend_script_callback()
	 *
	 * @since  1.0
	 * @access public
	 *
	 */
	public function scripts() {
		if ( $this->is_collect_js_enabled() ) {
			add_filter( 'script_loader_tag', array( $this, 'add_tokenization_key_to_js' ), 10, 2 );
			$scripts = array(
				array(
					'handle'  => 'nmi-collect.js',
					'src'     => 'https://secure.nmi.com/token/Collect.js',
					'version' => false,
					'deps'    => array(),
				),
				array(
					'handle'    => 'gforms_nmi_frontend',
					'src'       => $this->get_base_url() . "/js/frontend.js",
					'version'   => $this->_version,
					'deps'      => array( 'jquery', 'nmi-collect.js', 'gform_json' ),
					'in_footer' => false,
					'enqueue'   => array(
						array( $this, 'frontend_script_callback' ),
					),
				),
			);

			return array_merge( parent::scripts(), $scripts );
		}

		return parent::scripts();
	}

	/**
	 * Register NMI script when displaying form.
	 *
	 * @param array $form Form object.
	 * @param array $field_values Current field values. Not used.
	 * @param bool $is_ajax If form is being submitted via AJAX.
	 *
	 * @return void
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GF_NMI::init()
	 * @uses    GFFeedAddOn::has_feed()
	 * @uses    GFPaymentAddOn::get_credit_card_field()
	 * @uses    GF_NMI::get_publishable_api_key()
	 * @uses    GF_NMI::get_card_labels()
	 * @uses    GFFormDisplay::add_init_script()
	 * @uses    GFFormDisplay::ON_PAGE_RENDER
	 *
	 */
	public function register_init_scripts( $form, $field_values, $is_ajax ) {

		if ( ! $this->frontend_script_callback( $form ) ) {
			return;
		}

		$cc_field = $this->get_credit_card_field( $form );
		if ( ! $cc_field ) {
			return;
		}

		// Prepare NMI Javascript arguments.
		$args = array(
			'formId'      => $form['id'],
			'isAjax'      => $is_ajax,
			'ccCustomCss' => apply_filters( 'gf_nmi_cc_custom_css', '{}', $form['id'] ),
		);


		$card_number_input               = $cc_field->inputs[0];
		$cvv_input                       = $cc_field->inputs[3];
		$args['ccFieldId']               = $cc_field->id;
		$args['ccPage']                  = $cc_field->pageNumber;
		$args['ccCardNumberPlaceholder'] = rgar( $card_number_input, 'placeholder' );
		$args['ccCVVPlaceholder']        = rgar( $cvv_input, 'placeholder' );


		$args['timeout_error'] = __( 'The tokenization did not respond in the expected timeframe. Please make sure the fields are correctly filled in and submit the form again.', 'gf-nmi' );

		$feeds = $this->get_feeds_by_slug( $this->_slug, $form['id'] );

		foreach ( $feeds as $feed ) {
			if ( rgar( $feed, 'is_active' ) === '0' ) {
				continue;
			}
			$feed_settings = array(
				'feedId' => $feed['id'],
			);
			$args['feeds'][] = $feed_settings;
		}

		// Initialize NMI script.
		$args   = apply_filters( 'gform_nmi_object', $args, $form['id'] );
		$script = 'new GFNMI( ' . json_encode( $args, JSON_FORCE_OBJECT ) . ' );';

		// Add NMI script to form scripts.
		GFFormDisplay::add_init_script( $form['id'], 'nmi-gateway', GFFormDisplay::ON_PAGE_RENDER, $script );
	}

	/**
	 * Check if the form has an active NMI feed and a credit card field.
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return bool If the script should be enqueued.
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GF_NMI::scripts()
	 * @uses    GFFeedAddOn::has_feed()
	 *
	 */
	public function frontend_script_callback( $form ) {
		return $form && $this->has_feed( $form['id'] ) && $this->is_collect_js_enabled() && ( $this->has_credit_card_field( $form ) );
	}

	//----- SETTINGS PAGES ----------//
	public function plugin_settings_fields() {

		$description = wpautop( sprintf( __( 'NMI is a payment gateway for merchants. Use Gravity Forms to collect payment information and automatically integrate to your NMI account. If you don\'t have a NMI account, you can %ssign up for one here%s', 'gf-nmi' ), '<a href="https://www.nmi.com" target="_blank">', '</a>' ) . '<h4>' . __( 'Upgrade to Advanced', 'gf-nmi' ) . '</h4>' . sprintf( __( 'Advanced version is a full blown plugin that provides full support for <strong>processing subscriptions, ACH payments, authorize only payments and also provides NMI Custom Fields</strong>. It also has an option to <strong>enable 3D Secure 2 card verification</strong> and make your site Strong Customer Authentication (SCA) compliant. The credit card information is saved in your Authorize.Net account and is reused to charge future orders, recurring payments at a later time.<br/><br/>%sClick here%s to upgrade to Advanced version or to know more about it.', 'gf-nmi' ), '<a href="https://wpgateways.com/products/nmi-gateway-gravity-forms/" target="_blank">', '</a>' ) );

		return array(
			array(
				'title'       => esc_html__( 'NMI Account Information', 'gf-nmi' ),
				'description' => $description,
				'fields'      => array(
					array(
						'name'          => 'mode',
						'label'         => esc_html__( 'Mode', 'gf-nmi' ),
						'type'          => 'radio',
						'default_value' => 'test',
						'choices'       => array(
							array(
								'label' => esc_html__( 'Production', 'gf-nmi' ),
								'value' => 'production',
							),
							array(
								'label' => esc_html__( 'Test', 'gf-nmi' ),
								'value' => 'test',
							),
						),
						'horizontal'    => true,
					),
					array(
						'name'        => 'authentication',
						'label'       => esc_html__( 'Authentication Type', 'gf-nmi' ),
						'type'        => 'auth_checkbox',
						'description' => '<small>' . esc_html__( 'Enable Authentication via API keys.', 'gf-nmi' ) . '</small>',
						'tooltip'     => esc_html__( 'RECOMMENDED! Using the API keys ensures you are using the most updated API method. If not, the plugin will process via a legacy method and will need you to enter your login username and password.', 'gf-nmi' ),
						'choices'     => array(
							array(
								'label'         => esc_html__( 'API Keys', 'gf-nmi' ),
								'name'          => 'api_keys',
								'default_value' => 1,
							),
						),
						'horizontal'  => true,
						'onchange'    => "if(jQuery(this).prop('checked')){
									jQuery('#{$this->_input_container_prefix}private_key').show();
									jQuery('#{$this->_input_container_prefix}tokenization_key').show();
									jQuery('#{$this->_input_container_prefix}loginId').hide();
									jQuery('#{$this->_input_container_prefix}transactionKey').hide();
								} else {
									jQuery('#{$this->_input_container_prefix}loginId').show();
									jQuery('#{$this->_input_container_prefix}transactionKey').show();
									jQuery('#{$this->_input_container_prefix}private_key').hide();
									jQuery('#{$this->_input_container_prefix}tokenization_key').hide();
								}",
					),
					array(
						'name'              => 'private_key',
						'label'             => esc_html__( 'Private Key', 'gf-nmi' ),
						'type'              => 'text',
						'input_type'        => 'password',
						'description'       => '<small>' . esc_html__( 'Used for authenticating transactions. Make sure the private key you enter here has "API" permission enabled.', 'gf-nmi' ) . '</small>',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_setting_valid' ),
					),
					array(
						'name'              => 'tokenization_key',
						'label'             => esc_html__( 'Public Tokenization Key', 'gf-nmi' ),
						'type'              => 'text',
						'description'       => '<small>' . __( 'Used for Collect.js tokenization for PCI compliance. Leave it empty ONLY if you are facing Javascript issues at checkout and the plugin will default to Direct Post method.', 'gf-nmi' ) . '</small>',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_setting_valid' ),
					),
					array(
						'name'              => 'loginId',
						'label'             => esc_html__( 'Gateway Username', 'gf-nmi' ),
						'description'       => '<small>' . esc_html__( 'Enter your Gateway Username.', 'gf-nmi' ) . '</small>',
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_setting_valid' ),
					),
					array(
						'name'              => 'transactionKey',
						'label'             => esc_html__( 'Gateway Password', 'gf-nmi' ),
						'description'       => '<small>' . esc_html__( 'Enter your Gateway Password.', 'gf-nmi' ) . '</small>',
						'type'              => 'text',
						'input_type'        => 'password',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_setting_valid' ),
					),
				),
			),
		);
	}

	public function settings_auth_checkbox( $field, $echo = true ) {
		$html = $this->settings_checkbox( $field, false );
		$html .= <<<JS
			<script type="text/javascript">
			jQuery(document).ready(function() {
                jQuery('#{$this->_input_container_prefix}authentication input[type="checkbox"]').trigger('change');
			});
			</script>
JS;
		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	public function is_valid_plugin_settings() {
		$settings = $this->get_plugin_settings();

		return rgar( $settings, 'api_keys' ) ? $this->is_setting_valid( $settings['private_key'] ) : $this->is_setting_valid( $settings['loginId'] ) && $this->is_setting_valid( $settings['transactionKey'] );
	}

	public function is_valid_override_settings() {
		if ( ! $this->is_collect_js_enabled() && $this->get_setting( 'apiSettingsEnabled' ) ) {
			return $this->get_plugin_setting( 'api_keys' ) ? $this->is_setting_valid( $this->get_setting( 'override_private_key' ) ) : $this->is_setting_valid( $this->get_setting( 'overrideLogin' ) ) && $this->is_setting_valid( $this->get_setting( 'overrideKey' ) );
		}

		return false;
	}

	public function is_setting_valid( $value ) {
		return ! empty( $value );
	}

	private function get_api_settings( $feed = array() ) {

		if ( !$feed ) $feed = $this->current_feed;

		//for NMI, each feed can have its own Gateway Username and Gateway Password specified which overrides the master plugin one
		//use the custom settings if found, otherwise use the master plugin settings
		$settings = $this->get_plugin_settings();
		if ( rgars( $feed, 'meta/apiSettingsEnabled' ) ) {
			$api_settings = array(
				'username'     => rgars( $feed, 'meta/overrideLogin' ),
				'password'     => rgars( $feed, 'meta/overrideKey' ),
				'security_key' => rgars( $feed, 'meta/override_private_key' ),
				'mode'         => rgars( $feed, 'meta/overrideMode' )
			);
		} else {
			$api_settings = array(
				'username'     => rgar( $settings, 'loginId' ),
				'password'     => rgar( $settings, 'transactionKey' ),
				'security_key' => rgar( $settings, 'private_key' ),
				'mode'         => rgar( $settings, 'mode' )
			);
		}
		$api_settings['api_keys'] = rgar( $settings, 'api_keys' );

		return $api_settings;

	}

	/**
	 * Prevent feeds being listed or created if the api keys aren't valid.
	 *
	 * @return bool
	 */
	public function can_create_feed() {
		return $this->is_valid_plugin_settings();
	}

	public function feed_settings_fields() {
		$default_settings = parent::feed_settings_fields();

		//remove default options before adding custom
		$default_settings = $this->remove_field( 'transactionType', $default_settings );
		$default_settings = $this->remove_field( 'options', $default_settings );

		$form = $this->get_current_form();

		$transaction_type = array(
			array(
				'name'     => 'transactionType',
				'label'    => esc_html__( 'Transaction Type', 'gravityforms' ),
				'type'     => 'select',
				'onchange' => "jQuery(this).parents('form').submit();",
				'choices'  => array(
					array(
						'label' => esc_html__( 'Select a transaction type', 'gravityforms' ),
						'value' => ''
					),
					array(
						'label' => esc_html__( 'Products and Services', 'gravityforms' ),
						'value' => 'product'
					),
				),
				'tooltip'  => '<h6>' . esc_html__( 'Transaction Type', 'gravityforms' ) . '</h6>' . esc_html__( 'Select a transaction type.', 'gravityforms' )
			),
		);
		$default_settings = $this->add_field_after( 'feedName', $transaction_type, $default_settings );

		$customer_receipt = array(
			'name'    => 'customerReceipt',
			'label'   => esc_html__( 'Customer Receipt', 'gf-nmi' ),
			'type'    => 'checkbox',
			'choices' => array(
				array(
					'label' => esc_html__( 'Send customer receipt', 'gf-nmi' ),
					'name'  => 'customerReceipt',
				),
			),
			'tooltip' => '<h6>' . esc_html__( 'Customer Receipt', 'gf-nmi' ) . '</h6>' . esc_html__( 'If enabled, the customer will be sent an email receipt from NMI. Not recommended unless you are not sending an email receipt generated from Gravity Forms.', 'gf-nmi' ),
		);
		$default_settings = $this->add_field_after( 'billingInformation', $customer_receipt, $default_settings );

		$override_settings = array(
			array(
				'name'     => 'apiSettingsEnabled',
				'label'    => esc_html__( 'API Settings', 'gf-nmi' ),
				'type'     => $this->is_collect_js_enabled() ? 'override_enabled_checkbox' : 'checkbox',
				'tooltip'  => '<h6>' . esc_html__( 'API Settings', 'gf-nmi' ) . '</h6>' . esc_html__( 'Override the settings provided on the NMI Settings page and use these instead for this feed. Only works if Collect.js is not enabled.', 'gf-nmi' ),
				'onchange' => "if(jQuery(this).prop('checked')){
									jQuery('#{$this->_input_container_prefix}overrideMode').show();
									jQuery('#{$this->_input_container_prefix}override_api_keys').show();
									if( jQuery('input[name=\"api_keys\"]').is(':checked') ) {
										jQuery('#{$this->_input_container_prefix}override_private_key').show();
									} else {
										jQuery('#{$this->_input_container_prefix}overrideLogin').show();
										jQuery('#{$this->_input_container_prefix}overrideKey').show();
									}
								} else {
									jQuery('#{$this->_input_container_prefix}overrideMode').hide();
									jQuery('#{$this->_input_container_prefix}override_api_keys').hide();
									jQuery('#{$this->_input_container_prefix}override_private_key').hide();
									jQuery('#{$this->_input_container_prefix}overrideLogin').hide();
									jQuery('#{$this->_input_container_prefix}overrideKey').hide();
									jQuery('#overrideLogin').val('');
									jQuery('#overrideKey').val('');
									jQuery('#override_private_key').val('');
									jQuery('i').removeClass('icon-check fa-check gf_valid');
								}",
				'choices'  => array(
					array(
						'label' => 'Override Default Settings',
						'name'  => 'apiSettingsEnabled',
					),
				)
			),
			array(
				'name'          => 'overrideMode',
				'label'         => esc_html__( 'Mode', 'gf-nmi' ),
				'type'          => 'radio',
				'default_value' => 'test',
				'hidden'        => ! $this->get_setting( 'apiSettingsEnabled' ),
				'tooltip'       => '<h6>' . esc_html__( 'Mode', 'gf-nmi' ) . '</h6>' . esc_html__( 'Select either Production or Test mode to override the chosen mode on the NMI Settings page.', 'gf-nmi' ),
				'choices'       => array(
					array(
						'label' => esc_html__( 'Production', 'gf-nmi' ),
						'value' => 'production',
					),
					array(
						'label' => esc_html__( 'Test', 'gf-nmi' ),
						'value' => 'test',
					),
				),
				'horizontal'    => true,
			),
			array(
				'name'    => 'override_api_keys',
				'label'   => esc_html__( 'Authentication', 'gf-nmi' ),
				'type'    => 'override_api_keys_checkbox',
				'hidden'  => ! $this->get_setting( 'apiSettingsEnabled' ),
				'tooltip' => '<h6>' . esc_html__( 'Authentication', 'gf-nmi' ) . '</h6>' . '<p>' . sprintf( esc_html__( 'To change this, please visit the main settings page %shere%s.', 'gf-nmi' ), '<a target="_blank" href="' . add_query_arg( array( 'page' => 'gf_settings', 'subview' => $this->_slug ), admin_url( 'admin.php' ) ) . '">', '</a>' ) . '</p>',
				'choices' => array(
					array(
						'label'         => esc_html__( 'API Keys', 'gf-nmi' ),
						'name'          => 'api_keys',
						'default_value' => 1,
					),
				),
			),
			array(
				'name'              => 'override_private_key',
				'label'             => esc_html__( 'Private Key', 'gf-nmi' ),
				'type'              => 'text',
				'class'             => 'medium',
				'hidden'            => ! $this->get_setting( 'apiSettingsEnabled' ) || ! $this->get_plugin_setting( 'api_keys' ),
				'tooltip'           => '<h6>' . esc_html__( 'Private Key', 'gf-nmi' ) . '</h6>' . esc_html__( 'Enter a new value to override the Private Key on the NMI Settings page.', 'gf-nmi' ),
				'feedback_callback' => array( $this, 'is_valid_override_settings' ),
			),
			array(
				'name'              => 'overrideLogin',
				'label'             => esc_html__( 'Gateway Username', 'gf-nmi' ),
				'type'              => 'text',
				'class'             => 'medium',
				'hidden'            => ! $this->get_setting( 'apiSettingsEnabled' ) || $this->get_plugin_setting( 'api_keys' ),
				'tooltip'           => '<h6>' . esc_html__( 'Gateway Username', 'gf-nmi' ) . '</h6>' . esc_html__( 'Enter a new value to override the Gateway Username on the NMI Settings page.', 'gf-nmi' ),
				'feedback_callback' => array( $this, 'is_valid_override_settings' ),
			),
			array(
				'name'              => 'overrideKey',
				'label'             => esc_html__( 'Gateway Password', 'gf-nmi' ),
				'type'              => 'text',
				'input_type'        => 'password',
				'class'             => 'medium',
				'hidden'            => ! $this->get_setting( 'apiSettingsEnabled' ) || $this->get_plugin_setting( 'api_keys' ),
				'tooltip'           => '<h6>' . esc_html__( 'Gateway Password', 'gf-nmi' ) . '</h6>' . esc_html__( 'Enter a new value to override the Gateway Password on the NMI Settings page.', 'gf-nmi' ),
				'feedback_callback' => array( $this, 'is_valid_override_settings' ),
			),
		);
		$default_settings = $this->add_field_after( 'conditionalLogic', $override_settings, $default_settings );

		return apply_filters( 'gform_nmi_addon_feed_settings_fields', $default_settings, $form );
	}

	public function checkbox_input_change_post_status( $choice, $attributes, $value, $tooltip ) {
		$markup         = $this->checkbox_input( $choice, $attributes, $value, $tooltip );
		$dropdown_field = array(
			'name'     => 'update_post_action',
			'choices'  => array(
				array( 'label' => '' ),
				array( 'label' => esc_html__( 'Mark Post as Draft', 'gf-nmi' ), 'value' => 'draft' ),
				array( 'label' => esc_html__( 'Delete Post', 'gf-nmi' ), 'value' => 'delete' ),
			),
			'onChange' => "var checked = jQuery(this).val() ? 'checked' : false; jQuery('#change_post_status').attr('checked', checked);",
		);
		$markup         .= '&nbsp;&nbsp;' . $this->settings_select( $dropdown_field, false );

		return $markup;
	}

	/**
	 * Append the phone field to the default billing_info_fields added by the framework.
	 *
	 * @return array
	 */
	public function billing_info_fields() {

		$fields = parent::billing_info_fields();

		array_unshift( $fields, array(
			'name'     => 'lastName',
			'label'    => esc_html__( 'Last Name', 'gf-nmi' ),
			'required' => false
		) );
		array_unshift( $fields, array(
			'name'     => 'firstName',
			'label'    => esc_html__( 'First Name', 'gf-nmi' ),
			'required' => false
		) );

		$fields[] = array(
			'name'     => 'phone',
			'label'    => esc_html__( 'Phone', 'gf-nmi' ),
			'required' => false
		);

		$fields[] = array(
			'name'     => 'company',
			'label'    => esc_html__( 'Company', 'gf-nmi' ),
			'required' => false
		);

		$fields[] = array(
			'name'     => 'order_desc',
			'label'    => esc_html__( 'Description', 'gf-nmi' ),
			'required' => false
		);

		$fields[] = array(
			'name'     => 'orderid',
			'label'    => esc_html__( 'Order ID', 'gf-nmi' ),
			'required' => false,
			'tooltip'  => '<h6>' . esc_html__( 'Order ID', 'gf-nmi' ) . '</h6>' . esc_html__( 'OPTIONAL. Defaults to a random order id generated by the plugin.', 'gf-nmi' ),
		);

		return $fields;
	}

	/**
	 * Add supported notification events.
	 *
	 * @param array $form The form currently being processed.
	 *
	 * @return array
	 */
	public function supported_notification_events( $form ) {
		if ( ! $this->has_feed( $form['id'] ) ) {
			return array();
		}
		return array(
			'complete_payment'	=> esc_html__( 'Payment Completed', 'gf-nmi' ),
			'fail_payment'      => esc_html__( 'Payment Failed', 'gf-nmi' ),
		);
	}

	public function settings_override_enabled_checkbox( $field, $echo = true ) {

		$html           = $this->settings_checkbox( $field, false );
		$checkbox_value = ! $this->is_collect_js_enabled();

		$html .= "<script type='text/javascript'>
                    jQuery(function($) {
                        $('input[name=\'apiSettingsEnabled\']').prop('disabled', true).prop('checked', $checkbox_value);
                    });
                </script>";

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	public function settings_override_api_keys_checkbox( $field, $echo = true ) {

		$html           = $this->settings_checkbox( $field, false );
		$checkbox_value = $this->get_plugin_setting( 'api_keys' );

		$html .= "<script type='text/javascript'>
                    jQuery(function($) {
                        $('input[name=\'api_keys\']').prop('disabled', true).prop('checked', $checkbox_value);
                    });
                </script>";

		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	//------ PROCESSING SINGLE PAYMENT ------//

	public function authorize( $feed, $submission_data, $form, $entry ) {

		$payment_args = $this->get_payment_args( $feed, $submission_data, $form, $entry );

		$this->log_debug( __METHOD__ . "(): Processing transaction of " . GFCommon::to_money( $submission_data['payment_amount'], rgar( $entry, 'currency' ) ) );

		$response = $this->nmi_request( $payment_args, $feed );

		if ( is_wp_error( $response ) ) {

			$this->log_error( __METHOD__ . '(): Funds could not be processed. Response => ' . print_r( $response, true ) );

			return $this->failed_notification_plus_error( $entry, $response->get_error_message() );
		}

		if ( $response['response'] == 1 ) {

			$this->log_debug( __METHOD__ . "(): Payment processed successfully. Amount: " . GFCommon::to_money( $submission_data['payment_amount'], rgar( $entry, 'currency' ) ) . " Transaction Id: {$response['transactionid']}." );

			$captured_payment = array(
				'is_success'     => true,
				'error_message'  => '',
				'transaction_id' => $response['transactionid'],
				'amount'         => $submission_data['payment_amount']
			);
			$transaction = array(
				'is_authorized'    => true,
				'transaction_id'   => $response['transactionid'],
				'captured_payment' => $captured_payment,
			);

			return $transaction;

		}

	}

	public function get_payment_args( $feed, $submission_data, $form, $entry ) {

		$feed_name = rgars( $feed, 'meta/feedName' );
		$this->log_debug( __METHOD__ . "(): Initializing new NMI transaction based on feed #{$feed['id']} - {$feed_name}." );

		$names = isset( $submission_data['card_name'] ) ? $this->get_first_last_name( $submission_data['card_name'] ) : '';

		$payment_args = array(
			'type'           	=> 'sale',
			'orderid'           => rgar( $submission_data, 'orderid' ) ? rgar( $submission_data, 'orderid' ) : uniqid(),
			'order_description' => rgar( $submission_data, 'order_desc' ) ? rgar( $submission_data, 'order_desc' ) : rgar( $submission_data, 'form_title' ),
			'amount'            => rgar( $submission_data, 'payment_amount' ),
			'first_name'        => rgar( $submission_data, 'firstName' ) ? rgar( $submission_data, 'firstName' ) : rgar( $names, 'first_name' ),
			'last_name'         => rgar( $submission_data, 'lastName' ) ? rgar( $submission_data, 'lastName' ) : rgar( $names, 'last_name' ),
			'address1'          => rgar( $submission_data, 'address' ),
			'address2'          => rgar( $submission_data, 'address2' ),
			'city'              => rgar( $submission_data, 'city' ),
			'state'             => rgar( $submission_data, 'state' ),
			'country'           => rgar( $submission_data, 'country' ),
			'zip'               => rgar( $submission_data, 'zip' ),
			'email'             => rgar( $submission_data, 'email' ),
			'phone'             => rgar( $submission_data, 'phone' ),
			'company'           => rgar( $submission_data, 'company' ),
			'currency'          => rgar( $entry, 'currency' ),
		);

		if ( $js_response = $this->get_collect_js_response() ) {
			$payment_args['payment_token'] = $js_response['token'];
		}

		if ( ! rgar( $payment_args, 'payment_token' ) ) {
			$payment_args['ccnumber'] = $submission_data['card_number'];
			$payment_args['ccexp']    = str_pad( $submission_data['card_expiration_date'][0], 2, '0', STR_PAD_LEFT ) . substr( $submission_data['card_expiration_date'][1], -2 );
			$payment_args['cvv']      = $submission_data['card_security_code'];
		}

		return apply_filters( 'gf_nmi_payment_args', $payment_args, $feed, $submission_data, $form, $entry );
	}

	function nmi_request( $args, $feed, $request_type = 'transact' ) {

		$request_url = self::ENDPOINT_URL_TRANSACT;
		$request_url = apply_filters( 'gf_nmi_request_url', $request_url, $request_type );

		$api_settings = $this->get_api_settings( $feed );

		$auth_params = rgar( $api_settings, 'api_keys' ) ? array(
			'security_key' => $api_settings['security_key']
		) : array(
			'username' => $api_settings['username'],
			'password' => $api_settings['password'],
		);

		$args['customer_receipt'] = (bool) rgars( $feed, 'meta/customerReceipt' );
		$args['ipaddress']        = GFFormsModel::get_ip();

		$args['currency'] = isset( $args['currency'] ) ? $args['currency'] : GFCommon::get_currency();
		$args['sec_code'] = isset( $args['sec_code'] ) ? $args['sec_code'] : 'WEB';

		if ( isset( $args['customer_vault_id'] ) && empty( $args['customer_vault_id'] ) ) {
			unset( $args['customer_vault_id'] );
		}

		if ( isset( $args['transactionid'] ) && empty( $args['transactionid'] ) ) {
			unset( $args['transactionid'] );
		}

		if ( isset( $args['state'] ) && empty( $args['state'] ) && ! in_array( $args['type'], array( 'capture', 'void', 'refund' ) ) ) {
			$args['state'] = 'NA';
		}

		$args = array_merge( $args, $auth_params );

		// Setting custom timeout for the HTTP request
		add_filter( 'http_request_timeout', array( $this, 'http_request_timeout' ), 9999 );

		//$headers = array( 'Content-Type' => 'application/json' );
		$headers  = array();
		$response = wp_remote_post( $request_url, array( 'body' => $args, 'headers' => $headers ) );

		$result = is_wp_error( $response ) ? $response : wp_remote_retrieve_body( $response );

		// Saving to Log here
		$message = sprintf( "\nPosting to: \n%s\nRequest: \n%sResponse: \n%s", $request_url, print_r( $args, 1 ), print_r( $result, 1 ) );
		//$this->log_debug( __METHOD__ . '(): ' . $message );
		$this->log_debug( __METHOD__ . '(): ' . sprintf( "\nPosting to: \n%s\nResponse: \n%s", $request_url, print_r( $result, 1 ) ) );

		remove_filter( 'http_request_timeout', array( $this, 'http_request_timeout' ), 9999 );

		if ( is_wp_error( $result ) ) {
			return $result;
		} elseif ( empty( $result ) ) {
			return new WP_Error( 'invalid_response', __( 'There was an error with the gateway response.', 'gf-nmi' ) );
		}

		parse_str( $result, $result );

		if ( count( $result ) < 8 ) {
			return new WP_Error( 'invalid_response', sprintf( __( 'Unrecognized response from the gateway: %s', 'gf-nmi' ), $response ) );
		}

		if ( ! isset( $result['response'] ) || ! in_array( $result['response'], array( 1, 2, 3 ) ) ) {
			return new WP_Error( 'invalid_response', __( 'There was an error with the gateway response.', 'gf-nmi' ) );
		}

		if ( $result['response'] == 2 ) {
			return new WP_Error( 'decline_response', '<!-- Error: ' . $result['response_code'] . ' --> ' . __( 'Your card has been declined.', 'gf-nmi' ), $result );
		}

		if ( $result['response'] == 3 ) {
			return new WP_Error( 'error_response', '<!-- Error: ' . $result['response_code'] . ' -->' . $result['responsetext'], $result );
		}

		return $result;

	}

	public function http_request_timeout( $timeout_value ) {
		return 45; // 45 seconds. Too much for production, only for testing.
	}

	public function failed_notification_plus_error( $entry, $error_message ) {
		$entry['payment_status'] = 'Failed';
		$payment = array( 'error_message' => $error_message, 'type' => 'fail_payment' );

		$this->post_payment_action( $entry, $payment );

		return array( 'error_message' => $error_message, 'is_success' => false, 'is_authorized' => false );
	}

	/**
	 * Check if the current entry was processed by this add-on.
	 *
	 * @param int $entry_id The ID of the current Entry.
	 *
	 * @return bool
	 */
	public function is_payment_gateway( $entry_id ) {
		if ( $this->is_payment_gateway ) {
			return true;
		}
		$gateway = gform_get_meta( $entry_id, 'payment_gateway' );

		return in_array( $gateway, array( 'NMI', $this->_slug ) );
	}

	private function get_first_last_name( $text ) {
		$names      = explode( ' ', $text );
		$first_name = rgar( $names, 0 );
		$last_name  = '';
		if ( count( $names ) > 1 ) {
			$last_name = rgar( $names, count( $names ) - 1 );
		}

		return array( 'first_name' => $first_name, 'last_name' => $last_name );
	}

	/**
	 * Validate the card type and prevent the field from failing required validation, collect.js will handle the required validation.
	 *
	 * The card field inputs are erased on submit, this will cause two issues:
	 * 1. The field will fail standard validation if marked as required.
	 * 2. The card type validation will not be performed.
	 *
	 * @param array $result The field validation result and message.
	 * @param mixed $value The field input values; empty for the credit card field as they are cleared by frontend.js.
	 * @param array $form The Form currently being processed.
	 * @param GF_Field $field The field currently being processed.
	 *
	 * @return array $result The results of the validation.
	 * @uses    GF_NMI::get_card_slug()
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GF_NMI::init()
	 * @uses    GF_Field_CreditCard::is_card_supported()
	 */
	public function pre_validation( $result, $value, $form, $field ) {

		// If this is a credit card field and the last four credit card digits are defined, validate.
		if ( $field->type == 'creditcard' && rgpost( 'collect_js_token' ) ) {

			// Get card type.
			$response = $this->get_collect_js_response();

			// Get card slug.
			$card_slug = rgars( $response, 'card/type' );

			// If credit card type is not supported, mark field as invalid.
			if ( ! $field->is_card_supported( $card_slug ) ) {
				$result['is_valid'] = false;
				$result['message']  = sprintf( esc_html__( 'Card type (%s) is not supported. Please enter one of the supported credit cards.', 'gf-nmi' ), $card_slug );
			} else {
				$result['is_valid'] = true;
				$result['message']  = '';
			}
		}

		return $result;
	}

	/**
	 * Get the slug for the card type returned by NMI
	 *
	 *
	 * @return string
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GF_NMI::pre_validation()
	 * @uses    GFCommon::get_card_types()
	 *
	 */
	public function get_card_type( $token, $field = 'slug' ) {

		// If type is defined, attempt to get card slug.
		if ( $token ) {
			// Get card types.
			$card_types = GFCommon::get_card_types();

			// Loop through card types.
			foreach ( $card_types as $card ) {
				// If the requested card type is equal to the current card's name, return the slug.
				if ( strpos( $token, rgar( $card, 'slug' ) ) !== false ) {
					return rgar( $card, $field );
				}
			}
		}

		return 'unknown';
	}

	/**
	 * Helper to check that Collect.js is enabled.
	 *
	 * @return bool True if Collect.js is enabled. False otherwise.
	 * @uses    GFAddOn::get_plugin_setting()
	 *
	 * @since  2.6.0
	 * @access public
	 *
	 * @used-by GF_NMI::scripts()
	 */
	public function is_collect_js_enabled() {
		return $this->get_plugin_setting( 'api_keys' ) && ! empty( $this->get_plugin_setting( 'tokenization_key' ) );
	}

	/**
	 * Populate the $_POST with the last four digits of the card number and card type.
	 *
	 * @param array $form Form object.
	 *
	 * @uses    GFPaymentAddOn::$is_payment_gateway
	 * @uses    GFPaymentAddOn::get_credit_card_field()
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GF_NMI::init()
	 */
	public function populate_credit_card_last_four( $form ) {

		if ( ! $this->is_collect_js_enabled() || ! $this->is_payment_gateway || ! $this->has_credit_card_field( $form ) ) {
			return;
		}

		$response = $this->get_collect_js_response();

		if ( $this->has_credit_card_field( $form ) ) {
			$cc_field = $this->get_credit_card_field( $form );
			// Get card name.
			$card_name = $this->get_card_type( rgars( $response, 'card/type' ), 'name' );

			$_POST[ 'input_' . $cc_field->id . '_1' ] = 'XXXXXXXXXXXX' . substr( rgars( $response, 'card/number' ), -4 );
			$_POST[ 'input_' . $cc_field->id . '_4' ] = $card_name;
		}
	}

	/**
	 * Check if a collect.js has an error or is missing the ID and then return the appropriate message.
	 *
	 * @return bool|string The error. False if the error does not exist.
	 * @uses    GF_NMI::get_collect_js_response()
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GF_NMI::authorize()
	 */
	public function get_collect_js_error() {

		// Get collect.js response.
		$response = $this->get_collect_js_response();

		if ( rgar( $response, 'ccnumber' ) ) {
			return rgar( $response, 'ccnumber' ) != 1 ? rgar( $response, 'ccnumber' ) : esc_html__( 'Invalid card number. Please try again', 'gf-nmi' );
		}

		if ( rgar( $response, 'ccexp' ) ) {
			return rgar( $response, 'ccexp' ) != 1 ? rgar( $response, 'ccexp' ) : esc_html__( 'Invalid card expiry date. Please try again', 'gf-nmi' );
		}

		if ( rgar( $response, 'cvv' ) ) {
			return rgar( $response, 'cvv' ) != 1 ? rgar( $response, 'cvv' ) : esc_html__( 'Invalid CVV. Please try again', 'gf-nmi' );
		}

		// If an error message is provided, return error message.
		if ( $response == '-1' ) {
			return esc_html__( 'No response from collect.js.', 'gf-nmi' );
		}

		return false;

	}

	/**
	 * Response from collect.js is posted to the server as 'collect_js_response'.
	 *
	 * @return string|int A valid collect.js response object or null
	 * @since Unknown
	 * @access public
	 *
	 * @used-by GF_NMI::authorize_product()
	 * @used-by GF_NMI::get_collect_js_error()
	 *
	 */
	public function get_collect_js_response() {
		$response = json_decode( rgpost( 'collect_js_response' ), 1 );

		if ( $this->is_collect_js_enabled() && empty( $response ) ) {
			return -1;
		}

		return $response;
	}

	public function add_tokenization_key_to_js( $tag, $handle ) {
		if ( 'nmi-collect.js' !== $handle ) {
			return $tag;
		}

		return str_replace( ' src', ' data-tokenization-key="' . $this->tokenization_key . '" src', $tag );
	}

	public function upgrade( $previous_version ) {

		if ( empty( $previous_version ) ) {
			$previous_version = get_option( 'gravityformsaddon_nmi-gateway_version' );
		}

		if ( ! empty( $previous_version ) && version_compare( $previous_version, '1.2.0', '<' ) ) {
			$settings = get_option( 'gravityformsaddon_nmi-gateway_settings' );

			if ( ! empty( $settings ) && rgar( $settings, 'loginId' ) && rgar( $settings, 'transactionKey' ) && ! rgar( $settings, 'private_key' ) && ! rgar( $settings, 'tokenization_key' ) ) {
				$this->log_debug( __METHOD__ . '(): Copying plugin settings.' );
				$settings['api_keys'] = 0;

				parent::update_plugin_settings( $settings );
			}
		}

	}

	public function get_menu_icon() {
		return GF_NMI_PLUGIN_URL . '/images/icon.png';
	}

}