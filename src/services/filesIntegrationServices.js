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

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * Gets the signature information for a given file id
 *
 * @param {object} data the wrapping object;
 * @param {number} data.fileId The file id to get the signatures for
 * @param {object} options additional options
 * @param {boolean} options.force Force verifying the signatures
 * @return {axios.response} the response object
 */
const getFileSignatures = async function({ fileId }, options) {
	let query = []
	if (options?.force) {
		query.push('reverify=true')
	}
	query = query.length ? ('?' + query.join('&')) : ''
	const response = await axios.get(generateOcsUrl('apps/certificate24/api/v1/verify/{fileId}', { fileId }) + query)
	return response
}

export {
	getFileSignatures,
}
