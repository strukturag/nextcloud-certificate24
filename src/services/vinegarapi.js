import { loadState } from '@nextcloud/initial-state'

let api

const translator = (message, params) => {
	return t('esig', message, params)
}

const features = [
	'token-actions',
]

// eslint-disable-next-line jsdoc/require-jsdoc
function checkServerFeatures(features) {
	// We don't need any special server features for now.
	return true
}

const getVinegarApi = async () => {
	if (!api) {
		const ts = (new Date()).getTime()
		let base = loadState('esig', 'vinegar_server')
		if (!base) {
			throw new Error('No server configured')
		}

		if (base[base.length - 1] !== '/') {
			base += '/'
		}
		await import(/* webpackIgnore: true */ base + 'vinegar.api.js?t=' + ts)
		// eslint-disable-next-line no-undef
		if (!checkServerFeatures(VinegarApi.features)) {
			throw new Error('server_unsupported')
		}

		// eslint-disable-next-line no-undef
		const maybePromise = VinegarApi.Setup({
			url: base,
			features,
			translator,
		})
		if (maybePromise) {
			await maybePromise
		}
		// eslint-disable-next-line no-undef
		api = VinegarApi
		console.info('Loaded vinegar API', api.version)
	}
	return api
}

export default getVinegarApi
