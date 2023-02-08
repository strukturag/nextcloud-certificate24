<template>
	<div id="signing_requests" class="esig section">
		<h2>
			{{ t('esig', 'Incoming signing requests') }}
			<NcLoadingIcon v-if="loading" />
		</h2>

		<div v-if="!requests.length">
			{{ t('esig', 'No requests created yet') }}
		</div>
		<div v-else>
			<table>
				<thead>
					<tr>
						<th>
							{{ t('esig', 'File') }}
						</th>
						<th>
							{{ t('esig', 'Created') }}
						</th>
						<th>
							{{ t('esig', 'Signed') }}
						</th>
						<th>
							{{ t('esig', 'Creator') }}
						</th>
						<th>
							{{ t('esig', 'Actions') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="request in requests" :key="request.request_id">
						<td>
							<a :id="getLinkName(request)" />
							<a v-if="!request.signed" :href="downloadOriginalUrl(request)">{{ request.filename }}</a>
							<span v-if="request.signed">{{ request.filename }}</span>
						</td>
						<td>
							{{ request.created }}
						</td>
						<td>
							{{ request.signed }}
						</td>
						<td>
							<NcAvatar :user="request.user_id"
								:display-name="request.display_name"
								:disable-menu="true"
								:show-user-status="false"
								:show-user-status-compact="false" />
						</td>
						<td>
							<div class="grid">
								<NcButton v-if="!request.signed"
									type="primary"
									@click="signRequest(request)">
									{{ t('esig', 'Sign') }}
									<template #icon>
										<FileSign :size="20" />
									</template>
								</NcButton>
								<NcButton v-if="request.signed"
									type="primary"
									:href="downloadSignedUrl(request)">
									{{ t('esig', 'Download signed') }}
									<template #icon>
										<Download :size="20" />
									</template>
								</NcButton>
								<SignDialogModal v-if="signDialog === request"
									:request="request"
									@close="closeDialog" />
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script>
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import Download from 'vue-material-design-icons/Download.vue'
import FileSign from 'vue-material-design-icons/FileSign.vue'
import { showError } from '@nextcloud/dialogs'

import SignDialogModal from './SignDialogModal.vue'
import { getIncomingRequests, getOriginalUrl, getSignedUrl } from '../services/apiservice.js'
import getVinegarApi from '../services/vinegarapi.js'

export default {
	name: 'IncomingSignRequests',

	components: {
		NcAvatar,
		NcButton,
		NcLoadingIcon,
		Download,
		FileSign,
		SignDialogModal,
	},

	data() {
		return {
			requests: [],
			loading: false,
			signDialog: null,
		}
	},

	async mounted() {
		this.fetchRequests()
	},

	methods: {
		async fetchRequests() {
			this.loading = true
			let response
			try {
				response = await getIncomingRequests(true)
			} finally {
				this.loading = false
			}
			this.requests = response.data.ocs.data
		},

		async signRequest(request) {
			getVinegarApi()
				.then(() => {
					this.signDialog = request
				})
				.catch((error) => {
					const msg = error.message || error
					switch (msg) {
					case 'client_unsupported':
						showError(t('esig', 'The server requires a newer version of the app. Please contact your administrator.'))
						break
					case 'server_unsupported':
						showError(t('esig', 'This app requires a newer version of the server. Please contact your administrator.'))
						break
					default:
						console.error('Error loading esig API', error)
						showError(t('esig', 'Error loading serverside API, please try again later.'))
					}
				})
		},

		downloadOriginalUrl(request) {
			return getOriginalUrl(request.request_id)
		},

		downloadSignedUrl(request) {
			return getSignedUrl(request.request_id)
		},

		getLinkName(request) {
			return 'incoming-' + request.request_id
		},

		closeDialog() {
			this.signDialog = null
		},
	},
}
</script>
