import { test, expect, type Page } from '@playwright/test';
const password = process.env.TEST_BROWSER_PASSWORD;
if (!password) throw new Error('Falta la contraseña del entorno de navegador aislado.');

test.beforeEach(async ({ page }) => {
  const response = await page.goto('/login');
  expect(response?.ok()).toBe(true);
  const html = await response!.text();
  const data = await page.evaluate(html => {
    const document = new DOMParser().parseFromString(html, 'text/html');
    return JSON.parse(document.querySelector('script[data-page="app"]')!.textContent!);
  }, html);
  expect(data.props.test_environment, 'La instancia debe identificarse como testing antes de enviar credenciales o escribir').toBe(true);
});

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
  await page.route(page.url(), async route => {
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
  await page.getByRole('link').filter({ has: page.getByRole('heading', { name: 'Una web para nuestra comunidad' }) }).click();
  await expect(page.getByRole('button', { name: 'Mi autoevaluación', exact: true })).toBeVisible();
  await expect(page.locator('.person-tabs button')).toHaveCount(4);
  await expect(page.getByRole('heading', { name: 'Tus resultados publicados' })).toHaveCount(0);
  const chosen = page.locator('.student-rubric').first().locator('button.chosen');
  await chosen.click();
  await expect(page.getByRole('status')).toHaveText('Evaluación guardada');
  await page.goto('/');
  await page.locator('.challenge-card').filter({ has: page.locator('.badge.published') }).click();
  await expect(page.getByRole('heading', { name: 'Tus resultados publicados' })).toBeVisible();
  await expect(page.locator('.module-breakdowns section')).toHaveCount(4);
  await page.screenshot({ path: 'test-results/student-mobile.png', fullPage: true });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
});


test('organización: guardar una edición y cambiar de formulario sin arrastrar datos', async ({ page }) => {
  await login(page);
  await page.goto('/setup');
  await page.getByRole('button', { name:'Estudiante', exact:true }).first().click();
  await page.getByRole('button', { name:'Editar Ainhoa Agirre', exact:true }).click();
  await page.getByRole('dialog').getByRole('button', { name:'Guardar', exact:true }).click();
  await expect(page.getByRole('dialog')).toHaveCount(0);
  await page.getByRole('button', { name:'Profesor', exact:true }).first().click();
  await page.getByRole('button', { name:'Profesor', exact:true }).last().click();
  await expect(page.getByRole('dialog', { name:'Crear Profesor', exact:true })).toBeVisible();
  await expect(page.getByRole('dialog').getByLabel('Nombre', { exact:true })).toHaveValue('');
  await expect(page.getByRole('dialog').getByLabel('Correo', { exact:true })).toHaveValue('');
  await page.keyboard.press('Escape');
});


test('organización: alta con diez caracteres y clase, pestañas y renombrado con Evaluaciones bloqueadas', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await login(page);
  await page.goto('/setup');
  await page.getByRole('button', { name: 'Editar curso', exact: true }).first().click();
  const course = page.getByRole('dialog');
  await expect(course.locator('textarea')).toHaveAttribute('readonly', '');
  await course.getByLabel('Nombre', { exact: true }).fill('Curso revisado en navegador');
  await course.getByRole('button', { name: 'Guardar', exact: true }).click();
  await expect(course).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'Curso revisado en navegador' })).toBeVisible();
  await page.getByRole('button', { name: 'Estudiante', exact: true }).first().click();
  await expect(page.getByRole('columnheader', { name: 'CLASE', exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Estudiante', exact: true }).last().click();
  const student = page.getByRole('dialog');
  await student.getByLabel('Nombre', { exact: true }).fill('Estudiante del navegador');
  await student.getByLabel('Correo', { exact: true }).fill('browser-student@example.test');
  await student.getByLabel('Contraseña inicial', { exact: false }).fill('Clave12345');
  await expect(student.getByLabel('Clase', { exact: false })).toHaveValue('');
  await student.getByLabel('Clase', { exact: false }).selectOption({ label: '2DAW-A · Curso revisado en navegador' });
  await student.getByRole('button', { name: 'Guardar', exact: true }).click();
  await expect(student).toHaveCount(0);
  await expect(page.getByRole('row').filter({ hasText: 'browser-student@example.test' })).toContainText('2DAW-A');
  await page.getByRole('button', { name: 'Profesor', exact: true }).first().click();
  await expect(page.getByText('browser-student@example.test')).toHaveCount(0);
  expect(errors).toEqual([]);
});


test('equipos: recuperar participantes y guardar dos equipos desde los selectores', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await login(page);
  await page.getByRole('link').filter({ has: page.getByRole('heading', { name: 'Reto vacío para pruebas' }) }).click();
  await page.getByRole('button', { name: 'Gestionar', exact: true }).click();
  const dialog = page.getByRole('dialog');
  await expect(dialog.getByText('Este reto no tiene estudiantes.', { exact: false })).toBeVisible();
  await expect(dialog.getByRole('button', { name: 'Guardar equipos', exact: true })).toBeDisabled();
  await dialog.getByRole('button', { name: 'Incorporar estudiantes de la clase', exact: true }).click();
  const teams = dialog.locator('.team-editor > section');
  await expect(teams.first().getByRole('checkbox').first()).toBeVisible();
  await teams.first().getByRole('checkbox').nth(0).check();
  await teams.first().getByRole('checkbox').nth(1).check();
  await dialog.getByRole('button', { name: 'Añadir equipo', exact: true }).click();
  await expect(teams.nth(1).getByRole('checkbox').nth(0)).toBeDisabled();
  await teams.nth(1).getByRole('checkbox').nth(2).check();
  await teams.nth(1).getByRole('checkbox').nth(3).check();
  await dialog.getByRole('button', { name: 'Guardar equipos', exact: true }).click();
  await expect(dialog).toHaveCount(0);
  await page.reload();
  await page.getByRole('button', { name: 'Gestionar', exact: true }).click();
  await expect(dialog.locator('.team-editor > section')).toHaveCount(2);
  await expect(dialog.getByText('2 integrantes', { exact: true })).toHaveCount(2);
  expect(errors).toEqual([]);
});
