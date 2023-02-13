import { defineAsyncComponent } from 'vue'
import getVinegarApi from './vinegarapi.js'

// eslint-disable-next-line jsdoc/require-jsdoc
function externalComponent(name) {
	return defineAsyncComponent(() => {
		if (window[name]) return window[name]

		window[name] = new Promise((resolve, reject) => {
			getVinegarApi()
				.then((api) => {
					const component = api.components[name]
					if (!component) {
						reject(new Error(`Unknown component ${name}`))
						return
					}

					window[name] = component
					resolve(component)
				})
				.catch((error) => {
					reject(error)
				})
		})

		return window[name]
	})
}

export default externalComponent
