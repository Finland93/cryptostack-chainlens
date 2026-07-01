<?php
/**
 * Plugin Name:       CryptoStack ChainLens
 * Plugin URI:        https://github.com/Finland93/cryptostack-chainlens
 * Description:        A lightweight crypto token safety scanner for your site. Visitors paste a token address and get an instant on-chain risk report (honeypot, taxes, mint authority, liquidity lock, holder concentration and more). Shortcode and block. No API keys.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Finland93
 * Author URI:        https://github.com/Finland93
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cryptostack-chainlens
 * Domain Path:       /languages
 *
 * @package CryptoStack_ChainLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'CSC_VERSION', '0.1.0' );
define( 'CSC_FILE', __FILE__ );
define( 'CSC_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSC_URL', plugin_dir_url( __FILE__ ) );
define( 'CSC_BASENAME', plugin_basename( __FILE__ ) );

// Option key holding per-site settings.
define( 'CSC_OPTION_KEY', 'csc_settings' );

require_once CSC_DIR . 'includes/config.php';
require_once CSC_DIR . 'includes/class-csc-feed.php';
require_once CSC_DIR . 'includes/class-csc-settings.php';
require_once CSC_DIR . 'includes/class-csc-render.php';

/**
 * Boot the plugin.
 *
 * @return void
 */
function csc_bootstrap() {
	CSC_Feed::instance();     // New-tokens feed REST endpoint.
	CSC_Settings::instance(); // Admin settings screen.
	CSC_Render::instance();   // Shortcode + block + assets.

	add_filter(
		'plugin_action_links_' . CSC_BASENAME,
		static function ( $links ) {
			$url      = admin_url( 'options-general.php?page=cryptostack-chainlens' );
			$settings = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'cryptostack-chainlens' ) . '</a>';
			array_unshift( $links, $settings );
			return $links;
		}
	);
}
add_action( 'plugins_loaded', 'csc_bootstrap' );

/**
 * Seed default settings on activation.
 *
 * @return void
 */
function csc_activate() {
	if ( false === get_option( CSC_OPTION_KEY ) ) {
		add_option( CSC_OPTION_KEY, csc_default_settings() );
	}
}
register_activation_hook( CSC_FILE, 'csc_activate' );
