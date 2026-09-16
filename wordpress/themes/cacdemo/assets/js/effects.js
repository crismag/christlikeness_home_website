/**
 * Site-theme effects: falling snow (Winter) or leaves (Fall) inside the first image hero only.
 *
 * - Decorative: aria-hidden layer, no pointer events, animated with transform and opacity (compositor only).
 * - Never runs for prefers-reduced-motion or Save-Data, or without an image hero; fewer particles on small screens.
 * - Paused while the hero is off screen or the tab is hidden; a visible "Pause animation" button stops it (remembered
 *   in this browser), so motion never goes on without a way to stop it.
 * - Intensity comes from <html data-effects="subtle|enhanced">; the effect from data-effect.
 */
( function () {
	'use strict';
	const root = document.documentElement;
	const effect = root.getAttribute( 'data-effect' );
	const intensity = root.getAttribute( 'data-effects' );
	const hero = document.querySelector( '.cacdemo-hero.has-image' );
	const motion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const saveData = navigator.connection && navigator.connection.saveData;
	const text = window.cacdemoEffects || { pause: 'Pause animation', resume: 'Play animation' };
	const key = 'cacdemo:motion';
	if ( ! hero || ! [ 'snow', 'leaves' ].includes( effect ) || ! [ 'subtle', 'enhanced' ].includes( intensity ) || motion.matches || saveData ) {
		return;
	}

	let stored = null;
	try {
		stored = window.localStorage.getItem( key );
	} catch ( e ) {}

	const small = window.matchMedia( '(max-width: 781.98px)' ).matches;
	const counts = { snow: { subtle: 18, enhanced: 36 }, leaves: { subtle: 7, enhanced: 14 } };
	const count = Math.round( counts[ effect ][ intensity ] * ( small ? 0.5 : 1 ) );
	const random = ( min, max ) => min + Math.random() * ( max - min );

	const layer = document.createElement( 'div' );
	layer.className = 'cacdemo-effects is-' + effect;
	layer.setAttribute( 'aria-hidden', 'true' );
	const fragment = document.createDocumentFragment();
	for ( let i = 0; i < count; i++ ) {
		const p = document.createElement( 'span' );
		p.className = 'cacdemo-effects__p';
		const size = effect === 'snow' ? random( 3, intensity === 'enhanced' ? 7 : 5.5 ) : random( 20, 32 );
		const duration = effect === 'snow' ? random( 9, 18 ) : random( 12, 22 );
		p.style.cssText = [
			'--x:' + random( 0, 100 ).toFixed( 2 ) + '%',
			'--drift:' + random( -60, 60 ).toFixed( 0 ) + 'px',
			'--size:' + size.toFixed( 1 ) + 'px',
			'--spin:' + random( -320, 320 ).toFixed( 0 ) + 'deg',
			'--o:' + ( effect === 'snow' ? random( 0.45, 0.9 ) : random( 0.55, 0.85 ) ).toFixed( 2 ),
			'--tone:' + Math.floor( random( 0, 3 ) ),
			'animation-duration:' + duration.toFixed( 1 ) + 's',
			'animation-delay:-' + random( 0, duration ).toFixed( 1 ) + 's', // Already in motion at load, no burst.
		].join( ';' );
		fragment.append( p );
	}
	layer.append( fragment );

	const button = document.createElement( 'button' );
	button.type = 'button';
	button.className = 'cacdemo-effects-toggle';

	let userPaused = stored === 'off';
	let visible = true;
	const apply = () => {
		const running = ! userPaused && visible && ! document.hidden;
		layer.classList.toggle( 'is-paused', ! running );
		layer.hidden = userPaused;
		button.textContent = userPaused ? text.resume : text.pause;
		button.setAttribute( 'aria-pressed', userPaused ? 'true' : 'false' );
	};
	button.addEventListener( 'click', () => {
		userPaused = ! userPaused;
		try {
			window.localStorage.setItem( key, userPaused ? 'off' : 'on' );
		} catch ( e ) {}
		apply();
	} );

	const height = () => layer.style.setProperty( '--h', hero.offsetHeight + 'px' );
	height();
	const decor = hero.querySelector( '.cacdemo-hero__decor' );
	( decor || hero.firstChild ).after( layer );
	hero.append( button );
	apply();

	document.addEventListener( 'visibilitychange', apply );
	if ( 'IntersectionObserver' in window ) {
		new IntersectionObserver( ( entries ) => {
			visible = entries[ 0 ].isIntersecting;
			apply();
		} ).observe( hero );
	}
	if ( 'ResizeObserver' in window ) {
		new ResizeObserver( height ).observe( hero );
	}
	const stop = () => {
		if ( motion.matches ) {
			layer.remove();
			button.remove();
		}
	};
	if ( motion.addEventListener ) {
		motion.addEventListener( 'change', stop );
	}
} )();
