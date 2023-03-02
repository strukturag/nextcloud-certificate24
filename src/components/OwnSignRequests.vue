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
							{{ t('esig', 'Last signed') }}
						</th>
						<th>
							{{ t('esig', 'Recipients') }}
						</th>
						<th>
							{{ t('esig', 'Actions') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="request in requests"
						:key="request.request_id"
						:class="{'selected': request.request_id === selectedRequest}">
						<td>
							<a :id="getLinkName(request)" />
							{{ request.filename }}
						</td>
						<td>
							{{ request.created }}
						</td>
						<td>
							{{ getLastSignature(request) }}
						</td>
						<td>
							<div v-for="recipient in request.recipients" :key="recipient.type + '-' + recipient.value">
								<Recipient :recipient="recipient" />
							</div>
						</td>
						<td>
							<div class="grid">
								<NcButton type="primary"
									@click="deleteRequest(request)">
									{{ t('esig', 'Delete') }}
									<template #icon>
										<Delete :size="20" />
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
								<NcButton v-if="request.details_url"
									type="primary"
									@click="openWindow(request.details_url)">
									{{ t('esig', 'Show details') }}
									<template #icon>
										<OpenInNew :size="20" />
									</template>
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

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import Delete from 'vue-material-design-icons/Delete.vue'
import Download from 'vue-material-design-icons/Download.vue'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'

import Recipient from './Recipient.vue'
import { getRequests, deleteRequest, getSignedUrl } from '../services/apiservice.js'

export default {
	name: 'OwnSignRequests',

	components: {
		NcButton,
		NcLoadingIcon,
		Delete,
		Download,
		OpenInNew,
		Recipient,
	},

	data() {
		return {
			requests: [],
			loading: false,
			hash: '',
		}
	},

	computed: {
		selectedRequest() {
			let r = this.hash
			let pos = r.indexOf('outgoing-')
			if (pos === -1) {
				return ''
			}
			r = r.substring(pos + 9)
			pos = r.indexOf('&')
			if (pos !== -1) {
				r = r.substring(0, pos)
			}
			return r
		},
	},

	async mounted() {
		window.addEventListener('hashchange', this.onHashChange)
		this.hash = location.hash
		this.fetchRequests()
	},

	beforeDestroy() {
		window.removeEventListener('hashchange', this.onHashChange)
	},

	methods: {
		onHashChange() {
			this.hash = location.hash
			this.$nextTick(() => {
				this.scrollToSelected()
			})
		},

		scrollToSelected() {
			const selected = this.selectedRequest
			if (!selected) {
				return
			}

			const elem = document.getElementById('outgoing-' + selected)
			if (!elem) {
				return
			}

			elem.scrollIntoView()
		},

		getLastSignature(request) {
			if (request.signed) {
				return request.signed
			}
			let result
			request.recipients.forEach((recipient) => {
				if (!recipient.signed) {
					return
				}

				if (!result || recipient.signed > result) {
					result = recipient.signed
				}
			})
			return result
		},

		async fetchRequests() {
			this.loading = true
			let response
			try {
				response = await getRequests(true)
			} finally {
				this.loading = false
			}
			this.requests = response.data.ocs.data
			this.$nextTick(() => {
				this.scrollToSelected()
			})
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

		getLinkName(request) {
			return 'outgoing-' + request.request_id
		},

		openWindow(url) {
			window.open(url, '_blank')
		},
	},
}
</script>

<style lang="scss" scoped>
.grid {
	display: flex;
	column-gap: 12px;
	position: relative;
	margin: 12px 0;
}

tr.selected {
	background-color: var(--color-background-darker);
}
</style>
