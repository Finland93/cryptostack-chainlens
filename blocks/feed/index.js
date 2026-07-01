/* CryptoStack ChainLens — new-tokens feed block editor (no build step). */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';
	var el = element.createElement, Fragment = element.Fragment, __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps, InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody, SelectControl = components.SelectControl;

	var box = { border: '1px solid #262a37', borderRadius: '14px', background: 'linear-gradient(135deg,#12141c,#1b1f2a)', color: '#f6f7fc', padding: '1.5rem 1.25rem', textAlign: 'center' };
	var tabs = { display: 'inline-flex', gap: '4px', marginTop: '0.7rem' };
	var tab = { fontSize: '0.7rem', fontWeight: 700, color: '#aab1c6', border: '1px solid #2c3142', borderRadius: '7px', padding: '0.35rem 0.6rem' };

	blocks.registerBlockType( 'cryptostack/chainlens-feed', {
		attributes: { chain: { type: 'string', default: '' }, theme: { type: 'string', default: '' } },
		edit: function ( props ) {
			var a = props.attributes, bp = useBlockProps( { style: box } );
			return el( Fragment, {},
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Feed settings', 'cryptostack-chainlens' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Default tab', 'cryptostack-chainlens' ),
							value: a.chain,
							options: [
								{ label: 'ETH', value: 'ethereum' },
								{ label: 'BASE', value: 'base' },
								{ label: 'BNB', value: 'bsc' },
								{ label: 'SOL', value: 'solana' }
							],
							onChange: function ( v ) { props.setAttributes( { chain: v } ); }
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
					el( 'p', { style: { fontSize: '1rem', fontWeight: 700, margin: '0 0 0.2rem' } }, __( 'New Tokens Feed', 'cryptostack-chainlens' ) ),
					el( 'p', { style: { fontSize: '0.8rem', margin: 0, color: '#aab1c6' } }, __( 'Newly listed tokens, updated live.', 'cryptostack-chainlens' ) ),
					el( 'div', { style: tabs },
						el( 'span', { style: tab }, 'ETH' ), el( 'span', { style: tab }, 'BASE' ),
						el( 'span', { style: tab }, 'BNB' ), el( 'span', { style: tab }, 'SOL' )
					)
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
