import { createApp, reactive } from 'vue';
import './style.css';
import App from './App.vue';
import router from './router';

import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

const token = localStorage.getItem('jwtToken');
const user = JSON.parse(localStorage.getItem('user'));

const app = createApp(App);

app.config.globalProperties.$auth = reactive({
  isLoggedIn: !!token,
  token,
  user
});

app.use(router).mount('#app');