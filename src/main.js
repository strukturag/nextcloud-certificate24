import Vue from 'vue'
import App from './App.vue'
import { Tooltip } from '@nextcloud/vue'

// Styles
import '@nextcloud/dialogs/styles/toast.scss'

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA
Vue.prototype.OCP = OCP

Vue.directive('tooltip', Tooltip)

export default new Vue({
	el: '#content',
	render: h => h(App),
})
