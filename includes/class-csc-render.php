<?php
/**
 * Frontend rendering: shortcodes, blocks, and asset loading.
 *
 * The scanner runs the bundled ChainLens analysis engine headless (client-side) so
 * its score matches the upstream tool, and paints the result in this plugin's own UI.
 * The new-tokens feed is a separate widget backed by a server-side REST endpoint.
 * Nothing from the ChainLens website is embedded and there is no iframe.
 *
 * @package CryptoStack_ChainLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CSC_Render
 */
class CSC_Render {

	/**
	 * Singleton.
	 *
	 * @var CSC_Render|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return CSC_Render
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
		add_shortcode( 'chainlens', array( $this, 'scanner_shortcode' ) );
		add_shortcode( 'cryptostack_chainlens', array( $this, 'scanner_shortcode' ) );
		add_shortcode( 'chainlens_feed', array( $this, 'feed_shortcode' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register styles and the engine + widget scripts.
	 *
	 * Dependency chain guarantees load order: chains -> api -> analysis -> scanner-ui -> app,
	 * so the Render/Feed stubs and the Analysis.totalScore wrapper are installed before the
	 * engine runs.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( 'csc-chainlens', CSC_URL . 'assets/css/scanner.css', array(), CSC_VERSION );

		// Engine (author's open-source ChainLens code).
		wp_register_script( 'csc-chains', CSC_URL . 'engine/chains.js', array(), CSC_VERSION, true );
		wp_register_script( 'csc-api', CSC_URL . 'engine/api.js', array(), CSC_VERSION, true );
		wp_register_script( 'csc-analysis', CSC_URL . 'engine/analysis.js', array(), CSC_VERSION, true );
		wp_register_script( 'csc-scanner-ui', CSC_URL . 'assets/js/scanner-ui.js', array( 'csc-chains', 'csc-api', 'csc-analysis' ), CSC_VERSION, true );
		wp_register_script( 'csc-app', CSC_URL . 'engine/app.js', array( 'csc-scanner-ui' ), CSC_VERSION, true );

		wp_localize_script(
			'csc-scanner-ui',
			'CSC_SCAN',
			array(
				'i18n' => array(
					'scanning'     => __( 'Scanning…', 'cryptostack-chainlens' ),
					'enterAddr'    => __( 'Please paste a token contract address.', 'cryptostack-chainlens' ),
					'failed'       => __( 'Could not complete the scan. Check the address and try again.', 'cryptostack-chainlens' ),
					'onePerPage'   => __( 'Only one scanner can run per page. Use a single scanner block here.', 'cryptostack-chainlens' ),
					'score'        => __( 'Trust score', 'cryptostack-chainlens' ),
					'checksPassed' => __( 'checks passed', 'cryptostack-chainlens' ),
					'redFlags'     => __( 'Red flags', 'cryptostack-chainlens' ),
					'breakdown'    => __( 'Score breakdown', 'cryptostack-chainlens' ),
					'market'       => __( 'Market', 'cryptostack-chainlens' ),
					'findings'     => __( 'Findings', 'cryptostack-chainlens' ),
					'price'        => __( 'Price', 'cryptostack-chainlens' ),
					'liquidity'    => __( 'Liquidity', 'cryptostack-chainlens' ),
					'mcap'         => __( 'Market cap', 'cryptostack-chainlens' ),
					'vol'          => __( '24h volume', 'cryptostack-chainlens' ),
					'holders'      => __( 'Holders', 'cryptostack-chainlens' ),
					'age'          => __( 'Pair age', 'cryptostack-chainlens' ),
					'explorer'     => __( 'Explorer', 'cryptostack-chainlens' ),
				),
			)
		);

		// Feed widget.
		wp_register_script( 'csc-feed-ui', CSC_URL . 'assets/js/feed-ui.js', array(), CSC_VERSION, true );
		wp_localize_script(
			'csc-feed-ui',
			'CSC_FEED',
			array(
				'rest'  => esc_url_raw( rest_url( 'cryptostack-chainlens/v1/feed' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'i18n'  => array(
					'loading' => __( 'Loading…', 'cryptostack-chainlens' ),
					'empty'   => __( 'No new tokens right now.', 'cryptostack-chainlens' ),
					'failed'  => __( 'Could not load new tokens.', 'cryptostack-chainlens' ),
					'scan'    => __( 'Scan', 'cryptostack-chainlens' ),
				),
			)
		);
	}

	/* --------------------------------------------------------------------- */
	/* Scanner                                                               */
	/* --------------------------------------------------------------------- */

	/**
	 * Resolve a theme value, falling back to the saved setting.
	 *
	 * @param string $theme Requested theme or ''.
	 * @return string dark|light|auto.
	 */
	private function resolve_theme( $theme ) {
		$theme = sanitize_key( (string) $theme );
		if ( in_array( $theme, array( 'dark', 'light', 'auto' ), true ) ) {
			return $theme;
		}
		$s = CSC_Settings::get();
		return in_array( $s['theme'], array( 'dark', 'light', 'auto' ), true ) ? $s['theme'] : 'dark';
	}

	/**
	 * Render the scanner widget.
	 *
	 * @param array $args token (optional preloaded address).
	 * @return string
	 */
	public function render_scanner( $args = array() ) {
		$args  = wp_parse_args( $args, array( 'token' => '', 'theme' => '' ) );
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $args['token'] );
		$theme = $this->resolve_theme( $args['theme'] );

		wp_enqueue_style( 'csc-chainlens' );
		wp_enqueue_script( 'csc-app' ); // Pulls chains/api/analysis/scanner-ui via dependencies.

		ob_start();
		?>
		<div class="csc-scan csc-theme-<?php echo esc_attr( $theme ); ?>" data-theme="<?php echo esc_attr( $theme ); ?>" data-token="<?php echo esc_attr( $token ); ?>">
			<div class="csc-scan__head">
				<span class="csc-scan__logo" aria-hidden="true">&#9672;</span>
				<div class="csc-scan__titles">
					<strong><?php echo esc_html__( 'Token Safety Scanner', 'cryptostack-chainlens' ); ?></strong>
					<span><?php echo esc_html__( 'Paste any token address for an instant risk report', 'cryptostack-chainlens' ); ?></span>
				</div>
			</div>

			<form class="csc-scan__form" novalidate>
				<input type="text" class="csc-scan__input" autocomplete="off" autocapitalize="off" spellcheck="false"
					placeholder="<?php echo esc_attr__( 'Paste token contract address…', 'cryptostack-chainlens' ); ?>" />
				<button type="submit" class="csc-scan__btn"><?php echo esc_html__( 'Scan', 'cryptostack-chainlens' ); ?></button>
			</form>

			<div class="csc-scan__msg" role="status" aria-live="polite"></div>
			<div class="csc-scan__result" hidden></div>

			<div class="csc-scan__foot">
				<?php echo esc_html__( 'Automated analysis, not financial advice. Always do your own research.', 'cryptostack-chainlens' ); ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Scanner shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function scanner_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'token' => '', 'theme' => '' ), $atts, 'chainlens' );
		return $this->render_scanner( $atts );
	}

	/* --------------------------------------------------------------------- */
	/* Feed                                                                  */
	/* --------------------------------------------------------------------- */

	/**
	 * Render the new-tokens feed widget.
	 *
	 * @param array $args chain (default tab).
	 * @return string
	 */
	public function render_feed( $args = array() ) {
		$s     = CSC_Settings::get();
		$args  = wp_parse_args( $args, array( 'chain' => $s['default_chain'], 'theme' => '' ) );
		$chain = sanitize_key( (string) $args['chain'] );
		if ( ! array_key_exists( $chain, csc_chains() ) ) {
			$chain = 'ethereum';
		}
		$theme = $this->resolve_theme( $args['theme'] );

		wp_enqueue_style( 'csc-chainlens' );
		wp_enqueue_script( 'csc-feed-ui' );

		$tabs = array(
			'ethereum' => 'ETH',
			'base'     => 'BASE',
			'bsc'      => 'BNB',
			'solana'   => 'SOL',
		);

		ob_start();
		?>
		<div class="csc-feed csc-theme-<?php echo esc_attr( $theme ); ?>" data-theme="<?php echo esc_attr( $theme ); ?>" data-chain="<?php echo esc_attr( $chain ); ?>">
			<div class="csc-feed__head">
				<span class="csc-feed__logo" aria-hidden="true">&#9670;</span>
				<span class="csc-feed__title"><?php echo esc_html__( 'New tokens', 'cryptostack-chainlens' ); ?></span>
				<button type="button" class="csc-feed__refresh" aria-label="<?php echo esc_attr__( 'Refresh', 'cryptostack-chainlens' ); ?>">&#8635;</button>
			</div>
			<div class="csc-feed__tabs">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<button type="button" class="csc-feed__tab<?php echo ( $slug === $chain ) ? ' is-active' : ''; ?>" data-chain="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="csc-feed__list"></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Feed shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function feed_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'chain' => '', 'theme' => '' ), $atts, 'chainlens_feed' );
		return $this->render_feed( $atts );
	}

	/* --------------------------------------------------------------------- */
	/* Blocks                                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * Register both blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'csc-block',
			CSC_URL . 'blocks/chainlens/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			CSC_VERSION,
			true
		);
		wp_register_script(
			'csc-feed-block',
			CSC_URL . 'blocks/feed/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			CSC_VERSION,
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'csc-block', 'cryptostack-chainlens' );
			wp_set_script_translations( 'csc-feed-block', 'cryptostack-chainlens' );
		}

		register_block_type(
			CSC_DIR . 'blocks/chainlens',
			array( 'render_callback' => array( $this, 'render_scanner_block' ) )
		);
		register_block_type(
			CSC_DIR . 'blocks/feed',
			array( 'render_callback' => array( $this, 'render_feed_block' ) )
		);
	}

	/**
	 * Scanner block render callback.
	 *
	 * @param array $attributes Attributes.
	 * @return string
	 */
	public function render_scanner_block( $attributes ) {
		$attributes = is_array( $attributes ) ? $attributes : array();
		$args       = array();
		if ( isset( $attributes['token'] ) ) {
			$args['token'] = $attributes['token'];
		}
		if ( isset( $attributes['theme'] ) ) {
			$args['theme'] = $attributes['theme'];
		}
		return $this->render_scanner( $args );
	}

	/**
	 * Feed block render callback.
	 *
	 * @param array $attributes Attributes.
	 * @return string
	 */
	public function render_feed_block( $attributes ) {
		$attributes = is_array( $attributes ) ? $attributes : array();
		$args       = array();
		if ( isset( $attributes['chain'] ) ) {
			$args['chain'] = $attributes['chain'];
		}
		if ( isset( $attributes['theme'] ) ) {
			$args['theme'] = $attributes['theme'];
		}
		return $this->render_feed( $args );
	}
}
