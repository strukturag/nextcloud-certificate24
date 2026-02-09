<!-- eslint-disable vue/no-v-html -->
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
	<NcSettingsSection :name="t('certificate24', 'Account settings')"
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
				:placeholder="t('certificate24', 'the-secret-value')"
				:disabled="loading"
				:aria-label="t('certificate24', 'Account Secret')"
				@input="debounceUpdateAccount">
		</div>

		<div v-html="accountDescription" />
		<div>{{ t('certificate24', 'Name: {theme}', {'theme': theme.name}) }}</div>
		<div>{{ t('certificate24', 'Nextcloud Url: {url}', {'url': nextcloud.url}) }}</div>
		<div>
			<NcButton :disabled="checking || !account_id || !account_secret"
				@click="checkAccountSettings()">
				<template v-if="checking" #icon>
					<NcLoadingIcon />
				</template>
				{{ t('certificate24', 'Check account settings') }}
			</NcButton>
		</div>
	</NcSettingsSection>
</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import debounce from 'debounce'

import { checkAccountSettings } from '../../services/apiservice.js'

export default {
	name: 'AccountSettings',

	components: {
		NcButton,
		NcLoadingIcon,
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
			checking: false,
			nextcloud: {},
			theme: OC.theme,
		}
	},

	computed: {
		accountDescription() {
			return t('certificate24', 'If you don\'t have an account at Certificate24 yet, please login to <a href="{server}" target="_blank">{server}</a> and create an account with the following data:', {
				server: this.web_server,
			})
		},
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
		t,

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

		async checkAccountSettings() {
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				OC.PasswordConfirmation.requirePasswordConfirmation(() => {
					this.checkAccountSettings()
				})
				return
			}

			this.checking = true
			try {
				const result = await checkAccountSettings()
				const name = result.data.ocs?.data?.name || null
				if (name) {
					showSuccess(t('certificate24', 'The settings for account "{name}" are valid.', {
						name,
					}))
				} else {
					showSuccess(t('certificate24', 'The account settings are valid.'))
				}
			} catch (error) {
				console.error('Could not check account settings', error)
				let message = error.response.data.ocs?.data?.error || ''
				const details = error.response.data.ocs?.data?.details || ''
				switch (message) {
				case 'unconfigured':
					message = t('certificate24', 'No account id and/or secret configured.')
					break
				case 'not_authenticated':
					message = t('certificate24', 'Invalid account id and/or secret configured.')
					break
				case 'invalid_url':
					message = t('certificate24', 'The account url doesn\'t match the url of your Nextcloud instance.')
					break
				case 'bad_response':
					if (details) {
						message = t('certificate24', 'Bad response received from backend service: {details}', {
							details,
						})
					} else {
						message = t('certificate24', 'Bad response received from backend service, please check your Nextcloud log for details.')
					}
					break
				case 'connect_exception':
					if (details) {
						message = t('certificate24', 'Error connecting to the backend service: {details}', {
							details,
						})
					} else {
						message = t('certificate24', 'Error connecting to the backend service, please check your Nextcloud log for details.')
					}
					break
				default:
					message = t('certificate24', 'Error while checking account settings, please check your Nextcloud log for details.')
				}
				showError(message)
			} finally {
				setTimeout(() => {
					this.checking = false
				}, 250)
			}
		},
	},
}
</script>
<style scoped lang="scss">
input {
	width: 400px;
	vertical-align: middle;
}

div:deep {
	a {
		color: var(--color-primary-default);

		&:hover {
			color: var(--color-primary-hover)
		}
	}
}
</style>
