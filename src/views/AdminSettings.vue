<template>
	<div>
		<AccountSettings />
		<InstanceSettings />
		<SignatureProperties />
	</div>
</template>

<script>
import AccountSettings from '../components/AdminSettings/AccountSettings.vue'
import InstanceSettings from '../components/AdminSettings/InstanceSettings.vue'
import SignatureProperties from '../components/AdminSettings/SignatureProperties.vue'

export default {
	name: 'AdminSettings',

	components: {
		AccountSettings,
		InstanceSettings,
		SignatureProperties,
	},
}
</script>
