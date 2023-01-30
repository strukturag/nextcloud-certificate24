<template>
	<NcModal :aria-label="t('esig', 'Select signature position')"
		:title="t('esig', 'Select signature position')"
		size="large"
		@close="closeModal">
		<div class="modal__content">
			<h1>{{ t('esig', 'Select signature position') }}</h1>
			<div class="document">
				<PdfSelector :width="800"
					:height="1132"
					:max-height="400"
					:url="url"
					:recipients="recipients"
					:signature-positions="signaturePositions"
					@loading:start="loading++"
					@loading:stop="loading--"
					@rectangles:update="updateRectangles" />
			</div>
			<NcButton type="primary"
				:disabled="loading > 0"
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

import externalComponent from '../services/externalComponent.js'

const PdfSelector = () => externalComponent('PdfSelector')

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
		recipients: {
			type: Array,
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
			loading: 0,
			rectangles: null,
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

		updateRectangles(rects) {
			this.rectangles = rects
		},

		closeModal() {
			this.$emit('close', this.rectangles || this.signaturePositions)
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
