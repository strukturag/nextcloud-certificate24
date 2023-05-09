<!--
  - @copyright Copyright (c) 2023, struktur AG.
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
	<div class="esigSignaturesTab">
		<div v-if="isSidebarSupportedForFile === undefined" class="emptycontent">
			<div class="icon icon-loading" />
		</div>
		<div v-else-if="!isSidebarSupportedForFile" class="emptycontent">
			<div class="icon icon-esig" />
			<h2>{{ t('esig', 'Signatures') }}</h2>
			<p v-if="!signaturesPending">
				{{ t('esig', 'Signatures are not supported for this file.') }}
			</p>
			<p v-if="signaturesPending">
				{{ t('esig', 'Signature status is pending for this file.') }}
			</p>
			<NcButton v-if="signaturesPending"
				@click="forceCheck">
				{{ t('esig', 'Check manually') }}
			</NcButton>
		</div>
		<div v-else-if="isSidebarSupportedForFile && notSigned" class="emptycontent">
			<div class="icon icon-esig" />
			<h2>{{ t('esig', 'Signatures') }}</h2>
			<p>{{ t('esig', 'The file is not signed.') }}</p>
			<NcButton @click="forceCheck">
				{{ t('esig', 'Force recheck') }}
			</NcButton>
		</div>
		<template v-else>
			<SignaturesView :file-id="fileId" />
			<NcButton @click="forceCheck">
				{{ t('esig', 'Force recheck') }}
			</NcButton>
		</template>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import { showError } from '@nextcloud/dialogs'

import { getFileSignatures } from '../services/filesIntegrationServices.js'
import SignaturesView from './SignaturesView.vue'

export default {
	name: 'FilesSidebarTab',

	components: {
		NcButton,
		SignaturesView,
	},

	data() {
		return {
			// needed for reactivity
			Esig: OCA.Esig,
			sidebarState: OCA.Files.Sidebar.state,
			signaturesPending: false,
			notSigned: false,
			isSidebarSupportedForFile: undefined,
			prevSignaturesPending: false,
			prevNotSigned: false,
			prevIsSidebarSupportedForFile: undefined,
		}
	},

	computed: {
		fileInfo() {
			return this.Esig.fileInfo || {}
		},
		fileId() {
			return this.fileInfo.id
		},
		isTheActiveTab() {
			// FIXME check for empty active tab is currently needed because the
			// activeTab is not set when opening the sidebar from the "Details"
			// action (which opens the first tab, which is the Chat tab).
			return !this.sidebarState.activeTab || this.sidebarState.activeTab === 'signatures'
		},
	},

	watch: {
		fileInfo: {
			immediate: true,
			handler(fileInfo) {
				this.setSidebarSupportedForFile(fileInfo)
			},
		},

		isTheActiveTab: {
			immediate: true,
			handler(isTheActiveTab) {
				// recheck the file info in case the sharing info was changed
				this.setSidebarSupportedForFile(this.fileInfo)
			},
		},
	},

	methods: {
		/**
		 * @param {OCA.Files.FileInfo} fileInfo the FileInfo to check
		 * @param {boolean} force force fetching signatures
		 */
		async setSidebarSupportedForFile(fileInfo, force) {
			this.prevIsSidebarSupportedForFile = this.isSidebarSupportedForFile
			this.prevSignaturesPending = this.signaturesPending
			this.prevNotSigned = this.notSigned

			this.isSidebarSupportedForFile = undefined
			this.signaturesPending = false
			this.notSigned = false

			if (!fileInfo) {
				this.isSidebarSupportedForFile = false

				return
			}

			if (fileInfo.get('type') === 'dir') {
				this.isSidebarSupportedForFile = false

				return
			}

			if (fileInfo.get('mimetype') !== 'application/pdf') {
				this.isSidebarSupportedForFile = false

				return
			}

			if (fileInfo.get('shareOwnerId')) {
				// Shared with me
				// TODO How to check that it is not a remote share? At least for
				// local shares "shareTypes" is not defined when shared with me.
				this.isSidebarSupportedForFile = true

				return
			}

			if (!fileInfo.get('shareTypes')) {
				// When it is not possible to know whether the sidebar is
				// supported for a file or not only from the data in the
				// FileInfo it is necessary to query the server.
				try {
					this.isSidebarSupportedForFile = (await getFileSignatures({ fileId: fileInfo.id }, { force })) || false
				} catch (error) {
					this.isSidebarSupportedForFile = false
					this.handleGetSignaturesError(error)
				}

				return
			}

			const shareTypes = fileInfo.get('shareTypes').filter(function(shareType) {
				// Ensure that shareType is an integer (as in the past shareType
				// could be an integer or a string depending on whether the
				// Sharing tab was opened or not).
				shareType = parseInt(shareType)
				return shareType === OC.Share.SHARE_TYPE_USER
						|| shareType === OC.Share.SHARE_TYPE_GROUP
						|| shareType === OC.Share.SHARE_TYPE_CIRCLE
						|| shareType === OC.Share.SHARE_TYPE_ROOM
						|| shareType === OC.Share.SHARE_TYPE_LINK
						|| shareType === OC.Share.SHARE_TYPE_EMAIL
			})

			if (shareTypes.length === 0) {
				// When it is not possible to know whether the sidebar is
				// supported for a file or not only from the data in the
				// FileInfo it is necessary to query the server.
				try {
					this.isSidebarSupportedForFile = (await getFileSignatures({ fileId: fileInfo.id }, { force })) || false
				} catch (error) {
					this.isSidebarSupportedForFile = false
					this.handleGetSignaturesError(error)
				}

				return
			}

			this.isSidebarSupportedForFile = true
		},

		handleGetSignaturesError(error) {
			switch (error.response?.status) {
			case 404:
				switch (error.response.data.ocs?.data?.status) {
				case 'not_signed':
					this.isSidebarSupportedForFile = true
					this.notSigned = true
					break
				}
				break
			case 412:
				this.signaturesPending = true
				break
			case 502:
				this.isSidebarSupportedForFile = this.prevIsSidebarSupportedForFile
				this.signaturesPending = this.prevSignaturesPending
				this.notSigned = this.prevNotSigned
				showError(t('esig', 'Error fetching signature details.'))
				break
			}
		},

		forceCheck() {
			this.setSidebarSupportedForFile(this.fileInfo, true)
		},
	},
}
</script>

<style scoped>
.esigSignaturesTab {
	height: 100%;

	display: flex;
	flex-grow: 1;
	flex-direction: column;
}

.emptycontent {
	/* Override default top margin set in server and center vertically
	 * instead. */
	margin-top: unset;

	height: 100%;

	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}
</style>
