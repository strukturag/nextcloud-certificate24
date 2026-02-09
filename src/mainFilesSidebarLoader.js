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
import Vue from 'vue'
import wrap from '@vue/web-component-wrapper'
import { registerSidebarTab, FileType } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'

import tabIcon from '../img/app.svg?raw'

if (!window.OCA.Certificate24) {
	window.OCA.Certificate24 = {}
}

const tagName = 'certificate24-signatures-tab'

registerSidebarTab({
	id: 'signatures',
	displayName: t('certificate24', 'Signatures'),
	iconSvgInline: tabIcon,
	tagName,
	order: 20,
	enabled: (context) => {
		const node = context.node
		if (node.type !== FileType.File) {
			return false
		}

		return node.mime === 'application/pdf'
	},

	async onInit() {
		const { default: FilesSidebarTab } = await import('./views/FilesSidebarTab.vue')

		const webComponent = wrap(Vue, FilesSidebarTab)
		// In Vue 2, wrap doesn't support disabling shadow. Disable with a hack
		Object.defineProperty(webComponent.prototype, 'attachShadow', {
			value() { return this },
		})
		Object.defineProperty(webComponent.prototype, 'shadowRoot', {
			get() { return this },
		})
		window.customElements.define(tagName, webComponent)
	},
})

window.addEventListener('DOMContentLoaded', () => {
	// Nextcloud < 33.
	if (OCA.Files && OCA.Files.Sidebar && OCA.Files.Sidebar.Tab) {
		let tabInstance = null

		const isEnabled = function(fileInfo) {
			if (!fileInfo || fileInfo.isDirectory()) {
				return false
			}

			return fileInfo.mimetype === 'application/pdf'
		}

		OCA.Files.Sidebar.registerTab(new OCA.Files.Sidebar.Tab({
			id: 'signatures',
			name: t('certificate24', 'Signatures'),
			icon: 'icon-certificate24',
			enabled: isEnabled,

			async mount(el, fileInfo, context) {
				if (tabInstance) {
					tabInstance.$destroy()
				}

				// Dirty hack to force the style on parent component
				const tabChat = document.querySelector('#tab-signatures')
				tabChat.style.height = '100%'

				OCA.Certificate24.fileInfo = this.fileInfo
				tabInstance = OCA.Certificate24.newTab()
				tabInstance.$mount(el)
			},
			update(fileInfo) {
				OCA.Certificate24.fileInfo = fileInfo
			},
			destroy() {
				OCA.Certificate24.fileInfo = null
				tabInstance.$destroy()
				tabInstance = null
			},
		}))
	}
})
