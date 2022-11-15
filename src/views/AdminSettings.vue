<template>
	<div>
		<AccountSettings />
		<SignatureProperties />
	</div>
</template>

<script>
import AccountSettings from '../components/AdminSettings/AccountSettings.vue'
import SignatureProperties from '../components/AdminSettings/SignatureProperties.vue'

export default {
	name: 'AdminSettings',

	components: {
		AccountSettings,
		SignatureProperties,
	},
}
</script>
