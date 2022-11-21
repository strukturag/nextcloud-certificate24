/* eslint camelcase:0 */
import axios from '@nextcloud/axios'
import { generateUrl, generateOcsUrl } from '@nextcloud/router'

const metadataCache = {}

const isEmpty = (obj) => {
	return !obj || Object.keys(obj).length === 0
}

const shareFile = async (file_id, recipients, options, metadata) => {
	return axios.post(generateOcsUrl('apps/esig/api/v1/share'), {
		file_id,
		recipients,
		options: !isEmpty(options) ? options : null,
		metadata: !isEmpty(metadata) ? metadata : null,
	}).then(() => {
		if (isEmpty(metadata)) {
			delete metadataCache[file_id]
		} else {
			metadataCache[file_id] = metadata
		}
	})
}

const getRequests = async (include_signed) => {
	return await axios.get(generateOcsUrl('apps/esig/api/v1/share'), {
		params: {
			include_signed,
		},
	})
}

const deleteRequest = async (id) => {
	return await axios.delete(generateOcsUrl('apps/esig/api/v1/share/' + id))
}

const getIncomingRequests = async (include_signed) => {
	return await axios.get(generateOcsUrl('apps/esig/api/v1/share/incoming'), {
		params: {
			include_signed,
		},
	})
}

const signRequest = async (id, options) => {
	const form = new FormData()
	if (options) {
		form.append('options', JSON.stringify(options))
	}
	return await axios.postForm(generateOcsUrl('apps/esig/api/v1/share/' + id + '/sign'), form)
}

const getOriginalUrl = (id) => {
	return generateUrl('apps/esig/download/' + id)
}

const getSignedUrl = (id) => {
	return generateUrl('apps/esig/download/signed/' + id)
}

const search = async (search, type) => {
	return await axios.post(generateOcsUrl('apps/esig/api/v1/search'), {
		search,
		type,
	})
}

const resetSignatureImage = async () => {
	return await axios.delete(generateUrl('apps/esig/settings/signature'))
}

const uploadSignatureImage = async (image) => {
	const form = new FormData()
	form.append('image', image)

	return await axios.postForm(generateUrl('apps/esig/settings/signature'), form)
}

const getMetadata = async (file_id) => {
	return new Promise((resolve, reject) => {
		if (Object.prototype.hasOwnProperty.call(metadataCache, file_id)) {
			resolve(metadataCache[file_id])
			return
		}

		axios.get(generateOcsUrl('apps/esig/api/v1/metadata/' + file_id))
			.then((response) => {
				const metadata = response.data?.ocs?.data || {}
				metadataCache[file_id] = metadata
				resolve(metadata)
			}, (error) => {
				reject(error)
			})
	})
}

export {
	shareFile,
	getRequests,
	getIncomingRequests,
	deleteRequest,
	signRequest,
	getOriginalUrl,
	getSignedUrl,
	search,
	resetSignatureImage,
	uploadSignatureImage,
	getMetadata,
}
