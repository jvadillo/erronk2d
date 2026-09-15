<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from './Modal.vue';
type Registration = {id:number;name:string;email:string;review_url:string};
defineProps<{registrations:Registration[];classrooms:any[]}>();
const selected = ref<Registration|null>(null);
const form = useForm({decision:'approve',role:'student',classroom_id:''});
function review(registration:Registration) {selected.value=registration;form.reset();form.clearErrors()}
function submit() {if(selected.value)form.post(selected.value.review_url,{onSuccess:()=>selected.value=null})}
</script>
<template>
  <section>
    <h2>Solicitudes de registro con Google</h2>
    <p class="muted">Revisa quién solicita acceso. Los estudiantes necesitan un grupo; los profesores podrán crear grupos en los cursos abiertos.</p>
    <p v-if="!registrations.length" class="notice">No hay solicitudes pendientes.</p>
    <div v-else class="table-scroll"><table><thead><tr><th>NOMBRE</th><th>CORREO</th><th></th></tr></thead><tbody><tr v-for="registration in registrations" :key="registration.id"><td>{{registration.name}}</td><td>{{registration.email}}</td><td><button class="button" @click="review(registration)">Revisar solicitud</button></td></tr></tbody></table></div>
    <Modal v-if="selected" title="Revisar solicitud" @close="selected=null">
      <form class="form-grid" @submit.prevent="submit">
        <p class="span-2">{{selected.name}} · {{selected.email}}</p>
        <label class="span-2">Decisión<select v-model="form.decision"><option value="approve">Aprobar acceso</option><option value="reject">Rechazar solicitud</option></select></label>
        <template v-if="form.decision==='approve'">
          <label>Rol<select v-model="form.role"><option value="student">Estudiante</option><option value="teacher">Profesor</option></select></label>
          <label v-if="form.role==='student'">Grupo<select v-model="form.classroom_id" required><option value="">Selecciona un grupo</option><option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">{{classroom.name}}</option></select></label>
        </template>
        <p v-for="(error,key) in form.errors" :key="key" class="notice error span-2" role="alert">{{error}}</p>
        <button class="button primary span-2" :disabled="form.processing">{{form.processing?'Guardando…':form.decision==='approve'?'Aprobar cuenta':'Rechazar solicitud'}}</button>
      </form>
    </Modal>
  </section>
</template>
