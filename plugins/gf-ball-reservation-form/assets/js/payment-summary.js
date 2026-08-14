( function( $ ) {
	'use strict';

	function getPayer( $form ) {
		var $selected = $form.find( '[name="input_64"]:checked' );

		if ( ! $selected.length ) {
			return '';
		}

		if ( 'gf_other_choice' === $selected.val() ) {
			return $form.find( '[name="input_64_other"]' ).val() || '';
		}

		return $selected.val();
	}

	function refreshSummary( formId ) {
		if ( 30 !== Number( formId ) ) {
			return;
		}

		var $form = $( '#gform_' + formId );
		var $summary = $form.find( '.gf-ball-reservation-payment-summary-live' );

		if ( ! $summary.length ) {
			return;
		}

		$summary.each( function() {
			var $container = $( this );

			$.post( $container.data( 'ajax-url' ), {
				action: 'gf_ball_reservation_payment_summary',
				nonce: $container.data( 'nonce' ),
				reservation_count: $form.find( '[name="input_81"]' ).val() || '',
				payer: getPayer( $form ),
				child_entry_ids: $form.find( '[name="input_59"]' ).val() || ''
			} ).done( function( response ) {
				if ( response && response.success && response.data ) {
					$container.html( response.data.html || '' );
				}
			} );
		} );
	}

	$( document ).on( 'gform_post_render', function( event, formId ) {
		refreshSummary( formId );
	} );

	$( document ).on( 'gform_page_loaded', function( event, formId ) {
		refreshSummary( formId );
	} );
}( jQuery ) );
