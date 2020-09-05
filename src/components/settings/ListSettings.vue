<template>
	<div>
		<table>
			<thead>
				<tr>
					<th>{{ t(appid, 'Name') }}</th>
					<th />
					<th>{{ t(appid, 'Groups') }}</th>
					<th>{{ t(appid, 'Also') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="list in lists" :key="list.key">
					<td>
						<span class="list-id">{{ list.id }}</span>
					</td>
					<td>
						<input
							:id="'delete--' + list.id"
							class="icon-close"
							:disabled="loading"
							type="button"
							@click="deleteList(list)">
					</td>
					<td>
						<Multiselect
							:id="'group-select--' + list.id"
							v-model="list.selectedGroups"
							:disabled="loading"
							class="group-input"
							:options="groups"
							:auto-limit="true"
							:tag-width="90"
							:multiple="true"
							:placeholder="t(appid, 'Select groups')"
							:user-select="true"
							track-by="gid"
							label="displayName"
							style="width: 300px"
							@input="onGroupChange" />
						<!--<pre>Selected option: {{ JSON.stringify(list.selectedGroups) }}</pre>-->
					</td>
					<td>
						<div :class="['input-group', extraMailsValid(list) ? 'has-success' : 'has-error']">
							<input
								:id="'extra-input--' + list.id"
								v-model="list.selectedExtra"
								class="extra-input form-control"
								:disabled="loading"
								:placeholder="t(appid, 'Additional email addresses')"
								type="text"
								@change="onExtraChange(list)">
						</div>
						<!--{{ JSON.stringify(list.extra) }}-->
					</td>
				</tr>
				<tr>
					<td>
						<input
							id="new-list-id"
							v-model="newListID"
							:disabled="loading"
							:placeholder="t(appid, 'List ID')"
							type="text">
					</td>
					<td colspan="2">
						<input
							type="submit"
							class="primary"
							:disabled="loading || !newListIDValid"
							:value="t(appid, 'Create')"
							@click="newList">
					</td>
				</tr>
			</tbody>
		</table>
		<Preview
			:valid="lists.every(l => extraMailsValid(l))"
			:appid="appid"
			:preview="preview" />
		<input
			type="submit"
			class="primary"
			:disabled="loading || !modified"
			:value="t(appid, 'Submit')"
			@click="submit">
		<input
			type="button"
			:disabled="loading || !modified"
			:value="t(appid, 'Cancel')"
			@click="cancel">
		<!--<pre>{{ JSON.stringify(preview) | pretty }}</pre>-->
	</div>
</template>

<script>
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import { showError } from '@nextcloud/dialogs'
import Preview from './Preview'
import logger from '../../logger'
import { getPreview, setListData } from '../../service/SettingsService'

export default {
	name: 'ListSettings',
	components: {
		Multiselect,
		Preview,
	},
	filters: {
		pretty: (value) => {
			return JSON.stringify(JSON.parse(value), null, 2)
		},
	},
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
			lists: this.settings.lists.map((v, i) => Object.assign({}, v, {
				key: i,
				selectedGroups: v.groups.map(name => {
					return {
						gid: name,
						displayName: name,
						icon: 'icon-group',
						isNoUser: true,
					}
				}),
				selectedExtra: v.extra.join(', '),
			})),
			newListID: null,
			// mmlists: this.settings.mmlists,
			preview: this.settings.preview,
			groups: this.settings.groups,
			loading: false,
			emailreg: /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,24}))$/,
		}
	},
	computed: {
		groupOptions() {
			return this.groups.map(item => {
				return {
					gid: item.gid,
					displayName: item.displayName,
					icon: 'icon-group', // 'icon-user',
					isNoUser: true,
				}
			})
		},
		modifiedLists() {
			return this.lists.map(v => {
				return {
					id: v.id,
					groups: v.selectedGroups
						.map(g => g.gid),
					extra: v.selectedExtra
						.split(',')
						.map(s => s.trim())
						.filter(s => s.length > 0),
					exclude: v.exclude,
				}
			})
		},
		modified() {
			return this.preview.delete.length > 0
				|| this.preview.create.length > 0
				|| this.preview.subscribe.length > 0
				|| this.preview.unsubscribe.length > 0
		},
		newListIDValid() {
			if (!this.newListID || typeof this.newListID !== 'string') {
				return false
			}
			const nid = this.newListID.trim()
			return (nid.length > 0
				&& !nid.includes(' ')
				&& !this.lists.map(l => l.id).includes(nid)
			)
		},
	},
	methods: {
		importSettingsLists() {
			this.lists = this.settings.lists.map((v, i) => Object.assign({}, v, {
				key: i,
				selectedGroups: v.groups.map(name => {
					return {
						gid: name,
						displayName: name,
						icon: 'icon-group',
						isNoUser: true,
					}
				}),
				selectedExtra: v.extra.join(', '),
			}))
		},
		splitEmails(str) {
			return str.split(',')
				.map(s => s.trim())
				.filter(s => s.length > 0)
		},
		isEmailValid(email) {
			return (email === '') ? true : this.emailreg.test(email)
		},
		extraMailsValid(list) {
			// logger.info('Splitting "' + list.selectedExtra + '"...', { addresses: this.splitEmails(list.selectedExtra) })
			// logger.info(this.splitEmails(list.selectedExtra).map(m => 'EMail "' + m + '" ' + (this.isEmailValid(m) ? 'VALID' : 'INVALID')).join('\n'))
			return this.splitEmails(list.selectedExtra).every(m => this.isEmailValid(m))
		},
		updatePreview(onSuccess, onError) {
			this.loading = true
			getPreview({
				lists: this.modifiedLists,
			})
				.then((p) => {
					this.preview = p
					if (onSuccess) {
						onSuccess()
					}
				})
				.catch((error) => {
					showError(t(this.appid, 'Preview request failed: ' + error))
					logger.error('Preview request failed', { error })
					if (onError) {
						onError()
					}
				})
				.then(() => {
					this.loading = false
				})
		},
		clearPreview() {
			this.preview = {
				delete: [],
				create: [],
				subscribe: [],
				unsubscribe: [],
			}
		},
		onGroupChange(g, i) {
			const lid = i.replace('group-select--', '')
			logger.info('onGroupChange: ', {
				listID: lid,
				groups: JSON.stringify(g, true),
			})
			this.updatePreview()
		},
		onExtraChange(l) {
			// const lid = i.replace('extra-select--', '')
			logger.info('onExtraChange: ', {
				listID: l.id,
				extra: l.selectedExtra,
			})
			const emails = this.splitEmails(l.selectedExtra)
			if ((emails.length !== l.extra.length
				|| emails.some(m => !l.extra.includes(m)))
				&& emails.every(m => this.isEmailValid(m))
			) {
				this.updatePreview()
			}
		},
		newList() {
			this.loading = true
			const newKey = this.lists.length
			this.lists.push({
				id: this.newListID.trim(),
				groups: [],
				extra: [],
				exclude: [],
				key: newKey,
				selectedGroups: [],
				selectedExtra: '',
			})
			this.lists.sort((a, b) => a.id.localeCompare(b.id))
			this.updatePreview(() => {
				this.newListID = null
			}, () => {
				this.lists.pop()
			})
		},
		deleteList(l) {
			this.loading = true
			this.lists.splice(l.key, 1)
			this.updatePreview()
		},
		submit() {
			this.loading = true
			setListData({
				lists: this.modifiedLists,
			})
				.then((d) => {
					if (d && Object.prototype.hasOwnProperty.call(d, 'error')) {
						showError(t(this.appid, 'Submitting list data failed: ' + d.error))
						logger.error('Set list data failed', d.error)
					} else {
						this.settings.lists = this.modifiedLists
						this.importSettingsLists()
						this.clearPreview()
					}
				})
				.catch((error) => {
					showError(t(this.appid, 'Submitting list data failed: ' + error))
					logger.error('Set list data failed', { error })
				})
				.then(() => {
					this.loading = false
				})
		},
		cancel() {
			this.importSettingsLists()
			this.clearPreview()
		},
	},
}
</script>

<style lang="scss" scoped>
table {
	border-spacing: 10px;
}

.list-id {
	font-weight: bold;
}

.extra-input {
	min-width: 300px;
}

.group-input::v-deep .multiselect {
	.multiselect__tag {
		background: rgba(255, 225, 128, 0.5);
	}
}

.multiselect__tag {
	background: rgba(255, 225, 128, 0.5);
}

.has-error {
	border-color: #843534 !important;
}

.has-success {
	border-color: #2b542c !important;
}

.has-warning {
	border-color: #66512c !important;
}

.input-group {
	&.has-error {
		-webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 6px #ce8483 !important;
		box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 6px #ce8483 !important;
	}
	&.has-success {
		-webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 6px #67b168 !important;
		box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 6px #67b168 !important;
	}
	&.has-warning {
		-webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 6px #c0a16b !important;
		box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 6px #c0a16b !important;
	}
	input {
		margin: 0
	}
}

</style>
