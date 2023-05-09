const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const webpackRules = require('@nextcloud/webpack-vue-config/rules')
const BabelLoaderExcludeNodeModulesExcept = require('babel-loader-exclude-node-modules-except')

webpackConfig.entry = {
	'admin-settings': path.join(__dirname, 'src', 'mainAdminSettings.js'),
	'personal-settings': path.join(__dirname, 'src', 'mainPersonalSettings.js'),
	dashboard: path.join(__dirname, 'src', 'dashboard.js'),
	main: path.join(__dirname, 'src', 'main.js'),
	loader: path.join(__dirname, 'src', 'mainLoader.js'),
	'files-sidebar': [
		path.join(__dirname, 'src', 'mainFilesSidebar.js'),
		path.join(__dirname, 'src', 'mainFilesSidebarLoader.js'),
	],
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

webpackConfig.cache = true

module.exports = webpackConfig
