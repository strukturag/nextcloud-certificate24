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
	<NcSettingsSection :title="t('certificate24', 'Account settings')"
		:description="t('certificate24', 'The configured account will be used to request signatures and sign files.')">
		<div>
			<h4>{{ t('certificate24', 'Server') }}</h4>
			<div>{{ api_server }}</div>
		</div>

		<div>
			<h4>{{ t('certificate24', 'Account Id') }}</h4>
			<input ref="account_id"
				v-model="account_id"
				type="text"
				name="account_id"
				placeholder="1234-abcd-5678-efgh"
				:disabled="loading"
				:aria-label="t('certificate24', 'Account Id')"
				@input="debounceUpdateAccount">
		</div>

		<div>
			<h4>{{ t('certificate24', 'Account Secret') }}</h4>
			<input ref="account_secret"
				v-model="account_secret"
				type="text"
				name="account_secret"
				placeholder="the-secret-value"
				:disabled="loading"
				:aria-label="t('certificate24', 'Account Secret')"
				@input="debounceUpdateAccount">
		</div>

		<div>{{ t('certificate24', 'If you don\'t have an account at Certificate24 yet, please login to {server} and create an account with the following data:', {'server': web_server}) }}</div>
		<div>{{ t('certificate24', 'Name: {theme}', {'theme': theme.name}) }}</div>
		<div>{{ t('certificate24', 'Nextcloud Url: {url}', {'url': nextcloud.url}) }}</div>
	</NcSettingsSection>
</template>

<script>
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import debounce from 'debounce'

export default {
	name: 'AccountSettings',

	components: {
		NcSettingsSection,
	},

	data() {
		return {
			account_id: '',
			account_secret: '',
			api_server: '',
			web_server: '',
			loading: false,
			saved: false,
			nextcloud: {},
			theme: OC.theme,
		}
	},

	beforeMount() {
		const state = loadState('certificate24', 'account')
		this.account_id = state.id
		this.account_secret = state.secret
		this.api_server = state.api_server
		this.web_server = state.web_server
		this.nextcloud = loadState('certificate24', 'nextcloud')
	},

	methods: {
		debounceUpdateAccount: debounce(function() {
			this.updateAccount()
		}, 1000),

		async updateAccount() {
			this.loading = true

			const self = this
			OCP.AppConfig.setValue('certificate24', 'account', JSON.stringify({
				id: this.account_id,
				secret: this.account_secret,
			}), {
				success() {
					showSuccess(t('certificate24', 'Account settings saved'))
					self.loading = false
					self.toggleSave()
				},
				error() {
					showError(t('certificate24', 'Could not save account settings'))
					self.loading = false
					self.toggleSave()
				},
			})
		},

		toggleSave() {
			this.saved = true
			setTimeout(() => {
				this.saved = false
			}, 3000)
		},
	},
}
</script>
<style scoped lang="scss">
input {
	width: 400px;
	vertical-align: middle;
}
</style>
