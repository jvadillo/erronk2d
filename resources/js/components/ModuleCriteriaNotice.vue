<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import type { RubricItem, RubricModule } from '../rubrics';

const props = defineProps<{ items: RubricItem[]; modules: RubricModule[] }>();
const missingModules = computed(() => props.modules.filter(module => !props.items.some(item => Number(item.module_id) === module.id)));
const dismissed = ref(false);
watch(() => missingModules.value.map(module => module.id).join(','), () => { dismissed.value = false; });
</script>

<template>
  <div v-if="missingModules.length && !dismissed" class="notice warning" role="status">
    <p>La rúbrica técnica no tiene criterios específicos para estos módulos participantes: {{ missingModules.map(module => `${module.code} · ${module.name}`).join(', ') }}. Puedes añadirlos al editar la rúbrica. Este aviso no impide evaluar.</p>
    <button type="button" class="icon-button" aria-label="Cerrar aviso de módulos sin criterios" @click="dismissed = true"><X :size="18"/></button>
  </div>
</template>
