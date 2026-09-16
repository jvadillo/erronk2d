<script setup lang="ts">
import { computed } from 'vue';
import { Check } from 'lucide-vue-next';
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
const completed = computed(() => props.rubric.items.filter(item => props.selections[item.key] !== undefined).length);
const moduleName = (id: number | null) => id ? props.modules.find(module => module.id === id)?.code ?? 'Módulo' : 'GENERAL';
</script>

<template>
  <section class="rubric-assessment" :aria-label="`Rúbrica de ${subject}`">
    <div class="rubric-summary"><h3>{{ rubric.name }}</h3><span>{{ completed }} / {{ rubric.items.length }} criterios evaluados</span></div>
    <p v-if="!shared" class="notice info">Esta rúbrica conserva las notas originales de cada criterio. La puntuación se muestra en cada celda.</p>
    <div class="rubric-table-scroll" tabindex="0" role="region" :aria-label="`Tabla de evaluación de ${subject}`">
      <table class="rubric-table rubric-assessment-table">
        <caption class="sr-only">{{ rubric.name }} · {{ subject }}. Selecciona un nivel por criterio.</caption>
        <colgroup><col v-if="showModules" class="rubric-module-col"/><col class="rubric-name-col"/><col class="rubric-weight-col"/><col v-for="n in count" :key="n" class="rubric-level-col"/></colgroup>
        <thead><tr><th v-if="showModules" scope="col">Módulo</th><th scope="col">Nombre</th><th scope="col">Peso</th><th v-for="n in count" :key="n" scope="col"><span>Nivel {{ n }}</span><strong v-if="shared">{{ percentage(decimal(rubric.items[0].levels[n - 1].score)) }} puntos</strong></th></tr></thead>
        <tbody><tr v-for="item in rubric.items" :key="item.key" class="student-rubric">
          <td v-if="showModules"><span class="module-chip">{{ moduleName(item.module_id) }}</span></td>
          <th scope="row"><strong>{{ item.name }}</strong><p v-if="item.description" class="rubric-description">{{ item.description }}</p><small v-if="disabledCriteria.includes(item.key)">Solo el responsable del módulo puede evaluar este criterio.</small></th>
          <td class="rubric-weight">{{ percentage(total ? decimal(item.weight) / total * 100 : 0) }} %</td>
          <td v-for="n in count" :key="n" class="rubric-choice-cell" :class="{ 'is-selected': selections[item.key] === n - 1 }">
            <button v-if="item.levels[n - 1]" type="button" class="rubric-choice" :class="{ chosen: selections[item.key] === n - 1 }" :aria-pressed="selections[item.key] === n - 1" :aria-label="`${subject}, ${item.name}: ${item.levels[n - 1].score}. ${item.levels[n - 1].description}`" :disabled="disabled || disabledCriteria.includes(item.key)" @click="emit('select', item.key, n - 1)">
              <strong v-if="!shared">{{ percentage(decimal(item.levels[n - 1].score)) }} puntos</strong>
              <span>{{ item.levels[n - 1].description }}</span>
              <span class="rubric-choice-state"><Check v-if="selections[item.key] === n - 1" :size="15"/>{{ selections[item.key] === n - 1 ? 'Seleccionado' : 'Seleccionar' }}</span>
            </button><span v-else class="muted">—</span>
          </td>
        </tr></tbody>
      </table>
    </div>
    <p class="helper">Cada selección se guarda automáticamente. Puedes desplazar la tabla horizontalmente para ver todos los niveles.</p>
  </section>
</template>
