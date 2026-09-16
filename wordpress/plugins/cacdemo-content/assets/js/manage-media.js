/**
 * Content Manager media dialog: browse the Media Library by collection, search, upload (REST, joins General), and pick an
 * image for an image field, or add one to the text. Without this script the fields still upload through the form.
 */
( function () {
	'use strict';
	const config = window.cacdemoManageMedia;
	if ( ! config || typeof HTMLDialogElement === 'undefined' ) {
		return;
	}
	const t = config.text;
	const el = ( tag, attrs = {}, children = [] ) => {
		const node = document.createElement( tag );
		Object.entries( attrs ).forEach( ( [ key, value ] ) => {
			if ( key === 'text' ) {
				node.textContent = value;
			} else if ( value !== false && value !== null ) {
				node.setAttribute( key, value === true ? '' : value );
			}
		} );
		children.forEach( ( child ) => node.append( child ) );
		return node;
	};
	const size = ( item, preferred ) => {
		const sizes = ( item.media_details && item.media_details.sizes ) || {};
		for ( const name of preferred ) {
			if ( sizes[ name ] ) {
				return sizes[ name ].source_url;
			}
		}
		return item.source_url;
	};

	let dialog, dialogClose, grid, status, more, useButton, altWrap, altInput, collection, search, upload;
	let target = null; // { mode: 'field', fieldset } or { mode: 'text', editorId }
	let opener = null; // The button that opened the dialog: focus goes back to it on close (including Escape).
	let selected = null;
	let page = 1;
	let requestId = 0;

	function build() {
		const heading = el( 'h2', { id: 'cm-media-title', text: t.title } );
		const close = el( 'button', { type: 'button', class: 'cm-button', text: t.close } );
		close.addEventListener( 'click', () => dialog.close() );

		collection = el( 'select', { id: 'cm-media-collection' }, [ el( 'option', { value: '', text: t.all } ) ] );
		config.collections.forEach( ( c ) => collection.append( el( 'option', { value: String( c.id ), text: c.name } ) ) );
		collection.addEventListener( 'change', () => load( true ) );

		search = el( 'input', { type: 'search', id: 'cm-media-search' } );
		let timer;
		search.addEventListener( 'input', () => {
			clearTimeout( timer );
			timer = setTimeout( () => load( true ), 350 );
		} );

		upload = el( 'input', { type: 'file', id: 'cm-media-upload', accept: 'image/jpeg,image/png,image/webp' } );
		upload.addEventListener( 'change', uploadFile );

		grid = el( 'ul', { class: 'cm-media__grid', role: 'list' } );
		status = el( 'p', { class: 'cm-media__status', role: 'status', 'aria-live': 'polite' } );
		more = el( 'button', { type: 'button', class: 'cm-button', text: t.more, hidden: true } );
		more.addEventListener( 'click', () => load( false ) );

		altInput = el( 'input', { type: 'text', id: 'cm-media-alt' } );
		altWrap = el( 'div', { class: 'cm-media__alt', hidden: true }, [ el( 'label', { class: 'cm-sub', for: 'cm-media-alt', text: t.alt } ), altInput, el( 'p', { class: 'cm-help', text: t.altHelp } ) ] );
		useButton = el( 'button', { type: 'button', class: 'cm-button is-primary', disabled: true, text: t.use } );
		useButton.addEventListener( 'click', choose );

		dialogClose = () => opener && opener.isConnected && opener.focus();
		dialog = el( 'dialog', { class: 'cm-media', 'aria-labelledby': 'cm-media-title' }, [
			el( 'div', { class: 'cm-media__head' }, [ heading, close ] ),
			el( 'div', { class: 'cm-media__tools' }, [
				el( 'div', {}, [ el( 'label', { class: 'cm-sub', for: 'cm-media-collection', text: t.collection } ), collection ] ),
				el( 'div', {}, [ el( 'label', { class: 'cm-sub', for: 'cm-media-search', text: t.search } ), search ] ),
				el( 'div', {}, [ el( 'label', { class: 'cm-sub', for: 'cm-media-upload', text: t.upload } ), upload ] ),
			] ),
			status,
			el( 'div', { class: 'cm-media__body' }, [ grid, more ] ),
			el( 'div', { class: 'cm-media__foot' }, [ altWrap, useButton ] ),
		] );
		dialog.addEventListener( 'close', dialogClose );
		document.body.append( dialog );
	}

	function open( nextTarget, trigger ) {
		if ( ! dialog ) {
			build();
		}
		target = nextTarget;
		opener = trigger;
		select( null );
		altWrap.hidden = target.mode !== 'text';
		altInput.value = '';
		useButton.textContent = target.mode === 'text' ? t.insert : t.use;
		dialog.showModal();
		load( true );
		search.focus();
	}

	async function load( reset ) {
		const id = ++requestId;
		page = reset ? 1 : page + 1;
		if ( reset ) {
			grid.replaceChildren();
		}
		status.textContent = t.loading;
		more.hidden = true;
		const params = new URLSearchParams( { media_type: 'image', per_page: '24', page: String( page ), _fields: 'id,title,alt_text,source_url,media_details' } );
		if ( collection.value ) {
			params.set( 'media_collection', collection.value );
		}
		if ( search.value.trim() ) {
			params.set( 'search', search.value.trim() );
		}
		try {
			const response = await fetch( config.rest + '?' + params, { headers: { 'X-WP-Nonce': config.nonce }, credentials: 'same-origin' } );
			if ( id !== requestId ) {
				return;
			}
			if ( ! response.ok ) {
				throw new Error( String( response.status ) );
			}
			const items = await response.json();
			items.forEach( add );
			const pages = parseInt( response.headers.get( 'X-WP-TotalPages' ) || '1', 10 );
			more.hidden = page >= pages;
			status.textContent = grid.children.length ? '' : t.none;
		} catch ( e ) {
			if ( id === requestId ) {
				status.textContent = t.failed;
			}
		}
	}

	function add( item, first ) {
		const title = ( item.title && ( item.title.rendered || item.title.raw ) ) || '';
		const label = new DOMParser().parseFromString( title, 'text/html' ).body; // Rendered titles are HTML-escaped text; parsed inertly.
		const button = el( 'button', { type: 'button', class: 'cm-media__item', 'aria-pressed': 'false' }, [
			el( 'img', { src: size( item, [ 'medium', 'thumbnail' ] ), alt: '', loading: 'lazy' } ),
			el( 'span', { class: 'cm-media__label', text: label.textContent } ),
		] );
		button.addEventListener( 'click', () => select( { item, button, title: label.textContent } ) );
		button.addEventListener( 'dblclick', choose );
		const li = el( 'li', {}, [ button ] );
		if ( first === true ) {
			grid.prepend( li );
		} else {
			grid.append( li );
		}
		return button;
	}

	function select( next ) {
		if ( selected ) {
			selected.button.setAttribute( 'aria-pressed', 'false' );
		}
		selected = next;
		if ( selected ) {
			selected.button.setAttribute( 'aria-pressed', 'true' );
			altInput.value = selected.item.alt_text || '';
		}
		useButton.disabled = ! selected;
	}

	async function uploadFile() {
		const file = upload.files[ 0 ];
		if ( ! file ) {
			return;
		}
		status.textContent = t.uploading;
		const body = new FormData();
		body.append( 'file', file, file.name );
		try {
			const response = await fetch( config.rest, { method: 'POST', body, headers: { 'X-WP-Nonce': config.nonce }, credentials: 'same-origin' } );
			const item = await response.json();
			if ( ! response.ok ) {
				throw new Error( item && item.message ? item.message : String( response.status ) );
			}
			const button = add( item, true );
			select( { item, button, title: button.textContent } );
			status.textContent = t.uploaded;
			button.focus();
		} catch ( e ) {
			status.textContent = e.message || t.failed;
		}
		upload.value = '';
	}

	function choose() {
		if ( ! selected || ! target ) {
			return;
		}
		const { item, title } = selected;
		if ( target.mode === 'field' ) {
			const fieldset = target.fieldset;
			fieldset.querySelector( '[data-cm-image-library]' ).value = String( item.id );
			const remove = fieldset.querySelector( '[data-cm-image-remove]' );
			if ( remove ) {
				remove.checked = false;
			}
			const preview = fieldset.querySelector( '[data-cm-image-preview]' );
			preview.replaceChildren(
				el( 'img', { class: 'cm-cover__image', src: size( item, [ 'medium', 'large' ] ), alt: '' } ),
				el( 'span', { class: 'cm-image__name', text: title } ),
				el( 'span', { class: 'cm-help', text: t.willUse } )
			);
			const alt = fieldset.querySelector( 'input[name$="_alt"]' );
			if ( alt && ! alt.value ) {
				alt.value = item.alt_text || '';
			}
			dialog.close();
		} else {
			const editor = window.tinymce && window.tinymce.get( target.editorId );
			const img = el( 'img', { src: size( item, [ 'large', 'medium_large', 'full' ] ), alt: altInput.value.trim(), class: 'wp-image-' + item.id } );
			const html = '<figure class="wp-block-image size-large">' + img.outerHTML + '</figure><p></p>';
			opener = null; // Focus goes into the text, not back to the button.
			dialog.close();
			if ( editor && ! editor.isHidden() ) {
				editor.focus();
				editor.insertContent( html );
			} else {
				const textarea = document.getElementById( target.editorId );
				if ( textarea ) {
					textarea.value += '\n' + html;
					textarea.focus();
				}
			}
		}
	}

	document.querySelectorAll( '[data-cm-image]' ).forEach( ( fieldset ) => {
		const actions = fieldset.querySelector( '[data-cm-library-actions]' );
		if ( actions ) {
			actions.hidden = false;
			const trigger = actions.querySelector( '[data-cm-library]' );
			trigger.addEventListener( 'click', () => open( { mode: 'field', fieldset }, trigger ) );
		}
	} );

	// The text editor (SCF's post content field): a button above it adds an image from the library.
	document.querySelectorAll( '.acf-field[data-name="_post_content"]' ).forEach( ( field ) => {
		if ( ! field.querySelector( 'textarea' ) ) {
			return;
		}
		const button = el( 'button', { type: 'button', class: 'cm-button cm-add-image', text: t.addToText } );
		// SCF gives the editor its ID when it starts, after this script runs: look it up on click.
		button.addEventListener( 'click', () => open( { mode: 'text', editorId: field.querySelector( 'textarea' ).id }, button ) );
		const label = field.querySelector( '.acf-label' );
		( label || field ).after( button );
	} );
} )();
