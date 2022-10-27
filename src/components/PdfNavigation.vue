<template>
	<div class="nav">
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
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import PageFirst from 'vue-material-design-icons/PageFirst.vue'
import PagePrevious from 'vue-material-design-icons/PagePrevious.vue'
import PageNext from 'vue-material-design-icons/PageNext.vue'
import PageLast from 'vue-material-design-icons/PageLast.vue'
import Download from 'vue-material-design-icons/Download.vue'

export default {
	name: 'PdfNavigation',

	components: {
		NcButton,
		NcTextField,
		PageFirst,
		PagePrevious,
		PageNext,
		PageLast,
		Download,
	},

	props: {
		numPages: {
			type: Number,
			required: true,
		},
		downloadUrl: {
			type: String,
			required: false,
			default: '',
		},
	},

	emits: [
		'set-page',
	],

	data() {
		return {
			page: 1,
			pageValue: '1',
		}
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
			this.setPage(newValue)
		},
	},

	methods: {
		setPage(idx) {
			if (idx < 1) {
				idx = 1
			} else if (idx > this.numPages) {
				idx = this.numPages
			}
			this.page = idx
			this.$emit('set-page', idx)
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
.nav {
	position: relative;
	margin: 12px 0;
	display: grid;
	justify-content: center;
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
</style>
