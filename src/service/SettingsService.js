import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const getServerSettings = () => {
	const url = generateUrl('/apps/mailman/api/config')
	return axios.get(url).then((resp) => resp.data)
}

export const saveServerSettings = (config) => {
	const url = generateUrl('/apps/mailman/api/config')
	return axios.post(url, config).then((resp) => resp.data)
}

export const getServerStatus = () => {
	const url = generateUrl('/apps/mailman/api/status')
	return axios.get(url).then((resp) => resp.data)
}

export const getListData = () => {
	const url = generateUrl('/apps/mailman/api/listdata')
	return axios.get(url).then((resp) => resp.data)
}

export const setListData = (data) => {
	const url = generateUrl('/apps/mailman/api/listdata')
	return axios.post(url, data).then((resp) => resp.data)
}

export const updateListData = (id, data) => {
	const url = generateUrl('/apps/mailman/api/listdata/' + id)
	return axios.put(url, data).then((resp) => resp.data)
}

export const getPreview = (data) => {
	const url = generateUrl('/apps/mailman/api/preview')
	return axios.post(url, data).then((resp) => resp.data)
}
