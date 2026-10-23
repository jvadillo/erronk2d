<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Plus, Search, ArrowUpRight, Layers, ArrowRight } from 'lucide-vue-next';
import Layout from '../components/Layout.vue'; import Modal from '../components/Modal.vue';
import { permission, statusLabel } from '../lib';
const props=defineProps<{challenges:any[];classrooms:any[];rubrics:any[]}>();
const search=ref(''),filter=ref('all'),creating=ref(false);
const filtered=computed(()=>props.challenges.filter(c=>(filter.value==='all'||c.status===filter.value)&&`${c.name} ${c.classroom} ${c.year} ${c.period}`.toLowerCase().includes(search.value.toLowerCase())));
const form=useForm({name:'',description:'',classroom_id:props.classrooms[0]?.id??'',period_id:'',module_ids:[] as number[],team_rubric_id:'',transversal_rubric_id:'',weight:'1',distribution_enabled:true});
const selectedClass=computed(()=>props.classrooms.find(c=>c.id===Number(form.classroom_id)));
const rubricModules=(rubric:any)=>[...new Set<number>(rubric.items.filter((item:any)=>item.module_id).map((item:any)=>Number(item.module_id)))];
const compatibleRubric=(rubric:any)=>(!rubric.cycle_id||(rubric.cycle_id===selectedClass.value?.cycle_id&&rubric.level===selectedClass.value?.level))&&rubricModules(rubric).every(id=>form.module_ids.map(Number).includes(id));
const rubricRequirements=(rubric:any)=>rubricModules(rubric).map(id=>props.classrooms.flatMap(c=>c.modules).find((module:any)=>module.id===id)?.code??'otro módulo').join(', ');
watch(()=>[form.classroom_id,...form.module_ids],()=>{for(const field of ['team_rubric_id','transversal_rubric_id'] as const){const selected=props.rubrics.find(r=>r.id===Number(form[field]));if(selected&&!compatibleRubric(selected))form[field]=''}});
function create(){form.transform(data=>({...data,team_rubric_id:data.team_rubric_id==='new'?null:data.team_rubric_id,transversal_rubric_id:data.transversal_rubric_id==='new'?null:data.transversal_rubric_id})).post('/challenges',{onSuccess:()=>creating.value=false})}
const teacher=(usePage().props.auth as any).role!=='student';
</script>
<template><Layout><Head title="Tus retos."/>
  <div class="topline"><span>Tu espacio de trabajo</span><span class="quiet-tag">METODOLOGÍA ERRONK2D</span></div>
  <header class="page-heading"><div><h1>Tus retos<span class="accent">.</span></h1><p class="muted">Organiza el trabajo. Acompaña el proceso. Evalúa con claridad.</p></div><button v-if="permission('manage_challenges')" class="button primary" @click="creating=true"><Plus :size="18"/>Nuevo reto</button></header>
  <div class="section-line"><div class="tabs"><button :class="{selected:filter==='all'}" @click="filter='all'">Todos los retos <span>{{challenges.length}}</span></button><button :class="{selected:filter==='active'}" @click="filter='active'">En curso</button><button :class="{selected:filter==='evaluating'}" @click="filter='evaluating'">En evaluación</button><button :class="{selected:filter==='published'}" @click="filter='published'">Publicados</button></div><div class="search-box"><Search :size="17"/><input v-model="search" aria-label="Buscar retos" placeholder="Buscar reto, grupo o curso…"/></div></div>
  <div v-if="!challenges.length" class="empty-state"><Layers :size="36"/><h2>Todo empieza con un reto</h2><p>{{teacher?'Crea tu grupo en el curso académico activo y prepara las rúbricas de tu primer reto.':'Tus retos aparecerán aquí cuando el profesorado te incorpore.'}}</p><Link v-if="teacher" href="/setup" class="button primary">Organizar el curso <ArrowRight :size="17"/></Link></div>
  <div v-else class="challenge-grid"><Link v-for="ch in filtered" :key="ch.id" :href="`/challenges/${ch.id}`" class="challenge-card" :class="`is-${ch.status}`"><div class="card-top"><span class="card-icon"><Layers :size="19"/></span><span class="badge" :class="ch.status">{{statusLabel[ch.status]}}</span></div><h2>{{ch.name}}</h2><p class="card-description">{{ch.description||'Un nuevo proyecto para aprender, colaborar y avanzar.'}}</p><div class="module-chips"><span v-for="m in ch.modules">{{m}}</span></div><dl class="card-facts"><div><dt>Grupo</dt><dd>{{ch.classroom}}</dd></div><div><dt>Evaluación</dt><dd>{{ch.period}}</dd></div><div><dt>Equipos</dt><dd>{{ch.teams_count}}</dd></div></dl><footer><span>{{ch.year}}</span><span class="card-open">Abrir reto <ArrowUpRight :size="16"/></span></footer></Link></div>
  <p v-if="challenges.length&&!filtered.length" class="empty-state">No hay retos que coincidan con tu búsqueda.</p>
  <footer class="page-footer">Cada nota cuenta una historia. Aquí puedes ver el proceso completo.<span>ERRONK2D</span></footer>
  <Modal v-if="creating" title="Crear un reto" @close="creating=false"><form @submit.prevent="create" class="form-grid"><label class="span-2">Nombre del reto<input v-model="form.name" required placeholder="Por ejemplo, una web para nuestra comunidad"/></label><label class="span-2">Descripción<textarea v-model="form.description" rows="2"/></label><label>Grupo<select v-model="form.classroom_id" required @change="form.period_id='';form.module_ids=[]"><option v-for="c in classrooms" :value="c.id">{{c.name}} · {{c.academic_year.name}}</option></select></label><label>Evaluación<select v-model="form.period_id" required><option value="" disabled>Selecciona</option><option v-for="p in selectedClass?.periods" :value="p.id">{{p.name}}</option></select></label><fieldset class="span-2"><legend>Módulos participantes</legend><label v-for="m in selectedClass?.modules" class="check"><input v-model="form.module_ids" :value="m.id" type="checkbox"/>{{m.code}} · {{m.name}}</label></fieldset><label>Rúbrica del reto<select v-model="form.team_rubric_id" required><option value="" disabled>Selecciona</option><option value="new">Crear nueva rúbrica</option><option v-for="r in rubrics.filter(r=>r.kind==='team')" :value="r.id" :disabled="!compatibleRubric(r)">{{r.name}}{{compatibleRubric(r)?'':` · Requiere: ${rubricRequirements(r)}`}}</option></select></label><label>Rúbrica transversal<select v-model="form.transversal_rubric_id" required><option value="" disabled>Selecciona</option><option value="new">Crear nueva rúbrica</option><option v-for="r in rubrics.filter(r=>r.kind==='transversal'&&compatibleRubric(r))" :value="r.id">{{r.name}}</option></select></label><label>Peso dentro de la Evaluación<input v-model="form.weight" type="number" min="0.0001" step="0.0001" required/></label><label class="check"><input v-model="form.distribution_enabled" type="checkbox"/>Reparto individual de puntos</label><p class="helper span-2">Selecciona los módulos antes de elegir la rúbrica. Solo están disponibles las compatibles con esos módulos. Puedes crear una rúbrica vacía y añadir sus criterios desde la evaluación. Las rúbricas existentes se copian al reto para conservar su histórico. Las defensas se activan por defecto en todos los módulos.</p><p v-for="error in form.errors" class="notice error span-2" role="alert">{{error}}</p><button class="button primary span-2" :disabled="form.processing">Crear reto</button></form></Modal>
</Layout></template>

<style scoped>
.challenge-card { position: relative; overflow: hidden; min-height: 0; padding: 22px 22px 0; border-radius: 14px; transition: border-color .15s, box-shadow .15s, transform .15s; }
.challenge-card::before { content: ""; position: absolute; inset: 0 0 auto; height: 4px; background: #d9cfd6; }
.challenge-card.is-active::before { background: #82005e; }
.challenge-card.is-evaluating::before { background: #b0418f; }
.challenge-card.is-published::before { background: #4b826d; }
.challenge-card.is-finished::before { background: #50778a; }
.challenge-card:hover { transform: translateY(-2px); border-color: #dcc6d6; box-shadow: 0 12px 30px #4a0a3514; }
.challenge-card:focus-visible { outline: 3px solid #b0418f; outline-offset: 2px; }
.card-top { margin-bottom: 16px; }
.card-icon { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 10px; background: #f7e4f0; color: var(--green); }
.challenge-card h2 { margin-bottom: 6px; color: #2b1f28; font-size: 18px; line-height: 1.3; letter-spacing: -.3px; }
.card-description { min-height: 0; margin-bottom: 14px; color: var(--muted); font-size: 12px; line-height: 1.55; }
.module-chips { margin: 0 0 18px; }
.card-facts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1px; margin: auto 0 16px; overflow: hidden; border: 1px solid #f0e8ee; border-radius: 10px; background: #f0e8ee; }
.card-facts div { min-width: 0; padding: 9px 12px; background: #fbf8fa; }
.card-facts dt { color: var(--muted); font-size: 10px; }
.card-facts dd { margin: 2px 0 0; overflow: hidden; color: #2b1f28; font-size: 13px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
.challenge-card footer { margin: 0 -22px; padding: 12px 22px; color: var(--muted); font-size: 11px; }
.card-open { gap: 4px; color: var(--green); font-weight: 500; }
.card-open svg { transition: transform .15s; }
.challenge-card:hover .card-open svg { transform: translate(2px, -2px); }
@media (prefers-reduced-motion: reduce) {
  .challenge-card, .card-open svg { transition: none; }
  .challenge-card:hover { transform: none; }
}
</style>
