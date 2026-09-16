/* Editor registration without a build step: inner blocks are edited normally; the image is resolved on the server. */
( function ( wp ) {
	const el = wp.element.createElement;
	const { __ } = wp.i18n;
	const slots = ( window.cacdemoHeroSlots || [] );

	wp.blocks.registerBlockType( 'cacdemo/page-hero', {
		edit: function ( props ) {
			const blockProps = wp.blockEditor.useBlockProps( { className: 'cacdemo-hero is-editor is-' + ( props.attributes.variant || 'moderate' ) } );
			const slot = slots.find( ( s ) => s.value === ( props.attributes.themeSlot || '' ) );
			return el(
				wp.element.Fragment,
				null,
				el(
					wp.blockEditor.InspectorControls,
					null,
					el(
						wp.components.PanelBody,
						{ title: __( 'Hero', 'cacdemo' ) },
						el( wp.components.SelectControl, {
							label: __( 'Height', 'cacdemo' ),
							value: props.attributes.variant || 'moderate',
							options: [
								{ label: __( 'Tall (landing pages)', 'cacdemo' ), value: 'landing' },
								{ label: __( 'Moderate', 'cacdemo' ), value: 'moderate' },
							],
							onChange: ( value ) => props.setAttributes( { variant: value } ),
							__nextHasNoMarginBottom: true,
						} ),
						el( wp.components.SelectControl, {
							label: __( 'Image when the page has no banner', 'cacdemo' ),
							help: __( 'Site theme images are chosen in Appearance → Site Theme. "None" keeps the page typographic.', 'cacdemo' ),
							value: props.attributes.themeSlot || '',
							options: slots,
							onChange: ( value ) => props.setAttributes( { themeSlot: value } ),
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el(
					'div',
					blockProps,
					el(
						'p',
						{ className: 'cacdemo-block-placeholder' },
						slot && slot.value
							/* translators: %s: name of the site theme image slot */
							? wp.i18n.sprintf( __( 'Page hero — the page banner, or the site theme’s %s', 'cacdemo' ), slot.label )
							: __( 'Page hero — the page banner, if it has one', 'cacdemo' )
					),
					el( wp.blockEditor.InnerBlocks )
				)
			);
		},
		save: function () {
			return el( wp.blockEditor.InnerBlocks.Content );
		},
	} );
} )( window.wp );
