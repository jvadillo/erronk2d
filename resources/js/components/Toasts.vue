<script setup lang="ts">
import { onUnmounted, watch } from 'vue';
import { AlertCircle, AlertTriangle, CheckCircle2, Info, X } from 'lucide-vue-next';
import { dismissToast, toasts, type Toast } from '../lib';

const icons = { success: CheckCircle2, error: AlertCircle, warning: AlertTriangle, info: Info };
const timers = new Map<number, { handle: number; remaining: number; startedAt: number }>();

function start(toast: Toast, remaining = toast.duration): void {
  if (remaining === null) {
    return;
  }
  const handle = window.setTimeout(() => close(toast.id), remaining);
  timers.set(toast.id, { handle, remaining, startedAt: Date.now() });
}

function pause(toast: Toast): void {
  const timer = timers.get(toast.id);
  if (!timer) {
    return;
  }
  window.clearTimeout(timer.handle);
  timers.set(toast.id, { ...timer, handle: 0, remaining: Math.max(800, timer.remaining - (Date.now() - timer.startedAt)) });
}

function resume(toast: Toast): void {
  const timer = timers.get(toast.id);
  if (timer && !timer.handle) {
    start(toast, timer.remaining);
  }
}

function close(id: number): void {
  const timer = timers.get(id);
  if (timer) {
    window.clearTimeout(timer.handle);
    timers.delete(id);
  }
  dismissToast(id);
}

function runAction(toast: Toast): void {
  toast.action?.run();
  close(toast.id);
}

watch(() => toasts.map(toast => toast.id), () => {
  for (const toast of toasts) {
    if (!timers.has(toast.id)) {
      start(toast);
    }
  }
  for (const id of [...timers.keys()]) {
    if (!toasts.some(toast => toast.id === id)) {
      window.clearTimeout(timers.get(id)!.handle);
      timers.delete(id);
    }
  }
}, { immediate: true });

onUnmounted(() => timers.forEach(timer => window.clearTimeout(timer.handle)));
</script>

<template>
  <Teleport to="body">
    <TransitionGroup tag="ol" name="toast" class="toast-region" aria-label="Notificaciones">
      <li v-for="toast in toasts" :key="toast.id" class="toast" :class="toast.tone" :role="toast.tone === 'error' ? 'alert' : 'status'" @mouseenter="pause(toast)" @mouseleave="resume(toast)" @focusin="pause(toast)" @focusout="resume(toast)">
        <component :is="icons[toast.tone]" class="toast-icon" :size="18" aria-hidden="true" />
        <p>{{ toast.message }}</p>
        <button v-if="toast.action" type="button" class="toast-action" @click="runAction(toast)">{{ toast.action.label }}</button>
        <button type="button" class="toast-close" aria-label="Cerrar notificación" @click="close(toast.id)"><X :size="16" /></button>
        <i v-if="toast.duration" class="toast-timer" :style="{ animationDuration: `${toast.duration}ms` }" aria-hidden="true" />
      </li>
    </TransitionGroup>
  </Teleport>
</template>

<style scoped>
.toast-region { position: fixed; top: 18px; right: 18px; z-index: 70; display: flex; flex-direction: column; align-items: flex-end; gap: 10px; width: min(380px, calc(100vw - 32px)); margin: 0; padding: 0; list-style: none; pointer-events: none; }
.toast { position: relative; display: flex; align-items: flex-start; gap: 10px; width: 100%; padding: 13px 12px 13px 14px; overflow: hidden; border: 1px solid #ece4ea; border-radius: 11px; background: #fff; box-shadow: 0 12px 32px #2b1f2826, 0 2px 6px #2b1f2814; color: #2b1f28; font-size: 13px; line-height: 1.45; pointer-events: auto; }
.toast p { flex: 1; min-width: 0; padding-top: 1px; overflow-wrap: anywhere; }
.toast-icon { flex-shrink: 0; margin-top: 1px; }
.toast.success .toast-icon { color: #3c7a51; }
.toast.error { border-color: #f0d2cc; }
.toast.error .toast-icon { color: #b0413a; }
.toast.warning .toast-icon { color: #a77b22; }
.toast.info .toast-icon { color: #82005e; }
.toast-action { flex-shrink: 0; padding: 2px 4px; color: #82005e; font-size: 12px; font-weight: 600; text-decoration: underline; }
.toast-close { display: grid; flex-shrink: 0; place-items: center; width: 26px; height: 26px; margin: -3px -2px 0 0; border-radius: 6px; color: #8f7f8a; }
.toast-close:hover { background: #f5eef3; color: #2b1f28; }
.toast-timer { position: absolute; right: 0; bottom: 0; left: 0; height: 2px; background: currentColor; opacity: .14; transform-origin: left; animation: toast-timer linear forwards; }
.toast:hover .toast-timer, .toast:focus-within .toast-timer { animation-play-state: paused; }
.toast.success .toast-timer { color: #3c7a51; opacity: .35; }
.toast.error .toast-timer { color: #b0413a; opacity: .35; }
.toast.warning .toast-timer { color: #a77b22; opacity: .35; }
.toast.info .toast-timer { color: #82005e; opacity: .35; }
@keyframes toast-timer { from { transform: scaleX(1); } to { transform: scaleX(0); } }
.toast-enter-active, .toast-leave-active { transition: opacity .25s ease, transform .25s ease; }
.toast-enter-from { opacity: 0; transform: translateY(-8px); }
.toast-leave-to { opacity: 0; transform: translateX(16px); }
.toast-leave-active { position: absolute; right: 0; width: 100%; }
@media (max-width: 650px) {
  .toast-region { top: auto; right: 16px; bottom: 84px; }
}
@media (prefers-reduced-motion: reduce) {
  .toast-enter-active, .toast-leave-active { transition: none; }
  .toast-timer { display: none; }
}
</style>
