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
	<NcSettingsSection :title="t('esig', 'Signature properties')"
		:description="t('esig', 'Additional properties for signature processing can be configured here.')">
		<div>
			<h4>{{ t('esig', 'Default action to perform when a file was signed successfully.') }}</h4>
			<NcCheckboxRadioSwitch :checked.sync="settings.signed_save_mode"
				value="new"
				name="signed_save_mode"
				type="radio"
				@update:checked="debounceUpdateMode">
				{{ t('esig', 'Create new signed file next to original file') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked.sync="settings.signed_save_mode"
				value="replace"
				name="signed_save_mode"
				type="radio"
				@update:checked="debounceUpdateMode">
				{{ t('esig', 'Replace original file with signed file') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch :checked.sync="settings.signed_save_mode"
				value="none"
				name="signed_save_mode"
				type="radio"
				@update:checked="debounceUpdateMode">
				{{ t('esig', 'Don\'t save signed file automatically') }}
			</NcCheckboxRadioSwitch>
		</div>
	</NcSettingsSection>
</template>

<script>
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import debounce from 'debounce'

export default {
	name: 'SignatureProperties',

	components: {
		NcSettingsSection,
		NcCheckboxRadioSwitch,
	},

	data() {
		return {
			loading: false,
			settings: {},
		}
	},

	beforeMount() {
		this.settings = loadState('esig', 'settings')
	},

	methods: {
		debounceUpdateMode: debounce(function() {
			this.updateMode()
		}, 500),

		async updateMode() {
			this.loading = true

			const self = this
			OCP.AppConfig.setValue('esig', 'signed_save_mode', this.settings.signed_save_mode, {
				success() {
					showSuccess(t('esig', 'Settings saved'))
					self.loading = false
				},
				error() {
					showError(t('esig', 'Could not save settings'))
					self.loading = false
				},
			})
		},
	},
}
</script>
