/* Editor registration without a build step: the block renders on the server, and only for permitted users. */
( function ( wp ) {
	const el = wp.element.createElement;
	wp.blocks.registerBlockType( 'cacdemo/publish-actions', {
		edit: function () {
			return el( 'p', wp.blockEditor.useBlockProps( { className: 'cacdemo-block-placeholder' } ), 'Publishing actions — New sermon, Edit page… (shown on the site only to people allowed to publish here).' );
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
