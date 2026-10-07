<script setup lang="ts">
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Search, SlidersHorizontal, Check, Users, BookOpen, GraduationCap, AlertCircle, Upload, Download, History, X, ChevronRight } from 'lucide-vue-next';
import EvidenceWorkspace from '../components/EvidenceWorkspace.vue';
import RubricAssessment from '../components/RubricAssessment.vue';
import ModuleCriteriaNotice from '../components/ModuleCriteriaNotice.vue';
import Layout from '../components/Layout.vue'; import Modal from '../components/Modal.vue'; import GradeInput from '../components/GradeInput.vue';
import { api, grade, permission, type Auth } from '../lib';
const props=defineProps<{book:any;history:any[];rubricEditorUrls:Record<string,string>;evidences:any[];challengeUrl:string;evidenceStoreUrl:string}>();const book=ref(props.book);const auth=usePage().props.auth as Auth;
const page=usePage();
const evidenceSaving=ref(false);
const tabs=[{id:'teams',label:'Estudiantes y Equipos'},{id:'evaluation',label:'Evaluación'},{id:'evidence',label:'Evidencias'},{id:'settings',label:'Configuración'}];
const activeTab=computed(()=>{const tab=new URLSearchParams(page.url.split('?')[1]??'').get('tab');return tabs.some(item=>item.id===tab)?tab:'evaluation'});
function selectTab(tab:string):void{
  if(busy.value||evidenceSaving.value)return;
  if(tab==='evaluation')evaluating.value=false;
  router.push({url:`${props.challengeUrl}?tab=${tab}`,props:current=>({...current,book:book.value}),preserveState:true,preserveScroll:true});
}
function navigateTabs(event:KeyboardEvent):void{
  const keys=['ArrowLeft','ArrowRight','Home','End'];
  if(!keys.includes(event.key)||busy.value||evidenceSaving.value)return;
  event.preventDefault();
  const index=tabs.findIndex(tab=>tab.id===activeTab.value);
  const target=event.key==='Home'?0:event.key==='End'?tabs.length-1:(index+(event.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length;
  selectTab(tabs[target].id);
  nextTick(()=>document.getElementById(`challenge-tab-${tabs[target].id}`)?.focus());
}
const busy=ref(false),message=ref(''),error=ref(''),search=ref(''),onlyPending=ref(false),teamFilter=ref(''),sort=ref('name');
watch(()=>props.book,value=>{book.value=value;error.value='';message.value='Datos actualizados'});
const modal=ref(''),detail=ref<any>(null),rubricKind=ref('team'),allocTeam=ref<any>(null),allocations=reactive<Record<string,string>>({}),draftTeams=ref<any[]>([]),historyData=ref<any>(null),batch=ref({module_id:0,field:'exam',text:''});
const visible=reactive({transversal:true,team:true,defenses:true}); const settings=ref<any>({}); const showColumns=ref(false);
const closed=computed(()=>!(usePage().props.academic as any)?.year?.is_open||['published','finished'].includes(book.value.challenge.status));
const rows=computed(()=>book.value.rows.filter((r:any)=>(!onlyPending.value||r.pending.length)&&(!teamFilter.value||r.team_id===Number(teamFilter.value))&&r.name.toLowerCase().includes(search.value.toLowerCase())).sort((a:any,b:any)=>sort.value==='team'?(a.team_name??'').localeCompare(b.team_name??'')||a.name.localeCompare(b.name):a.name.localeCompare(b.name)));
const complete=computed(()=>book.value.rows.filter((r:any)=>r.pending.length===0).length);
const modules=computed(()=>book.value.modules);
const participatingTeachers=computed(()=>Array.from(new Set<string>(modules.value.flatMap((m:any)=>m.teachers.map((t:any)=>t.name)))));
const writable=(p:string)=>permission(p)&&!closed.value;
const moduleWritable=(p:string,m:any)=>writable(p)&&(auth.role==='admin'||m.teachers.some((t:any)=>t.id===auth.id));
let saveQueue: Promise<boolean> = Promise.resolve(true);
let queued = 0;
function save(payload:any): Promise<boolean> {
  const input = JSON.parse(JSON.stringify(payload));
  queued++; busy.value = true;
  const operation = saveQueue.then(async (previousSucceeded) => {
    if (!previousSucceeded) return false;
    error.value = ''; message.value = 'Guardando…';
    try {
      const data = await api(`/challenges/${book.value.challenge.id}`, {...input, revision:book.value.challenge.revision});
      book.value = data.book;
      return true;
    } catch(e) {
      error.value = (e as Error).message;
      return false;
    }
  });
  saveQueue = operation;
  return operation.finally(() => {
    queued--; busy.value = queued > 0;
    message.value = error.value ? '' : busy.value ? 'Guardando…' : 'Cambios guardados';
    if (!queued) saveQueue = Promise.resolve(true);
  });
}
async function saveGrade(row:any,m:any,field:string,value:string|null|boolean){await save({action:'grades',module_id:m.id,field,entries:[{student_id:row.id,value}]})}
function allocationCents(value:string|number|null|undefined):number{const [whole,fraction='']=String(value??'0').replace(',','.').split('.');const digits=(fraction+'000').slice(0,3);return Number(whole||0)*100+Number(digits.slice(0,2))+(Number(digits[2])>=5?1:0)}
function allocationValue(cents:number):string{return `${Math.floor(cents/100)}.${String(cents%100).padStart(2,'0')}`}
function openAllocation(team:any){allocTeam.value=team;Object.keys(allocations).forEach(k=>delete allocations[k]);const members=team.members;const saved=members.some((member:any)=>member.allocation!==null&&member.allocation!==undefined);let cents:number[];if(saved){cents=members.map((member:any)=>allocationCents(member.allocation??team.grade))}else{const total=allocationCents(team.points);const share=Math.floor(total/members.length);const remainder=total%members.length;cents=members.map((_:any,index:number)=>share+(index>=members.length-remainder?1:0))}members.forEach((member:any,index:number)=>allocations[member.student_id]=allocationValue(cents[index]));modal.value='allocation'}
function limitAllocation(event:Event,studentId:number){const input=event.target as HTMLInputElement;const raw=input.value.replace(/[^0-9.,]/g,'').replace(',','.');const separator=raw.indexOf('.');const whole=(separator<0?raw:raw.slice(0,separator)).slice(0,2);const fraction=separator<0?'':`.${raw.slice(separator+1).replaceAll('.','').slice(0,2)}`;allocations[studentId]=whole+fraction;input.value=allocations[studentId]}
const allocated=computed(()=>Object.values(allocations).reduce((sum,value)=>sum+allocationCents(value),0)/100);
async function submitAllocation(){if(await save({action:'allocation',team_id:allocTeam.value.id,allocations:Object.fromEntries(Object.entries(allocations).map(([k,v])=>[k,v.replace(',','.')]))}))modal.value=''}
function initializeTeams(){draftTeams.value=book.value.teams.map((t:any)=>({selecting:false,search:'',name:t.name,students:t.members.map((m:any)=>m.student_id)}));if(!draftTeams.value.length)draftTeams.value=[{selecting:false,search:'',name:'Equipo 1',students:[]}]}
const unassignedStudents=computed(()=>{const assigned=new Set<number>(draftTeams.value.flatMap((team:any)=>team.students));return book.value.rows.filter((student:any)=>!assigned.has(student.id)).sort((a:any,b:any)=>a.name.localeCompare(b.name,'es',{sensitivity:'base'})||a.id-b.id)});
function normalizeStudentSearch(value:string):string{return value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es').trim()}
function availableStudents(team:any):any[]{const query=normalizeStudentSearch(team.search);return unassignedStudents.value.filter((student:any)=>normalizeStudentSearch(student.name).includes(query))}
function teamMembers(team:any):any[]{return book.value.rows.filter((student:any)=>team.students.includes(student.id)).sort((a:any,b:any)=>a.name.localeCompare(b.name,'es',{sensitivity:'base'})||a.id-b.id)}
function toggleTeamPicker(team:any,index:number):void{team.selecting=!team.selecting;team.search='';if(team.selecting){nextTick(()=>document.getElementById(`team-student-search-${index}`)?.focus())}}
function addStudentToTeam(team:any,student:any):void{if(!unassignedStudents.value.some((available:any)=>available.id===student.id))return;team.students.push(student.id);team.search=''}
const teamNameErrors=computed(()=>draftTeams.value.map((team:any)=>!team.name.trim()?'Escribe un nombre para el equipo.':draftTeams.value.filter((other:any)=>other.name.trim()===team.name.trim()).length>1?'Los nombres de los equipos deben ser distintos.':''));
const teamErrors=computed(()=>draftTeams.value.map((team:any,index:number)=>teamNameErrors.value[index]||(team.students.length<2||team.students.length>5?'Selecciona entre 2 y 5 estudiantes.':'')));
const evaluationQuery=new URLSearchParams(usePage().url.split('?')[1]??'');
const initialEvaluation=evaluationQuery.get('evaluation');
if(initialEvaluation==='team'||initialEvaluation==='teacher')rubricKind.value=initialEvaluation;
const evaluating=ref(initialEvaluation==='team'||initialEvaluation==='teacher'),evaluationSubject=ref<number|null>(Number(evaluationQuery.get('subject'))||null),evaluationHeading=ref<HTMLElement|null>(null);
const evaluationOverviewHeading=ref<HTMLElement|null>(null);
function closeRubric():void{
  if(busy.value||evidenceSaving.value)return;
  selectTab('evaluation');
  nextTick(()=>evaluationOverviewHeading.value?.focus());
}
function openRubric(kind:string,subjectId?:number){rubricKind.value=kind;evaluationSubject.value=subjectId??subjects.value[0]?.id??null;evaluating.value=true;nextTick(()=>evaluationHeading.value?.focus())}
function editRubric(){if(busy.value||closed.value)return;router.visit(props.rubricEditorUrls[rubricKind.value==='team'?'team':'transversal'],{data:{subject:evaluationSubject.value??''}})}
const currentSubject=computed(()=>subjects.value.find((subject:any)=>subject.id===Number(evaluationSubject.value)));
const evaluationSelections=computed(()=>Object.fromEntries(rubric.value.items.map((item:any)=>[item.key,selected(Number(evaluationSubject.value),item.key)])));
const disabledCriteria=computed(()=>rubricKind.value==='team'?rubric.value.items.filter((item:any)=>item.module_id&&auth.role!=='admin'&&!modules.value.find((module:any)=>module.id===Number(item.module_id))?.teachers.some((teacher:any)=>teacher.id===auth.id)).map((item:any)=>item.key):[]);
function changeSubject(by:number){const index=subjects.value.findIndex((subject:any)=>subject.id===Number(evaluationSubject.value));const target=subjects.value[index+by];if(target)evaluationSubject.value=target.id}
const rubric=computed(()=>rubricKind.value==='team'?book.value.challenge.team_rubric:book.value.challenge.transversal_rubric);
const subjects=computed(()=>rubricKind.value==='team'?book.value.teams:book.value.rows);
watch(subjects,available=>{if(!available.some((subject:any)=>subject.id===evaluationSubject.value))evaluationSubject.value=available[0]?.id??null},{immediate:true});
function selected(subject:number,key:string){return book.value.assessments.find((a:any)=>a.kind===rubricKind.value&&a.subject_id===subject&&a.criterion===key)?.level}
function initializeSettings(){settings.value=JSON.parse(JSON.stringify({...book.value.challenge,defenses:Object.fromEntries(modules.value.map((m:any)=>[m.id,m.defense_enabled])),reason:''}))}
async function history(){error.value='';try{historyData.value=await api(`/challenges/${book.value.challenge.id}/history`);modal.value='history'}catch(e){error.value=(e as Error).message}}
async function submitBatch(){const entries=batch.value.text.trim().split('\n').filter(Boolean).map((line,i)=>({student_id:rows.value[i]?.id,value:line.trim()===''?null:line.trim().replace(',','.')}));if(entries.length!==rows.value.length){error.value='Introduce una nota por cada estudiante de la lista filtrada, en el mismo orden.';return}if(await save({action:'grades',module_id:batch.value.module_id,field:batch.value.field,entries}))modal.value=''}
function exportCsv(){const data=[['Estudiante','Equipo','Transversales','Reparto','Defensas','Reto final',...modules.value.flatMap((m:any)=>[`Examen ${m.code}`,`Final ${m.code}`])],...rows.value.map((r:any)=>[r.name,r.team_name,r.transversal,r.allocation,r.defenses_total,r.challenge_final,...modules.value.flatMap((m:any)=>[r.modules[m.id].not_enrolled?'No matriculado':r.modules[m.id].exam,r.modules[m.id].not_enrolled?'No matriculado':r.modules[m.id].final])])];const escape=(v:any)=>{let s=String(v??'Pendiente');if(/^[=+@\-\t\r]/.test(s))s="'"+s;return '"'+s.replaceAll('"','""')+'"'};const blob=new Blob(['\ufeff'+data.map(row=>row.map(escape).join(';')).join('\r\n')],{type:'text/csv;charset=utf-8'});const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='erronk2d-reto.csv';a.click();URL.revokeObjectURL(a.href)}
initializeTeams();
initializeSettings();
const reopenReason=ref('');
const defenseDetail=ref<any>({});function openDefense(row:any,m:any){defenseDetail.value={student_id:row.id,name:row.name,module:m,not_enrolled:row.modules[m.id].not_enrolled,value:row.modules[m.id].defense??'',date:row.modules[m.id].defense_date??new Date().toISOString().slice(0,10),notes:row.modules[m.id].defense_notes??''};modal.value='defense'}
async function submitDefense(){const d=defenseDetail.value;if(await save({action:'grades',field:'defense',module_id:d.module.id,entries:[{student_id:d.student_id,value:d.value===''?null:String(d.value).replace(',','.'),date:d.date,notes:d.notes}]}))modal.value=''}
</script>
<template><Layout><Head :title="book.challenge.name"/>
<div class="topline"><button v-if="activeTab==='evaluation'&&evaluating" class="back-link" :disabled="busy" @click="closeRubric"><ArrowLeft :size="16"/>Atrás</button><Link v-else href="/" class="back-link"><ArrowLeft :size="16"/>Todos los retos</Link><span>{{book.year}} / {{book.classroom}} / {{book.period}}</span></div>
<header class="page-heading compact"><div><h1>{{book.challenge.name}}</h1><p class="muted">{{book.description||book.challenge.description||'Una visión completa del aprendizaje de tu grupo.'}}</p></div><div class="actions"><button v-if="false" class="button" @click="history"><History :size="17"/>Histórico</button><button v-if="permission('publish_results')&&!closed" :disabled="busy||evidenceSaving" class="button primary" @click="modal='publish'"><Upload :size="17"/>Publicar</button><button v-if="permission('publish_results')&&closed" :disabled="busy||evidenceSaving" class="button" @click="reopenReason='';modal='reopen'">Reabrir</button></div></header>
<section class="challenge-summary"><div><Users :size="20"/><strong>{{book.rows.length}}</strong><span>estudiantes</span></div><div><strong>{{book.teams.length}}</strong><span>equipos</span></div><div><button type="button" class="teacher-stat" :aria-label="`Ver ${participatingTeachers.length} ${participatingTeachers.length===1?'profesor participante':'profesores participantes'}`" @click="modal='teachers'"><GraduationCap :size="20"/><strong>{{participatingTeachers.length}}</strong><span>{{participatingTeachers.length===1?'profesor':'profesores'}}</span></button></div><div><BookOpen :size="20"/><strong>{{modules.length}}</strong><span>módulos</span></div><div class="progress-summary"><span><strong>{{complete}} / {{book.rows.length}}</strong> evaluaciones completas</span><div class="progress-track"><i :style="{width:`${book.rows.length?complete/book.rows.length*100:0}%`}"/></div></div></section>
<div class="challenge-tabs" role="tablist" aria-label="Vistas del reto" @keydown="navigateTabs">
  <button v-for="tab in tabs" :id="`challenge-tab-${tab.id}`" :key="tab.id" type="button" role="tab" :aria-selected="activeTab===tab.id" :aria-controls="`challenge-panel-${tab.id}`" :tabindex="activeTab===tab.id?0:-1" :disabled="busy||evidenceSaving" @click="selectTab(tab.id)">{{tab.label}}</button>
</div>
<section v-show="activeTab==='evaluation'" id="challenge-panel-evaluation" role="tabpanel" aria-labelledby="challenge-tab-evaluation" tabindex="0">
<template v-if="!evaluating">
<div v-if="book.issues.length" class="notice warning"><AlertCircle :size="18"/><div v-for="issue in book.issues">{{issue}}</div></div>
<div class="workspace-heading"><div><h2 ref="evaluationOverviewHeading" tabindex="-1">Evaluación <span class="live-dot"/></h2></div><div class="bottom-actions"><div class="save-status" aria-live="polite"><Check v-if="message==='Cambios guardados'" :size="15"/>{{message}}</div><button class="button" @click="openRubric('team')">Ev. técnica <ChevronRight :size="16"/></button><button class="button" @click="openRubric('teacher')">Ev. transversales <ChevronRight :size="16"/></button><button v-if="writable('enter_exams')||writable('enter_defenses')" class="button" @click="batch={module_id:modules.find((m:any)=>moduleWritable('enter_exams',m)||moduleWritable('enter_defenses',m))?.id??0,field:'exam',text:''};modal='batch'">Introducción masiva</button><select v-if="writable('manage_challenges')" :value="book.challenge.status" aria-label="Estado del reto" :disabled="busy" @change="save({action:'status',status:($event.target as HTMLSelectElement).value})"><option value="draft">Borrador</option><option value="active">En curso</option><option value="evaluating">En evaluación</option><option value="finished">Finalizado</option></select></div></div>
<div v-if="error" class="notice error" role="alert">{{error}}<button @click="router.reload()">Actualizar datos</button></div>
<div class="matrix-toolbar"><div class="search-box"><Search :size="16"/><input v-model="search" aria-label="Buscar estudiante" placeholder="Buscar estudiante…"/></div><select v-model="teamFilter" aria-label="Filtrar equipo"><option value="">Todos los equipos</option><option v-for="t in book.teams" :value="t.id">{{t.name}}</option></select><select v-model="sort" aria-label="Ordenar estudiantes"><option value="name">Nombre A–Z</option><option value="team">Por equipo</option></select><label class="check"><input type="checkbox" v-model="onlyPending"/>Solo pendientes</label><div class="toolbar-spacer"/><button class="icon-button" aria-label="Mostrar u ocultar columnas" @click="showColumns=!showColumns"><SlidersHorizontal :size="18"/></button><button class="icon-button" aria-label="Exportar matriz CSV" @click="exportCsv"><Download :size="18"/></button></div>
<div v-if="showColumns" class="column-options"><label class="check"><input type="checkbox" v-model="visible.transversal"/>Transversales</label><label class="check"><input type="checkbox" v-model="visible.team"/>Nota de equipo y reparto</label><label class="check"><input type="checkbox" v-model="visible.defenses"/>Defensas por módulo</label></div>
<div class="table-scroll matrix-scroll"><table class="matrix"><thead><tr><th class="frozen student-col" rowspan="2">ESTUDIANTE <small>Equipo / estado</small></th><th v-if="visible.transversal" class="group-trans" rowspan="2">TRANSVERSALES <small>{{book.challenge.component_weights.transversal}}%</small></th><th v-if="visible.team" :colspan="book.challenge.distribution_enabled?2:1" class="group-team">VALORACIÓN DEL EQUIPO</th><th colspan="2" class="group-challenge">NOTA COMÚN A TODOS LOS MÓDULOS</th><th v-for="m in modules" :colspan="visible.defenses&&m.defense_enabled?3:2" class="group-module">{{m.code}}</th><th rowspan="2">ESTADO</th></tr><tr><th v-if="visible.team">Equipo</th><th v-if="visible.team&&book.challenge.distribution_enabled">Reparto</th><th class="defense-total">Σ Defensas</th><th class="challenge-total">Reto final <small>{{book.challenge.component_weights.challenge}}%</small></th><template v-for="m in modules"><th v-if="visible.defenses&&m.defense_enabled">Defensa</th><th>Examen <small>{{book.challenge.component_weights.exam}}%</small></th><th class="module-final">Final</th></template></tr></thead><tbody><tr v-for="row in rows" :key="row.id"><td class="frozen student-col"><button class="student-button" @click="detail=row;modal='detail'"><span class="student-avatar">{{row.name.split(' ').slice(0,2).map((n:string)=>n[0]).join('')}}</span><span><strong>{{row.name}}</strong><small>{{row.team_name??'Sin equipo'}}</small></span></button></td><td v-if="visible.transversal"><button class="grade-link" @click="detail=row;modal='detail'">{{grade(row.transversal)}}</button></td><td v-if="visible.team"><button class="grade-link" @click="openRubric('team',row.team_id)">{{grade(row.team_grade)}}</button></td><td v-if="visible.team&&book.challenge.distribution_enabled"><button class="allocation-cell" :disabled="!row.team_id" @click="openAllocation(book.teams.find((t:any)=>t.id===row.team_id))">{{grade(row.allocation)}}<span v-if="row.pending.includes('Reparto pendiente o inválido')" class="pending-dot"/></button></td><td class="defense-total"><button class="grade-link" @click="detail=row;modal='detail'">{{grade(row.defenses_total)}}</button></td><td class="challenge-total"><button class="grade-link bold" @click="detail=row;modal='detail'">{{grade(row.challenge_final)}}</button></td><template v-for="m in modules"><td v-if="visible.defenses&&m.defense_enabled"><button class="defense-cell" :class="{editable:moduleWritable('enter_defenses',m)}" @click="openDefense(row,m)">{{grade(row.modules[m.id].defense)}}</button></td><td><GradeInput :model-value="row.modules[m.id].exam" :label="`Examen ${m.code} de ${row.name}`" :disabled="row.modules[m.id].not_enrolled||!moduleWritable('enter_exams',m)" @save="saveGrade(row,m,'exam',$event)"/></td><td class="module-final"><button class="grade-link bold" :class="{'low-grade':row.modules[m.id].final!==null&&Number(row.modules[m.id].final)<5}" @click="detail=row;modal='detail'">{{row.modules[m.id].not_enrolled?'NM':grade(row.modules[m.id].final)}}</button></td></template><td><span v-if="!row.pending.length" class="row-state done"><Check :size="14"/>Listo</span><button v-else class="row-state pending" @click="detail=row;modal='detail'">{{row.pending.length}} pendientes</button></td></tr><tr v-if="!rows.length"><td colspan="30" class="empty-cell">No hay estudiantes que coincidan con estos filtros.</td></tr></tbody></table></div>
<div class="matrix-footer"><span>{{rows.length}} estudiantes · <kbd>Tab</kbd> siguiente campo · <kbd>Enter</kbd> guardar · <kbd>Esc</kbd> cancelar edición</span><span>— Pendiente de evaluar</span></div>
</template>
<section v-else class="rubric-evaluation">
  <div class="actions"><button type="button" class="button" :disabled="busy" @click="closeRubric"><ArrowLeft :size="16"/>Volver a todas las evaluaciones</button></div>
  <div v-if="error" class="notice error" role="alert">{{error}}<button @click="router.reload()">Actualizar datos</button></div>
  <p v-if="closed" class="notice info">Solo lectura. El reto o el curso académico está cerrado.</p>
  <div class="rubric-evaluation-toolbar"><h1 ref="evaluationHeading" tabindex="-1">{{rubricKind==='team'?'Rúbrica del equipo':'Transversales del profesorado'}}</h1><label>{{rubricKind==='team'?'Equipo a evaluar':'Estudiante a evaluar'}}<select v-model="evaluationSubject" :disabled="busy"><option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{subject.name}}</option></select></label><div class="actions"><button class="button" :disabled="busy||!currentSubject||currentSubject.id===subjects[0]?.id" @click="changeSubject(-1)">Anterior</button><button class="button" :disabled="busy||!currentSubject||currentSubject.id===subjects[subjects.length-1]?.id" @click="changeSubject(1)">Siguiente</button></div><button v-if="rubric.items.length&&writable('manage_challenges')" class="button" :disabled="busy" @click="editRubric">Editar rúbrica</button><span class="save-status" role="status">{{message}}</span></div>
  <div v-if="!rubric.items.length" class="notice warning" role="alert"><p>La rúbrica no contiene todavía criterios.</p><button v-if="writable('manage_challenges')" class="button" :disabled="busy" @click="editRubric">Editar rúbrica</button><p v-else>El profesorado con permiso para gestionar el reto debe completar la rúbrica antes de evaluar.</p></div>
  <ModuleCriteriaNotice v-if="rubricKind==='team'&&rubric.items.length" :items="rubric.items" :modules="modules"/>
  <RubricAssessment v-if="rubric.items.length&&currentSubject" :rubric="rubric" :subject="currentSubject.name" :selections="evaluationSelections" :show-modules="rubricKind==='team'" :modules="modules" :disabled="busy||!writable(rubricKind==='team'?'evaluate_team':'evaluate_transversal')" :disabled-criteria="disabledCriteria" @select="(key,level)=>save({action:'assess',kind:rubricKind,entries:[{subject_id:Number(evaluationSubject),criterion:key,level}]})"/>
  <p v-else-if="rubric.items.length" class="empty-state">{{rubricKind==='team'?'Crea los equipos del reto para poder evaluarlos.':'No hay estudiantes en este reto.'}}</p>
</section>

</section>
<section v-show="activeTab==='evidence'" id="challenge-panel-evidence" role="tabpanel" aria-labelledby="challenge-tab-evidence" tabindex="0">
  <EvidenceWorkspace :challenge="book.challenge" :students="book.rows" :evidences="evidences" :can-add="!closed" :store-url="evidenceStoreUrl" @saving="evidenceSaving=$event" @teams="selectTab('teams')"/>
</section>

<Modal v-if="modal==='teachers'" title="Profesorado participante" @close="modal=''"><ul v-if="participatingTeachers.length"><li v-for="teacher in participatingTeachers" :key="teacher">{{teacher}}</li></ul><p v-else class="muted">Sin asignar.</p></Modal>
<Modal v-if="modal==='detail'" :title="detail.name" @close="modal=''" wide><div class="detail-grid"><section><p class="eyebrow">COMPETENCIAS TRANSVERSALES</p><div class="breakdown-row"><span>Autoevaluación · {{book.challenge.transversal_weights.self}}%</span><strong>{{grade(detail.self)}}</strong></div><div class="breakdown-row"><span>Compañeros · {{book.challenge.transversal_weights.peer}}%</span><strong>{{grade(detail.peer)}}</strong></div><div class="breakdown-row"><span>Profesorado · {{book.challenge.transversal_weights.teacher}}%</span><strong>{{grade(detail.teacher)}}</strong></div><div class="breakdown-row total"><span>Transversales</span><strong>{{grade(detail.transversal)}}</strong></div></section><section><p class="eyebrow">NOTA FINAL DEL RETO</p><div class="breakdown-row"><span>Nota del equipo</span><strong>{{grade(detail.team_grade)}}</strong></div><div class="breakdown-row"><span>{{book.challenge.distribution_enabled?'Reparto':'Base sin reparto'}}</span><strong>{{grade(detail.base)}}</strong></div><div v-for="m in modules.filter((m:any)=>m.defense_enabled)" class="breakdown-row"><span>Defensa {{m.code}}</span><strong>{{grade(detail.modules[m.id].defense)}}</strong></div><div class="breakdown-row"><span>Resultado antes de límites</span><strong>{{grade(detail.challenge_raw)}}</strong></div><div class="breakdown-row total"><span>Única nota final del reto</span><strong>{{grade(detail.challenge_final)}}</strong></div></section></div><div class="module-breakdowns"><section v-for="m in modules"><h3>{{m.code}}</h3><label class="check"><input type="checkbox" :checked="detail.modules[m.id].not_enrolled" :disabled="busy||!moduleWritable('enter_exams',m)" @change="saveGrade(detail,m,'not_enrolled',($event.target as HTMLInputElement).checked).then(()=>detail=book.rows.find((r:any)=>r.id===detail.id))"/>No matriculado</label><p>{{grade(detail.transversal)}} × {{book.challenge.component_weights.transversal}}% + {{grade(detail.challenge_final)}} × {{book.challenge.component_weights.challenge}}% + {{grade(detail.modules[m.id].exam)}} × {{book.challenge.component_weights.exam}}%</p><strong>{{detail.modules[m.id].not_enrolled?'No matriculado':grade(detail.modules[m.id].final)}}</strong></section></div><div v-if="detail.pending.length" class="notice warning">Pendiente: {{detail.pending.join(' · ')}}</div><p class="helper">Los cálculos conservan la precisión interna; las notas mostradas están redondeadas.</p></Modal>
<Modal v-if="modal==='allocation'" :title="`Reparto · ${allocTeam.name}`" @close="modal=''"><div class="points-banner"><div><small>NOTA DEL EQUIPO</small><strong>{{grade(allocTeam.grade)}}</strong></div><span>× {{allocTeam.members.length}} =</span><div><small>PUNTOS A REPARTIR</small><strong>{{grade(allocTeam.points)}}</strong></div></div><form @submit.prevent="submitAllocation"><label v-for="member in allocTeam.members" class="allocation-row"><span>{{book.rows.find((r:any)=>r.id===member.student_id)?.name}}</span><input v-model="allocations[member.student_id]" inputmode="decimal" maxlength="5" required :disabled="!writable('evaluate_team')" @input="limitAllocation($event,member.student_id)"/></label><div class="breakdown-row total"><span>Total introducido</span><strong>{{grade(allocated)}}</strong></div><p class="helper">El profesor registra el reparto acordado presencialmente. Se validará la suma exacta antes de guardar.</p><p v-if="error" class="notice error" role="alert">{{error}}</p><button v-if="writable('evaluate_team')" class="button primary full" :disabled="busy||allocTeam.grade===null">Guardar reparto</button></form></Modal>
<section v-show="activeTab==='teams'" id="challenge-panel-teams" role="tabpanel" aria-labelledby="challenge-tab-teams" tabindex="0">
  <div class="workspace-heading"><h2>Estudiantes y Equipos</h2><span class="save-status" role="status">{{message}}</span></div>
  <p v-if="!writable('manage_teams')" class="notice info">Solo lectura. {{closed?'El reto o el curso académico está cerrado.':'No tienes permiso para gestionar los equipos.'}}</p>
  <fieldset class="challenge-form-fields" :disabled="busy||!writable('manage_teams')">
  <p class="team-dialog-intro">Distribuye al alumnado en equipos de 2 a 5 personas. Cada estudiante puede pertenecer a un solo equipo.</p>
  <div v-if="!book.rows.length" class="notice">
    <p>Este reto no tiene estudiantes. Asígnalos primero a su grupo desde <Link :href="(usePage().props.setupNavigation as any[]).find(link=>link.section==='students')?.url">Organización → Estudiante</Link>.</p>
    <p>Después puedes incorporar la matrícula actual a este reto vacío. Los retos con evaluaciones conservan sus participantes.</p>
    <button class="button" :disabled="busy" @click="save({action:'participants'})">Incorporar estudiantes del grupo</button>
  </div>
  <div class="team-editor">
    <section v-for="(team,index) in draftTeams" :key="index" class="team-card" :class="{ selecting: team.selecting }">
      <div class="team-card-heading">
        <span class="team-card-index">Equipo {{String(index+1).padStart(2,'0')}}</span>
        <button type="button" class="icon-button" :aria-label="'Eliminar equipo ' + (index+1)" @click="draftTeams.splice(index,1)"><X :size="18"/></button>
      </div>
      <label class="team-name-field">Nombre del equipo<input v-model="team.name" :aria-label="'Nombre del equipo ' + (index+1)" required/></label>
      <div class="team-roster-heading"><div><strong>Integrantes</strong><span>Entre 2 y 5 estudiantes</span></div><b>{{team.students.length}}/5</b></div>
      <ul v-if="teamMembers(team).length" class="team-members">
        <li v-for="row in teamMembers(team)" :key="row.id">
          <span class="team-member-name">{{row.name}}</span>
          <button type="button" class="icon-button" :aria-label="'Quitar a ' + row.name + ' del equipo'" @click="team.students=team.students.filter((id:number)=>id!==row.id)"><X :size="15"/></button>
        </li>
      </ul>
      <p v-else class="team-members-empty">Aún no hay integrantes. Añade estudiantes desde la lista.</p>
      <div class="team-picker-control">
        <button type="button" class="button team-picker-toggle" :aria-expanded="!!team.selecting" :aria-controls="'team-students-' + index" :disabled="team.students.length>=5&&!team.selecting" @click="toggleTeamPicker(team,index)">
          <span>{{team.selecting?'Cerrar selector':'Añadir estudiantes'}}</span><ChevronRight :size="16" :class="{ 'team-picker-open': team.selecting }"/>
        </button>
        <span>{{unassignedStudents.length}} disponibles</span>
      </div>
      <div v-if="team.selecting" :id="'team-students-' + index" class="team-student-picker">
        <label class="team-picker-search"><Search :size="16"/><input :id="'team-student-search-' + index" v-model="team.search" type="search" :aria-label="'Buscar estudiantes disponibles para el equipo ' + (index+1)" placeholder="Buscar por nombre…"/></label>
        <div v-if="availableStudents(team).length" class="team-student-options">
          <button v-for="student in availableStudents(team)" :key="student.id" type="button" class="team-student-option" :disabled="team.students.length>=5" @click="addStudentToTeam(team,student)">
            <span>{{student.name}}</span><span class="team-option-action">Añadir <ChevronRight :size="15"/></span>
          </button>
        </div>
        <p v-else class="team-no-matches" role="status">{{unassignedStudents.length?'No hay estudiantes que coincidan con la búsqueda.':'Todos los estudiantes ya tienen equipo.'}}</p>
      </div>
      <p v-if="teamNameErrors[index]" class="notice error team-name-error" role="alert">{{teamNameErrors[index]}}</p>
      <p v-if="team.students.length<2" class="team-capacity-hint">Añade {{2-team.students.length}} {{2-team.students.length===1?'estudiante más':'estudiantes más'}} para completar el mínimo.</p>
    </section>
  </div>
  <div class="team-editor-actions">
    <button type="button" class="button" @click="draftTeams.push({selecting:false,search:'',name:`Equipo ${draftTeams.length+1}`,students:[]})">Añadir equipo</button>
    <button type="button" class="button primary" :disabled="busy||!draftTeams.length||teamErrors.some(Boolean)||!book.rows.length" @click="save({action:'teams',teams:draftTeams.map(team=>({name:team.name,students:team.students}))}).then(ok=>{if(ok)initializeTeams()})"><Check :size="16"/>Guardar equipos</button>
  </div>
  <p v-if="error" class="notice error" role="alert">{{error}}</p>
</fieldset>
</section>
<section v-show="activeTab==='settings'" id="challenge-panel-settings" role="tabpanel" aria-labelledby="challenge-tab-settings" tabindex="0">
<div class="workspace-heading"><h2>Configuración del reto</h2><span class="save-status" role="status">{{message}}</span></div>
<p v-if="!writable('manage_challenges')" class="notice info">Solo lectura. {{closed?'El reto o el curso académico está cerrado.':'No tienes permiso para configurar el reto.'}}</p>
<form @submit.prevent="save({action:'configure',...settings}).then(ok=>{if(ok)initializeSettings()})" class="challenge-settings"><fieldset class="challenge-form-fields form-grid" :disabled="busy||!writable('manage_challenges')"><label class="span-2">Nombre<input v-model="settings.name" required/></label><label class="span-2">Descripción<textarea v-model="settings.description"/></label><label>Inicio<input type="date" v-model="settings.starts_at"/></label><label>Fin<input type="date" v-model="settings.ends_at"/></label><label>Peso en la Evaluación<input v-model="settings.weight" required inputmode="decimal"/></label><label class="check"><input v-model="settings.distribution_enabled" type="checkbox"/>Reparto individual</label><label class="check span-2"><input v-model="settings.clamp_grade" type="checkbox"/>Limitar nota final del reto a 0–10</label><fieldset><legend>Nota de módulo · suma 100%</legend><label v-for="(label,key) in {transversal:'Transversales',challenge:'Reto',exam:'Examen'}">{{label}}<input v-model="settings.component_weights[key]" inputmode="decimal"/></label></fieldset><fieldset><legend>Transversales · suma 100%</legend><label v-for="(label,key) in {self:'Autoevaluación',peer:'Compañeros',teacher:'Profesorado'}">{{label}}<input v-model="settings.transversal_weights[key]" inputmode="decimal"/></label></fieldset><fieldset class="span-2"><legend>Defensas por módulo</legend><label v-for="m in modules" class="check"><input v-model="settings.defenses[m.id]" type="checkbox"/>{{m.code}}</label></fieldset><label class="span-2">Observaciones<textarea v-model="settings.notes"/></label><label class="span-2">Motivo de la corrección, si ha estado publicado<input v-model="settings.reason"/></label><p v-if="error" class="notice error span-2">{{error}}</p><button class="button primary span-2" :disabled="busy||closed">Guardar configuración</button></fieldset></form></section>
<Modal v-if="modal==='publish'" title="Publicar resultados" @close="modal=''"><p>Se conservará una versión de las notas y el alumnado podrá consultar su desglose. El reto quedará cerrado a modificaciones.</p><p v-if="!book.complete" class="notice warning">Hay datos pendientes o errores. Completa el reto antes de publicar.</p><p v-if="error" class="notice error">{{error}}</p><button class="button primary full" :disabled="busy||!book.complete" @click="save({action:'publish'}).then(ok=>{if(ok)modal=''})">Publicar resultados</button></Modal>
<Modal v-if="modal==='reopen'" title="Reabrir para corregir" @close="modal=''"><form @submit.prevent="save({action:'reopen',reason:reopenReason}).then(ok=>{if(ok)modal=''})"><p>Se conserva la publicación anterior. Las notas volverán a estar disponibles para el alumnado cuando publiques la corrección.</p><label>Motivo de la corrección<textarea v-model="reopenReason" required minlength="5"/></label><p v-if="error" class="notice error">{{error}}</p><button class="button primary full" :disabled="busy">Reabrir reto</button></form></Modal>
<Modal v-if="modal==='history'" title="Histórico del reto" @close="modal=''" wide><h3>Publicaciones conservadas</h3><details v-for="p in historyData.publications"><summary>Versión {{p.version}} · {{new Date(p.created_at).toLocaleString('es-ES')}}</summary><table><thead><tr><th>Estudiante</th><th>Reto final</th><th>Módulos</th></tr></thead><tbody><tr v-for="r in p.snapshot.rows"><td>{{r.name}}</td><td>{{grade(r.challenge_final)}}</td><td>{{p.snapshot.modules.map((m:any)=>`${m.code}: ${r.modules[m.id].not_enrolled?'No matriculado':grade(r.modules[m.id].final)}`).join(' · ')}}</td></tr></tbody></table></details><p v-if="!historyData.publications.length" class="muted">Todavía no hay publicaciones.</p><h3>Últimos cambios</h3><div v-for="e in historyData.events" class="audit-row"><span>{{e.action}}</span><small>Usuario {{e.user_id}} · {{new Date(e.created_at).toLocaleString('es-ES')}}</small><p v-if="e.reason">{{e.reason}}</p></div></Modal>
<Modal v-if="modal==='batch'" title="Introducir varias notas" @close="modal=''"><form @submit.prevent="submitBatch"><div class="form-grid"><label>Módulo<select v-model="batch.module_id"><option v-for="m in modules.filter((m:any)=>moduleWritable('enter_exams',m)||moduleWritable('enter_defenses',m))" :value="m.id">{{m.code}}</option></select></label><label>Tipo<select v-model="batch.field"><option value="exam">Examen</option><option value="defense">Defensa</option></select></label></div><p class="helper">Pega una nota por línea, siguiendo este orden de {{rows.length}} estudiantes. El lote se guarda completo o se rechaza entero.</p><div class="batch-grid"><ol><li v-for="r in rows">{{r.name}}</li></ol><textarea v-model="batch.text" :rows="Math.min(rows.length,20)" required aria-label="Notas, una por línea" placeholder="7,5&#10;8&#10;6,25"/></div><p v-if="error" class="notice error">{{error}}</p><button class="button primary full" :disabled="busy">Guardar todas las notas</button></form></Modal>
<Modal v-if="modal==='defense'" :title="`Defensa ${defenseDetail.module.code} · ${defenseDetail.name}`" @close="modal=''"><form @submit.prevent="submitDefense"><label>Puntos que suma o resta<input v-model="defenseDetail.value" inputmode="decimal" placeholder="Por ejemplo, +0,5 o -0,25" :disabled="defenseDetail.not_enrolled||!moduleWritable('enter_defenses',defenseDetail.module)"/></label><label>Fecha<input v-model="defenseDetail.date" type="date" :disabled="defenseDetail.not_enrolled||!moduleWritable('enter_defenses',defenseDetail.module)"/></label><label>Observaciones<textarea v-model="defenseDetail.notes" :disabled="defenseDetail.not_enrolled||!moduleWritable('enter_defenses',defenseDetail.module)"/></label><p class="helper">Cero significa defensa realizada sin ajuste. Un campo vacío indica que está pendiente.</p><p v-if="error" class="notice error">{{error}}</p><button v-if="!defenseDetail.not_enrolled&&moduleWritable('enter_defenses',defenseDetail.module)" class="button primary full" :disabled="busy">Guardar defensa</button></form></Modal>
</Layout></template>

<style scoped>
.challenge-tabs { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; margin: 0 0 24px; padding: 6px; border: 1px solid var(--border); border-radius: 10px; background: #f1e8ef; }
.challenge-tabs button { flex: 1; padding: 12px 18px; border-radius: 7px; color: #72616d; font-size: 13px; font-weight: 500; white-space: normal; }
.challenge-tabs button:hover { background: #eddee9; }
.challenge-tabs button[aria-selected=true] { color: var(--green); background: #fff; box-shadow: 0 2px 6px #331d2d10; font-weight: 600; }
.challenge-form-fields { min-width: 0; margin: 0; padding: 0; border: 0; }
.challenge-settings { max-width: 940px; padding: 24px; background: #fff; border: 1px solid var(--border); border-radius: 12px; }
.challenge-settings fieldset { min-width: 0; }
.challenge-settings .form-grid > label { min-width: 0; }
.topline > span { overflow-wrap: anywhere; text-align: right; }
@media (max-width: 1100px) {
  .challenge-tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 680px) {
  .challenge-tabs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 4px; }
  .challenge-tabs button { padding: 12px 5px; font-size: 11px; white-space: normal; }
  .challenge-settings { padding: 16px; }
}
</style>
