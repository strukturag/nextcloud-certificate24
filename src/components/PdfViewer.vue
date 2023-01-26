<template>
	<div class="pdfviewer">
		<PdfNavigtion v-if="!initialLoad && !loadingFailed"
			:num-pages="numPages"
			:download-url="downloadUrl"
			@set-page="renderPage" />
		<NcLoadingIcon v-if="loading"
			:class="!initialLoad ? 'loader' : ''" />
		<div v-if="loadingFailed">
			<div>
				{{ t('esig', 'Could not load document, please download and review manually.') }}
			</div>
			<NcButton v-if="downloadUrl"
				:href="downloadUrl"
				class="download">
				{{ t('esig', 'Download') }}
				<template #icon>
					<Download :size="20" />
				</template>
			</NcButton>
		</div>
		<PdfDocument v-show="!initialLoad && !loadingFailed"
			:url="downloadUrl"
			:page="page"
			:width="width"
			:height="height"
			:max-height="maxHeight"
			:signature-positions="signaturePositions"
			@pdf:loaded="pdfLoaded"
			@pdf:error="pdfFailed"
			@render:error="renderError"
			@loading:start="loading++"
			@loading:stop="loading--" />
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import Download from 'vue-material-design-icons/Download.vue'
import { showError } from '@nextcloud/dialogs'

import PdfNavigtion from './PdfNavigation.vue'
import externalComponent from '../services/externalComponent.js'

const PdfDocument = () => externalComponent('PdfDocument')

export default {
	name: 'PdfViewer',

	components: {
		NcButton,
		NcLoadingIcon,
		Download,
		PdfNavigtion,
		PdfDocument,
	},

	props: {
		url: {
			type: String,
			required: true,
		},
		downloadUrl: {
			type: String,
			required: false,
			default: '',
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
			page: 0,
			numPages: null,
		}
	},

	computed: {
	},

	async mounted() {
		this.$emit('init:start')
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
	},
}
</script>

<style lang="scss" scoped>
.pdfviewer {
	position: relative;
}

.loader {
	position: absolute;
	left: 0;
	top: 0;
}
</style>
