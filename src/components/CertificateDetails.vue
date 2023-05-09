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
	<div :class="'level-' + level">
		<span :title="cert.subject" class="subject">
			Subject: {{ certName }}
		</span>
		<a :href="downloadUrl"
			:download="filename"
			target="_blank">
			{{ t('esig', 'Download certificate') }}
		</a>
	</div>
</template>

<script>
import { X509Certificate } from '@peculiar/x509'

export default {
	name: 'CertificateDetails',

	components: {
	},

	props: {
		certificate: {
			type: String,
			required: true,
		},
		level: {
			type: Number,
			required: false,
			default: 0,
		},
	},

	data() {
		return {
		}
	},

	computed: {
		cert() {
			return new X509Certificate(this.certificate)
		},
		certName() {
			const sn = this.cert.subjectName
			if (!sn) {
				return this.cert.subject
			}

			const fields = sn.getField('CN')
			if (!fields || !fields.length) {
				return this.cert.subject
			}

			return fields[0]
		},
		downloadUrl() {
			return 'data:application/x-pem-file;base64,' + window.btoa(this.certificate)
		},
		filename() {
			const sn = this.cert.subjectName
			if (!sn) {
				return 'certificate.pem'
			}

			const fields = sn.getField('CN')
			if (!fields || !fields.length) {
				return 'certificate.pem'
			}

			return 'certificate-' + fields[0].replace(' ', '_') + '.pem'
		},
	},

	watch: {
	},

	methods: {
	},
}
</script>

<style lang="scss" scoped>
a {
	color: var(--color-primary);
	display: block;
}

a:hover {
	color: var(--color-primary-hover);
}

/* stylelint-disable-next-line at-rule-no-unknown */
@for $i from 1 through 5 {
	.level-#{$i} {
		margin-left: #{$i}em;

		&:before {
			content: '└';
			margin-left: -1em;
		}
	}
}
</style>
