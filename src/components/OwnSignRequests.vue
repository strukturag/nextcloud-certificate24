<template>
	<div id="signing_requests" class="esig section">
		<h2>
			{{ t('esig', 'Signing requests') }}
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
							{{ t('esig', 'Recipient') }}
						</th>
						<th>
							{{ t('esig', 'Actions') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="request in requests" :key="request.request_id">
						<td>
							{{ request.filename }}
						</td>
						<td>
							{{ request.created }}
						</td>
						<td>
							{{ request.signed }}
						</td>
						<td>
							<div v-if="request.recipient_type === 'user'">
								<NcAvatar :user="request.recipient"
									:display-name="request.recipient"
									:disable-menu="true"
									:show-user-status="false"
									:show-user-status-compact="false" />
							</div>
							<div v-else-if="request.recipient_type === 'email'">
								<a :href="'mailto:' + request.recipient">{{ request.recipient }}</a>
							</div>
							<div v-else>
								Unknown {{ request.recipient }}
							</div>
						</td>
						<td>
							<div class="grid">
								<NcButton type="primary"
									@click="deleteRequest(request)">
									{{ t('esig', 'Delete') }}
								</NcButton>
								<NcButton v-if="request.signed"
									type="primary"
									:href="downloadSignedUrl(request)">
									{{ t('esig', 'Download signed') }}
								</NcButton>
							</div>
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
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import { getRequests, deleteRequest, getSignedUrl } from '../services/apiservice.js'

export default {
	name: 'OwnSignRequests',

	components: {
		NcAvatar,
		NcButton,
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
				response = await getRequests(true)
			} finally {
				this.loading = false
			}
			this.requests = response.data.ocs.data
		},

		async deleteRequest(request) {
			OC.dialogs.confirm(
				t('esig', 'Do you really want to delete this signing request?'),
				t('esig', 'Delete request'),
				async function(decision) {
					if (!decision) {
						return
					}

					this.loading = true
					try {
						await deleteRequest(request.request_id)
						this.requests = this.requests.filter((r) => {
							return r.request_id !== request.request_id
						})
						showSuccess(t('esig', 'Request deleted.'))
					} catch (error) {
						console.error('Could not delete request', request, error)
						showError(t('esig', 'Error while deleting request.'))
					} finally {
						this.loading = false
					}
				}.bind(this)
			)
		},

		downloadSignedUrl(request) {
			return getSignedUrl(request.request_id)
		},
	},
}
</script>

<style lang="scss" scoped>
.grid {
	display: grid;
	grid-template-columns: 1fr 1fr 1fr 1fr;
	grid-template-rows: repeat(auto-fill, auto);
	position: relative;
	margin: 12px 0;
}
</style>
