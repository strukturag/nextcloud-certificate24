<template>
	<div v-if="recipient.type === 'user'">
		<NcAvatar :user="recipient.value"
			:display-name="recipient.display_name || recipient.value"
			:disable-menu="true"
			:show-user-status="false"
			:show-user-status-compact="false" />
	</div>
	<div v-else-if="recipient.type === 'email'">
		<a :href="'mailto:' + recipient.value">{{ recipient.value }}</a>
	</div>
	<div v-else>
		Unknown {{ recipient }}
	</div>
</template>

<script>
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'

export default {
	name: 'Recipient',

	components: {
		NcAvatar,
	},

	props: {
		recipient: {
			type: Object,
			required: true,
		},
	},
}
</script>
