/**
 * @copyright Copyright (c) 2022, struktur AG.
 *
 * @author Joachim Bauch <bauch@struktur.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 */

import Vue from 'vue'
import VueObserveVisibility from 'vue-observe-visibility'
import { Tooltip } from '@nextcloud/vue'

import '@nextcloud/dialogs/dist/index.css'

import ShareDialogView from './views/ShareDialogView.vue'
import './styles/loader.scss'

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA
Vue.prototype.OCP = OCP

Vue.directive('tooltip', Tooltip)

Vue.use(VueObserveVisibility)

const el = document.createElement('div')
document.body.appendChild(el)

const app = new Vue({
	el,
	data: {
		fileModel: null,
	},
	render: h => h(ShareDialogView),
})

app.$on('dialog:open', (model) => {
	app.$data.fileModel = model
})

app.$on('dialog:closed', () => {
	app.$data.fileModel = null
})

OCA.Certificate24 = OCA.Certificate24 || {}

/**
 * @namespace OCA.Certificate24.SignPlugin
 */
OCA.Certificate24.SignPlugin = {

	attach(fileList) {
		if (fileList.$el && fileList.$el.attr('id') === 'app-content-trashbin') {
			// Don't add action to files in trashbin.
			return
		}

		fileList.fileActions.registerAction({
			displayName: t('certificate24', 'Request signature'),
			iconClass: 'icon-certificate24-sign',
			name: 'Sign',
			mime: 'application/pdf',
			permissions: OC.PERMISSION_READ | OC.PERMISSION_WRITE,
			actionHandler: function(fileName, context) {
				const fileInfoModel = context.fileInfoModel || context.fileList.getModelForFile(fileName)
				this.show(fileInfoModel)
			}.bind(this),
		})
	},

	show(id) {
		app.$emit('dialog:open', id)
	},
}

window.addEventListener('DOMContentLoaded', () => {
	OC.Plugins.register('OCA.Files.FileList', OCA.Certificate24.SignPlugin)
})
