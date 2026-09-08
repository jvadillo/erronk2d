<script setup lang="ts">
import { ref, watch } from 'vue';
const props=defineProps<{modelValue:string|null;label:string;disabled?:boolean;signed?:boolean}>(); const emit=defineEmits<{save:[value:string|null]}>();
const value=ref(props.modelValue??''), focused=ref(false), dirty=ref(false);
watch(()=>props.modelValue,v=>{
  const incoming=v??'';
  if(value.value.trim()===incoming)dirty.value=false;
  if(value.value.trim().replace(',','.')!=='' && Number(value.value.replace(',','.'))===Number(incoming) && incoming!=='')dirty.value=false;
  if(!focused.value&&!dirty.value)value.value=incoming;
});
function save(){const normalized=value.value.trim().replace(',','.');if(normalized!==(props.modelValue??''))emit('save',normalized===''?null:normalized)}
function key(e:KeyboardEvent){if(e.key==='Enter'){e.preventDefault();(e.target as HTMLInputElement).blur(); const all=Array.from(document.querySelectorAll<HTMLInputElement>('.grade-input:not([disabled])'));const index=all.indexOf(e.target as HTMLInputElement);all[index+(e.shiftKey?-1:1)]?.focus()} if(e.key==='Escape'){dirty.value=false;value.value=props.modelValue??'';(e.target as HTMLInputElement).blur()}}
</script>
<template><input v-model="value" :aria-label="label" :disabled="disabled" :placeholder="signed?'+ / −':'—'" class="grade-input" :class="{'unsaved':dirty}" :title="dirty?'Cambio pendiente de guardar':label" inputmode="decimal" autocomplete="off" @input="dirty=true" @blur="focused=false;save()" @keydown="key" @focus="focused=true;($event.target as HTMLInputElement).select()"/></template>
