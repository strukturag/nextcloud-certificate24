<template>
	<NcModal :aria-label="t('esig', 'Select signature position')"
		:title="t('esig', 'Select signature position')"
		size="large"
		@close="closeModal">
		<div class="modal__content">
			<h1>{{ t('esig', 'Select signature position') }}</h1>
			<div class="document">
				<PdfSelector ref="selector"
					:width="800"
					:height="1132"
					:max-height="400"
					:url="url"
					:signature-positions="signaturePositions"
					@init:start="loading = true"
					@init:done="loading = false" />
			</div>
			<NcButton type="primary"
				:disabled="loading"
				@click="savePositions()">
				{{ t('esig', 'Save') }}
				<template #icon>
					<Check :size="20" />
				</template>
			</NcButton>
		</div>
	</NcModal>
</template>

<script>
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import Check from 'vue-material-design-icons/Check.vue'

import PdfSelector from '../components/PdfSelector.vue'

export default {
	name: 'SelectorDialogModal',

	components: {
		NcButton,
		NcModal,
		Check,
		PdfSelector,
	},

	props: {
		url: {
			type: String,
			required: true,
		},
		signaturePositions: {
			type: Array,
			required: false,
			default: null,
		},
	},

	data() {
		return {
			loading: false,
		}
	},

	mounted() {
		const elems = this.$el.getElementsByClassName('modal__content')
		elems[0].scrollTop = 0
	},

	methods: {
		savePositions() {
			this.closeModal()
		},

		closeModal() {
			const positions = this.$refs.selector.getSignaturePositions()
			this.$emit('close', positions)
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
