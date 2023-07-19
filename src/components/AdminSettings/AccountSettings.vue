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
	<NcSettingsSection :title="t('esig', 'Account settings')"
		:description="t('esig', 'The configured account will be used to request signatures and sign files.')">
		<div>
			<h4>{{ t('esig', 'Server') }}</h4>
			<div>{{ api_server }}</div>
		</div>

		<div>
			<h4>{{ t('esig', 'Account Id') }}</h4>
			<input ref="account_id"
				v-model="account_id"
				type="text"
				name="account_id"
				placeholder="1234-abcd-5678-efgh"
				:disabled="loading"
				:aria-label="t('esig', 'Account Id')"
				@input="debounceUpdateAccount">
		</div>

		<div>
			<h4>{{ t('esig', 'Account Secret') }}</h4>
			<input ref="account_secret"
				v-model="account_secret"
				type="text"
				name="account_secret"
				placeholder="the-secret-value"
				:disabled="loading"
				:aria-label="t('esig', 'Account Secret')"
				@input="debounceUpdateAccount">
		</div>

		<div>{{ t('esig', 'If you don\'t have an account on the esig service yet, please login to {server} and create an account with the following data:', {'server': web_server}) }}</div>
		<div>{{ t('esig', 'Name: {theme}', {'theme': theme.name}) }}</div>
		<div>{{ t('esig', 'Nextcloud Url: {url}', {'url': nextcloud.url}) }}</div>
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
		const state = loadState('esig', 'account')
		this.account_id = state.id
		this.account_secret = state.secret
		this.api_server = state.api_server
		this.web_server = state.web_server
		this.nextcloud = loadState('esig', 'nextcloud')
	},

	methods: {
		debounceUpdateAccount: debounce(function() {
			this.updateAccount()
		}, 1000),

		async updateAccount() {
			this.loading = true

			const self = this
			OCP.AppConfig.setValue('esig', 'account', JSON.stringify({
				id: this.account_id,
				secret: this.account_secret,
			}), {
				success() {
					showSuccess(t('esig', 'Account settings saved'))
					self.loading = false
					self.toggleSave()
				},
				error() {
					showError(t('esig', 'Could not save account settings'))
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
