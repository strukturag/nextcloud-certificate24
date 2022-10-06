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
								:show-user-status="false"
								:show-user-status-compact="false" />
						</td>
						<td>
							<a v-if="!request.signed" @click="signRequest(request)">Sign</a>
							<a v-if="request.signed" :href="downloadSignedUrl(request)">Download signed</a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<script>
import { showSuccess, showError } from '@nextcloud/dialogs'

import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import { getIncomingRequests, signRequest, getOriginalUrl, getSignedUrl } from '../services/apiservice.js'

export default {
	name: 'IncomingSignRequests',

	components: {
		NcAvatar,
		NcLoadingIcon,
	},

	data() {
		return {
			requests: [],
			loading: false,
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
			OC.dialogs.confirm(
				t('esig', 'Do you really want to sign this request?'),
				t('esig', 'Sign request'),
				async function(decision) {
					if (!decision) {
						return
					}

					this.loading = true
					try {
						await signRequest(request.request_id)
						request.signed = new Date()
						showSuccess(t('esig', 'Request signed.'))
					} catch (error) {
						console.error('Could not sign request', request, error)
						showError(t('esig', 'Error while signing request.'))
					} finally {
						this.loading = false
					}
				}.bind(this)
			)
		},

		downloadOriginalUrl(request) {
			return getOriginalUrl(request.request_id)
		},

		downloadSignedUrl(request) {
			return getSignedUrl(request.request_id)
		},
	},
}
</script>
