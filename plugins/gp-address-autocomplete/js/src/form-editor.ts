const { __ } = window.wp.i18n;

interface MapFieldState {
	gpaaMapAttachedAddressField?: string;
}

type GPAddressAutocompleteState = { [fieldId: string]: MapFieldState };

interface Form {
	fields: Array<FormField>;
}

type FieldType = 'map' | 'address';

interface FormField {
	id: number;
	type: FieldType;
	label: string;
	gpaaEnable?: boolean;
	gpaaMapAttachedAddressField?: number;
}

jQuery(function($) {
	for (const fieldType in window.fieldSettings) {
		if (fieldType !== 'address') {
			continue;
		}

		if (window.fieldSettings.hasOwnProperty(fieldType)) {
			window.fieldSettings[fieldType] += ', .gpaa-field-setting';
		}
	}

	$(document).on('gform_load_field_settings', function(event, field, form) {
		$('#gpaa-enable').prop('checked', !!field.gpaaEnable);

		updateAttachedAddressFieldSettingDropdown();

		if (field.gpaaMapAttachedAddressField) {
			$('#gpaa-map-attached-address-field').val(
				field.gpaaMapAttachedAddressField
			);
		}
	});

	$(document).on('gform_field_added', (event, form, field) => {
		if (field.type === 'map') {
			updateMapFieldContents();
		}
	});

	$('#gpaa-map-attached-address-field').on('change', (event) => {
		window.SetFieldProperty(
			'gpaaMapAttachedAddressField',
			// @ts-ignore
			event?.target?.value
		);

		updateMapFieldContents();
	});

	$('#gpaa-enable').on('change', () => {
		updateMapFieldContents();
	});

	function updateAttachedAddressFieldSettingDropdown() {
		const addressFields = window.form.fields.filter(
			({ type }) => type === 'address'
		);

		$('#gpaa-map-attached-address-field').html(
			`<option value="">Select a Field</option>
			${addressFields.map(({ id, label }) => {
				return `<option value="${id}">${label}</option>`;
			})}
			`
		);
	}

	function updateMapFieldContents() {
		const renderWarn = ({
			heading,
			content,
			id,
		}: {
			heading: string;
			content: string;
			id: number;
		}) => {
			$(`#gpaa_admin_map_field_message_container_${id}`)
				.css('background-image', 'none')
				.html(
					`<div class="gpaa_map_field_centered_text">
					<p class="gpaa_map_field_placeholder_text">
						<strong style="color: #ca4a1f;">
							${heading}
						</strong>
						<br>
						${content}
					</p>
				</div>`
				);
		};

		const mapFields = window.form.fields.filter(
			({ type }) => type === 'map'
		);

		mapFields.forEach(({ id, gpaaMapAttachedAddressField }) => {
			// eslint-disable-next-line @wordpress/no-unused-vars-before-return
			const attachedAddressField = window.form.fields.find(
				({ id }) => id === Number(gpaaMapAttachedAddressField)
			);

			if (!gpaaMapAttachedAddressField) {
				renderWarn({
					heading: __(
						'CONFIGURATION REQUIRED',
						'gp-address-autocomplete'
					),
					content: __(
						'Use the "Attached Address Field" setting to choose the address field you would like to use to populate the map.',
						'gp-address-autocomplete'
					),
					id,
				});

				return;
			} else if (!attachedAddressField?.gpaaEnable) {
				renderWarn({
					heading: __(
						'CONFIGURATION REQUIRED',
						'gp-address-autocomplete'
					),
					content: __(
						'The Attached Address Field must have the "Enable Google Address Autocomplete" setting enabled.',
						'gp-address-autocomplete'
					),
					id,
				});

				return;
			}

			$(`#gpaa_admin_map_field_message_container_${id}`)
				.html(
					`<div class="gpaa_blurred_map_overlay gpaa_map_field_centered_text">
					<p class="gpaa_map_field_placeholder_text">
						<strong>
							${__('MAP CONTENT', 'gp-address-autocomplete')}
						</strong>
						<br>
						${__(
							'This is a content placeholder. Preview this form to view the&nbsp;live&nbsp;map.',
							'gp-address-autocomplete'
						)}
					</p>
				</div>`
				)
				.css(
					'background-image',
					`url(${window.location.origin}/wp-content/plugins/gp-address-autocomplete/images/map-field-editor.png)`
				);
		});
	}

	updateMapFieldContents();
});

// Make this a module to avoid TypeScript error with block-scoped variables since we're not importing anything
export {};
