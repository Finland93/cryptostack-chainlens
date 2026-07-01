<?php
/**
 * Central configuration for CryptoStack ChainLens.
 *
 * @package CryptoStack_ChainLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supported chains and their metadata.
 *
 * family   = evm | solana (which security source to use)
 * goplus   = GoPlus chain id ('1','56','8453') or 'solana'
 * dex_id   = DexScreener chainId string
 * explorer = base URL for the token page
 *
 * @return array
 */
function csc_chains() {
	return array(
		'ethereum' => array(
			'label'    => 'Ethereum',
			'family'   => 'evm',
			'goplus'   => '1',
			'dex_id'   => 'ethereum',
			'explorer' => 'https://etherscan.io/token/',
		),
		'bsc'      => array(
			'label'    => 'BNB Chain',
			'family'   => 'evm',
			'goplus'   => '56',
			'dex_id'   => 'bsc',
			'explorer' => 'https://bscscan.com/token/',
		),
		'base'     => array(
			'label'    => 'Base',
			'family'   => 'evm',
			'goplus'   => '8453',
			'dex_id'   => 'base',
			'explorer' => 'https://basescan.org/token/',
		),
		'solana'   => array(
			'label'    => 'Solana',
			'family'   => 'solana',
			'goplus'   => 'solana',
			'dex_id'   => 'solana',
			'explorer' => 'https://solscan.io/token/',
		),
	);
}

/**
 * Chain choices for the selector / settings ('' = Auto-detect).
 *
 * @return array<string,string>
 */
function csc_supported_chains() {
	$out = array( '' => __( 'Auto-detect', 'cryptostack-chainlens' ) );
	foreach ( csc_chains() as $slug => $chain ) {
		$out[ $slug ] = $chain['label'];
	}
	return $out;
}

/**
 * Default plugin settings.
 *
 * @return array
 */
function csc_default_settings() {
	return array(
		'default_chain' => '', // '' = Auto-detect (feed default tab).
		'theme'         => 'dark', // dark | light | auto.
	);
}

