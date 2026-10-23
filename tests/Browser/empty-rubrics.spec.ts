import { test, expect, type Page } from '@playwright/test';

const password = process.env.TEST_BROWSER_PASSWORD;
if (!password) throw new Error('Falta la contraseña del entorno de navegador aislado.');

async function login(page: Page, email = 'admin@erronk2d.test') {
  const response = await page.goto('/login');
  const data = await page.evaluate(html => JSON.parse(new DOMParser().parseFromString(html, 'text/html').querySelector('script[data-page="app"]')!.textContent!), await response!.text());
  expect(data.props.test_environment).toBe(true);
  await page.getByLabel('Correo electrónico').fill(email);
  await page.getByLabel('Contraseña', { exact: true }).fill(password!);
  await page.getByRole('button', { name: 'Entrar a Erronk2D' }).click();
  await expect(page.getByRole('heading', { name: 'Tus retos.' })).toBeVisible();
}

test('reto con rúbricas nuevas: avisos, contexto fijo, primeros criterios y evaluación', async ({ page, browser }) => {
  test.setTimeout(120_000);
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await login(page);
  await page.getByRole('button', { name: 'Nuevo reto', exact: true }).click();
  const dialog = page.getByRole('dialog');
  await dialog.getByLabel('Nombre del reto').fill(`Rúbricas nuevas ${Date.now()}`);
  await dialog.getByRole('combobox', { name: 'Grupo', exact: true }).selectOption({ label: '2DAW-A · 2026-2027' });
  await dialog.getByRole('combobox', { name: 'Evaluación', exact: true }).selectOption({ label: '1.ª Evaluación' });
  await dialog.getByLabel('PROG · Programación', { exact: true }).check();
  await dialog.getByLabel('DWEC · Desarrollo web en entorno cliente', { exact: true }).check();
  await dialog.getByRole('combobox', { name: 'Rúbrica del reto', exact: true }).selectOption({ label: 'Crear nueva rúbrica' });
  await dialog.getByRole('combobox', { name: 'Rúbrica transversal', exact: true }).selectOption({ label: 'Crear nueva rúbrica' });
  await dialog.getByRole('button', { name: 'Crear reto', exact: true }).click();
  await expect(page).toHaveURL(/\/challenges\/\d+$/);
  const challengeUrl = page.url();
  await page.getByRole('link', { name: 'Ev. técnica', exact: true }).click();
  await expect(page.getByRole('alert')).toContainText('La rúbrica no contiene todavía criterios.');
  await expect(page.locator('.rubric-assessment')).toHaveCount(0);
  await page.getByRole('button', { name: 'Editar rúbrica', exact: true }).click();
  await expect(page.getByRole('combobox', { name: 'Tipo', exact: true })).toBeDisabled();
  await expect(page.getByRole('combobox', { name: 'Tipo', exact: true })).toHaveValue('team');
  await expect(page.getByLabel('Ciclo', { exact: true })).toHaveValue('Desarrollo de Aplicaciones Web');
  await expect(page.getByLabel('Ciclo', { exact: true })).toBeDisabled();
  await expect(page.getByLabel('Curso / nivel', { exact: true })).toHaveValue('2.º');
  await expect(page.getByLabel('Curso / nivel', { exact: true })).toBeDisabled();
  await expect(page.getByText('Esta rúbrica usa pesos relativos anteriores.', { exact: false })).toHaveCount(0);
  const missing = page.getByRole('status').filter({ hasText: 'La rúbrica técnica no tiene criterios específicos' });
  await expect(missing).toContainText('PROG');
  await expect(missing).toContainText('DWEC');
  await page.getByRole('button', { name: 'Cerrar aviso de módulos sin criterios' }).click();
  await expect(missing).toHaveCount(0);
  await page.getByLabel('Módulo del criterio 1', { exact: true }).selectOption({ label: 'PROG · Programación' });
  await expect(missing).toContainText('DWEC');
  await expect(missing).not.toContainText('PROG');
  await page.getByLabel('Nombre del criterio 1', { exact: true }).fill('Primera solución');
  for (let level = 1; level <= 4; level++) {
    await page.getByLabel(`Criterio 1, descripción del nivel ${level}`, { exact: true }).fill(`Solución de nivel ${level}`);
  }
  await page.getByRole('button', { name: 'Guardar cambios', exact: true }).first().click();
  await page.getByRole('button', { name: 'Confirmar y guardar', exact: true }).click();
  await expect(page.getByText('Crea los equipos del reto para poder evaluarlos.', { exact: true })).toBeVisible();
  await page.getByRole('tab', { name: 'Estudiantes y Equipos', exact: true }).click();
  await page.getByRole('button', { name: 'Añadir estudiantes', exact: true }).click();
  await page.locator('.team-student-option').filter({ hasText: 'Ainhoa Agirre' }).click();
  await page.locator('.team-student-option').filter({ hasText: 'Aitor Fernández' }).click();
  await page.getByRole('button', { name: 'Guardar equipos', exact: true }).click();
  await expect(page.locator('.toast-region').getByText('Los cambios se han guardado correctamente.', { exact: true })).toBeVisible();
  await expect(page).toHaveURL(challengeUrl);
  await page.getByRole('link', { name: 'Evaluación', exact: true }).click();
  await expect(page.locator('.matrix')).toBeVisible();
  await page.getByRole('button', { name: 'Ev. técnica', exact: true }).click();
  await page.getByRole('button', { name: 'Equipo 1, Primera solución: 8. Solución de nivel 3', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await page.reload();
  await page.getByRole('button', { name: 'Ev. técnica', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Equipo 1, Primera solución: 8. Solución de nivel 3', exact: true })).toHaveAttribute('aria-pressed', 'true');
  await page.getByRole('button', { name: 'Atrás', exact: true }).click();
  await page.getByRole('button', { name: 'Ev. transversales', exact: true }).click();
  await expect(page.getByRole('alert')).toContainText('La rúbrica no contiene todavía criterios.');

  const studentContext = await browser.newContext({ baseURL: new URL(challengeUrl).origin });
  const student = await studentContext.newPage();
  student.on('pageerror', error => errors.push(error.message));
  await login(student, 'alumno1@erronk2d.test');
  await student.goto(challengeUrl);
  await expect(student.getByRole('alert')).toContainText('La rúbrica no contiene todavía criterios.');
  await expect(student.getByRole('button', { name: 'Editar rúbrica', exact: true })).toHaveCount(0);
  await expect(student.locator('.rubric-assessment')).toHaveCount(0);
  await studentContext.close();

  await page.getByRole('button', { name: 'Editar rúbrica', exact: true }).click();
  await expect(page.getByRole('combobox', { name: 'Tipo', exact: true })).toHaveValue('transversal');
  await expect(page.getByRole('combobox', { name: 'Tipo', exact: true })).toBeDisabled();
  await expect(missing).toHaveCount(0);
  await page.getByLabel('Nombre del criterio 1', { exact: true }).fill('Colaboración');
  for (let level = 1; level <= 4; level++) {
    await page.getByLabel(`Criterio 1, descripción del nivel ${level}`, { exact: true }).fill(`Colaboración de nivel ${level}`);
  }
  await page.getByRole('button', { name: 'Guardar cambios', exact: true }).first().click();
  await page.getByRole('button', { name: 'Confirmar y guardar', exact: true }).click();
  await expect(page.locator('.rubric-assessment tbody tr')).toHaveCount(1);
  await page.getByRole('button', { name: 'Ainhoa Agirre, Colaboración: 8. Colaboración de nivel 3', exact: true }).click();
  await page.waitForLoadState('networkidle');
  expect(errors).toEqual([]);
});

test('rúbrica vacía: copiar una rúbrica compatible de la biblioteca', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await login(page);
  await page.getByRole('button', { name: 'Nuevo reto', exact: true }).click();
  const creation = page.getByRole('dialog');
  await creation.getByLabel('Nombre del reto').fill(`Rúbrica copiada ${Date.now()}`);
  await creation.getByRole('combobox', { name: 'Grupo', exact: true }).selectOption({ label: '2DAW-A · 2026-2027' });
  await creation.getByRole('combobox', { name: 'Evaluación', exact: true }).selectOption({ label: '1.ª Evaluación' });
  await creation.getByLabel('PROG · Programación', { exact: true }).check();
  await creation.getByRole('combobox', { name: 'Rúbrica del reto', exact: true }).selectOption({ label: 'Crear nueva rúbrica' });
  await creation.getByRole('combobox', { name: 'Rúbrica transversal', exact: true }).selectOption({ label: 'Crear nueva rúbrica' });
  await creation.getByRole('button', { name: 'Crear reto', exact: true }).click();
  await expect(page).toHaveURL(/\/challenges\/\d+$/);
  await page.getByRole('link', { name: 'Ev. transversales', exact: true }).click();
  const empty = page.getByRole('alert');
  await expect(empty).toContainText('La rúbrica no contiene todavía criterios.');
  await expect(empty.getByRole('button', { name: 'Editar rúbrica', exact: true })).toBeVisible();
  await empty.getByRole('button', { name: 'Copiar rúbrica', exact: true }).click();
  const dialog = page.getByRole('dialog', { name: 'Copiar rúbrica', exact: true });
  const source = dialog.getByRole('combobox', { name: 'Rúbrica de la biblioteca', exact: true });
  const value = await source.locator('option', { hasText: 'Aprender en equipo · Transversales' }).getAttribute('value');
  await source.selectOption(value!);
  await page.screenshot({ path: 'test-results/rubric-copy-dialog.png' });
  await dialog.getByRole('button', { name: 'Copiar rúbrica', exact: true }).click();
  await expect(page.locator('.toast-region').getByText('Rúbrica copiada.', { exact: false })).toBeVisible();
  await expect(dialog).toHaveCount(0);
  await expect(empty).toHaveCount(0);
  await expect(page.locator('.rubric-assessment')).toBeVisible();
  await page.reload();
  await page.getByRole('button', { name: 'Ev. transversales', exact: true }).click();
  await expect(page.locator('.rubric-assessment')).toBeVisible();
  expect(errors).toEqual([]);
});
