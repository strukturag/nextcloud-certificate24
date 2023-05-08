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
	<div>
		<div v-if="signatures === undefined" class="emptycontent">
			<div class="icon icon-loading" />
		</div>

		<div v-else-if="signatures.status === 'not_signed'" class="emptycontent">
			<div class="icon icon-esig" />
			<h2>{{ t('esig', 'Signatures') }}</h2>
			<p>{{ t('esig', 'The file is not signed.') }}</p>
		</div>

		<template v-else>
			<h2>{{ t('esig', 'Signatures') }}</h2>
			<p>
				{{ t('esig', 'Checked on: {date}', {
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
					{{ t('esig', 'Signature {index}', {
						index: index+1,
					}) }}
				</h3>
				<div>
					<div>
						{{ t('esig', 'Signed on: {date}', {
							'date': formatDate(signature.signed)
						}) }}
					</div>
					<div v-if="!signature.signed_details">
						<em>
							{{ t('esig', 'The signature timestamp was generated on the signers machine and is untrusted.') }}
						</em>
					</div>
				</div>
				<div>
					<SignatureStatus :status="signature.validation" />
				</div>
				<div v-if="signature.whole_file">
					{{ t('esig', 'The file was not modified since it was signed.') }}
				</div>
				<div v-else>
					{{ t('esig', 'The file was modified since it was signed.') }}
				</div>

				<template v-if="signature.signed_details">
					<h3>
						{{ t('esig', 'Signature timestamp') }}
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
					{{ t('esig', 'Certificate chain') }}
				</h3>
				<div v-for="(cert, cindex) of signature.certificates"
					:key="'cert-'+cindex"
					class="certificate">
					<CertificateDetails :certificate="cert" :level="cindex" />
				</div>
			</div>
		</template>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'

import CertificateDetails from '../components/CertificateDetails.vue'
import SignatureStatus from '../components/SignatureStatus.vue'
import { getFileSignatures } from '../services/filesIntegrationServices.js'

export default {
	name: 'SignaturesView',

	components: {
		CertificateDetails,
		SignatureStatus,
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
				console.error('Error loading signatures', error)
			}
		},

		formatDate(d) {
			return moment(d).format('LLL')
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
</style>
