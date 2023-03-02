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
					<tr v-for="request in requests"
						:key="request.request_id"
						:class="{'selected': request.request_id === selectedRequest}">
						<td>
							<a :id="getLinkName(request)" />
							<a v-if="!request.signed" :href="downloadOriginalUrl(request)">{{ request.filename }}</a>
							<span v-if="request.signed">{{ request.filename }}</span>
						</td>
						<td>
							{{ request.created }}
						</td>
						<td>
							{{ request.own_signed }}
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
								<NcButton v-show="!request.own_signed"
									:disabled="request.loading"
									type="primary"
									@click="signRequest(request)">
									{{ t('esig', 'Sign') }}
									<template #icon>
										<NcLoadingIcon v-show="request.loading" :size="20" />
										<FileSign v-show="!request.loading" :size="20" />
									</template>
								</NcButton>
								<NcButton v-show="request.signed"
									type="primary"
									:href="downloadSignedUrl(request)">
									{{ t('esig', 'Download signed') }}
									<template #icon>
										<Download :size="20" />
									</template>
								</NcButton>
								<NcButton v-show="request.details_url"
									type="primary"
									@click="openWindow(request.details_url)">
									{{ t('esig', 'Show details') }}
									<template #icon>
										<OpenInNew :size="20" />
									</template>
								</NcButton>
								<div v-show="request.own_signed && !request.signed">
									{{ t('esig', 'Waiting for other signatures.') }}
								</div>
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
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
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
		OpenInNew,
		SignDialogModal,
	},

	data() {
		return {
			requests: [],
			loading: false,
			signDialog: null,
			hash: '',
		}
	},

	computed: {
		selectedRequest() {
			let r = this.hash
			let pos = r.indexOf('incoming-')
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

			const elem = document.getElementById('incoming-' + selected)
			if (!elem) {
				return
			}

			elem.scrollIntoView()
		},

		async fetchRequests() {
			this.loading = true
			let response
			try {
				response = await getIncomingRequests(true)
			} finally {
				this.loading = false
			}
			this.requests = response.data.ocs.data
			this.$nextTick(() => {
				this.scrollToSelected()
			})
		},

		async signRequest(request) {
			this.$set(request, 'loading', true)
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
				.finally(() => {
					this.$delete(request, 'loading')
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
