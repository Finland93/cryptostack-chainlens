<?php
/**
 * New-tokens feed service.
 *
 * Exposes a REST endpoint that returns recently created pools for a chain,
 * fetched SERVER-SIDE from the public GeckoTerminal API. Powers the
 * "new tokens" widget (ETH | BASE | BNB | SOL tabs). Cached + rate-limited.
 *
 * @package CryptoStack_ChainLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CSC_Feed
 */
class CSC_Feed {

	/**
	 * Singleton.
	 *
	 * @var CSC_Feed|null
	 */
	private static $instance = null;

	const CACHE_TTL   = 90;
	const RATE_LIMIT  = 60;
	const RATE_WINDOW = 60;

	/**
	 * Map our chain slugs to GeckoTerminal network ids.
	 *
	 * @var array
	 */
	private $gt = array(
		'ethereum' => 'eth',
		'bsc'      => 'bsc',
		'base'     => 'base',
		'solana'   => 'solana',
	);

	/**
	 * Get instance.
	 *
	 * @return CSC_Feed
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the REST route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'cryptostack-chainlens/v1',
			'/feed',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'handle_feed' ),
				'args'                => array(
					'chain' => array(
						'required' => false,
						'type'     => 'string',
						'default'  => 'ethereum',
					),
				),
			)
		);
	}

	/**
	 * REST callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function handle_feed( WP_REST_Request $request ) {
		$chain = sanitize_key( (string) $request->get_param( 'chain' ) );
		if ( ! isset( $this->gt[ $chain ] ) ) {
			$chain = 'ethereum';
		}

		if ( $this->is_rate_limited() ) {
			return new WP_REST_Response(
				array(
					'ok'    => false,
					'error' => __( 'Please wait a moment before refreshing.', 'cryptostack-chainlens' ),
				),
				200
			);
		}

		$cache_key = 'csc_feed_' . $chain;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return new WP_REST_Response( $cached, 200 );
		}

		$items = $this->fetch_new_pools( $chain );
		if ( null === $items ) {
			return new WP_REST_Response(
				array(
					'ok'    => false,
					'error' => __( 'Could not load new tokens right now.', 'cryptostack-chainlens' ),
				),
				200
			);
		}

		$payload = array(
			'ok'    => true,
			'chain' => $chain,
			'items' => $items,
		);
		set_transient( $cache_key, $payload, self::CACHE_TTL );

		return new WP_REST_Response( $payload, 200 );
	}

	/**
	 * Fetch + normalize new pools from GeckoTerminal.
	 *
	 * @param string $chain Our chain slug.
	 * @return array|null
	 */
	private function fetch_new_pools( $chain ) {
		$network = $this->gt[ $chain ];
		$url     = 'https://api.geckoterminal.com/api/v2/networks/' . rawurlencode( $network ) . '/new_pools?page=1';

		$json = $this->get_json( $url );
		if ( ! is_array( $json ) || empty( $json['data'] ) || ! is_array( $json['data'] ) ) {
			return null;
		}

		$out = array();
		foreach ( $json['data'] as $pool ) {
			if ( ! is_array( $pool ) || empty( $pool['attributes'] ) ) {
				continue;
			}
			$attr = $pool['attributes'];

			// Token address from the base_token relationship id "{network}_{address}".
			$address = '';
			if ( isset( $pool['relationships']['base_token']['data']['id'] ) ) {
				$id      = (string) $pool['relationships']['base_token']['data']['id'];
				$address = preg_replace( '/^' . preg_quote( $network, '/' ) . '_/', '', $id );
			}
			if ( '' === $address ) {
				continue;
			}

			// Symbol from the pool name "SYMBOL / QUOTE".
			$name   = isset( $attr['name'] ) ? (string) $attr['name'] : '';
			$symbol = $name;
			if ( false !== strpos( $name, ' / ' ) ) {
				$parts  = explode( ' / ', $name );
				$symbol = trim( $parts[0] );
			}

			$liq      = isset( $attr['reserve_in_usd'] ) ? (float) $attr['reserve_in_usd'] : 0.0;
			$created  = isset( $attr['pool_created_at'] ) ? strtotime( (string) $attr['pool_created_at'] ) : 0;
			$age_min  = $created > 0 ? max( 0, (int) floor( ( time() - $created ) / 60 ) ) : -1;

			$out[] = array(
				'symbol'  => sanitize_text_field( $symbol ),
				'name'    => sanitize_text_field( $name ),
				'address' => preg_replace( '/[^A-Za-z0-9]/', '', $address ),
				'chain'   => $chain,
				'liq'     => $this->fmt_usd( $liq ),
				'age'     => $this->fmt_age( $age_min ),
			);

			if ( count( $out ) >= 15 ) {
				break;
			}
		}

		return $out;
	}

	/**
	 * GET JSON helper.
	 *
	 * @param string $url URL.
	 * @return array|null
	 */
	private function get_json( $url ) {
		$res = wp_remote_get(
			$url,
			array(
				'timeout' => 12,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			return null;
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Per-IP rate limit.
	 *
	 * @return bool
	 */
	private function is_rate_limited() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'csc_frl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= self::RATE_LIMIT ) {
			return true;
		}
		set_transient( $key, $n + 1, self::RATE_WINDOW );
		return false;
	}

	/**
	 * Compact USD.
	 *
	 * @param float $n Amount.
	 * @return string
	 */
	private function fmt_usd( $n ) {
		$n = (float) $n;
		if ( $n >= 1000000 ) {
			return '$' . number_format( $n / 1000000, 1 ) . 'M';
		}
		if ( $n >= 1000 ) {
			return '$' . number_format( $n / 1000, 1 ) . 'K';
		}
		return '$' . number_format( $n, 0 );
	}

	/**
	 * Compact age from minutes.
	 *
	 * @param int $min Minutes.
	 * @return string
	 */
	private function fmt_age( $min ) {
		$min = (int) $min;
		if ( $min < 0 ) {
			return '';
		}
		if ( $min < 60 ) {
			/* translators: %d: minutes. */
			return sprintf( __( '%dm', 'cryptostack-chainlens' ), $min );
		}
		if ( $min < 1440 ) {
			/* translators: %d: hours. */
			return sprintf( __( '%dh', 'cryptostack-chainlens' ), (int) floor( $min / 60 ) );
		}
		/* translators: %d: days. */
		return sprintf( __( '%dd', 'cryptostack-chainlens' ), (int) floor( $min / 1440 ) );
	}
}
