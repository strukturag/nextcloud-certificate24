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
		<PdfDocument v-show="!initialLoad && !loadingFailed"
			:url="url"
			:page="page"
			:width="width"
			:height="height"
			:max-height="maxHeight"
			:enable-select="true"
			:signature-positions="signaturePositions"
			:recipients="recipients"
			@pdf:loaded="pdfLoaded"
			@pdf:error="pdfFailed"
			@render:error="renderError"
			@loading:start="loading++"
			@loading:stop="loading--"
			@update:rectangles="updateRectangles" />
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import { showError } from '@nextcloud/dialogs'

import PdfNavigtion from './PdfNavigation.vue'
import externalComponent from '../services/externalComponent.js'

const PdfDocument = () => externalComponent('PdfDocument')

export default {
	name: 'PdfSelector',

	components: {
		NcLoadingIcon,
		PdfNavigtion,
		PdfDocument,
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
		recipients: {
			type: Array,
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
			page: 0,
			numPages: null,
			updatedRects: null,
		}
	},

	async mounted() {
		this.$emit('init:start')
		this.updatedRects = this.signaturePositions || []
	},

	methods: {
		pdfLoaded(properties) {
			this.numPages = properties.numPages
			this.initialLoad = false
			this.$emit('init:done')
		},

		pdfFailed(error) {
			console.error('Could not load document', error)
			showError(t('esig', 'Could not load document, please download and review manually.'))
			this.loadingFailed = true
			this.initialLoad = false
			this.$emit('init:done')
		},

		renderPage(idx) {
			this.page = idx
		},

		renderError(idx, error) {
			console.error('Could not render page', idx, error)
			showError(t('esig', 'Could not render page {page}.', { page: idx }))
		},

		updateRectangles(rects) {
			this.updatedRects = rects
		},

		getSignaturePositions() {
			return this.updatedRects
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
