<template>
	<div>
		<div class="settings-group">
			<div class="group-title">
				{{ t(appid, 'Connection') }}
			</div>
			<div v-if="status.active" class="server-msg server-active">
				{{ t(appid, 'established') }}
			</div>
			<div v-else class="server-msg server-error">
				{{ t(appid, 'error') }}
			</div>
		</div>
		<div v-if="status.active" class="settings-group">
			<div class="group-title">
				{{ t(appid, 'Versions') }}
			</div>
			<div class="server-info">
				Mailman: <i>{{ status.mailman_version }}</i><br>
				Python: <i>{{ status.python_version }}</i><br>
				API: <i>{{ status.api_version }}</i><br>
			</div>
		</div>
		<form @submit.prevent="submit">
			<div class="settings-group">
				<div class="group-title">
					{{ t(appid, 'REST') }}
				</div>
				<div class="group-inputs">
					<label for="mailman-settings-url"> {{ t(appid, 'URL') }} </label>
					<input
						id="mailman-settings-url"
						v-model="url"
						:disabled="loading"
						name="url"
						type="text">
					<label for="mailman-settings-cred"> {{ t(appid, 'Credentials') }} </label>
					<input
						id="mailman-settings-cred"
						v-model="cred"
						:disabled="loading"
						name="cred"
						type="text">
				</div>
			</div>
			<div class="settings-group">
				<div class="group-title">
					{{ t(appid, "Hyperkitty") }}
				</div>
				<div class="group-inputs">
					<label for="mailman-settings-kitty"> {{ t(appid, 'URL') }} </label>
					<input
						id="mailman-settings-kitty"
						v-model="kitty"
						:disabled="loading"
						name="kitty"
						type="text">
				</div>
			</div>
			<div class="settings-group">
				<div class="group-title">
					{{ t(appid, 'List attributes') }}
				</div>
				<div class="group-inputs">
					<label for="mailman-settings-domain"> {{ t(appid, 'Domain') }} </label>
					<input
						id="mailman-settings-domain"
						v-model="domain"
						:disabled="loading"
						name="domain"
						type="text">
				</div>
			</div>
			<div class="settings-group">
				<div class="group-title">
					{{ t(appid, 'Mail attributes') }}
				</div>
				<div class="group-inputs">
					<label for="mailman-settings-limit"> {{ t(appid, 'Size limit (kB)') }} </label>
					<input
						id="mailman-settings-limit"
						v-model="limit"
						:disabled="loading"
						name="limit"
						type="number">
				</div>
			</div>
			<div class="settings-group">
				<div class="group-title" />
				<div class="group-inputs">
					<input
						type="submit"
						class="primary"
						:disabled="loading || !modified"
						:value="t(appid, 'Apply')">
				</div>
			</div>
		</form>
	</div>
</template>

<script>
import logger from '../../logger'
import { saveServerSettings, getServerStatus, getListData } from '../../service/SettingsService'
import { showError } from '@nextcloud/dialogs'

export default {
	name: 'ServerSettings',
	props: {
		settings: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			appid: this.settings.appid,
			status: this.settings.status,
			url: this.settings.url,
			cred: this.settings.cred,
			kitty: this.settings.kitty,
			domain: this.settings.domain,
			limit: this.settings.limit,
			loading: false,
		}
	},
	computed: {
		modified() {
			return (
				this.url !== this.settings.url
				|| this.cred !== this.settings.cred
				|| this.kitty !== this.settings.kitty
				|| this.domain !== this.settings.domain
				|| this.limit !== this.settings.limit
			)
		},
	},
	// beforeMount() {
	// logger.debug('server settings loaded', { settings: this.settings })
	// },
	methods: {
		submit() {
			this.loading = true
			const newSettings = {
				url: this.url,
				cred: this.cred,
				kitty: this.kitty,
				domain: this.domain,
				limit: this.limit,
			}
			logger.info('submitting new server settings', { settings: newSettings })
			return saveServerSettings(newSettings)
				.then(() => {
					logger.info('server settings updated')
					Object.assign(this.settings, newSettings)
				})
				.catch((error) => {
					showError(t(this.appid, 'Error while saving settings: ' + error))
					logger.error('server settings not updated', { error })
				})
				.then(() => {
					logger.info('requesting server status')
					return getServerStatus()
				})
				.then((status) => {
					logger.info('server status response', { status })
					this.status = status
				})
				.catch((error) => {
					logger.error('server status error', { error })
					this.status = {
						active: false,
						error: error.message,
					}
				})
				.then(() => {
					Object.assign(this.settings.status, this.status)
					return getListData()
				})
				.then((data) => {
					logger.info('list data response', { data })
					Object.assign(this.settings, data)
				})
				.catch((error) => {
					showError(t(this.appid, 'Error obtaining list data: ' + error))
					logger.error('list data error', { error })
				})
				.then(() => {
					this.loading = false
				})
		},
	},
}
</script>

<style lang="scss" scoped>
.settings-group {
	display: flex;
	flex-direction: row;
	flex-wrap: nowrap;
	.group-title {
		min-width: 100px;
		text-align: right;
		margin: 10px;
		font-weight: bold;
	}
	.group-inputs {
		margin: 10px;
		flex-grow: 1;
		input[type='text'] {
			min-width: 200px;
		}
	}
}

.server-msg {
	text-transform: uppercase;
	margin: auto 0;
}

.server-active {
	color: var(--color-success);
}

.server-error {
	color: var(--color-error);
}

input[type='radio'] {
	display: none;
}

.flex-row {
	display: flex;
}

form {
	label {
		color: var(--color-text-maxcontrast);
	}
}
</style>
