/* Tabs for sermon media sources (WAI-ARIA tabs pattern). Without JS the first source shows. */
document.querySelectorAll( '.wp-block-cacdemo-sermon-media.has-tabs' ).forEach( ( root ) => {
	const tabs = [ ...root.querySelectorAll( '[role="tab"]' ) ];

	const select = ( tab ) => {
		tabs.forEach( ( t ) => {
			const on = t === tab;
			t.setAttribute( 'aria-selected', String( on ) );
			t.tabIndex = on ? 0 : -1;
			const panel = root.querySelector( '#' + t.getAttribute( 'aria-controls' ) );
			panel.hidden = ! on;
			// Pause media in panels that are no longer shown.
			if ( ! on ) {
				panel.querySelectorAll( 'audio, video' ).forEach( ( m ) => m.pause() );
				panel.querySelectorAll( 'iframe' ).forEach( ( f ) => { const src = f.src; f.src = 'about:blank'; f.src = src; } );
			}
		} );
		tab.focus();
	};

	tabs.forEach( ( tab, i ) => {
		tab.addEventListener( 'click', () => select( tab ) );
		tab.addEventListener( 'keydown', ( e ) => {
			const next = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[ e.key ];
			if ( next !== undefined ) {
				e.preventDefault();
				select( tabs[ ( next + tabs.length ) % tabs.length ] );
			}
		} );
	} );
} );
