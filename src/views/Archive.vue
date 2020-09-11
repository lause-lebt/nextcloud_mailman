<template>
	<AppContent v-if="isLoadingList">
		<EmptyContent icon="icon-loading">
			{{ t('mailman', 'Loading {title} …', { title: list.id }) }}
		</EmptyContent>
	</AppContent>

	<AppContent v-else-if="archiveData">
		<header>
			<h2>{{ list.id }}</h2>
		</header>
		<section>
			<iframe :srcdoc="archiveData" style="width:100%; height:100%" />
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

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

import logger from '../logger'

export default {
	name: 'Archive',
	components: {
		AppContent,
		EmptyContent,
	},
	props: {
		list: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			isLoadingList: true,
			archiveData: null,
		}
	},
	watch: {
		// Fetch full form on change
		'list.id'() {
			logger.info('ID changed', this.list)
			this.fetchList(this.list.id)
		},
	},
	beforeMount() {
		logger.info('Before mount', this.list)
		this.fetchList(this.list.id)
	},
	methods: {
		async fetchList(listid) {
			this.isLoadingList = true
			try {
				const url = generateUrl('apps/mailman/api/archive/' + listid)
				logger.info('fetching ' + url)
				const response = await axios.get(url)
				logger.info('obtained response', response)
				if (response.data) {
					this.archiveData = response.data
					// this.$emit('update:archiveData', response.data)
					// this.$emit('update:list', response.data)
				} else {
					throw new Error('Illegal data')
				}
			} catch (error) {
				showError(t(this.appid, 'Server error: ' + error))
				logger.error(error)
			} finally {
				this.isLoadingList = false
			}
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
		}
	}
}
</style>
