const path = require( 'path' );

/**
 * WordPress Dependencies
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );
const defaultEntryPoints = defaultConfig.entry();
const entryPoints = {
  ...defaultEntryPoints,
  'PressreviewThis' : path.resolve('js/PressreviewThis/index.js')
}

module.exports = {
  ...defaultConfig,
  ...{
    entry: entryPoints
  }
}
