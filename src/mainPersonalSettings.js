import Vue from 'vue'
import PersonalSettings from './views/PersonalSettings.vue'

// Styles
import '@nextcloud/dialogs/styles/toast.scss'

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA
Vue.prototype.OCP = OCP

export default new Vue({
	el: '#personal_settings',
	render: h => h(PersonalSettings),
})
