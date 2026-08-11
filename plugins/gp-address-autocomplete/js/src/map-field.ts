/* eslint-disable jsdoc/no-undefined-types */

import { ensureGPAAInittedAndExec } from './frontend';

const { jQuery: $ } = window;

interface GPAAFieldsFilledPlace {
	geometry: {
		location: {
			lat: () => number;
			lng: () => number;
		};
	};
}

interface Coords {
	lat: number;
	lng: number;
}

interface GPAddressAutocompleteMapFieldOpts {
	formId: number;
	fieldId: number;
	addressFieldId: number;
	prepopulateCoords?: Coords;
}

class GP_Address_Autocomplete_Map_Field {
	formId: number;

	fieldId: number;

	addressFieldId: number;

	prepopulateCoords?: Coords;

	mapContainer: HTMLElement;

	map: google.maps.Map | undefined;

	marker: google.maps.marker.AdvancedMarkerElement | undefined;

	zoom = 15;

	constructor({
		formId,
		fieldId,
		addressFieldId,
		prepopulateCoords,
	}: GPAddressAutocompleteMapFieldOpts) {
		this.formId = formId;
		this.fieldId = fieldId;
		this.addressFieldId = addressFieldId;
		this.prepopulateCoords =
			this.getPostedAddressFieldCoords() || prepopulateCoords;

		this.mapContainer = document.querySelector(
			`#gpaa_map_container_${this.fieldId}`
		)!;

		// The google maps sdk script will not be loaded unless the conncted address field has autocomplete enabled.
		// This is usually because the attached addressFieldId does not have the "Enable Google Address Autocomplete" option enabled.
		// We do check before rendering the map div if the autocomplete field is checked so this *shouldn't* happen, but just in case...
		if (window.google === undefined) {
			return;
		}

		this.setupListeners();

		// wait to init the map as the Google Maps API is loaded asynchronously
		ensureGPAAInittedAndExec(() => {
			this.initMap();
		});
	}

	// Get coords from an address field that is on a previous page of a multi-page form
	// These coords will be stored in a hidden input field
	getPostedAddressFieldCoords(): Coords | undefined {
		const $form = document.querySelector<HTMLFormElement>(
			`#gform_${this.formId}`
		);
		const $input = $form?.querySelector<HTMLInputElement>(
			`input[name="gpaa_coords_${this.addressFieldId}"]`
		);

		if (!$input?.value) {
			return;
		}

		let prepopulateCoords: Coords | undefined;

		try {
			prepopulateCoords = JSON.parse($input.value);
		} catch (err) {
			// eslint-disable-next-line no-console
			console.debug(err);

			return;
		}

		// Convert lat and lng to numbers if they're strings.
		if (typeof prepopulateCoords?.lat === 'string') {
			prepopulateCoords.lat = Number(prepopulateCoords.lat);
		}

		if (typeof prepopulateCoords?.lng === 'string') {
			prepopulateCoords.lng = Number(prepopulateCoords.lng);
		}

		if (!prepopulateCoords?.lat || !prepopulateCoords?.lng) {
			return;
		}

		return prepopulateCoords;
	}

	setupListeners() {
		window.gform.addAction(
			'gpaa_fields_filled',
			(
				place: GPAAFieldsFilledPlace,
				instance: null,
				formId: number,
				fieldId: number
			) => {
				if (formId !== this.formId || fieldId !== this.addressFieldId) {
					return;
				}

				this.setMarker({
					lat: place.geometry.location.lat(),
					lng: place.geometry.location.lng(),
				});
			}
		);
	}

	initMap() {
		let opts: google.maps.MapOptions = {
			zoom: this.zoom,
			center: {
				lng: -73.95,
				lat: 40.8,
			},
			fullscreenControl: false,
			mapTypeControl: false,
			streetViewControl: false,
			zoomControl: false,
			mapId: `gpaa_map_${this.formId}_${this.fieldId}`, // Map ID is required for advanced markers.
		};

		if (this.prepopulateCoords) {
			opts.center = this.prepopulateCoords;
		}

		/**
		 * Filter the options used to initialize the map.
		 *
		 * @param {google.maps.MapOptions} options Contains the map configuration options. All available options are documented here: https://developers.google.com/maps/documentation/javascript/reference/map#MapOptions
		 * @param {number}                 formId  The current form ID.
		 * @param {number}                 fieldId The current field ID.
		 *
		 * @since 1.2.1
		 */
		opts = window.gform.applyFilters(
			'gpaa_map_options',
			opts,
			this.formId,
			this.fieldId
		);

		this.map = new google.maps.Map(this.mapContainer, opts);

		this.map?.setZoom(opts.zoom || this.zoom);

		if (this.prepopulateCoords) {
			this.setMarker(this.prepopulateCoords);
		}

		/**
		 * Action that fires once right after the map is initialized.
		 *
		 * @param {google.maps.Map} map     The map object.
		 * @param {number}          formId  The current form ID.
		 * @param {number}          fieldId The current field ID.
		 */
		window.gform.doAction(
			'gpaa_map_initialized',
			this.map,
			this.formId,
			this.fieldId
		);
	}

	setMarker(position: Coords) {
		// remove marker from the map if it exists
		if (this.marker) {
			this.marker.map = null;
		}

		let opts: google.maps.marker.AdvancedMarkerElementOptions = {
			position,
			map: this.map,
		};

		/**
		 * Filter the options used to add a marker to the map.
		 *
		 * @param {google.maps.MarkerOptions} options Contains the marker options. All available options are documented here: https://developers-dot-devsite-v2-prod.appspot.com/maps/documentation/javascript/reference/marker#MarkerOptions
		 * @param {number}                    formId  The current form ID.
		 * @param {number}                    fieldId The current field ID.
		 *
		 * @since 1.2.1
		 */
		opts = window.gform.applyFilters(
			'gpaa_marker_options',
			opts,
			this.formId,
			this.fieldId
		);

		this.marker = new google.maps.marker.AdvancedMarkerElement(opts);

		const markerPos = this.marker.position;

		if (markerPos) {
			this.map?.panTo(markerPos);
		}

		/**
		 * Action that fires after a marker is added to the map.
		 *
		 * @param params.formId         ID of the form the map field is in.
		 * @param params.fieldId        ID of the map field the marker was added to.
		 * @param params.addressFieldId ID of the address field the map field is attached to.
		 * @param params.map            The map object that the marker was added to.
		 * @param params.marker         The marker object that was addded to the map.
		 *
		 * @since 1.2.3
		 */
		window.gform.doAction('gpaa_marker_set', {
			formId: this.formId,
			fieldId: this.fieldId,
			addressFieldId: this.addressFieldId,
			map: this.map,
			marker: this.marker,
		});
	}
}

window.GP_Address_Autocomplete_Map_Field = GP_Address_Autocomplete_Map_Field;

// Make this a module to avoid TypeScript error with block-scoped variables since we're not importing anything
export {};
