import { usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';
export type Auth = { id: number; name: string; role: string; permissions: string[] };
export const permission = (name: string) => {
  const page=usePage();
  const allowed=(page.props.auth as Auth | null)?.permissions.includes(name)??false;
  const contextual=['manage_teams','manage_challenges','evaluate_team','evaluate_transversal','enter_exams','enter_defenses','modify_grades','publish_results'];
  return allowed&&(!contextual.includes(name)||!!(page.props.academic as any)?.year?.is_open);
};
export const grade = (value: string | number | null | undefined, digits = 2) => value === null || value === undefined || value === '' ? '—' : Number(value).toLocaleString('es-ES', {minimumFractionDigits: digits, maximumFractionDigits: digits});
export const statusLabel: Record<string,string> = {active:'En curso', evaluating:'En evaluación', finished:'Finalizado', published:'Publicado'};
export async function api(url: string, payload?: object | FormData) {
  const xsrf = document.cookie.split('; ').find(cookie => cookie.startsWith('XSRF-TOKEN='))?.slice('XSRF-TOKEN='.length);
  const csrfHeaders: Record<string, string> = xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf) } : { 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' };
  const response = await fetch(url, { method: payload ? 'POST' : 'GET', credentials:'same-origin', headers: { Accept:'application/json', 'X-Requested-With':'XMLHttpRequest', ...csrfHeaders, 'X-Academic-Year':document.documentElement.dataset.academicYear??'', ...(payload instanceof FormData ? {} : {'Content-Type':'application/json'}) }, body: payload ? (payload instanceof FormData ? payload : JSON.stringify(payload)) : undefined });
  const data = await response.json().catch(() => ({message:'No se ha podido guardar. Comprueba la conexión e inténtalo de nuevo.'}));
  if (!response.ok) { throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message ?? 'No se ha podido completar la operación.'); }
  return data;
}

/** Tiempo, en milisegundos, que permanece visible un aviso antes de desvanecerse. */
export const TOAST_DURATION_MS = 5000;
export type ToastTone = 'success' | 'error' | 'warning' | 'info';
export type Toast = { id: number; message: string; tone: ToastTone; duration: number | null; action?: { label: string; run: () => void } };
export const toasts = reactive<Toast[]>([]);
let toastSequence = 0;
/** Muestra un aviso flotante. `duration: null` lo mantiene hasta que se cierre con la X. */
export function notify(message: string, tone: ToastTone = 'info', options: { duration?: number | null; action?: Toast['action'] } = {}): number {
  const existing = toasts.find(toast => toast.message === message && toast.tone === tone);
  if (existing) dismissToast(existing.id);
  const id = ++toastSequence;
  toasts.push({ id, message, tone, duration: options.duration === undefined ? TOAST_DURATION_MS : options.duration, action: options.action });
  if (toasts.length > 4) toasts.splice(0, toasts.length - 4);
  return id;
}
export function dismissToast(id: number): void {
  const index = toasts.findIndex(toast => toast.id === id);
  if (index >= 0) toasts.splice(index, 1);
}
