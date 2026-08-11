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

	function normalizeRoute( route ) {
		var tokens = route.trim().split( /\s+/ );
		var suffixIndex = tokens.length - 1;
		var firstDirection = normalizedKey( tokens[ 0 ] );
		var lastDirection = normalizedKey( tokens[ suffixIndex ] );

		// Directional abbreviations are only expanded at a route boundary.
		if ( directions[ firstDirection ] ) {
			tokens[ 0 ] = directions[ firstDirection ];
		}

		if ( directions[ lastDirection ] ) {
			tokens[ suffixIndex ] = directions[ lastDirection ];
			suffixIndex--;
		}

		// A suffix is only expanded at the end of the route (before an optional direction).
		if ( suffixIndex >= 0 && suffixes[ normalizedKey( tokens[ suffixIndex ] ) ] ) {
			tokens[ suffixIndex ] = suffixes[ normalizedKey( tokens[ suffixIndex ] ) ];
		}

		return tokens.join( ' ' );
	}

	gform.addFilter( 'gpaa_values', function( values, place ) {
		var components = place && Array.isArray( place.address_components ) ? place.address_components : [];
		var streetNumber;
		var route;

		if ( ! components.length || ! isUnitedStatesPlace( components ) ) {
			return values;
		}

		streetNumber = componentByType( components, 'street_number' );
		route = componentByType( components, 'route' );

		// Do not replace a value when Google did not provide a usable street route.
		if ( ! route || ! route.long_name ) {
			return values;
		}

		values.address1 = ( streetNumber && streetNumber.long_name ? streetNumber.long_name + ' ' : '' ) + normalizeRoute( route.long_name );

		return values;
	} );
}() );
