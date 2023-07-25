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

const state = {
	incoming: [],
	own: [],
}

const getters = {
	getIncomingRequests: (state) => () => {
		return state.incoming
	},
	getOwnRequests: (state) => () => {
		return state.own
	},
}

const mutations = {
	setIncomingRequests(state, requests) {
		state.incoming = requests
	},
	setOwnRequests(state, requests) {
		state.own = requests
	},
	deleteOwnRequest(state, request) {
		state.own = state.own.filter((x) => {
			return x.request_id !== request.request_id
		})
		// Also handle case where a request by and to the user itself is deleted.
		state.incoming = state.incoming.filter((x) => {
			return x.request_id !== request.request_id
		})
	},
}

const actions = {
	setIncomingRequests(context, requests) {
		context.commit('setIncomingRequests', requests)
	},
	setOwnRequests(context, requests) {
		context.commit('setOwnRequests', requests)
	},
	deleteOwnRequest(context, request) {
		context.commit('deleteOwnRequest', request)
	},
}

export default { state, mutations, getters, actions }
