<?php
/**
 * Admin settings screen.
 *
 * @package CryptoStack_ChainLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CSC_Settings
 */
class CSC_Settings {

	/**
	 * Singleton.
	 *
	 * @var CSC_Settings|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return CSC_Settings
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Merged settings.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( CSC_OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, csc_default_settings() );
	}

	/**
	 * Register the option.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'csc_settings_group',
			CSC_OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => csc_default_settings(),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = csc_default_settings();

		$chain = isset( $input['default_chain'] ) ? sanitize_key( (string) $input['default_chain'] ) : '';
		if ( ! array_key_exists( $chain, csc_supported_chains() ) ) {
			$chain = '';
		}
		$out['default_chain'] = $chain;

		$theme = isset( $input['theme'] ) ? sanitize_key( (string) $input['theme'] ) : 'dark';
		if ( ! in_array( $theme, array( 'dark', 'light', 'auto' ), true ) ) {
			$theme = 'dark';
		}
		$out['theme'] = $theme;

		return $out;
	}

	/**
	 * Add the settings page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_options_page(
			__( 'CryptoStack ChainLens', 'cryptostack-chainlens' ),
			__( 'CryptoStack ChainLens', 'cryptostack-chainlens' ),
			'manage_options',
			'cryptostack-chainlens',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the settings + help page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s      = self::get();
		$chains = csc_supported_chains();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'CryptoStack ChainLens', 'cryptostack-chainlens' ); ?></h1>

			<form action="options.php" method="post">
				<?php settings_fields( 'csc_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="csc_default_chain"><?php echo esc_html__( 'Default chain', 'cryptostack-chainlens' ); ?></label>
						</th>
						<td>
							<select name="<?php echo esc_attr( CSC_OPTION_KEY ); ?>[default_chain]" id="csc_default_chain">
								<?php foreach ( $chains as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $s['default_chain'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php echo esc_html__( 'Starting tab for the New Tokens feed. The scanner always auto-detects the chain from the address.', 'cryptostack-chainlens' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="csc_theme"><?php echo esc_html__( 'Theme', 'cryptostack-chainlens' ); ?></label>
						</th>
						<td>
							<select name="<?php echo esc_attr( CSC_OPTION_KEY ); ?>[theme]" id="csc_theme">
								<option value="dark" <?php selected( $s['theme'], 'dark' ); ?>><?php echo esc_html__( 'Dark', 'cryptostack-chainlens' ); ?></option>
								<option value="light" <?php selected( $s['theme'], 'light' ); ?>><?php echo esc_html__( 'Light', 'cryptostack-chainlens' ); ?></option>
								<option value="auto" <?php selected( $s['theme'], 'auto' ); ?>><?php echo esc_html__( 'Auto (match visitor device)', 'cryptostack-chainlens' ); ?></option>
							</select>
							<p class="description"><?php echo esc_html__( 'Colour scheme for the scanner and feed. Individual blocks can override this.', 'cryptostack-chainlens' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr />

			<h2><?php echo esc_html__( 'How to add the widgets', 'cryptostack-chainlens' ); ?></h2>
			<p><strong><?php echo esc_html__( 'Scanner', 'cryptostack-chainlens' ); ?></strong> — <?php echo esc_html__( 'add the "ChainLens Token Scanner" block, or the shortcode:', 'cryptostack-chainlens' ); ?> <code>[chainlens]</code></p>
			<p class="description"><code>[chainlens token="0x..."]</code> — <?php echo esc_html__( 'optional: a contract address to scan automatically on page load. The chain is auto-detected. Use one scanner per page.', 'cryptostack-chainlens' ); ?></p>

			<p style="margin-top:1rem"><strong><?php echo esc_html__( 'New Tokens feed', 'cryptostack-chainlens' ); ?></strong> — <?php echo esc_html__( 'add the "ChainLens New Tokens" block, or the shortcode:', 'cryptostack-chainlens' ); ?> <code>[chainlens_feed]</code></p>
			<p class="description"><code>[chainlens_feed chain="base"]</code> — <?php echo esc_html__( 'optional: the starting tab (ethereum, base, bsc or solana). Placing the feed and the scanner on the same page lets visitors click a new token to scan it.', 'cryptostack-chainlens' ); ?></p>

			<hr />
			<p class="description">
				<?php echo esc_html__( 'How it works: the scanner runs the open-source ChainLens analysis engine directly in the visitor\'s browser, so the score matches the ChainLens website exactly. The browser queries public blockchain APIs (such as DexScreener, GoPlus, Honeypot.is, RugCheck and others). The New Tokens feed is fetched by your server from GeckoTerminal. The plugin collects and stores no personal visitor data.', 'cryptostack-chainlens' ); ?>
			</p>
		</div>
		<?php
	}
}
