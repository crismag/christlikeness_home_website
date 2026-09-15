/**
 * Facebook page widgets on the Follow us page: each loads only when its card comes near the screen, at the card's width
 * (Facebook accepts 180–500px and does not resize an iframe after loading).
 */
const feeds = document.querySelectorAll( '.cacdemo-channel__feed[data-href]' );

function load( feed ) {
	const width = Math.max( 180, Math.min( 500, Math.floor( feed.clientWidth ) ) );
	const src = new URL( 'https://www.facebook.com/plugins/page.php' );
	Object.entries( { href: feed.dataset.href, tabs: 'timeline', width, height: 620, small_header: 'true', adapt_container_width: 'true', hide_cover: 'false', show_facepile: 'false' } ).forEach( ( [ key, value ] ) => src.searchParams.set( key, value ) );
	const frame = Object.assign( document.createElement( 'iframe' ), { src: src.toString(), width, height: 620, title: feed.dataset.title, loading: 'lazy' } );
	frame.setAttribute( 'allow', 'encrypted-media' );
	feed.replaceChildren( frame );
	feed.removeAttribute( 'data-href' );
}

if ( 'IntersectionObserver' in window ) {
	const observer = new IntersectionObserver( ( entries ) => {
		entries.forEach( ( entry ) => {
			if ( entry.isIntersecting ) {
				observer.unobserve( entry.target );
				load( entry.target );
			}
		} );
	}, { rootMargin: '400px 0px' } );
	feeds.forEach( ( feed ) => observer.observe( feed ) );
} else {
	feeds.forEach( load );
}
