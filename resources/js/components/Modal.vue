<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { X } from 'lucide-vue-next';
defineProps<{title:string;wide?:boolean}>(); const emit=defineEmits(['close']); const panel=ref<HTMLElement>(); let previous:HTMLElement|null=null;
function key(e:KeyboardEvent){if(e.key==='Escape')emit('close');if(e.key==='Tab'){const els=Array.from(panel.value?.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]')??[]); if(!els.length)return; const first=els[0],last=els[els.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus()}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus()}}}
onMounted(()=>{previous=document.activeElement as HTMLElement;panel.value?.focus();document.addEventListener('keydown',key)});onUnmounted(()=>{document.removeEventListener('keydown',key);previous?.focus()});
</script>
<template><Teleport to="body"><div class="modal-backdrop" @click.self="emit('close')"><section ref="panel" tabindex="-1" role="dialog" aria-modal="true" :aria-label="title" class="modal" :class="{wide}"><header><h2>{{title}}</h2><button class="icon-button" aria-label="Cerrar" @click="emit('close')"><X :size="22"/></button></header><slot/></section></div></Teleport></template>
