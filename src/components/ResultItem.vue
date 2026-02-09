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
	<li class="row"
		@click="handleClick">
		<NcAvatar v-if="item.value.shareType === ShareType.User"
			:user="item.value.shareWith"
			:display-name="itemName(item)"
			:size="44"
			:disable-menu="true"
			:show-user-status="false"
			:show-user-status-compact="false" />

		<NcAvatar v-if="item.value.shareType === ShareType.Email"
			:display-name="itemName(item)"
			:size="44"
			:is-no-user="true"
			:disable-menu="true"
			:show-user-status="false"
			:show-user-status-compact="false" />

		<div class="row__user-wrapper">
			<div ref="userName"
				class="row__user-descriptor">
				<span class="row__user-name">
					<NcHighlight :text="itemName(item)"
						:search="searchText" />
				</span>
			</div>
			<div>
				<NcHighlight :text="itemEmail(item)"
					:search="searchText" />
			</div>
		</div>
		<div class="row__icon icon"
			:class="itemIcon(item)" />
	</li>
</template>

<script>
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcHighlight from '@nextcloud/vue/components/NcHighlight'
import { ShareType } from '@nextcloud/sharing'

export default {
	name: 'ResultItem',

	components: {
		NcAvatar,
		NcHighlight,
	},

	props: {
		item: {
			type: Object,
			required: true,
		},
		searchText: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			ShareType,
		}
	},

	computed: {
	},

	methods: {
		itemName(item) {
			return item.name || item.label || ''
		},

		itemIcon(item) {
			if (item.icon) {
				return item.icon
			}

			const shareType = item.value?.shareType || null
			switch (shareType) {
			case ShareType.User:
				return 'icon-user'
			case ShareType.Email:
				return 'icon-mail'
			}
			return ''
		},

		itemEmail(item) {
			if (item.shareWithDisplayNameUnique) {
				return item.shareWithDisplayNameUnique
			}

			const shareType = item.value?.shareType || null
			const shareWith = item.value?.shareWith || ''
			switch (shareType) {
			case ShareType.Email:
				return shareWith
			}
			return ''
		},

		handleClick() {
			this.$emit('click-item', this.item)
		},
	},
}
</script>

<style lang="scss" scoped>
.row {
	display: flex;
	align-items: center;
	cursor: pointer;
	margin: 4px 0;
	border-radius: var(--border-radius-pill);
	height: 56px;
	padding: 0 4px;

	span, div {
		cursor: pointer;
	}

	div:deep img {
		cursor: pointer;
	}

	&__user-wrapper {
		margin-top: -4px;
		margin-left: 12px;
		padding-right: 5px;
		width: calc(100% - 90px);
		display: flex;
		flex-direction: column;
	}

	&__user-name {
		vertical-align: middle;
		line-height: normal;
	}

	&__icon {
		width: 44px;
		height: 44px;
	}
}
</style>
