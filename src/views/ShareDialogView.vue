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
						:disabled="shareLoading"
						value="user"
						name="recipient_type"
						type="radio">
						{{ t('esig', 'Request signature from user') }}
					</NcCheckboxRadioSwitch>
					<div v-if="userSelected" class="search">
						<NcTextField ref="userField"
							v-observe-visibility="userVisibilityChanged"
							:disabled="shareLoading"
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
						:disabled="shareLoading"
						value="email"
						name="recipient_type"
						type="radio">
						{{ t('esig', 'Request signature from email address') }}
					</NcCheckboxRadioSwitch>
					<div v-if="!userSelected" class="search">
						<NcTextField ref="emailField"
							v-observe-visibility="emailVisibilityChanged"
							:disabled="shareLoading"
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
					<label>
						{{ t('esig', 'Action to perform when the file was signed successfully:') }}
						<select id="signed_save_mode" v-model="signed_save_mode">
							<option value="new">
								{{ t('esig', 'Create new signed file next to original file') }}
							</option>
							<option value="replace">
								{{ t('esig', 'Replace original file with signed file') }}
							</option>
							<option value="none">
								{{ t('esig', 'Don\'t save signed file automatically') }}
							</option>
						</select>
					</label>
				</div>
				<div v-if="signaturePositions && signaturePositions.length">
					{{ n('esig', '%n signature field positioned', '%n signature fields positioned', signaturePositions.length) }}
				</div>
				<div class="buttons">
					<NcButton v-if="fileModel && fileModel.canDownload()"
						type="secondary"
						@click="openSelectModal">
						<template #icon>
							<FileSign />
						</template>
						{{ t('esig', 'Select signature position') }}
					</NcButton>
					<NcButton type="primary"
						:disabled="shareLoading"
						@click="requestSignature">
						<template #icon>
							<FileSign />
						</template>
						{{ t('esig', 'Request signature') }}
					</NcButton>
				</div>
			</div>
			<SelectorDialogModal v-if="showSelectModal"
				:url="getFileUrl(fileModel)"
				:signature-positions="signaturePositions"
				@close="closeSelectModal" />
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
import { loadState } from '@nextcloud/initial-state'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { generateRemoteUrl } from '@nextcloud/router'

import { shareFile, search, getMetadata } from '../services/apiservice.js'
import SelectorDialogModal from '../components/SelectorDialogModal.vue'
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
		SelectorDialogModal,
		SearchResults,
	},

	data() {
		return {
			fileModel: null,
			showSelectModal: false,
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
			shareLoading: false,
			signaturePositions: [],
			settings: {},
			signed_save_mode: null,
			prevMetadata: {},
		}
	},

	computed: {
		showDialog() {
			return !!this.fileModel
		},
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

	beforeMount() {
		this.settings = loadState('esig', 'public-settings')
	},

	created() {
		this.$root.$watch('fileModel', async (newValue) => {
			this.fileModel = newValue
			this.signed_save_mode = this.settings.signed_save_mode
			this.recipient_type = 'user'
			this.noUserResults = false
			this.usersLoading = false
			this.user = ''
			this.userResults = {}
			this.noEmailResults = false
			this.emailsLoading = false
			this.email = ''
			this.emailResults = {}
			this.shareLoading = false
			this.signaturePositions = []
			if (newValue) {
				const metadata = await getMetadata(newValue.id)
				if (metadata && metadata.signature_fields) {
					this.signaturePositions = metadata.signature_fields
				}
			}
		})
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
			if (this.$refs.userField && this.$refs.userField.$el) {
				this.$refs.userField.$el.focus()
			}
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
			if (this.$refs.emailField && this.$refs.emailField.$el) {
				this.$refs.emailField.$el.focus()
			}
		},

		closeModal() {
			this.$root.$emit('dialog:closed')
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
			if (!this.fileModel) {
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

			const recipients = [
				{
					type: this.recipient_type,
					value: recipient,
				},
			]

			this.error = ''
			this.shareLoading = true
			try {
				let metadata
				if (this.signaturePositions && this.signaturePositions.length) {
					metadata = {
						version: '1.0',
						signature_fields: this.signaturePositions,
					}
				}
				const options = {
					signed_save_mode: this.signed_save_mode,
				}
				await shareFile(this.fileModel.id, recipients, options, metadata)
				this.shareLoading = false
				this.closeModal()
				showSuccess(t('esig', 'Requested signature.'))
			} catch (error) {
				this.shareLoading = false
				console.error('Could not request signature', this.fileModel, error)
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

		openSelectModal() {
			this.showSelectModal = true
		},

		closeSelectModal(positions) {
			this.showSelectModal = false
			this.signaturePositions = positions
		},

		getFileUrl(model) {
			return generateRemoteUrl('webdav') + model.getFullPath()
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

.buttons {
	display: flex;
	justify-content: space-between;
}
</style>
