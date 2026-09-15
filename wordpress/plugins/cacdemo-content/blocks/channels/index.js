/* Editor registration without a build step: the block renders on the server from the Social channels list. */
( function ( wp ) {
	const el = wp.element.createElement;
	wp.blocks.registerBlockType( 'cacdemo/channels', {
		edit: function ( props ) {
			return el( 'p', wp.blockEditor.useBlockProps( { className: 'cacdemo-block-placeholder' } ), 'Social channels (' + props.attributes.variant + ') — shown on the site from Social channels.' );
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
