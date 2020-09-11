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
			<!-- <AppNavigationCaption :title="t(appid, 'Mailing lists')" /> -->
			<template #list>
				<AppNavigationCaption :title="t(appid, 'My lists')" />
				<AppNavigationList v-for="list in myLists"
					:key="list.id"
					:appid="appid"
					:list="list"
					@mobile-close-navigation="mobileCloseNavigation" />
				<AppNavigationSpacer />
				<AppNavigationCaption :title="t(appid, 'Other lists')" />
				<AppNavigationList v-for="list in otherLists"
					:key="list.id"
					:appid="appid"
					:list="list"
					@mobile-close-navigation="mobileCloseNavigation" />
			</template>
		</AppNavigation>

		<!-- No lists & loading emptycontents -->
		<AppContent v-if="loading || noLists || !routeID">
			<EmptyContent v-if="loading" icon="icon-loading">
				{{ t(appid, 'Loading lists …') }}
			</EmptyContent>
			<EmptyContent v-else-if="noLists" icon="icon-toggle-filelist">
				{{ t(appid, 'You don\'t have access to any mailing lists') }}
			</EmptyContent>
			<EmptyContent v-else icon="icon-toggle-filelist">
				{{ t(appid, 'Subscribe or unsubscribe to mailing lists') }}
				<template #desc>
					{{ t(appid, '(Using the button next to the name of the list in the sidebar)') }}
				</template>
			</EmptyContent>
		</AppContent>

		<template v-else>
			<router-view :list.sync="selectedList" />
			<!--<router-view v-if="!selectedForm.partial"
				:form="selectedForm"
				name="sidebar" />-->
		</template>
	</Content>
</template>

<script>
import { emit } from '@nextcloud/event-bus'
import { showError } from '@nextcloud/dialogs'
// import { generateUrl } from '@nextcloud/router'
// import axios from '@nextcloud/axios'

import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationSpacer from '@nextcloud/vue/dist/Components/AppNavigationSpacer'
import AppNavigationCaption from '@nextcloud/vue/dist/Components/AppNavigationCaption'
// import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'
import Content from '@nextcloud/vue/dist/Components/Content'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import isMobile from '@nextcloud/vue/src/mixins/isMobile'

import AppNavigationList from './components/AppNavigationList'
// import EmptyContent from './components/EmptyContent'

import logger from './logger'
import { getLists } from './service/UserService'

export default {
	name: 'Lists',

	components: {
		AppNavigationList,
		AppContent,
		AppNavigation,
		AppNavigationCaption,
		AppNavigationSpacer,
		// AppNavigationNew,
		Content,
		EmptyContent,
	},

	mixins: [isMobile],

	data() {
		return {
			appid: 'mailman',
			loading: true,
			lists: [],
		}
	},

	computed: {
		noLists() {
			return this.lists && this.lists.length === 0
		},

		routeID() {
			return this.$route.params.id
		},

		myLists() {
			if (!this.lists) { return [] }
			return this.lists.filter(l => l.included)
		},

		otherLists() {
			if (!this.lists) { return [] }
			return this.lists.filter(l => !l.included)
		},

		selectedList: {
			get() {
				return this.lists.find(list => list.id === this.routeID)
			},
			set(list) {
				const index = this.lists.findIndex(search => search.id === this.routeID)
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
				const listdata = await getLists()
				/* const url = generateUrl('apps/' + this.appid + '/api/lists')
				const response = await axios.get(url) */
				logger.info('Lists data', listdata)
				this.lists = listdata
			} catch (error) {
				showError(t(this.appid,
					'An error occurred while loading the lists: ' + error
				))
				console.error(error)
			} finally {
				this.loading = false
			}
		},

	},
}
</script>
