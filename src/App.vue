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
	<NcContent :class="{ 'icon-loading': loading }"
		app-name="certificate24">
		<NcAppContent>
			<OwnSignRequests />
			<IncomingSignRequests />
		</NcAppContent>
	</NcContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'

import OwnSignRequests from './components/OwnSignRequests.vue'
import IncomingSignRequests from './components/IncomingSignRequests.vue'

export default {
	name: 'App',

	components: {
		NcAppContent,
		NcContent,
		OwnSignRequests,
		IncomingSignRequests,
	},

	data() {
		return {
			loading: false,
		}
	},
}
</script>
<style lang="scss">
table th, table td {
	padding: 4px 8px;
}
</style>
