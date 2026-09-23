<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, Clock3, Plus, Search, UserRound } from 'lucide-vue-next';
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

const search = ref('');
const selectedStudentId = ref<number | null>(props.students[0]?.id ?? null);
const note = ref('');
const saving = ref(false);
const errors = computed(() => usePage().props.errors as Record<string, string>);

const filteredEvidences = computed(() => {
  const query = search.value.trim().toLocaleLowerCase('es');

  if (!query) {
    return props.evidences;
  }

  return props.evidences.filter((evidence) => [
    evidence.student_name,
    evidence.team_name,
    evidence.author_name,
    evidence.note,
  ].filter(Boolean).join(' ').toLocaleLowerCase('es').includes(query));
});

function formattedDate(value: string): string {
  return new Intl.DateTimeFormat('es-ES', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function submit(): void {
  if (selectedStudentId.value === null || !note.value.trim() || saving.value) {
    return;
  }

  router.post(`/challenges/${props.challenge.id}/evidence`, {
    student_id: selectedStudentId.value,
    note: note.value.trim(),
  }, {
    preserveScroll: true,
    onStart: () => { saving.value = true; },
    onSuccess: () => { note.value = ''; },
    onFinish: () => { saving.value = false; },
  });
}
</script>

<template>
  <Layout>
    <Head title="Evidencias" />
    <div class="topline">
      <Link :href="`/challenges/${challenge.id}`" class="back-link"><ArrowLeft :size="16" />Volver al reto</Link>
      <span>{{ challenge.name }}</span>
    </div>

    <header class="page-heading evidence-heading">
      <div>
        <p class="eyebrow">{{ challenge.name }}</p>
        <h1>Evidencias</h1>
        <p class="muted">Registra observaciones del trabajo de cada estudiante durante el reto.</p>
      </div>
      <span class="evidence-count"><ClipboardList :size="17" />{{ evidences.length }} {{ evidences.length === 1 ? 'anotación' : 'anotaciones' }}</span>
    </header>

    <p v-if="!canAdd" class="notice info" role="status">El reto o el curso académico está cerrado. Puedes consultar las evidencias en modo lectura.</p>
    <p v-if="errors.student_id || errors.note" class="notice error" role="alert">{{ errors.student_id || errors.note }}</p>

    <div class="evidence-workspace">
      <form v-if="canAdd && students.length" class="panel evidence-form" @submit.prevent="submit">
        <div class="evidence-form-heading">
          <span class="evidence-form-icon"><Plus :size="18" /></span>
          <div><h2>Añadir anotación</h2><p>La anotación quedará asociada al estudiante y al profesorado que la registra.</p></div>
        </div>
        <label for="evidence-student">Estudiante</label>
        <select id="evidence-student" v-model="selectedStudentId" required>
          <option v-for="student in students" :key="student.id" :value="student.id">
            {{ student.name }}{{ student.team_name ? ` · ${student.team_name}` : '' }}
          </option>
        </select>
        <label for="evidence-note">Observación</label>
        <textarea id="evidence-note" v-model="note" maxlength="2000" rows="5" required placeholder="Ej.: No está colaborando activamente en el grupo." />
        <div class="evidence-form-footer">
          <span>{{ note.length }} / 2000</span>
          <button class="button primary" type="submit" :disabled="saving || !note.trim()"><Plus :size="16" />{{ saving ? 'Guardando…' : 'Guardar evidencia' }}</button>
        </div>
      </form>
      <section v-else-if="canAdd" class="panel evidence-empty-form">
        <h2>No hay estudiantes en este reto</h2>
        <p>Cuando se asignen participantes podrás registrar anotaciones.</p>
      </section>

      <section class="evidence-list" aria-labelledby="evidence-list-title">
        <div class="evidence-list-heading">
          <div><h2 id="evidence-list-title">Anotaciones del reto</h2><p>Consulta las observaciones por estudiante, equipo o contenido.</p></div>
          <label class="search-box evidence-search"><Search :size="16" /><input v-model="search" aria-label="Buscar evidencias" placeholder="Buscar evidencia…" /></label>
        </div>

        <div v-if="filteredEvidences.length" class="evidence-cards">
          <article v-for="evidence in filteredEvidences" :key="evidence.id" class="evidence-card">
            <header>
              <div class="evidence-student"><span class="student-avatar"><UserRound :size="16" /></span><div><strong>{{ evidence.student_name }}</strong><small v-if="evidence.team_name">{{ evidence.team_name }}</small></div></div>
              <time :datetime="evidence.created_at"><Clock3 :size="14" />{{ formattedDate(evidence.created_at) }}</time>
            </header>
            <p>{{ evidence.note }}</p>
            <footer>Registrada por <strong>{{ evidence.author_name }}</strong></footer>
          </article>
        </div>
        <div v-else class="evidence-empty">
          <ClipboardList :size="23" />
          <p>{{ search ? 'No hay anotaciones que coincidan con la búsqueda.' : 'Todavía no se han registrado evidencias para este reto.' }}</p>
        </div>
      </section>
    </div>
  </Layout>
</template>

<style scoped>
.evidence-heading { align-items: flex-end; }
.evidence-count { display: inline-flex; align-items: center; gap: 8px; padding: 9px 13px; border: 1px solid var(--border); border-radius: 7px; background: #fff; color: #64735d; font-size: 11px; white-space: nowrap; }
.evidence-count svg { color: #78936b; }
.evidence-workspace { display: grid; grid-template-columns: minmax(260px, 0.78fr) minmax(0, 1.5fr); gap: 22px; align-items: start; }
.evidence-form { position: sticky; top: 22px; }
.evidence-form-heading { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 19px; }
.evidence-form-heading h2, .evidence-list-heading h2, .evidence-empty-form h2 { font-size: 16px; }
.evidence-form-heading p, .evidence-list-heading p, .evidence-empty-form p { color: var(--muted); font-size: 11px; margin-top: 4px; }
.evidence-form-icon { display: grid; place-items: center; width: 34px; height: 34px; flex: 0 0 auto; border-radius: 8px; background: #edf3e7; color: #527849; }
.evidence-form label { margin: 13px 0 6px; font-size: 11px; }
.evidence-form textarea { min-height: 125px; }
.evidence-form-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 15px; }
.evidence-form-footer > span { color: #89947f; font-size: 10px; }
.evidence-list { min-width: 0; }
.evidence-list-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 15px; margin: 0 0 14px; }
.evidence-search { min-width: 190px; max-width: 260px; background: #fff; }
.evidence-search input { border: 0 !important; padding: 9px 0 !important; font-size: 11px; }
.evidence-cards { display: grid; gap: 11px; }
.evidence-card { padding: 17px 19px; border: 1px solid var(--border); border-radius: 8px; background: #fff; }
.evidence-card header, .evidence-student, .evidence-card time { display: flex; align-items: center; }
.evidence-card header { justify-content: space-between; gap: 12px; }
.evidence-student { gap: 9px; min-width: 0; }
.evidence-student strong { display: block; font-size: 12px; font-weight: 600; }
.evidence-student small { display: block; color: #88947f; font-size: 10px; margin-top: 2px; }
.student-avatar { display: grid; place-items: center; width: 31px; height: 31px; flex: 0 0 auto; border-radius: 50%; background: #eef1e8; color: #78896b; }
.evidence-card time { gap: 5px; color: #89947f; font-size: 10px; white-space: nowrap; }
.evidence-card > p { margin: 14px 0; color: #39483c; font-size: 12px; overflow-wrap: anywhere; white-space: pre-wrap; }
.evidence-card footer { padding-top: 9px; border-top: 1px solid #edf0e9; color: #89947f; font-size: 10px; }
.evidence-card footer strong { color: #586e50; font-weight: 500; }
.evidence-empty, .evidence-empty-form { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 9px; min-height: 170px; text-align: center; color: #8b9782; }
.evidence-empty { padding: 25px; border: 1px dashed var(--border); border-radius: 8px; background: #fff; font-size: 11px; }
.evidence-empty-form { align-items: flex-start; justify-content: flex-start; min-height: 0; }
@media (max-width: 900px) { .evidence-workspace { grid-template-columns: minmax(220px, 0.85fr) minmax(0, 1.15fr); gap: 15px; } .evidence-list-heading { align-items: flex-start; flex-direction: column; } .evidence-search { max-width: none; width: 100%; } }
@media (max-width: 650px) { .evidence-heading { align-items: flex-start; } .evidence-count { align-self: flex-start; } .evidence-workspace { grid-template-columns: 1fr; } .evidence-form { position: static; } .evidence-list-heading { margin-top: 7px; } .evidence-card { padding: 14px; } .evidence-card header { align-items: flex-start; flex-direction: column; } }
</style>
