const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const webpackRules = require('@nextcloud/webpack-vue-config/rules')
const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')

webpackConfig.entry = {
	'admin-settings': path.join(__dirname, 'src', 'mainAdminSettings.js'),
	loader: path.join(__dirname, 'src', 'mainLoader.js'),
}

webpackConfig.output.assetModuleFilename = '[name][ext]?v=[contenthash]'

// Edit JS rule
webpackRules.RULE_JS.exclude = BabelLoaderExcludeNodeModulesExcept([
	'@nextcloud/vue-richtext',
	'@nextcloud/event-bus',
	'@nextcloud/vue-dashboard',
	'ansi-regex',
	'color.js',
	'fast-xml-parser',
	'hot-patcher',
	'nextcloud-vue-collections',
	'semver',
	'strip-ansi',
	'tributejs',
	'vue-resize',
	'webdav',
])

// Replaces rules array
webpackConfig.module.rules = Object.values(webpackRules)

module.exports = webpackConfig
