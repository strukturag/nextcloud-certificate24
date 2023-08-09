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
	<div :class="isEmptyContent ? 'emptycontent' : ''">
		<div v-if="signatures === undefined">
			<div class="icon icon-loading" />
		</div>

		<div v-else-if="signatures.status === 'not_signed' || signatures.code === 'error_encrypted_file'">
			<div class="icon icon-certificate24" />
			<h2>{{ t('certificate24', 'Signatures') }}</h2>
			<p v-if="signatures.status === 'not_signed'">
				{{ t('certificate24', 'The file is not signed.') }}
			</p>
			<p v-else-if="signatures.code === 'error_encrypted_file'">
				{{ t('certificate24', 'The file is encrypted and can not be checked.') }}
			</p>
		</div>

		<template v-else>
			<h2>{{ t('certificate24', 'Signatures') }}</h2>
			<p>
				{{ t('certificate24', 'Checked on: {date}', {
					'date': formatDate(signatures.verified)
				}) }}
			</p>
			<p>
				<SignatureStatus :status="signatures.validation" />
			</p>
			<div v-for="(signature, index) of signatures.signatures"
				:key="'sig-'+index"
				class="signature">
				<h3>
					{{ t('certificate24', 'Signature {index}', {
						index: index+1,
					}) }}
				</h3>
				<div>
					<div>
						{{ t('certificate24', 'Signed by: {signer}', {
							'signer': getSigner(signature)
						}) }}
					</div>
					<div>
						{{ t('certificate24', 'Signed on: {date}', {
							'date': formatDate(signature.signed)
						}) }}
					</div>
					<div v-if="!signature.signed_details">
						<em>
							{{ t('certificate24', 'The signature timestamp was generated on the signers machine and is untrusted.') }}
						</em>
					</div>
				</div>
				<div>
					<SignatureStatus :status="signature.validation" />
				</div>
				<div v-if="signature.whole_file">
					{{ t('certificate24', 'The file was not modified since it was signed.') }}
				</div>
				<div v-else>
					{{ t('certificate24', 'The file was modified since it was signed.') }}
				</div>

				<template v-if="hasProperties(signature)">
					<h3>
						{{ t('certificate24', 'Signature properties') }}
					</h3>
					<SignatureProperties :properties="signature.properties" />
				</template>

				<template v-if="signature.signed_details">
					<h3>
						{{ t('certificate24', 'Signature timestamp') }}
					</h3>
					<div>
						<SignatureStatus :status="signature.signed_details.validation" />
					</div>
					<div v-for="(cert, cindex) of signature.signed_details.certificates"
						:key="'time-cert-'+cindex"
						class="certificate">
						<CertificateDetails :certificate="cert" :level="cindex" />
					</div>
				</template>

				<h3>
					{{ t('certificate24', 'Certificate chain') }}
				</h3>
				<div v-for="(cert, cindex) of signature.certificates"
					:key="'cert-'+cindex"
					class="certificate">
					<CertificateDetails :certificate="cert" :level="cindex" />
				</div>
			</div>
		</template>

		<NcButton v-if="signatures !== undefined"
			@click="$emit('recheck')">
			{{ t('certificate24', 'Force recheck') }}
		</NcButton>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import CertificateDetails from '../components/CertificateDetails.vue'
import SignatureProperties from '../components/SignatureProperties.vue'
import SignatureStatus from '../components/SignatureStatus.vue'
import { getFileSignatures } from '../services/filesIntegrationServices.js'

import { formatDate } from '../services/formatter.js'

export default {
	name: 'SignaturesView',

	components: {
		CertificateDetails,
		SignatureProperties,
		SignatureStatus,
		NcButton,
	},

	props: {
		fileId: {
			type: Number,
			required: true,
		},
	},

	data() {
		return {
			signatures: undefined,
		}
	},

	computed: {
		isEmptyContent() {
			return this.signatures === 'undefined' || this.signatures?.status === 'not_signed'
		},
	},

	watch: {
		fileId(newId) {
			this.fetchSignatures(newId)
		},
	},

	mounted() {
		if (this.fileId) {
			this.fetchSignatures(this.fileId)
		}
	},

	methods: {
		async fetchSignatures(fileId) {
			this.signatures = undefined
			if (!fileId) {
				return
			}

			try {
				const response = await getFileSignatures({ fileId })

				this.signatures = response.data.ocs?.data || {}
			} catch (error) {
				switch (error.response?.status) {
				case 400:
					switch (error.response.data.ocs?.data?.code) {
					case 'error_encrypted_file':
						this.signatures = error.response.data.ocs?.data
						return
					}
					break
				case 404:
					switch (error.response.data.ocs?.data?.status) {
					case 'not_signed':
						this.signatures = error.response.data.ocs?.data
						return
					}
					break
				}
				console.error('Error loading signatures', error)
			}
		},

		formatDate(d) {
			return formatDate(d)
		},

		getSigner(signature) {
			return signature.properties?.name || t('certificate24', 'Unknown')
		},

		hasProperties(signature) {
			const keys = Object.keys(signature.properties || {})
			if (!keys || !keys.length) {
				return false
			}

			return keys.length > 1 || keys.indexOf('name') === -1
		},
	},

}
</script>

<style lang="scss" scoped>
h3 {
	text-decoration: underline;
}

.signature {
	margin-bottom: 1em;

	&:not(:first-of-type) {
		border-top: 1px solid grey;
	}
}

.certificate {
	&:not(:first-of-type) {
		margin-bottom: 0.5em;
	}
}

.emptycontent {
	/* Override default top margin set in server and center vertically
	 * instead. */
	margin-top: unset;

	height: 100%;

	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}
</style>
