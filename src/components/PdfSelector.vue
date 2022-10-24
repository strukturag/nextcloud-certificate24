<template>
	<div class="pdfselector">
		<PdfNavigtion v-if="!initialLoad && !loadingFailed"
			:num-pages="numPages"
			@set-page="renderPage" />
		<NcLoadingIcon v-if="loading"
			:class="!initialLoad ? 'loader' : ''" />
		<div v-if="loadingFailed">
			<div>
				{{ t('esig', 'Could not load document, signature position is not supported.') }}
			</div>
		</div>
		<div v-if="!loadingFailed"
			v-show="!initialLoad"
			ref="container"
			class="container" />
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import { showError } from '@nextcloud/dialogs'

import PdfNavigtion from './PdfNavigation.vue'
import getVinegarApi from '../services/vinegarapi.js'

export default {
	name: 'PdfSelector',

	components: {
		NcLoadingIcon,
		PdfNavigtion,
	},

	props: {
		url: {
			type: String,
			required: true,
		},
		width: {
			type: Number,
			required: true,
		},
		height: {
			type: Number,
			required: true,
		},
		maxHeight: {
			type: Number,
			required: false,
			default: 0,
		},
		signaturePositions: {
			type: Array,
			required: false,
			default: null,
		},
	},

	data() {
		return {
			initialLoad: true,
			loadingFailed: false,
			loading: 0,
			api: null,
			doc: null,
			numPages: null,
		}
	},

	async mounted() {
		this.$emit('init:start')
		this.loading++
		try {
			const container = this.$refs.container
			container.style.minWidth = this.width + 'px'
			container.style.minHeight = this.height + 'px'
			let scrollbarWidth = 0
			if (this.maxHeight > 0) {
				container.style.maxHeight = this.maxHeight + 'px'
				container.style.minHeight = this.maxHeight + 'px'
				container.style.overflowY = 'scroll'

				// Create the div
				const scrollDiv = document.createElement('div')
				scrollDiv.className = 'scrollbar-measure'
				document.body.appendChild(scrollDiv)

				// Get the scrollbar width
				scrollbarWidth = scrollDiv.offsetWidth - scrollDiv.clientWidth
				// Add space for box shadow
				scrollbarWidth += 10

				// Delete the div
				document.body.removeChild(scrollDiv)
			}
			this.api = await getVinegarApi()
			this.doc = new this.api.PdfDocument(container, {
				url: this.url,
				width: this.width - scrollbarWidth,
				height: this.height,
				enable_select: true,
				signaturePositions: this.signaturePositions,
			})
			this.numPages = await this.doc.numPages()
			this.renderPage(1)
		} catch (e) {
			console.error('Could not load document', e)
			showError(t('esig', 'Could not load document.'))
			this.loadingFailed = true
		} finally {
			this.initialLoad = false
			this.loading--
			this.$emit('init:done')
		}
	},

	methods: {
		async renderPage(idx) {
			this.loading++
			try {
				await this.doc.renderPage(idx)
			} catch (e) {
				console.error('Could not render page', idx, e)
				showError(t('esig', 'Could not render page {page}.', { page: idx }))
			} finally {
				this.loading--
			}
		},

		getSignaturePositions() {
			return this.doc.getSignaturePositions()
		},

		closeModal() {
			this.$emit('close')
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

canvas {
	display: block;
}

.pdfselector {
	position: relative;
}

.loader {
	position: absolute;
	left: 0;
	top: 0;
}

.center {
	display: flex;
	justify-content: center;
}

.container {
	position: relative;
	margin-bottom: 1em;
	width: 100%;
}

.container:deep {
	canvas {
		border: 1px solid #888;
	}

	canvas.pdfpage {
		box-shadow: 2px 2px 5px #888;
	}

	canvas.pdfoverlay {
		box-shadow: 2px 2px 5px white;
	}
}
</style>

<style lang="scss">
.scrollbar-measure {
	width: 100px;
	height: 100px;
	overflow: scroll;
	position: absolute;
	top: -9999px;
}
</style>
