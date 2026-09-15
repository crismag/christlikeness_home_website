/* Editor registration without a build step: the block renders on the server. */
( function ( wp ) {
	const el = wp.element.createElement;
	wp.blocks.registerBlockType( 'cacdemo/sermon-filters', {
		edit: function () {
			return el( 'p', wp.blockEditor.useBlockProps( { className: 'cacdemo-block-placeholder' } ), 'Sermon filters — search, series, year, speaker, location, sort and the view switch (shown on the site).' );
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
