/* CryptoStack ChainLens — scanner block editor (no build step). */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';
	var el = element.createElement, Fragment = element.Fragment, __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps, InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody, TextControl = components.TextControl, SelectControl = components.SelectControl;

	var box = { border: '1px solid #262a37', borderRadius: '14px', background: 'linear-gradient(135deg,#12141c,#1b1f2a)', color: '#f6f7fc', padding: '1.75rem 1.25rem', textAlign: 'center' };
	var badge = { display: 'inline-block', fontWeight: 700, fontSize: '0.85rem', color: '#fff', background: 'linear-gradient(135deg,#8b7bff,#06b6d4)', padding: '0.35rem 0.85rem', borderRadius: '999px' };

	blocks.registerBlockType( 'cryptostack/chainlens', {
		attributes: { token: { type: 'string', default: '' }, theme: { type: 'string', default: '' } },
		edit: function ( props ) {
			var a = props.attributes, bp = useBlockProps( { style: box } );
			return el( Fragment, {},
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Scanner settings', 'cryptostack-chainlens' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Preloaded token address (optional)', 'cryptostack-chainlens' ),
							help: __( 'If set, this token is scanned automatically when the page loads.', 'cryptostack-chainlens' ),
							value: a.token,
							onChange: function ( v ) { props.setAttributes( { token: ( v || '' ).replace( /[^A-Za-z0-9]/g, '' ) } ); }
						} ),
						el( SelectControl, {
						label: __( 'Theme', 'cryptostack-chainlens' ),
						value: a.theme,
						options: [
							{ label: __( 'Site default', 'cryptostack-chainlens' ), value: '' },
							{ label: __( 'Dark', 'cryptostack-chainlens' ), value: 'dark' },
							{ label: __( 'Light', 'cryptostack-chainlens' ), value: 'light' },
							{ label: __( 'Auto', 'cryptostack-chainlens' ), value: 'auto' }
						],
						onChange: function ( v ) { props.setAttributes( { theme: v } ); }
					} )
					)
				),
				el( 'div', bp,
					el( 'span', { style: badge }, '\uD83D\uDD0D ChainLens' ),
					el( 'p', { style: { fontSize: '1.05rem', fontWeight: 700, margin: '0.8rem 0 0.3rem' } }, __( 'Token Safety Scanner', 'cryptostack-chainlens' ) ),
					el( 'p', { style: { fontSize: '0.85rem', margin: 0, color: '#aab1c6' } }, __( 'Visitors scan any token here on the published page.', 'cryptostack-chainlens' ) )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
