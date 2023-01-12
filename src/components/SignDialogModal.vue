<template>
	<NcModal :aria-label="t('esig', 'Sign {filename}', {filename: request.filename})"
		size="large"
		:title="t('esig', 'Sign {filename}', {filename: request.filename})"
		@close="$emit('close', arguments)">
		<div class="modal__content">
			<h1>{{ t('esig', 'Sign {filename}', {filename: request.filename}) }}</h1>
			<div class="document">
				<PdfViewer :width="800"
					:height="1132"
					:max-height="400"
					:url="downloadSourceUrl(request)"
					:download-url="downloadSourceUrl(request)"
					:signature-positions="signature_fields"
					@init:start="loading = true"
					@init:done="loading = false" />
			</div>
			<NcCheckboxRadioSwitch v-if="signature_fields && settings['has-signature-image']"
				:disabled="loading"
				:checked.sync="embedSignature">
				{{ t('esig', 'Embed personal signature in fields') }}
			</NcCheckboxRadioSwitch>
			<!--
				{{ request }}
			-->
			<NcButton type="primary"
				:disabled="loading"
				@click="sign(request)">
				{{ t('esig', 'Sign') }}
				<template #icon>
					<FileSign :size="20" />
				</template>
			</NcButton>
		</div>
	</NcModal>
</template>

<script>
import { showSuccess, showError } from '@nextcloud/dialogs'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import FileSign from 'vue-material-design-icons/FileSign.vue'
import { loadState } from '@nextcloud/initial-state'

import PdfViewer from './PdfViewer.vue'
import { signRequest, getSourceUrl } from '../services/apiservice.js'

export default {
	name: 'SignDialogModal',

	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcModal,
		PdfViewer,
		FileSign,
	},

	props: {
		request: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			loading: false,
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

		sign(request) {
			OC.dialogs.confirm(
				t('esig', 'Do you really want to sign this request?'),
				t('esig', 'Sign request'),
				async function(decision) {
					if (!decision) {
						return
					}

					this.loading = true
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
						this.loading = false
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
