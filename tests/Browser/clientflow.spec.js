import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';

const screenshotDirectory = path.join(process.cwd(), 'docs', 'screenshots');
const demoAdminPassword = process.env.DEMO_ADMIN_PASSWORD;

if (!demoAdminPassword) {
    throw new Error('DEMO_ADMIN_PASSWORD must be configured before running browser tests.');
}

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('Adresse e-mail').fill('admin@clientflow.ma');
    await page.getByLabel('Mot de passe', { exact: true }).fill(demoAdminPassword);
    await page.getByRole('button', { name: 'Se connecter' }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page.getByRole('heading', { name: 'Vue d’ensemble' })).toBeVisible();
}

test.beforeAll(async () => {
    await mkdir(screenshotDirectory, { recursive: true });
});

test.beforeEach(async ({ page }) => {
    const browserErrors = [];
    page.on('pageerror', error => browserErrors.push(error.message));
    page.on('console', message => {
        if (message.type() === 'error') {
            browserErrors.push(message.text());
        }
    });
    page.browserErrors = browserErrors;
});

test.afterEach(async ({ page }) => {
    expect(page.browserErrors, 'Browser console and page errors').toEqual([]);
});

test('admin can log in and log out', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByRole('heading', { name: 'Heureux de vous revoir' })).toBeVisible();
    await page.screenshot({ path: path.join(screenshotDirectory, 'login.png'), fullPage: true });

    await login(page);
    await page.getByRole('button', { name: 'Déconnexion' }).click();

    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole('heading', { name: 'Heureux de vous revoir' })).toBeVisible();
});

test('admin can use every management area and capture seeded screens', async ({ page }) => {
    await login(page);
    await page.screenshot({ path: path.join(screenshotDirectory, 'dashboard.png'), fullPage: true });

    const screens = [
        { path: '/clients', heading: 'Clients', file: 'clients.png', evidence: 'Atlas Concept' },
        { path: '/projects', heading: 'Projets', file: 'projects.png', evidence: 'Plateforme e-commerce Atlas' },
        { path: '/tasks', heading: 'Tâches', file: 'tasks.png', evidence: 'Valider les maquettes' },
        { path: '/invoices', heading: 'Factures', file: 'invoices.png', evidence: 'CF-2026-0001' },
        { path: '/payments', heading: 'Paiements', file: 'payments.png', evidence: 'CF-2026-0003' },
    ];

    for (const screen of screens) {
        await page.goto(screen.path);
        await expect(page.getByRole('heading', { name: screen.heading, exact: true })).toBeVisible();
        await expect(page.getByText(screen.evidence, { exact: false }).first()).toBeVisible();
        await page.screenshot({ path: path.join(screenshotDirectory, screen.file), fullPage: true });
    }

    await page.goto('/users');
    await expect(page.getByRole('heading', { name: 'Équipe' })).toBeVisible();
    await expect(page.getByText('Salma Benali')).toBeVisible();
});

test('language switcher changes the interface between French and English', async ({ page }) => {
    await login(page);

    await page.getByRole('button', { name: 'EN', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Overview' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Projects' })).toBeVisible();

    await page.getByRole('button', { name: 'FR', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Vue d’ensemble' })).toBeVisible();
});

test('mobile dashboard and navigation remain usable', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);

    await expect(page.getByRole('heading', { name: 'Vue d’ensemble' })).toBeVisible();
    await page.waitForTimeout(300);
    await page.screenshot({ path: path.join(screenshotDirectory, 'mobile.png'), fullPage: false });

    await page.getByRole('button', { name: 'Menu' }).click();
    await expect(page.getByRole('link', { name: 'Projets' })).toBeVisible();
    await page.getByRole('link', { name: 'Projets' }).click();
    await expect(page.getByRole('heading', { name: 'Projets', exact: true })).toBeVisible();
});
