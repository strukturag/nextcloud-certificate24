/**
 * @copyright Copyright (C) 2022, struktur AG
 *
 * @author Joachim Bauch <mail@joachim-bauch.de>
 *
 * @license AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
import Vue from 'vue'

import ShareDialogView from './views/ShareDialogView.vue'

import VueObserveVisibility from 'vue-observe-visibility'

import '@nextcloud/dialogs/styles/toast.scss'

import './styles/loader.scss'

(function(OCA) {
	OCA.Esig = OCA.Esig || {}

	/**
	 * @namespace OCA.Esig.SignPlugin
	 */
	OCA.Esig.SignPlugin = {

		attach(fileList) {
			if (fileList.$el && fileList.$el.attr('id') === 'app-content-trashbin') {
				// Don't add action to files in trashbin.
				return
			}

			fileList.fileActions.registerAction({
				displayName: t('esig', 'Request signature'),
				iconClass: 'icon-esig-sign',
				name: 'Sign',
				mime: 'application/pdf',
				permissions: OC.PERMISSION_READ | OC.PERMISSION_WRITE,
				actionHandler: function(fileName, context) {
					const fileInfoModel = context.fileInfoModel || context.fileList.getModelForFile(fileName)
					this.show(fileInfoModel.id)
				}.bind(this),
			})
		},

		async show(id) {
			const el = document.createElement('div')
			el.id = 'esig-sign-dialog'
			document.body.appendChild(el)

			const tmp = new Vue({
				el: '#esig-sign-dialog',
				data: {
					fileId: id,
				},
				render: h => h(ShareDialogView),
			})
			console.error('XXX', tmp)
		},
	}
})(OCA)

window.addEventListener('DOMContentLoaded', () => {
	Vue.prototype.t = t
	Vue.prototype.n = n
	Vue.prototype.OC = OC
	Vue.prototype.OCA = OCA
	Vue.prototype.OCP = OCP

	Vue.use(VueObserveVisibility)

	OC.Plugins.register('OCA.Files.FileList', OCA.Esig.SignPlugin)
})
