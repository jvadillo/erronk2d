<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Settings2, ChartNoAxesCombined, LogOut, ArrowUpRight, PanelLeftClose, PanelLeftOpen, ChevronDown, CalendarDays, School, ContactRound, GraduationCap, BookOpen, ListChecks, UserRoundPlus } from 'lucide-vue-next';
import { notify, type Auth } from '../lib';
import Toasts from './Toasts.vue';

type SetupLink = {section:string;label:string;url:string};
const page = usePage();
const academic = computed(() => page.props.academic as any);
const switching = ref(false);
watch(() => (page.props.flash as any)?.success, (message) => {if(message&&typeof window!=='undefined')notify(message,'success')}, {immediate:true});
watch(() => academic.value?.year?.id, (id) => {if(typeof document!=='undefined')document.documentElement.dataset.academicYear=String(id??'')}, {immediate:true});
function switchYear(event:Event) {
  const academicYearId=Number((event.target as HTMLSelectElement).value);
  if(academicYearId===academic.value?.year?.id)return;
  switching.value=true;
  router.post(academic.value.switch_url,{academic_year_id:academicYearId},{preserveState:false,onFinish:()=>switching.value=false});
}
function showContextError(event:Event){notify((event as CustomEvent).detail,'error',{duration:null,action:{label:'Volver al curso activo',run:()=>router.visit('/')}})}
onMounted(()=>window.addEventListener('academic-context-error',showContextError));
onUnmounted(()=>window.removeEventListener('academic-context-error',showContextError));
const auth = computed(() => page.props.auth as Auth);
const currentPath = computed(() => page.url.split('?')[0]);
const setupLinks = computed(() => (page.props.setupNavigation ?? []) as SetupLink[]);
const sectionIcons = {courses:CalendarDays,cycles:BookOpen,classrooms:School,teachers:ContactRound,students:GraduationCap,modules:BookOpen,rubrics:ListChecks,registrations:UserRoundPlus};
const inOrganization = computed(() => currentPath.value.startsWith('/setup'));
const organizationOpen = ref(inOrganization.value && (typeof window === 'undefined' || !window.matchMedia('(max-width: 650px)').matches));
const organizationButton = ref<HTMLButtonElement|null>(null);
function storedCollapse(): boolean {
  if (typeof window === 'undefined') return false;
  try {
    const value = window.localStorage.getItem('erronk2d.sidebar.collapsed');
    if (value !== null) return value === 'true';
  } catch {}
  return window.matchMedia('(max-width: 900px)').matches;
}
const collapsed = ref(storedCollapse());
function toggleSidebar() {
  collapsed.value = !collapsed.value;
  try {window.localStorage.setItem('erronk2d.sidebar.collapsed', String(collapsed.value))} catch {}
}
function closeOrganization() {
  organizationOpen.value = false;
  organizationButton.value?.focus();
}
watch(currentPath, () => {
  organizationOpen.value = window.matchMedia('(max-width: 650px)').matches ? false : inOrganization.value;
});
function isCurrent(url:string): boolean {
  return new URL(url, 'http://localhost').pathname === currentPath.value;
}
</script>
<template>
  <div class="app-shell" :class="{'sidebar-collapsed':collapsed}">
    <aside class="sidebar" aria-label="Menú lateral">
      <div class="sidebar-header">
        <Link href="/" class="brand" aria-label="Erronk2D, inicio"><span class="brand-mark">E<span>2</span></span><span class="brand-name">ERRONK<span class="brand-light">2D</span></span></Link>
        <button type="button" class="sidebar-toggle" :aria-label="collapsed?'Expandir menú lateral':'Contraer menú lateral'" :title="collapsed?'Expandir menú lateral':'Contraer menú lateral'" :aria-expanded="!collapsed" aria-controls="main-navigation" @click="toggleSidebar"><PanelLeftOpen v-if="collapsed" :size="19"/><PanelLeftClose v-else :size="19"/></button>
      </div>
      <label class="academic-selector"><span>Curso académico</span><select aria-label="Curso académico activo" :title="academic?.year?.name??'Sin curso disponible'" :value="academic?.year?.id??''" :disabled="switching||!academic?.years?.length" @change="switchYear"><option v-if="!academic?.years?.length" value="">Sin curso</option><option v-for="year in academic?.years" :key="year.id" :value="year.id">{{year.name}}{{year.is_open?'':' · Cerrado'}}</option></select></label>
      <p class="nav-label">ESPACIO DE EVALUACIÓN</p>
      <nav id="main-navigation" aria-label="Navegación principal">
        <Link href="/" :class="{active:currentPath==='/'||currentPath.startsWith('/challenges')}" :aria-current="currentPath==='/'||currentPath.startsWith('/challenges')?'page':undefined" aria-label="Retos" title="Retos"><LayoutGrid :size="19"/><span class="nav-text">Retos</span><ArrowUpRight :size="15" class="nav-arrow"/></Link>
        <Link v-if="auth.role!=='student'" href="/reports" :class="{active:currentPath.startsWith('/reports')}" :aria-current="currentPath.startsWith('/reports')?'page':undefined" aria-label="Evaluaciones" title="Evaluaciones"><ChartNoAxesCombined :size="19"/><span class="nav-text">Evaluaciones</span></Link>
        <div v-if="setupLinks.length" class="organization-group" @keydown.esc.stop="closeOrganization">
          <button ref="organizationButton" type="button" class="organization-toggle" :class="{'section-active':inOrganization}" :aria-expanded="organizationOpen" aria-controls="organization-submenu" aria-label="Organización" title="Organización" @click="organizationOpen=!organizationOpen"><Settings2 :size="19"/><span class="nav-text">Organización</span><ChevronDown :size="15" class="submenu-arrow" :class="{rotated:organizationOpen}"/></button>
          <ul v-if="organizationOpen" id="organization-submenu" class="organization-submenu" aria-label="Secciones de Organización">
            <li v-for="link in setupLinks" :key="link.section"><Link :href="link.url" :class="{active:isCurrent(link.url)}" :aria-current="isCurrent(link.url)?'page':undefined" :aria-label="link.label" :title="link.label"><component :is="sectionIcons[link.section as keyof typeof sectionIcons]" :size="17"/><span class="nav-text">{{link.label}}</span></Link></li>
          </ul>
        </div>
      </nav>
      <div class="sidebar-note"><span class="tiny-orbit">↗</span><p>Aprender en equipo.<br><strong>Crecer individualmente.</strong></p></div>
      <div class="profile"><span class="avatar" :title="auth.name">{{auth.name.split(' ').slice(0,2).map(n=>n[0]).join('')}}</span><div class="profile-info"><strong>{{auth.name}}</strong><small>{{auth.role==='admin'?'Administración':auth.role==='teacher'?'Profesorado':'Estudiante'}}</small></div><Link href="/logout" method="post" as="button" aria-label="Cerrar sesión" title="Cerrar sesión"><LogOut :size="17"/></Link></div>
    </aside>
    <main class="main"><div v-if="academic?.year&&!academic.year.is_open" class="notice warning" role="status">{{academic.year.name}} · Curso cerrado. Solo lectura.</div><div v-if="academic&&!academic.year" class="notice" role="status">{{auth.role==='admin'?'Configura el primer curso académico, los ciclos y los módulos en Organización.':auth.role==='student'?'Todavía no tienes matrícula. Estás a la espera de asignación administrativa.':'No hay cursos académicos disponibles. Estás a la espera de asignación administrativa.'}}</div><slot/></main>
    <Toasts/>
  </div>
</template>
