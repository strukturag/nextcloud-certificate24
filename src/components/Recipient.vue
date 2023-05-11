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
	<div v-if="recipient.type === 'user'">
		<NcAvatar :user="recipient.value"
			:display-name="recipient.display_name || recipient.value"
			:disable-menu="true"
			:show-user-status="false"
			:show-user-status-compact="false" />
	</div>
	<div v-else-if="recipient.type === 'email'">
		<a :href="'mailto:' + recipient.value"
			:title="recipient.value">
			{{ recipient.display_name || recipient.value }}
		</a>
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
