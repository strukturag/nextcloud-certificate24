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
	<div class="search-results"
		:class="{'scrollable': scrollable }">
		<template v-if="addableUsers.length !== 0">
			<ul>
				<ResultItem v-for="item in addableUsers"
					:key="generateKey(item)"
					:item="item"
					:search-text="searchText"
					@click-item="handleClickItem" />
			</ul>
		</template>

		<template v-if="addableEmails.length !== 0">
			<ul>
				<ResultItem v-for="item in addableEmails"
					:key="generateKey(item)"
					:item="item"
					:search-text="searchText"
					@click-item="handleClickItem" />
			</ul>
		</template>

		<Hint v-if="entriesLoading" :hint="t('esig', 'Searching …')" />
		<Hint v-if="noResults && !entriesLoading && sourcesWithoutResults" :hint="t('esig', 'No search results')" />
	</div>
</template>

<script>
import Hint from './Hint.vue'
import ResultItem from './ResultItem.vue'

export default {
	name: 'SearchResults',

	components: {
		Hint,
		ResultItem,
	},

	props: {
		searchText: {
			type: String,
			required: true,
		},
		searchResults: {
			type: Object,
			required: true,
		},
		entriesLoading: {
			type: Boolean,
			required: true,
		},
		/**
		 * Display no-results state instead of list.
		 */
		noResults: {
			type: Boolean,
			default: false,
		},
		scrollable: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		sourcesWithoutResults() {
			return !this.addableUsers.length
				&& !this.addableEmails.length
		},

		addableUsers() {
			const exactUsers = this.searchResults.exact?.users || []
			const users = this.searchResults.users || []
			return exactUsers.concat(users)
		},

		addableEmails() {
			const emails = this.searchResults.emails || []
			return emails
		},
	},

	methods: {
		async handleClickItem(item) {
			this.$emit('click', item)
		},

		generateKey(item) {
			return item.shareWithDisplayNameUnique || ''
		},
	},
}
</script>

<style lang="scss" scoped>
.scrollable {
	overflow-y: auto;
	overflow-x: hidden;
	flex-shrink: 1;
}
</style>
