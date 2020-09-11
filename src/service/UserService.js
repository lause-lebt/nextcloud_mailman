import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const getLists = async() => {
	const url = generateUrl('apps/mailman/api/lists')
	const response = await axios.get(url)
	if (!response.data) {
		throw new Error('Server error')
	} else if (response.data.error) {
		throw new Error(response.data.error)
	} else if (!response.data.lists) {
		return []
	}
	return response.data.lists
}

export const subscribe = async(listid) => {
	const url = generateUrl('apps/mailman/api/subscribe')
	const response = await axios.post(url, { list: listid })
	if (response.data && response.data.error) {
		throw new Error(response.data.error)
	}
}

export const unsubscribe = async(listid) => {
	const url = generateUrl('apps/mailman/api/unsubscribe')
	const response = await axios.post(url, { list: listid })
	if (response.data && response.data.error) {
		throw new Error(response.data.error)
	}
}
