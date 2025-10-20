const path = require( 'path' );

/**
 * WordPress Dependencies
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );
const defaultEntryPoints = defaultConfig.entry();
const entryPoints = {
  ...defaultEntryPoints,
    'pressreview-edit' : path.resolve('js/pressreview-edit.js'),
  'PressreviewThis' : path.resolve('js/PressreviewThis/index.js')
}

module.exports = {
  ...defaultConfig,
  ...{
    entry: entryPoints
  }
}
