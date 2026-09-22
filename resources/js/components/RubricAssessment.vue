<script setup lang="ts">
import { computed, ref } from 'vue';
import { commonScores, decimal, percentage, type RubricData, type RubricModule } from '../rubrics';
const props = withDefaults(defineProps<{
  rubric: RubricData;
  subject: string;
  selections: Record<string, number | undefined>;
  modules?: RubricModule[];
  showModules?: boolean;
  disabled?: boolean;
  disabledCriteria?: string[];
}>(), { modules: () => [], disabledCriteria: () => [] });
const emit = defineEmits<{ select: [key: string, level: number] }>();
const shared = computed(() => commonScores(props.rubric.items));
const count = computed(() => Math.max(0, ...props.rubric.items.map(item => item.levels.length)));
const total = computed(() => props.rubric.items.reduce((sum, item) => sum + decimal(item.weight), 0));
const moduleName = (id: number | null) => id ? props.modules.find(module => module.id === id)?.code ?? 'Módulo' : 'GENERAL';
const expandedLevels = ref<Record<string, boolean>>({});
const expandedCriteria = ref<Set<string>>(new Set());
function toggleDescription(key: string): void {
  const next = new Set(expandedCriteria.value);
  if (next.has(key)) next.delete(key);
  else next.add(key);
  expandedCriteria.value = next;
}
</script>

<template>
  <section class="rubric-assessment" :aria-label="`Rúbrica de ${subject}`">
    <p v-if="!shared" class="notice info">Esta rúbrica conserva las notas originales de cada criterio. La puntuación se muestra en cada celda.</p>
    <div class="rubric-table-scroll" tabindex="0" role="region" :aria-label="`Tabla de evaluación de ${subject}`">
      <table class="rubric-table rubric-assessment-table">
        <caption class="sr-only">{{ rubric.name }} · {{ subject }}. Selecciona un nivel por criterio.</caption>
        <colgroup><col class="rubric-name-col"/><col v-for="n in count" :key="n" class="rubric-level-col"/></colgroup>
        <thead><tr><th scope="col">Nombre</th><th v-for="n in count" :key="n" scope="col"><span>Nivel {{ n }}</span><strong v-if="shared">{{ percentage(decimal(rubric.items[0].levels[n - 1].score)) }} puntos</strong></th></tr></thead>
        <tbody><tr v-for="item in rubric.items" :key="item.key" class="student-rubric">
          <th scope="row"><div class="rubric-criterion-name"><div class="rubric-criterion-meta"><span v-if="showModules" class="module-chip">{{ moduleName(item.module_id) }}</span><span class="rubric-weight">{{ percentage(total ? decimal(item.weight) / total * 100 : 0) }} %</span></div><strong>{{ item.name }}</strong></div><button v-if="item.description" type="button" class="rubric-description-toggle" :aria-expanded="expandedCriteria.has(item.key)" :aria-label="`${expandedCriteria.has(item.key) ? 'Contraer' : 'Expandir'} descripción de ${item.name}`" @click="toggleDescription(item.key)"><span class="rubric-description" :class="{ expanded: expandedCriteria.has(item.key) }">{{ item.description }}</span></button><small v-if="disabledCriteria.includes(item.key)">Solo el responsable del módulo puede evaluar este criterio.</small></th>
          <td v-for="n in count" :key="n" class="rubric-choice-cell" :class="{ 'is-selected': selections[item.key] === n - 1 }">
            <div v-if="item.levels[n - 1]" class="rubric-level-content">
            <div class="rubric-level-description">
              <span class="rubric-description" :class="{ expanded: expandedLevels[`${item.key}:${n}`] }">{{ item.levels[n - 1].description }}</span>
              <button v-if="item.levels[n - 1].description.length > 120" type="button" class="rubric-read-more" :aria-expanded="!!expandedLevels[`${item.key}:${n}`]" :aria-label="`${expandedLevels[`${item.key}:${n}`] ? 'Contraer' : 'Leer más'}: ${item.name}, nivel ${n}`" @click="expandedLevels[`${item.key}:${n}`] = !expandedLevels[`${item.key}:${n}`]">{{ expandedLevels[`${item.key}:${n}`] ? '− Leer menos' : '+ Leer más' }}</button>
            </div>
            <button type="button" class="rubric-choice" :class="{ chosen: selections[item.key] === n - 1 }" :aria-pressed="selections[item.key] === n - 1" :aria-label="`${subject}, ${item.name}: ${item.levels[n - 1].score}. ${item.levels[n - 1].description}`" :disabled="disabled || disabledCriteria.includes(item.key)" @click="emit('select', item.key, n - 1)">
              <strong v-if="!shared">{{ percentage(decimal(item.levels[n - 1].score)) }} puntos</strong>
            </button></div><span v-else class="muted">—</span>
          </td>
        </tr></tbody>
      </table>
    </div>
    <p class="helper">Cada selección se guarda automáticamente. Puedes desplazar la tabla horizontalmente para ver todos los niveles.</p>
  </section>
</template>
