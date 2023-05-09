/**
 * @copyright Copyright (c) 2023, struktur AG.
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

const isEnabled = function(fileInfo) {
	if (!fileInfo || fileInfo.isDirectory()) {
		return false
	}

	return fileInfo.mimetype === 'application/pdf'
}

let tabInstance = null

if (!window.OCA.Esig) {
	window.OCA.Esig = {}
}

window.addEventListener('DOMContentLoaded', () => {
	if (OCA.Files && OCA.Files.Sidebar) {
		OCA.Files.Sidebar.registerTab(new OCA.Files.Sidebar.Tab({
			id: 'signatures',
			name: t('esig', 'Signatures'),
			icon: 'icon-esig',
			enabled: isEnabled,

			async mount(el, fileInfo, context) {
				if (tabInstance) {
					tabInstance.$destroy()
				}

				// Dirty hack to force the style on parent component
				const tabChat = document.querySelector('#tab-signatures')
				tabChat.style.height = '100%'

				OCA.Esig.fileInfo = this.fileInfo
				tabInstance = OCA.Esig.newTab()
				tabInstance.$mount(el)
			},
			update(fileInfo) {
				OCA.Esig.fileInfo = fileInfo
			},
			destroy() {
				OCA.Esig.fileInfo = null
				tabInstance.$destroy()
				tabInstance = null
			},
		}))
	}
})
