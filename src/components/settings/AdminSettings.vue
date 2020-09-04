<template>
	<div id="mailman-admin-settings">
		<!--
		<div id="mailman-admin-settings-server" class="section">
			<h2>{{ t('mailman', 'Mailman3 server') }}</h2>
			<h3>{{ t('mailman', 'Server configuration can be found by calling "mailman info"') }}</h3>
		</div>
		-->
		<SettingsSection
			:title="t(settings.appid, 'Mailman3 server')"
			:description="t(settings.appid, 'Server configuration can be found by calling \'mailman info\'.')"
			doc-url="https://docs.mailman3.org">
			<ServerSettings :settings="settings" />
		</SettingsSection>
		<!--
		<div v-if="settings.status.active" id="mailman-admin-settings-lists" class="section">
			<h2>{{ t('mailman', 'Mailing lists') }}</h2>
			<h3>{{ t('mailman', 'Global mailing list settings. Users can manage their lists in the app itself (bottom-left corner).') }}</h3>
		</div>
		-->
		<SettingsSection
			:title="t(settings.appid, 'Mailing lists')"
			:description="t(settings.appid, 'Global mailing list settings. Users can manage their lists in the app itself (bottom-left corner).')">
			<ListSettings :settings="settings" />
		</SettingsSection>
	</div>
</template>

<script>
import logger from '../../logger'
import ServerSettings from './ServerSettings'
import ListSettings from './ListSettings'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'

export default {
	name: 'AdminSettings',
	components: {
		ServerSettings,
		ListSettings,
		SettingsSection,
	},
	props: {
		settings: {
			type: Object,
			required: true,
		},
	},
	beforeMount() {
		logger.debug('settings loaded', {
			settings: this.settings,
		})
	},

}
</script>
