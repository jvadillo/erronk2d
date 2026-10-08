import { test, expect, type Page } from '@playwright/test';

const password = process.env.TEST_BROWSER_PASSWORD;
if (!password) throw new Error('Falta la contraseña del entorno de navegador aislado.');

async function login(page: Page, email = 'admin@erronk2d.test') {
  const response = await page.goto('/login');
  expect(response?.ok()).toBe(true);
  const html = await response!.text();
  const data = await page.evaluate(html => {
    const document = new DOMParser().parseFromString(html, 'text/html');
    return JSON.parse(document.querySelector('script[data-page="app"]')!.textContent!);
  }, html);
  expect(data.props.test_environment).toBe(true);
  await page.getByLabel('Correo electrónico').fill(email);
  await page.getByLabel('Contraseña', { exact: true }).fill(password!);
  await page.getByRole('button', { name: 'Entrar a Erronk2D' }).click();
  await expect(page.getByRole('heading', { name: 'Tus retos.' })).toBeVisible();
}

async function openEvidence(page: Page) {
  await page.getByRole('link').filter({ has: page.getByRole('heading', { name: 'Una web para nuestra comunidad' }) }).click();
  await page.getByRole('link', { name: 'Evidencias', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Evidencias', exact: true })).toBeVisible();
}

test('evidencias: búsqueda, historial individual, borradores y guardado persistente', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await login(page);
  await openEvidence(page);
  const search = page.getByRole('searchbox', { name: 'Buscar estudiante o equipo' });
  const roster = page.getByRole('region', { name: 'Estudiantes', exact: true });
  const options = roster.locator('.evidence-student-option');
  await expect(options).toHaveCount(20);
  await expect(options.first()).toContainText('Ainhoa Agirre');
  await search.fill('fernandez');
  await expect(options).toHaveCount(1);
  await expect(options.first()).toContainText('Aitor Fernández');
  await options.first().click();
  const note = page.getByLabel('Nueva anotación', { exact: false });
  const draft = 'Borrador propio de Aitor';
  await note.fill(draft);
  await search.fill('ainhoa');
  await options.first().click();
  await expect(note).toHaveValue('');
  await search.fill('fernandez');
  await options.first().click();
  await expect(note).toHaveValue(draft);
  const observation = `Aportación registrada en navegador ${Date.now()}`;
  await note.fill(observation);
  const sentiment = page.getByRole('radiogroup', { name: 'Valoración de la anotación' });
  await expect(sentiment.getByRole('radio', { name: 'Neutra' })).toHaveAttribute('aria-checked', 'true');
  await sentiment.getByRole('radio', { name: 'Positiva' }).click();
  await expect(sentiment.getByRole('radio', { name: 'Positiva' })).toHaveAttribute('aria-checked', 'true');
  const save = page.getByRole('button', { name: 'Guardar anotación', exact: true });
  await save.click();
  await expect(page.getByText('Anotación guardada', { exact: true })).toBeVisible();
  await expect(page.locator('.evidence-entry').first()).toContainText(observation);
  await expect(page.locator('.evidence-entry').first()).toContainText('Positiva');
  await expect(sentiment.getByRole('radio', { name: 'Neutra' })).toHaveAttribute('aria-checked', 'true');
  await expect(note).toHaveValue('');
  await expect(save).toBeDisabled();
  await page.reload();
  await search.fill('fernandez');
  await options.first().click();
  await expect(page.locator('.evidence-entry').first()).toContainText(observation);
  await page.getByRole('button', { name: 'Limpiar búsqueda' }).click();
  await page.getByRole('button', { name: /Con anotaciones/ }).click();
  await expect(options.filter({ hasText: 'Aitor Fernández' })).toBeVisible();
  await search.fill('Ningún estudiante coincide');
  await expect(page.getByText('No hay estudiantes que coincidan con estos filtros.')).toBeVisible();
  await page.getByRole('button', { name: 'Mostrar todos' }).click();
  await expect(options).toHaveCount(20);
  await page.screenshot({ path: 'test-results/evidence-desktop.png', fullPage: true });
  await search.fill('ainhoa');
  await options.first().click();
  await expect(page.locator('.evidence-entry').filter({ hasText: observation })).toHaveCount(0);

  await page.setViewportSize({ width: 390, height: 844 });
  await expect(page.getByRole('heading', { name: 'Ainhoa Agirre', exact: true })).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
  await page.screenshot({ path: 'test-results/evidence-mobile-detail.png', fullPage: true });
  await page.getByRole('button', { name: 'Cambiar estudiante' }).click();
  await expect(search).toBeVisible();
  await search.fill('fernandez');
  await options.first().click();
  await expect(page.getByRole('heading', { name: 'Aitor Fernández', exact: true })).toBeFocused();
  await expect(page.locator('.evidence-entry').first()).toContainText(observation);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
  await page.getByRole('button', { name: 'Cambiar estudiante' }).click();
  await page.getByRole('button', { name: 'Limpiar búsqueda' }).click();
  await page.screenshot({ path: 'test-results/evidence-mobile-list.png', fullPage: true });
  expect(errors).toEqual([]);
});

test('evidencias: modo lectura y acceso restringido al profesorado', async ({ page }) => {
  await login(page);
  await page.locator('.challenge-card').filter({ has: page.locator('.badge.published') }).first().click();
  await expect(page).toHaveURL(/\/challenges\/\d+$/);
  const challengeUrl = page.url();
  await page.goto(`${challengeUrl}/evidence`);
  await expect(page.getByRole('tabpanel', { name: 'Evidencias', exact: true }).getByText('Solo lectura. El reto o el curso académico está cerrado.')).toBeVisible();
  await expect(page.locator('.evidence-student-option').first()).toBeVisible();
  await expect(page.getByRole('button', { name: 'Guardar anotación' })).toHaveCount(0);
  await page.getByRole('tab', { name: 'Estudiantes y Equipos' }).click();
  await expect(page.getByRole('button', { name: 'Guardar equipos' })).toBeDisabled();
  await expect(page.getByRole('textbox', { name: 'Nombre del equipo 1', exact: true })).toBeDisabled();
  await page.getByRole('tab', { name: 'Configuración', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Guardar configuración' })).toBeDisabled();
  await expect(page.getByLabel('Descripción', { exact: true })).toBeDisabled();
  await page.getByRole('button', { name: 'Cerrar sesión', exact: true }).click();
  await login(page, 'alumno1@erronk2d.test');
  const denied = await page.goto(`${challengeUrl}/evidence`);
  expect(denied?.status()).toBe(403);
});

test('evidencias: error de guardado conserva el texto y la ficha se adapta a tablet', async ({ page }) => {
  await login(page);
  await openEvidence(page);
  await page.getByRole('button', { name: /Ainhoa Agirre/ }).click();
  const note = page.getByLabel('Nueva anotación', { exact: false });
  await note.fill('Observación que debe conservarse si falla la validación.');
  await page.route('**/challenges/*/evidence', async route => {
    if (route.request().method() === 'POST') {
      await route.continue({ postData: JSON.stringify({ ...route.request().postDataJSON(), student_id: -1 }) });
    } else {
      await route.continue();
    }
  });
  await page.getByRole('button', { name: 'Guardar anotación', exact: true }).click();
  await expect(page.getByRole('alert')).toContainText('Selecciona un estudiante participante en este reto.');
  await expect(note).toHaveValue('Observación que debe conservarse si falla la validación.');
  await expect(page.getByRole('button', { name: 'Guardar anotación', exact: true })).toBeEnabled();
  await page.unroute('**/challenges/*/evidence');
  for (const width of [1024, 820, 768]) {
    await page.setViewportSize({ width, height: 1024 });
    await expect(note).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    expect(await page.locator('.evidence-detail-identity').evaluate(element => element.getBoundingClientRect().width)).toBeGreaterThan(120);
  }
  await page.screenshot({ path: 'test-results/evidence-tablet.png', fullPage: true });
  await page.getByRole('button', { name: 'Cambiar estudiante' }).click();
  await page.getByRole('button', { name: /Aitor Fernández/ }).click();
  await expect(page.getByRole('alert')).toHaveCount(0);
  await expect(note).toHaveValue('');
});


test('pestañas del reto: vistas, borradores, configuración persistente y navegación accesible', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await login(page);
  await page.getByRole('link').filter({ has: page.getByRole('heading', { name: 'Una web para nuestra comunidad' }) }).click();
  const tabs = page.getByRole('tablist', { name: 'Vistas del reto' });
  await expect(tabs).toHaveCount(0);
  await page.getByRole('link', { name: 'Evaluación', exact: true }).click();
  await expect(tabs.getByRole('tab')).toHaveText(['Estudiantes y Equipos', 'Evaluación', 'Evidencias', 'Configuración']);
  await expect(tabs.getByRole('tab', { name: 'Evaluación', exact: true })).toHaveAttribute('aria-selected', 'true');
  await expect(page.locator('.matrix')).toBeVisible();
  await expect(page.locator('.workspace-heading').getByRole('link', { name: 'Evidencias' })).toHaveCount(0);
  await tabs.getByRole('tab', { name: 'Estudiantes y Equipos' }).click();
  await expect(page.getByRole('dialog')).toHaveCount(0);
  const teams = page.getByRole('tabpanel', { name: 'Estudiantes y Equipos' });
  const teamName = teams.getByRole('textbox', { name: 'Nombre del equipo 1', exact: true });
  const originalTeam = await teamName.inputValue();
  await teamName.fill('Borrador de equipo');
  await tabs.getByRole('tab', { name: 'Configuración', exact: true }).click();
  const settings = page.getByRole('tabpanel', { name: 'Configuración', exact: true });
  const description = settings.getByLabel('Descripción', { exact: true });
  const originalDescription = await description.inputValue();
  const updatedDescription = `Descripción de prueba ${Date.now()}`;
  await description.fill(updatedDescription);
  await tabs.getByRole('tab', { name: 'Evidencias', exact: true }).click();
  const note = page.getByLabel('Nueva anotación', { exact: false });
  await note.fill('Borrador de evidencia entre pestañas');
  await tabs.getByRole('tab', { name: 'Estudiantes y Equipos' }).click();
  await expect(teamName).toHaveValue('Borrador de equipo');
  await teamName.fill(originalTeam);
  await tabs.getByRole('tab', { name: 'Configuración', exact: true }).click();
  await expect(description).toHaveValue(updatedDescription);
  await settings.getByRole('button', { name: 'Guardar configuración' }).click();
  await expect(settings.getByRole('status')).toHaveText('Cambios guardados');
  await tabs.getByRole('tab', { name: 'Evidencias', exact: true }).click();
  await expect(note).toHaveValue('Borrador de evidencia entre pestañas');
  await expect(page).toHaveURL(/\?tab=evidence$/);
  await page.reload();
  await expect(tabs.getByRole('tab', { name: 'Evidencias', exact: true })).toHaveAttribute('aria-selected', 'true');
  await expect(note).toHaveValue('');
  await tabs.getByRole('tab', { name: 'Configuración', exact: true }).click();
  await expect(description).toHaveValue(updatedDescription);
  await description.fill(originalDescription);
  await settings.getByRole('button', { name: 'Guardar configuración' }).click();
  await expect(settings.getByRole('status')).toHaveText('Cambios guardados');
  await tabs.getByRole('tab', { name: 'Evaluación', exact: true }).click();
  await expect(page).toHaveURL(/\?tab=evaluation$/);
  await page.goBack();
  await expect(tabs.getByRole('tab', { name: 'Configuración', exact: true })).toHaveAttribute('aria-selected', 'true');
  await tabs.getByRole('tab', { name: 'Configuración', exact: true }).focus();
  await page.keyboard.press('Home');
  await expect(tabs.getByRole('tab', { name: 'Estudiantes y Equipos' })).toBeFocused();
  await expect(teams).toBeVisible();
  await page.keyboard.press('ArrowRight');
  await expect(page.locator('.matrix')).toBeVisible();
  await page.getByRole('button', { name: 'Ev. técnica', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Rúbrica del equipo', exact: true })).toBeVisible();
  await expect(tabs).toBeVisible();
  await page.getByRole('button', { name: 'Atrás', exact: true }).click();
  await page.screenshot({ path: 'test-results/challenge-tabs-desktop.png', fullPage: true });
  for (const width of [390, 768]) {
    await page.setViewportSize({ width, height: 900 });
    for (const name of ['Estudiantes y Equipos', 'Evaluación', 'Evidencias', 'Configuración']) {
      await tabs.getByRole('tab', { name, exact: true }).click();
      await expect(page.getByRole('tabpanel', { name, exact: true })).toBeVisible();
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    }
  }
  await page.screenshot({ path: 'test-results/challenge-tabs-tablet.png', fullPage: true });
  expect(errors).toEqual([]);
});

test('inicio del reto: cuatro secciones, accesos directos y secciones sin cabecera del reto', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await login(page);
  await page.getByRole('link').filter({ has: page.getByRole('heading', { name: 'Una web para nuestra comunidad' }) }).click();
  await expect(page).toHaveURL(/\/challenges\/\d+$/);
  const challengeUrl = page.url();
  const title = page.getByRole('heading', { level: 1, name: 'Una web para nuestra comunidad', exact: true });
  const back = page.getByRole('link', { name: /^Volver al inicio del reto/ });
  await expect(title).toBeVisible();
  for (const name of ['Estudiantes y Equipos', 'Evaluación', 'Evidencias', 'Configuración']) {
    await expect(page.getByRole('heading', { level: 2, name, exact: true })).toBeVisible();
  }
  await expect(page.getByRole('tablist', { name: 'Vistas del reto' })).toHaveCount(0);
  await expect(page.locator('.matrix')).toBeHidden();
  await page.screenshot({ path: 'test-results/challenge-hub-desktop.png', fullPage: true });

  await page.getByRole('link', { name: 'Ev. técnica', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Rúbrica del equipo', exact: true })).toBeVisible();
  await expect(title).toHaveCount(0);
  await expect(page.locator('.challenge-hub-heading')).toHaveCount(0);
  await page.getByRole('button', { name: 'Atrás', exact: true }).click();
  await expect(page.locator('.matrix')).toBeVisible();
  await page.screenshot({ path: 'test-results/challenge-section-evaluation.png' });
  await back.click();
  await expect(page).toHaveURL(challengeUrl);
  await expect(title).toBeVisible();

  await page.getByRole('link', { name: 'Ev. transversales', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Transversales del profesorado', exact: true })).toBeVisible();
  await page.goBack();
  await expect(title).toBeVisible();

  await page.getByRole('link', { name: 'Tabla general', exact: true }).click();
  await expect(page).toHaveURL(`${challengeUrl}?tab=evaluation`);
  await expect(page.locator('.matrix')).toBeVisible();
  await back.click();
  await page.getByRole('link', { name: 'Gestionar equipos', exact: true }).click();
  await expect(page.getByRole('tabpanel', { name: 'Estudiantes y Equipos', exact: true })).toBeVisible();
  await back.click();
  const download = page.waitForEvent('download');
  await page.getByRole('button', { name: 'Exportar CSV', exact: true }).click();
  expect((await download).suggestedFilename()).toBe('erronk2d-reto.csv');

  for (const width of [390, 768]) {
    await page.setViewportSize({ width, height: 900 });
    await expect(title).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await page.screenshot({ path: `test-results/challenge-hub-${width}.png`, fullPage: true });
    await page.getByRole('link', { name: 'Configuración', exact: true }).click();
    await expect(page.getByRole('tab', { name: 'Configuración', exact: true })).toHaveAttribute('aria-selected', 'true');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await page.screenshot({ path: `test-results/challenge-section-${width}.png` });
    await back.click();
  }
  expect(errors).toEqual([]);
});
