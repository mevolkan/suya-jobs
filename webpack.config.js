const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    entry: {
        'job-application-form': './blocks/job-application-form/index.js',
    },
    output: {
        path: __dirname + '/blocks/job-application-form/build',
        filename: '[name].js',
    }
};