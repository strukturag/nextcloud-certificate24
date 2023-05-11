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
			<div v-if="settings.last_verified">
				{{ t('esig', 'Last verification: {timestamp}', {
					timestamp: formatDate(settings.last_verified),
				}) }}
			</div>
			<div v-else>
				{{ t('esig', 'Last verification: none yet') }}
			</div>
			<div v-if="settings.unverified_count !== null">
				{{ t('esig', 'Number of pending verifications: {count}', {
					count: settings.unverified_count,
				}) }}
			</div>
			<NcButton :disabled="clearing"
				@click="clearVerification">
				{{ t('esig', 'Clear verification cache') }}
			</NcButton>
		</div>
	</NcSettingsSection>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import debounce from 'debounce'

import { formatDate } from '../../services/formatter.js'
import { clearVerificationCache } from '../../services/apiservice.js'

export default {
	name: 'InstanceSettings',

	components: {
		NcButton,
		NcSettingsSection,
		NcCheckboxRadioSwitch,
	},

	data() {
		return {
			loading: false,
			clearing: false,
			settings: {},
		}
	},

	beforeMount() {
		this.settings = loadState('esig', 'settings')
	},

	methods: {
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

		formatDate(d) {
			return formatDate(d)
		},

		clearVerification() {
			OC.dialogs.confirm(
				t('esig', 'Do you really want to delete the verification cache? This will require that all files need to be verified again.'),
				t('esig', 'Clear verification cache'),
				async function(decision) {
					if (!decision) {
						return
					}

					this.clearing = true
					try {
						const response = await clearVerificationCache()
						this.settings.last_verified = null
						const unverifiedCount = response.data.ocs?.data?.unverified_count || null
						if (unverifiedCount !== null) {
							this.settings.unverified_count = unverifiedCount
						}
						showSuccess(t('esig', 'Verification cache cleared.'))
					} catch (error) {
						console.error('Could not clear verification cache', error)
						showError(t('esig', 'Error while clearing verification cache.'))
					} finally {
						this.clearing = false
					}
				}.bind(this)
			)
		},
	},
}
</script>
