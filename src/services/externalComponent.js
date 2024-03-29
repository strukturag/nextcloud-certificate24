/**
 * @copyright Copyright (c) 2023, struktur AG.
 *
 * @author Joachim Bauch <bauch@struktur.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 */

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
