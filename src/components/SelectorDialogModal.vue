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
	<NcModal :aria-label="t('certificate24', 'Select signature position')"
		:name="t('certificate24', 'Select signature position')"
		size="large"
		@close="closeModal">
		<div class="modal__content">
			<h1>{{ t('certificate24', 'Select signature position') }}</h1>
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
				{{ t('certificate24', 'Save') }}
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

const PdfSelector = externalComponent('PdfSelector')

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
