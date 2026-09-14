import '../css/app.css';
import '../css/sidebar.css';
import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
router.on('before', event => {
  const year=document.documentElement.dataset.academicYear;
  if(year)event.detail.visit.headers['X-Academic-Year']=year;
});
router.on('httpException', event => {
  if([403,409].includes(event.detail.response.status)) {
    event.preventDefault();
    window.dispatchEvent(new CustomEvent('academic-context-error',{detail:event.detail.response.status===409?'El curso académico o los datos han cambiado. Actualiza la página antes de guardar.':'Esta operación no está disponible. Comprueba los permisos y si el curso está abierto.'}));
  }
});
const pages = import.meta.glob<{default: DefineComponent}>('./pages/**/*.vue');
createInertiaApp({
  title: title => `${title} · Erronk2D`,
  resolve: async name => (await pages[`./pages/${name}.vue`]()).default,
  setup({ el, App, props, plugin }) { createApp({ render: () => h(App, props) }).use(plugin).mount(el); },
  progress: { color: '#427a5b' },
});
