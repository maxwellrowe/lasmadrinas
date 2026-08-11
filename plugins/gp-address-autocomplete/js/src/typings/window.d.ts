interface Window {
	jQuery: JQueryStatic

	// Form Editor
	wp: any

	// Frontend
	gpaaInit: () => void
	gpaaReady: true | undefined
	GP_Address_Autocomplete: any
	GP_Address_Autocomplete_Map_Field: any
	GP_ADDRESS_AUTOCOMPLETE_CONSTANTS: {
		allowed_countries: string[]
		countries: { [abbreviation: string]: string }
	}
}

interface GFField {
	gpaaMapAttachedAddressField: any;
	gpaaEnable: any;
}

