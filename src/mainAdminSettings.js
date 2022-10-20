import Vue from 'vue'
import AdminSettings from './views/AdminSettings.vue'

// Styles
import '@nextcloud/dialogs/styles/toast.scss'

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA
Vue.prototype.OCP = OCP

export default new Vue({
	el: '#admin_settings',
	render: h => h(AdminSettings),
})
