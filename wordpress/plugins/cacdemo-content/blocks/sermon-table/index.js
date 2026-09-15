/* Editor registration without a build step: the block renders on the server. */
( function ( wp ) {
	const el = wp.element.createElement;
	wp.blocks.registerBlockType( 'cacdemo/sermon-table', {
		edit: function () {
			return el( 'p', wp.blockEditor.useBlockProps( { className: 'cacdemo-block-placeholder' } ), 'Sermon table — grouped list of sermons (shown on the site in the Table view).' );
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
