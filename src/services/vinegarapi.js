/**
 * @copyright Copyright (c) 2022, struktur AG.
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

import { loadState } from '@nextcloud/initial-state'
import { getCanonicalLocale } from '@nextcloud/l10n'

let api

const features = [
	'token-actions',
]

// eslint-disable-next-line jsdoc/require-jsdoc
function checkServerFeatures(features) {
	return features
		&& features.indexOf('sign-anonymous') !== -1
		&& features.indexOf('signature-drawer') !== -1
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
			locale: getCanonicalLocale(),
			features,
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
