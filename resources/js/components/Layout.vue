<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Settings2, ChartNoAxesCombined, LogOut, ArrowUpRight } from 'lucide-vue-next';
import type { Auth } from '../lib';
const page = usePage();
const auth = page.props.auth as Auth;
</script>
<template>
  <div class="app-shell">
    <aside class="sidebar">
      <Link href="/" class="brand"><span class="brand-mark">E<span>2</span></span><span>ERRONK<span class="brand-light">2D</span></span></Link>
      <p class="nav-label">ESPACIO DE EVALUACIÓN</p>
      <nav aria-label="Navegación principal">
        <Link href="/" :class="{active:page.url==='/' || page.url.startsWith('/challenges')}"><LayoutGrid :size="19"/>Retos <ArrowUpRight :size="15" class="nav-arrow"/></Link>
        <Link v-if="auth.role!=='student'" href="/reports" :class="{active:page.url.startsWith('/reports')}"><ChartNoAxesCombined :size="19"/>Evaluaciones</Link>
        <Link v-if="auth.role!=='student'" href="/setup" :class="{active:page.url.startsWith('/setup')}"><Settings2 :size="19"/>Organización</Link>
      </nav>
      <div class="sidebar-note"><span class="tiny-orbit">↗</span><p>Aprender en equipo.<br><strong>Crecer individualmente.</strong></p></div>
      <div class="profile"><span class="avatar">{{auth.name.split(' ').slice(0,2).map(n=>n[0]).join('')}}</span><div><strong>{{auth.name}}</strong><small>{{auth.role==='admin'?'Administración':auth.role==='teacher'?'Profesorado':'Estudiante'}}</small></div><Link href="/logout" method="post" as="button" aria-label="Cerrar sesión"><LogOut :size="17"/></Link></div>
    </aside>
    <main class="main"><div v-if="(page.props.flash as any)?.success" class="notice success" role="status">{{(page.props.flash as any).success}}</div><slot/></main>
  </div>
</template>
