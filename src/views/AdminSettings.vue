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
