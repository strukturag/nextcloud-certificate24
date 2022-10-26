<template>
	<div id="signature_image" class="esig section">
		<h2>{{ t('esig', 'Signature Image') }}</h2>

		<div v-if="settings['has-signature-image']">
			<div class="signature-image">
				<img :src="imageUrl">
			</div>
			<NcButton :disabled="loading"
				type="secondary"
				@click="resetImage">
				<template #icon>
					<Close :size="20" />
				</template>
				{{ t('esig', 'Reset') }}
			</NcButton>
		</div>

		<div>
			<div>{{ t('esig', 'Upload signature image') }}</div>
			<input ref="image"
				type="file"
				accept="image/png, image/jpeg"
				:disabled="loading"
				:aria-label="t('esig', 'Signature image')"
				@change="updateFiles">
		</div>
		<div class="buttons">
			<NcButton :disabled="loading || !hasFile"
				type="primary"
				@click="uploadImage">
				<template #icon>
					<Upload :size="20" />
				</template>
				{{ t('esig', 'Upload') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import Close from 'vue-material-design-icons/Close.vue'
import Upload from 'vue-material-design-icons/Upload.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'

import { resetSignatureImage, uploadSignatureImage } from '../../services/apiservice.js'

export default {
	name: 'SignatureImage',

	components: {
		NcButton,
		Close,
		Upload,
	},

	data() {
		return {
			loading: false,
			hasFile: false,
			ts: 0,
			settings: {},
		}
	},

	computed: {
		imageUrl() {
			let url = this.settings['signature-image-url']
			if (this.ts) {
				url += '?ts=' + this.ts
			}
			return url
		},
	},

	beforeMount() {
		this.settings = loadState('esig', 'settings')
	},

	methods: {
		updateFiles(event) {
			this.hasFile = !!event.target.files.length
		},

		async loadFile(file) {
			return new Promise((resolve, reject) => {
				const reader = new FileReader()
				reader.onload = (event) => {
					resolve(event.target.result)
				}
				reader.onerror = (error) => {
					reject(error)
				}
				reader.readAsBinaryString(file)
			})
		},

		async uploadImage() {
			const files = this.$refs.image.files
			if (!files || !files.length) {
				showError(t('esig', 'Please select a file first.'))
				return
			}

			const file = files[0]
			try {
				await this.loadFile(file)
			} catch (error) {
				console.error('Could not open file', file, error)
				showError(t('esig', 'Could not open file.'))
				return
			}

			this.loading = true
			try {
				await uploadSignatureImage(file)
				showSuccess(t('esig', 'Signature image uploaded.'))
				this.ts = (new Date()).getTime()
				this.settings['has-signature-image'] = true
				this.$refs.image.value = ''
				this.hasFile = false
			} catch (error) {
				console.error('Could not upload signature image', error)
				switch (error.response?.status) {
				case 413:
					showError(t('esig', 'The uploaded image is too large.'))
					break
				default:
					showError(t('esig', 'Error while uploading signature image.'))
				}
			} finally {
				this.loading = false
			}
		},

		resetImage() {
			OC.dialogs.confirm(
				t('esig', 'Do you really want to reset the signature image?'),
				t('esig', 'Reset signature image'),
				async (decision) => {
					if (!decision) {
						return
					}

					this.loading = true
					try {
						await resetSignatureImage()
						showSuccess(t('esig', 'Signature image reset.'))
						this.settings['has-signature-image'] = false
					} catch (error) {
						console.error('Could not reset signature image', error)
						showError(t('esig', 'Error while resetting signature image.'))
					} finally {
						this.loading = false
					}
				}
			)
		},
	},
}
</script>
<style scoped lang="scss">
input[type=file] {
	border: 0 !important;
	border-radius: 0 !important;
	padding-left: 0 !important;
}

.signature-image {
	display: flex;
	max-width: 400px;
	max-height: 300px;
	border: 2px dashed var(--color-border);
	margin-bottom: 1em;

	img {
		width: 100%;
		height: auto;
		object-fit: contain;
	}
}
</style>
