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

	/** @var int The site's normal incomplete-submission retention period. */
	private $default_incomplete_submission_expiration_days = 30;

	/** @var string User-meta key prefix for Ball Reservation resume tokens. */
	const RESUME_TOKEN_META_KEY_PREFIX = '_gf_ball_reservation_resume_token_';

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
	 * Registers backend hooks used by the normal Gravity Forms cleanup process.
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		// This filter is global. Capture its final value so other forms retain their existing setting.
		add_filter( 'gform_incomplete_submissions_expiration_days', array( $this, 'capture_incomplete_submission_expiration_days' ), 9999 );
		add_filter( 'gform_purge_expired_incomplete_submissions_query', array( $this, 'set_ball_reservation_draft_retention' ) );
		add_action( 'gform_incomplete_submission_post_save', array( $this, 'store_current_users_resume_token' ), 10, 4 );
		add_action( 'gform_after_submission', array( $this, 'clear_current_users_resume_token' ), 10, 2 );
		add_filter( 'gform_form_args', array( $this, 'load_current_users_incomplete_submission' ) );
		add_filter( 'gpnf_save_and_continue_token', array( $this, 'provide_nested_forms_resume_token' ), 10, 2 );
		add_filter( 'gform_pre_process', array( $this, 'remove_save_and_continue_email_control' ) );
		add_filter( 'gform_disable_notification', array( $this, 'disable_save_and_continue_email_notification' ), 10, 5 );
		add_filter( 'gform_other_choice_value', array( $this, 'set_payer_other_choice_value' ), 10, 2 );
		add_filter( 'gform_field_content_30', array( $this, 'set_payer_other_choice_placeholder' ), 10, 2 );
		add_filter( 'gform_field_content_30', array( $this, 'render_payment_summary_html_field' ), 20, 2 );
		add_filter( 'gform_field_content_31', array( $this, 'set_payer_other_choice_placeholder' ), 10, 2 );
		add_filter( 'gform_replace_merge_tags', array( $this, 'replace_payment_summary_merge_tag' ), 10, 7 );
		add_shortcode( 'gf_ball_reservation_payment_summary', array( $this, 'payment_summary_shortcode' ) );
		add_action( 'wp_ajax_gf_ball_reservation_payment_summary', array( $this, 'ajax_payment_summary' ) );
		add_action( 'wp_ajax_nopriv_gf_ball_reservation_payment_summary', array( $this, 'ajax_payment_summary' ) );
	}

	/**
	 * Keeps the native Other radio label in place.
	 *
	 * @param string        $value Default Other-choice input value.
	 * @param GF_Field|null $field Field being rendered or validated.
	 * @return string
	 */
	public function set_payer_other_choice_value( $value, $field ) {
		if ( is_object( $field ) && $this->is_payer_field( $field ) ) {
			return 'Other';
		}

		return $value;
	}

	/**
	 * Adds a placeholder to the text input that Gravity Forms appends to Other.
	 *
	 * The radio label remains "Other" and the entered text is the saved value.
	 *
	 * @param string   $content Field HTML.
	 * @param GF_Field $field   Field being rendered.
	 * @return string
	 */
	public function set_payer_other_choice_placeholder( $content, $field ) {
		if ( ! is_object( $field ) || ! $this->is_payer_field( $field ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/<input\\b[^>]*\\bname=(["\'])input_' . absint( $field->id ) . '_other\\1[^>]*>/i',
			function( $matches ) {
				$input = preg_replace( '/\\svalue=(["\']).*?\\1/i', ' value=""', $matches[0] );

				if ( false === stripos( $input, ' placeholder=' ) ) {
					$input = preg_replace( '/\\s*\\/?>(?=$)/', ' placeholder="Enter who is paying."$0', $input, 1 );
				}

				return $input;
			},
			$content
		);
	}

	/**
	 * Determines whether a field is one of the payer radio fields.
	 *
	 * @param GF_Field $field Field being checked.
	 * @return bool
	 */
	private function is_payer_field( $field ) {
		return ( 31 === absint( $field->formId ) && 13 === absint( $field->id ) )
			|| ( 30 === absint( $field->formId ) && 64 === absint( $field->id ) );
	}

	/**
	/**
	 * Populates Form 30's dedicated payment-summary HTML field.
	 *
	 * @param string   $content Rendered field HTML.
	 * @param GF_Field $field   Field being rendered.
	 * @return string
	 */
	public function render_payment_summary_html_field( $content, $field ) {
		$shortcode = '[gf_ball_reservation_payment_summary]';

		if ( ! is_object( $field ) || 'html' !== $field->type ) {
			return $content;
		}

		// Form 30, Field 89 is the summary field on page three. Retain shortcode
		// support for any additional HTML field that intentionally uses it.
		if ( 89 === absint( $field->id ) ) {
			return $this->get_live_payment_summary_container();
		}

		if ( false === strpos( $content, $shortcode ) ) {
			return $content;
		}

		return str_replace( $shortcode, $this->get_live_payment_summary_container(), $content );
	}

	/**
	 * Builds a live-summary container which is populated when page three loads.
	 *
	 * @return string
	 */
	private function get_live_payment_summary_container() {
		$ajax_url = wp_json_encode( admin_url( 'admin-ajax.php' ) );
		$nonce    = wp_json_encode( wp_create_nonce( 'gf_ball_reservation_payment_summary' ) );
		$script   = sprintf(
			'(function($){function refresh(){var $form=$("#gform_30"),$summary=$form.find(".gf-ball-reservation-payment-summary-live");if(!$summary.length){return;}var $selected=$form.find("[name=input_64]:checked"),payer=$selected.val()||"";if(payer==="gf_other_choice"){payer=$form.find("[name=input_64_other]").val()||"";}$summary.each(function(){var $container=$(this);$.post(%1$s,{action:"gf_ball_reservation_payment_summary",nonce:%2$s,reservation_count:$form.find("[name=input_81]").val()||"",payer:payer,child_entry_ids:$form.find("[name=input_59]").val()||""}).done(function(response){if(response&&response.success&&response.data&&response.data.html){$container.html(response.data.html);}else{$container.text("Payment summary is not available yet.");}}).fail(function(){$container.text("Payment summary could not be loaded.");});});}$(document).on("gform_post_render gform_page_loaded",function(event,formId){if(Number(formId)===30){refresh();}});refresh();}(jQuery));',
			$ajax_url,
			$nonce
		);

		return sprintf(
			'<div class="gf-ball-reservation-payment-summary-live" data-ajax-url="%1$s" data-nonce="%2$s">%3$s</div><script>%4$s</script>',
			esc_url( admin_url( 'admin-ajax.php' ) ),
			esc_attr( wp_create_nonce( 'gf_ball_reservation_payment_summary' ) ),
			esc_html__( 'Loading payment summary…', 'gf-ball-reservation-form' ),
			$script
		);
	}

	/**
	 * Returns the in-progress payment summary for the active Nested Forms session.
	 *
	 * @return void
	 */
	public function ajax_payment_summary() {
		check_ajax_referer( 'gf_ball_reservation_payment_summary', 'nonce' );

		$child_entry_ids = array_filter( array_map( 'absint', explode( ',', (string) rgpost( 'child_entry_ids' ) ) ) );

		// Only allow guest entries attached to this browser's Nested Forms session.
		if ( class_exists( 'GPNF_Session' ) ) {
			$session_entries = ( new GPNF_Session( 30 ) )->get( 'nested_entries' );
			$session_ids     = is_array( $session_entries ) && isset( $session_entries[59] ) ? (array) $session_entries[59] : array();
			$allowed_ids     = array_filter( array_map( 'absint', explode( ',', implode( ',', $session_ids ) ) ) );

			// Depending on the Nested Forms request type, its session entry list is
			// not always available. In that case use the field's submitted IDs.
			if ( empty( $child_entry_ids ) && ! empty( $allowed_ids ) ) {
				$child_entry_ids = array_values( $allowed_ids );
			} elseif ( ! empty( $allowed_ids ) ) {
				$child_entry_ids = array_values( array_intersect( $child_entry_ids, $allowed_ids ) );
			}
		}

		$entry = array(
			'form_id' => 30,
			'81'      => rgpost( 'reservation_count' ),
			'64'      => rgpost( 'payer' ),
			'59'      => implode( ',', $child_entry_ids ),
		);

		wp_send_json_success( array( 'html' => $this->get_payment_summary_markup( $entry ) ) );
	}

	/**
	 * Registers a payment-summary merge tag and makes the shortcode usable in
	 * Gravity Forms confirmations and notifications.
	 *
	 * Use {ball_reservation_payment_summary} (recommended) or
	 * [gf_ball_reservation_payment_summary] in a Form 30 confirmation or
	 * notification. The shortcode also supports entry_id when used in content.
	 *
	 * @param string     $text       Text containing merge tags.
	 * @param array|bool $form       Current form.
	 * @param array|bool $entry      Current entry.
	 * @param bool       $url_encode Whether URLs are encoded.
	 * @param bool       $esc_html   Whether HTML is escaped.
	 * @param bool       $nl2br      Whether newlines are converted.
	 * @param string     $format     Output format.
	 * @return string
	 */
	public function replace_payment_summary_merge_tag( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
		$merge_tag = '{ball_reservation_payment_summary}';
		$shortcode = '[gf_ball_reservation_payment_summary]';

		if ( false === strpos( $text, $merge_tag ) && false === strpos( $text, $shortcode ) ) {
			return $text;
		}

		if ( ! is_array( $entry ) || 30 !== absint( rgar( $entry, 'form_id' ) ) ) {
			return str_replace( array( $merge_tag, $shortcode ), '', $text );
		}

		$summary = $this->get_payment_summary_markup( $entry );

		return str_replace( array( $merge_tag, $shortcode ), $summary, $text );
	}

	/**
	 * Outputs a payment summary for an existing Form 30 entry.
	 *
	 * Example: [gf_ball_reservation_payment_summary entry_id="123"]
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string
	 */
	public function payment_summary_shortcode( $attributes ) {
		$attributes = shortcode_atts( array( 'entry_id' => 0 ), $attributes, 'gf_ball_reservation_payment_summary' );
		$entry_id   = absint( $attributes['entry_id'] );

		if ( ! $entry_id || ! class_exists( 'GFAPI' ) ) {
			return '';
		}

		$entry = GFAPI::get_entry( $entry_id );

		return is_wp_error( $entry ) || 30 !== absint( rgar( $entry, 'form_id' ) ) ? '' : $this->get_payment_summary_markup( $entry );
	}

	/**
	 * Builds the reservation count and payer list for one parent entry.
	 *
	 * @param array $parent_entry Form 30 entry.
	 * @return string
	 */
	private function get_payment_summary_markup( $parent_entry ) {
		$items = array();

		$this->add_payment_summary_item( $items, rgar( $parent_entry, '81' ), rgar( $parent_entry, '64' ) );

		foreach ( $this->get_child_entries( $parent_entry ) as $child_entry ) {
			$this->add_payment_summary_item( $items, rgar( $child_entry, '14' ), rgar( $child_entry, '13' ) );
		}

		if ( empty( $items ) ) {
			return '';
		}

		$total_reservations = 0;
		$payer_totals       = array(
			'Table Hostess' => 0,
			'Other'         => 0,
			'Las Madrinas'  => 0,
		);

		foreach ( $items as $item ) {
			$total_reservations += $item['count'];
			$payer_category     = isset( $payer_totals[ $item['payer'] ] ) ? $item['payer'] : 'Other';
			$payer_totals[ $payer_category ] += $item['count'];
		}

		$lines = array(
			sprintf(
				'<strong>%1$s</strong> = %2$s',
				esc_html__( 'Total Reservations', 'gf-ball-reservation-form' ),
				esc_html( (string) $total_reservations )
			),
		);

		foreach ( $payer_totals as $payer => $count ) {
			if ( ! $count ) {
				continue;
			}

			$lines[] = sprintf(
				'<strong>%1$s</strong> %2$s = %3$s',
				esc_html( $payer . ':' ),
				esc_html( (string) $count ),
				esc_html( $this->format_currency( $count * $this->get_reservation_price() ) )
			);
		}

		$markup = '<p>' . implode( '<br>', $lines ) . '</p>';

		return $markup;
	}

	/**
	 * Adds a valid reservation/payer pair to a summary.
	 *
	 * @param array  $items Summary items.
	 * @param mixed  $count Reservation count.
	 * @param mixed  $payer Payer name.
	 * @return void
	 */
	private function add_payment_summary_item( &$items, $count, $payer ) {
		$count = absint( $count );
		$payer = is_scalar( $payer ) ? trim( wp_strip_all_tags( (string) $payer ) ) : '';

		if ( $count && '' !== $payer ) {
			$items[] = array( 'count' => $count, 'payer' => $payer );
		}
	}

	/**
	 * Gets entries linked to the configured Nested Forms field.
	 *
	 * @param array $parent_entry Form 30 entry.
	 * @return array
	 */
	private function get_child_entries( $parent_entry ) {
		if ( ! empty( $parent_entry['id'] ) && class_exists( 'GPNF_Entry' ) ) {
			$nested_entry = new GPNF_Entry( $parent_entry );
			$entries      = $nested_entry->get_child_entries( 59 );

			return is_array( $entries ) ? $entries : array();
		}

		$entry_ids = array_filter( array_map( 'absint', explode( ',', (string) rgar( $parent_entry, '59' ) ) ) );
		$entries   = array();

		foreach ( $entry_ids as $entry_id ) {
			$entry = GFAPI::get_entry( $entry_id );
			if ( ! is_wp_error( $entry ) && 31 === absint( rgar( $entry, 'form_id' ) ) ) {
				$entries[] = $entry;
			}
		}

		return $entries;
	}

	/**
	 * Returns the configured price for one reservation.
	 *
	 * @return float
	 */
	private function get_reservation_price() {
		$settings = $this->get_plugin_settings();
		$price    = is_array( $settings ) && isset( $settings['reservation_price'] ) ? (float) $settings['reservation_price'] : 550;

		return $price >= 0 ? $price : 550;
	}

	/**
	 * Formats a value using the site's currency format.
	 *
	 * @param float $amount Amount to format.
	 * @return string
	 */
	private function format_currency( $amount ) {
		return '$' . number_format_i18n( $amount, 2 );
	}

	/**
	 * Stores a resume token only for the authenticated user who saved this form.
	 *
	 * @param array  $submission   Saved incomplete submission data.
	 * @param string $resume_token Gravity Forms resume token.
	 * @param array  $form         Form object.
	 * @param array  $entry        Partial entry.
	 * @return void
	 */
	public function store_current_users_resume_token( $submission, $resume_token, $form, $entry ) {
		if ( ! is_user_logged_in() || ! $this->is_configured_form( $form ) || ! is_string( $resume_token ) || '' === $resume_token ) {
			return;
		}

		update_user_meta( get_current_user_id(), $this->get_resume_token_meta_key(), sanitize_key( $resume_token ) );
	}

	/**
	 * Removes the current user's token when they complete the configured form.
	 *
	 * @param array $entry Completed entry.
	 * @param array $form  Form object.
	 * @return void
	 */
	public function clear_current_users_resume_token( $entry, $form ) {
		if ( is_user_logged_in() && $this->is_configured_form( $form ) ) {
			delete_user_meta( get_current_user_id(), $this->get_resume_token_meta_key() );
		}
	}

	/**
	 * Lets Gravity Forms restore the current user's valid draft via its native flow.
	 *
	 * No redirect is used, so the token never appears in the browser URL. Existing
	 * direct gf_token links are deliberately left to Gravity Forms unchanged.
	 *
	 * @param array $form_args Gravity Forms form rendering arguments.
	 * @return array
	 */
	public function load_current_users_incomplete_submission( $form_args ) {
		if ( is_admin() || wp_doing_ajax() || ! is_user_logged_in() || ! is_array( $form_args ) ) {
			return $form_args;
		}

		$form_id = isset( $form_args['form_id'] ) ? absint( $form_args['form_id'] ) : 0;

		// A direct native resume URL always takes precedence and prevents loops.
		if ( ! $form_id || $form_id !== $this->get_configured_form_id() || isset( $_GET['gf_token'] ) || ! empty( $_POST['gform_resume_token'] ) ) {
			return $form_args;
		}

		$token = get_user_meta( get_current_user_id(), $this->get_resume_token_meta_key(), true );

		if ( ! $this->is_valid_current_users_resume_token( $token, $form_id ) ) {
			delete_user_meta( get_current_user_id(), $this->get_resume_token_meta_key() );

			return $form_args;
		}

		// GFFormDisplay reads gf_token and performs its ordinary native restoration.
		$_GET['gf_token'] = $token;

		return $form_args;
	}

	/**
	 * Provides Nested Forms with the authenticated user's stored resume token.
	 *
	 * Nested Forms uses this token to repopulate child entries while Gravity
	 * Forms restores the parent draft. A direct gf_token always takes priority.
	 *
	 * @param string|null $resume_token Existing Save & Continue token.
	 * @param int|bool    $form_id      Parent form ID.
	 * @return string|null
	 */
	public function provide_nested_forms_resume_token( $resume_token, $form_id ) {
		if ( ! empty( $resume_token ) || ! is_user_logged_in() || absint( $form_id ) !== $this->get_configured_form_id() ) {
			return $resume_token;
		}

		$token = get_user_meta( get_current_user_id(), $this->get_resume_token_meta_key(), true );

		if ( $this->is_valid_current_users_resume_token( $token, absint( $form_id ) ) ) {
			return $token;
		}

		delete_user_meta( get_current_user_id(), $this->get_resume_token_meta_key() );

		return $resume_token;
	}

	/**
	 * Blocks Save & Continue resume-token emails for the configured form.
	 *
	 * Existing completion and administrator notifications are not affected.
	 *
	 * @param bool  $is_disabled Existing disabled state.
	 * @param array $notification Notification settings.
	 * @param array $form         Form object.
	 * @param array $entry        Entry object.
	 * @param array $data         Additional notification data.
	 * @return bool
	 */
	public function disable_save_and_continue_email_notification( $is_disabled, $notification, $form, $entry, $data ) {
		if ( $this->is_configured_form( $form ) && 'form_save_email_requested' === rgar( $notification, 'event' ) ) {
			return true;
		}

		return $is_disabled;
	}

	/**
	 * Removes the email-link control from the saved-form confirmation.
	 *
	 * Notification blocking above remains the server-side safeguard if a request
	 * is forged to submit the removed control.
	 *
	 * @param array $form Form object before Save & Continue processing.
	 * @return array
	 */
	public function remove_save_and_continue_email_control( $form ) {
		if ( ! $this->is_configured_form( $form ) || ! rgpost( 'gform_save' ) || empty( $form['confirmations'] ) || ! is_array( $form['confirmations'] ) ) {
			return $form;
		}

		foreach ( $form['confirmations'] as &$confirmation ) {
			if ( 'form_saved' !== rgar( $confirmation, 'event' ) || empty( $confirmation['message'] ) ) {
				continue;
			}

			$confirmation['message'] = preg_replace( '/\{save_email_input(?::[^}]*)?\}/', '', $confirmation['message'] );
		}
		unset( $confirmation );

		return $form;
	}

	/**
	 * Validates that a stored token still belongs to a current draft for this form.
	 *
	 * @param mixed $token   Stored resume token.
	 * @param int   $form_id Expected form ID.
	 * @return bool
	 */
	private function is_valid_current_users_resume_token( $token, $form_id ) {
		if ( ! is_string( $token ) || '' === $token || sanitize_key( $token ) !== $token || ! method_exists( 'GFFormsModel', 'get_draft_submission_values' ) ) {
			return false;
		}

		$draft = GFFormsModel::get_draft_submission_values( $token );

		if ( ! is_array( $draft ) || absint( rgar( $draft, 'form_id' ) ) !== $form_id || empty( $draft['date_created'] ) ) {
			return false;
		}

		$created_at = strtotime( $draft['date_created'] . ' UTC' );

		return false !== $created_at && $created_at >= ( time() - ( 120 * DAY_IN_SECONDS ) );
	}

	/**
	 * Builds the current configured-form user-meta key.
	 *
	 * @return string
	 */
	private function get_resume_token_meta_key() {
		return self::RESUME_TOKEN_META_KEY_PREFIX . $this->get_configured_form_id();
	}

	/**
	 * Checks whether a form is the configured Ball Reservation form.
	 *
	 * @param array $form Form object.
	 * @return bool
	 */
	private function is_configured_form( $form ) {
		return is_array( $form ) && isset( $form['id'] ) && $this->get_configured_form_id() === absint( $form['id'] );
	}

	/**
	 * Captures Gravity Forms' normal, globally filtered expiration period.
	 *
	 * The expiration-days filter does not provide a form ID, so it cannot itself
	 * be used to target just the Ball Reservation form.
	 *
	 * @param int $expiration_days Number of days before drafts normally expire.
	 * @return int Unchanged expiration period.
	 */
	public function capture_incomplete_submission_expiration_days( $expiration_days ) {
		$this->default_incomplete_submission_expiration_days = max( 0, absint( $expiration_days ) );

		return $expiration_days;
	}

	/**
	 * Retains selected-form Save & Continue drafts for 120 days.
	 *
	 * Gravity Forms runs this query as part of its normal draft cleanup. Its
	 * draft table includes form_id, allowing this supported query filter to
	 * apply a longer period to only the configured form.
	 *
	 * @param array $query Query clauses used by Gravity Forms to purge drafts.
	 * @return array
	 */
	public function set_ball_reservation_draft_retention( $query ) {
		$form_id = $this->get_configured_form_id();

		if ( ! $form_id || ! is_array( $query ) ) {
			return $query;
		}

		global $wpdb;

		$ball_reservation_expiration = gmdate( 'Y-m-d H:i:s', time() - ( 120 * DAY_IN_SECONDS ) );
		$default_expiration          = gmdate( 'Y-m-d H:i:s', time() - ( $this->default_incomplete_submission_expiration_days * DAY_IN_SECONDS ) );

		$query['where'] = $wpdb->prepare(
			'WHERE ( form_id = %d AND date_created < %s ) OR ( form_id != %d AND date_created < %s )',
			$form_id,
			$ball_reservation_expiration,
			$form_id,
			$default_expiration
		);

		return $query;
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
					array(
						'name'          => 'reservation_price',
						'label'         => esc_html__( 'Price Per Reservation', 'gf-ball-reservation-form' ),
						'type'          => 'text',
						'input_type'    => 'number',
						'default_value' => '550',
						'validation_callback' => array( $this, 'is_valid_reservation_price' ),
						'description'   => esc_html__( 'Used when calculating the Table Hostess amount owed in the payment summary.', 'gf-ball-reservation-form' ),
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
	 * Validates the reservation price before the Add-On Framework saves it.
	 *
	 * @param string $value Submitted price.
	 * @return bool
	 */
	public function is_valid_reservation_price( $value ) {
		return is_scalar( $value ) && is_numeric( $value ) && (float) $value >= 0;
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
			array(
				'handle'    => 'gf-ball-reservation-payment-summary',
				'src'       => $this->get_base_url() . '/assets/js/payment-summary.js',
				'version'   => $this->_version,
				'in_footer' => true,
				'deps'      => array( 'jquery', 'gform_gravityforms' ),
				'enqueue'   => array(
					array( $this, 'should_enqueue_address_normalization' ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Registers Ball Reservation-specific frontend styles.
	 *
	 * @return array
	 */
	public function styles() {
		$style_path    = plugin_dir_path( GF_BALL_RESERVATION_FORM_FILE ) . 'assets/css/gpnf-add-entry.css';
		$style_version = file_exists( $style_path ) ? (string) filemtime( $style_path ) : $this->_version;

		$styles = array(
			array(
				'handle'  => 'gf-ball-reservation-gpnf-add-entry',
				'src'     => $this->get_base_url() . '/assets/css/gpnf-add-entry.css',
				'version' => $style_version,
				'enqueue' => array(
					array( $this, 'should_enqueue_address_normalization' ),
				),
			),
		);

		return array_merge( parent::styles(), $styles );
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
