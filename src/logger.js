import { getLoggerBuilder } from '@nextcloud/logger'

export default getLoggerBuilder().setApp('mailman').detectUser().build()
