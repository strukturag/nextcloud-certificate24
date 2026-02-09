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
import { FileAction, Permission, registerFileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'

import '@nextcloud/dialogs/style.css'

import ShareDialogView from './views/ShareDialogView.vue'
import Logo from '../img/app.svg?raw'
import './styles/loader.scss'

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

registerFileAction(new FileAction({
	id: 'certificate24-sign',
	displayName: () => t('certificate24', 'Request signature'),
	iconSvgInline: () => Logo,
	enabled: (files, view) => {
		return (files.length === 1
				&& files[0].mime === 'application/pdf'
				&& (files[0].permissions & (Permission.READ | Permission.WRITE)) === (Permission.READ | Permission.WRITE))
	},
	exec: (file, view, dir) => {
		app.$emit('dialog:open', file)
	},
}))
