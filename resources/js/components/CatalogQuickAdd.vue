<script setup lang="ts">
import { computed, ref } from 'vue';
import { Plus } from 'lucide-vue-next';
import { api } from '../lib';

export type CatalogItem = { id: number; name: string; code?: string; cycle_id?: number | null; level?: number; is_open?: boolean };

const props = withDefaults(defineProps<{
    kind: 'year' | 'cycle' | 'module';
    items: CatalogItem[];
    cycles?: CatalogItem[];
    cycleId?: number | null;
    level?: number | null;
    allowNoCycle?: boolean;
}>(), { cycles: () => [], cycleId: undefined, level: undefined, allowNoCycle: false });
const emit = defineEmits<{ created: [item: CatalogItem]; select: [item: CatalogItem]; conflict: [] }>();

const labels = { year: 'curso académico', cycle: 'ciclo', module: 'módulo' };
const placeholders = { year: 'Por ejemplo, 2027-2028', cycle: 'Por ejemplo, Desarrollo de Aplicaciones Web', module: 'Por ejemplo, Programación' };
const name = ref('');
const code = ref('');
const chosenCycle = ref<number | ''>(props.cycleId ?? '');
const chosenLevel = ref(props.level ?? 1);
const busy = ref(false);
const error = ref('');
const fixedPlacement = computed(() => props.kind === 'module' && props.cycleId !== undefined);
const targetCycle = computed(() => (fixedPlacement.value ? props.cycleId ?? null : chosenCycle.value === '' ? null : Number(chosenCycle.value)));
const targetLevel = computed(() => (fixedPlacement.value ? props.level ?? 1 : chosenLevel.value));

/** Ignores case, accents and repeated spaces, so that near-identical entries are shown before creating another one. */
const normalize = (value = '') => value.normalize('NFD').replace(/[̀-ͯ]/g, '').toLocaleLowerCase('es').replace(/\s+/g, ' ').replace(/\s*([/-])\s*/g, '$1').trim();
const similar = (candidate: string | undefined, typed: string) => {
    const a = normalize(candidate), b = normalize(typed);
    return b.length >= 2 && !!a && (a.includes(b) || b.includes(a));
};
const matches = computed(() => props.items
    .filter(item => similar(item.name, name.value) || (props.kind !== 'year' && similar(item.code, code.value)))
    .filter(item => props.kind !== 'module' || targetCycle.value === null || item.cycle_id === targetCycle.value)
    .slice(0, 8));
const cycleName = (id: number | null | undefined) => props.cycles.find(cycle => cycle.id === id)?.name ?? '';
const usable = (item: CatalogItem) => props.kind !== 'module' || (item.cycle_id === targetCycle.value && item.level === targetLevel.value);

async function create(): Promise<void> {
    error.value = '';
    if (!name.value.trim() || (props.kind !== 'year' && !code.value.trim())) {
        error.value = props.kind === 'year' ? 'Escribe el nombre del curso académico.' : 'Escribe el nombre y el código.';
        return;
    }
    busy.value = true;
    try {
        const payload: Record<string, unknown> = { name: name.value };
        if (props.kind !== 'year') payload.code = code.value;
        if (props.kind === 'module') Object.assign(payload, { cycle_id: targetCycle.value, level: targetLevel.value });
        const { item } = await api(`/setup/${props.kind}`, payload);
        name.value = '';
        code.value = '';
        emit('created', item);
    } catch (exception) {
        error.value = (exception as Error).message;
        emit('conflict');
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="catalog-quick-add" role="group" :aria-label="`Crear ${labels[kind]}`">
        <div class="catalog-quick-fields">
            <label>Nombre del {{ labels[kind] }}<input v-model="name" :maxlength="kind === 'year' ? 100 : 150" :placeholder="placeholders[kind]" @keydown.enter.prevent="create" /></label>
            <label v-if="kind !== 'year'">Código<input v-model="code" maxlength="30" @keydown.enter.prevent="create" /></label>
            <template v-if="kind === 'module' && !fixedPlacement">
                <label>Ciclo<select v-model="chosenCycle"><option value="" :disabled="!allowNoCycle">{{ allowNoCycle ? 'Sin ciclo asignado' : 'Selecciona' }}</option><option v-for="cycle in cycles" :key="cycle.id" :value="cycle.id">{{ cycle.name }}</option></select></label>
                <label>Curso / nivel<select v-model="chosenLevel"><option v-for="n in 4" :key="n" :value="n">{{ n }}.º</option></select></label>
            </template>
        </div>
        <div v-if="matches.length" class="catalog-matches" aria-live="polite">
            <p class="helper">Ya existen en el catálogo común. Reutilízalos en lugar de crear un duplicado:</p>
            <div v-for="item in matches" :key="item.id" class="breakdown-row">
                <span>{{ item.code ? `${item.code} · ` : '' }}{{ item.name }}<small v-if="kind === 'module'" class="helper">{{ cycleName(item.cycle_id) || 'Sin ciclo asignado' }} · Curso / nivel {{ item.level }}.º</small></span>
                <button v-if="usable(item)" type="button" class="button small" @click="emit('select', item)">Usar</button>
                <small v-else class="muted">Otro curso / nivel</small>
            </div>
        </div>
        <p v-if="error" class="notice error" role="alert">{{ error }}</p>
        <button type="button" class="button" :disabled="busy || (kind === 'module' && !allowNoCycle && targetCycle === null)" @click="create"><Plus :size="16" />Crear {{ labels[kind] }}</button>
    </div>
</template>

<style scoped>
.catalog-quick-add { padding: 12px 14px; border: 1px dashed var(--border); border-radius: 8px; background: #fbf8fa; margin: 8px 0 12px; }
.catalog-quick-fields { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 6px 14px; }
.catalog-quick-fields label { margin: 4px 0; min-width: 0; }
.catalog-matches .breakdown-row { padding: 8px 0; flex-wrap: wrap; }
.catalog-matches .breakdown-row > span { flex: 1 1 180px; min-width: 0; overflow-wrap: anywhere; }
.catalog-matches small.helper { display: block; margin: 2px 0 0; }
</style>
