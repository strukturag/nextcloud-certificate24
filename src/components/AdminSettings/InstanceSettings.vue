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
	<NcSettingsSection :title="t('esig', 'Instance settings')"
		:description="t('esig', 'Settings of the Nextcloud instance can be configured here.')">
		<div>
			<NcCheckboxRadioSwitch :checked.sync="settings.intranet_instance"
				type="switch"
				@update:checked="debounceUpdateIntranet">
				{{ t('esig', 'The Nextcloud instance is private and can not be accessed from the internet.') }}
				{{ t('esig', 'If this is set, links to the instance will not be sent to external users.') }}
			</NcCheckboxRadioSwitch>
		</div>
		<div>
			<NcCheckboxRadioSwitch :checked.sync="settings.insecure_skip_verify"
				type="switch"
				@update:checked="debounceUpdateInsecureVerify">
				{{ t('esig', 'Skip verification of certificates when communicating with the backend service.') }}
				{{ t('esig', 'This is potentially insecure and should only be enabled during development (if necessary).') }}
			</NcCheckboxRadioSwitch>
		</div>
		<div>
			<NcCheckboxRadioSwitch :checked.sync="settings.background_verify"
				type="switch"
				@update:checked="debounceUpdateBackgroundVerify">
				{{ t('esig', 'Verify document signatures in the background.') }}
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
	name: 'InstanceSettings',

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
		debounceUpdateIntranet: debounce(function() {
			this.updateIntranet()
		}, 500),

		updateIntranet() {
			this.loading = true

			const self = this
			OCP.AppConfig.setValue('esig', 'intranet_instance', this.settings.intranet_instance, {
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

		debounceUpdateInsecureVerify: debounce(function() {
			this.updateInsecureSkipVerify()
		}, 500),

		updateInsecureSkipVerify() {
			this.loading = true

			const self = this
			OCP.AppConfig.setValue('esig', 'insecure_skip_verify', this.settings.insecure_skip_verify, {
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

		debounceUpdateBackgroundVerify: debounce(function() {
			this.updateBackgroundVerify()
		}, 500),

		updateBackgroundVerify() {
			this.loading = true

			const self = this
			OCP.AppConfig.setValue('esig', 'background_verify', this.settings.background_verify, {
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
