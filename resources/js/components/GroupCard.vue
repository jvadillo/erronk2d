<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Pencil } from 'lucide-vue-next';

const props = defineProps<{ group: any; catalog: any[]; writable: boolean }>();
const emit = defineEmits<{ edit: [group: any]; periods: [group: any]; responsibility: [data: any] }>();
const enrollment = useForm({ classroom_id: props.group.id, email: '', active: true });
const withdrawal = useForm({ classroom_id: props.group.id, email: '', active: false });
const moduleForm = useForm({ classroom_id: props.group.id, module_id: '', active: true });
const moduleToAdd = ref('');
const availableModules = computed(() => props.catalog.filter(module => module.cycle_id === props.group.cycle_id && module.level === props.group.level && !props.group.modules.some((active: any) => active.id === module.id)));
function enroll() {
    enrollment.post('/setup/enrollment', { preserveScroll: true, onSuccess: () => enrollment.reset('email') });
}
function withdraw(student: any) {
    withdrawal.email = student.email;
    withdrawal.post('/setup/enrollment', { preserveScroll: true });
}
function setModule(id: string | number, active: boolean) {
    moduleForm.module_id = String(id);
    moduleForm.active = active;
    moduleForm.post('/setup/group-module', { preserveScroll: true, onSuccess: () => { moduleToAdd.value = ''; } });
}
</script>

<template>
    <section class="panel group-card">
        <div class="actions">
            <h3>{{ group.name }}</h3>
            <span class="muted">{{ group.cycle_name }} · {{ group.level }}.º</span>
            <button v-if="writable && group.can_manage" class="icon-button" aria-label="Editar grupo" @click="emit('edit', group)"><Pencil :size="16" /></button>
        </div>
        <p>{{ group.students.length }} estudiantes · {{ group.modules.length }} módulos · {{ group.periods.length }} evaluaciones</p>
        <div class="module-chips"><span v-for="period in group.periods" :key="period.id">{{ period.name }}</span></div>
        <button v-if="writable" class="button small" @click="emit('periods', group)">Configurar evaluaciones</button>
        <p class="helper">Profesorado: {{ group.users.map((teacher: any) => teacher.name).join(', ') }}</p>
        <details>
            <summary>Estudiantes · {{ group.students.length }}</summary>
            <form v-if="writable" class="group-inline-form" @submit.prevent="enroll">
                <label>Correo del estudiante<input v-model="enrollment.email" type="email" required placeholder="estudiante@centro.edu" /></label>
                <button class="button small" :disabled="enrollment.processing">Matricular</button>
            </form>
            <p v-for="error in enrollment.errors" class="notice error" role="alert">{{ error }}</p>
            <p v-for="error in withdrawal.errors" class="notice error" role="alert">{{ error }}</p>
            <p v-if="!group.students.length" class="helper">Todavía no hay estudiantes matriculados.</p>
            <div class="scroll-list">
                <div v-for="student in group.students" :key="student.id" class="breakdown-row">
                    <span>{{ student.name }}<small class="helper">{{ student.email }}</small></span>
                    <button v-if="writable" class="button small" :disabled="withdrawal.processing" :aria-label="`Desmatricular a ${student.name}`" @click="withdraw(student)">Desmatricular</button>
                </div>
            </div>
            <p class="helper">La baja conserva la cuenta, las otras matrículas y las notas anteriores.</p>
        </details>
        <details open>
            <summary>Módulos · {{ group.modules.length }}</summary>
            <form v-if="writable && availableModules.length" class="group-inline-form" @submit.prevent="setModule(moduleToAdd, true)">
                <label>Añadir módulo<select v-model="moduleToAdd" required><option value="" disabled>Selecciona un módulo</option><option v-for="module in availableModules" :key="module.id" :value="module.id">{{ module.code }} · {{ module.name }}</option></select></label>
                <button class="button small" :disabled="moduleForm.processing">Añadir</button>
            </form>
            <p v-for="error in moduleForm.errors" class="notice error" role="alert">{{ error }}</p>
            <p v-if="!group.modules.length" class="helper">El grupo no tiene módulos activos.</p>
            <div v-for="module in group.modules" :key="module.id" class="breakdown-row">
                <span>{{ module.code }} · {{ module.name }}<small class="helper">{{ module.teachers.map((teacher: any) => teacher.name).join(', ') || 'Sin responsables' }}</small></span>
                <div class="actions">
                    <button v-if="writable && group.can_manage" class="button small" @click="emit('responsibility', { classroom_id: group.id, module_id: module.id, teacher_ids: module.teachers.map((teacher: any) => teacher.id) })">Responsables</button>
                    <button v-if="writable" class="button small" :disabled="moduleForm.processing" :aria-label="`Quitar ${module.code}`" @click="setModule(module.id, false)">Quitar</button>
                </div>
            </div>
            <p v-if="group.retired_modules.length" class="helper">Retirados: {{ group.retired_modules.map((module: any) => module.code).join(', ') }}.</p>
            <p class="helper">Al quitar un módulo, sus retos y notas se conservan. Puedes volver a añadirlo.</p>
        </details>
    </section>
</template>

<style scoped>
.group-card { min-width: 0; }
.group-card details { margin-top: 1rem; }
.group-card summary { cursor: pointer; font-weight: 600; margin-bottom: .75rem; }
.group-card .helper { display: block; overflow-wrap: anywhere; }
.group-card .breakdown-row { flex-wrap: wrap; gap: .5rem; }
.group-card .breakdown-row > span { flex: 1 1 180px; min-width: 0; overflow-wrap: anywhere; }
.group-inline-form { display: flex; align-items: end; flex-wrap: wrap; gap: .5rem; margin-bottom: .75rem; }
.group-inline-form label { flex: 1 1 180px; min-width: 0; }
.group-inline-form select, .group-inline-form input { width: 100%; min-width: 0; }
</style>
