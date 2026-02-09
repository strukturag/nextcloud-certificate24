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
import { DialogBuilder } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/style.css'

const confirmDialog = async (title, text) => {
	return new Promise((resolve, reject) => {
		let clicked = false
		const builder = (new DialogBuilder())
			.setName(title)
			.setText(text)
			.setButtons([
				{
					label: t('core', 'No'),
					callback: () => {
						clicked = true
						resolve(false)
					},
				},
				{
					label: t('core', 'Yes'),
					type: 'primary',
					callback: () => {
						clicked = true
						resolve(true)
					},
				},
			])

		const dialog = builder.build()
		dialog.show().then(() => {
			if (!clicked) {
				resolve(false)
			}
		})
	})
}

export default confirmDialog
