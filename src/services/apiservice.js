import axios from '@nextcloud/axios'
import { generateOcsUrl,  } from '@nextcloud/router'

const shareFile = async (file_id, recipient, recipient_type) => {
  return await axios.post(generateOcsUrl('apps/esig/api/v1/share'), {
    file_id,
    recipient,
    recipient_type,
  })
}

export {
  shareFile,
}
