/* CryptoStack ChainLens — headless driver for the bundled ChainLens analysis engine.
   Runs the real engine (chains/api/analysis/app) with stubbed Render/Feed/CLStore and a
   hidden DOM, captures Analysis.totalScore() plus the Render call arguments, and renders
   the result in this plugin's own UI. The score is therefore identical to the live engine. */
( function () {
	'use strict';

	var CFG  = window.CSC_SCAN || { i18n: {} };
	var I18N = CFG.i18n || {};
	function t( k, f ) { return I18N[ k ] || f; }

	function el( tag, cls, text ) {
		var n = document.createElement( tag );
		if ( cls ) { n.className = cls; }
		if ( text !== undefined && text !== null ) { n.textContent = String( text ); }
		return n;
	}

	var CLS_MOD   = { safe: 'good', warn: 'warn', risk: 'risk', danger: 'danger', scam: 'scam' };
	var CLS_RING  = { safe: '#16c784', warn: '#f5b23d', risk: '#f97b3d', danger: '#ef4d4d', scam: '#e11d48' };
	var RISK_ICON = { danger: '\u2715', warning: '!', good: '\u2713' };
	var RISK_MOD  = { danger: 'fail', warning: 'warn', good: 'pass' };
	var SECTION_LABELS = {
		botDetection: 'Bot activity',
		tradePatterns: 'Trade patterns',
		contractSecurity: 'Contract security',
		holderDistribution: 'Holder distribution',
		liquidityHealth: 'Liquidity health',
		priceBehavior: 'Price behavior',
		socialMeta: 'Social & metadata',
		deployerHistory: 'Deployer history'
	};

	function fmtUsd( n ) {
		n = Number( n ) || 0; var a = Math.abs( n );
		if ( a >= 1e9 ) { return '$' + ( n / 1e9 ).toFixed( 2 ) + 'B'; }
		if ( a >= 1e6 ) { return '$' + ( n / 1e6 ).toFixed( 2 ) + 'M'; }
		if ( a >= 1e3 ) { return '$' + ( n / 1e3 ).toFixed( 1 ) + 'K'; }
		return '$' + Math.round( n );
	}
	function fmtPrice( n ) {
		n = Number( n ) || 0;
		if ( n <= 0 ) { return '\u2014'; }
		if ( n >= 1 ) { return '$' + n.toLocaleString( undefined, { maximumFractionDigits: 4 } ); }
		var d = Math.min( 12, Math.max( 4, Math.ceil( -Math.log10( n ) ) + 3 ) );
		return '$' + n.toFixed( d ).replace( /0+$/, '' ).replace( /\.$/, '' );
	}
	function fmtNum( n ) { n = Number( n ) || 0; return n.toLocaleString(); }
	function fmtAge( ms ) {
		if ( !ms || ms < 0 ) { return ''; }
		var d = ms / 86400000;
		if ( d < 1 ) { return Math.max( 1, Math.round( ms / 3600000 ) ) + 'h'; }
		if ( d < 30 ) { return Math.round( d ) + 'd'; }
		if ( d < 365 ) { return Math.round( d / 30 ) + 'mo'; }
		return ( d / 365 ).toFixed( 1 ) + 'y';
	}

	/* ---- capture state ---- */
	var store = {};
	var capturedScore = null;
	var activeRoot = null;
	var renderTimer = null;
	var scan = { running: false, handled: false, savedTitle: '', timeout: null, obs: null };

	var RENDER_METHODS = [ 'apiTransparency', 'botTab', 'chartTab', 'contractTab', 'copycatTab',
		'detailsTab', 'generateShareCard', 'header', 'holderBubbles', 'holdersTab', 'linksTab',
		'risks', 'scamCards', 'score', 'shareBar', 'tradesTab', 'warningBanner' ];

	function capture( prop, args ) {
		try { store[ prop ] = Array.prototype.slice.call( args ); } catch ( e ) {}
		schedule();
	}

	var stubReady = false;
	function ensureStub() {
		if ( stubReady ) { return; }
		stubReady = true;

		var host = document.getElementById( 'csc-engine-dom' );
		if ( !host ) {
			host = document.createElement( 'div' );
			host.id = 'csc-engine-dom';
			host.setAttribute( 'aria-hidden', 'true' );
			host.style.cssText = 'position:absolute!important;left:-99999px!important;top:0!important;width:1px;height:1px;overflow:hidden;';
			var ids = [ 'inp', 'scanBtn', 'overlay', 'ovSub', 'results', 'emptyState', 'exChips',
				'copyBtn', 'favBtn', 'hScanned', 'hThreats', 'panelBody', 'panelOverlay',
				'shareBar', 'shareCard', 'tbHold', 'tokenHdr', 'warningBanner' ];
			var html = '';
			for ( var i = 0; i < ids.length; i++ ) {
				var id = ids[ i ];
				if ( id === 'inp' ) { html += '<input id="inp" type="text" />'; }
				else if ( id === 'scanBtn' || id === 'copyBtn' || id === 'favBtn' ) { html += '<button id="' + id + '" type="button"></button>'; }
				else if ( id === 'shareCard' ) { html += '<canvas id="shareCard"></canvas>'; }
				else { html += '<div id="' + id + '"></div>'; }
			}
			host.innerHTML = html;
			( document.body || document.documentElement ).appendChild( host );
		}

		if ( typeof Proxy !== 'undefined' ) {
			window.Render = new Proxy( {}, { get: function ( _, prop ) {
				return function () { capture( String( prop ), arguments ); };
			} } );
		} else {
			window.Render = {};
			RENDER_METHODS.forEach( function ( m ) { window.Render[ m ] = function () { capture( m, arguments ); }; } );
		}
		window.Feed = { start: function () {}, restart: function () {}, stop: function () {}, render: function () {} };
		window.CLStore = {
			addHistory: function () {}, isFavorite: function () { return false; }, updateEntry: function () {},
			getHistory: function () { return []; }, getFavorites: function () { return []; },
			removeFavorite: function () {}, addFavorite: function () { return false; }, clearHistory: function () {}
		};

		try {
			if ( typeof Analysis !== 'undefined' && Analysis && typeof Analysis.totalScore === 'function' && !Analysis.__cscWrapped ) {
				var origTotal = Analysis.totalScore;
				Analysis.totalScore = function () {
					var r = origTotal.apply( this, arguments );
					try { capturedScore = r; } catch ( e ) {}
					schedule();
					return r;
				};
				Analysis.__cscWrapped = true;
			}
		} catch ( e ) {}
	}

	function chainName() {
		try { if ( typeof CHAINS !== 'undefined' && typeof curChain !== 'undefined' && CHAINS[ curChain ] ) { return CHAINS[ curChain ].name || curChain; } } catch ( e ) {}
		return '';
	}
	function explorerUrl( addr ) {
		try { if ( typeof CHAINS !== 'undefined' && typeof curChain !== 'undefined' && CHAINS[ curChain ] && typeof CHAINS[ curChain ].token === 'function' ) { return CHAINS[ curChain ].token( addr ); } } catch ( e ) {}
		return '';
	}
	function chainSlug() { try { if ( typeof curChain !== 'undefined' ) { return curChain; } } catch ( e ) {} return ''; }
	function themeOf( root ) {
		var th = root.getAttribute( 'data-theme' );
		return ( th === 'light' || th === 'auto' ) ? th : 'dark';
	}

	function setMsg( root, text, state ) {
		var m = root.querySelector( '.csc-scan__msg' );
		if ( !m ) { return; }
		m.textContent = text || '';
		m.className = 'csc-scan__msg' + ( state ? ' is-' + state : '' );
	}

	function schedule() {
		if ( !scan.running ) { return; }
		if ( renderTimer ) { clearTimeout( renderTimer ); }
		renderTimer = setTimeout( finalize, 110 );
	}

	function finalize() {
		if ( !scan.running || scan.handled ) { return; }
		var Q = capturedScore
			|| ( store.score && store.score[ 0 ] )
			|| ( store.warningBanner && store.warningBanner[ 0 ] );
		var te = ( store.header && store.header[ 0 ] )
			|| ( store.detailsTab && store.detailsTab[ 0 ] )
			|| ( store.shareBar && store.shareBar[ 0 ] );
		if ( !Q && !te ) { return; }
		scan.handled = true;
		cleanup();
		var Z = ( store.risks && store.risks[ 0 ] ) || [];
		var Y = ( store.scamCards && store.scamCards[ 0 ] ) || ( store.warningBanner && store.warningBanner[ 1 ] ) || [];
		renderResult( activeRoot, Q || {}, te || {}, Z, Y );
	}

	function cleanup() {
		if ( scan.timeout ) { clearTimeout( scan.timeout ); scan.timeout = null; }
		if ( scan.obs ) { try { scan.obs.disconnect(); } catch ( e ) {} scan.obs = null; }
		try { if ( scan.savedTitle ) { document.title = scan.savedTitle; } } catch ( e ) {}
		var btn = activeRoot && activeRoot.querySelector( '.csc-scan__btn' );
		if ( btn ) { btn.disabled = false; }
		scan.running = false;
	}

	function fail( root, msg ) {
		if ( scan.handled ) { return; }
		scan.handled = true;
		cleanup();
		setMsg( root, msg || t( 'failed', 'Could not complete the scan.' ), 'error' );
	}

	function watchErrors( root ) {
		var hdr = document.getElementById( 'tokenHdr' );
		if ( !hdr || !window.MutationObserver ) { return; }
		var obs = new MutationObserver( function () {
			var err = hdr.querySelector( '.scan-error p' );
			if ( err ) { fail( root, err.textContent || '' ); }
		} );
		obs.observe( hdr, { childList: true, subtree: true } );
		scan.obs = obs;
	}

	function section( box, title ) { box.appendChild( el( 'div', 'csc-scan__section-h', title ) ); }

	function renderResult( root, Q, te, Z, Y ) {
		var box = root.querySelector( '.csc-scan__result' );
		box.innerHTML = '';
		box.hidden = false;

		var cls = Q.cls || 'warn';
		root.className = 'csc-scan csc-theme-' + themeOf( root ) + ' csc-scan--' + ( CLS_MOD[ cls ] || 'warn' );

		var pct = Math.max( 0, Math.min( 100, Number( Q.pct ) || 0 ) );

		/* Hero: identity + score gauge */
		var hero = el( 'div', 'csc-scan__hero' );
		var idw = el( 'div', 'csc-scan__id' );
		idw.appendChild( el( 'div', 'csc-scan__name', te.name || te.symbol || 'Token' ) );
		var subline = el( 'div', 'csc-scan__subline' );
		if ( te.symbol ) { subline.appendChild( el( 'span', 'csc-scan__ticker', '$' + te.symbol ) ); }
		var cn = chainName();
		if ( cn ) { subline.appendChild( el( 'span', 'csc-scan__chip', cn ) ); }
		if ( subline.childNodes.length ) { idw.appendChild( subline ); }
		hero.appendChild( idw );

		var gw = el( 'div', 'csc-scan__gaugewrap' );
		var gauge = el( 'div', 'csc-scan__gauge' );
		gauge.style.setProperty( '--csc-pct', String( pct ) );
		gauge.style.setProperty( '--csc-ring', CLS_RING[ cls ] || CLS_RING.warn );
		var gnum = el( 'div', 'csc-scan__gauge-num' );
		gnum.appendChild( el( 'span', 'csc-scan__gauge-val', pct ) );
		gnum.appendChild( el( 'span', 'csc-scan__gauge-max', '/100' ) );
		gauge.appendChild( gnum );
		gw.appendChild( gauge );
		if ( Q.verdict ) { gw.appendChild( el( 'div', 'csc-scan__verdict-label', Q.verdict ) ); }
		hero.appendChild( gw );
		box.appendChild( hero );

		if ( typeof Q.passedChecks === 'number' && typeof Q.totalChecks === 'number' && Q.totalChecks > 0 ) {
			box.appendChild( el( 'div', 'csc-scan__meta', Q.passedChecks + ' / ' + Q.totalChecks + ' ' + t( 'checksPassed', 'checks passed' ) ) );
		}
		if ( Q.dataWarning ) { box.appendChild( el( 'div', 'csc-scan__notice', Q.dataWarning ) ); }

		/* Red flags */
		var flags = [];
		if ( Y && Y.length ) { for ( var i = 0; i < Y.length; i++ ) { if ( Y[ i ] && Y[ i ].detected ) { flags.push( Y[ i ] ); } } }
		if ( flags.length ) {
			section( box, t( 'redFlags', 'Red flags' ) );
			var fwrap = el( 'div', 'csc-scan__flags' );
			flags.forEach( function ( f ) {
				var chip = el( 'span', 'csc-scan__flag' );
				chip.appendChild( el( 'span', 'csc-scan__flag-ico', f.icon || '\u26a0' ) );
				chip.appendChild( el( 'span', null, f.name || f.id || 'Flag' ) );
				if ( f.evidence && f.evidence.length ) { chip.title = f.evidence.join( '  \u2022  ' ); }
				fwrap.appendChild( chip );
			} );
			box.appendChild( fwrap );
		}

		/* Score breakdown */
		if ( Q.breakdown && Q.breakdown.length ) {
			section( box, t( 'breakdown', 'Score breakdown' ) );
			var bars = el( 'div', 'csc-scan__bars' );
			Q.breakdown.forEach( function ( s ) {
				var p = Math.max( 0, Math.min( 100, Number( s.pct ) || 0 ) );
				var lvl = p >= 65 ? 'good' : ( p >= 35 ? 'warn' : 'danger' );
				var row = el( 'div', 'csc-scan__bar-row' );
				row.appendChild( el( 'span', 'csc-scan__bar-label', SECTION_LABELS[ s.name ] || s.name ) );
				var track = el( 'span', 'csc-scan__bar-track' );
				var fill = el( 'span', 'csc-scan__bar-fill csc-scan__bar-fill--' + lvl );
				fill.style.width = p + '%';
				track.appendChild( fill );
				row.appendChild( track );
				row.appendChild( el( 'span', 'csc-scan__bar-num', p ) );
				bars.appendChild( row );
			} );
			box.appendChild( bars );
		}

		/* Market */
		var market = [];
		if ( te.price ) { market.push( [ t( 'price', 'Price' ), fmtPrice( te.price ) ] ); }
		if ( te.reserve ) { market.push( [ t( 'liquidity', 'Liquidity' ), fmtUsd( te.reserve ) ] ); }
		if ( te.mcap ) { market.push( [ t( 'mcap', 'Market cap' ), fmtUsd( te.mcap ) ] ); }
		else if ( te.fdv ) { market.push( [ 'FDV', fmtUsd( te.fdv ) ] ); }
		if ( te.vol24 ) { market.push( [ t( 'vol', '24h volume' ), fmtUsd( te.vol24 ) ] ); }
		if ( te.holderCount && te.holderCount > 0 ) { market.push( [ t( 'holders', 'Holders' ), fmtNum( te.holderCount ) ] ); }
		if ( te.poolAge ) { var age = fmtAge( Date.now() - te.poolAge ); if ( age ) { market.push( [ t( 'age', 'Pair age' ), age ] ); } }
		if ( market.length ) {
			section( box, t( 'market', 'Market' ) );
			var grid = el( 'div', 'csc-scan__market' );
			market.forEach( function ( m ) {
				var cell = el( 'div', 'csc-scan__stat' );
				cell.appendChild( el( 'div', 'csc-scan__stat-l', m[ 0 ] ) );
				cell.appendChild( el( 'div', 'csc-scan__stat-v', m[ 1 ] ) );
				grid.appendChild( cell );
			} );
			box.appendChild( grid );
		}

		/* Findings */
		if ( Z && Z.length ) {
			var sorted = Z.slice().sort( function ( a, b ) {
				var o = { danger: 0, warning: 1, good: 2 };
				return ( o[ a.l ] === undefined ? 1 : o[ a.l ] ) - ( o[ b.l ] === undefined ? 1 : o[ b.l ] );
			} );
			section( box, t( 'findings', 'Findings' ) );
			var list = el( 'div', 'csc-scan__checks' );
			sorted.forEach( function ( r ) {
				var row = el( 'div', 'csc-scan__check' );
				row.appendChild( el( 'span', 'csc-scan__ico csc-scan__ico--' + ( RISK_MOD[ r.l ] || 'unknown' ), RISK_ICON[ r.l ] || '\u00b7' ) );
				var txt = el( 'div', 'csc-scan__check-body' );
				txt.appendChild( el( 'div', 'csc-scan__check-l', r.t || '' ) );
				if ( r.d ) { txt.appendChild( el( 'div', 'csc-scan__check-d2', r.d ) ); }
				row.appendChild( txt );
				list.appendChild( row );
			} );
			box.appendChild( list );
		}

		/* Links */
		var addr = te.address || '';
		var links = el( 'div', 'csc-scan__links' );
		var slug = chainSlug();
		if ( addr && slug ) {
			var dsUrl = 'https://dexscreener.com/' + encodeURIComponent( slug ) + '/' + encodeURIComponent( addr );
			var a1 = el( 'a', 'csc-scan__link', 'DexScreener' );
			a1.setAttribute( 'href', dsUrl ); a1.setAttribute( 'target', '_blank' ); a1.setAttribute( 'rel', 'noopener nofollow' );
			links.appendChild( a1 );
		}
		var ex = explorerUrl( addr );
		if ( ex && ex.indexOf( 'https://' ) === 0 ) {
			var a2 = el( 'a', 'csc-scan__link', t( 'explorer', 'Explorer' ) );
			a2.setAttribute( 'href', ex ); a2.setAttribute( 'target', '_blank' ); a2.setAttribute( 'rel', 'noopener nofollow' );
			links.appendChild( a2 );
		}
		if ( links.childNodes.length ) { box.appendChild( links ); }

		setMsg( root, '', '' );
	}

	function doScan( root ) {
		var input = root.querySelector( '.csc-scan__input' );
		var addr = ( input.value || '' ).trim();
		if ( !addr ) { setMsg( root, t( 'enterAddr', 'Please paste a token contract address.' ), 'error' ); input.focus(); return; }

		store = {};
		capturedScore = null;
		activeRoot = root;
		scan = { running: true, handled: false, savedTitle: document.title, timeout: null, obs: null };

		ensureStub();
		var hdr = document.getElementById( 'tokenHdr' );
		if ( hdr ) { hdr.innerHTML = ''; }

		setMsg( root, t( 'scanning', 'Scanning\u2026' ), 'loading' );
		var btn = root.querySelector( '.csc-scan__btn' );
		if ( btn ) { btn.disabled = true; }

		watchErrors( root );
		scan.timeout = setTimeout( function () { fail( root, t( 'failed', 'Could not complete the scan.' ) ); }, 35000 );

		try {
			if ( typeof window.scanAddress === 'function' ) { window.scanAddress( addr ); }
			else { fail( root, t( 'failed', 'Could not complete the scan.' ) ); }
		} catch ( e ) {
			fail( root, t( 'failed', 'Could not complete the scan.' ) );
		}
	}

	function initRoot( root ) {
		if ( root.getAttribute( 'data-csc-ready' ) === '1' ) { return; }
		root.setAttribute( 'data-csc-ready', '1' );

		if ( window.__cscScannerBound ) {
			setMsg( root, t( 'onePerPage', 'Only one scanner can run per page. Use a single scanner block here.' ), 'error' );
			var b = root.querySelector( '.csc-scan__btn' );
			if ( b ) { b.disabled = true; }
			return;
		}
		window.__cscScannerBound = true;

		ensureStub();

		var form = root.querySelector( '.csc-scan__form' );
		if ( form ) { form.addEventListener( 'submit', function ( e ) { e.preventDefault(); doScan( root ); } ); }

		window.cscScan = function ( address ) {
			var inp = root.querySelector( '.csc-scan__input' );
			if ( inp ) { inp.value = address; }
			try { root.scrollIntoView( { behavior: 'smooth', block: 'start' } ); } catch ( e ) {}
			doScan( root );
			return true;
		};

		var token = root.getAttribute( 'data-token' );
		if ( token ) {
			var inp = root.querySelector( '.csc-scan__input' );
			if ( inp ) { inp.value = token; }
			doScan( root );
		}
	}

	function init() {
		var roots = document.querySelectorAll( '.csc-scan' );
		for ( var i = 0; i < roots.length; i++ ) { initRoot( roots[ i ] ); }
	}

	ensureStub();

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
