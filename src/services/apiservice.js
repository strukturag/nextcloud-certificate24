import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

const shareFile = async (file_id, recipient, recipient_type) => {
	return await axios.post(generateOcsUrl('apps/esig/api/v1/share'), {
		file_id,
		recipient,
		recipient_type,
	})
}

const getRequests = async () => {
	return await axios.get(generateOcsUrl('apps/esig/api/v1/share'))
}

const deleteRequest = async (id) => {
	return await axios.delete(generateOcsUrl('apps/esig/api/v1/share/' + id))
}

export {
	shareFile,
	getRequests,
	deleteRequest,
}
