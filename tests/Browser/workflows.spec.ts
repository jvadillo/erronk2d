import { test, expect, type Page } from '@playwright/test';
import { readFileSync } from 'node:fs';

// This suite only targets the local, disposable demonstration database.
const base = process.env.TEST_BROWSER_URL || 'http://127.0.0.1:8082';
if (!/^http:\/\/(127\.0\.0\.1|localhost):8082$/.test(base)) {
  throw new Error('Las pruebas de navegador requieren la demostración local en el puerto 8082.');
}
const password = process.env.TEST_BROWSER_PASSWORD || readFileSync('.env', 'utf8').match(/^ERRONK2D_DEMO_PASSWORD=["']?([^\r\n"']+)/m)?.[1];
if (!password) throw new Error('Falta la contraseña de la demostración.');

async function login(page: Page, email = 'admin@erronk2d.test') {
  await page.goto('/login');
  await page.getByLabel('Correo electrónico').fill(email);
  await page.getByLabel('Contraseña', { exact: true }).fill(password!);
  await page.getByRole('button', { name: 'Entrar a Erronk2D' }).click();
  await expect(page.getByRole('heading', { name: 'Los retos, en perspectiva.' })).toBeVisible();
}

test('profesorado: matriz, teclado, configuración y seguimiento', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', e => errors.push(e.message));
  await login(page);
  await page.screenshot({ path: 'test-results/dashboard.png', fullPage: true });
  await page.getByRole('link').filter({ has: page.getByRole('heading', { name: 'Una web para nuestra comunidad' }) }).click();
  await expect(page.locator('.matrix tbody tr')).toHaveCount(20);
  await expect(page.getByLabel('Progreso por tipo de evaluación')).toContainText('Exámenes');
  const first = page.getByLabel('Examen PROG de Ainhoa Agirre', { exact: true });
  const second = page.getByLabel('Examen DWEC de Ainhoa Agirre', { exact: true });
  const oldFirst = await first.inputValue(), oldSecond = await second.inputValue();
  await page.route('**/challenges/1', async route => {
    if (route.request().method() === 'POST') await new Promise(resolve => setTimeout(resolve, 350));
    await route.continue();
  });
  try {
    await first.fill('7,1234');
    await first.press('Tab');
    await second.fill('8,4321');
    await second.press('Enter');
    await expect(page.getByText('Cambios guardados', { exact: true })).toBeVisible();
    await page.reload();
    await expect(first).toHaveValue('7.1234');
    await expect(second).toHaveValue('8.4321');
    await page.getByRole('button', { name: 'Transversales del profesorado', exact: true }).click();
    await expect(page.getByRole('dialog').locator('tbody tr')).toHaveCount(20);
    await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Configurar reto', exact: true }).click();
    await expect(page.getByRole('dialog').getByText('Defensas por módulo')).toBeVisible();
    await page.keyboard.press('Escape');
    await page.screenshot({ path: 'test-results/matrix.png', fullPage: true });
  } finally {
    await first.fill(oldFirst); await first.press('Tab');
    await second.fill(oldSecond); await second.press('Enter');
    await expect(page.getByText('Cambios guardados', { exact: true })).toBeVisible();
  }
  await page.goto('/reports');
  await expect(page.getByRole('heading', { name: 'Cada Evaluación cuenta.' })).toBeVisible();
  await expect(page.locator('tbody tr')).toHaveCount(80);
  await page.goto('/setup');
  await page.getByRole('button', { name: 'Biblioteca de rúbricas', exact: true }).click();
  await page.getByRole('button', { name: 'Duplicar rúbrica' }).first().click();
  await expect(page.getByRole('dialog').getByLabel('Nombre', { exact: true }).first()).toHaveValue(/\(copia\)/);
  await page.keyboard.press('Escape');
  expect(errors).toEqual([]);
});

test('alumnado: autoevaluación, compañeros, resultado propio y móvil', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page, 'alumno1@erronk2d.test');
  await page.goto('/challenges/1');
  await expect(page.getByRole('button', { name: 'Mi autoevaluación', exact: true })).toBeVisible();
  await expect(page.locator('.person-tabs button')).toHaveCount(4);
  await expect(page.getByRole('heading', { name: 'Tus resultados publicados' })).toHaveCount(0);
  const chosen = page.locator('.student-rubric').first().locator('button.chosen');
  await chosen.click();
  await expect(page.getByRole('status')).toHaveText('Evaluación guardada');
  await page.goto('/challenges/2');
  await expect(page.getByRole('heading', { name: 'Tus resultados publicados' })).toBeVisible();
  await expect(page.locator('.module-breakdowns section')).toHaveCount(4);
  await page.screenshot({ path: 'test-results/student-mobile.png', fullPage: true });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
});


test('organización: guardar una edición y cambiar de formulario sin arrastrar datos', async ({ page }) => {
  await login(page);
  await page.goto('/setup');
  await page.getByRole('button', { name:'Personas', exact:true }).click();
  await page.getByRole('button', { name:'Editar Ainhoa Agirre', exact:true }).click();
  await page.getByRole('dialog').getByRole('button', { name:'Guardar', exact:true }).click();
  await expect(page.getByRole('dialog')).toHaveCount(0);
  await page.getByRole('button', { name:'Profesor', exact:true }).click();
  await expect(page.getByRole('dialog', { name:'Crear Profesor', exact:true })).toBeVisible();
  await expect(page.getByRole('dialog').getByLabel('Nombre', { exact:true })).toHaveValue('');
  await expect(page.getByRole('dialog').getByLabel('Correo', { exact:true })).toHaveValue('');
  await page.keyboard.press('Escape');
});
