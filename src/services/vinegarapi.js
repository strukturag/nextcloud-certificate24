let api

const getVinegarApi = async () => {
	if (!api) {
		const ts = (new Date()).getTime()
		// eslint-disable-next-line no-undef, camelcase
		let base = vinegar_server
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
