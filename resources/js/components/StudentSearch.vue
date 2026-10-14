<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { api } from '../lib';

type Student = { id: number; name: string; email: string };
const props = defineProps<{ classroomId: number; disabled?: boolean }>();
const email = defineModel<string>({ required: true });

const MIN_CHARACTERS = 3;
const options = ref<Student[]>([]);
const open = ref(false);
const loading = ref(false);
const error = ref('');
const highlighted = ref(-1);
const selected = ref<Student | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;
let request = 0;

const query = computed(() => email.value.trim());
const status = computed(() => {
    if (error.value) return error.value;
    if (query.value.length < MIN_CHARACTERS) return '';
    if (loading.value) return 'Buscando estudiantes…';
    return options.value.length ? `${options.value.length} estudiantes encontrados.` : 'No hay estudiantes sin matricular que coincidan.';
});

watch(query, value => {
    clearTimeout(timer);
    if (selected.value?.email === value) return;
    selected.value = null;
    highlighted.value = -1;
    error.value = '';
    if (value.length < MIN_CHARACTERS) {
        options.value = [];
        open.value = false;
        return;
    }
    timer = setTimeout(() => search(value), 250);
});
onBeforeUnmount(() => clearTimeout(timer));

async function search(value: string): Promise<void> {
    const current = ++request;
    loading.value = true;
    open.value = true;
    try {
        const data = await api(`/students/search?${new URLSearchParams({ classroom_id: String(props.classroomId), q: value })}`);
        if (current === request) options.value = data.students;
    } catch (exception) {
        if (current === request) { options.value = []; error.value = (exception as Error).message; }
    } finally {
        if (current === request) loading.value = false;
    }
}

function choose(student: Student): void {
    selected.value = student;
    email.value = student.email;
    open.value = false;
    highlighted.value = -1;
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') { open.value = false; return; }
    if (!open.value || !options.value.length) return;
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        const step = event.key === 'ArrowDown' ? 1 : -1;
        highlighted.value = (highlighted.value + step + options.value.length) % options.value.length;
    } else if (event.key === 'Enter' && highlighted.value >= 0) {
        event.preventDefault();
        choose(options.value[highlighted.value]);
    }
}

defineExpose({ reset: () => { selected.value = null; options.value = []; open.value = false; } });
</script>

<template>
    <div class="student-search">
        <label :for="`student-search-${classroomId}`">Estudiante (nombre o correo)</label>
        <input
            :id="`student-search-${classroomId}`"
            v-model="email"
            type="text"
            role="combobox"
            autocomplete="off"
            required
            :disabled="disabled"
            placeholder="Escribe al menos 3 letras…"
            aria-autocomplete="list"
            :aria-expanded="open"
            :aria-controls="`student-options-${classroomId}`"
            :aria-activedescendant="highlighted >= 0 ? `student-option-${options[highlighted].id}` : undefined"
            @keydown="onKeydown"
            @focus="open = query.length >= MIN_CHARACTERS && !selected"
            @blur="open = false"
        />
        <ul v-show="open && options.length" :id="`student-options-${classroomId}`" role="listbox" aria-label="Estudiantes encontrados" class="student-options">
            <li v-for="(student, index) in options" :id="`student-option-${student.id}`" :key="student.id" role="option" :aria-selected="index === highlighted" :class="{ highlighted: index === highlighted }" @mousedown.prevent="choose(student)" @mouseenter="highlighted = index">
                <strong>{{ student.name }}</strong><small>{{ student.email }}</small>
            </li>
        </ul>
        <p class="sr-only" role="status" aria-live="polite">{{ status }}</p>
        <p v-if="open && !options.length && status" class="student-search-status" :class="{ 'field-error': !!error }">{{ status }}</p>
    </div>
</template>

<style scoped>
.student-search { position: relative; display: flex; flex-direction: column; gap: 7px; flex: 1 1 260px; min-width: 0; max-width: 460px; font-size: 12px; font-weight: 500; color: #71616d; }
.student-options { position: absolute; top: calc(100% - 2px); left: 0; right: 0; z-index: 20; margin: 0; padding: 4px; list-style: none; background: #fff; border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 10px 30px #27162220; max-height: 280px; overflow-y: auto; }
.student-options li { display: flex; flex-direction: column; gap: 1px; padding: 8px 10px; border-radius: 6px; cursor: pointer; }
.student-options li.highlighted { background: #f6e8f2; }
.student-options strong { font-size: 12px; font-weight: 600; color: #2b1f28; }
.student-options small { font-size: 11px; color: var(--muted); overflow-wrap: anywhere; }
.student-search-status { position: absolute; top: 100%; left: 0; right: 0; z-index: 20; margin: 0; padding: 9px 11px; background: #fff; border: 1px solid var(--border); border-radius: 8px; font-size: 11px; font-weight: 400; color: var(--muted); }
.student-search label { margin: 0; }
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
</style>
