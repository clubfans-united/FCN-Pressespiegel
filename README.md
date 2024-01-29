# FCN Pressespiegel
FCN Pressespiegel Wordpress Plugin. Formerly part of Clubfans United.

## Requirements

- PHP version 8.2
- WordPress 6.*

## Installation

To install the plugin, follow these steps:

1. Download the plugin files to your `/wp-content/plugins/` directory, or install the plugin through the WordPress plugin screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

## Build

## PHP/Composer

```bash 
composer install
```

## Build (or develop) CSS & JS

Use [npm](https://nodejs.org/en/) to install JavaScript and CSS dependencies.

```bash 
npm install
```

### Build Assets JS/JSX/Blocks (with [@wordpress/scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/))

Transforms your code according the configuration provided so it’s ready for development. The script will automatically
rebuild if you make changes to the code, and you will see the build errors in the console.

```bash 
npm run start
```

You may find that in certain environments Webpack isn't updating when your files change. If this is the case on your
system, consider using the `watch-poll` command:

## Linting & Formatting

### Linting
- [ESLint](https://eslint.org/) with [JavaScript Standard Style](https://standardjs.com/)

### Formatting
- [Prettier](https://prettier.io/) with [eslint-config-prettier-standard](https://www.npmjs.com/package/eslint-config-prettier-standard)

