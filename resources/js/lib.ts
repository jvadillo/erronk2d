import { usePage } from '@inertiajs/vue3';
export type Auth = { id: number; name: string; role: string; permissions: string[] };
export const permission = (name: string) => (usePage().props.auth as Auth | null)?.permissions.includes(name) ?? false;
export const grade = (value: string | number | null | undefined, digits = 2) => value === null || value === undefined || value === '' ? '—' : Number(value).toLocaleString('es-ES', {minimumFractionDigits: digits, maximumFractionDigits: digits});
export const statusLabel: Record<string,string> = {draft:'Borrador', active:'En curso', evaluating:'En evaluación', finished:'Finalizado', published:'Publicado'};
export async function api(url: string, payload?: object | FormData) {
  const response = await fetch(url, { method: payload ? 'POST' : 'GET', credentials:'same-origin', headers: { Accept:'application/json', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '', ...(payload instanceof FormData ? {} : {'Content-Type':'application/json'}) }, body: payload ? (payload instanceof FormData ? payload : JSON.stringify(payload)) : undefined });
  const data = await response.json().catch(() => ({message:'No se ha podido guardar. Comprueba la conexión e inténtalo de nuevo.'}));
  if (!response.ok) { throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : data.message ?? 'No se ha podido completar la operación.'); }
  return data;
}
