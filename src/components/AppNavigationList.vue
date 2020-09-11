<template>
	<AppNavigationItem
		ref="navigationItem"
		:class="list.included ? 'included' : 'notincluded'"
		:icon="icon"
		:title="listTitle"
		:to="{ name: 'listRoot', params: { id: list.id } }"
		@click="mobileCloseNavigation">
		<template v-if="!loading && list.included" #actions>
			<ActionButton v-if="list.subscribed"
				:close-after-click="true"
				icon="icon-delete"
				@click="onSubscription(false)">
				{{ t(appid, 'Unsubscribe') }}
			</ActionButton>
			<ActionButton v-else
				:close-after-click="true"
				:disabled="!list.included"
				icon="icon-add"
				@click="onSubscription(true)">
				{{ t(appid, 'Subscribe') }}
			</ActionButton>
			<!--
			<ActionSeparator />
			<ActionButton
				:close-after-click="true"
				icon="icon-mail"
				:title="t(appid, 'Send email')"
				@click="onMailTo" />
			-->
		</template>
	</AppNavigationItem>
</template>

<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
// import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
// import ActionRouter from '@nextcloud/vue/dist/Components/ActionRouter'
// import ActionSeparator from '@nextcloud/vue/dist/Components/ActionSeparator'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'

import { showError } from '@nextcloud/dialogs'

import { subscribe, unsubscribe } from '../service/UserService'
import logger from '../logger'

export default {
	name: 'AppNavigationList',

	components: {
		AppNavigationItem,
		ActionButton,
		// ActionSeparator,
	},

	props: {
		appid: {
			type: String,
			required: true,
		},
		list: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			loading: false,
		}
	},

	computed: {
		icon() {
			if (this.loading) {
				return 'icon-loading-small'
			}
			return 'icon-mail'
		},
		/**
		 * Return list title, or placeholder if not set
		 * @returns {string}
		 */
		listTitle() {
			if (this.list.id) {
				return this.list.id
			}
			return t(this.appid, '???')
		},

	},

	methods: {
		/**
		 * Closes the App-Navigation on mobile-devices
		 */
		mobileCloseNavigation() {
			this.$emit('mobile-close-navigation')
		},

		async onSubscription(sub) {
			this.loading = true
			try {
				if (sub) {
					await subscribe(this.list.id)
				} else {
					await unsubscribe(this.list.id)
				}
				this.list.subscribed = sub
				logger.info('Lists', this.list)
			} catch (error) {
				showError(t(this.appid, 'Server error: ' + error))
				logger.error(error)
			} finally {
				this.loading = false
			}
		},

		onMailTo() {
			// window.open('mailto:' + this.list.fqdn) // , '_blank')
			location.href = 'mailto:' + this.list.fqdn
		},

	},
}
</script>

<style lang="scss" scoped>
.notincluded {
	color: var(--color-text-lighter);
}
</style>
