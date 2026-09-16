/* Appearance → Site Theme: choose or remove a theme slot image with the Media Library. Saving is a normal form post. */
( function ( $, wp ) {
	'use strict';
	if ( ! wp || ! wp.media ) {
		return;
	}
	$( document ).on( 'click', '[data-slot-choose]', function () {
		const slot = $( this ).closest( '[data-slot]' );
		const frame = wp.media( {
			title: $( this ).text(),
			library: { type: 'image' },
			multiple: false,
			button: { text: $( this ).text() },
		} );
		frame.on( 'open', function () {
			const current = parseInt( slot.find( '[data-slot-input]' ).val(), 10 );
			if ( current ) {
				const attachment = wp.media.attachment( current );
				attachment.fetch();
				frame.state().get( 'selection' ).add( attachment );
			}
		} );
		frame.on( 'select', function () {
			const image = frame.state().get( 'selection' ).first().toJSON();
			const size = ( image.sizes && ( image.sizes.medium || image.sizes.full ) ) || image;
			slot.find( '[data-slot-input]' ).val( image.id );
			slot.find( '[data-slot-preview]' ).empty().append( $( '<img alt="">' ).attr( 'src', size.url ) );
			slot.find( '[data-slot-name]' ).text( image.title || image.filename );
			slot.find( '[data-slot-clear]' ).prop( 'hidden', false );
		} );
		frame.open();
	} );
	$( document ).on( 'click', '[data-slot-clear]', function () {
		const slot = $( this ).closest( '[data-slot]' );
		slot.find( '[data-slot-input]' ).val( '0' );
		slot.find( '[data-slot-preview]' ).empty().append( $( '<span>' ).text( wp.i18n ? wp.i18n.__( 'No image', 'cacdemo' ) : 'No image' ) );
		slot.find( '[data-slot-name]' ).text( '' );
		$( this ).prop( 'hidden', true );
		slot.find( '[data-slot-choose]' ).trigger( 'focus' );
	} );
} )( window.jQuery, window.wp );
