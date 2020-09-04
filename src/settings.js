import { generateFilePath } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import Vue from 'vue'

import AdminSettings from './components/settings/AdminSettings'
import Nextcloud from './mixins/Nextcloud'

// eslint-disable-next-line camelcase, no-undef
__webpack_nonce__ = btoa(getRequestToken())
// eslint-disable-next-line camelcase, no-undef
__webpack_public_path__ = generateFilePath('mailman', '', 'js/')

Vue.mixin(Nextcloud)

const View = Vue.extend(AdminSettings)
new View({
	propsData: {
		settings: loadState('mailman', 'settings') || {},
	},
}).$mount('#mailman-admin-settings-root')
