<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import CatalogQuickAdd, { type CatalogItem } from './CatalogQuickAdd.vue';

type Person = { id: number; name: string; email?: string; active?: boolean };
type SourceGroup = { id: number; name: string; cycle_name: string; level: number; users: Person[]; students: Person[]; modules: CatalogItem[]; periods: { name: string }[] };

const props = withDefaults(defineProps<{
    mode: 'create' | 'duplicate';
    submitUrl: string;
    years: CatalogItem[];
    teachers: Person[];
    currentYearId?: number | null;
    cycles?: CatalogItem[];
    modules?: CatalogItem[];
    source?: SourceGroup;
}>(), { currentYearId: null, cycles: () => [], modules: () => [], source: undefined });

const page = usePage();
const auth = page.props.auth as { id: number; role: string };
const isAdmin = auth.role === 'admin';
const duplicating = computed(() => props.mode === 'duplicate');

/** Entries created from this form, kept until the page props include them. */
const created = ref<{ cycle: CatalogItem[]; module: CatalogItem[] }>({ cycle: [], module: [] });
const merge = (items: CatalogItem[], extra: CatalogItem[]) => [...items, ...extra.filter(item => !items.some(existing => existing.id === item.id))];
const yearOptions = computed(() => props.years);
const cycleOptions = computed(() => merge(props.cycles, created.value.cycle).sort((a, b) => a.name.localeCompare(b.name, 'es')));
const moduleCatalog = computed(() => merge(props.modules, created.value.module));
const adding = ref<'' | 'cycle' | 'module'>('');

const defaultYear = props.years.find(year => year.id === props.currentYearId && !duplicating.value)?.id ?? props.years[0]?.id ?? '';
const form = useForm({
    name: '',
    academic_year_id: defaultYear as number | '',
    cycle_id: '' as number | '',
    level: 1,
    period_count: 3,
    module_ids: [] as number[],
    owner_id: auth.id,
    user_ids: duplicating.value ? (props.source?.users ?? []).filter(user => user.active !== false).map(user => user.id) : [auth.id],
    enroll_students: false,
    student_ids: [] as number[],
});
if (!form.user_ids.includes(auth.id)) form.user_ids.push(auth.id);

const groupModules = computed(() => moduleCatalog.value
    .filter(module => module.cycle_id === Number(form.cycle_id) && module.level === Number(form.level))
    .sort((a, b) => (a.code ?? '').localeCompare(b.code ?? '', 'es', { numeric: true })));
watch(() => [form.cycle_id, form.level], () => { form.module_ids = groupModules.value.map(module => module.id); });
const activeStudents = computed(() => (props.source?.students ?? []).filter(student => student.active !== false));
watch(() => form.enroll_students, enabled => { form.student_ids = enabled ? activeStudents.value.map(student => student.id) : []; });
const ownerId = computed(() => (isAdmin && !duplicating.value ? Number(form.owner_id) : auth.id));

function select(kind: 'cycle' | 'module', item: CatalogItem, isNew: boolean): void {
    if (isNew) created.value[kind].push(item);
    if (kind === 'cycle') form.cycle_id = item.id;
    if (kind === 'module' && !form.module_ids.includes(item.id)) form.module_ids.push(item.id);
    adding.value = '';
}
function refreshCatalog(): void {
    router.reload({ only: duplicating.value ? ['openYears'] : ['openYears', 'cycles', 'modules'] });
}
function submit(): void {
    form.transform(data => {
        const userIds = Array.from(new Set([...data.user_ids, ownerId.value]));
        if (duplicating.value) return { academic_year_id: data.academic_year_id, name: data.name, user_ids: userIds, student_ids: data.student_ids };
        const { enroll_students, student_ids, owner_id, ...group } = data;
        return { ...group, user_ids: userIds, ...(isAdmin ? { owner_id } : {}) };
    }).post(props.submitUrl, { preserveScroll: true });
}
</script>

<template>
    <form class="group-form" @submit.prevent="submit">
        <section class="group-form-step">
            <h3>1. Curso académico</h3>
            <label>Curso académico<select v-model="form.academic_year_id" required><option value="" disabled>{{ yearOptions.length ? 'Selecciona' : 'Todavía no hay cursos abiertos' }}</option><option v-for="year in yearOptions" :key="year.id" :value="year.id">{{ year.name }}</option></select></label>
            <p v-if="!yearOptions.length" class="notice warning" role="status">No hay cursos académicos abiertos. Solo administración puede crearlos o reabrirlos.</p>
            <p v-else class="helper">Solo se muestran cursos académicos abiertos. Los crea administración.</p>
        </section>

        <section class="group-form-step">
            <h3>2. Grupo</h3>
            <label>Nombre del grupo<input v-model="form.name" required maxlength="100" placeholder="Por ejemplo, 2DAW-A" /></label>
            <template v-if="!duplicating">
                <div class="group-form-row">
                    <label>Ciclo<select v-model="form.cycle_id" required><option value="" disabled>Selecciona</option><option v-for="cycle in cycleOptions" :key="cycle.id" :value="cycle.id">{{ cycle.name }}</option></select></label>
                    <label class="group-form-level">Curso / nivel<select v-model="form.level" required><option v-for="n in 4" :key="n" :value="n">{{ n }}.º</option></select></label>
                    <button type="button" class="button small" :aria-expanded="adding === 'cycle'" @click="adding = adding === 'cycle' ? '' : 'cycle'">{{ adding === 'cycle' ? 'Cancelar' : 'Crear ciclo' }}</button>
                </div>
                <CatalogQuickAdd v-if="adding === 'cycle'" kind="cycle" :items="cycleOptions" @created="select('cycle', $event, true)" @select="select('cycle', $event, false)" @conflict="refreshCatalog" />
                <p class="helper">El ciclo y el curso / nivel no se pueden cambiar después de crear el grupo.</p>
            </template>
            <p v-else class="helper">Se copian {{ source?.cycle_name }} · Curso / nivel {{ source?.level }}.º y las evaluaciones {{ source?.periods.map(period => period.name).join(', ') }}.</p>
        </section>

        <section class="group-form-step">
            <h3>3. Módulos</h3>
            <template v-if="!duplicating">
                <p v-if="!form.cycle_id" class="helper">Selecciona un ciclo para revisar sus módulos.</p>
                <template v-else>
                    <fieldset class="group-form-list">
                        <legend class="visually-hidden">Módulos del grupo</legend>
                        <label v-for="module in groupModules" :key="module.id" class="check"><input v-model="form.module_ids" type="checkbox" :value="module.id" />{{ module.code }} · {{ module.name }}</label>
                    </fieldset>
                    <p v-if="!groupModules.length" class="helper">El catálogo todavía no tiene módulos para este ciclo y curso / nivel. Añádelos aquí.</p>
                    <button type="button" class="button small" :aria-expanded="adding === 'module'" @click="adding = adding === 'module' ? '' : 'module'">{{ adding === 'module' ? 'Cancelar' : 'Añadir módulo al catálogo' }}</button>
                    <CatalogQuickAdd v-if="adding === 'module'" kind="module" :items="moduleCatalog" :cycles="cycleOptions" :cycle-id="Number(form.cycle_id)" :level="Number(form.level)" @created="select('module', $event, true)" @select="select('module', $event, false)" @conflict="refreshCatalog" />
                </template>
            </template>
            <p v-else class="helper">Se reutilizan del catálogo los módulos activos: {{ source?.modules.map(module => module.code).join(', ') || 'ninguno' }}.</p>
        </section>

        <section v-if="!duplicating" class="group-form-step">
            <h3>4. Evaluaciones</h3>
            <label class="group-form-level">Número de evaluaciones<input v-model="form.period_count" type="number" min="1" max="12" required /></label>
            <p class="helper">Puedes cambiar sus nombres desde la página del grupo.</p>
        </section>

        <section class="group-form-step">
            <h3>{{ duplicating ? 4 : 5 }}. Profesores</h3>
            <label v-if="isAdmin && !duplicating">Propietario<select v-model="form.owner_id"><option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option></select></label>
            <p class="helper">{{ duplicating ? 'Se proponen los profesores activos del grupo de origen. Serás el propietario del grupo nuevo.' : 'El propietario forma parte del grupo automáticamente.' }}</p>
            <fieldset class="group-form-list">
                <legend class="visually-hidden">Profesores del grupo</legend>
                <label v-for="teacher in teachers" :key="teacher.id" class="check"><input v-model="form.user_ids" type="checkbox" :value="teacher.id" :disabled="teacher.id === ownerId" />{{ teacher.name }}<small v-if="teacher.id === ownerId" class="muted">Propietario</small></label>
            </fieldset>
        </section>

        <section v-if="duplicating" class="group-form-step">
            <h3>5. Alumnado</h3>
            <label class="check"><input v-model="form.enroll_students" type="checkbox" :disabled="!activeStudents.length" />Matricular alumnado activo del grupo de origen</label>
            <fieldset v-if="form.enroll_students" class="group-form-list">
                <legend class="visually-hidden">Estudiantes que se matricularán</legend>
                <label v-for="student in activeStudents" :key="student.id" class="check"><input v-model="form.student_ids" type="checkbox" :value="student.id" />{{ student.name }}</label>
            </fieldset>
            <p class="helper">Se reutilizan sus cuentas sin cambiar credenciales, matrículas anteriores ni resultados. No se copian retos, equipos, rúbricas, notas ni publicaciones.</p>
        </section>

        <p v-for="error in form.errors" class="notice error" role="alert">{{ error }}</p>
        <button class="button primary full" :disabled="form.processing || !form.academic_year_id">{{ duplicating ? 'Duplicar grupo' : 'Crear grupo' }}</button>
    </form>
</template>

<style scoped>
.group-form-step { padding: 4px 0 10px; border-bottom: 1px solid var(--border); margin-bottom: 10px; }
.group-form-step h3 { margin: 6px 0 8px; font-size: 13px; }
.group-form-row { display: flex; align-items: end; flex-wrap: wrap; gap: 8px 14px; }
.group-form-row label { flex: 1 1 220px; min-width: 0; margin: 4px 0; }
.group-form-row .group-form-level { flex: 0 1 140px; }
.group-form-level { max-width: 220px; }
.group-form-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0 18px; max-height: 260px; overflow: auto; border: 0; padding: 0; margin: 0 0 8px; }
.group-form-list small { margin-left: 4px; }
.visually-hidden { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
</style>
