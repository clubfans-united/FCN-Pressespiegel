# FCN Pressespiegel

FCN Pressespiegel WordPress plugin. Formerly part of Clubfans United.

Aggregates news about the 1. FC Nürnberg from a set of configured RSS/Atom feeds
into a custom `pressreview` post type.

## Requirements

- PHP 8.3+
- WordPress 6.4+

## Installation

1. Download the plugin files to your `/wp-content/plugins/` directory, or install the plugin through the WordPress plugin screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

## Usage

### Importing articles

- **WordPress admin:** open **Pressespiegel** (the post type list) and click
  **„Artikel aus Feeds importieren"** to pull in new articles from all feeds.
- **WP-CLI:** see below.

Imports are incremental: only articles newer than the most recent existing
article are imported. When no articles exist yet (fresh install or after
`wp pressreview delete`), everything available in the feeds is imported.

## WP-CLI

```bash
# Import new articles from all configured feeds
wp pressreview import

# Delete all press review articles (asks for confirmation)
wp pressreview delete

# ... without the confirmation prompt
wp pressreview delete --yes
```

## Development

### PHP / Composer

```bash
composer install
```

Run the PHP coding-standard checks (PHP_CodeSniffer, PSR-12):

```bash
composer test
```

### JavaScript / CSS

Install dependencies:

```bash
npm install
```

Build assets for development — rebuilds on change, via
[@wordpress/scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/):

```bash
npm run start
```

Production build:

```bash
npm run build
```

### Linting & Formatting

Linting and formatting are handled by [Biome](https://biomejs.dev/) (configured
in `biome.json`: recommended rules incl. the React domain; formatter set to the
project's JS style — no semicolons, single quotes, no trailing commas, 2-space
indent, 80 columns).

```bash
npm run check        # lint + format check (used in CI)
npm run check-fix    # apply safe lint fixes + format
npm run lint         # lint only
npm run format       # format only (writes)
```

## License

Proprietary — All Rights Reserved. © 2025–2026 Clubfans United. See [LICENSE](LICENSE).
