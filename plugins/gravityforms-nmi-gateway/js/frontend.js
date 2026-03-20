/**
 * Front-end Script
 */

window.GFNMI = null;

(function($){

    GFNMI = function( args ) {
        for( var prop in args ) {
            if( args.hasOwnProperty( prop ) )
                this[prop] = args[prop];
        }

        this.form = null;
        this.nmi_errors = {};

        this.init = function() {

			if( ! this.isCreditCardOnPage() )
                return;

            this.form = $('#gform_' + this.formId);
            let GFNMIObj = this, activeFeed = null, feedActivated = false, customCss = JSON.parse(GFNMIObj.ccCustomCss);

            gform.addAction('gform_frontend_feeds_evaluated', function (feeds, formId) {
                if ( formId !== GFNMIObj.formId ) {
					return;
				}
                activeFeed = null;
				feedActivated = false;
                for (var i = 0; i < Object.keys(feeds).length; i++) {
                   console.log(feeds[i]);
                   if (feeds[i].addonSlug === 'nmi-gateway' && feeds[i].isActivated) {
                        console.log(GFNMIObj.feeds[i]);
						feedActivated = true;
						activeFeed = GFNMIObj.feeds[i];
						//apiKey = activeFeed.hasOwnProperty('apiKey') ? activeFeed.apiKey : GFNMIObj.apiKey;

						break; // allow only one active feed.
					}
				}

                if (feedActivated) {

					//jQuery('span.ginput_card_security_code_icon').hide();

					var ccInputPrefix = 'input_' + GFNMIObj.formId + '_' + GFNMIObj.ccFieldId + '_';
					card_number_id = '#' + ccInputPrefix + '1';
					card_number = $( '#gform_' + formId ).find(card_number_id);
					card_number.replaceWith('<span id="nmi-card-number-element" class="gf-nmi-elements-field"><!-- a NMI Element will be inserted here. --></span><input id="' + ccInputPrefix + '1" type="hidden">');

					cvv_id = '#' + ccInputPrefix + '3';
					cvv = $( '#gform_' + formId ).find(cvv_id);
					cvv.replaceWith('<span id="nmi-card-cvc-element" class="gf-nmi-elements-field" style="display: inline-block;max-width: 10rem;vertical-align: middle;"><!-- a NMI Element will be inserted here. --></span>');

					card_expiry_id = '#' + ccInputPrefix + '2_month';
					card_expiry = $( '#gform_' + formId ).find(card_expiry_id).parent('span');
					card_expiry.closest('fieldset.ginput_cardinfo_left').css('flex-basis', '50%');
					card_expiry.replaceWith(' <span id="nmi-card-expiry-element" class="gf-nmi-elements-field" style="display: inline-block;width: 90%;"><!-- a NMI Element will be inserted here. --></span>');
					$('fieldset.gfield--type-creditcard #nmi-card-number-element').css('display', 'block');
                    $('li.gfield--type-creditcard #nmi-card-expiry-element').css('margin-bottom', '-12px');
					if($('span.ginput_card_expiration_year_container').length) {
						$('span.ginput_card_expiration_year_container').remove();
					}

                    CollectJS.configure({
                        //"paymentSelector" : "#place_order",
                        "variant" : "inline",
                        "styleSniffer" : "true",
                        "customCss" : (customCss && typeof customCss === "object") ? customCss : {},
                        //"googleFont": "Montserrat:400",
                        "fields": {
                            "ccnumber": {
                                "selector": "#nmi-card-number-element",
                                "placeholder": GFNMIObj.ccCardNumberPlaceholder
                            },
                            "ccexp": {
                                "selector": "#nmi-card-expiry-element",
                                "placeholder": "MM / YY"
                            },
                            "cvv": {
                                "display": "show",
                                "selector": "#nmi-card-cvc-element",
                                "placeholder": GFNMIObj.ccCVVPlaceholder
                            },
                        },
                        'validationCallback' : function(field, status, message) {
                            if (status) {
                                var message = field + " is OK: " + message;
                                GFNMIObj.nmi_errors[field] = '';
                            } else {
                                GFNMIObj.nmi_errors[field] = message;
                            }
                            console.log(message);
                        },
                        "timeoutDuration" : 20000,
                        "timeoutCallback" : function () {
                            $( document ).trigger( 'nmiError', GFNMIObj.timeout_error );
                        },
                        "fieldsAvailableCallback" : function () {
                            console.log("Collect.js loaded the fields onto the form");
                        },
                        'callback' : function(response) {
                            GFNMIObj.responseHandler(response);
                        }
                    });

                    //setAccount(apiKey, gforms_nmi_frontend_strings.software_name, gforms_nmi_frontend_strings.software_version);

                }

            });

            // bind NMI functionality to submit event
            $( '#gform_' + this.formId ).submit( function( event ) {

				if (!feedActivated || $(this).data('gfnmisubmitting') || $('#gform_save_' + GFNMIObj.formId).val() == 1 || !GFNMIObj.isLastPage() || GFNMIObj.invisibleCaptchaPending() || GFNMIObj.recaptchav3Pending()) {
					return;
				} else {
					event.preventDefault();
					$(this).data('gfnmisubmitting', true);
					GFNMIObj.maybeAddSpinner();
				}

                var form = $(this);

				var validCardNumber = document.querySelector("#nmi-card-number-element .CollectJSValid") !== null;
				var validCardExpiry = document.querySelector("#nmi-card-expiry-element .CollectJSValid") !== null;
				var validCardCvv = document.querySelector("#nmi-card-cvc-element .CollectJSValid") !== null;

				if( !validCardNumber ) {
					GFNMIObj.nmi_errors.ccnumber = GFNMIObj.nmi_errors.ccnumber ? GFNMIObj.nmi_errors.ccnumber : 1;
				}

				if( !validCardNumber ) {
					GFNMIObj.nmi_errors.ccexp = GFNMIObj.nmi_errors.ccexp ? GFNMIObj.nmi_errors.ccexp : 1;
				}

				if( !validCardCvv ) {
					GFNMIObj.nmi_errors.cvv = GFNMIObj.nmi_errors.cvv ? GFNMIObj.nmi_errors.cvv : 1;
				}

				if( !validCardNumber || !validCardExpiry || !validCardCvv ) {
					GFNMIObj.form.find($('[name="collect_js_response"]').val(JSON.stringify(GFNMIObj.nmi_errors)));
					GFNMIObj.form.find($('[name="collect_js_token"]').val(''));
					form.submit();
				}

                CollectJS.startPaymentRequest();
                return false;

            } );

        };

        this.responseHandler = function (response) {
            var form = this.form;
            console.log(response);

            form.append(this.createElementFromHTML('<input type="hidden" name="collect_js_token" id="gf_collect_js_token" value="" />'));
            form.append(this.createElementFromHTML('<input type="hidden" name="collect_js_response" id="gf_collect_js_response" value="" />'));

            $('#gf_collect_js_token').val(response.token);
            $('#gf_collect_js_response').val(JSON.stringify(response));

            // submit the form
            setTimeout( function() {
                form.submit();
            }, 2000 );

        };

		this.isLastPage = function() {

            var targetPageInput = $( '#gform_target_page_number_' + this.formId );
            if( targetPageInput.length > 0 )
                return targetPageInput.val() == 0;

            return true;
        }

        this.isCreditCardOnPage = function() {

            var currentPage = this.getCurrentPageNumber();

            // if current page is false or no credit card page number, assume this is not a multi-page form
            if( ! this.ccPage || ! currentPage )
                return true;

            return this.ccPage == currentPage;
        }

        this.getCurrentPageNumber = function() {
            var currentPageInput = $( '#gform_source_page_number_' + this.formId );
            return currentPageInput.length > 0 ? currentPageInput.val() : false;
        }

		this.maybeAddSpinner = function() {
			if (this.isAjax)
				return;

			if (typeof gformAddSpinner === 'function') {
				gformAddSpinner(this.formId);
			} else {
				// Can be removed after min Gravity Forms version passes 2.1.3.2.
				var formId = this.formId;

				if (jQuery('#gform_ajax_spinner_' + formId).length == 0) {
					var spinnerUrl = gform.applyFilters('gform_spinner_url', gf_global.spinnerUrl, formId),
						$spinnerTarget = gform.applyFilters('gform_spinner_target_elem', jQuery('#gform_submit_button_' + formId + ', #gform_wrapper_' + formId + ' .gform_next_button, #gform_send_resume_link_button_' + formId), formId);
					$spinnerTarget.after('<img id="gform_ajax_spinner_' + formId + '"  class="gform_ajax_spinner" src="' + spinnerUrl + '" alt="" />');
				}
			}
		};

        this.createElementFromHTML = function(htmlString) {
            var div = document.createElement('div');
            div.innerHTML = htmlString.trim();

            // Change this to div.childNodes to support multiple top-level nodes
            return div.firstChild;
        };

		this.invisibleCaptchaPending = function () {
            var form = this.form,
                reCaptcha = form.find('.ginput_recaptcha');

            if (!reCaptcha.length || reCaptcha.data('size') !== 'invisible') {
                return false;
            }

            var reCaptchaResponse = reCaptcha.find('.g-recaptcha-response');

            return !(reCaptchaResponse.length && reCaptchaResponse.val());
        };

        this.recaptchav3Pending = function () {
            const form = this.form;
            const recaptchaField = form.find('.ginput_recaptchav3');
            if (!recaptchaField.length) {
                return false;
            }

            const recaptchaResponse = recaptchaField.find('.gfield_recaptcha_response');

            return !(recaptchaResponse && recaptchaResponse.val());
        };

        this.init();

    }

})(jQuery);