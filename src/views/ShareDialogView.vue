<template>
	<div>
		<NcModal v-if="showDialog"
			:aria-label="t('esig', 'Request signature')"
			:title="t('esig', 'Request signature')"
			@close="closeModal">
			<div class="modal__content">
				<h1>{{ t('esig', 'Request signature') }}</h1>
				<div v-if="error" class="error">
					{{ error }}
				</div>
				<div class="recipient_section">
					<NcCheckboxRadioSwitch :checked.sync="recipient_type"
						value="user"
						name="recipient_type"
						type="radio">
						{{ t('esig', 'Request signature from user') }}
					</NcCheckboxRadioSwitch>
					<div v-if="userSelected" class="search">
						<NcTextField ref="userField"
							v-observe-visibility="userVisibilityChanged"
							:value.sync="user"
							type="text"
							:placeholder="t('esig', 'Search users')"
							@input="handleUserInput">
							<Magnify :size="16" />
						</NcTextField>
						<NcButton v-if="isSearchingUser"
							class="abort-search"
							type="tertiary-no-background"
							:aria-label="cancelSearchLabel"
							@click="abortUserSearch">
							<template #icon>
								<Close :size="20" />
							</template>
						</NcButton>
						<SearchResults v-if="user !== ''"
							:search-text="user"
							:search-results="userResults"
							:entries-loading="usersLoading"
							:no-results="noUserResults"
							:scrollable="true"
							:selectable="true"
							@click="selectUser" />
					</div>
				</div>
				<div class="recipient_section">
					<NcCheckboxRadioSwitch :checked.sync="recipient_type"
						value="email"
						name="recipient_type"
						type="radio">
						{{ t('esig', 'Request signature from email address') }}
					</NcCheckboxRadioSwitch>
					<div v-if="!userSelected" class="search">
						<NcTextField ref="emailField"
							v-observe-visibility="emailVisibilityChanged"
							:value.sync="email"
							type="text"
							:placeholder="t('esig', 'E-mail address')"
							@input="handleEmailInput">
							<Magnify :size="16" />
						</NcTextField>
						<NcButton v-if="isSearchingEmail"
							class="abort-search"
							type="tertiary-no-background"
							:aria-label="cancelSearchLabel"
							@click="abortEmailSearch">
							<template #icon>
								<Close :size="20" />
							</template>
						</NcButton>
						<SearchResults v-if="email !== ''"
							:search-text="email"
							:search-results="emailResults"
							:entries-loading="emailsLoading"
							:no-results="noEmailResults"
							:scrollable="true"
							:selectable="true"
							@click="selectEmail" />
					</div>
				</div>
				<div>
					<NcButton type="primary"
						@click="requestSignature">
						<template #icon>
							<FileSign />
						</template>
						{{ t('esig', 'Request signature') }}
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<script>
import Close from 'vue-material-design-icons/Close.vue'
import FileSign from 'vue-material-design-icons/FileSign.vue'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import debounce from 'debounce'
import { showSuccess, showError } from '@nextcloud/dialogs'

import { shareFile, search } from '../services/apiservice.js'
import SearchResults from '../components/SearchResults.vue'

export default {
	name: 'ShareDialogView',

	components: {
		Close,
		FileSign,
		Magnify,
		NcButton,
		NcCheckboxRadioSwitch,
		NcModal,
		NcTextField,
		SearchResults,
	},

	data() {
		return {
			showDialog: true,
			error: '',
			recipient_type: 'user',
			noUserResults: false,
			usersLoading: false,
			user: '',
			userResults: {},
			noEmailResults: false,
			emailsLoading: false,
			email: '',
			emailResults: {},
		}
	},

	computed: {
		userSelected() {
			return this.recipient_type === 'user'
		},
		isSearchingUser() {
			return this.user !== ''
		},
		isSearchingEmail() {
			return this.email !== ''
		},
		cancelSearchLabel() {
			return t('esig', 'Cancel search')
		},
	},

	watch: {
		'recipient_type'(newValue) {
			this.error = ''
			this.$nextTick(() => {
				if (newValue === 'user') {
					this.abortUserSearch()
				} else if (newValue === 'email') {
					this.abortEmailSearch()
				}
			})
		},
	},

	async mounted() {
		this.focusUserInput()
	},

	methods: {
		handleUserInput() {
			this.error = ''
			this.noUserResults = false
			this.usersLoading = true
			this.userResults = {}
			this.debounceSearchUsers()
		},

		abortUserSearch() {
			this.noUserResults = false
			this.usersLoading = false
			this.userResults = {}
			this.user = ''
			this.focusUserInput()
		},

		debounceSearchUsers: debounce(function() {
			this.searchUsers()
		}, 250),

		async searchUsers() {
			try {
				const response = await search(this.user, 'user')
				this.userResults = response?.data?.ocs?.data || {}
				if (Array.isArray(this.userResults)) {
					this.userResults = {}
				}
				this.usersLoading = false
				const users = this.userResults.users || []
				const exact = this.userResults.exact?.users || []
				if (!users.length && !exact.length) {
					this.noUserResults = true
				}
			} catch (exception) {
				console.error(exception)
				showError(t('esig', 'An error occurred while performing the search'))
			}
		},

		userVisibilityChanged(isVisible) {
			if (isVisible) {
				this.focusUserInput()
			}
		},
		focusUserInput() {
			this.$refs.userField.$el.focus()
		},

		handleEmailInput() {
			this.error = ''
			this.noEmailResults = false
			this.emailsLoading = true
			this.emailResults = {}
			this.debounceSearchEmails()
		},

		abortEmailSearch() {
			this.noEmailResults = false
			this.emailsLoading = false
			this.emailResults = {}
			this.email = ''
			this.focusEmailInput()
		},

		debounceSearchEmails: debounce(function() {
			this.searchEmails()
		}, 250),

		async searchEmails() {
			const response = await search(this.email, 'email')
			this.emailResults = response?.data?.ocs?.data || {}
			if (Array.isArray(this.emailResults)) {
				this.emailResults = {}
			}
			this.emailsLoading = false
			const emails = this.emailResults.emails || []
			const exact = this.emailResults.exact?.users || []
			if (!emails.length && !exact.length) {
				this.noEmailResults = true
			}
		},

		emailVisibilityChanged(isVisible) {
			if (isVisible) {
				this.focusEmailInput()
			}
		},
		focusEmailInput() {
			this.$refs.emailField.$el.focus()
		},

		closeModal() {
			this.showDialog = false
		},

		selectUser(item) {
			this.user = item.value.shareWith
			this.userResults = {}
			this.noUserResults = false
		},

		selectEmail(item) {
			if (item.value && item.value.shareType === OC.Share.SHARE_TYPE_USER) {
				this.recipient_type = 'user'
				this.selectUser(item)
				return
			}

			const shareWith = item.value?.shareWith || ''
			this.email = shareWith
			this.emailResults = {}
			this.noEmailResults = false
		},

		async requestSignature() {
			const id = this.$root.$data.fileId
			if (!id) {
				showError(t('esig', 'No file selected.'))
				return
			}

			let recipient
			switch (this.recipient_type) {
			case 'user':
				recipient = this.user
				break
			case 'email':
				recipient = this.email
				break
			}
			if (!recipient) {
				this.error = t('esig', 'Please select a recipient first.')
				return
			}

			try {
				await shareFile(id, recipient, this.recipient_type)
				this.showDialog = false
				showSuccess(t('esig', 'Requested signature.'))
			} catch (error) {
				console.error('Could not request signature', id, error)
				const response = error.response
				const data = response.data.ocs?.data || {}
				switch (data.error) {
				case 'unknown_user':
					this.error = t('esig', 'Unknown user.')
					break
				case 'invalid_email':
					this.error = t('esig', 'Invalid email address.')
					break
				case 'error_connecting':
					this.error = t('esig', 'Error connecting to esig service.')
					break
				default:
					this.error = t('esig', 'Error while requesting signature.')
				}
			}
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

.recipient_section {
	margin-bottom: 1em;
}

.error {
	color: red;
	border: 1px solid red;
	border-radius: 4px;
	padding: 6px 10px;
	margin-bottom: 1em;
}

.search {
	position: relative;
	height: 100%;
	display: flex;
	flex-direction: column;
	overflow: hidden;
	.abort-search {
		position: absolute;
		right: 0;
		top: -2px;
		z-index: 2;
	}
}
</style>
