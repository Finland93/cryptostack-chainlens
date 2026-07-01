# Changelog

All notable changes to this project are documented here. This project adheres to
[Semantic Versioning](https://semver.org/).

## [0.1.0] - 2026-07-01
### Added
- Token Safety Scanner powered by the open-source ChainLens engine (run headless in the
  browser), rendered in the plugin's own UI so the score matches the ChainLens site.
- New Tokens feed widget with ETH / BASE / BNB / SOL tabs, backed by a server-side
  GeckoTerminal REST endpoint with caching and rate limiting.
- Dark / Light / Auto themes, selectable in settings or per block.
- Full-width, self-contained UI with no external fonts or CDNs.
- Shortcodes `[chainlens]` and `[chainlens_feed]`, plus matching blocks.
