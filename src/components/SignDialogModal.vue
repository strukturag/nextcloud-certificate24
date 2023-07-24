<!-- eslint-disable vue/no-v-html -->
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
	<NcModal :aria-label="t('certificate24', 'Sign {filename}', {filename: request.filename})"
		size="large"
		:title="t('certificate24', 'Sign {filename}', {filename: request.filename})"
		@close="$emit('close', arguments)">
		<div class="modal__content">
			<h1>{{ t('certificate24', 'Sign {filename}', {filename: request.filename}) }}</h1>
			<div class="document">
				<PdfSigner :url="downloadSourceUrl(request)"
					:download-url="downloadSourceUrl(request)"
					:width="800"
					:height="1132"
					:max-height="400"
					:signature-positions="signature_fields"
					@pdf:error="pdfFailed"
					@render:error="renderError"
					@loading:start="loading++"
					@loading:stop="loading--" />
			</div>
			<NcCheckboxRadioSwitch v-if="canEmbedImages"
				:disabled="loading > 0"
				:checked.sync="embedSignature">
				{{ t('certificate24', 'Embed personal signature in fields') }}
			</NcCheckboxRadioSwitch>
			<div v-if="signature_fields && !canEmbedImages"
				v-html="uploadMessage" />
			<NcButton type="primary"
				:disabled="submitDisabled"
				@click="sign(request)">
				{{ t('certificate24', 'Sign') }}
				<template #icon>
					<NcLoadingIcon v-show="signLoading" :size="20" />
					<FileSign v-show="!signLoading" :size="20" />
				</template>
			</NcButton>
		</div>
	</NcModal>
</template>

<script>
import { showSuccess, showError } from '@nextcloud/dialogs'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import FileSign from 'vue-material-design-icons/FileSign.vue'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'

import { signRequest, getSourceUrl } from '../services/apiservice.js'

import externalComponent from '../services/externalComponent.js'

const PdfSigner = externalComponent('PdfSigner')

export default {
	name: 'SignDialogModal',

	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcModal,
		NcLoadingIcon,
		FileSign,
		PdfSigner,
	},

	props: {
		request: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			loading: 0,
			signLoading: false,
			settings: [],
			embedSignature: true,
		}
	},

	computed: {
		signature_fields() {
			return this.request.metadata?.signature_fields || null
		},
		canEmbedImages() {
			return this.signature_fields
				&& !!this.signature_fields.length
				&& this.settings['has-signature-image']
		},
		submitDisabled() {
			return this.loading > 0
				|| (
					this.signature_fields
					&& !!this.signature_fields.length
					&& (
						!this.settings['has-signature-image']
						|| !this.embedSignature
					)
				)
		},
		uploadMessage() {
			return t('certificate24', 'Please upload a signature image in the <a href="{link}">personal settings</a> to sign this file.', {
				link: generateUrl('/settings/user/certificate24'),
			})
		},
	},

	beforeMount() {
		this.settings = loadState('certificate24', 'user-settings')
	},

	mounted() {
		const elems = this.$el.getElementsByClassName('modal__content')
		elems[0].scrollTop = 0
	},

	methods: {
		downloadSourceUrl(request) {
			return getSourceUrl(request.request_id)
		},

		pdfFailed(error) {
			console.error('Could not load document', error)
			showError(t('certificate24', 'Could not load document, please download and review manually.'))
		},

		renderError(idx, error) {
			console.error('Could not render page', idx, error)
			showError(t('certificate24', 'Could not render page {page}.', { page: idx }))
		},

		sign(request) {
			OC.dialogs.confirm(
				t('certificate24', 'Do you really want to sign this request?'),
				t('certificate24', 'Sign request'),
				async function(decision) {
					if (!decision) {
						return
					}

					this.loading++
					this.signLoading = true
					try {
						const response = await signRequest(request.request_id, {
							embed_user_signature: this.embedSignature,
						})
						const data = response.data.ocs?.data || {}
						request.own_signed = data.signed
						if (data.details_url) {
							request.signed = data.signed
							request.details_url = data.details_url
						}
						showSuccess(t('certificate24', 'Request signed.'))
						this.$emit('close', arguments)
					} catch (error) {
						console.error('Could not sign request', request, error)
						showError(t('certificate24', 'Error while signing request.'))
					} finally {
						this.loading--
						this.signLoading = false
					}
				}.bind(this)
			)
		},
	},
}
</script>

<style lang="scss" scoped>
h1 {
	font-size: 150%;
	font-weight: bold;
	margin-bottom: 1em;
}

.modal__content {
	margin: 50px;
}

.document {
	margin-bottom: 1em;
}

.modal__content:deep a {
	color: var(--color-primary-default);

	&:hover {
		color: var(--color-primary-hover);
	}
}
</style>
