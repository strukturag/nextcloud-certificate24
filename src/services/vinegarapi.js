import { loadState } from '@nextcloud/initial-state'

let api

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
		api = VinegarApi
		api.Setup({
			url: base,
		})
		console.info('Loaded vinegar API', api.version)
	}
	return api
}

export default getVinegarApi
