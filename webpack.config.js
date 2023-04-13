const defaults = require('@wordpress/scripts/config/webpack.config');
const path = require( 'path' );

module.exports = {
    ...defaults,
    externals: {
        react: 'React',
        'react-dom': 'ReactDOM',
    },
};