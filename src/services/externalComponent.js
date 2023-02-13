import { defineAsyncComponent } from 'vue'
import getVinegarApi from './vinegarapi.js'

// eslint-disable-next-line jsdoc/require-jsdoc
function externalComponent(name) {
	return defineAsyncComponent(() => {
		return new Promise((resolve, reject) => {
			getVinegarApi()
				.then((api) => {
					const component = api.components[name]
					if (!component) {
						reject(new Error(`Unknown component ${name}`))
						return
					}

					resolve(component)
				})
				.catch((error) => {
					reject(error)
				})
		})
	})
}

export default externalComponent
