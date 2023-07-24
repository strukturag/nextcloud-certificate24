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
		<div v-if="properties.reason" class="row">
			{{ t('certificate24', 'Reason: {reason}', {
				'reason': properties.reason
			}) }}
		</div>
		<div v-if="properties.location" class="row">
			{{ t('certificate24', 'Location:') }}
			<!-- eslint-disable-next-line vue/no-v-html -->
			<span v-html="location" />
		</div>
		<div v-if="properties.contact" class="row">
			{{ t('certificate24', 'Contact:') }}
			<!-- eslint-disable-next-line vue/no-v-html -->
			<span v-html="contact" />
		</div>
		<div v-if="nextcloudUrl" class="row">
			{{ t('certificate24', 'Nextcloud URL:') }}
			<!-- eslint-disable-next-line vue/no-v-html -->
			<span v-html="nextcloudUrl" />
		</div>
	</div>
</template>

<script>
export default {
	name: 'SignatureProperties',

	props: {
		properties: {
			type: Object,
			default: Object,
		},
	},

	computed: {
		location() {
			return this.formatLink(this.properties.location)
		},
		contact() {
			return this.formatLink(this.properties.contact)
		},
		nextcloudUrl() {
			return this.formatLink(this.properties.metadata?.nextcloud || null)
		},
	},

	methods: {
		formatLink(s) {
			if (!s) {
				return s
			}

			if (s.indexOf('http://') === 0
				|| s.indexOf('https://') === 0) {
				const elem = document.createElement('a')
				elem.setAttribute('href', s)
				elem.setAttribute('title', s)
				elem.setAttribute('target', '_blank')
				elem.innerText = s
				return elem.outerHTML
			}
			const atPos = s.indexOf('@')
			if (atPos > 0) {
				const dotPos = s.indexOf('.', atPos + 1)
				if (dotPos > 0) {
					const elem = document.createElement('a')
					elem.setAttribute('href', 'mailto:' + s)
					elem.setAttribute('title', s)
					elem.innerText = s
					return elem.outerHTML
				}
			}
			return s
		},
	},
}
</script>

<style lang="scss" scoped>
.row:deep {
	display: flex;
	gap: 4px;
	width: 100%;

	span {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	a {
		color: var(--color-primary);
	}

	a:hover {
		color: var(--color-primary-hover);
	}
}
</style>
