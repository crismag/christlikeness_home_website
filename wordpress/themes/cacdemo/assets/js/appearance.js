/**
 * Visitor colour choice (see inc/appearance/site-themes.php for the pre-paint part in <head>).
 * - Reveals the footer picker when browser storage works, and places a copy at the foot of the phone menu.
 * - A choice applies at once, is saved in localStorage, and keeps every copy of the picker in step.
 * - "Site default" removes the saved choice, so the site theme's recommended palette applies again.
 */
( function () {
	const root = document.documentElement;
	const KEY = 'cacdemo:palette';
	const pickers = Array.from( document.querySelectorAll( '[data-palette-picker]' ) );
	if ( ! pickers.length ) {
		return;
	}

	let storage = null;
	try {
		const probe = '__cacdemo__';
		window.localStorage.setItem( probe, probe );
		window.localStorage.removeItem( probe );
		storage = window.localStorage;
	} catch ( e ) {
		return; // No storage: keep the picker hidden rather than offer a choice that cannot be kept.
	}

	const forced = root.hasAttribute( 'data-palette-forced' );

	// Phone menu: the navigation overlay is core markup; add a copy at its foot.
	const overlay = document.querySelector( '.site-header .wp-block-navigation__responsive-container-content' );
	if ( overlay && ! overlay.querySelector( '[data-palette-picker]' ) ) {
		const copy = pickers[ 0 ].cloneNode( true );
		copy.classList.add( 'is-in-menu' );
		overlay.appendChild( copy );
		pickers.push( copy );
	}

	const selected = () => {
		const saved = storage.getItem( KEY );
		return saved && root.getAttribute( 'data-palette' ) === saved ? saved : '';
	};

	pickers.forEach( ( picker, index ) => {
		picker.querySelectorAll( 'input[type="radio"]' ).forEach( ( input ) => {
			input.name = 'cacdemo-palette-' + index;
			input.checked = input.value === selected();
			input.disabled = forced;
		} );
		picker.querySelector( '.cacdemo-palette-picker__note' ).hidden = ! forced;
		picker.hidden = false;
		picker.addEventListener( 'change', ( event ) => {
			const value = event.target.value;
			if ( value ) {
				storage.setItem( KEY, value );
				root.setAttribute( 'data-palette', value );
			} else {
				storage.removeItem( KEY );
				root.setAttribute( 'data-palette', root.getAttribute( 'data-palette-default' ) || 'default' );
			}
			pickers.forEach( ( other ) => {
				other.querySelectorAll( 'input[type="radio"]' ).forEach( ( input ) => {
					input.checked = input.value === value;
				} );
			} );
		} );
	} );
} )();
