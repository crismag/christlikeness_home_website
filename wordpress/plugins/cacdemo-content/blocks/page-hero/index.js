/* Editor registration without a build step: inner blocks are edited normally; the image is resolved on the server. */
( function ( wp ) {
	const el = wp.element.createElement;
	wp.blocks.registerBlockType( 'cacdemo/page-hero', {
		edit: function () {
			const props = wp.blockEditor.useBlockProps( { className: 'cacdemo-hero is-editor' } );
			return el( 'div', props, el( 'p', { className: 'cacdemo-block-placeholder' }, 'Page hero — banner image shown on the site' ), el( wp.blockEditor.InnerBlocks ) );
		},
		save: function () {
			return el( wp.blockEditor.InnerBlocks.Content );
		},
	} );
} )( window.wp );
