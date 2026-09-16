<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUp, ArrowDown, Plus, Save, X } from 'lucide-vue-next';
import Layout from '../components/Layout.vue';
import { commonScores, decimal, percentage, percentageWeights, type RubricItem, type RubricModule } from '../rubrics';
const props = defineProps<{
  rubric: { id: number; name: string; kind: string; cycle_id: number | null; level: number | null; items: RubricItem[]; shared_users: { id: number }[] } | null;
  cycles: { id: number; name: string }[];
  modules: RubricModule[];
  teachers: { id: number; name: string }[];
  saveUrl: string;
  libraryUrl: string;
}>();
const legacy = ref(!!props.rubric && !commonScores(props.rubric.items));
const initialItems: RubricItem[] = props.rubric ? JSON.parse(JSON.stringify(props.rubric.items)) : [];
const weights = percentageWeights(initialItems);
initialItems.forEach((item, index) => { item.weight = weights[index]; });
const initialScores = initialItems.length
  ? Array.from({ length: Math.max(...initialItems.map(item => item.levels.length)) }, (_, index) => String(initialItems.find(item => item.levels[index])?.levels[index].score ?? '10'))
  : ['4', '6', '8', '10'];
function makeItem(scores: string[], weight = '0'): RubricItem {
  return { key: `c_${Array.from(crypto.getRandomValues(new Uint32Array(3)), value => value.toString(16)).join('')}`, name: '', description: '', module_id: null, weight, levels: scores.map(score => ({ score, description: '' })) };
}
const form = useForm(`RubricEditor:${props.rubric?.id ?? 'new'}`, {
  id: props.rubric?.id ?? null,
  name: props.rubric?.name ?? '',
  kind: props.rubric?.kind ?? 'team',
  cycle_id: props.rubric?.cycle_id ?? '' as number | string,
  level: props.rubric?.level ?? '' as number | string,
  shared_user_ids: props.rubric?.shared_users.map(user => user.id) ?? [],
  scores: initialScores,
  items: initialItems.length ? initialItems : [makeItem(initialScores, '100')],
});
const availableModules = computed(() => props.modules.filter(module => module.cycle_id === Number(form.cycle_id) && module.level === Number(form.level)));
const totalCents = computed(() => form.items.reduce((sum, item) => sum + Math.round((decimal(item.weight) || 0) * 100), 0));
const weightMessage = computed(() => {
  const difference = 10000 - totalCents.value;
  return difference === 0 ? 'Total: 100 %' : difference > 0 ? `Total: ${percentage(totalCents.value / 100)} % · Faltan ${percentage(difference / 100)} %` : `Total: ${percentage(totalCents.value / 100)} % · Sobran ${percentage(-difference / 100)} %`;
});
const fieldError = (key: string) => (form.errors as Record<string, string>)[key];
watch(() => [form.kind, form.cycle_id, form.level], () => {
  const ids = availableModules.value.map(module => module.id);
  form.items.forEach(item => { if (form.kind === 'transversal' || !ids.includes(Number(item.module_id))) item.module_id = null; });
});
function addLevel() {
  if (form.scores.length >= 20) return;
  form.scores.push('10');
  form.items.forEach(item => item.levels.push({ score: '10', description: '' }));
}
function removeLevel(index: number) {
  if (form.scores.length <= 2 || !confirm(`¿Quitar el nivel ${index + 1} y sus descripciones de todos los criterios?`)) return;
  form.scores.splice(index, 1);
  form.items.forEach(item => item.levels.splice(index, 1));
}
function removeItem(index: number) {
  if (form.items.length <= 1) return;
  const item = form.items[index];
  if ((item.name || item.description || item.levels.some(level => level.description)) && !confirm('¿Eliminar este criterio?')) return;
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
  if (legacy.value) return;
  form.transform(data => ({
    id: data.id, name: data.name, kind: data.kind, cycle_id: data.cycle_id || null, level: data.level || null, shared_user_ids: data.shared_user_ids,
    items: data.items.map(item => ({ ...item, module_id: data.kind === 'transversal' ? null : item.module_id, weight: String(item.weight).replace(',', '.'), levels: data.scores.map((score, index) => ({ score: score.replace(',', '.'), description: item.levels[index]?.description ?? '' })) })),
  })).post(props.saveUrl, { onSuccess: () => form.defaults() });
}
let removeNavigationGuard: (() => void) | undefined;
function beforeUnload(event: BeforeUnloadEvent) { if (form.isDirty && !form.processing) { event.preventDefault(); event.returnValue = ''; } }
onMounted(() => {
  window.addEventListener('beforeunload', beforeUnload);
  removeNavigationGuard = router.on('before', event => {
    if (event.detail.visit.method === 'get' && form.isDirty && !form.processing && !confirm('Hay cambios sin guardar. ¿Salir del editor?')) event.preventDefault();
  });
});
onBeforeUnmount(() => { window.removeEventListener('beforeunload', beforeUnload); removeNavigationGuard?.(); });
</script>

<template>
  <Layout>
    <Head :title="rubric ? 'Editar rúbrica' : 'Crear rúbrica'"/>
    <div class="topline"><Link :href="libraryUrl" class="back-link"><ArrowLeft :size="16"/>Biblioteca de rúbricas</Link></div>
    <form class="rubric-workspace" @submit.prevent="submit">
      <header class="page-heading"><div><p class="eyebrow">BIBLIOTECA DE RÚBRICAS</p><h1>{{ rubric ? 'Editar rúbrica' : 'Crear rúbrica' }}</h1><p class="muted">Un criterio por fila. Una nota común por columna.</p></div><button class="button primary" type="submit" :disabled="form.processing || legacy"><Save :size="17"/>{{ form.processing ? 'Guardando…' : 'Guardar rúbrica' }}</button></header>
      <section class="panel rubric-settings">
        <label>Nombre de la rúbrica<input v-model="form.name" required maxlength="150"/><span v-if="form.errors.name" class="field-error">{{ form.errors.name }}</span></label>
        <label>Tipo<select v-model="form.kind"><option value="team">Valoración del reto</option><option value="transversal">Competencias transversales</option></select></label>
        <label>Ciclo<select v-model="form.cycle_id"><option value="">General</option><option v-for="cycle in cycles" :key="cycle.id" :value="cycle.id">{{ cycle.name }}</option></select><span v-if="form.errors.cycle_id" class="field-error">{{ form.errors.cycle_id }}</span></label>
        <label>Curso / nivel<select v-model="form.level"><option value="">General</option><option v-for="n in 4" :key="n" :value="n">{{ n }}.º</option></select></label>
      </section>
      <div v-if="legacy" class="notice warning"><p>Esta plantilla tiene niveles diferentes entre criterios. Revisa las notas de las columnas y unifica los niveles para editarla en tabla. Las evaluaciones de retos anteriores se conservan.</p><button class="button" type="button" @click="unifyLegacy">Unificar niveles de la plantilla</button></div>
      <div v-if="form.hasErrors" class="notice error" role="alert"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
      <div class="rubric-toolbar"><div><h2>Criterios y niveles</h2><p class="helper">Pesos en porcentaje, con hasta dos decimales. Todos deben sumar 100 %.</p></div><div class="actions"><span :class="{ 'field-error': totalCents !== 10000 }" role="status">{{ weightMessage }}</span><button type="button" class="button" @click="distributeWeights">Repartir pesos por igual</button><button type="button" class="button" :disabled="form.scores.length >= 20 || legacy" @click="addLevel"><Plus :size="16"/>Añadir nivel</button></div></div>
      <div class="rubric-table-scroll" tabindex="0" role="region" aria-label="Tabla de criterios y niveles">
        <table class="rubric-table rubric-editor-table">
          <caption class="sr-only">Editor de rúbrica. Una fila por criterio y una columna por nivel.</caption>
          <colgroup><col v-if="form.kind === 'team'" class="rubric-module-col"/><col class="rubric-name-col"/><col class="rubric-weight-col"/><col v-for="(_, index) in form.scores" :key="index" class="rubric-level-col"/></colgroup>
          <thead><tr><th v-if="form.kind === 'team'" scope="col">Módulo</th><th scope="col">Nombre</th><th scope="col">Peso (%)</th><th v-for="(_, index) in form.scores" :key="index" scope="col"><div class="rubric-level-heading"><label :for="`score-${index}`">Nivel {{ index + 1 }}</label><button type="button" class="icon-button" :aria-label="`Quitar nivel ${index + 1}`" :disabled="form.scores.length <= 2 || legacy" @click="removeLevel(index)"><X :size="16"/></button></div><label class="rubric-score" :for="`score-${index}`"><span>Nota</span><input :id="`score-${index}`" v-model="form.scores[index]" :aria-label="`Nota del nivel ${index + 1}`" inputmode="decimal" required/></label></th></tr></thead>
          <tbody><tr v-for="(item, index) in form.items" :key="item.key">
            <td v-if="form.kind === 'team'"><label class="sr-only" :for="`module-${item.key}`">Módulo del criterio {{ index + 1 }}</label><select :id="`module-${item.key}`" v-model="item.module_id"><option :value="null">GENERAL</option><option v-for="module in availableModules" :key="module.id" :value="module.id">{{ module.code }} · {{ module.name }}</option></select></td>
            <th scope="row"><label class="sr-only" :for="`name-${item.key}`">Nombre del criterio {{ index + 1 }}</label><input :id="`name-${item.key}`" v-model="item.name" placeholder="Nombre del criterio" required maxlength="150"/><label class="sr-only" :for="`description-${item.key}`">Descripción del criterio {{ index + 1 }}</label><textarea :id="`description-${item.key}`" v-model="item.description" class="rubric-criterion-description" rows="2" maxlength="2000" placeholder="Descripción opcional"/><div class="rubric-row-actions"><span>Criterio {{ index + 1 }}</span><button type="button" class="icon-button" :aria-label="`Subir criterio ${index + 1}`" :disabled="index === 0" @click="move(index, -1)"><ArrowUp :size="15"/></button><button type="button" class="icon-button" :aria-label="`Bajar criterio ${index + 1}`" :disabled="index === form.items.length - 1" @click="move(index, 1)"><ArrowDown :size="15"/></button><button type="button" class="icon-button" :aria-label="`Eliminar criterio ${index + 1}`" :disabled="form.items.length === 1" @click="removeItem(index)"><X :size="15"/></button></div></th>
            <td><label class="sr-only" :for="`weight-${item.key}`">Peso del criterio {{ index + 1 }}</label><input :id="`weight-${item.key}`" v-model="item.weight" inputmode="decimal" required/><span v-if="fieldError(`items.${index}.weight`)" class="field-error">{{ fieldError(`items.${index}.weight`) }}</span></td>
            <td v-for="(_, levelIndex) in form.scores" :key="levelIndex"><label class="sr-only" :for="`cell-${item.key}-${levelIndex}`">Criterio {{ index + 1 }}, descripción del nivel {{ levelIndex + 1 }}</label><textarea v-if="item.levels[levelIndex]" :id="`cell-${item.key}-${levelIndex}`" v-model="item.levels[levelIndex].description" rows="5" maxlength="2000" placeholder="Describe qué debe conseguir…" required/><span v-else class="muted">Unifica los niveles para completar esta celda.</span><span v-if="fieldError(`items.${index}.levels.${levelIndex}.description`)" class="field-error">{{ fieldError(`items.${index}.levels.${levelIndex}.description`) }}</span></td>
          </tr></tbody>
        </table>
      </div>
      <div class="rubric-toolbar"><button type="button" class="button" :disabled="form.items.length >= 40 || legacy" @click="form.items.push(makeItem(form.scores))"><Plus :size="16"/>Añadir criterio</button><span class="helper">{{ form.items.length }} criterios · {{ form.scores.length }} niveles. Desplaza la tabla para ver todas las columnas.</span></div>
      <details class="panel rubric-sharing"><summary>Compartir para usar y copiar · {{ form.shared_user_ids.length }} personas</summary><div class="rubric-sharing-list"><label v-for="teacher in teachers" :key="teacher.id" class="check"><input v-model="form.shared_user_ids" type="checkbox" :value="teacher.id"/>{{ teacher.name }}</label></div></details>
      <footer class="rubric-toolbar"><p class="helper">Editar esta plantilla no modifica las rúbricas que ya están asignadas a retos.</p><div class="actions"><Link :href="libraryUrl" class="button">Cancelar</Link><button class="button primary" type="submit" :disabled="form.processing || legacy">Guardar rúbrica</button></div></footer>
    </form>
  </Layout>
</template>
