<template>
	<div id="general_settings" class="esig section">
		<h2>{{ t('esig', 'General settings') }}</h2>

		<h3>{{ t('esig', 'Account settings') }}</h3>

		<div>
			<h4>{{ t('esig', 'Server') }}</h4>
			<div>{{ server }}</div>
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
	</div>
</template>

<script>
import { showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import debounce from 'debounce'

export default {
	name: 'GeneralSettings',

	components: {
	},

	data() {
		return {
			account_id: '',
			account_secret: '',
			server: '',
			loading: false,
			saved: false,
		}
	},

	beforeMount() {
		const state = loadState('esig', 'account')
		this.account_id = state.id
		this.account_secret = state.secret
		this.server = state.server
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
