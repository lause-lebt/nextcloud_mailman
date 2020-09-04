<template>
	<div>
		<h2 v-if="valid && modified">
			{{ t(appid, 'The following changes will be made once you press "Save"') }}
		</h2>
		<h2 v-else-if="!valid" class="invalid">
			{{ t(appid, 'Configuration error') }}
		</h2>
		<ul v-if="valid && modified">
			<li v-if="preview.delete.length > 0">
				<b>{{ t(appid, 'DELETE lists') }}</b>
				<ol>
					<li v-for="l in preview.delete" :key="l">
						{{ l }}
					</li>
				</ol>
			</li>
			<li v-if="preview.create.length > 0">
				<b>{{ t(appid, 'CREATE lists') }}</b>
				<ol>
					<li v-for="l in preview.create" :key="l">
						{{ l }}
					</li>
				</ol>
			</li>
			<li v-if="preview.subscribe.length > 0">
				<b>{{ t(appid, 'SUBSCRIBE') }}</b>
				<ol>
					<li v-for="l in preview.subscribe" :key="l.list+'.'+l.email">
						{{ l.email + t(appid, ' TO ') + l.list }}
					</li>
				</ol>
			</li>
			<li v-if="preview.unsubscribe.length > 0">
				<b>{{ t(appid, 'UNSUBSCRIBE') }}</b>
				<ol>
					<li v-for="l in preview.unsubscribe" :key="l.list+'.'+l.email">
						{{ l.email + t(appid, ' FROM ') + l.list }}
					</li>
				</ol>
			</li>
		</ul>
	</div>
</template>

<script>
export default {
	name: 'Preview',
	props: {
		appid: {
			type: String,
			required: true,
		},
		valid: {
			type: Boolean,
			required: false,
			default: true,
		},
		preview: {
			type: Object,
			required: true,
		},
	},
	computed: {
		modified() {
			return this.preview.delete.length > 0
				|| this.preview.create.length > 0
				|| this.preview.subscribe.length > 0
				|| this.preview.unsubscribe.length > 0
		},
	},
}
</script>

<style lang="scss" scoped>
.invalid {
	color: var(--color-error)
}
</style>
