=== CryptoStack ChainLens ===
Contributors: axndata
Tags: crypto, token scanner, honeypot, blockchain, defi
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A crypto token safety scanner and a live new-tokens feed for your site. Instant on-chain risk reports, powered by the open-source ChainLens engine.

== Description ==

**CryptoStack ChainLens** adds two lightweight, self-contained crypto widgets to any page or post:

1. **Token Safety Scanner** — a visitor pastes a token contract address and gets an instant risk report: a 0-100 trust score, a plain-language verdict, a per-category score breakdown, detected red flags, key market data and links. The chain is auto-detected.
2. **New Tokens Feed** — a live list of newly listed tokens with **ETH | BASE | BNB | SOL** tabs. If the scanner is on the same page, clicking a token scans it instantly.

The scanner is powered by the **open-source ChainLens analysis engine** ( https://github.com/Finland93/ChainLens ), run directly in the visitor's browser. Because it is the same engine, **the score matches the ChainLens website exactly**. The plugin wraps that engine in its own clean, purpose-built interface - it is **not** an iframe and does not embed the ChainLens website.

There is no signup, no API key configuration, and no wallet connection.

= What the scanner reports =

* A **0-100 trust score** with the engine's verdict (Low / Moderate / Elevated / High / Extreme risk).
* A **per-category breakdown**: contract security, liquidity health, holder distribution, trade patterns, bot activity, price behavior, social/metadata and deployer history.
* **Red flags** such as honeypot, artificial volume, supply concentration and deployer track record.
* **Market data**: price, liquidity, market cap / FDV, 24h volume, holder count and pair age.
* Links to the token on DexScreener and the relevant block explorer.

Supported chains: **Ethereum, BNB Chain, Base and Solana**.

= Part of CryptoStack =

CryptoStack ChainLens is part of the CryptoStack family of WordPress plugins for crypto creators, alongside CryptoStack Donations.

= External services =

This plugin relies on third-party services to build its reports. No API keys are required, and the plugin adds no advertising or tracking.

**Scanner (runs in the visitor's browser).** When a visitor scans a token, their browser queries public blockchain-data APIs directly. Only the token address being scanned is sent. The APIs are:

* DexScreener - https://api.dexscreener.com - market data and chain detection. Terms: https://docs.dexscreener.com/ Privacy: https://dexscreener.com/privacy-policy
* GeckoTerminal - https://api.geckoterminal.com - pool/price data. https://www.geckoterminal.com/
* GoPlus Security - https://api.gopluslabs.io - contract/token security. https://gopluslabs.io/
* Honeypot.is - https://api.honeypot.is - honeypot simulation (EVM). https://honeypot.is/
* RugCheck - https://api.rugcheck.xyz - Solana token risk. https://rugcheck.xyz/
* Jupiter - https://api.jup.ag - Solana price. https://jup.ag/
* CoinGecko - https://api.coingecko.com - listing check. https://www.coingecko.com/en/api
* Birdeye - https://public-api.birdeye.so - Solana overview/security. https://birdeye.so/
* De.Fi - https://public-api.de.fi - EVM risk shields. https://de.fi/
* Solscan - https://public-api.solscan.io - Solana holders. https://solscan.io/

Some of these requests are routed through public CORS proxies when a service does not send browser-friendly CORS headers:

* corsproxy.io - https://corsproxy.io - https://corsproxy.io/
* AllOrigins - https://api.allorigins.win - https://allorigins.win/

**New Tokens feed (runs on your server).** To build the feed, your server requests newly created pools from GeckoTerminal ( https://api.geckoterminal.com ). Results are cached briefly and requests are rate-limited.

= Disclaimer =

This tool is for research and does **not** constitute financial advice. No score guarantees a token's safety or profitability, and automated checks can be incomplete or wrong. Cryptocurrency carries significant risk, including total loss of capital. Always do your own research.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/cryptostack-chainlens`, or install through the **Plugins** screen in WordPress.
2. Activate the plugin through the **Plugins** screen.
3. Add the **ChainLens Token Scanner** block (or `[chainlens]`) and/or the **ChainLens New Tokens** block (or `[chainlens_feed]`) to any page, post or widget area.
4. (Optional) Visit **Settings - CryptoStack ChainLens** to set the feed's default tab.

== Frequently Asked Questions ==

= Does the score match the ChainLens website? =

Yes. The scanner runs the same open-source ChainLens analysis engine in the visitor's browser, so the resulting score is identical.

= How do I add the widgets? =

Use the **ChainLens Token Scanner** and **ChainLens New Tokens** blocks, or the `[chainlens]` and `[chainlens_feed]` shortcodes.

= Can I scan a specific token automatically? =

Yes: `[chainlens token="0x..."]` scans that token on page load.

= Can visitors scan tokens from the feed? =

Yes, when the feed and the scanner are on the same page. Clicking a token in the feed fills the scanner and runs it.

= Does it need an API key? =

No. It uses public APIs that do not require keys.

= Does it collect visitor data? =

No. The plugin adds no tracking and stores no analysis data beyond a short-lived server-side cache of the public feed. Only the token address a visitor enters is sent to the data providers listed above.

= Which blockchains are supported? =

Ethereum, BNB Chain, Base and Solana.

== Screenshots ==

1. The scanner widget, ready for a token address.
2. A completed report: trust score, verdict, score breakdown, red flags and market data.
3. The New Tokens feed with ETH / BASE / BNB / SOL tabs.
4. The block settings panel in the editor.

== Changelog ==

= 0.1.0 =
* Initial release.
* Token Safety Scanner powered by the open-source ChainLens engine (run headless in the browser), rendered in the plugin's own UI so the score matches the ChainLens site.
* New Tokens feed widget with ETH / BASE / BNB / SOL tabs, backed by a server-side GeckoTerminal REST endpoint with caching and rate limiting.
* Shortcodes `[chainlens]` and `[chainlens_feed]`, plus matching blocks. No iframe, no external site embed.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
