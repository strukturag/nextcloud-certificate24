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
	<div id="signature_image" class="certificate24 section">
		<h2>{{ t('certificate24', 'Signature Image') }}</h2>

		<div v-if="settings['has-signature-image']">
			<div class="signature-image">
				<img :src="imageUrl">
			</div>
			<div class="buttons">
				<NcButton :disabled="loading"
					type="secondary"
					@click="resetImage">
					<template #icon>
						<Close :size="20" />
					</template>
					{{ t('certificate24', 'Reset') }}
				</NcButton>
			</div>
		</div>

		<div>
			<div>{{ t('certificate24', 'Upload signature image') }}</div>
			<input ref="image"
				type="file"
				accept="image/png, image/jpeg"
				:disabled="loading"
				:aria-label="t('certificate24', 'Signature image')"
				@change="updateFiles">
		</div>
		<div class="buttons">
			<NcButton :disabled="loading || !hasFile"
				type="primary"
				@click="uploadImage">
				<template #icon>
					<Upload :size="20" />
				</template>
				{{ t('certificate24', 'Upload') }}
			</NcButton>
		</div>
		<div>
			<div>{{ t('certificate24', 'Draw written signature') }}</div>
			<div class="drawer">
				<SignatureDrawer ref="drawer"
					:width="600"
					:height="400"
					@select="updateDrawImage" />
			</div>
		</div>
		<div class="buttons">
			<NcButton :disabled="loading || !drawImage"
				type="primary"
				@click="saveDrawImage">
				<template #icon>
					<ContentSave :size="20" />
				</template>
				{{ t('certificate24', 'Save') }}
			</NcButton>
			<NcButton :disabled="loading || !drawImage"
				type="secondary"
				@click="clearDrawImage">
				<template #icon>
					<Close :size="20" />
				</template>
				{{ t('certificate24', 'Clear') }}
			</NcButton>
		</div>
		<div>
			<div>{{ t('certificate24', 'Create signature from text') }}</div>
			<input v-model="textValue"
				type="text"
				class="text-input"
				maxlength="50"
				:disabled="loading"
				:placeholder="t('certificate24', 'Your name or initials')"
				:aria-label="t('certificate24', 'Signature text')">
			<select v-model="textFont"
				class="text-font"
				:disabled="loading"
				:aria-label="t('certificate24', 'Signature font')">
				<option v-for="font in fonts" :key="font.value" :value="font.value">
					{{ font.label }}
				</option>
			</select>
			<div class="drawer text-preview">
				<canvas ref="textCanvas" :width="600" :height="200" />
			</div>
		</div>
		<div class="buttons">
			<NcButton :disabled="loading || !hasText"
				type="primary"
				@click="saveTextSignature">
				<template #icon>
					<ContentSave :size="20" />
				</template>
				{{ t('certificate24', 'Save') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import Close from 'vue-material-design-icons/Close.vue'
import Upload from 'vue-material-design-icons/Upload.vue'
import ContentSave from 'vue-material-design-icons/ContentSave.vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import confirmDialog from '../../services/confirm.js'

import { resetSignatureImage, uploadSignatureImage } from '../../services/apiservice.js'
import externalComponent from '../../services/externalComponent.js'

const SignatureDrawer = externalComponent('SignatureDrawer')

export default {
	name: 'SignatureImage',

	components: {
		NcButton,
		Close,
		Upload,
		ContentSave,
		SignatureDrawer,
	},

	data() {
		return {
			loading: false,
			hasFile: false,
			drawImage: null,
			ts: 0,
			settings: {},
			textValue: '',
			textFont: "'Segoe Script', 'Bradley Hand', cursive",
			fonts: [
				{ value: "'Segoe Script', 'Bradley Hand', cursive", label: t('certificate24', 'Handwriting') },
				{ value: 'serif', label: t('certificate24', 'Serif') },
				{ value: 'sans-serif', label: t('certificate24', 'Sans-serif') },
				{ value: 'monospace', label: t('certificate24', 'Monospace') },
			],
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
		hasText() {
			return !!this.textValue.trim()
		},
	},

	watch: {
		textValue() {
			this.renderTextSignature()
		},
		textFont() {
			this.renderTextSignature()
		},
	},

	beforeMount() {
		this.settings = loadState('certificate24', 'settings')
	},

	mounted() {
		this.renderTextSignature()
	},

	methods: {
		t,

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
				showError(t('certificate24', 'Please select a file first.'))
				return
			}

			const file = files[0]
			try {
				await this.loadFile(file)
			} catch (error) {
				console.error('Could not open file', file, error)
				showError(t('certificate24', 'Could not open file.'))
				return
			}

			this.loading = true
			try {
				await uploadSignatureImage(file)
				showSuccess(t('certificate24', 'Signature image uploaded.'))
				this.ts = (new Date()).getTime()
				this.settings['has-signature-image'] = true
				this.$refs.image.value = ''
				this.hasFile = false
				document.getElementById('app-content').scrollTop = 0
			} catch (error) {
				console.error('Could not upload signature image', error)
				switch (error.response?.status) {
				case 413:
					showError(t('certificate24', 'The uploaded image is too large.'))
					break
				default:
					showError(t('certificate24', 'Error while uploading signature image.'))
				}
			} finally {
				this.loading = false
			}
		},

		updateDrawImage(image) {
			this.drawImage = image
		},

		clearDrawImage() {
			this.$refs.drawer.clear()
			this.drawImage = null
		},

		async saveDrawImage() {
			this.loading = true
			try {
				await uploadSignatureImage(this.drawImage)
				showSuccess(t('certificate24', 'Signature image uploaded.'))
				this.ts = (new Date()).getTime()
				this.settings['has-signature-image'] = true
				this.clearDrawImage()
				document.getElementById('app-content').scrollTop = 0
			} catch (error) {
				console.error('Could not upload signature image', error)
				switch (error.response?.status) {
				case 413:
					showError(t('certificate24', 'The uploaded image is too large.'))
					break
				default:
					showError(t('certificate24', 'Error while uploading signature image.'))
				}
			} finally {
				this.loading = false
			}
		},

		renderTextSignature() {
			const canvas = this.$refs.textCanvas
			if (!canvas) {
				return
			}

			const ctx = canvas.getContext('2d')
			ctx.clearRect(0, 0, canvas.width, canvas.height)

			const text = this.textValue.trim()
			if (!text) {
				return
			}

			const padding = 20
			const maxWidth = canvas.width - padding * 2
			const maxHeight = canvas.height - padding * 2
			let fontSize = maxHeight
			ctx.fillStyle = '#000000'
			ctx.textAlign = 'center'
			ctx.textBaseline = 'middle'
			do {
				ctx.font = fontSize + 'px ' + this.textFont
				if (ctx.measureText(text).width <= maxWidth) {
					break
				}
				fontSize -= 2
			} while (fontSize > 10)
			ctx.font = fontSize + 'px ' + this.textFont
			ctx.fillText(text, canvas.width / 2, canvas.height / 2)
		},

		async saveTextSignature() {
			const canvas = this.$refs.textCanvas
			if (!canvas) {
				return
			}

			this.loading = true
			try {
				const blob = await new Promise((resolve) => {
					canvas.toBlob(resolve, 'image/png')
				})
				await uploadSignatureImage(blob)
				showSuccess(t('certificate24', 'Signature image uploaded.'))
				this.ts = (new Date()).getTime()
				this.settings['has-signature-image'] = true
				this.textValue = ''
				document.getElementById('app-content').scrollTop = 0
			} catch (error) {
				console.error('Could not upload signature image', error)
				switch (error.response?.status) {
				case 413:
					showError(t('certificate24', 'The uploaded image is too large.'))
					break
				default:
					showError(t('certificate24', 'Error while uploading signature image.'))
				}
			} finally {
				this.loading = false
			}
		},

		resetImage() {
			confirmDialog(
				t('certificate24', 'Do you really want to reset the signature image?'),
				t('certificate24', 'Reset signature image'),
			).then(async (decision) => {
				if (!decision) {
					return
				}

				this.loading = true
				try {
					await resetSignatureImage()
					showSuccess(t('certificate24', 'Signature image reset.'))
					this.settings['has-signature-image'] = false
				} catch (error) {
					console.error('Could not reset signature image', error)
					showError(t('certificate24', 'Error while resetting signature image.'))
				} finally {
					this.loading = false
				}
			})
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
		background-color: white;
	}
}

.buttons {
	margin-bottom: 1em;

	.button-vue {
		display: inline;
		margin-right: 0.25em;
	}
}

.drawer {
	border: 1px solid gray;
	border-radius: 4px;
	width: 600px;
	height: 400px;
	margin-bottom: 1em;
	background-color: white;
}

.text-input {
	display: block;
	width: 600px;
	max-width: 100%;
	margin: 0.5em 0;
}

.text-font {
	display: block;
	margin-bottom: 0.5em;
}

.text-preview {
	height: 200px;

	canvas {
		width: 100%;
		height: 100%;
	}
}
</style>
