<template>
	<li class="row"
		@click="handleClick">
		<NcAvatar v-if="item.value.shareType === 0"
			:user="item.value.shareWith"
			:display-name="itemName(item)"
			:size="44"
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
				<NcHighlight :text="item.shareWithDisplayNameUnique"
					:search="searchText" />
			</div>
		</div>
		<div class="row__icon icon"
			:class="itemIcon(item)" />
	</li>
</template>

<script>
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcHighlight from '@nextcloud/vue/dist/Components/NcHighlight.js'

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

	computed: {
	},

	methods: {
		itemName(item) {
			return item.name || item.label
		},

		itemIcon(item) {
			return item.icon || 'icon-user'
		},

		handleClick() {
			this.$emit('click-item', this.item)
		},
	}
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

	div::v-deep img {
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
