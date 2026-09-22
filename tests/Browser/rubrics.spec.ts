import { test, expect, type Page } from '@playwright/test';
const password = process.env.TEST_BROWSER_PASSWORD;
if (!password) throw new Error('Falta la contraseña del entorno de navegador aislado.');

test.beforeEach(async ({ page }) => {
  const response = await page.goto('/login');
  const data = await page.evaluate(html => JSON.parse(new DOMParser().parseFromString(html, 'text/html').querySelector('script[data-page="app"]')!.textContent!), await response!.text());
  expect(data.props.test_environment).toBe(true);
});

async function login(page: Page, email = 'admin@erronk2d.test') {
  await page.getByLabel('Correo electrónico').fill(email);
  await page.getByLabel('Contraseña', { exact: true }).fill(password!);
  await page.getByRole('button', { name: 'Entrar a Erronk2D' }).click();
  await expect(page.getByRole('heading', { name: 'Los retos, en perspectiva.' })).toBeVisible();
}

async function assertNoOverflow(page: Page) {
  const dimensions = await page.evaluate(() => ({
    viewport: window.innerWidth, document: document.documentElement.scrollWidth,
    outside: Array.from(document.querySelectorAll('main *')).filter(element => !element.closest('.rubric-table-scroll') && element.getBoundingClientRect().right > window.innerWidth).map(element => `${element.tagName}.${element.className}`).slice(0, 8),
  }));
  expect(dimensions.document, JSON.stringify(dimensions)).toBeLessThanOrEqual(dimensions.viewport);
}

test('rúbrica: página propia, columnas comunes, porcentajes, persistencia y móvil', async ({ page }) => {
  test.setTimeout(90_000);
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('dialog', dialog => dialog.accept());
  await login(page);
  await page.goto('/setup/rubrics');
  await page.getByRole('link', { name: 'Rúbrica', exact: true }).click();
  await expect(page).toHaveURL(/\/setup\/rubrics\/create$/);
  await expect(page.getByRole('dialog')).toHaveCount(0);
  const name = `Rúbrica de tabla · navegador ${Date.now()}`;
  await page.getByLabel('Nombre de la rúbrica').fill(name);
  await page.getByLabel('Nombre del criterio 1', { exact: true }).fill('Calidad del código');
  await page.getByLabel('Descripción del criterio 1', { exact: true }).fill('Descripción opcional larga. '.repeat(14));
  await expect(page.getByLabel('Nota del nivel 4')).toHaveValue('10');
  await page.getByRole('button', { name: 'Añadir criterio', exact: true }).click();
  await page.getByLabel('Nombre del criterio 2', { exact: true }).fill('Presentación');
  await page.getByRole('button', { name: 'Añadir nivel', exact: true }).click();
  await expect(page.locator('.rubric-editor-table tbody tr').first().locator('td,th')).toHaveCount(8);
  await expect(page.locator('.rubric-editor-table tbody tr').last().locator('td,th')).toHaveCount(8);
  await page.getByLabel('Nota del nivel 1').fill('0');
  await page.getByLabel('Nota del nivel 5').fill('9,5');
  for (let row = 1; row <= 2; row++) for (let col = 1; col <= 5; col++) {
    await page.getByLabel(`Criterio ${row}, descripción del nivel ${col}`, { exact: true }).fill(`Criterio ${row}, evidencia ${col}. ` + 'Descripción de desempeño extensa que se ajusta dentro de su columna. '.repeat(3));
  }
  await page.getByLabel('Peso del criterio 1').fill('30');
  await page.getByLabel('Peso del criterio 2').fill('60');
  await expect(page.getByRole('status').filter({ hasText: 'Faltan 10 %' })).toBeVisible();
  await page.getByRole('button', { name: 'Guardar rúbrica', exact: true }).first().click();
  await expect(page.getByRole('alert')).toContainText('Los pesos de los criterios deben sumar exactamente 100 %.');
  await expect(page.getByLabel('Nombre de la rúbrica')).toHaveValue(name);
  await page.getByLabel('Peso del criterio 2').fill('70');
  await assertNoOverflow(page);
  await page.locator('.rubric-table-scroll').evaluate(element => element.scrollLeft = 0);
  await page.locator('textarea').evaluateAll(elements => elements.forEach(element => element.scrollTop = 0));
  await page.screenshot({ path: 'test-results/rubric-editor-desktop.png', fullPage: true });
  await page.getByRole('button', { name: 'Guardar rúbrica', exact: true }).first().click();
  await expect(page).toHaveURL(/\/setup\/rubrics$/);
  const card = page.locator('.setup-grid > section').filter({ has: page.getByRole('heading', { name, exact: true }) });
  await card.getByRole('link', { name: 'Editar', exact: true }).click();
  await expect(page).toHaveURL(/\/setup\/rubrics\/\d+\/edit$/);
  await page.reload();
  await expect(page.getByLabel('Peso del criterio 1')).toHaveValue('30');
  await expect(page.getByLabel('Nota del nivel 5')).toHaveValue('9.5');
  await expect(page.getByLabel('Criterio 2, descripción del nivel 5')).toHaveValue(/evidencia 5/);
  await page.getByRole('button', { name: 'Bajar criterio 1' }).click();
  await expect(page.getByLabel('Nombre del criterio 1', { exact: true })).toHaveValue('Presentación');
  await page.getByRole('button', { name: 'Quitar nivel 5' }).click();
  await expect(page.getByLabel('Nota del nivel 5')).toHaveCount(0);
  await expect(page.locator('.rubric-editor-table tbody tr').first().locator('td,th')).toHaveCount(7);
  await page.getByRole('combobox', { name: 'Tipo', exact: true }).selectOption('transversal');
  await expect(page.getByRole('columnheader', { name: 'Módulo', exact: true })).toHaveCount(0);
  await page.setViewportSize({ width: 390, height: 844 });
  await assertNoOverflow(page);
  const organization = page.getByRole('button', { name: 'Organización', exact: true });
  if (await organization.getAttribute('aria-expanded') === 'true') await organization.click();
  await page.screenshot({ path: 'test-results/rubric-editor-mobile.png', fullPage: true });
  await page.getByRole('button', { name: 'Guardar rúbrica', exact: true }).first().click();
  await expect(page).toHaveURL(/\/setup\/rubrics$/);
  await card.getByRole('link', { name: 'Editar', exact: true }).click();
  await expect(page.getByRole('combobox', { name: 'Tipo', exact: true })).toHaveValue('transversal');
  await expect(page.getByLabel('Nombre del criterio 1', { exact: true })).toHaveValue('Presentación');
  await expect(page.getByLabel('Nota del nivel 5')).toHaveCount(0);
  expect(errors).toEqual([]);
});

test('rúbrica: evaluación docente por filas, selección persistente y permisos de módulo', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await login(page, 'profesor2@erronk2d.test');
  await page.getByRole('link').filter({ has: page.getByRole('heading', { name: 'Una web para nuestra comunidad' }) }).click();
  await expect(page).toHaveURL(/\/challenges\/\d+$/);
  const challengeUrl = page.url();
  await expect(page.locator('.workspace-heading .bottom-actions')).toBeVisible();
  await expect(page.getByText('Una única nota de reto. Todos los módulos conectados.', { exact: true })).toHaveCount(0);
  await page.getByRole('button', { name: 'Rúbrica del equipo', exact: true }).click();
  await expect(page.getByRole('dialog')).toHaveCount(0);
  await expect(page).toHaveURL(challengeUrl);
  await expect(page.locator('.rubric-assessment tbody tr')).toHaveCount(5);
  await expect(page.getByRole('button', { name: 'Atrás', exact: true })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Todos los retos', exact: true })).toHaveCount(0);
  await expect(page.locator('.page-heading.compact')).toHaveCount(0);
  await expect(page.locator('.rubric-evaluation-header')).toHaveCount(0);
  await expect(page.locator('.rubric-summary')).toHaveCount(0);
  const code = page.getByRole('row').filter({ has: page.getByRole('rowheader', { name: /Calidad del código/ }) });
  const architecture = page.getByRole('row').filter({ has: page.getByRole('rowheader', { name: /Arquitectura del servidor/ }) });
  await expect(code.locator('.rubric-criterion-name > *')).toHaveCount(2);
  await expect(code.locator('.rubric-criterion-meta > *')).toHaveCount(2);
  await expect(code.locator('.rubric-criterion-name')).toHaveCSS('flex-direction', 'column');
  const columnWidths = await page.locator('.rubric-assessment-table col').evaluateAll(columns => columns.map(column => Math.round(column.getBoundingClientRect().width)));
  expect(columnWidths.every(width => width === columnWidths[0])).toBe(true);
  const description = code.locator('.rubric-description-toggle');
  const descriptionLayout = await code.locator('.rubric-description').evaluate(element => {
    const style = getComputedStyle(element);
    return { lineClamp: style.webkitLineClamp, lineHeight: parseFloat(style.lineHeight), maxHeight: parseFloat(style.maxHeight) };
  });
  expect(descriptionLayout.lineClamp).toBe('4');
  expect(descriptionLayout.maxHeight).toBeLessThanOrEqual(descriptionLayout.lineHeight * 4 + 0.5);
  await expect(description).toHaveAttribute('aria-expanded', 'false');
  await description.click();
  await expect(description).toHaveAttribute('aria-expanded', 'true');
  await expect(code.locator('.rubric-choice').first()).toBeEnabled();
  await expect(architecture.locator('.rubric-choice').first()).toBeDisabled();
  const old = await code.locator('.chosen').getAttribute('aria-label');
  await code.getByRole('button').last().click();
  await expect(page.getByRole('status')).toHaveText('Cambios guardados');
  await page.reload();
  await page.getByRole('button', { name: 'Rúbrica del equipo', exact: true }).click();
  await expect(code.getByRole('button').last()).toHaveAttribute('aria-pressed', 'true');
  await page.locator('.rubric-table-scroll').evaluate(element => element.scrollLeft = 0);
  const tableWidth = await page.locator('.rubric-table-scroll').evaluate(element => ({ content: element.scrollWidth, viewport: element.clientWidth }));
  expect(tableWidth.content).toBeLessThanOrEqual(tableWidth.viewport);
  await page.screenshot({ path: 'test-results/rubric-assessment-desktop.png', fullPage: true });
  if (old) {
    await page.getByRole('button', { name: old, exact: true }).click();
    await expect(page.getByRole('status')).toHaveText('Cambios guardados');
  }
  await page.getByRole('button', { name: 'Siguiente', exact: true }).click();
  await page.getByRole('button', { name: 'Anterior', exact: true }).click();
  await page.getByRole('button', { name: 'Atrás', exact: true }).click();
  await page.getByRole('button', { name: 'Transversales del profesorado', exact: true }).click();
  await expect(page.getByRole('columnheader', { name: 'Módulo', exact: true })).toHaveCount(0);
  await expect(page.getByRole('combobox', { name: 'Estudiante a evaluar' }).locator('option')).toHaveCount(20);
  await expect(page.locator('.rubric-assessment tbody tr')).toHaveCount(4);
  await page.setViewportSize({ width: 390, height: 844 });
  await assertNoOverflow(page);
  await page.screenshot({ path: 'test-results/rubric-assessment-mobile.png', fullPage: true });
  expect(errors).toEqual([]);
});


test('rúbrica: convierte los pesos relativos antiguos a porcentajes al editar', async ({ page }) => {
  await login(page);
  await page.goto('/setup/rubrics');
  const card = page.locator('.setup-grid > section').filter({ has: page.getByRole('heading', { name: 'Presentación de un prototipo', exact: true }) });
  await card.getByRole('link', { name: 'Editar', exact: true }).click();
  await expect(page.getByLabel('Peso del criterio 1')).toHaveValue('50');
  await expect(page.getByLabel('Peso del criterio 2')).toHaveValue('50');
  await expect(page.getByRole('status').filter({ hasText: 'Total: 100 %' })).toBeVisible();
  await page.getByRole('button', { name: 'Añadir criterio', exact: true }).click();
  await page.getByRole('button', { name: 'Repartir pesos por igual', exact: true }).click();
  await expect(page.getByLabel('Peso del criterio 1')).toHaveValue('33.34');
  await expect(page.getByLabel('Peso del criterio 2')).toHaveValue('33.33');
  await expect(page.getByLabel('Peso del criterio 3')).toHaveValue('33.33');
  await expect(page.getByRole('status').filter({ hasText: 'Total: 100 %' })).toBeVisible();
});
