<template>
	<AppContent v-if="isLoadingList">
		<EmptyContent icon="icon-loading">
			{{ t('mailman', 'Loading {title} …', { title: list.id }) }}
		</EmptyContent>
	</AppContent>

	<AppContent v-else-if="list">
		<header>
			<h2>
				<a :href="'mailto:'+list.fqdn">
					<div class="link-icon icon-mail" />
					<span>{{ list.fqdn }}</span>
				</a>
			</h2>
		</header>
		<section v-for="group in list.groups"
			:key="group.gid">
			<h3>{{ t('mailman', 'Members of') }} <b>{{ group.gid }}</b></h3>
			<UserBubble v-for="user in group.members"
				:key="user.uid"
				:display-name="user.display_name"
				size="30"
				:url="'mailto:'+user.email"
				:user="user.uid">
				<span class="email-hint">{{ user.email }}</span>
			</UserBubble>
		</section>
		<section v-if="hasExtra">
			<h3>{{ t('mailman', 'Additional Members') }}</h3>
			<UserBubble v-for="email in list.extra"
				:key="email"
				:display-name="email.split('@')[0]"
				size="30"
				:url="'mailto:'+email"
				avatar-image="icon-mail">
				<span class="email-hint">{{ email }}</span>
			</UserBubble>
		</section>
	</AppContent>

	<AppContent v-else>
		<EmptyContent icon="icon-error">
			{{ t('mailman', 'Error loading mailing list archive') }}
		</EmptyContent>
	</AppContent>
</template>

<script>
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import UserBubble from '@nextcloud/vue/dist/Components/UserBubble'

// import axios from '@nextcloud/axios'
// import { generateUrl } from '@nextcloud/router'
// import { showError } from '@nextcloud/dialogs'

// import logger from '../logger'

export default {
	name: 'ListInfo',
	components: {
		AppContent,
		EmptyContent,
		UserBubble,
	},
	props: {
		list: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			isLoadingList: false,
		}
	},
	computed: {
		hasExtra() {
			return (this.list && this.list.extra && this.list.extra.length > 0)
		},
	},
}
</script>

<style lang="scss">
.app-content {
	display: flex;
	align-items: center;
	flex-direction: column;

	header,
	section {
		width: 100%;
		max-width: 750px;
	}

	header {
		display: flex;
		flex-direction: column;
		margin-top: 44px;
		margin-bottom: 24px;

		h2 {
			margin-bottom: 0;

			a {
				&:hover {
					color: var(--color-primary)
				}
			}
		}
	}
}

.email-hint {
	padding: 3px;
}

.link-icon {
	align-items: center;
	display: inline-block;
	justify-content: center;
	vertical-align: middle;
	width: 44px;
	height: 44px;
}
</style>
