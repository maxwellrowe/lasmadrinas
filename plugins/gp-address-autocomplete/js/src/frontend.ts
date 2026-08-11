/// <reference types="google.maps" />
/* eslint-disable jsdoc/no-undefined-types */

import deburr from 'lodash/deburr';
import Promise from 'promise-polyfill';
import scrollparent from 'scrollparent';

const { jQuery: $ } = window;

/* Polyfills */
if (!Object.entries) {
	Object.entries = function(obj: any) {
		const ownProps = Object.keys(obj);

		let i = ownProps.length;
		const resArray = new Array(i); // preallocate the Array

		while (i--) resArray[i] = [ownProps[i], obj[ownProps[i]]];

		return resArray;
	};
}

// Callback to be fired by the Google <script> when the Places API and Maps JavaScript API are ready.
window.gpaaInit = () => {
	if (window.gpaaReady) {
		return;
	}

	const event = new CustomEvent('gpaa_api_ready');
	document.dispatchEvent(event);

	window.gpaaReady = true;
};

document.addEventListener('gpaa_api_ready', () => {
	// eslint-disable-next-line no-console
	console.debug('GP Address Autocomplete: Google Maps API ready.');
});

export function ensureGPAAInittedAndExec(cb: () => void) {
	if (window.gpaaReady) {
		cb();
		return;
	}

	document.addEventListener('gpaa_api_ready', cb);

	// If google.maps.places is already defined, fire gpaaInit() manually in case something else is loading the API.
	if (window?.google?.maps?.places) {
		// eslint-disable-next-line no-console
		console.debug(
			'GP Address Autocomplete: Google Maps API already loaded.'
		);

		window.gpaaInit();
	}
}

interface GPAAParsedAdrMicroformat {
	'post-office-box'?: string;
	'extended-address'?: string;
	'street-address'?: string;
	locality: string;
	region?: string;
	'postal-code'?: string;
	'country-name': string;
}

interface GPAAInputsSelectors {
	autocomplete: string; // Defaults to be the same as Address Line 1
	address1: string;
	address2: string | undefined;
	postalCode: string | undefined;
	city: string | undefined;
	stateProvince: string | undefined;
	country: string | undefined;
}

interface GPAAInputs {
	autocomplete: HTMLInputElement; // Defaults to be the same as Address Line 1
	address1: HTMLInputElement;
	address2: HTMLInputElement | undefined;
	postalCode: HTMLInputElement | undefined;
	city: HTMLInputElement | undefined;
	stateProvince: HTMLInputElement | undefined;
	country: HTMLSelectElement | undefined;
}

class GP_Address_Autocomplete {
	public autocomplete: google.maps.places.Autocomplete | undefined;
	public autocompleteListener: google.maps.MapsEventListener | undefined;
	public pacContainer: HTMLElement | undefined;
	public pacContainerStyleObserver: MutationObserver | undefined;

	public formId: number;
	public fieldId: number;
	public inputSelectors: GPAAInputsSelectors;
	public inputs: GPAAInputs;
	public addressType: string;
	public interval: NodeJS.Timer | undefined;

	/**
	 * Key/value store of any GPAA instances on the page. Useful for re-initializing using existing instances and
	 * preventing pac-containers from doubling-up in use-cases such as GPNF when re-opening the modal multiple times.
	 */
	public static instances: {
		[formIdFieldId: string]: GP_Address_Autocomplete;
	} = {};

	constructor(
		opts: Pick<
			GP_Address_Autocomplete,
			'formId' | 'fieldId' | 'addressType'
		> & { inputSelectors: GPAAInputsSelectors }
	) {
		this.formId = opts.formId;
		this.fieldId = opts.fieldId;
		this.inputSelectors = opts.inputSelectors;
		this.inputs = this.getInputEls(this.inputSelectors); // we also get the inputEls in init()
		this.addressType = opts.addressType;

		/**
		 * Bail early if no autocomplete input is present.
		 *
		 * Autocomplete relies on the autocomplete input. If this field has been disabled by the user, then there
		 * is not anything to attach the autocomplete to functionality to.
		 *
		 * We'll go ahead and try to initialize in 1 second in case the input takes some time to show up.
		 */
		if (!this.inputs.autocomplete) {
			setTimeout(() => this.init(), 1000);
			return;
		}

		ensureGPAAInittedAndExec(() => {
			this.init();
		});
	}

	/**
	 * Get the pacContainer from the google.maps.places.Autocomplete instance.
	 *
	 * Credit: https://stackoverflow.com/a/49693605
	 */
	getAutocompletePacContainer() {
		if (!this.autocomplete) {
			return null;
		}

		// @ts-ignore
		const place: Object = this.autocomplete?.gm_accessors_?.place;

		if (!place) {
			return null;
		}

		const placeKey = Object.keys(place).find(
			(value) =>
				// @ts-ignore
				typeof place[value] === 'object' &&
				// @ts-ignore
				place[value].hasOwnProperty('gm_accessors_')
		);

		// @ts-ignore
		const input = place[placeKey].gm_accessors_.input[placeKey];

		const inputKey = Object.keys(input).find(
			(value) =>
				input[value].classList &&
				input[value].classList.contains('pac-container')
		);

		return input[inputKey];
	}

	init() {
		if (!this.inputs.autocomplete) {
			this.inputs = this.getInputEls(this.inputSelectors);

			if (!this.inputs.autocomplete) {
				// eslint-disable-next-line no-console
				console.debug(
					'GP Address Autocomplete: No input found yet. It could be due to it being in a modal that has yet to initialize.'
				);

				return;
			}
		}

		/* Re-use existing instance if its present. */
		if (
			typeof GP_Address_Autocomplete.instances[
				`${this.formId}-${this.fieldId}`
			] !== 'undefined'
		) {
			GP_Address_Autocomplete.instances[
				`${this.formId}-${this.fieldId}`
			].initPac();
			return;
		}

		GP_Address_Autocomplete.instances[
			`${this.formId}-${this.fieldId}`
		] = this;

		this.initPac();
		this.bindGPPAListener();

		/**
		 * Action that fires after Address Autocomplete has been initialized on the frontend.
		 *
		 * @param {GP_Address_Autocomplete} instance Current instance of the class.
		 * @param {number}                  formId   The current form ID.
		 * @param {number}                  fieldId  The current field ID.
		 *
		 * @since 1.0
		 */
		window.gform.doAction('gpaa_init', this, this.formId, this.fieldId);
	}

	/**
	 * Google Places Autocomplete attempts to add autocomplete="off" by default which does not work in Chrome
	 * anymore.
	 *
	 * To take this a step further, we also disable autocomplete for any inputs found in the field so when a user
	 * goes to subsequent inputs like Address Line 2, browsers don't try to autocomplete.
	 *
	 * If the browser is Chrome, change the autocomplete attribute to "password"
	 */
	preventBrowserAutocomplete() {
		/**
		 * Filter whether browser autocomplete should be prevented. Defaults to true.
		 *
		 * @param {boolean}                 preventBrowserAutocomplete Whether browser autocomplete should be prevented.
		 * @param {GP_Address_Autocomplete} instance                   Current instance of the class.
		 * @param {number}                  formId                     The current form ID.
		 * @param {number}                  fieldId                    The current field ID.
		 *
		 * @since 1.2.13
		 */
		if (
			!window.gform.applyFilters(
				'gpaa_prevent_browser_autocomplete',
				true,
				this,
				this.formId,
				this.fieldId
			)
		) {
			this.inputs.autocomplete.removeAttribute('autocomplete');

			return;
		}

		for (const [inputName, input] of Object.entries(this.inputs)) {
			let autocompleteVal = 'new-password';

			/**
			 * Filters the value for an input's autocomplete attribute. (Defaults to "off")
			 * Reference: https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete
			 *
			 * @param {string}                  autocompleteVal Value for the autocomplete attribute.
			 * @param {HTMLInputElement}        input           The input element.
			 * @param {string}                  inputName       The name of the input element.
			 * @param {GP_Address_Autocomplete} instance        Current instance of the class.
			 * @param {number}                  formId          The current form ID.
			 * @param {number}                  fieldId         The current field ID.
			 */
			autocompleteVal = window.gform.applyFilters(
				'gpaa_field_autocomplete_value',
				autocompleteVal,
				input,
				inputName,
				this,
				this.formId,
				this.fieldId
			);

			input?.setAttribute('autocomplete', autocompleteVal);
		}
	}

	/**
	 * Bind a listener to gppa_updated_batch_fields jQuery event that will reinitialize Address Autocomplete when
	 * the Address field markup is replaced.
	 */
	bindGPPAListener() {
		$(document).on(
			'gppa_updated_batch_fields',
			(event, formId, updatedFieldIds) => {
				if (parseInt(formId) !== this.formId) {
					return;
				}

				updatedFieldIds = updatedFieldIds.map((fieldId: string) =>
					parseInt(fieldId)
				);

				if (updatedFieldIds.indexOf(this.fieldId) === -1) {
					return;
				}

				this.init();
			}
		);
	}

	getInputEls(selectors: GPAAInputsSelectors): GPAAInputs {
		const inputs: { [inputName: string]: any } = {};

		for (const [inputName, selector] of Object.entries(selectors)) {
			inputs[inputName] = document.querySelector(selector);
		}

		return inputs as GPAAInputs;
	}

	get autocompleteOptions(): google.maps.places.AutocompleteOptions {
		const {
			allowed_countries: allowedCountries,
		} = window.GP_ADDRESS_AUTOCOMPLETE_CONSTANTS;

		const autocompleteOptions: google.maps.places.AutocompleteOptions = {
			componentRestrictions: undefined,
			fields: [
				'address_components', // Individual components of the address to be populated into address field inputs
				'formatted_address', // String representation of the address
				'geometry', // Coordinates
				'adr_address', // Similar to formatted_address but uses ADR microformat which we can use to extract components.
			],
			types: ['address'],
		};

		// Restrict country to Canada if Address Field type is configured to United States
		if (this.addressType === 'us') {
			autocompleteOptions.componentRestrictions = {
				country: ['us'],
			};
			// Restrict country to Canada if Address Field type is configured to Canadian
		} else if (this.addressType === 'canadian') {
			autocompleteOptions.componentRestrictions = {
				country: ['ca'],
			};
			// Restrict to countries set in settings
		} else if (allowedCountries?.length) {
			autocompleteOptions.componentRestrictions = {
				country: allowedCountries,
			};
		}

		/**
		 * Filter to change the autocomplete options used to initialize Google Places API Autocomplete.
		 *
		 * @param {google.maps.places.AutocompleteOptions} autocompleteOptions Options used to initialize Places API Autocomplete.
		 * @param {GP_Address_Autocomplete}                instance            Current instance of the class.
		 * @param {number}                                 formId              The current form ID.
		 * @param {number}                                 fieldId             The current field ID.
		 *
		 * @return {google.maps.places.AutocompleteOptions} Filtered options used to initialized Places API Autocomplete.
		 *
		 * @since 1.0
		 */
		return window.gform.applyFilters(
			'gpaa_autocomplete_options',
			autocompleteOptions,
			this,
			this.formId,
			this.fieldId
		);
	}

	initPac = (): void => {
		// Remove existing pac-container since a new one will be created
		this.pacContainer?.remove();

		// Get fresh input elements in case this method is called in the future and the input els were replaced (GPPA).
		this.inputs = this.getInputEls(this.inputSelectors);

		if (this.autocomplete) {
			google.maps.event.clearInstanceListeners(this.autocomplete);
		}

		if (this.autocompleteListener) {
			google.maps.event.removeListener(this.autocompleteListener);
		}

		// Do not add autocomplete to read-only fields as focusing the input will still open the autocomplete dropdown.
		if (this.inputs.autocomplete.readOnly) {
			return;
		}

		this.waitForPacContainer()
			.then((pacContainer) => {
				this.pacContainer = pacContainer;
				this.pacContainer?.classList.add(
					'pac-container-gpaa',
					'gform-theme__no-reset--el',
					'gform-theme__no-reset--children'
				);

				/**
				 * Filter to enable/disable the fixed positioner for the PAC container. By default, it's enabled to
				 * improve positioning when the PAC container is in a fixed div such as GPNF Tingle, but there are
				 * situations where it works best disabled.
				 *
				 * @param {boolean}                 useFixedPositioner Whether fixed positioning should be used for the PAC container.
				 * @param {GP_Address_Autocomplete} instance           Current instance of the class.
				 * @param {number}                  formId             The current form ID.
				 * @param {number}                  fieldId            The current field ID.
				 *
				 * @return {google.maps.places.AutocompleteOptions} Filtered options used to initialized Places API Autocomplete.
				 *
				 * @since 1.2.8
				 */
				if (
					window.gform.applyFilters(
						'gpaa_use_fixed_positioner',
						true,
						this,
						this.formId,
						this.fieldId
					)
				) {
					this.addFixedPositioner();
				}

				this.preventBrowserAutocomplete();
			})
			.catch((reason) => {
				// eslint-disable-next-line no-console
				console.error(
					'Unable to initialize GP Address Autocomplete.',
					reason
				);
			});

		this.autocomplete = new google.maps.places.Autocomplete(
			this.inputs.autocomplete,
			this.autocompleteOptions
		);

		// Prevent enter from submitting the form when pressing enter in auto complete.
		this.inputs.autocomplete.addEventListener(
			'keydown',
			(e: KeyboardEvent) => {
				let pacContainerVisible = false;

				document
					.querySelectorAll('.pac-container')
					.forEach((container) => {
						if (
							!(
								window.getComputedStyle(container).display ===
								'none'
							)
						) {
							pacContainerVisible = true;
						}
					});

				if (
					(e.code === 'Enter' || e.keyCode === 13) &&
					pacContainerVisible
				) {
					e.preventDefault();
				}
			}
		);

		// When the user selects an address, populate the address inputs in the field.
		this.autocompleteListener = this.autocomplete.addListener(
			'place_changed',
			this.fillFields
		);

		// Store coordinates in hidden input. Utilize action so this can be unhooked if desired.
		window.gform.addAction(
			'gpaa_fields_filled',
			(
				place: google.maps.places.PlaceResult,
				instance: GP_Address_Autocomplete
			) => {
				if (instance !== this) {
					return;
				}

				this.fillCoordinatesMetaInput(place);
			}
		);
	};

	/**
	 * Get the pacContainer from the google.maps.places.Autocomplete instance. We leverage an interval to wait
	 * for the pacContainer to be available. We'll wait up to 5 seconds before rejecting the promise.
	 *
	 * @return {Promise<HTMLElement>} Promise that resolves with the pacContainer.
	 */
	waitForPacContainer = (): Promise<HTMLElement> => {
		let interval: NodeJS.Timer | undefined;

		return new Promise(
			(resolve: (value: any) => void, reject: (reason?: any) => void) => {
				// Try every 25ms to get the pac-container off of the autocomplete instance for up to 5 seconds.
				interval = setInterval(() => {
					this.pacContainer = this.getAutocompletePacContainer();

					if (this.pacContainer) {
						clearInterval(interval);
						resolve(this.pacContainer);
					}
				}, 25);

				setTimeout(() => {
					clearInterval(interval);
					reject(
						'GP Address Autocomplete: Could not find the pac-container.'
					);
				}, 5000);
			}
		);
	};

	/**
	 * Google PAC does not work well if the autocomplete input is in any offset parent outside of the root document.
	 *
	 * This method will move the pacContainer to the offset parent (Tingle in the case of GPNF) and adds
	 * the following:
	 *  * Mutation Observer to watch when the style of the PAC container changes and auto-positions when it does
	 *  * Binds to the scroll event of the scroll parent and document and auto repositions when they scroll
	 */
	addFixedPositioner = (): void => {
		let scrollParent = scrollparent(this.inputs.autocomplete)!;

		// Force the scroll parent to be the Tingle (GPNF) modal if the autocomplete input is in it.
		if ($(this.inputs.autocomplete).closest('.tingle-modal').length) {
			scrollParent = $(this.inputs.autocomplete).closest(
				'.tingle-modal'
			)[0];
		}

		$(this.pacContainer!).appendTo(scrollParent);

		if (this.pacContainerStyleObserver) {
			this.pacContainerStyleObserver.disconnect();
		}

		/**
		 * Flag to lockout the mutation observer from repositioning when the page/scrollparent is scrolled. Without
		 * this, an infinite loop will ensue.
		 */
		let mutationObserverLockout: boolean = false;

		this.pacContainerStyleObserver = new MutationObserver((mutations) => {
			if (mutationObserverLockout) {
				return;
			}

			/* Without this, the position can sometimes be switched back to absolute by PAC */
			requestAnimationFrame(() => {
				this.positionPacContainer();
			});
		});

		scrollParent.addEventListener('scroll', () => {
			mutationObserverLockout = true;
			this.positionPacContainer();
			mutationObserverLockout = false;
		});

		document.addEventListener('scroll', () => {
			mutationObserverLockout = true;
			this.positionPacContainer();
			mutationObserverLockout = false;
		});

		this.pacContainerStyleObserver.observe(this.pacContainer as Node, {
			attributes: true,
			attributeFilter: ['style'],
		});

		// Reposition on scrollParent resize
		const pacContainerResizeObserver = new ResizeObserver(() =>
			this.positionPacContainer()
		);
		pacContainerResizeObserver.observe(scrollParent);
	};

	/**
	 * Position the pac-container to be directly below the autocomplete input.
	 * The default position behavior does not work if the autocomplete dropdown is in a modal.
	 */
	positionPacContainer = (): void => {
		if (!this.pacContainer) {
			return;
		}

		const inputBoundingRect = this.inputs.autocomplete.getBoundingClientRect();

		if (
			!this.pacContainer?.classList.contains(
				'pac-container-gpaa-position-managed'
			)
		) {
			this.pacContainer?.classList.add(
				'pac-container-gpaa-position-managed'
			);
		}

		/* Disconnection MutationObserver since we're about to modify the style and it'd cause an infinite loop */
		this.pacContainerStyleObserver?.disconnect();

		this.pacContainer.style.top =
			inputBoundingRect!.y + inputBoundingRect.height! + 'px';
		this.pacContainer.style.left = inputBoundingRect!.x + 'px';

		this.pacContainerStyleObserver?.observe(this.pacContainer as Node, {
			attributes: true,
			attributeFilter: ['style'],
		});
	};

	fillFields = (): void => {
		if (!this.autocomplete) {
			// eslint-disable-next-line no-console
			console.warn('GP Address Autocomplete: Google API not ready.');
			return;
		}

		// Get the place details from the autocomplete object.
		const place: google.maps.places.PlaceResult = this.autocomplete.getPlace();
		const adrParsed = this.parseAdrAddressHTML(place.adr_address!);

		// Use parsed adr_address for most of the components as it respects the local formatting of addresses much
		// better than trying to piece together the individual address_components which differ from region-to-region.
		let values = {
			address1: adrParsed['street-address'],
			address2: adrParsed['extended-address'],
			postcode: adrParsed['postal-code'],
			city: adrParsed.locality,
			stateProvince: adrParsed.region,
			country: '',
			autocomplete: undefined, // Intended to be populated using the gpaa_values JS filter.
		};

		// Set the country now (as ISO Alpha-2), so we can do modifications in the loop below depending on the country.
		let countryAlpha2;

		for (const component of place.address_components as google.maps.GeocoderAddressComponent[]) {
			if (component.types[0] !== 'country') {
				continue;
			}

			countryAlpha2 = component.short_name;
		}

		// Augment the parsed ADR address with the address components as some of the items in the ADR address come through
		// as the short_name instead of long_name.
		for (const component of place.address_components as google.maps.GeocoderAddressComponent[]) {
			// filter out `political` from types since we don't care about that type for auto-fill purposes.
			const componentType = component.types.filter(
				(type) => type !== 'political'
			)[0];

			switch (componentType) {
				// Use locality as city if the city is not present in the ADR. This is necessary for Singapore.
				case 'locality':
					if (!values.city) {
						values.city = component.long_name;
					}
					break;

				case 'administrative_area_level_2':
				case 'administrative_area_level_1':
					if (component.short_name === values.stateProvince) {
						values.stateProvince = component.long_name;

						/*
						 * Try various formats of the State/Province as the default Gravity Forms province selector for
						 * Canada is ASCII / basic Latin only.
						 */
						if (
							this.inputs.stateProvince?.tagName.toLowerCase() ===
							'select'
						) {
							if (
								!this.inputs.stateProvince.querySelector(
									`option[value="${values.stateProvince}"]`
								)
							) {
								values.stateProvince = deburr(
									values.stateProvince
								);
							}
						}
					} else if (
						componentType === 'administrative_area_level_1' &&
						!values.stateProvince &&
						countryAlpha2 &&
						['NL', 'NZ', 'FR'].indexOf(countryAlpha2) !== -1
					) {
						/*
						 * If the stateProvince wasn't pulled from the ADR, use the admin area level 1 if an address
						 * in the Netherlands, and New Zealand.
						 *
						 * We only apply this logic to a specific set of countries as it can return odd results with
						 * other cities such as London or Berlin.
						 */
						values.stateProvince = component.long_name;
					} else if (
						componentType === 'administrative_area_level_2' &&
						!values.stateProvince &&
						countryAlpha2 &&
						['GB'].indexOf(countryAlpha2) !== -1
					) {
						/*
						 * If the stateProvince wasn't pulled from the ADR, use the admin area level 2 if an address
						 * in the England.
						 *
						 * We only apply this logic to a specific set of countries as it can return odd results with
						 * other cities such as Auckland or Amsterdam.
						 */
						values.stateProvince = component.long_name;
					}
					break;

				case 'country':
					/*
					 * The long_name of the country may not always match up with what Gravity Forms is outputting.
					 * Reasons include the browser being set to a language that isn't the same as the website.
					 *
					 * To work around this, we take the short_name (abbreviation) of the country and find the
					 * long_name in Gravity Forms' countries array.
					 */
					const {
						countries,
					} = window.GP_ADDRESS_AUTOCOMPLETE_CONSTANTS;

					/**
					 * Depending on the website setup, the values of the select can be abbreviations rather than the
					 * long name.
					 *
					 * Go through and try populating with each.
					 */
					const googleShortName = component.short_name;
					const googleLongName = component.long_name;
					const gravityFormsLongName =
						countries?.[component.short_name];

					if (this.inputs.country) {
						if (
							this.inputs.country.querySelector(
								`option[value="${googleShortName}"]`
							)
						) {
							values.country = googleShortName;
						} else if (
							this.inputs.country.querySelector(
								`option[value="${googleLongName}"]`
							)
						) {
							values.country = googleLongName;
						} else if (
							this.inputs.country.querySelector(
								`option[value="${gravityFormsLongName}"]`
							)
						) {
							values.country = gravityFormsLongName;
						}
					}

					if (!values.country) {
						values.country = gravityFormsLongName;
					}

					break;
			}
		}

		/**
		 * Filter the formatted values after a place has been selected. Use this to change the format of individual
		 * values such as Address 1, City, etc.
		 *
		 * @param {Object}                         values   The values to be populated into address inputs.
		 * @param {google.maps.places.PlaceResult} place    The place selected.
		 * @param {GP_Address_Autocomplete}        instance Current instance of the class.
		 * @param {number}                         formId   The current form ID.
		 * @param {number}                         fieldId  The current field ID.
		 *
		 * @return {Object} The filtered values to be populated into address inputs.
		 *
		 * @since 1.0
		 */
		values = window.gform.applyFilters(
			'gpaa_values',
			values,
			place,
			this,
			this.formId,
			this.fieldId
		);

		if (this.inputs.autocomplete) {
			this.inputs.autocomplete.value = values.autocomplete ?? '';
			this.triggerChange(this.inputs.autocomplete);
		}

		if (this.inputs.address1) {
			this.inputs.address1.value = values.address1 ?? '';
			this.triggerChange(this.inputs.address1);
		}

		if (this.inputs.address2) {
			this.inputs.address2.value = values.address2 ?? '';
			this.triggerChange(this.inputs.address2);
		}

		if (this.inputs.city) {
			this.inputs.city.value = values.city ?? '';
			this.triggerChange(this.inputs.city);
		}

		if (this.inputs.stateProvince) {
			this.inputs.stateProvince.value = values.stateProvince ?? '';
			this.triggerChange(this.inputs.stateProvince);
		}

		if (this.inputs.postalCode) {
			this.inputs.postalCode.value = values.postcode ?? '';
			this.triggerChange(this.inputs.postalCode);
		}

		if (this.inputs.country) {
			this.inputs.country.value = values.country ?? '';
			this.triggerChange(this.inputs.country);
		}

		/**
		 * Action that fires after a place is selected and Address Autocomplete has filled the fields.
		 *
		 * @param {google.maps.places.PlaceResult} place    The place selected.
		 * @param {GP_Address_Autocomplete}        instance Current instance of the class.
		 * @param {number}                         formId   The current form ID.
		 * @param {number}                         fieldId  The current field ID.
		 *
		 * @since 1.0
		 */
		window.gform.doAction(
			'gpaa_fields_filled',
			place,
			this,
			this.formId,
			this.fieldId
		);
	};

	fillCoordinatesMetaInput(place: google.maps.places.PlaceResult) {
		const $form = document.querySelector<HTMLFormElement>(
			`#gform_${this.formId}`
		);
		const $input = $form?.querySelector<HTMLInputElement>(
			`input[name="gpaa_place_${this.fieldId}"]`
		);

		if (!$input) {
			return;
		}

		if (place) {
			$input.value = JSON.stringify(place);
		} else {
			$input.value = '';
		}
	}

	triggerChange(input: HTMLInputElement | HTMLSelectElement): void {
		input.dispatchEvent(new Event('change'));

		$(input).trigger('change');
	}

	parseAdrAddressHTML(html: string): GPAAParsedAdrMicroformat {
		const parsedAddress: Partial<GPAAParsedAdrMicroformat> = {};
		const adrPattern = /<(?:span|div) class="(post-office-box|extended-address|street-address|locality|region|postal-code|country-name)">(.*?)<\/(?:span|div)>/gm;

		let m;

		// I would prefer to use String.matchAll, but the Can I Use stat is 92% and the polyfills take the frontend JS
		// from 3-4KB to 20KB at a minimum.
		while ((m = adrPattern.exec(html)) !== null) {
			// This is necessary to avoid infinite loops with zero-width matches
			if (m.index === adrPattern.lastIndex) {
				adrPattern.lastIndex++;
			}

			// The result can be accessed through the `m`-variable.
			parsedAddress[
				m[1] as keyof GPAAParsedAdrMicroformat
			] = this.decodeHTMLEntities(m[2]);
		}

		return parsedAddress as GPAAParsedAdrMicroformat;
	}

	/* eslint-disable jsdoc/check-tag-names */
	/**
	 * @param  html
	 * @credit https://stackoverflow.com/a/1395954
	 */
	decodeHTMLEntities(html: string): string {
		const textarea = document.createElement('textarea');
		textarea.innerHTML = html;
		const value = textarea.value;
		textarea.remove();

		return value;
	}
}

// @ts-ignore
window.GP_Address_Autocomplete = GP_Address_Autocomplete;

// Make this a module to avoid TypeScript error with block-scoped variables since we're not importing anything
export {};
