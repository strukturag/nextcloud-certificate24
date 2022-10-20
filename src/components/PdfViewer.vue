<template>
	<div class="pdfviewer">
		<div v-if="!initialLoad && !loadingFailed"
			class="nav center">
			<NcButton v-tooltip="t('esig', 'First page')"
				:disabled="page === 1"
				@click="firstPage">
				<template #icon>
					<PageFirst :size="20" />
				</template>
			</NcButton>
			<NcButton v-tooltip="t('esig', 'Previous page')"
				:disabled="page === 1"
				@click="prevPage">
				<template #icon>
					<PagePrevious :size="20" />
				</template>
			</NcButton>
			<div class="pageinfo">
				<NcTextField :value.sync="pageValue"
					class="curpage"
					label="Page" />
				<span class="numpages">
					/ {{ numPages }}
				</span>
			</div>
			<NcButton v-tooltip="t('esig', 'Next page')"
				:disabled="page === numPages"
				@click="nextPage">
				<template #icon>
					<PageNext :size="20" />
				</template>
			</NcButton>
			<NcButton v-tooltip="t('esig', 'Last page')"
				:disabled="page === numPages"
				@click="lastPage">
				<template #icon>
					<PageLast :size="20" />
				</template>
			</NcButton>
			<NcButton v-if="downloadUrl"
				v-tooltip="t('esig', 'Download')"
				:href="downloadUrl"
				class="download">
				<template #icon>
					<Download :size="20" />
				</template>
			</NcButton>
		</div>
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
		<div v-if="!loadingFailed"
			v-show="!initialLoad"
			ref="container"
			class="container center" />
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import PageFirst from 'vue-material-design-icons/PageFirst.vue'
import PagePrevious from 'vue-material-design-icons/PagePrevious.vue'
import PageNext from 'vue-material-design-icons/PageNext.vue'
import PageLast from 'vue-material-design-icons/PageLast.vue'
import Download from 'vue-material-design-icons/Download.vue'
import { showError } from '@nextcloud/dialogs'

import getVinegarApi from '../services/vinegarapi.js'

export default {
	name: 'PdfViewer',

	components: {
		NcButton,
		NcLoadingIcon,
		NcTextField,
		PageFirst,
		PagePrevious,
		PageNext,
		PageLast,
		Download,
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
	},

	data() {
		return {
			initialLoad: true,
			loadingFailed: false,
			loading: 0,
			api: null,
			doc: null,
			numPages: null,
			page: 1,
			pageValue: '1',
		}
	},

	computed: {
	},

	watch: {
		pageValue(newValue) {
			if (!newValue) {
				return
			}

			const idx = Number.parseInt(newValue, 10)
			if (isNaN(idx)) {
				return
			}

			if (idx < 1) {
				this.pageValue = '1'
				return
			} else if (idx > this.numPages) {
				this.pageValue = String(this.numPages)
				return
			}

			if (idx !== this.page) {
				this.page = idx
			}
		},

		page(newValue) {
			this.pageValue = String(newValue)
			this.renderPage(newValue)
		},
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
			})
			this.numPages = await this.doc.numPages()
			this.renderPage(1)
		} catch (e) {
			console.error('Could not load document', e)
			showError(t('esig', 'Could not load document, please download and review manually.'))
			this.loadingFailed = true
		} finally {
			this.initialLoad = false
			this.loading--
			this.$emit('init:done')
		}
	},

	methods: {
		setPage(idx) {
			if (idx < 1) {
				idx = 1
			} else if (idx > this.numPages) {
				idx = this.numPages
			}
			this.page = idx
		},

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

		firstPage() {
			this.setPage(1)
		},

		prevPage() {
			this.setPage(this.page - 1)
		},

		nextPage() {
			this.setPage(this.page + 1)
		},

		lastPage() {
			this.setPage(this.numPages)
		},
	},
}
</script>

<style lang="scss" scoped>
canvas {
	display: block;
}

.pdfviewer {
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

.nav {
	position: relative;
	margin: 12px 0;
	display: grid;
	grid-template-columns: 1fr repeat(5, auto) 1fr;
	grid-column-gap: 5px;

	button:nth-child(1) {
		grid-column-start: 2;
	}

	.download {
		margin-left: auto;
	}
}

.pageinfo {
	display: flex;
	align-items: center;
	margin: 0 1em;

	.numpages {
		margin-left: 1em;
	}

	.curpage {
		max-width: 70px;
	}
}

.container {
	margin-bottom: 1em;
	width: 100%;
}

.container:deep canvas {
	border: 1px solid #888;
	box-shadow: 2px 2px 5px #888;
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
