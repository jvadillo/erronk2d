<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';

defineProps<{ group: { id: number; name: string; cycle_name: string; level: number; students_count: number; users: unknown[]; periods: unknown[]; url: string } }>();
</script>

<template>
    <section class="panel group-card">
        <h3><Link :href="group.url">{{ group.name }}</Link></h3>
        <p class="muted">{{ group.cycle_name }} · {{ group.level }}.º curso</p>
        <dl class="group-stats">
            <div><dt>Estudiantes</dt><dd>{{ group.students_count }}</dd></div>
            <div><dt>Profesores</dt><dd>{{ group.users.length }}</dd></div>
            <div><dt>Evaluaciones</dt><dd>{{ group.periods.length }}</dd></div>
        </dl>
        <footer><Link class="button small" :href="group.url" :aria-label="`Abrir el grupo ${group.name}`">Abrir grupo<ArrowRight :size="15" /></Link></footer>
    </section>
</template>

<style scoped>
.group-card { min-width: 0; display: flex; flex-direction: column; }
.group-card h3 { margin: 0 0 4px; overflow-wrap: anywhere; }
.group-card h3 a:hover { color: var(--green); text-decoration: underline; }
.group-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin: 18px 0 0; }
.group-stats div { padding: 10px 12px; border: 1px solid var(--border); border-radius: 7px; background: #fbf8fa; }
.group-stats dt { font-size: 10px; color: var(--muted); }
.group-stats dd { margin: 2px 0 0; font-size: 20px; font-weight: 500; font-variant-numeric: tabular-nums; }
.group-card footer { margin-top: 18px; }
</style>
