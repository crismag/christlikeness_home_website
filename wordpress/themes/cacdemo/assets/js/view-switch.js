/**
 * Grid / List / Table switch for the sermon collection.
 * The switch links work without JavaScript (the view and filters are in the address). This script
 * switches Grid ↔ List in place, without reloading, and keeps the address and the filter form in step.
 * The Table is rendered on the server, so switching to or from it loads the page.
 */
( function () {
	// Filter bar: dropdowns apply on change, empty fields stay out of the address, and on phones the
	// "Filters" toggle starts closed (unless a filter is active) so the sermons are visible first.
	document.querySelectorAll( '.cacdemo-filters' ).forEach( function ( form ) {
		form.classList.add( 'is-enhanced' );
		form.addEventListener( 'change', function ( event ) {
			if ( event.target.matches( 'select' ) ) {
				form.requestSubmit();
			}
		} );
		form.addEventListener( 'submit', function () {
			form.querySelectorAll( 'input, select' ).forEach( function ( field ) {
				if ( '' === field.value ) {
					field.disabled = true;
				}
			} );
		} );
		const toggle = form.querySelector( '.cacdemo-filters__toggle' );
		const setOpen = function ( open ) {
			form.classList.toggle( 'is-open', open );
			toggle?.setAttribute( 'aria-expanded', String( open ) );
		};
		setOpen( '0' !== form.dataset.active );
		toggle?.addEventListener( 'click', function () {
			setOpen( ! form.classList.contains( 'is-open' ) );
		} );
	} );

	const switches = document.querySelectorAll( '.cacdemo-view-switch' );
	if ( ! switches.length ) {
		return;
	}
	const current = () => ( document.body.classList.contains( 'cacdemo-view-table' ) ? 'table' : document.body.classList.contains( 'cacdemo-view-list' ) ? 'list' : 'grid' );

	function show( view ) {
		document.body.classList.toggle( 'cacdemo-view-list', 'list' === view );
		document.querySelectorAll( '.cacdemo-view-switch__link' ).forEach( function ( link ) {
			if ( link.dataset.view === view ) {
				link.setAttribute( 'aria-current', 'true' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
		const url = new URL( window.location.href );
		if ( 'grid' === view ) {
			url.searchParams.delete( 'view' );
		} else {
			url.searchParams.set( 'view', view );
		}
		window.history.replaceState( null, '', url );
		document.querySelectorAll( '.cacdemo-filters' ).forEach( function ( form ) {
			let input = form.querySelector( 'input[name="view"]' );
			if ( 'grid' === view ) {
				input?.remove();
				return;
			}
			if ( ! input ) {
				input = Object.assign( document.createElement( 'input' ), { type: 'hidden', name: 'view' } );
				form.appendChild( input );
			}
			input.value = view;
		} );
	}

	switches.forEach( function ( nav ) {
		nav.addEventListener( 'click', function ( event ) {
			const link = event.target.closest( '.cacdemo-view-switch__link' );
			if ( ! link ) {
				return;
			}
			const target = link.dataset.view;
			if ( 'table' === target || 'table' === current() ) {
				return; // Server-rendered: follow the link.
			}
			event.preventDefault();
			show( target );
		} );
	} );
} )();
