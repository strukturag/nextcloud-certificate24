<template>
	<div>
		<NcModal v-if="showDialog"
			:aria-label="t('esig', 'Request signature')"
			:title="t('esig', 'Request signature')"
			@close="closeModal">
			<div ref="content" class="modal__content">
				<h1>{{ t('esig', 'Request signature') }}</h1>
				<div v-if="error" class="error">
					{{ error }}
				</div>
				<div v-if="recipients.length > 0"
					class="recipients_section">
					<h2>{{ t('esig', 'Recipients') }}</h2>
					<ul>
						<NcListItemIcon v-for="recipient in recipients"
							:key="recipient.type + '-' + recipient.value"
							:user="recipient.type === 'user' ? recipient.value : undefined"
							:disable-menu="true"
							:show-user-status="false"
							:show-user-status-compact="false"
							:title="recipientName(recipient)"
							:subtitle="recipientTitle(recipient)">
							<NcActions>
								<NcActionButton @click="deleteRecipient(recipient)">
									<template #icon>
										<Delete :size="20" />
									</template>
									{{ t('esig', 'Delete recipient') }}
								</NcActionButton>
							</NcActions>
						</NcListItemIcon>
					</ul>
				</div>
				<div class="recipient_section">
					<NcCheckboxRadioSwitch :checked.sync="recipient_type"
						:disabled="shareLoading"
						value="user"
						name="recipient_type"
						type="radio">
						{{ t('esig', 'Add user') }}
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
							@click="addUser" />
					</div>
				</div>
				<div class="recipient_section">
					<NcCheckboxRadioSwitch :checked.sync="recipient_type"
						:disabled="shareLoading"
						value="email"
						name="recipient_type"
						type="radio">
						{{ t('esig', 'Add email address') }}
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
							@click="addEmail" />
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
						:disabled="selectModalLoading"
						@click="openSelectModal">
						<template #icon>
							<NcLoadingIcon v-show="selectModalLoading" :size="24" />
							<FileSign v-show="!selectModalLoading" />
						</template>
						{{ t('esig', 'Select signature position') }}
					</NcButton>
					<NcButton type="primary"
						:disabled="shareLoading || !recipients.length"
						@click="requestSignature">
						<template #icon>
							<NcLoadingIcon v-show="shareLoading" :size="24" />
							<FileSign v-show="!shareLoading" />
						</template>
						{{ t('esig', 'Request signature') }}
					</NcButton>
				</div>
			</div>
			<SelectorDialogModal v-if="showSelectModal"
				:url="getFileUrl(fileModel)"
				:signature-positions="signaturePositions"
				:recipients="recipients"
				@close="closeSelectModal" />
		</NcModal>
	</div>
</template>

<script>
import Close from 'vue-material-design-icons/Close.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import FileSign from 'vue-material-design-icons/FileSign.vue'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcListItemIcon from '@nextcloud/vue/dist/Components/NcListItemIcon.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import debounce from 'debounce'
import { loadState } from '@nextcloud/initial-state'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { generateRemoteUrl } from '@nextcloud/router'

import { shareFile, search, getMetadata } from '../services/apiservice.js'
import getVinegarApi from '../services/vinegarapi.js'
import SelectorDialogModal from '../components/SelectorDialogModal.vue'
import SearchResults from '../components/SearchResults.vue'

export default {
	name: 'ShareDialogView',

	components: {
		Close,
		Delete,
		FileSign,
		Magnify,
		NcActions,
		NcActionButton,
		NcButton,
		NcCheckboxRadioSwitch,
		NcModal,
		NcTextField,
		NcListItemIcon,
		NcLoadingIcon,
		SelectorDialogModal,
		SearchResults,
	},

	data() {
		return {
			fileModel: null,
			showSelectModal: false,
			error: '',
			recipients: [],
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
			selectModalLoading: false,
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
			this.clearError()
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
			this.recipients = []
			this.clearError()
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
			this.clearError()
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
			this.clearError()
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

		isEmail(email) {
			if (!email) {
				return false
			}

			// Email addresses need at least one "@".
			const atpos = email.indexOf('@')
			if (atpos <= 0) {
				return false
			}

			// And a dot after the "@".
			const dotpos = email.indexOf('.', atpos + 1)
			if (dotpos < 0 || dotpos === email.length - 1) {
				return false
			}

			return true
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
			if (!emails.length && this.isEmail(this.email)) {
				emails.push({
					name: this.email,
					value: {
						shareType: OC.Share.SHARE_TYPE_EMAIL,
						shareWith: this.email,
					},
				})
				this.emailResults.emails = emails
			}
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

		addRecipient(recipient) {
			const prev = this.recipients.find((elem) => {
				return elem.type === recipient.type && elem.value === recipient.value
			})
			if (prev) {
				return
			}

			this.recipients.push(recipient)
			this.clearError()
		},

		deleteRecipient(recipient) {
			this.recipients = this.recipients.filter((elem) => {
				return elem.type !== recipient.type || elem.value !== recipient.value
			})
			this.clearError()
		},

		addUser(item) {
			this.addRecipient({
				type: 'user',
				value: item.value.shareWith,
				display_name: item.label,
				item,
			})
			this.user = ''
			this.userResults = {}
			this.noUserResults = false
		},

		addEmail(item) {
			if (item.value && item.value.shareType === OC.Share.SHARE_TYPE_USER) {
				this.recipient_type = 'user'
				this.addUser(item)
				return
			}

			const shareWith = item.value?.shareWith || ''
			this.addRecipient({
				type: 'email',
				value: shareWith,
				display_name: item.label,
				item,
			})
			this.email = ''
			this.emailResults = {}
			this.noEmailResults = false
		},

		clearError() {
			this.error = ''
		},

		renderError(error) {
			this.error = error
			this.$refs.content.parentNode.scrollTo(0, 0)
		},

		async requestSignature() {
			if (!this.fileModel) {
				showError(t('esig', 'No file selected.'))
				return
			}

			if (!this.recipients.length) {
				this.renderError(t('esig', 'Please add at least one recipient first.'))
				return
			}

			const recipients = this.recipients.map((elem) => {
				return {
					type: elem.type,
					value: elem.value,
				}
			})

			this.clearError()
			this.shareLoading = true
			try {
				let metadata
				if (this.signaturePositions && this.signaturePositions.length) {
					let signaturePositions = this.signaturePositions
					if (recipients.length === 1) {
						signaturePositions = signaturePositions.map((e) => {
							delete e.recipient_idx
							return e
						})
					} else {
						let missingIndexes = false
						signaturePositions.forEach((e) => {
							if (!Object.prototype.hasOwnProperty.call(e, 'recipient_idx') || e.recipient_idx === -1) {
								missingIndexes = true
							}
						})
						if (missingIndexes) {
							this.renderError(t('esig', 'At least one field has no recipient assigned.'))
							return
						}
					}
					metadata = {
						version: '1.0',
						signature_fields: signaturePositions,
					}
				}
				const options = {
					signed_save_mode: this.signed_save_mode,
				}
				await shareFile(this.fileModel.id, recipients, options, metadata)
				this.closeModal()
				showSuccess(t('esig', 'Requested signature.'))
			} catch (error) {
				this.shareLoading = false
				console.error('Could not request signature', this.fileModel, error)
				const response = error.response
				const data = response.data.ocs?.data || {}
				let errorMessage = ''
				switch (data.error) {
				case 'unknown_user':
					errorMessage = t('esig', 'Unknown user.')
					break
				case 'invalid_email':
					errorMessage = t('esig', 'Invalid email address.')
					break
				case 'error_connecting':
					errorMessage = t('esig', 'Error connecting to esig service.')
					break
				default:
					errorMessage = t('esig', 'Error while requesting signature.')
				}
				this.renderError(errorMessage)
			} finally {
				this.shareLoading = false
			}
		},

		openSelectModal() {
			this.selectModalLoading = true
			getVinegarApi()
				.then(() => {
					this.showSelectModal = true
				})
				.catch((error) => {
					const msg = error.message || error
					switch (msg) {
					case 'client_unsupported':
						showError(t('esig', 'The server requires a newer version of the app. Please contact your administrator.'))
						break
					case 'server_unsupported':
						showError(t('esig', 'This app requires a newer version of the server. Please contact your administrator.'))
						break
					default:
						console.error('Error loading esig API', error)
						showError(t('esig', 'Error loading serverside API, please try again later.'))
					}
				})
				.finally(() => {
					this.selectModalLoading = false
				})
		},

		closeSelectModal(positions) {
			this.showSelectModal = false
			this.signaturePositions = positions
		},

		getFileUrl(model) {
			return generateRemoteUrl('webdav') + model.getFullPath()
		},

		recipientName(recipient) {
			switch (recipient.type) {
			case 'user':
				// fallthrough
			case 'email':
				return recipient.item.name || recipient.item.label || ''
			default:
				return ''
			}
		},

		recipientTitle(recipient) {
			return recipient.value
		},

		recipientIcon(recipient) {
			switch (recipient.type) {
			case 'user':
				return 'icon-user'
			case 'email':
				return 'icon-mail'
			default:
				return ''
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

.recipient__icon {
	width: 44px;
	height: 44px;
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
