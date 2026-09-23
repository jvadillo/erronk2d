<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUpRight, Check, ChevronRight, ClipboardList, LockKeyhole, Plus, Search, Users, X } from 'lucide-vue-next';
import Layout from '../components/Layout.vue';

type Student = { id: number; name: string; team_name: string | null };
type Evidence = {
  id: number;
  student_id: number;
  student_name: string;
  team_name: string | null;
  author_name: string;
  note: string;
  created_at: string;
};

const props = defineProps<{
  challenge: { id: number; name: string };
  students: Student[];
  evidences: Evidence[];
  canAdd: boolean;
}>();

const studentsWithHistory = computed(() => {
  const students = new Map(props.students.map(student => [student.id, student]));
  for (const evidence of props.evidences) {
    if (!students.has(evidence.student_id)) {
      students.set(evidence.student_id, { id: evidence.student_id, name: evidence.student_name, team_name: evidence.team_name });
    }
  }
  return [...students.values()].sort((a, b) => a.name.localeCompare(b.name, 'es'));
});
const evidenceByStudent = computed(() => {
  const groups = new Map<number, Evidence[]>();
  for (const evidence of props.evidences) {
    const entries = groups.get(evidence.student_id) ?? [];
    entries.push(evidence);
    groups.set(evidence.student_id, entries);
  }
  return groups;
});
const search = ref('');
const onlyWithNotes = ref(false);
const selectedStudentId = ref<number | null>(studentsWithHistory.value[0]?.id ?? null);
const mobileDetail = ref(false);
const drafts = ref<Record<number, string>>({});
const saving = ref(false);
const error = ref('');
const saved = ref(false);
const noteInput = ref<HTMLTextAreaElement | null>(null);
const detailHeading = ref<HTMLHeadingElement | null>(null);
const selectedStudent = computed(() => studentsWithHistory.value.find(student => student.id === selectedStudentId.value));
const selectedEvidences = computed(() => evidenceByStudent.value.get(selectedStudentId.value ?? -1) ?? []);
const canWrite = computed(() => props.canAdd && props.students.some(student => student.id === selectedStudentId.value));
const note = computed({
  get: () => drafts.value[selectedStudentId.value ?? -1] ?? '',
  set: (value: string) => {
    if (selectedStudentId.value !== null) {
      drafts.value[selectedStudentId.value] = value;
      saved.value = false;
    }
  },
});
function normalized(value: string): string {
  return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
}
const filteredStudents = computed(() => {
  const query = normalized(search.value.trim());
  return studentsWithHistory.value.filter(student =>
    normalized(`${student.name} ${student.team_name ?? ''}`).includes(query)
    && (!onlyWithNotes.value || evidenceByStudent.value.has(student.id)),
  );
});
function initials(name: string): string {
  return name.trim().split(/\s+/).slice(0, 2).map(part => part[0]).join('').toLocaleUpperCase('es');
}
function formattedDate(value: string): string {
  return new Intl.DateTimeFormat('es-ES', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
async function selectStudent(student: Student): Promise<void> {
  selectedStudentId.value = student.id;
  mobileDetail.value = true;
  error.value = '';
  saved.value = false;
  await nextTick();
  if (window.matchMedia('(max-width: 1000px)').matches) {
    detailHeading.value?.focus();
  }
}
async function showStudents(): Promise<void> {
  mobileDetail.value = false;
  await nextTick();
  document.getElementById(`evidence-student-${selectedStudentId.value}`)?.focus();
}
function submit(): void {
  const studentId = selectedStudentId.value;
  if (studentId === null || !canWrite.value || !note.value.trim() || saving.value) {
    return;
  }
  router.post(`/challenges/${props.challenge.id}/evidence`, {
    student_id: studentId,
    note: note.value.trim(),
  }, {
    preserveScroll: true,
    onStart: () => { saving.value = true; error.value = ''; saved.value = false; },
    onSuccess: () => { drafts.value[studentId] = ''; saved.value = true; },
    onError: (errors) => { error.value = errors.student_id || errors.note || 'No se ha podido guardar la anotación.'; },
    onFinish: () => { saving.value = false; },
  });
}
</script>

<template>
  <Layout>
    <Head title="Evidencias" />
    <div class="topline evidence-topline">
      <Link :href="`/challenges/${challenge.id}`" class="evidence-back"><ArrowLeft :size="16" />Volver al reto</Link>
      <span>{{ challenge.name }}</span>
    </div>
    <header class="page-heading evidence-heading">
      <div>
        <h1>Evidencias</h1>
        <p class="muted">El seguimiento de cada estudiante, en un solo lugar.</p>
      </div>
      <div class="evidence-summary" aria-label="Resumen de evidencias">
        <span><Users :size="16" /><strong>{{ studentsWithHistory.length }}</strong> estudiantes</span>
        <span><ClipboardList :size="16" /><strong>{{ evidences.length }}</strong> {{ evidences.length === 1 ? 'anotación' : 'anotaciones' }}</span>
      </div>
    </header>
    <p v-if="!canAdd" class="evidence-readonly" role="status"><LockKeyhole :size="16" />Solo lectura. El reto o el curso académico está cerrado.</p>

    <div v-if="studentsWithHistory.length" class="evidence-workspace" :class="{ 'show-detail': mobileDetail }">
      <section class="evidence-roster" aria-labelledby="evidence-students-title">
        <div class="evidence-roster-tools">
          <div class="evidence-section-heading"><h2 id="evidence-students-title">Estudiantes</h2><span>{{ filteredStudents.length }} de {{ studentsWithHistory.length }}</span></div>
          <label class="evidence-search">
            <Search :size="17" aria-hidden="true" />
            <input v-model="search" type="search" aria-label="Buscar estudiante o equipo" placeholder="Buscar estudiante o equipo…" />
            <button v-if="search" type="button" aria-label="Limpiar búsqueda" @click="search = ''"><X :size="15" /></button>
          </label>
          <div class="evidence-filters" aria-label="Filtrar estudiantes">
            <button type="button" :aria-pressed="!onlyWithNotes" @click="onlyWithNotes = false">Todos</button>
            <button type="button" :aria-pressed="onlyWithNotes" @click="onlyWithNotes = true">Con anotaciones <span>{{ evidenceByStudent.size }}</span></button>
          </div>
        </div>
        <ul v-if="filteredStudents.length" class="evidence-students">
          <li v-for="student in filteredStudents" :key="student.id">
            <button :id="`evidence-student-${student.id}`" type="button" class="evidence-student-option" :aria-pressed="selectedStudentId === student.id" :disabled="saving" @click="selectStudent(student)">
              <span class="evidence-avatar" aria-hidden="true">{{ initials(student.name) }}</span>
              <span class="evidence-student-name"><strong>{{ student.name }}</strong><small>{{ student.team_name || 'Sin equipo' }}</small></span>
              <span class="evidence-student-count" :class="{ 'has-notes': evidenceByStudent.has(student.id) }" :aria-label="`${evidenceByStudent.get(student.id)?.length ?? 0} anotaciones`">{{ evidenceByStudent.get(student.id)?.length ?? 0 }}</span>
              <ChevronRight :size="15" class="evidence-student-arrow" aria-hidden="true" />
            </button>
          </li>
        </ul>
        <div v-else class="evidence-no-matches" role="status"><Search :size="22" /><p>No hay estudiantes que coincidan con estos filtros.</p><button type="button" @click="search = ''; onlyWithNotes = false">Mostrar todos</button></div>
      </section>

      <section v-if="selectedStudent" class="evidence-detail" aria-labelledby="evidence-detail-title">
        <button type="button" class="evidence-mobile-back evidence-back" @click="showStudents"><ArrowLeft :size="16" />Cambiar estudiante</button>
        <header class="evidence-detail-header">
          <span class="evidence-avatar evidence-avatar-large" aria-hidden="true">{{ initials(selectedStudent.name) }}</span>
          <div class="evidence-detail-identity"><h2 id="evidence-detail-title" ref="detailHeading" tabindex="-1">{{ selectedStudent.name }}</h2><p>{{ selectedStudent.team_name || 'Sin equipo asignado' }}<span v-if="!canWrite && canAdd"> · Ya no participa en el reto</span></p></div>
          <button v-if="canWrite" type="button" class="button evidence-add" @click="noteInput?.focus()"><Plus :size="16" />Anotar</button>
        </header>

        <div class="evidence-history">
          <div class="evidence-section-heading"><h3>Historial de anotaciones <span class="evidence-history-count">{{ selectedEvidences.length }}</span></h3><span v-if="selectedEvidences.length">Más recientes primero</span></div>
          <ol v-if="selectedEvidences.length" class="evidence-timeline" aria-label="Anotaciones del estudiante">
            <li v-for="evidence in selectedEvidences" :key="evidence.id">
              <article class="evidence-entry">
                <header><strong>{{ evidence.author_name }}</strong><time :datetime="evidence.created_at">{{ formattedDate(evidence.created_at) }}</time></header>
                <p>{{ evidence.note }}</p>
              </article>
            </li>
          </ol>
          <div v-else class="evidence-empty-history"><span><ClipboardList :size="25" /></span><h3>Todavía no hay anotaciones</h3><p>{{ canWrite ? 'Registra la primera observación sobre su trabajo en este reto.' : 'Este estudiante aún no tiene evidencias registradas en el reto.' }}</p></div>
        </div>

        <form v-if="canWrite" class="evidence-composer" @submit.prevent="submit">
          <label for="evidence-note">Nueva anotación <span>Para {{ selectedStudent.name }}</span></label>
          <textarea id="evidence-note" ref="noteInput" v-model="note" :disabled="saving" maxlength="2000" rows="4" required placeholder="¿Qué has observado? Describe avances, aportaciones o aspectos a mejorar…" :aria-invalid="!!error" :aria-describedby="error ? 'evidence-error evidence-note-hint' : 'evidence-note-hint'" />
          <p v-if="error" id="evidence-error" class="notice error" role="alert">{{ error }}</p>
          <div class="evidence-composer-footer">
            <p id="evidence-note-hint"><span v-if="saved" class="evidence-saved" role="status"><Check :size="14" />Anotación guardada</span><span v-else>Visible solo para el profesorado</span><small>{{ note.length }} / 2000</small></p>
            <button class="button primary" type="submit" :disabled="saving || !note.trim()"><ArrowUpRight :size="16" />{{ saving ? 'Guardando…' : 'Guardar anotación' }}</button>
          </div>
        </form>
      </section>
    </div>
    <div v-else class="evidence-empty-history evidence-no-students"><span><Users :size="26" /></span><h2>No hay estudiantes en este reto</h2><p>Añade participantes desde la gestión de equipos para comenzar su seguimiento.</p><Link :href="`/challenges/${challenge.id}`" class="button">Volver al reto<ArrowUpRight :size="16" /></Link></div>
  </Layout>
</template>

<style scoped>
.evidence-topline > span { min-width: 0; overflow-wrap: anywhere; text-align: right; }
.evidence-back { display: inline-flex; align-items: center; gap: 7px; flex-shrink: 0; }
.evidence-heading { gap: 20px; }
.evidence-summary { display: flex; flex-wrap: wrap; gap: 10px 20px; color: #64736c; font-size: 12px; }
.evidence-summary > span { display: inline-flex; align-items: center; gap: 7px; }
.evidence-summary strong { color: var(--green); font-weight: 600; }
.evidence-readonly { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding: 12px 16px; border: 1px solid #dce5d5; border-radius: 8px; background: #edf3e7; color: #526649; font-size: 12px; }
.evidence-readonly svg { flex-shrink: 0; }
.evidence-workspace { display: grid; grid-template-columns: minmax(260px, 310px) minmax(0, 1fr); align-items: start; gap: 24px; }
.evidence-roster, .evidence-detail { min-width: 0; border: 1px solid var(--border); border-radius: 12px; background: #fff; box-shadow: 0 3px 14px #153b3504; }
.evidence-roster { position: sticky; top: 24px; }
.evidence-roster-tools { padding: 20px 16px 14px; border-bottom: 1px solid var(--border); }
.evidence-section-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.evidence-section-heading h2, .evidence-section-heading h3 { font-size: 14px; letter-spacing: -.2px; }
.evidence-section-heading > span { color: #78847c; font-size: 11px; }
.evidence-search { display: flex; flex-direction: row; align-items: center; gap: 8px; margin: 16px 0 12px; padding: 0 10px; border: 1px solid #dce3db; border-radius: 7px; background: #fafbf9; color: #7b897d; }
.evidence-search:focus-within { outline: 2px solid #70a678; outline-offset: 2px; }
.evidence-search svg { flex-shrink: 0; }
.evidence-search input { padding: 11px 0; border: 0; background: transparent; font-size: 12px; outline: none; }
.evidence-search input::-webkit-search-cancel-button { display: none; }
.evidence-search button { display: grid; place-items: center; padding: 5px; }
.evidence-filters { display: flex; gap: 5px; }
.evidence-filters button { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 6px; color: #65756a; font-size: 11px; }
.evidence-filters button[aria-pressed=true] { background: #edf3e7; color: #375d3d; font-weight: 600; }
.evidence-filters button span { font-size: 10px; }
.evidence-students { list-style: none; margin: 0; padding: 7px; max-height: min(640px, 65vh); overflow-y: auto; scrollbar-width: thin; scrollbar-color: #ccd7c7 transparent; }
.evidence-student-option { display: flex; align-items: center; gap: 10px; width: 100%; min-height: 70px; padding: 12px 10px; border: 1px solid transparent; border-radius: 8px; text-align: left; }
.evidence-student-option:hover { background: #f6f8f3; }
.evidence-student-option[aria-pressed=true] { background: #edf3e7; border-color: #d7e3cd; }
.evidence-avatar { display: grid; place-items: center; width: 34px; height: 34px; flex-shrink: 0; border: 1px solid #e2e8de; border-radius: 50%; background: #f4f6f0; color: #627459; font-size: 11px; font-weight: 600; }
.evidence-student-option[aria-pressed=true] .evidence-avatar { background: #fff; border-color: #d7e3cd; color: #375d3d; }
.evidence-student-name { flex: 1; min-width: 0; }
.evidence-student-name strong { display: block; color: #32483c; font-size: 12px; font-weight: 600; overflow-wrap: anywhere; }
.evidence-student-name small { display: block; margin-top: 3px; color: #7c887e; font-size: 11px; overflow-wrap: anywhere; }
.evidence-student-count { display: grid; place-items: center; min-width: 24px; height: 24px; padding: 0 5px; flex-shrink: 0; border-radius: 6px; color: #8c968c; font-size: 11px; font-variant-numeric: tabular-nums; }
.evidence-student-count.has-notes { background: #e8efdf; color: #476538; font-weight: 600; }
.evidence-student-arrow { flex-shrink: 0; color: #95a28b; }
.evidence-no-matches { display: grid; justify-items: center; gap: 12px; padding: 36px 22px; color: #71816f; text-align: center; font-size: 12px; }
.evidence-no-matches button { color: var(--green); text-decoration: underline; }
.evidence-mobile-back { display: none; }
.evidence-detail-header { display: flex; align-items: center; gap: 14px; padding: 25px 28px; border-bottom: 1px solid var(--border); }
.evidence-avatar-large { width: 48px; height: 48px; border-radius: 12px; background: #eaf1e2; border-color: #dce6d3; color: #52723e; font-size: 16px; }
.evidence-detail-identity { flex: 1; min-width: 0; }
.evidence-detail-identity h2 { font-size: 21px; overflow-wrap: anywhere; }
.evidence-detail-identity p { margin-top: 4px; color: #778471; font-size: 12px; overflow-wrap: anywhere; }
.evidence-add { flex-shrink: 0; }
.evidence-history { padding: 24px 28px; }
.evidence-history-count { display: inline-block; margin-left: 5px; color: #748768; font-size: 12px; font-weight: 400; }
.evidence-timeline { list-style: none; margin: 22px 0 0; padding: 0 0 0 20px; border-left: 1px solid #dfe6d8; }
.evidence-timeline > li { position: relative; padding-bottom: 16px; }
.evidence-timeline > li:last-child { padding-bottom: 0; }
.evidence-timeline > li::before { content: ''; position: absolute; left: -25px; top: 20px; width: 9px; height: 9px; border-radius: 50%; background: #b1c49d; box-shadow: 0 0 0 4px #fff; }
.evidence-entry { padding: 16px 18px; border: 1px solid #e6ebe1; border-radius: 9px; background: #fcfdfb; }
.evidence-entry header { display: flex; align-items: baseline; justify-content: space-between; gap: 6px 14px; flex-wrap: wrap; }
.evidence-entry header strong { font-size: 12px; font-weight: 600; overflow-wrap: anywhere; }
.evidence-entry time { color: #7b8775; font-size: 11px; }
.evidence-entry p { margin-top: 10px; font-size: 13px; line-height: 1.75; color: #42513e; white-space: pre-wrap; overflow-wrap: anywhere; }
.evidence-empty-history { display: flex; flex-direction: column; align-items: center; gap: 9px; padding: 40px 18px; text-align: center; }
.evidence-empty-history > span { display: grid; place-items: center; width: 52px; height: 52px; margin-bottom: 6px; border: 1px solid #e2e9db; border-radius: 14px; background: #f4f7ef; color: #839a6e; }
.evidence-empty-history h3 { font-size: 15px; color: #516346; }
.evidence-empty-history p { max-width: 350px; font-size: 12px; color: #7a8872; }
.evidence-composer { padding: 22px 28px 25px; border-top: 1px solid var(--border); background: #f9fbf6; border-radius: 0 0 12px 12px; }
.evidence-composer label { display: flex; flex-direction: row; align-items: baseline; flex-wrap: wrap; justify-content: space-between; gap: 5px 12px; margin: 0 0 12px; color: #354c39; font-size: 13px; font-weight: 600; }
.evidence-composer label span { font-size: 11px; color: #75846b; font-weight: 400; overflow-wrap: anywhere; }
.evidence-composer textarea { display: block; min-height: 110px; font-size: 13px; line-height: 1.7; padding: 12px 14px; }
.evidence-composer textarea::placeholder { color: #8a9581; }
.evidence-composer-footer { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 14px; }
.evidence-composer-footer p { font-size: 11px; color: #75826e; }
.evidence-composer-footer small { display: block; margin-top: 2px; color: #8c9686; font-size: 10px; font-variant-numeric: tabular-nums; }
.evidence-saved { display: inline-flex; align-items: center; gap: 5px; color: #3d743e; }
.evidence-no-students { padding: 65px 22px; border: 1px dashed var(--border); border-radius: 12px; background: #fff; }
.evidence-no-students .button { margin-top: 12px; }
@media (max-width: 1100px) {
  .evidence-workspace { grid-template-columns: minmax(240px, 280px) minmax(0, 1fr); gap: 16px; }
  .evidence-heading { align-items: flex-start; flex-direction: column; gap: 16px; }
  .evidence-detail-header, .evidence-history, .evidence-composer { padding: 20px; }
  .evidence-detail-header { gap: 10px; }
  .evidence-detail-identity h2 { font-size: 18px; }
  .evidence-add { padding: 8px 10px; }
  .evidence-composer-footer { align-items: flex-start; flex-direction: column; }
  .evidence-composer-footer .button { align-self: flex-end; }
}
@media (max-width: 1000px) {
  .evidence-workspace { grid-template-columns: minmax(0, 1fr); }
  .evidence-roster { position: static; }
  .evidence-students { max-height: none; }
  .evidence-detail { display: none; }
  .show-detail .evidence-roster { display: none; }
  .show-detail .evidence-detail { display: block; }
  .evidence-mobile-back { display: flex; margin: 18px 20px 0; padding: 4px 0; color: #657a58; font-size: 12px; }
  .evidence-detail-header { flex-wrap: wrap; }
  .evidence-detail-identity h2 { font-size: 19px; }
  .evidence-avatar-large { width: 40px; height: 40px; font-size: 13px; }
  .evidence-add { margin-left: auto; }
  .evidence-history > .evidence-section-heading { align-items: flex-start; flex-direction: column; }
  .evidence-timeline { padding-left: 15px; }
  .evidence-timeline > li::before { left: -20px; }
  .evidence-entry { padding: 14px; }
  .evidence-composer-footer .button { width: 100%; }
}
</style>
