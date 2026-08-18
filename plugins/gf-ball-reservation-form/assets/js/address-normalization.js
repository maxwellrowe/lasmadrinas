/* global gform */
( function() {
	'use strict';

	// Keys are normalized by removing periods and converting to uppercase.
	var directions = {
		N: 'North', S: 'South', E: 'East', W: 'West',
		NE: 'Northeast', NW: 'Northwest', SE: 'Southeast', SW: 'Southwest'
	};

	// Common USPS street suffixes. Add new whole-token mappings here as needed.
	var suffixes = {
		ALY: 'Alley', ANX: 'Annex', ARC: 'Arcade', AVE: 'Avenue', BYP: 'Bypass', BLVD: 'Boulevard',
		CTR: 'Center', CIR: 'Circle', CLB: 'Club', CMN: 'Common', COR: 'Corner', CRSE: 'Course',
		CT: 'Court', CV: 'Cove', CRK: 'Creek', CRES: 'Crescent', XING: 'Crossing', DL: 'Dale',
		DM: 'Dam', DV: 'Divide', DR: 'Drive', EST: 'Estate', EXWY: 'Expressway', EXPY: 'Expressway',
		FALL: 'Fall', FLS: 'Falls', FRY: 'Ferry', FLD: 'Field', FLDS: 'Fields', FLT: 'Flat',
		FLTS: 'Flats', FRD: 'Ford', FRST: 'Forest', FRG: 'Forge', FRK: 'Fork', FRKS: 'Forks',
		FT: 'Fort', FWY: 'Freeway', GDN: 'Garden', GDNS: 'Gardens', GTWY: 'Gateway', GLN: 'Glen',
		GRN: 'Green', GRV: 'Grove', HBR: 'Harbor', HVN: 'Haven', HTS: 'Heights', HWY: 'Highway',
		HL: 'Hill', HLS: 'Hills', HOLW: 'Hollow', INLT: 'Inlet', IS: 'Island', ISS: 'Islands',
		JCT: 'Junction', KY: 'Key', KYS: 'Keys', KNL: 'Knoll', KNLS: 'Knolls', LK: 'Lake',
		LKS: 'Lakes', LAND: 'Land', LNDG: 'Landing', LN: 'Lane', LGT: 'Light', LF: 'Loaf',
		LCK: 'Lock', LCKS: 'Locks', LDG: 'Lodge', LOOP: 'Loop', MALL: 'Mall', MNR: 'Manor',
		MDW: 'Meadow', MEWS: 'Mews', ML: 'Mill', MLS: 'Mills', MSN: 'Mission', MTWY: 'Motorway',
		MT: 'Mount', MTN: 'Mountain', NCK: 'Neck', ORCH: 'Orchard', OVAL: 'Oval', PARK: 'Park',
		PASS: 'Pass', PATH: 'Path', PIKE: 'Pike', PNE: 'Pine', PKWY: 'Parkway', PL: 'Place',
		PLN: 'Plain', PLNS: 'Plains', PLZ: 'Plaza', PT: 'Point', PRT: 'Port', PR: 'Prairie',
		RADL: 'Radial', RNCH: 'Ranch', RPD: 'Rapid', RPDS: 'Rapids', RST: 'Rest', RDG: 'Ridge',
		RIV: 'River', RD: 'Road', RTE: 'Route', ROW: 'Row', RUE: 'Rue', RUN: 'Run', SHL: 'Shoal',
		SHR: 'Shore', SHWY: 'Skyway', SPG: 'Spring', SQ: 'Square', STA: 'Station', STRM: 'Stream',
		ST: 'Street', SMIT: 'Summit', TER: 'Terrace', TRWY: 'Throughway', TRCE: 'Trace', TRK: 'Track',
		TRFY: 'Trafficway', TRL: 'Trail', TUNL: 'Tunnel', TPKE: 'Turnpike', UPAS: 'Underpass',
		VLY: 'Valley', VLYS: 'Valleys', VIA: 'Viaduct', VW: 'View', VLG: 'Village', VL: 'Ville',
		VIS: 'Vista', WALK: 'Walk', WALL: 'Wall', WAY: 'Way', WLS: 'Wells'
	};

	function normalizedKey( token ) {
		return token.replace( /\./g, '' ).toUpperCase();
	}

	function componentByType( components, type ) {
		for ( var index = 0; index < components.length; index++ ) {
			if ( components[ index ].types && components[ index ].types.indexOf( type ) !== -1 ) {
				return components[ index ];
			}
		}

		return null;
	}

	function isUnitedStatesPlace( components ) {
		var country = componentByType( components, 'country' );
		return country && country.short_name === 'US';
	}

	function isHouseNumberToken( token ) {
		return /^\d[\dA-Za-z-]*$/.test( token ) || /^\d+\/\d+$/.test( token );
	}

	function normalizeAddressLine1( addressLine1 ) {
		var tokens = addressLine1.trim().split( /\s+/ );
		var suffixIndex = tokens.length - 1;
		var directionIndex = isHouseNumberToken( tokens[ 0 ] ) ? 1 : 0;
		var firstDirection = normalizedKey( tokens[ directionIndex ] || '' );
		var lastDirection = normalizedKey( tokens[ suffixIndex ] );

		// Expand a direction only at the beginning of the street name or at its end.
		if ( directions[ firstDirection ] ) {
			tokens[ directionIndex ] = directions[ firstDirection ];
		}

		if ( directions[ lastDirection ] ) {
			tokens[ suffixIndex ] = directions[ lastDirection ];
			suffixIndex--;
		}

		// A suffix is only expanded at the end (before an optional direction).
		if ( suffixIndex >= 0 && suffixes[ normalizedKey( tokens[ suffixIndex ] ) ] ) {
			tokens[ suffixIndex ] = suffixes[ normalizedKey( tokens[ suffixIndex ] ) ];
		}

		return tokens.join( ' ' );
	}

	gform.addFilter( 'gpaa_values', function( values, place ) {
		var components = place && Array.isArray( place.address_components ) ? place.address_components : [];

		if ( ! components.length || ! isUnitedStatesPlace( components ) ) {
			return values;
		}

		// Preserve the value GP Address Autocomplete already derived. The prediction
		// label and Google's route component are not a reliable replacement for it.
		if ( ! values.address1 || 'string' !== typeof values.address1 ) {
			return values;
		}

		values.address1 = normalizeAddressLine1( values.address1 );

		// Keep only the five-digit ZIP for standard ZIP+4 values (e.g. 91105-1930).
		if ( 'string' === typeof values.postcode && /^(\d{5})-\d{4}$/.test( values.postcode ) ) {
			values.postcode = values.postcode.slice( 0, 5 );
		}

		return values;
	} );

	// GP Address Autocomplete replaces address-input autocomplete values after
	// Gravity Forms has rendered them. Override its default "new-password"
	// value for the Ball Reservation parent and nested guest forms.
	gform.addFilter( 'gpaa_field_autocomplete_value', function( value, input, inputName, instance, formId ) {
		if ( 30 === Number( formId ) || 31 === Number( formId ) ) {
			return 'off';
		}

		return value;
	} );
}() );
