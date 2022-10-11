<template>
	<li class="row"
		@click="handleClick">
		<NcAvatar v-if="item.value.shareType === OC.Share.SHARE_TYPE_USER"
			:user="item.value.shareWith"
			:display-name="itemName(item)"
			:size="44"
			:disable-menu="true"
			:show-user-status="false"
			:show-user-status-compact="false" />

		<NcAvatar v-if="item.value.shareType === OC.Share.SHARE_TYPE_EMAIL"
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
			if (item.icon) {
				return item.icon
			}

			const shareType = item.value?.shareType || null
			switch (shareType) {
			case OC.Share.SHARE_TYPE_USER:
				return 'icon-user'
			case OC.Share.SHARE_TYPE_EMAIL:
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
			case OC.Share.SHARE_TYPE_EMAIL:
				return shareWith
			}
			return ''
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
