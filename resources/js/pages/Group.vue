<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen, ContactRound, Copy, GraduationCap, Settings, X } from 'lucide-vue-next';
import CatalogQuickAdd, { type CatalogItem } from '../components/CatalogQuickAdd.vue';
import GroupForm from '../components/GroupForm.vue';
import Layout from '../components/Layout.vue';
import Modal from '../components/Modal.vue';
import StudentSearch from '../components/StudentSearch.vue';

type Person = { id: number; name: string; email?: string; active?: boolean };
type GroupModule = { id: number; name: string; code: string };
type Period = { id?: number; name: string; challenges_count: number };
const props = defineProps<{
    group: { id: number; name: string; cycle_id: number; level: number; cycle_name: string; owner_id: number; can_manage: boolean; users: Person[]; students: Person[]; modules: GroupModule[]; retired_modules: GroupModule[]; periods: Period[] };
    catalog: { id: number; name: string; code: string }[];
    teachers: Person[];
    openYears: CatalogItem[];
    groupsUrl: string;
    duplicateUrl: string;
}>();
const duplicating = ref(false);
const addingModule = ref(false);
const catalogItems = computed<CatalogItem[]>(() => props.catalog.map(module => ({ ...module, cycle_id: props.group.cycle_id, level: props.group.level })));
function useCatalogModule(module: CatalogItem): void {
    addingModule.value = false;
    if (!props.group.modules.some(active => active.id === module.id)) setModule(module.id, true);
}

const page = usePage();
const writable = computed(() => !!(page.props.academic as any)?.year?.is_open);
const canManage = computed(() => writable.value && props.group.can_manage);
const isAdmin = (page.props.auth as any).role === 'admin';
const byName = (a: Person, b: Person) => a.name.localeCompare(b.name, 'es', { sensitivity: 'base' });

const tabs = [
    { id: 'settings', label: 'Configuración', icon: Settings },
    { id: 'modules', label: 'Módulos', icon: BookOpen },
    { id: 'teachers', label: 'Profesores', icon: ContactRound },
    { id: 'students', label: 'Estudiantes', icon: GraduationCap },
];
const activeTab = computed(() => {
    const tab = new URLSearchParams(page.url.split('?')[1] ?? '').get('tab');
    return tabs.some(item => item.id === tab) ? tab : 'settings';
});
function selectTab(tab: string): void {
    const groupUrl = page.url.split('?')[0];
    router.push({ url: tab === 'settings' ? groupUrl : `${groupUrl}?tab=${tab}`, preserveState: true, preserveScroll: true });
}
function navigateTabs(event: KeyboardEvent): void {
    if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
    event.preventDefault();
    const index = tabs.findIndex(tab => tab.id === activeTab.value);
    const target = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
    selectTab(tabs[target].id);
    nextTick(() => document.getElementById(`group-tab-${tabs[target].id}`)?.focus());
}

const groupForm = useForm({ id: props.group.id, name: props.group.name, cycle_id: props.group.cycle_id, level: props.group.level, owner_id: props.group.owner_id, user_ids: props.group.users.map(user => user.id) });
function saveGroup(): void {
    groupForm.transform(data => {
        const { owner_id, ...rest } = data;
        return isAdmin ? data : rest;
    }).post('/setup/classroom', { preserveScroll: true });
}

const periodsForm = useForm({ classroom_id: props.group.id, periods: props.group.periods.map(period => ({ id: period.id, name: period.name, challenges_count: period.challenges_count })) });
function savePeriods(): void {
    periodsForm.transform(data => ({ classroom_id: data.classroom_id, periods: data.periods.map(period => ({ id: period.id ?? null, name: period.name })) })).post('/setup/group-periods', { preserveScroll: true });
}

const moduleForm = useForm({ classroom_id: props.group.id, module_id: '', active: true });
const moduleToAdd = ref('');
const availableModules = computed(() => props.catalog.filter(module => !props.group.modules.some(active => active.id === module.id)));
function setModule(id: string | number, active: boolean): void {
    moduleForm.module_id = String(id);
    moduleForm.active = active;
    moduleForm.post('/setup/group-module', { preserveScroll: true, onSuccess: () => { moduleToAdd.value = ''; } });
}
const groupTeachers = computed(() => [...props.group.users].sort(byName));
const ownerName = computed(() => props.group.users.find(user => user.id === props.group.owner_id)?.name ?? props.teachers.find(teacher => teacher.id === props.group.owner_id)?.name ?? '');

const students = computed(() => [...props.group.students].sort(byName));
const enrollment = useForm({ classroom_id: props.group.id, email: '', active: true });
const withdrawal = useForm({ classroom_id: props.group.id, email: '', active: false });
function enroll(): void {
    enrollment.post('/setup/enrollment', { preserveScroll: true, onSuccess: () => enrollment.reset('email') });
}
function withdraw(student: Person): void {
    withdrawal.email = student.email ?? '';
    withdrawal.post('/setup/enrollment', { preserveScroll: true });
}
</script>

<template>
    <Layout>
        <Head :title="group.name" />
        <div class="topline"><Link :href="groupsUrl" class="back-link"><ArrowLeft :size="16" />Grupos</Link><span>BASES PARA UN BUEN RETO</span></div>
        <header class="page-heading">
            <div>
                <h1>{{ group.name }}<span class="accent">.</span></h1>
                <p class="muted">{{ group.cycle_name }} · Curso / nivel {{ group.level }}.º · {{ group.students.length }} estudiantes · {{ group.users.length }} profesores · {{ group.periods.length }} evaluaciones</p>
            </div>
            <button type="button" class="button" @click="duplicating = true"><Copy :size="17" />Duplicar para otro curso académico</button>
        </header>
        <div class="section-switcher group-tabs" role="tablist" aria-label="Configuración del grupo" @keydown="navigateTabs">
            <button v-for="tab in tabs" :id="`group-tab-${tab.id}`" :key="tab.id" type="button" role="tab" :aria-selected="activeTab === tab.id" :aria-controls="`group-panel-${tab.id}`" :tabindex="activeTab === tab.id ? 0 : -1" :title="tab.label" @click="selectTab(tab.id)"><component :is="tab.icon" :size="16" aria-hidden="true" /><span>{{ tab.label }}</span></button>
        </div>

        <section v-if="activeTab === 'settings'" id="group-panel-settings" role="tabpanel" aria-labelledby="group-tab-settings" class="group-panels">
            <form class="panel" @submit.prevent="saveGroup">
                <h2>Datos del grupo</h2>
                <div class="form-grid">
                    <label class="span-2">Nombre<input v-model="groupForm.name" required maxlength="100" :disabled="!canManage" /></label>
                    <label>Ciclo<input :value="group.cycle_name" disabled /></label>
                    <label>Curso / nivel<input :value="`${group.level}.º`" disabled /></label>
                    <label v-if="isAdmin" class="span-2">Propietario<select v-model="groupForm.owner_id" :disabled="!canManage"><option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option></select></label>
                    <p v-else class="helper span-2">Propietario: {{ ownerName }}</p>
                </div>
                <p class="helper">El ciclo y el nivel no se pueden cambiar. Crea otro grupo para cambiarlos.</p>
                <p v-for="error in groupForm.errors" class="notice error" role="alert">{{ error }}</p>
                <button v-if="canManage" class="button primary" :disabled="groupForm.processing">Guardar datos</button>
            </form>
            <form class="panel" @submit.prevent="savePeriods">
                <h2>Evaluaciones</h2>
                <p class="helper">Las evaluaciones con retos conservan su nombre y no se pueden eliminar.<template v-if="writable && !canManage"> Solo el propietario del grupo o administración pueden cambiarlas.</template></p>
                <div v-for="(period, index) in periodsForm.periods" :key="period.id ?? `new-${index}`" class="level-editor period-editor">
                    <label>Evaluación {{ index + 1 }}<input v-model="period.name" :readonly="!!period.challenges_count || !canManage" required maxlength="100" /></label>
                    <button v-if="canManage" type="button" class="icon-button" :aria-label="`Quitar evaluación ${index + 1}`" :disabled="!!period.challenges_count || periodsForm.periods.length === 1" @click="periodsForm.periods.splice(index, 1)"><X :size="16" /></button>
                </div>
                <p v-for="error in periodsForm.errors" class="notice error" role="alert">{{ error }}</p>
                <div v-if="canManage" class="actions">
                    <button type="button" class="button" :disabled="periodsForm.periods.length >= 12" @click="periodsForm.periods.push({ id: undefined, name: '', challenges_count: 0 })">Añadir evaluación</button>
                    <button class="button primary" :disabled="periodsForm.processing">Guardar evaluaciones</button>
                </div>
            </form>
        </section>

        <section v-if="activeTab === 'modules'" id="group-panel-modules" role="tabpanel" aria-labelledby="group-tab-modules" class="panel">
            <h2>Módulos · {{ group.modules.length }}</h2>
            <form v-if="canManage && availableModules.length" class="group-inline-form" @submit.prevent="setModule(moduleToAdd, true)">
                <label>Añadir módulo<select v-model="moduleToAdd" required><option value="" disabled>Selecciona un módulo</option><option v-for="module in availableModules" :key="module.id" :value="module.id">{{ module.code }} · {{ module.name }}</option></select></label>
                <button class="button" :disabled="moduleForm.processing">Añadir</button>
            </form>
            <template v-if="canManage">
                <button type="button" class="button small" :aria-expanded="addingModule" @click="addingModule = !addingModule">{{ addingModule ? 'Cancelar' : 'Crear módulo en el catálogo' }}</button>
                <CatalogQuickAdd v-if="addingModule" kind="module" :items="catalogItems" :cycle-id="group.cycle_id" :level="group.level" @created="useCatalogModule" @select="useCatalogModule" @conflict="router.reload({ only: ['catalog'] })" />
            </template>
            <p v-for="error in moduleForm.errors" class="notice error" role="alert">{{ error }}</p>
            <p v-if="!group.modules.length" class="helper">El grupo no tiene módulos activos.</p>
            <div v-for="module in group.modules" :key="module.id" class="breakdown-row">
                <span>{{ module.code }} · {{ module.name }}</span>
                <button v-if="canManage" class="button small" :disabled="moduleForm.processing" :aria-label="`Quitar ${module.code}`" @click="setModule(module.id, false)">Quitar</button>
            </div>
            <p v-if="group.retired_modules.length" class="helper">Retirados: {{ group.retired_modules.map(module => module.code).join(', ') }}.</p>
            <p class="helper">Los módulos activos se pueden elegir en los retos del grupo; cada reto lo evalúa su profesorado participante. Al quitar un módulo, sus retos y notas se conservan.<template v-if="writable && !canManage"> Solo el propietario del grupo o administración pueden cambiar los módulos.</template></p>
        </section>

        <section v-if="activeTab === 'teachers'" id="group-panel-teachers" role="tabpanel" aria-labelledby="group-tab-teachers" class="panel">
            <h2>Profesores · {{ group.users.length }}</h2>
            <form v-if="canManage" @submit.prevent="saveGroup">
                <p class="helper">Marca el profesorado que trabaja con este grupo. El propietario siempre forma parte del grupo.</p>
                <div class="group-teacher-list">
                    <label v-for="teacher in teachers" :key="teacher.id" class="check"><input v-model="groupForm.user_ids" type="checkbox" :value="teacher.id" :disabled="teacher.id === group.owner_id" />{{ teacher.name }}<small v-if="teacher.id === group.owner_id" class="muted">Propietario</small></label>
                </div>
                <p v-for="error in groupForm.errors" class="notice error" role="alert">{{ error }}</p>
                <button class="button primary" :disabled="groupForm.processing">Guardar profesores</button>
            </form>
            <template v-else>
                <div v-for="teacher in groupTeachers" :key="teacher.id" class="breakdown-row"><span>{{ teacher.name }}</span><small v-if="teacher.id === group.owner_id" class="muted">Propietario</small></div>
                <p class="helper">Solo el propietario del grupo o administración pueden cambiar el profesorado.</p>
            </template>
        </section>

        <section v-if="activeTab === 'students'" id="group-panel-students" role="tabpanel" aria-labelledby="group-tab-students" class="panel">
            <h2>Estudiantes · {{ group.students.length }}</h2>
            <form v-if="writable" class="group-inline-form" @submit.prevent="enroll">
                <StudentSearch v-model="enrollment.email" :classroom-id="group.id" />
                <button class="button" :disabled="enrollment.processing">Matricular</button>
            </form>
            <p v-for="error in enrollment.errors" class="notice error" role="alert">{{ error }}</p>
            <p v-for="error in withdrawal.errors" class="notice error" role="alert">{{ error }}</p>
            <p v-if="!students.length" class="helper">Todavía no hay estudiantes matriculados.</p>
            <div v-for="student in students" :key="student.id" class="breakdown-row">
                <span>{{ student.name }}<small class="helper">{{ student.email }}</small></span>
                <button v-if="writable" class="button small" :disabled="withdrawal.processing" :aria-label="`Desmatricular a ${student.name}`" @click="withdraw(student)">Desmatricular</button>
            </div>
            <p class="helper">La baja conserva la cuenta, las otras matrículas y las notas anteriores. Para importar varios estudiantes a la vez, usa Organización → Estudiantes.</p>
        </section>

        <Modal v-if="duplicating" :title="`Duplicar ${group.name} para otro curso académico`" wide @close="duplicating = false">
            <GroupForm mode="duplicate" :submit-url="duplicateUrl" :years="openYears" :teachers="teachers" :source="group" />
        </Modal>
    </Layout>
</template>

<style scoped>
.group-tabs { display: inline-flex; flex-wrap: wrap; margin-bottom: 20px; }
.section-switcher button { display: inline-flex; align-items: center; gap: 7px; min-height: 34px; padding: 6px 12px; border-radius: 7px; color: #7a6b76; font-size: 12px; font-weight: 500; white-space: nowrap; }
.section-switcher { gap: 2px; padding: 3px; border: 1px solid var(--border); border-radius: 10px; background: #fff; }
.section-switcher button:hover { background: #f6eff4; color: #4d3a47; }
.section-switcher button[aria-selected=true] { background: #82005e; color: #fff; }
.group-panels { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; align-items: start; }
.panel h2 { margin-bottom: 12px; }
.panel .helper { display: block; overflow-wrap: anywhere; }
.breakdown-row { flex-wrap: wrap; gap: .5rem; }
.breakdown-row > span { flex: 1 1 180px; min-width: 0; overflow-wrap: anywhere; }
.breakdown-row small.helper { margin: 2px 0 0; }
.period-editor > label:first-child { flex: 1; width: auto; min-width: 0; }
.group-inline-form { display: flex; align-items: end; flex-wrap: wrap; gap: .5rem; margin-bottom: .75rem; }
.group-inline-form label { flex: 1 1 220px; min-width: 0; max-width: 420px; margin: 0; }
.group-teacher-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0 18px; margin-bottom: 12px; }
.group-teacher-list small { margin-left: 4px; }
@media (max-width: 900px) { .group-panels { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 650px) {
    .section-switcher button { padding: 6px 10px; }
    .section-switcher button span { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
}
</style>
