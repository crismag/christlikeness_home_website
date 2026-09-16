/* Editor placeholder for the server-rendered visitor colour picker. */
( function ( wp ) {
	wp.blocks.registerBlockType( 'cacdemo/palette-picker', {
		edit: function () {
			return wp.element.createElement( 'p', wp.blockEditor.useBlockProps( { className: 'cacdemo-block-placeholder' } ), 'Visitor colours — palette choice for visitors (shown on the site).' );
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
