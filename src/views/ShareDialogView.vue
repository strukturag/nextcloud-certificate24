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
	<div>
		<NcModal v-if="showDialog"
			:aria-label="t('certificate24', 'Request signature')"
			:name="t('certificate24', 'Request signature')"
			@close="closeModal">
			<div ref="content" class="modal__content">
				<h1>{{ t('certificate24', 'Request signature') }}</h1>
				<div v-if="error" class="error">
					{{ error }}
				</div>
				<div v-if="recipients.length > 0"
					class="recipients_section">
					<h2>{{ t('certificate24', 'Recipients') }}</h2>
					<ul>
						<NcListItemIcon v-for="recipient in recipients"
							:key="recipient.type + '-' + recipient.value"
							:user="recipient.type === 'user' ? recipient.value : undefined"
							:disable-menu="true"
							:show-user-status="false"
							:show-user-status-compact="false"
							:name="recipientName(recipient)"
							:subtitle="recipientTitle(recipient)">
							<NcActions>
								<NcActionButton @click="deleteRecipient(recipient)">
									<template #icon>
										<Delete :size="20" />
									</template>
									{{ t('certificate24', 'Delete recipient') }}
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
						{{ t('certificate24', 'Add user') }}
					</NcCheckboxRadioSwitch>
					<div v-if="userSelected" class="search">
						<NcTextField ref="userField"
							v-observe-visibility="userVisibilityChanged"
							:disabled="shareLoading"
							:value.sync="user"
							type="text"
							:placeholder="t('certificate24', 'Search users')"
							trailing-button-icon="close"
							:trailing-button-label="cancelSearchLabel"
							:show-trailing-button="isSearchingUser"
							@trailing-button-click="abortUserSearch"
							@input="handleUserInput">
							<Magnify :size="16" />
						</NcTextField>
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
						{{ t('certificate24', 'Add email address') }}
					</NcCheckboxRadioSwitch>
					<div v-if="!userSelected" class="search">
						<NcTextField ref="emailField"
							v-observe-visibility="emailVisibilityChanged"
							:disabled="shareLoading"
							:value.sync="email"
							type="text"
							:placeholder="t('certificate24', 'E-mail address')"
							trailing-button-icon="close"
							:trailing-button-label="cancelSearchLabel"
							:show-trailing-button="isSearchingEmail"
							@trailing-button-click="abortEmailSearch"
							@input="handleEmailInput">
							<Magnify :size="16" />
						</NcTextField>
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
						{{ t('certificate24', 'Action to perform when the file was signed successfully:') }}
						<select id="signed_save_mode" v-model="signed_save_mode">
							<option value="new">
								{{ t('certificate24', 'Create new signed file next to original file') }}
							</option>
							<option value="replace">
								{{ t('certificate24', 'Replace original file with signed file') }}
							</option>
							<option value="none">
								{{ t('certificate24', 'Don\'t save signed file automatically') }}
							</option>
						</select>
					</label>
				</div>
				<div v-if="signaturePositions && signaturePositions.length">
					{{ n('certificate24', '%n signature field positioned', '%n signature fields positioned', signaturePositions.length) }}
				</div>
				<div class="buttons">
					<NcButton v-if="canDownload(fileModel)"
						type="secondary"
						:disabled="selectModalLoading"
						:title="t('certificate24', 'Select signature position')"
						@click="openSelectModal">
						<template #icon>
							<NcLoadingIcon v-show="selectModalLoading" :size="24" />
							<FileSign v-show="!selectModalLoading" />
						</template>
						{{ t('certificate24', 'Select signature position') }}
					</NcButton>
					<NcButton type="primary"
						:disabled="shareLoading || !recipients.length"
						:title="t('certificate24', 'Request signature')"
						@click="requestSignature">
						<template #icon>
							<NcLoadingIcon v-show="shareLoading" :size="24" />
							<FileSign v-show="!shareLoading" />
						</template>
						{{ t('certificate24', 'Request signature') }}
					</NcButton>
				</div>
			</div>
			<SelectorDialogModal v-if="showSelectModal"
				:url="fileModel.encodedSource"
				:signature-positions="signaturePositions"
				:recipients="recipients"
				@close="closeSelectModal" />
		</NcModal>
	</div>
</template>

<script>
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

import { shareFile, search, getMetadata } from '../services/apiservice.js'
import getVinegarApi from '../services/vinegarapi.js'
import SelectorDialogModal from '../components/SelectorDialogModal.vue'
import SearchResults from '../components/SearchResults.vue'

export default {
	name: 'ShareDialogView',

	components: {
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
		fileid() {
			return this.fileModel ? this.fileModel.fileid : null
		},
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
			return t('certificate24', 'Cancel search')
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
		this.settings = loadState('certificate24', 'public-settings')
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
				const metadata = await getMetadata(this.fileid)
				if (metadata && metadata.signature_fields) {
					this.signaturePositions = metadata.signature_fields
				}
			}
		})
	},

	methods: {
		canDownload(fileModel) {
			if (!fileModel) {
				return false
			}

			const saStr = fileModel.attributes['share-attributes']
			if (!saStr) {
				return true
			}

			let sa
			try {
				sa = JSON.parse(saStr)
			} catch (e) {
				console.error('Could not parse shared attributes', fileModel)
				return true
			}

			for (const i in sa) {
				const attr = this.shareAttributes[i]
				if (attr.scope === 'permissions' && attr.key === 'download') {
					return attr.enabled
				}
			}

			return true
		},
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
				showError(t('certificate24', 'An error occurred while performing the search'))
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
				showError(t('certificate24', 'No file selected.'))
				return
			}

			if (!this.recipients.length) {
				this.renderError(t('certificate24', 'Please add at least one recipient first.'))
				return
			}

			const recipients = this.recipients.map((elem) => {
				const result = {
					type: elem.type,
					value: elem.value,
				}
				if (elem.type === 'email' && elem.item.name && elem.item.name !== elem.value) {
					result.display_name = elem.item.name
				}
				return result
			})

			this.clearError()
			this.shareLoading = true
			try {
				let signaturePositions = this.signaturePositions
				if (!signaturePositions || !signaturePositions.length) {
					this.renderError(t('certificate24', 'Please create signature fields first.'))
					return
				}

				if (recipients.length === 1) {
					signaturePositions = signaturePositions.map((e) => {
						delete e.recipient_idx
						return e
					})
				} else {
					let missingIndexes = false
					const required = []
					recipients.forEach((e, idx) => {
						required.push(idx)
					})
					signaturePositions.forEach((e) => {
						if (!Object.prototype.hasOwnProperty.call(e, 'recipient_idx')
							|| e.recipient_idx < 0
							|| e.recipient_idx >= recipients.length) {
							missingIndexes = true
							return
						}

						const pos = required.indexOf(e.recipient_idx)
						if (pos >= 0) {
							required.splice(pos, 1)
						}
					})
					if (missingIndexes) {
						this.renderError(t('certificate24', 'At least one field has no recipient assigned.'))
						return
					} else if (required.length) {
						this.renderError(t('certificate24', 'At least one recipient has no field assigned.'))
						return
					}
				}
				const metadata = {
					version: '1.0',
					signature_fields: signaturePositions,
				}
				const options = {
					signed_save_mode: this.signed_save_mode,
				}
				await shareFile(this.fileid, recipients, options, metadata)
				this.closeModal()
				showSuccess(t('certificate24', 'Requested signature.'))
			} catch (error) {
				this.shareLoading = false
				console.error('Could not request signature', this.fileModel, error)
				const response = error.response
				const data = response.data.ocs?.data || {}
				let errorMessage = ''
				switch (data.error) {
				case 'unknown_user':
					errorMessage = t('certificate24', 'Unknown user.')
					break
				case 'invalid_email':
					errorMessage = t('certificate24', 'Invalid email address.')
					break
				case 'error_connecting':
					errorMessage = t('certificate24', 'Error connecting to Certificate24 service.')
					break
				default:
					errorMessage = t('certificate24', 'Error while requesting signature.')
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
						showError(t('certificate24', 'The server requires a newer version of the app. Please contact your administrator.'))
						break
					case 'server_unsupported':
						showError(t('certificate24', 'This app requires a newer version of the server. Please contact your administrator.'))
						break
					default:
						console.error('Error loading Certificate24 API', error)
						showError(t('certificate24', 'Error loading serverside API, please try again later.'))
					}
				})
				.finally(() => {
					this.selectModalLoading = false
				})
		},

		closeSelectModal(positions) {
			this.showSelectModal = false
			this.signaturePositions = positions
			if (positions && positions.length) {
				this.clearError()
			}
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
}

.buttons {
	display: flex;
	justify-content: space-between;
}
</style>
