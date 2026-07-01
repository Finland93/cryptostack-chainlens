/* CryptoStack ChainLens — new-tokens feed widget. Vanilla JS. */
( function () {
	'use strict';

	var CFG = window.CSC_FEED || { rest: '', nonce: '', i18n: {} };
	var I18N = CFG.i18n || {};
	function t( k, f ) { return I18N[ k ] || f; }

	function el( tag, cls, text ) {
		var n = document.createElement( tag );
		if ( cls ) { n.className = cls; }
		if ( text !== undefined && text !== null ) { n.textContent = String( text ); }
		return n;
	}

	var EXPLORER = {
		ethereum: 'https://dexscreener.com/ethereum/',
		bsc: 'https://dexscreener.com/bsc/',
		base: 'https://dexscreener.com/base/',
		solana: 'https://dexscreener.com/solana/'
	};

	function renderList( root, data ) {
		var list = root.querySelector( '.csc-feed__list' );
		list.innerHTML = '';

		if ( ! data || ! data.ok ) {
			list.appendChild( el( 'div', 'csc-feed__msg', ( data && data.error ) || t( 'failed', 'Could not load new tokens.' ) ) );
			return;
		}
		if ( ! data.items || ! data.items.length ) {
			list.appendChild( el( 'div', 'csc-feed__msg', t( 'empty', 'No new tokens right now.' ) ) );
			return;
		}

		data.items.forEach( function ( it ) {
			var row = el( 'button', 'csc-feed__item' );
			row.type = 'button';

			var left = el( 'div', 'csc-feed__item-main' );
			left.appendChild( el( 'span', 'csc-feed__sym', it.symbol || it.name || '—' ) );
			var meta = el( 'span', 'csc-feed__meta' );
			meta.textContent = [ it.age, it.liq ].filter( Boolean ).join( ' · ' );
			left.appendChild( meta );
			row.appendChild( left );

			row.appendChild( el( 'span', 'csc-feed__scan', t( 'scan', 'Scan' ) ) );

			row.addEventListener( 'click', function () {
				if ( typeof window.cscScan === 'function' ) {
					window.cscScan( it.address );
					return;
				}
				var base = EXPLORER[ it.chain ] || EXPLORER.ethereum;
				window.open( base + encodeURIComponent( it.address ), '_blank', 'noopener' );
			} );

			list.appendChild( row );
		} );
	}

	function load( root, chain ) {
		var list = root.querySelector( '.csc-feed__list' );
		list.innerHTML = '';
		list.appendChild( el( 'div', 'csc-feed__msg csc-feed__msg--loading', t( 'loading', 'Loading…' ) ) );

		var url = CFG.rest + ( CFG.rest.indexOf( '?' ) === -1 ? '?' : '&' ) + 'chain=' + encodeURIComponent( chain );

		fetch( url, { headers: { 'X-WP-Nonce': CFG.nonce || '' }, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) { renderList( root, data ); } )
			.catch( function () { renderList( root, { ok: false, error: t( 'failed', 'Could not load new tokens.' ) } ); } );
	}

	function initRoot( root ) {
		if ( root.getAttribute( 'data-csc-ready' ) === '1' ) { return; }
		root.setAttribute( 'data-csc-ready', '1' );

		var current = root.getAttribute( 'data-chain' ) || 'ethereum';
		var tabs = root.querySelectorAll( '.csc-feed__tab' );

		function setActive( chain ) {
			current = chain;
			for ( var i = 0; i < tabs.length; i++ ) {
				var on = tabs[ i ].getAttribute( 'data-chain' ) === chain;
				tabs[ i ].className = 'csc-feed__tab' + ( on ? ' is-active' : '' );
			}
			load( root, chain );
		}

		for ( var i = 0; i < tabs.length; i++ ) {
			( function ( tab ) {
				tab.addEventListener( 'click', function () { setActive( tab.getAttribute( 'data-chain' ) ); } );
			} )( tabs[ i ] );
		}

		var refresh = root.querySelector( '.csc-feed__refresh' );
		if ( refresh ) {
			refresh.addEventListener( 'click', function () { load( root, current ); } );
		}

		setActive( current );
	}

	function init() {
		var roots = document.querySelectorAll( '.csc-feed' );
		for ( var i = 0; i < roots.length; i++ ) { initRoot( roots[ i ] ); }
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
