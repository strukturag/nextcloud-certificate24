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
	<NcSettingsSection :title="t('certificate24', 'Signature properties')"
		:description="t('certificate24', 'Additional properties for signature processing can be configured here.')">
		<div>
			<h4>{{ t('certificate24', 'Default action to perform when a file was signed successfully.') }}</h4>
			<NcCheckboxRadioSwitch :checked.sync="settings.signed_save_mode"
				value="new"
				name="signed_save_mode"
				type="radio"
				@update:checked="debounceUpdateMode">
				{{ t('certificate24', 'Create new signed file next to original file') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked.sync="settings.signed_save_mode"
				value="replace"
				name="signed_save_mode"
				type="radio"
				@update:checked="debounceUpdateMode">
				{{ t('certificate24', 'Replace original file with signed file') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked.sync="settings.signed_save_mode"
				value="none"
				name="signed_save_mode"
				type="radio"
				@update:checked="debounceUpdateMode">
				{{ t('certificate24', 'Don\'t save signed file automatically') }}
			</NcCheckboxRadioSwitch>
		</div>
		<div>
			<NcTextField :value.sync="settings.delete_max_age"
				:label="t('certificate24', 'Number of days after which fully signed signature requests are deleted.')"
				:label-visible="true"
				:error="!!errors.delete_max_age"
				:helper-text="errors.delete_max_age"
				type="number"
				min="0"
				placeholder="30"
				:disabled="loading"
				@update:value="debounceUpdateDeleteMaxAge" />
		</div>
	</NcSettingsSection>
</template>

<script>
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import debounce from 'debounce'

export default {
	name: 'SignatureProperties',

	components: {
		NcSettingsSection,
		NcCheckboxRadioSwitch,
		NcTextField,
	},

	data() {
		return {
			loading: false,
			settings: {},
			errors: {},
		}
	},

	beforeMount() {
		this.settings = loadState('certificate24', 'settings')
		if (this.settings.delete_max_age) {
			this.settings.delete_max_age = String(this.settings.delete_max_age)
		}
	},

	methods: {
		debounceUpdateMode: debounce(function() {
			this.updateMode()
		}, 500),

		async updateMode() {
			this.loading = true

			const self = this
			OCP.AppConfig.setValue('certificate24', 'signed_save_mode', this.settings.signed_save_mode, {
				success() {
					showSuccess(t('certificate24', 'Settings saved'))
					self.loading = false
				},
				error() {
					showError(t('certificate24', 'Could not save settings'))
					self.loading = false
				},
			})
		},

		debounceUpdateDeleteMaxAge: debounce(function() {
			this.updateDeleteMaxAge()
		}, 500),

		async updateDeleteMaxAge() {
			this.$delete(this.errors, 'delete_max_age')
			const val = this.settings.delete_max_age
			if (!val) {
				this.$set(this.errors, 'delete_max_age', t('certificate24', 'The value may not be empty.'))
				return
			} else if (val < 0) {
				this.$set(this.errors, 'delete_max_age', t('certificate24', 'The value may not be negative.'))
				return
			}

			this.loading = true

			const self = this
			OCP.AppConfig.setValue('certificate24', 'delete_max_age', this.settings.delete_max_age, {
				success() {
					showSuccess(t('certificate24', 'Settings saved'))
					self.loading = false
				},
				error() {
					showError(t('certificate24', 'Could not save settings'))
					self.loading = false
				},
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.input-field:deep {
	.input-field__main-wrapper {
		max-width: 100px;
	}
}
</style>
