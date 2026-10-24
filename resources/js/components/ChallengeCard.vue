<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, CalendarDays, GraduationCap, UsersRound } from 'lucide-vue-next';
import { statusLabel } from '../lib';

const props = defineProps<{ challenge: { id: number; name: string; description: string | null; status: string; classroom: string; period: string; year: string; modules: string[]; teams_count: number } }>();

/**
 * Soft palette and shapes for the card header. Both derive from the challenge id, so a challenge
 * keeps its look everywhere; the pairs only repeat every 24 challenges.
 */
const themes = [
  { name: 'rosa', background: '#fbe9f3', shape: '#f3cde2', ink: '#a3417f' },
  { name: 'lavanda', background: '#eeeafb', shape: '#d9d0f5', ink: '#6a52b8' },
  { name: 'menta', background: '#e4f4ec', shape: '#c5e8d6', ink: '#2f7d5a' },
  { name: 'melocoton', background: '#fdeee3', shape: '#f7d4bc', ink: '#b2603a' },
  { name: 'cielo', background: '#e5f0fa', shape: '#c7def2', ink: '#3a6f9e' },
  { name: 'limon', background: '#faf4dc', shape: '#efe1ad', ink: '#8a7120' },
  { name: 'turquesa', background: '#e2f3f3', shape: '#c0e3e4', ink: '#2b7c80' },
  { name: 'coral', background: '#fce8e7', shape: '#f5c8c6', ink: '#b04a45' },
];
const shapes = ['circles', 'rings', 'triangles', 'waves', 'squares', 'dots'] as const;
const theme = computed(() => themes[props.challenge.id % themes.length]);
const shape = computed(() => shapes[(props.challenge.id * 5) % shapes.length]);
const dots = Array.from({ length: 24 }, (_, index) => ({ x: 196 + (index % 6) * 22, y: 16 + Math.floor(index / 6) * 22 }));
</script>

<template>
  <Link :href="`/challenges/${challenge.id}`" class="challenge-card" :data-theme="`${theme.name}-${shape}`" :style="{ '--card-background': theme.background, '--card-shape': theme.shape, '--card-ink': theme.ink }">
    <div class="card-cover">
      <svg class="card-art" viewBox="0 0 320 96" preserveAspectRatio="xMaxYMid slice" aria-hidden="true" focusable="false">
        <template v-if="shape === 'circles'"><circle cx="272" cy="18" r="70" /><circle cx="186" cy="96" r="30" opacity=".6" /></template>
        <g v-else-if="shape === 'rings'" class="card-art-lines"><circle cx="262" cy="48" r="24" /><circle cx="262" cy="48" r="50" opacity=".6" /><circle cx="262" cy="48" r="76" opacity=".35" /></g>
        <template v-else-if="shape === 'triangles'"><path d="M206 96 258 8 310 96Z" /><path d="M262 96 294 40 326 96Z" opacity=".6" /></template>
        <template v-else-if="shape === 'waves'"><path d="M0 62Q80 30 160 62T320 62V96H0Z" opacity=".6" /><path d="M0 78Q80 50 160 78T320 78V96H0Z" /></template>
        <template v-else-if="shape === 'squares'"><rect x="222" y="-14" width="72" height="72" rx="16" transform="rotate(20 258 22)" /><rect x="178" y="50" width="40" height="40" rx="10" transform="rotate(-15 198 70)" opacity=".6" /></template>
        <template v-else><circle v-for="dot in dots" :key="`${dot.x}-${dot.y}`" :cx="dot.x" :cy="dot.y" r="5" /></template>
      </svg>
      <span class="badge" :class="challenge.status">{{ statusLabel[challenge.status] }}</span>
    </div>
    <div class="card-body">
      <h2>{{ challenge.name }}</h2>
      <p class="card-description">{{ challenge.description || 'Un nuevo proyecto para aprender, colaborar y avanzar.' }}</p>
      <div class="module-chips"><span v-for="module in challenge.modules" :key="module">{{ module }}</span></div>
      <dl class="card-facts">
        <div><dt><GraduationCap :size="15" aria-hidden="true" /><span class="sr-only">Grupo</span></dt><dd>{{ challenge.classroom }}</dd></div>
        <div><dt><CalendarDays :size="15" aria-hidden="true" /><span class="sr-only">Evaluación</span></dt><dd>{{ challenge.period }}</dd></div>
        <div><dt><UsersRound :size="15" aria-hidden="true" /><span class="sr-only">Equipos</span></dt><dd>{{ challenge.teams_count }} {{ challenge.teams_count === 1 ? 'equipo' : 'equipos' }}</dd></div>
      </dl>
      <footer><span>{{ challenge.year }}</span><span class="card-open">Abrir reto <ArrowUpRight :size="16" /></span></footer>
    </div>
  </Link>
</template>

<style scoped>
.challenge-card { overflow: hidden; min-height: 0; padding: 0; border-radius: 16px; transition: border-color .15s, box-shadow .15s, transform .15s; }
.challenge-card:hover { transform: translateY(-2px); border-color: #e0d3dc; box-shadow: 0 14px 32px #4a0a3514; }
.challenge-card:focus-visible { outline: 3px solid #b0418f; outline-offset: 2px; }
.card-cover { position: relative; height: 96px; padding: 16px 18px; background: var(--card-background); }
.card-art { position: absolute; inset: 0; width: 100%; height: 100%; fill: var(--card-shape); transition: transform .3s; transform-origin: right center; }
.card-art-lines { fill: none; stroke: var(--card-shape); stroke-width: 10; }
.challenge-card:hover .card-art { transform: scale(1.04); }
.card-cover .badge { position: relative; margin: 0; }
.card-cover .badge:not(.active) { background: #ffffffd9; color: var(--card-ink); }
.card-body { display: flex; flex: 1; flex-direction: column; padding: 18px 20px 0; }
.challenge-card h2 { margin-bottom: 6px; color: #2b1f28; font-size: 18px; line-height: 1.3; letter-spacing: -.3px; }
.card-description { min-height: 0; margin-bottom: 14px; color: var(--muted); font-size: 12px; line-height: 1.55; }
.module-chips { margin: 0 0 16px; }
.card-facts { display: flex; flex-wrap: wrap; gap: 8px 18px; margin: auto 0 16px; }
.card-facts div { display: flex; align-items: center; gap: 6px; min-width: 0; }
.card-facts dt { display: flex; color: var(--card-ink); }
.card-facts dd { margin: 0; overflow: hidden; color: #3b2a36; font-size: 12px; font-weight: 500; text-overflow: ellipsis; white-space: nowrap; }
.challenge-card footer { margin: 0 -20px; padding: 12px 20px; color: var(--muted); font-size: 11px; }
.card-open { gap: 4px; color: var(--green); font-weight: 500; }
.card-open svg { transition: transform .15s; }
.challenge-card:hover .card-open svg { transform: translate(2px, -2px); }
@media (prefers-reduced-motion: reduce) {
  .challenge-card, .card-art, .card-open svg { transition: none; }
  .challenge-card:hover, .challenge-card:hover .card-art { transform: none; }
}
</style>
