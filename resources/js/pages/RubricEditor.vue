<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUp, ArrowDown, Plus, Save, X } from 'lucide-vue-next';
import Layout from '../components/Layout.vue';
import Modal from '../components/Modal.vue';
import ModuleCriteriaNotice from '../components/ModuleCriteriaNotice.vue';
import { api } from '../lib';
import { commonScores, decimal, percentage, percentageWeights, type RubricItem, type RubricModule } from '../rubrics';
const props = defineProps<{
  rubric: { id: number | null; name: string; kind: string; cycle_id: number | null; level: number | null; items: RubricItem[]; shared_users: { id: number }[] } | null;
  cycles: { id: number; name: string }[];
  modules: RubricModule[];
  teachers: { id: number; name: string }[];
  saveUrl: string;
  libraryUrl: string;
  challengeContext?: { id: number; name: string; kind: 'team' | 'transversal'; cycle: string | null; level: number | null; revision: number; requiresReason: boolean; savesToLibrary: boolean; previewUrl: string };

}>();
const legacy = ref(!!props.rubric && !commonScores(props.rubric.items));
const initialItems: RubricItem[] = props.rubric ? JSON.parse(JSON.stringify(props.rubric.items)) : [];
const weights = percentageWeights(initialItems);
if (!props.challengeContext) initialItems.forEach((item, index) => { item.weight = weights[index]; });
const initialScores = initialItems.length
  ? Array.from({ length: Math.max(...initialItems.map(item => item.levels.length)) }, (_, index) => String(initialItems.find(item => item.levels[index])?.levels[index].score ?? '10'))
  : ['4', '6', '8', '10'];
function makeItem(scores: string[], weight = '0'): RubricItem {
  return { key: `c_${Array.from(crypto.getRandomValues(new Uint32Array(3)), value => value.toString(16)).join('')}`, name: '', description: '', module_id: null, weight, levels: scores.map(score => ({ score, description: '', ...(props.challengeContext ? { source_index: null } : {}) })) };
}
const form = useForm(props.challengeContext ? `ChallengeRubric:${props.challengeContext.id}:${props.challengeContext.kind}:${props.challengeContext.revision}` : `RubricEditor:${props.rubric?.id ?? 'new'}`, {
  reason: '',
  id: props.rubric?.id ?? null,
  name: props.rubric?.name ?? '',
  kind: props.rubric?.kind ?? 'team',
  cycle_id: props.rubric?.cycle_id ?? '' as number | string,
  level: props.rubric?.level ?? '' as number | string,
  shared_user_ids: props.rubric?.shared_users.map(user => user.id) ?? [],
  scores: initialScores,
  items: initialItems.length ? initialItems : [makeItem(initialScores, '100')],
});
const availableModules = computed(() => props.challengeContext ? props.modules : props.modules.filter(module => module.cycle_id === Number(form.cycle_id) && module.level === Number(form.level)));
const totalCents = computed(() => form.items.reduce((sum, item) => sum + Math.round((decimal(item.weight) || 0) * 100), 0));
const weightMessage = computed(() => {
  if (originalRelativeWeights.value) return `Pesos relativos originales · Total: ${percentage(totalCents.value / 100)}`;
  const difference = 10000 - totalCents.value;
  return difference === 0 ? 'Total: 100 %' : difference > 0 ? `Total: ${percentage(totalCents.value / 100)} % · Faltan ${percentage(difference / 100)} %` : `Total: ${percentage(totalCents.value / 100)} % · Sobran ${percentage(-difference / 100)} %`;
});
const fieldError = (key: string) => (form.errors as Record<string, string>)[key];
watch(() => [form.kind, form.cycle_id, form.level], () => {
  if (props.challengeContext) return;
  const ids = availableModules.value.map(module => module.id);
  form.items.forEach(item => { if (form.kind === 'transversal' || !ids.includes(Number(item.module_id))) item.module_id = null; });
});
function addLevel() {
  if (form.scores.length >= 20) return;
  form.scores.push('10');
  form.items.forEach(item => item.levels.push({ score: '10', description: '', ...(props.challengeContext ? { source_index: null } : {}) }));
}
function canRemoveLevel(index: number): boolean {
  return form.scores.length > 2 && form.items.every(item => !item.levels[index] || item.levels.length > 2);
}
function removeLevel(index: number) {
  const warning = props.challengeContext ? ' Las valoraciones que usen este nivel se eliminarán al confirmar el guardado. Las demás se conservarán.' : '';
  if (!canRemoveLevel(index) || !confirm(`¿Quitar el nivel ${index + 1} y sus descripciones de todos los criterios?${warning}`)) return;
  form.scores.splice(index, 1);
  form.items.forEach(item => item.levels.splice(index, 1));
}
function removeItem(index: number) {
  if (form.items.length <= 1) return;
  const item = form.items[index];
  if ((item.name || item.description || item.levels.some(level => level.description)) && !confirm(props.challengeContext ? '¿Eliminar este criterio? Sus valoraciones se eliminarán al confirmar el guardado. Las demás se conservarán.' : '¿Eliminar este criterio?')) return;
  form.items.splice(index, 1);
}
function move(index: number, by: number) {
  const target = index + by;
  if (target < 0 || target >= form.items.length) return;
  [form.items[index], form.items[target]] = [form.items[target], form.items[index]];
}
function unifyLegacy() {
  form.items.forEach(item => { item.levels = form.scores.map((score, index) => ({ score, description: item.levels[index]?.description ?? '' })); });
  legacy.value = false;
}
function distributeWeights() {
  const values = percentageWeights(form.items.map(item => ({ ...item, weight: '1' })));
  form.items.forEach((item, index) => { item.weight = values[index]; });
}
function submit() {
  if (props.challengeContext) { previewChange(); return; }
  if (legacy.value) return;
  form.transform(data => ({
    id: data.id, name: data.name, kind: data.kind, cycle_id: data.cycle_id || null, level: data.level || null, shared_user_ids: data.shared_user_ids,
    items: data.items.map(item => ({ ...item, module_id: data.kind === 'transversal' ? null : item.module_id, weight: String(item.weight).replace(',', '.'), levels: data.scores.map((score, index) => ({ score: score.replace(',', '.'), description: item.levels[index]?.description ?? '' })) })),
  })).post(props.saveUrl, { onSuccess: () => form.defaults() });
}
type RubricImpact = { removed_assessments: number; removed_by_kind: Record<string, number>; affected_subjects: string[]; removed_criteria: string[]; changed_team_grades: string[]; invalid_allocations: string[]; changed_results: string[] };
const impact = ref<RubricImpact | null>(null), submitting = ref(false), changeError = ref('');
const pendingChange = ref<Record<string, unknown> | null>(null);
const legacyWeights = computed(() => !!props.challengeContext && initialItems.length > 0 && initialItems.reduce((sum, item) => sum + decimal(item.weight), 0) !== 100);
const originalRelativeWeights = computed(() => legacyWeights.value && form.items.length === initialItems.length && form.items.every(item => { const original = initialItems.find(candidate => candidate.key === item.key); return original && decimal(item.weight) === decimal(original.weight); }));
const submitDisabled = computed(() => form.processing || submitting.value || (legacy.value && !props.challengeContext));
const saveLabel = computed(() => props.challengeContext ? 'Guardar cambios' : 'Guardar rúbrica');
const kindLabels: Record<string, string> = { team: 'equipo', teacher: 'docentes', self: 'autoevaluaciones', peer: 'coevaluaciones' };
watch(() => [form.name, form.items, form.scores, form.reason], () => { impact.value = null; pendingChange.value = null; }, { deep: true });
async function previewChange() {
  const context = props.challengeContext;
  if (!context || submitting.value) return;
  submitting.value = true; changeError.value = ''; impact.value = null;
  const input = {
    action: 'rubric', kind: context.kind, revision: context.revision, reason: form.reason || null,
    rubric: { name: form.name, items: form.items.map(item => ({
      ...item, module_id: context.kind === 'transversal' ? null : item.module_id,
      weight: String(item.weight).replace(',', '.'),
      levels: item.levels.map((level, index) => ({ source_index: level.source_index ?? null, description: level.description, score: String(legacy.value ? level.score : form.scores[index]).replace(',', '.') })),
    })) },
  };
  try {
    const result = await api(context.previewUrl, input);
    pendingChange.value = { ...input, preview_token: result.token };
    impact.value = result.impact;
  } catch (error) { changeError.value = (error as Error).message; }
  finally { submitting.value = false; }
}
async function confirmChange() {
  if (!pendingChange.value || submitting.value) return;
  submitting.value = true; changeError.value = '';
  try {
    await api(props.saveUrl, pendingChange.value);
    impact.value = null; pendingChange.value = null; form.defaults();
    router.visit(props.libraryUrl, { preserveState: false });
  } catch (error) {
    changeError.value = (error as Error).message;
    impact.value = null; pendingChange.value = null;
  } finally { submitting.value = false; }
}
let removeNavigationGuard: (() => void) | undefined;
function beforeUnload(event: BeforeUnloadEvent) { if (form.isDirty && !form.processing && !submitting.value) { event.preventDefault(); event.returnValue = ''; } }
onMounted(() => {
  window.addEventListener('beforeunload', beforeUnload);
  removeNavigationGuard = router.on('before', event => {
    if (event.detail.visit.method === 'get' && form.isDirty && !form.processing && !submitting.value && !confirm('Hay cambios sin guardar. ¿Salir del editor?')) event.preventDefault();
  });
});
onBeforeUnmount(() => { window.removeEventListener('beforeunload', beforeUnload); removeNavigationGuard?.(); });
</script>

<template>
  <Layout>
    <Head :title="rubric ? 'Editar rúbrica' : 'Crear rúbrica'"/>
    <div class="topline"><Link :href="libraryUrl" class="back-link"><ArrowLeft :size="16"/>{{ challengeContext ? 'Volver a la evaluación' : 'Biblioteca de rúbricas' }}</Link></div>
    <form class="rubric-workspace" @submit.prevent="submit">
      <header class="page-heading"><div><h1>{{ rubric ? 'Editar rúbrica' : 'Crear rúbrica' }}</h1><p class="muted">Un criterio por fila. Una nota común por columna.</p></div><button class="button primary" type="submit" :disabled="submitDisabled"><Save :size="17"/>{{ form.processing || submitting ? 'Procesando…' : saveLabel }}</button></header>
      <div v-if="challengeContext" class="notice warning">Estás editando la rúbrica de este reto para todos sus equipos o estudiantes. Al guardar, se eliminarán solo las valoraciones de criterios o niveles retirados. Los cambios de pesos o puntuaciones pueden modificar notas y repartos. Revisa también las valoraciones si cambias el significado de un criterio.</div>
      <div v-if="changeError" class="notice error" role="alert">{{ changeError }}</div>
      <fieldset class="rubric-editor-fields" :disabled="submitting">
      <section class="panel rubric-settings">
        <label>Nombre de la rúbrica<input v-model="form.name" required maxlength="150"/><span v-if="form.errors.name" class="field-error">{{ form.errors.name }}</span></label>
        <label>Tipo<select v-model="form.kind" :disabled="!!challengeContext"><option value="team">Valoración del reto</option><option value="transversal">Competencias transversales</option></select></label>
        <label v-if="challengeContext">Ciclo<input :value="challengeContext.cycle || 'General'" disabled/></label>
        <label v-else>Ciclo<select v-model="form.cycle_id"><option value="">General</option><option v-for="cycle in cycles" :key="cycle.id" :value="cycle.id">{{ cycle.name }}</option></select><span v-if="form.errors.cycle_id" class="field-error">{{ form.errors.cycle_id }}</span></label>
        <label v-if="challengeContext">Curso / nivel<input :value="challengeContext.level ? `${challengeContext.level}.º` : 'General'" disabled/></label>
        <label v-else>Curso / nivel<select v-model="form.level"><option value="">General</option><option v-for="n in 4" :key="n" :value="n">{{ n }}.º</option></select></label>
      </section>
      <ModuleCriteriaNotice v-if="challengeContext && form.kind === 'team'" :items="form.items" :modules="availableModules"/>
      <div v-if="legacy && !challengeContext" class="notice warning"><p>Esta plantilla tiene niveles diferentes entre criterios. Revisa las notas de las columnas y unifica los niveles para editarla en tabla. Las evaluaciones de retos anteriores se conservan.</p><button class="button" type="button" @click="unifyLegacy">Unificar niveles de la plantilla</button></div>
      <p v-if="challengeContext && legacy" class="notice">Esta rúbrica tiene niveles distintos entre criterios. Se conservan sus notas originales; puedes editarlas en cada celda.</p>
      <p v-if="legacyWeights" class="notice">Esta rúbrica usa pesos relativos anteriores. Puedes conservarlos. Si añades o eliminas criterios o cambias sus pesos, ajusta el total al 100 %.</p>
      <label v-if="challengeContext?.requiresReason">Motivo de la corrección<textarea v-model="form.reason" required minlength="5" maxlength="2000"/></label>
      <div v-if="form.hasErrors" class="notice error" role="alert"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
      <div class="rubric-toolbar"><div><h2>Criterios y niveles</h2><p class="helper">{{ legacyWeights ? 'Conserva los pesos originales o ajusta el total al 100 %.' : 'Pesos en porcentaje, con hasta dos decimales. Todos deben sumar 100 %.' }}</p></div><div class="actions"><span :class="{ 'field-error': totalCents !== 10000 && !originalRelativeWeights }" role="status">{{ weightMessage }}</span><button type="button" class="button" @click="distributeWeights">Repartir pesos por igual</button><button type="button" class="button" :disabled="form.scores.length >= 20 || (legacy && !challengeContext)" @click="addLevel"><Plus :size="16"/>Añadir nivel</button></div></div>
      <div class="rubric-table-scroll" tabindex="0" role="region" aria-label="Tabla de criterios y niveles">
        <table class="rubric-table rubric-editor-table">
          <caption class="sr-only">Editor de rúbrica. Una fila por criterio y una columna por nivel.</caption>
          <colgroup><col class="rubric-criterion-col"/><col class="rubric-weight-col"/><col v-for="(_, index) in form.scores" :key="index" class="rubric-level-col"/></colgroup>
          <thead><tr><th scope="col">Criterio</th><th scope="col">{{ legacyWeights ? 'Peso' : 'Peso (%)' }}</th><th v-for="(_, index) in form.scores" :key="index" scope="col"><div class="rubric-level-heading"><label :for="`score-${index}`">Nivel {{ index + 1 }}</label><button type="button" class="icon-button" :aria-label="`Quitar nivel ${index + 1}`" :disabled="!canRemoveLevel(index) || (legacy && !challengeContext)" @click="removeLevel(index)"><X :size="16"/></button></div><label v-if="!challengeContext || !legacy" class="rubric-score" :for="`score-${index}`"><span>Nota</span><input :id="`score-${index}`" v-model="form.scores[index]" :aria-label="`Nota del nivel ${index + 1}`" inputmode="decimal" required/></label></th></tr></thead>
          <tbody><tr v-for="(item, index) in form.items" :key="item.key">
            <th scope="row" class="rubric-criterion-cell"><label v-if="form.kind === 'team'" class="sr-only" :for="`module-${item.key}`">Módulo del criterio {{ index + 1 }}</label><select v-if="form.kind === 'team'" :id="`module-${item.key}`" v-model="item.module_id" class="rubric-module-select"><option :value="null">GENERAL</option><option v-for="module in availableModules" :key="module.id" :value="module.id">{{ module.code }} · {{ module.name }}</option></select><label class="sr-only" :for="`name-${item.key}`">Nombre del criterio {{ index + 1 }}</label><input :id="`name-${item.key}`" v-model="item.name" placeholder="Nombre del criterio" required maxlength="150"/><label class="sr-only" :for="`description-${item.key}`">Descripción del criterio {{ index + 1 }}</label><textarea :id="`description-${item.key}`" v-model="item.description" class="rubric-criterion-description" rows="2" maxlength="2000" placeholder="Descripción opcional"/><div class="rubric-row-actions"><span>Criterio {{ index + 1 }}</span><button type="button" class="icon-button" :aria-label="`Subir criterio ${index + 1}`" :disabled="index === 0" @click="move(index, -1)"><ArrowUp :size="15"/></button><button type="button" class="icon-button" :aria-label="`Bajar criterio ${index + 1}`" :disabled="index === form.items.length - 1" @click="move(index, 1)"><ArrowDown :size="15"/></button><button type="button" class="icon-button" :aria-label="`Eliminar criterio ${index + 1}`" :disabled="form.items.length === 1" @click="removeItem(index)"><X :size="15"/></button></div></th>
            <td><label class="sr-only" :for="`weight-${item.key}`">Peso del criterio {{ index + 1 }}</label><input :id="`weight-${item.key}`" v-model="item.weight" class="rubric-weight-input" type="number" min="0.01" max="100" step="0.01" required/><span v-if="fieldError(`items.${index}.weight`)" class="field-error">{{ fieldError(`items.${index}.weight`) }}</span></td>
            <td v-for="(_, levelIndex) in form.scores" :key="levelIndex"><label v-if="challengeContext && legacy && item.levels[levelIndex]" class="rubric-score">Nota<input v-model="item.levels[levelIndex].score" :aria-label="`Criterio ${index + 1}, nota del nivel ${levelIndex + 1}`" inputmode="decimal" required/></label><label class="sr-only" :for="`cell-${item.key}-${levelIndex}`">Criterio {{ index + 1 }}, descripción del nivel {{ levelIndex + 1 }}</label><textarea v-if="item.levels[levelIndex]" :id="`cell-${item.key}-${levelIndex}`" v-model="item.levels[levelIndex].description" rows="5" maxlength="2000" placeholder="Describe qué debe conseguir…" required/><span v-else class="muted">{{ challengeContext ? 'Este criterio no tiene este nivel.' : 'Unifica los niveles para completar esta celda.' }}</span><span v-if="fieldError(`items.${index}.levels.${levelIndex}.description`)" class="field-error">{{ fieldError(`items.${index}.levels.${levelIndex}.description`) }}</span></td>
          </tr></tbody>
        </table>
      </div>
      <div class="rubric-toolbar"><button type="button" class="button" :disabled="form.items.length >= 40 || (legacy && !challengeContext)" @click="form.items.push(makeItem(form.scores))"><Plus :size="16"/>Añadir criterio</button><span class="helper">{{ form.items.length }} criterios · {{ form.scores.length }} niveles. Desplaza la tabla para ver todas las columnas.</span></div>
      <details v-if="!challengeContext" class="panel rubric-sharing"><summary>Compartir para usar y copiar · {{ form.shared_user_ids.length }} personas</summary><div class="rubric-sharing-list"><label v-for="teacher in teachers" :key="teacher.id" class="check"><input v-model="form.shared_user_ids" type="checkbox" :value="teacher.id"/>{{ teacher.name }}</label></div></details>
      </fieldset>
      <footer class="rubric-toolbar"><p class="helper">{{ challengeContext ? (challengeContext.savesToLibrary ? 'Los cambios se aplicarán a este reto y a su rúbrica en la biblioteca. Se conservará la evidencia anterior en el historial.' : 'Los cambios se aplicarán únicamente a este reto. Se conservará la evidencia anterior en el historial.') : 'Editar esta plantilla no modifica las rúbricas que ya están asignadas a retos.' }}</p><div class="actions"><Link :href="libraryUrl" class="button">Cancelar</Link><button class="button primary" type="submit" :disabled="submitDisabled">{{ saveLabel }}</button></div></footer>
    </form>
    <Modal v-if="impact" title="Confirmar cambios de la rúbrica" @close="!submitting && (impact = null)">
      <p><strong>{{ impact.removed_assessments }} valoraciones se eliminarán.</strong> Las restantes se conservarán.</p>
      <p v-if="impact.removed_assessments">Detalle: <span v-for="(count, kind) in impact.removed_by_kind" :key="kind">{{ kindLabels[kind] }}: {{ count }} · </span></p>
      <p v-if="impact.affected_subjects.length">Afectados por las eliminaciones: {{ impact.affected_subjects.join(', ') }}.</p>
      <p v-if="impact.removed_criteria.length">Criterios eliminados: {{ impact.removed_criteria.join(', ') }}.</p>
      <p v-if="impact.changed_team_grades.length">Cambiará la nota de estos equipos: {{ impact.changed_team_grades.join(', ') }}.</p>
      <p v-if="impact.changed_results.length">Cambiarán resultados o evaluaciones pendientes de {{ impact.changed_results.length }} estudiantes: {{ impact.changed_results.join(', ') }}.</p>
      <p v-if="impact.invalid_allocations.length" class="notice warning">Repartos que deberás revisar: {{ impact.invalid_allocations.join(', ') }}. Sus valores se conservan, pero ya no cumplen el reparto del equipo.</p>
      <p>Se conservará la evidencia anterior en el historial. Las publicaciones anteriores mantienen sus resultados.</p>
      <div class="actions"><button class="button" :disabled="submitting" @click="impact = null">Seguir editando</button><button class="button primary" :disabled="submitting" @click="confirmChange">{{ submitting ? 'Guardando…' : 'Confirmar y guardar' }}</button></div>
    </Modal>
  </Layout>
</template>
