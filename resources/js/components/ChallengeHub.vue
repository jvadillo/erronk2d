<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { ArrowUpRight, ChevronRight, ClipboardCheck, FilePenLine, ListChecks, NotebookPen, Settings2, Table2, UserPlus, Users, UsersRound } from 'lucide-vue-next';

type Section = 'teams' | 'evaluation' | 'evidence' | 'settings';
type Shortcut = { id: string; label: string; hint: string; href?: string };

const props = defineProps<{
  book: any;
  evidences: { student_id: number; student_name: string; author_name: string; note: string; created_at: string }[];
  challengeUrl: string;
  canManageTeams: boolean;
  canEditRubrics: boolean;
}>();

const emit = defineEmits<{
  open: [section: Section];
  rubric: [kind: 'team' | 'teacher'];
  participants: [];
  editRubric: [kind: 'team' | 'transversal'];
}>();

const students = computed(() => props.book.rows.length);
const complete = computed(() => props.book.rows.filter((row: any) => row.pending.length === 0).length);
const progress = computed(() => (students.value ? Math.round((complete.value / students.value) * 100) : 0));
const unassigned = computed(() => props.book.rows.filter((row: any) => !row.team_id).length);
const studentNames = computed(() => new Map<number, string>(props.book.rows.map((row: any) => [row.id, row.name])));
const visibleTeams = computed(() => props.book.teams.map((team: any) => ({
  id: team.id,
  name: team.name,
  members: team.members.map((member: any) => studentNames.value.get(member.student_id) ?? '').filter(Boolean),
})));
const notedStudents = computed(() => new Set(props.evidences.map(evidence => evidence.student_id)).size);
const recentEvidences = computed(() => props.evidences.slice(0, 2));
const teamList = ref<HTMLElement | null>(null);
const teamsOverflow = ref(false);
let teamListObserver: ResizeObserver | null = null;

function measureTeams(): void {
  teamsOverflow.value = !!teamList.value && teamList.value.scrollHeight > teamList.value.clientHeight + 1;
}

onMounted(() => {
  teamListObserver = new ResizeObserver(measureTeams);
  if (teamList.value) {
    teamListObserver.observe(teamList.value);
  }
  measureTeams();
});
onUnmounted(() => teamListObserver?.disconnect());
watch(visibleTeams, () => nextTick(measureTeams));

function initials(name: string): string {
  return name.split(' ').slice(0, 2).map(part => part[0]).join('');
}
const weights = computed(() => [
  { key: 'transversal', label: 'Transversales', value: Number(props.book.challenge.component_weights.transversal) },
  { key: 'challenge', label: 'Reto', value: Number(props.book.challenge.component_weights.challenge) },
  { key: 'exam', label: 'Examen', value: Number(props.book.challenge.component_weights.exam) },
]);
const period = computed(() => {
  const { starts_at: start, ends_at: end } = props.book.challenge;
  if (!start && !end) {
    return 'Sin fechas definidas';
  }
  return [start, end].map(date => (date ? formatDate(date) : '…')).join(' – ');
});

const url = (query: string): string => `${props.challengeUrl}?${query}`;
const evaluationShortcuts = computed<Shortcut[]>(() => [
  { id: 'matrix', label: 'Tabla general', hint: 'Notas de todos los módulos', href: url('tab=evaluation') },
  { id: 'team', label: 'Ev. técnica', hint: 'Rúbrica de cada equipo', href: url('tab=evaluation&evaluation=team') },
  { id: 'teacher', label: 'Ev. transversales', hint: 'Valoración del profesorado', href: url('tab=evaluation&evaluation=teacher') },
]);

function formatDate(value: string): string {
  return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
}

function formatRelative(value: string): string {
  return new Date(value).toLocaleDateString('es-ES', { day: 'numeric', month: 'long' });
}

function runEvaluationShortcut(id: string): void {
  if (id === 'matrix') {
    emit('open', 'evaluation');
  } else if (id === 'team' || id === 'teacher') {
    emit('rubric', id);
  }
}
</script>

<template>
  <div class="hub-grid">
    <article class="hub-card">
      <header class="hub-card-header">
        <span class="hub-icon"><UsersRound :size="20" /></span>
        <div>
          <h2><a :href="url('tab=teams')" class="hub-title" @click.prevent="emit('open', 'teams')">Estudiantes y Equipos</a></h2>
          <p>Forma los equipos y revisa quién participa.</p>
        </div>
        <ArrowUpRight class="hub-arrow" :size="18" aria-hidden="true" />
      </header>
      <div class="hub-figures">
        <p><strong>{{ students }}</strong> estudiantes</p>
        <p><strong>{{ book.teams.length }}</strong> equipos</p>
        <p v-if="students" :class="unassigned ? 'hub-flag warning' : 'hub-flag'">{{ unassigned ? `${unassigned} sin equipo` : 'Todos con equipo' }}</p>
      </div>
      <ul v-if="visibleTeams.length" ref="teamList" class="hub-teams" :class="{ 'is-clipped': teamsOverflow }" :tabindex="teamsOverflow ? 0 : undefined" aria-label="Equipos del reto">
        <li v-for="team in visibleTeams" :key="team.id">
          <span class="hub-team-name">{{ team.name }}</span>
          <span class="hub-avatars" :aria-label="team.members.join(', ')" role="img"><i v-for="member in team.members" :key="member" :title="member">{{ initials(member) }}</i></span>
          <b>{{ team.members.length }}</b>
        </li>
      </ul>
      <p v-else class="hub-empty">Todavía no hay equipos en este reto.</p>
      <ul class="hub-links">
        <li><a :href="url('tab=teams')" @click.prevent="emit('open', 'teams')" aria-describedby="hub-hint-gestionar-equipos"><Users :size="17" /><span>Gestionar equipos<small id="hub-hint-gestionar-equipos" aria-hidden="true">Nombres e integrantes</small></span><ChevronRight :size="16" /></a></li>
        <li v-if="!students && canManageTeams"><button type="button" @click="emit('participants')" aria-describedby="hub-hint-incorporar-estudiantes-del-grupo"><UserPlus :size="17" /><span>Incorporar estudiantes del grupo<small id="hub-hint-incorporar-estudiantes-del-grupo" aria-hidden="true">Matrícula actual del grupo</small></span><ChevronRight :size="16" /></button></li>
      </ul>
    </article>

    <article class="hub-card hub-card-primary">
      <header class="hub-card-header">
        <span class="hub-icon"><ClipboardCheck :size="20" /></span>
        <div>
          <h2><a :href="url('tab=evaluation')" class="hub-title" @click.prevent="emit('open', 'evaluation')">Evaluación</a></h2>
          <p>Notas, rúbricas y defensas del grupo.</p>
        </div>
        <ArrowUpRight class="hub-arrow" :size="18" aria-hidden="true" />
      </header>
      <div class="hub-progress">
        <p><strong>{{ complete }}</strong> de {{ students }} estudiantes con la evaluación completa</p>
        <div class="hub-progress-track" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100" aria-label="Evaluaciones completas"><i :style="{ width: `${progress}%` }" /></div>
        <p v-if="book.issues.length" class="hub-issues">{{ book.issues.length }} {{ book.issues.length === 1 ? 'aviso pendiente de revisar' : 'avisos pendientes de revisar' }}</p>
      </div>
      <ul class="hub-links">
        <li v-for="shortcut in evaluationShortcuts" :key="shortcut.id">
          <component :is="shortcut.href ? 'a' : 'button'" :href="shortcut.href" :type="shortcut.href ? undefined : 'button'" :aria-describedby="`hub-hint-${shortcut.id}`" @click.prevent="runEvaluationShortcut(shortcut.id)">
            <Table2 v-if="shortcut.id === 'matrix'" :size="17" /><ListChecks v-else :size="17" />
            <span>{{ shortcut.label }}<small :id="`hub-hint-${shortcut.id}`" aria-hidden="true">{{ shortcut.hint }}</small></span><ChevronRight :size="16" />
          </component>
        </li>
      </ul>
    </article>

    <article class="hub-card">
      <header class="hub-card-header">
        <span class="hub-icon"><NotebookPen :size="20" /></span>
        <div>
          <h2><a :href="url('tab=evidence')" class="hub-title" @click.prevent="emit('open', 'evidence')">Evidencias</a></h2>
          <p>Anotaciones de seguimiento de cada estudiante.</p>
        </div>
        <ArrowUpRight class="hub-arrow" :size="18" aria-hidden="true" />
      </header>
      <div class="hub-figures">
        <p><strong>{{ evidences.length }}</strong> {{ evidences.length === 1 ? 'anotación' : 'anotaciones' }}</p>
        <p><strong>{{ notedStudents }}</strong> de {{ students }} estudiantes con seguimiento</p>
      </div>
      <ol v-if="recentEvidences.length" class="hub-notes" aria-label="Últimas anotaciones">
        <li v-for="(evidence, index) in recentEvidences" :key="index">
          <p><strong>{{ evidence.student_name }}</strong><time :datetime="evidence.created_at">{{ formatRelative(evidence.created_at) }}</time></p>
          <blockquote>{{ evidence.note }}</blockquote>
          <small>Anotada por {{ evidence.author_name }}</small>
        </li>
      </ol>
      <p v-else class="hub-empty">Aún no hay anotaciones. La primera aparecerá aquí.</p>
      <ul class="hub-links">
        <li><a :href="url('tab=evidence')" @click.prevent="emit('open', 'evidence')" aria-describedby="hub-hint-abrir-el-historial"><NotebookPen :size="17" /><span>Abrir el historial<small id="hub-hint-abrir-el-historial" aria-hidden="true">Busca y anota por estudiante</small></span><ChevronRight :size="16" /></a></li>
      </ul>
    </article>

    <article class="hub-card">
      <header class="hub-card-header">
        <span class="hub-icon"><Settings2 :size="20" /></span>
        <div>
          <h2><a :href="url('tab=settings')" class="hub-title" @click.prevent="emit('open', 'settings')">Configuración</a></h2>
          <p>Pesos, fechas, defensas y rúbricas.</p>
        </div>
        <ArrowUpRight class="hub-arrow" :size="18" aria-hidden="true" />
      </header>
      <div class="hub-weights">
        <p>Nota de módulo <span>{{ period }}</span></p>
        <div class="hub-weight-bar" aria-hidden="true"><i v-for="weight in weights" :key="weight.key" :class="`weight-${weight.key}`" :style="{ flexGrow: weight.value }" /></div>
        <ul class="hub-weight-legend">
          <li v-for="weight in weights" :key="weight.key"><i :class="`weight-${weight.key}`" />{{ weight.label }} <strong>{{ weight.value }}%</strong></li>
        </ul>
      </div>
      <ul class="hub-links">
        <li><a :href="url('tab=settings')" @click.prevent="emit('open', 'settings')"><Settings2 :size="17" /><span>Ajustes generales<small>Nombre, fechas y ponderaciones</small></span><ChevronRight :size="16" /></a></li>
        <template v-if="canEditRubrics">
          <li><button type="button" @click="emit('editRubric', 'team')" aria-describedby="hub-hint-editar-rbrica-tcnica"><FilePenLine :size="17" /><span>Editar rúbrica técnica<small id="hub-hint-editar-rbrica-tcnica" aria-hidden="true">Criterios de equipo</small></span><ChevronRight :size="16" /></button></li>
          <li><button type="button" @click="emit('editRubric', 'transversal')" aria-describedby="hub-hint-editar-rbrica-transversal"><FilePenLine :size="17" /><span>Editar rúbrica transversal<small id="hub-hint-editar-rbrica-transversal" aria-hidden="true">Competencias individuales</small></span><ChevronRight :size="16" /></button></li>
        </template>
      </ul>
    </article>
  </div>
</template>

<style scoped>
.hub-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.hub-card { position: relative; display: flex; flex-direction: column; min-width: 0; padding: 18px 20px 6px; background: #fff; border: 1px solid var(--border); border-radius: 14px; transition: border-color .15s, box-shadow .15s; }
.hub-card:hover { border-color: #dcc6d6; box-shadow: 0 10px 30px #4a0a3510; }
.hub-card-header { position: relative; display: grid; grid-template-columns: auto minmax(0, 1fr) auto; gap: 14px; align-items: start; }
.hub-icon { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 10px; background: #f7e4f0; color: var(--green); }
.hub-card h2 { font-size: 17px; letter-spacing: -.35px; line-height: 1.3; }
.hub-title::after { content: ""; position: absolute; inset: -6px; border-radius: 10px; }
.hub-title:focus-visible { outline: none; }
.hub-title:focus-visible::after { outline: 3px solid #b0418f; outline-offset: 2px; }
.hub-card-header p { margin-top: 1px; color: var(--muted); font-size: 12px; line-height: 1.5; }
.hub-arrow { color: #b8a6b3; transition: transform .15s, color .15s; }
.hub-card-header:hover .hub-arrow { color: var(--green); transform: translate(2px, -2px); }

.hub-figures { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px 22px; margin: 14px 0 8px; color: var(--muted); font-size: 12px; }
.hub-figures strong { margin-right: 3px; color: #2b1f28; font-size: 21px; font-weight: 600; letter-spacing: -.6px; }
.hub-flag { padding: 3px 9px; border-radius: 99px; background: #eef4ef; color: #376c49; font-size: 11px; font-weight: 500; }
.hub-flag.warning { background: #faf3df; color: #8a6620; }
.hub-teams { flex: 1 1 auto; height: 0; min-height: 72px; margin: 0 0 8px; padding: 0; overflow: hidden; list-style: none; }
.hub-teams.is-clipped { -webkit-mask-image: linear-gradient(to bottom, #000 calc(100% - 46px), transparent); mask-image: linear-gradient(to bottom, #000 calc(100% - 46px), transparent); }
.hub-teams.is-clipped:focus-visible { overflow-y: auto; -webkit-mask-image: none; mask-image: none; }
.hub-teams li { display: grid; grid-template-columns: minmax(0, 1fr) auto 26px; align-items: center; gap: 12px; padding: 5px 0; border-bottom: 1px dashed #efe7ed; color: #3b2a36; font-size: 13px; }
.hub-teams li:last-child { border-bottom: 0; }
.hub-team-name { overflow: hidden; font-weight: 500; text-overflow: ellipsis; white-space: nowrap; }
.hub-avatars { display: flex; }
.hub-avatars i { display: grid; place-items: center; width: 26px; height: 26px; margin-left: -6px; border: 2px solid #fff; border-radius: 50%; background: #f3e5ef; color: #82005e; font-size: 9px; font-style: normal; font-weight: 600; }
.hub-avatars i:first-child { margin-left: 0; }
.hub-avatars i:nth-child(2n) { background: #ead2e3; }
.hub-teams b { color: var(--muted); font-size: 12px; font-weight: 500; text-align: right; }
.hub-empty { flex: 1; margin: 12px 0 10px; color: var(--muted); font-size: 12px; }

.hub-notes { margin: 0 0 10px; padding: 0; list-style: none; }
.hub-notes li { padding: 6px 0 6px 14px; border-left: 2px solid #ecdbe7; }
.hub-notes li + li { margin-top: 2px; }
.hub-notes li:first-child { border-left-color: #82005e; }
.hub-notes p { display: flex; justify-content: space-between; gap: 12px; font-size: 12px; line-height: 1.4; }
.hub-notes strong { overflow: hidden; color: #2b1f28; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
.hub-notes time { flex-shrink: 0; color: var(--muted); font-size: 11px; }
.hub-notes blockquote { display: -webkit-box; margin: 4px 0 2px; overflow: hidden; color: #4d3a47; font-size: 12px; line-height: 1.55; -webkit-box-orient: vertical; -webkit-line-clamp: 1; }
.hub-notes small { color: var(--muted); font-size: 11px; }

.hub-weights { margin: 14px 0 12px; }
.hub-weights > p { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 9px; color: #4d3a47; font-size: 12px; font-weight: 500; }
.hub-weights > p span { color: var(--muted); font-weight: 400; }
.hub-weight-bar { display: flex; gap: 3px; height: 10px; }
.hub-weight-bar i { flex-basis: 0; border-radius: 3px; }
.weight-transversal { background: #e3bfd8; }
.weight-challenge { background: #82005e; }
.weight-exam { background: #b0418f; }
.hub-weight-legend { display: flex; flex-wrap: wrap; gap: 6px 18px; margin: 10px 0 0; padding: 0; list-style: none; color: var(--muted); font-size: 11px; }
.hub-weight-legend li { display: inline-flex; align-items: center; gap: 6px; }
.hub-weight-legend i { width: 8px; height: 8px; border-radius: 2px; }
.hub-weight-legend strong { color: #2b1f28; font-weight: 600; }

.hub-links { margin: auto 0 0; padding: 4px 0 0; border-top: 1px solid #f0eaee; list-style: none; }
.hub-links li + li { border-top: 1px solid #f5f0f3; }
.hub-links a, .hub-links button { display: flex; align-items: center; gap: 12px; width: 100%; min-height: 44px; margin: 0 -10px; padding: 5px 10px; border-radius: 8px; color: #3b2a36; font-size: 13px; font-weight: 500; text-align: left; box-sizing: content-box; }
.hub-links a > svg:first-child, .hub-links button > svg:first-child { flex-shrink: 0; color: #a07a95; }
.hub-links span { display: flex; flex: 1; flex-direction: column; min-width: 0; }
.hub-links small { color: var(--muted); font-size: 11px; font-weight: 400; }
.hub-links a > svg:last-child, .hub-links button > svg:last-child { flex-shrink: 0; color: #c9b8c4; transition: transform .15s, color .15s; }
.hub-links a:hover, .hub-links button:hover { background: #faf4f8; }
.hub-links a:hover > svg:last-child, .hub-links button:hover > svg:last-child { color: var(--green); transform: translateX(2px); }

.hub-card-primary { background: #82005e; border-color: #82005e; color: #fff; }
.hub-card-primary:hover { border-color: #6f0050; box-shadow: 0 14px 34px #82005e33; }
.hub-card-primary .hub-icon { background: #ffffff1f; color: #fff; }
.hub-card-primary .hub-card-header p, .hub-card-primary .hub-links small { color: #ecc9e1; }
.hub-card-primary .hub-arrow { color: #e0a8cf; }
.hub-card-primary .hub-card-header:hover .hub-arrow { color: #fff; }
.hub-card-primary .hub-title:focus-visible::after { outline-color: #fff; }
.hub-progress { margin: 14px 0 12px; }
.hub-progress p { color: #f3dcec; font-size: 12px; }
.hub-progress strong { margin-right: 3px; color: #fff; font-size: 21px; font-weight: 600; letter-spacing: -.6px; }
.hub-progress-track { height: 6px; margin-top: 10px; overflow: hidden; border-radius: 99px; background: #ffffff2b; }
.hub-progress-track i { display: block; height: 100%; border-radius: inherit; background: #fff; }
.hub-progress .hub-issues { margin-top: 10px; color: #ffd9a8; font-size: 11px; }
.hub-card-primary .hub-links { border-top-color: #ffffff26; }
.hub-card-primary .hub-links li + li { border-top-color: #ffffff17; }
.hub-card-primary .hub-links a, .hub-card-primary .hub-links button { color: #fff; }
.hub-card-primary .hub-links a > svg:first-child, .hub-card-primary .hub-links button > svg:first-child { color: #e9b9da; }
.hub-card-primary .hub-links a > svg:last-child, .hub-card-primary .hub-links button > svg:last-child { color: #d08fbd; }
.hub-card-primary .hub-links a:hover, .hub-card-primary .hub-links button:hover { background: #ffffff14; }
.hub-card-primary .hub-links a:hover > svg:last-child, .hub-card-primary .hub-links button:hover > svg:last-child { color: #fff; }
.hub-card-primary :focus-visible { outline-color: #fff; }

@media (max-width: 1000px) {
  .hub-grid { grid-template-columns: minmax(0, 1fr); }
  .hub-teams { flex: none; height: auto; max-height: 176px; }
}
@media (max-height: 860px) and (min-width: 1001px) {
  .hub-card-header p { display: none; }
  .hub-card-header { align-items: center; }
  .hub-links a, .hub-links button { min-height: 38px; padding: 3px 10px; }
  .hub-figures, .hub-progress, .hub-weights { margin-top: 10px; }
}
@media (max-width: 650px) {
  .hub-card { padding: 20px 18px 10px; }
  .hub-figures strong, .hub-progress strong { font-size: 21px; }
}
@media (prefers-reduced-motion: reduce) {
  .hub-card, .hub-arrow, .hub-links svg { transition: none; }
}
</style>
