# WordPress.org plugin assets

Drop the store assets here. On a published GitHub release, the deploy workflow
uploads everything in this folder to the plugin's SVN `assets/` directory.

Expected files (PNG or JPG):

- `icon-128x128.png` and `icon-256x256.png` — plugin icon
- `banner-772x250.png` and `banner-1544x500.png` — header banner
- `screenshot-1.png` … `screenshot-4.png` — match the order in `readme.txt`
  1. The scanner widget, ready for a token address
  2. A completed report: score, verdict, breakdown, red flags, market
  3. The New Tokens feed with ETH / BASE / BNB / SOL tabs
  4. The block settings panel in the editor

Tip: `chainlens-ui-preview.html` in the repo root renders the UI with sample
data — a quick way to produce screenshots.
