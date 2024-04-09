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
	<NcDateTime v-if="timestamp"
		:timestamp="timestamp"
		ignore-seconds="true" />
</template>

<script>
import { NcDateTime } from '@nextcloud/vue'
import moment from '@nextcloud/moment'

export default {
	name: 'FormattedDate',

	components: {
		NcDateTime,
	},

	props: {
		date: {
			type: String,
			required: false,
			default: '',
		},
	},

	computed: {
		timestamp() {
			if (!this.date) {
				return 0
			}

			const m = moment(this.date)
			return m.valueOf()
		},
	},
}
</script>
