<template>
	<NcContent :class="{ 'icon-loading': loading }"
		app-name="esig">
		<NcAppContent>
			<OwnSignRequests />
			<IncomingSignRequests />
		</NcAppContent>
	</NcContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'

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
