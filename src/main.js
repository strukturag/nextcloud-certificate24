import Vue from 'vue'
import App from './App.vue'

// Styles
import '@nextcloud/dialogs/styles/toast.scss'

Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = OC
Vue.prototype.OCA = OCA
Vue.prototype.OCP = OCP

export default new Vue({
	el: '#content',
	render: h => h(App),
})
