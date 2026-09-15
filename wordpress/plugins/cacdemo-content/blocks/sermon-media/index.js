/* Editor registration without a build step: the block renders on the server. */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps } = wp.blockEditor;
	const el = wp.element.createElement;

	registerBlockType( 'cacdemo/sermon-media', {
		edit: function () {
			return el(
				'div',
				useBlockProps( { className: 'cacdemo-sermon-media-placeholder' } ),
				el( 'p', null, 'Sermon media' ),
				el( 'p', null, 'Shows the sermon’s Sources (YouTube, Facebook, audio) as tabs. Edit them in Sermon details → Sources.' )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
