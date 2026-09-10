import '../css/app.css';
import '../css/sidebar.css';
import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
const pages = import.meta.glob<{default: DefineComponent}>('./pages/**/*.vue');
createInertiaApp({
  title: title => `${title} · Erronk2D`,
  resolve: async name => (await pages[`./pages/${name}.vue`]()).default,
  setup({ el, App, props, plugin }) { createApp({ render: () => h(App, props) }).use(plugin).mount(el); },
  progress: { color: '#427a5b' },
});
