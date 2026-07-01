<?php
/**
 * Uninstall routine — removes plugin options.
 *
 * @package CryptoStack_ChainLens
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'csc_settings' );
