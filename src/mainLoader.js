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
import { Permission, registerFileAction } from '@nextcloud/files'
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

app.$on('dialog:open', (node) => {
	app.$data.fileModel = node
})

app.$on('dialog:closed', () => {
	app.$data.fileModel = null
})

registerFileAction({
	id: 'certificate24-sign',
	displayName: () => t('certificate24', 'Request signature'),
	iconSvgInline: () => Logo,
	enabled: (context) => {
		const nodes = context.nodes ? context.nodes : context
		return (nodes.length === 1
				&& nodes[0].mime === 'application/pdf'
				&& (nodes[0].permissions & (Permission.READ | Permission.UPDATE)) === (Permission.READ | Permission.UPDATE))
	},
	exec: (context) => {
		const nodes = context.nodes ? context.nodes : [context]
		app.$emit('dialog:open', nodes[0])
	},
})
