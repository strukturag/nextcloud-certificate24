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

import { shareFile } from './services/apiservice.js'

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
				iconClass: 'icon-sign',
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
			return shareFile(id, 'admin', 'user')
		},
	}
})(OCA)

OC.Plugins.register('OCA.Files.FileList', OCA.Esig.SignPlugin)
