<template>
	<NcModal :aria-label="t('esig', 'Sign {filename}', {filename: request.filename})"
		size="large"
		:title="t('esig', 'Sign {filename}', {filename: request.filename})"
		@close="$emit('close', arguments)">
		<div class="modal__content">
			<h1>{{ t('esig', 'Sign {filename}', {filename: request.filename}) }}</h1>
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
			<NcCheckboxRadioSwitch v-if="signature_fields && settings['has-signature-image']"
				:disabled="loading > 0"
				:checked.sync="embedSignature">
				{{ t('esig', 'Embed personal signature in fields') }}
			</NcCheckboxRadioSwitch>
			<NcButton type="primary"
				:disabled="loading > 0"
				@click="sign(request)">
				{{ t('esig', 'Sign') }}
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
	},

	beforeMount() {
		this.settings = loadState('esig', 'user-settings')
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
			showError(t('esig', 'Could not load document, please download and review manually.'))
		},

		renderError(idx, error) {
			console.error('Could not render page', idx, error)
			showError(t('esig', 'Could not render page {page}.', { page: idx }))
		},

		sign(request) {
			OC.dialogs.confirm(
				t('esig', 'Do you really want to sign this request?'),
				t('esig', 'Sign request'),
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
						request.signed = data.signed
						showSuccess(t('esig', 'Request signed.'))
						this.$emit('close', arguments)
					} catch (error) {
						console.error('Could not sign request', request, error)
						showError(t('esig', 'Error while signing request.'))
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
</style>
