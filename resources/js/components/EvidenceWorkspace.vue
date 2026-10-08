<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUpRight, Check, ChevronRight, ClipboardList, Frown, LockKeyhole, Meh, Plus, Search, Smile, Users, X } from 'lucide-vue-next';

type Student = { id: number; name: string; team_name: string | null };
type Sentiment = 'positive' | 'neutral' | 'negative';
type Evidence = {
  id: number;
  student_id: number;
  student_name: string;
  team_name: string | null;
  author_name: string;
  note: string;
  sentiment: Sentiment;
  created_at: string;
};

const sentiments: { value: Sentiment; label: string; icon: typeof Smile }[] = [
  { value: 'positive', label: 'Positiva', icon: Smile },
  { value: 'neutral', label: 'Neutra', icon: Meh },
  { value: 'negative', label: 'Negativa', icon: Frown },
];
const sentimentOf = (value: Sentiment) => sentiments.find(option => option.value === value) ?? sentiments[1];

const props = defineProps<{
  challenge: { id: number; name: string };
  students: Student[];
  evidences: Evidence[];
  canAdd: boolean;
  storeUrl: string;
}>();

const emit = defineEmits<{ saving: [value: boolean]; teams: [] }>();

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
const sentiment = ref<Sentiment>('neutral');
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
  sentiment.value = 'neutral';
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
  router.post(props.storeUrl, {
    student_id: studentId,
    note: note.value.trim(),
    sentiment: sentiment.value,
  }, {
    preserveScroll: true,
    onStart: () => { saving.value = true; emit('saving', true); error.value = ''; saved.value = false; },
    onSuccess: () => { drafts.value[studentId] = ''; sentiment.value = 'neutral'; saved.value = true; },
    onError: (errors) => { error.value = errors.student_id || errors.note || errors.sentiment || 'No se ha podido guardar la anotación.'; },
    onFinish: () => { saving.value = false; emit('saving', false); },
  });
}
function moveSentiment(event: KeyboardEvent): void {
  const step = ['ArrowRight', 'ArrowDown'].includes(event.key) ? 1 : ['ArrowLeft', 'ArrowUp'].includes(event.key) ? -1 : 0;
  if (!step) {
    return;
  }
  event.preventDefault();
  const index = sentiments.findIndex(option => option.value === sentiment.value);
  sentiment.value = sentiments[(index + step + sentiments.length) % sentiments.length].value;
  nextTick(() => document.getElementById(`evidence-sentiment-${sentiment.value}`)?.focus());
}
</script>

<template>
  <div>
    <header class="page-heading evidence-heading">
      <div>
        <h2>Evidencias</h2>
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
                <header><strong>{{ evidence.author_name }}</strong><span class="evidence-sentiment-tag" :class="evidence.sentiment"><component :is="sentimentOf(evidence.sentiment).icon" :size="14" aria-hidden="true" />{{ sentimentOf(evidence.sentiment).label }}</span><time :datetime="evidence.created_at">{{ formattedDate(evidence.created_at) }}</time></header>
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
            <div class="evidence-composer-actions">
              <div class="evidence-sentiment" role="radiogroup" aria-label="Valoración de la anotación" @keydown="moveSentiment">
                <button v-for="option in sentiments" :id="`evidence-sentiment-${option.value}`" :key="option.value" type="button" role="radio" :class="option.value" :aria-checked="sentiment === option.value" :tabindex="sentiment === option.value ? 0 : -1" :aria-label="option.label" :title="option.label" :disabled="saving" @click="sentiment = option.value"><component :is="option.icon" :size="20" aria-hidden="true" /></button>
              </div>
              <button class="button primary" type="submit" :disabled="saving || !note.trim()"><ArrowUpRight :size="16" />{{ saving ? 'Guardando…' : 'Guardar anotación' }}</button>
            </div>
          </div>
        </form>
      </section>
    </div>
    <div v-else class="evidence-empty-history evidence-no-students"><span><Users :size="26" /></span><h2>No hay estudiantes en este reto</h2><p>Añade participantes desde la gestión de equipos para comenzar su seguimiento.</p><button type="button" class="button" @click="emit('teams')">Estudiantes y Equipos<ArrowUpRight :size="16" /></button></div>
  </div>
</template>

<style scoped>
.evidence-topline > span { min-width: 0; overflow-wrap: anywhere; text-align: right; }
.evidence-back { display: inline-flex; align-items: center; gap: 7px; flex-shrink: 0; }
.evidence-heading { gap: 20px; }
.evidence-summary { display: flex; flex-wrap: wrap; gap: 10px 20px; color: #71666e; font-size: 12px; }
.evidence-summary > span { display: inline-flex; align-items: center; gap: 7px; }
.evidence-summary strong { color: var(--green); font-weight: 600; }
.evidence-readonly { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding: 12px 16px; border: 1px solid #ecdfe8; border-radius: 8px; background: #f4e6f0; color: #89266d; font-size: 12px; }
.evidence-readonly svg { flex-shrink: 0; }
.evidence-workspace { display: grid; grid-template-columns: minmax(260px, 310px) minmax(0, 1fr); align-items: start; gap: 24px; }
.evidence-roster, .evidence-detail { min-width: 0; border: 1px solid var(--border); border-radius: 12px; background: #fff; box-shadow: 0 3px 14px #82005e04; }
.evidence-roster { position: sticky; top: 24px; }
.evidence-roster-tools { padding: 20px 16px 14px; border-bottom: 1px solid var(--border); }
.evidence-section-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.evidence-section-heading h2, .evidence-section-heading h3 { font-size: 14px; letter-spacing: -.2px; }
.evidence-section-heading > span { color: #827a80; font-size: 11px; }
.evidence-search { display: flex; flex-direction: row; align-items: center; gap: 8px; margin: 16px 0 12px; padding: 0 10px; border: 1px solid #e2dce0; border-radius: 7px; background: #fbf9fa; color: #877d84; }
.evidence-search:focus-within { outline: 2px solid #b12589; outline-offset: 2px; }
.evidence-search svg { flex-shrink: 0; }
.evidence-search input { padding: 11px 0; border: 0; background: transparent; font-size: 12px; outline: none; }
.evidence-search input::-webkit-search-cancel-button { display: none; }
.evidence-search button { display: grid; place-items: center; padding: 5px; }
.evidence-filters { display: flex; gap: 5px; }
.evidence-filters button { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 6px; color: #73676f; font-size: 11px; }
.evidence-filters button[aria-pressed=true] { background: #f4e6f0; color: #5a334f; font-weight: 600; }
.evidence-filters button span { font-size: 10px; }
.evidence-students { list-style: none; margin: 0; padding: 7px; max-height: min(640px, 65vh); overflow-y: auto; scrollbar-width: thin; scrollbar-color: #d8c6d3 transparent; }
.evidence-student-option { display: flex; align-items: center; gap: 10px; width: 100%; min-height: 70px; padding: 12px 10px; border: 1px solid transparent; border-radius: 8px; text-align: left; }
.evidence-student-option:hover { background: #f8f3f7; }
.evidence-student-option[aria-pressed=true] { background: #f4e6f0; border-color: #e4ccdd; }
.evidence-avatar { display: grid; place-items: center; width: 34px; height: 34px; flex-shrink: 0; border: 1px solid #ebe0e8; border-radius: 50%; background: #f6f0f4; color: #705d6b; font-size: 11px; font-weight: 600; }
.evidence-student-option[aria-pressed=true] .evidence-avatar { background: #fff; border-color: #e4ccdd; color: #5a334f; }
.evidence-student-name { flex: 1; min-width: 0; }
.evidence-student-name strong { display: block; color: #4a2a41; font-size: 12px; font-weight: 600; overflow-wrap: anywhere; }
.evidence-student-name small { display: block; margin-top: 3px; color: #867e84; font-size: 11px; overflow-wrap: anywhere; }
.evidence-student-count { display: grid; place-items: center; min-width: 24px; height: 24px; padding: 0 5px; flex-shrink: 0; border-radius: 6px; color: #948e93; font-size: 11px; font-variant-numeric: tabular-nums; }
.evidence-student-count.has-notes { background: #f0deeb; color: #871667; font-weight: 600; }
.evidence-student-arrow { flex-shrink: 0; color: #9f8e9a; }
.evidence-no-matches { display: grid; justify-items: center; gap: 12px; padding: 36px 22px; color: #7e727b; text-align: center; font-size: 12px; }
.evidence-no-matches button { color: var(--green); text-decoration: underline; }
.evidence-mobile-back { display: none; }
.evidence-detail-header { display: flex; align-items: center; gap: 14px; padding: 25px 28px; border-bottom: 1px solid var(--border); }
.evidence-avatar-large { width: 48px; height: 48px; border-radius: 12px; background: #f2e1ed; border-color: #eddee9; color: #991774; font-size: 16px; }
.evidence-detail-identity { flex: 1; min-width: 0; }
.evidence-detail-identity h2 { font-size: 21px; overflow-wrap: anywhere; }
.evidence-detail-identity p { margin-top: 4px; color: #81747d; font-size: 12px; overflow-wrap: anywhere; }
.evidence-add { flex-shrink: 0; }
.evidence-composer-actions { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.evidence-sentiment { display: flex; gap: 2px; padding: 3px; border: 1px solid #e8dde5; border-radius: 10px; background: #fff; }
.evidence-sentiment button { display: grid; place-items: center; width: 36px; height: 34px; border-radius: 7px; color: #a8979f; transition: background .15s, color .15s; }
.evidence-sentiment button:hover:not(:disabled) { background: #f6f0f4; color: #4d3a47; }
.evidence-sentiment button.positive[aria-checked=true] { background: #e3f1e6; color: #2f7347; }
.evidence-sentiment button.neutral[aria-checked=true] { background: #efeaee; color: #5d4f59; }
.evidence-sentiment button.negative[aria-checked=true] { background: #fbe6e3; color: #ad3a31; }
.evidence-sentiment-tag { display: inline-flex; align-items: center; gap: 4px; margin-right: auto; padding: 2px 8px; border-radius: 99px; font-size: 11px; font-weight: 500; }
.evidence-sentiment-tag.positive { background: #e3f1e6; color: #2f7347; }
.evidence-sentiment-tag.neutral { background: #efeaee; color: #5d4f59; }
.evidence-sentiment-tag.negative { background: #fbe6e3; color: #ad3a31; }
.evidence-history { padding: 24px 28px; }
.evidence-history-count { display: inline-block; margin-left: 5px; color: #826d7c; font-size: 12px; font-weight: 400; }
.evidence-timeline { list-style: none; margin: 22px 0 0; padding: 0 0 0 20px; border-left: 1px solid #ecdfe8; }
.evidence-timeline > li { position: relative; padding-bottom: 16px; }
.evidence-timeline > li:last-child { padding-bottom: 0; }
.evidence-timeline > li::before { content: ''; position: absolute; left: -25px; top: 20px; width: 9px; height: 9px; border-radius: 50%; background: #b3238a; box-shadow: 0 0 0 4px #fff; }
.evidence-entry { padding: 16px 18px; border: 1px solid #ece0e8; border-radius: 9px; background: #fdfbfc; }
.evidence-entry header { display: flex; align-items: baseline; justify-content: space-between; gap: 6px 14px; flex-wrap: wrap; }
.evidence-entry header strong { font-size: 12px; font-weight: 600; overflow-wrap: anywhere; }
.evidence-entry time { color: #847881; font-size: 11px; }
.evidence-entry p { margin-top: 10px; font-size: 13px; line-height: 1.75; color: #57314c; white-space: pre-wrap; overflow-wrap: anywhere; }
.evidence-empty-history { display: flex; flex-direction: column; align-items: center; gap: 9px; padding: 40px 18px; text-align: center; }
.evidence-empty-history > span { display: grid; place-items: center; width: 52px; height: 52px; margin-bottom: 6px; border: 1px solid #ecdfe8; border-radius: 14px; background: #f7eff5; color: #a92d86; }
.evidence-empty-history h3 { font-size: 15px; color: #852469; }
.evidence-empty-history p { max-width: 350px; font-size: 12px; color: #857580; }
.evidence-composer { padding: 22px 28px 25px; border-top: 1px solid var(--border); background: #fbf6fa; border-radius: 0 0 12px 12px; }
.evidence-composer label { display: flex; flex-direction: row; align-items: baseline; flex-wrap: wrap; justify-content: space-between; gap: 5px 12px; margin: 0 0 12px; color: #4e2c45; font-size: 13px; font-weight: 600; }
.evidence-composer label span { font-size: 11px; color: #806f7b; font-weight: 400; overflow-wrap: anywhere; }
.evidence-composer textarea { display: block; min-height: 110px; font-size: 13px; line-height: 1.7; padding: 12px 14px; }
.evidence-composer textarea::placeholder { color: #92848e; }
.evidence-composer-footer { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 14px; }
.evidence-composer-footer p { font-size: 11px; color: #7f717b; }
.evidence-composer-footer small { display: block; margin-top: 2px; color: #948890; font-size: 10px; font-variant-numeric: tabular-nums; }
.evidence-saved { display: inline-flex; align-items: center; gap: 5px; color: #9b1675; }
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
  .evidence-composer-actions { align-self: stretch; justify-content: space-between; }
}
@media (max-width: 1000px) {
  .evidence-workspace { grid-template-columns: minmax(0, 1fr); }
  .evidence-roster { position: static; }
  .evidence-students { max-height: none; }
  .evidence-detail { display: none; }
  .show-detail .evidence-roster { display: none; }
  .show-detail .evidence-detail { display: block; }
  .evidence-mobile-back { display: flex; margin: 18px 20px 0; padding: 4px 0; color: #a42e83; font-size: 12px; }
  .evidence-detail-header { flex-wrap: wrap; }
  .evidence-detail-identity h2 { font-size: 19px; }
  .evidence-avatar-large { width: 40px; height: 40px; font-size: 13px; }
  .evidence-add { margin-left: auto; }
  .evidence-history > .evidence-section-heading { align-items: flex-start; flex-direction: column; }
  .evidence-timeline { padding-left: 15px; }
  .evidence-timeline > li::before { left: -20px; }
  .evidence-entry { padding: 14px; }
  .evidence-composer-footer .button { flex: 1; }
}
</style>
