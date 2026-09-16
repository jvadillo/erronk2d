export type RubricLevel = { score: string | number; description: string };
export type RubricItem = { key: string; name: string; description?: string; module_id: number | null; weight: string | number; levels: RubricLevel[] };
export type RubricData = { name: string; items: RubricItem[] };
export type RubricModule = { id: number; code: string; name: string; cycle_id?: number; level?: number };

export const decimal = (value: string | number) => Number(String(value).replace(',', '.'));
export const percentage = (value: number) => value.toLocaleString('es-ES', { maximumFractionDigits: 2 });
export function commonScores(items: RubricItem[]): boolean {
  const first = items[0]?.levels ?? [];
  return items.every(item => item.levels.length === first.length && item.levels.every((level, index) => decimal(level.score) === decimal(first[index].score)));
}

// Reparte el redondeo a centésimas sin cambiar el total de 100 %.
export function percentageWeights(items: RubricItem[]): string[] {
  const total = items.reduce((sum, item) => sum + decimal(item.weight), 0);
  if (!items.length || total <= 0) return items.map(() => '0');
  const exact = items.map(item => decimal(item.weight) / total * 10000);
  const cents = exact.map(Math.floor);
  const order = exact.map((value, index) => ({ index, fraction: value - cents[index] })).sort((a, b) => b.fraction - a.fraction);
  const remaining = 10000 - cents.reduce((sum, value) => sum + value, 0);
  for (let index = 0; index < remaining; index++) cents[order[index % order.length].index]++;
  return cents.map(value => String(value / 100));
}
