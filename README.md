# CryptoStack ChainLens

![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![License](https://img.shields.io/badge/License-GPLv2%2B-3da639)

A crypto **token safety scanner** and a live **new-tokens feed** for WordPress. Visitors paste a token address and get an instant on-chain risk report; the feed lists newly listed tokens with ETH / BASE / BNB / SOL tabs.

The scanner is powered by the open-source [ChainLens](https://github.com/Finland93/ChainLens) analysis engine, run headless in the visitor's browser and painted in the plugin's own UI — so **the score matches the ChainLens website exactly**. No iframe, no site embed, no API keys.

> Want to see it first? Open **`chainlens-ui-preview.html`** in a browser — it renders the UI with sample data in both themes.

## Features

- **Token Safety Scanner** — 0–100 trust score, verdict, per-category score breakdown (contract security, liquidity, holders, trade patterns, bots, price, social, deployer), red flags, market data and links. Chain auto-detected.
- **New Tokens Feed** — live newly-listed tokens with **ETH | BASE | BNB | SOL** tabs. On a page with the scanner, clicking a token scans it.
- **Dark / Light / Auto** themes, selectable globally in settings or per block.
- **Full-width**, self-contained, responsive, accessible, no external fonts or CDNs.
- Shortcodes and blocks for both widgets.
- Supported chains: **Ethereum, BNB Chain, Base, Solana**.

## How it works

- **Scanner (client-side).** The bundled ChainLens engine runs in the visitor's browser, queries public blockchain APIs directly, and computes the score. The plugin captures that result and renders it in its own interface. Same engine → identical score.
- **Feed (server-side).** Your site fetches newly created pools from GeckoTerminal via a cached, rate-limited REST endpoint.

## Installation

**From a release**
1. Download the plugin zip from the [Releases](../../releases) page.
2. WordPress admin → **Plugins → Add New → Upload Plugin** → choose the zip → **Install** → **Activate**.

**Manual**
1. Copy this repository's contents into `wp-content/plugins/cryptostack-chainlens/`.
2. Activate **CryptoStack ChainLens** from the Plugins screen.

## Usage

**Scanner**
```
[chainlens]
[chainlens token="0x..." theme="light"]
```

**New Tokens feed**
```
[chainlens_feed]
[chainlens_feed chain="base" theme="dark"]
```

Or add the **ChainLens Token Scanner** and **ChainLens New Tokens** blocks in the editor. Attributes: `token` (scanner, optional preloaded address), `chain` (feed default tab: `ethereum` / `base` / `bsc` / `solana`), `theme` (`dark` / `light` / `auto`, or blank to use the site setting).

Settings live under **Settings → CryptoStack ChainLens** (theme + default feed tab). Use one scanner per page.

## External services

To build reports, the scanner's browser-side engine contacts public APIs (DexScreener, GeckoTerminal, GoPlus, Honeypot.is, RugCheck, Jupiter, CoinGecko, Birdeye, De.Fi, Solscan) — some via the public CORS proxies corsproxy.io and allorigins.win. The feed contacts GeckoTerminal from your server. Only the token address being scanned is sent; no personal visitor data is collected or stored. Full details and links are in [`readme.txt`](readme.txt).

## Development & release

- PHP is syntax-checked on push/PR (`.github/workflows/lint.yml`).
- Publishing a GitHub **release** deploys to WordPress.org via `.github/workflows/deploy.yml` (10up action). Add repository secrets `SVN_USERNAME` and `SVN_PASSWORD`, and put store assets (icon, banner, screenshots) in `.wordpress-org/`.
- `.distignore` controls what ships to WordPress.org (dev files are excluded).

## Credits

Analysis engine: [ChainLens](https://github.com/Finland93/ChainLens) by Finland93 (MIT). Part of the **CryptoStack** family, alongside [CryptoStack Donations](https://github.com/Finland93/cryptostack-donations).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
