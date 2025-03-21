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
	<NcDashboardWidget id="certificate24-panel"
		:items="requests"
		:loading="loading"
		:show-more-label="t('certificate24', 'More signature requests…')"
		:show-more-url="showMoreUrl">
		<template #default="{ item }">
			<NcDashboardWidgetItem :target-url="getItemTargetUrl(item)"
				:main-text="getMainText(item)"
				:sub-text="getSubText(item)"
				:avatar-username="item.user_id"
				:item="item" />
		</template>
		<template #empty-content>
			<NcEmptyContent :description="t('certificate24', 'No signature requests')">
				<template #icon>
					<span class="icon icon-certificate24" />
				</template>
			</NcEmptyContent>
		</template>
	</NcDashboardWidget>
</template>

<script>
import NcDashboardWidget from '@nextcloud/vue/components/NcDashboardWidget'
import NcDashboardWidgetItem from '@nextcloud/vue/components/NcDashboardWidgetItem'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import axios from '@nextcloud/axios'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { formatDate } from '../services/formatter.js'

const REQUEST_POLLING_INTERVAL = 30

export default {
	name: 'Dashboard',

	components: {
		NcDashboardWidget,
		NcDashboardWidgetItem,
		NcEmptyContent,
	},

	data() {
		return {
			requests: [],
			loading: true,
			windowVisibility: true,
			pollInverval: null,
			showMoreUrl: generateUrl('apps/certificate24#incoming_requests'),
		}
	},

	computed: {
		getItemTargetUrl() {
			return (request) => {
				return generateUrl(`apps/certificate24#incoming-${request.request_id}`)
			}
		},

		getMainText() {
			return (request) => {
				return request.filename
			}
		},

		getSubText() {
			return (request) => {
				return t('certificate24', 'Requested by {display_name} on {date}', {
					display_name: request.display_name,
					date: formatDate(request.created),
				})
			}
		},
	},

	watch: {
		windowVisibility(newValue) {
			if (newValue) {
				this.loadRequests()
			}
		},
	},

	beforeDestroy() {
		document.removeEventListener('visibilitychange', this.changeWindowVisibility)
		clearInterval(this.pollInverval)
	},

	beforeMount() {
		this.loadRequests()
		this.pollInverval = setInterval(this.loadRequests, REQUEST_POLLING_INTERVAL * 1000)
		document.addEventListener('visibilitychange', this.changeWindowVisibility)
	},

	methods: {
		loadRequests() {
			if (!this.windowVisibility) {
				// Dashboard is not visible, so don't update the list
				return
			}

			axios.get(generateOcsUrl('apps/certificate24/api/v1/share/incoming')).then((response) => {
				const requests = response.data.ocs.data
				this.requests = requests
				this.loading = false
			})
		},

		changeWindowVisibility() {
			this.windowVisibility = !document.hidden
		},
	},
}
</script>

<style lang="scss" scoped>
.empty-content {
	text-align: center;
	margin-top: 0 !important;

	.icon-certificate24 {
		width: 64px;
		height: 64px;
		background-size: 64px;
		background-image: url(../../img/app-dark.svg);
		filter: var(--background-invert-if-dark);
	}
}
</style>
