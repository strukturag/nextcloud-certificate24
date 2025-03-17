<!--
  - @copyright Copyright (c) 2022, struktur AG.
  -
  - @author Joachim Bauch <bauch@struktur.de>
  -
  - @license AGPL-3.0
  -
  - This code is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License, version 3,
  - as published by the Free Software Foundation.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License, version 3,
  - along with this program. If not, see <http://www.gnu.org/licenses/>
-->

<template>
	<div id="signing_requests" class="certificate24 section">
		<h2>
			{{ t('certificate24', 'Signing requests') }}
			<NcLoadingIcon v-if="loading" />
		</h2>

		<div v-if="!requests.length">
			{{ t('certificate24', 'No requests created yet') }}
		</div>
		<div v-else>
			<table>
				<thead>
					<tr>
						<th>
							{{ t('certificate24', 'File') }}
						</th>
						<th>
							{{ t('certificate24', 'Created') }}
						</th>
						<th>
							{{ t('certificate24', 'Last signed') }}
						</th>
						<th>
							{{ t('certificate24', 'Recipients') }}
						</th>
						<th>
							{{ t('certificate24', 'Actions') }}
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
							<FormattedDate :date="request.created" />
						</td>
						<td>
							<FormattedDate :date="getLastSignature(request)" />
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
									{{ t('certificate24', 'Delete') }}
									<template #icon>
										<Delete :size="20" />
									</template>
								</NcButton>
								<NcButton v-if="request.signed"
									type="primary"
									:href="downloadSignedUrl(request)">
									{{ t('certificate24', 'Download signed') }}
									<template #icon>
										<Download :size="20" />
									</template>
								</NcButton>
								<NcButton v-if="request.details_url"
									type="primary"
									@click="openWindow(request.details_url)">
									{{ t('certificate24', 'Show details') }}
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

import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import Delete from 'vue-material-design-icons/Delete.vue'
import Download from 'vue-material-design-icons/Download.vue'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'

import FormattedDate from './FormattedDate.vue'
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
		FormattedDate,
		Recipient,
	},

	data() {
		return {
			loading: false,
			hash: '',
		}
	},

	computed: {
		requests() {
			return this.$store.getters.getOwnRequests()
		},
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
			const requests = response?.data?.ocs?.data || []
			this.$store.dispatch('setOwnRequests', requests)
			this.$nextTick(() => {
				this.scrollToSelected()
			})
		},

		async deleteRequest(request) {
			OC.dialogs.confirm(
				t('certificate24', 'Do you really want to delete this signing request?'),
				t('certificate24', 'Delete request'),
				async function(decision) {
					if (!decision) {
						return
					}

					this.loading = true
					try {
						await deleteRequest(request.request_id)
						this.$store.dispatch('deleteOwnRequest', request)
						showSuccess(t('certificate24', 'Request deleted.'))
					} catch (error) {
						console.error('Could not delete request', request, error)
						showError(t('certificate24', 'Error while deleting request.'))
					} finally {
						this.loading = false
					}
				}.bind(this),
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
