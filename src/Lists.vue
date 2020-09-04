<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<Content :app-name="appid">
		<AppNavigation>
			<template #list>
				<AppNavigationForm v-for="list in lists"
					:key="list.id"
					:list="list"
					@mobile-close-navigation="mobileCloseNavigation" />
			</template>
		</AppNavigation>

		<!-- No lists & loading emptycontents -->
		<AppContent v-if="loading || noLists">
			<EmptyContent v-if="loading" icon="icon-loading">
				{{ t(appid, 'Loading forms …') }}
			</EmptyContent>
			<EmptyContent v-else-if="noLists">
				{{ t(appid, 'You don\'t have access to any mailing lists') }}
			</EmptyContent>

			<EmptyContent v-else>
				{{ t(appid, 'Subscribe or unsubscribe to mailing lists') }}
			</EmptyContent>
		</AppContent>
	</Content>
</template>

<script>
import { emit } from '@nextcloud/event-bus'
import { showError } from '@nextcloud/dialogs'
import { generateOcsUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
// import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'
import Content from '@nextcloud/vue/dist/Components/Content'
import isMobile from '@nextcloud/vue/src/mixins/isMobile'

import AppNavigationForm from './components/AppNavigationForm'
import EmptyContent from './components/EmptyContent'

export default {
	name: 'Lists',

	components: {
		AppNavigationForm,
		AppContent,
		AppNavigation,
		// AppNavigationNew,
		Content,
		EmptyContent,
	},

	mixins: [isMobile],

	props: {
		appid: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			loading: true,
			lists: [],
		}
	},

	computed: {
		noLists() {
			return this.lists && this.lists.length === 0
		},

		selectedList: {
			get() {
				return this.lists.find(list => list.hash === this.routeHash)
			},
			set(list) {
				const index = this.lists.findIndex(search => search.hash === this.routeHash)
				if (index > -1) {
					this.$set(this.lists, index, list)
				}
			},
		},
	},

	beforeMount() {
		this.loadLists()
	},

	methods: {
		/**
		 * Closes the App-Navigation on mobile-devices
		 */
		mobileCloseNavigation() {
			if (this.isMobile) {
				emit('toggle-navigation', { open: false })
			}
		},

		/**
		 * Initial forms load
		 */
		async loadLists() {
			this.loading = true
			try {
				const response = await axios.get(generateOcsUrl('apps/forms/api/v1', 2) + 'forms')
				this.forms = response.data
			} catch (error) {
				showError(t('forms', 'An error occurred while loading the forms list'))
				console.error(error)
			} finally {
				this.loading = false
			}
		},

	},
}
</script>
