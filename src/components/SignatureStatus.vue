<!--
  - @copyright Copyright (c) 2023, struktur AG.
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
	<span>
		{{
			t('certificate24', 'Signature status: {status}', {
				'status': signatureStatus
			})
		}}
	</span>
</template>

<script>
import { t } from '@nextcloud/l10n'

export default {
	name: 'SignatureStatus',

	props: {
		status: {
			type: String,
			required: true,
		},
	},

	computed: {
		signatureStatus() {
			switch (this.status) {
			case 'valid':
				return t('certificate24', 'Valid')
			case 'format_error':
				return t('certificate24', 'The format is not valid')
			case 'error_validating':
				return t('certificate24', 'Error while validating')
			case 'certificate_not_valid':
				return t('certificate24', 'The certificate is not valid yet')
			case 'certificate_expired':
				return t('certificate24', 'The certificate is expired')
			case 'no_signature_date':
				return t('certificate24', 'No signature date found')
			case 'signature_date_invalid':
				return t('certificate24', 'The signature date is invalid')
			case 'unknown_ca':
				return t('certificate24', 'Signed by unknown CA')
			case 'invalid_signature':
				return t('certificate24', 'The signature is invalid')
			default:
				return this.status
			}
		},
	},

	methods: {
		t,
	},
}
</script>
