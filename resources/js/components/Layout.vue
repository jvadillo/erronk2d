<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Settings2, ChartNoAxesCombined, LogOut, ArrowUpRight, PanelLeftClose, PanelLeftOpen, ChevronDown, CalendarDays, School, ContactRound, GraduationCap, BookOpen, ListChecks, UserRoundPlus } from 'lucide-vue-next';
import type { Auth } from '../lib';

type SetupLink = {section:string;label:string;url:string};
const page = usePage();
const auth = computed(() => page.props.auth as Auth);
const currentPath = computed(() => page.url.split('?')[0]);
const setupLinks = computed(() => (page.props.setupNavigation ?? []) as SetupLink[]);
const sectionIcons = {courses:CalendarDays,classrooms:School,teachers:ContactRound,students:GraduationCap,modules:BookOpen,rubrics:ListChecks,registrations:UserRoundPlus};
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
    <main class="main"><div v-if="(page.props.flash as any)?.success" class="notice success" role="status">{{(page.props.flash as any).success}}</div><slot/></main>
  </div>
</template>
