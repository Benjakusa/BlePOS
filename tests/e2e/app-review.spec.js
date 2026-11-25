import { createHmac } from 'node:crypto';
import { existsSync, readFileSync, writeFileSync, unlinkSync } from 'node:fs';
import { resolve } from 'node:path';
import { expect, test } from '@playwright/test';

const base32Alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function decodeBase32(value) {
    let bits = '';
    for (const character of value.toUpperCase()) {
        const digit = base32Alphabet.indexOf(character);
        if (digit < 0) throw new Error('Invalid base32 secret');
        bits += digit.toString(2).padStart(5, '0');
    }

    const bytes = [];
    for (let index = 0; index + 8 <= bits.length; index += 8) {
        bytes.push(Number.parseInt(bits.slice(index, index + 8), 2));
    }

    return Buffer.from(bytes);
}

function totp(secret, timestamp = Date.now()) {
    const counter = Math.floor(timestamp / 30_000);
    const message = Buffer.alloc(8);
    message.writeBigUInt64BE(BigInt(counter));
    const digest = createHmac('sha1', decodeBase32(secret)).update(message).digest();
    const offset = digest[digest.length - 1] & 0x0f;
    const binary = ((digest[offset] & 0x7f) << 24)
        | ((digest[offset + 1] & 0xff) << 16)
        | ((digest[offset + 2] & 0xff) << 8)
        | (digest[offset + 3] & 0xff);

    return String(binary % 1_000_000).padStart(6, '0');
}

test('stale Vite hot file serves the built stylesheet after a server restart', async ({ page }) => {
    const hotFile = resolve('public/hot');
    const previousHotFile = existsSync(hotFile) ? readFileSync(hotFile, 'utf8') : null;
    writeFileSync(hotFile, 'http://127.0.0.1:1');

    try {
        await page.goto('/tenant/login');
        const stylesheet = page.locator('link[rel="stylesheet"]').first();
        await expect(stylesheet).toHaveAttribute('href', /\/build\/assets\/app-.+\.css$/);
        const href = await stylesheet.getAttribute('href');
        const response = await page.request.get(href);
        expect(response.ok()).toBeTruthy();
    } finally {
        if (previousHotFile === null) {
            if (existsSync(hotFile)) unlinkSync(hotFile);
        } else {
            writeFileSync(hotFile, previousHotFile);
        }
    }
});

test('authenticated platform console loads and applies the design system stylesheet', async ({ page }) => {
    const hotFile = resolve('public/hot');
    const previousHotFile = existsSync(hotFile) ? readFileSync(hotFile, 'utf8') : null;
    writeFileSync(hotFile, 'http://127.0.0.1:1');

    try {
        await page.goto('/platform-console/login');
        const authCard = page.locator('.ath-auth-card');
        await expect(authCard).toBeVisible();
        const authCardBounds = await authCard.boundingBox();
        expect(Math.abs(authCardBounds.x + authCardBounds.width / 2 - page.viewportSize().width / 2)).toBeLessThan(2);
        await expect.poll(() => page.locator('.ath-img-side').evaluate(element => getComputedStyle(element).display)).toBe('none');
        await page.locator('#email').fill('platform.owner@blepos.test');
        await page.locator('#password').fill('Platform@123456!');
        await page.getByRole('button', { name: 'Sign In' }).click();

        await expect(page).toHaveURL(/\/platform-console\/mfa\/setup$/);
        const secret = (await page.locator('code').innerText()).trim();
        await page.locator('input[name="code"]').fill(totp(secret));
        await page.getByRole('button', { name: 'Confirm & Finish Setup' }).click();
        await expect(page).toHaveURL(/\/platform-console\/?$/);

        const stylesheet = page.locator('link[rel="stylesheet"]').first();
        await expect(stylesheet).toHaveAttribute('href', /\/build\/assets\/app-.+\.css$/);
        const response = await page.request.get(await stylesheet.getAttribute('href'));
        expect(response.ok()).toBeTruthy();
        expect(response.headers()['content-type']).toContain('text/css');
        await expect(page.locator('.app-shell')).toBeVisible();
        await expect(page.locator('.sidebar')).toBeVisible();
        await expect.poll(() => page.locator('.app-shell').evaluate(element => getComputedStyle(element).display)).toBe('flex');
        await expect.poll(() => page.locator('.sidebar').evaluate(element => element.getBoundingClientRect().width)).toBeGreaterThan(200);
    } finally {
        if (previousHotFile === null) {
            if (existsSync(hotFile)) unlinkSync(hotFile);
        } else {
            writeFileSync(hotFile, previousHotFile);
        }
    }
});

test('tenant login, POS checkout, product image, billing attempt, and support ticket', async ({ page }) => {
    const runId = Date.now();
    await page.goto('/tenant/login');
    await page.locator('#email').fill('restaurant.owner@blepos.co.ke');
    await page.locator('#password').fill('Owner@123456!');
    await page.getByRole('button', { name: 'Sign In' }).click();

    await expect(page).toHaveURL(/\/tenant\/mfa\/setup$/);
    const secret = (await page.locator('code').innerText()).trim();
    await page.locator('input[name="code"]').fill(totp(secret));
    await page.getByRole('button', { name: 'Enable MFA' }).click();
    await expect(page).toHaveURL(/\/tenant(?:\?.*)?$/);

    await page.goto('/tenant/pos/checkout');
    await expect(page.getByRole('heading', { name: 'POS Terminal' })).toBeVisible();
    const product = page.locator('.product-card-btn[title="Cream of Wild Forest Mushroom Soup"]');
    await expect(product).toBeVisible();
    const productName = await product.getAttribute('title');
    await product.click();
    await expect(page.locator('#cart-rows')).toContainText(productName);
    await page.locator('#pay-btn').click();
    await page.locator('#payment-method').selectOption('cash');
    const saleResponse = page.waitForResponse(response => response.url().includes('/tenant/pos/process') && response.request().method() === 'POST');
    await page.locator('#submit-sale-btn').click();
    expect((await saleResponse).ok()).toBeTruthy();
    await expect(page).toHaveURL(/\/tenant\/pos\/receipt\/\d+$/);

    await page.goto('/tenant/products/create');
    await page.locator('input[name="name"]').fill(`Browser Acceptance Product ${runId}`);
    await page.locator('input[name="sku"]').fill(`BROWSER-${runId}`);
    await page.locator('input[name="cost_price"]').fill('50.00');
    await page.locator('input[name="selling_price"]').fill('100.00');
    await page.locator('input[name="image"]').setInputFiles({
        name: 'product.png',
        mimeType: 'image/png',
        buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/vWQAAAAASUVORK5CYII=', 'base64'),
    });
    await page.getByRole('button', { name: 'Save Product' }).click();
    await expect(page.getByText(`Browser Acceptance Product ${runId}`)).toBeVisible();

    await page.goto('/tenant/subscription');
    await expect(page.getByRole('heading', { name: 'Subscription & Billing' })).toBeVisible();
    await page.locator('#sub-payment-method').selectOption('bank_transfer');
    await page.locator('form[action$="/tenant/subscription/pay"] select[name="plan_id"]').selectOption({ index: 0 });
    await page.locator('form[action$="/tenant/subscription/pay"]:has(select[name="plan_id"]) button[type="submit"]').click();
    await expect(page.getByRole('alert')).toContainText(/is pending confirmation/);

    await page.goto('/tenant/support/cases/create');
    await page.locator('input[name="subject"]').fill('Browser acceptance support request');
    await page.locator('#description').fill('Confirm the tenant support ticket form works end to end.');
    await page.getByRole('button', { name: 'Submit ticket' }).click();
    await expect(page.getByText('Browser acceptance support request')).toBeVisible();
});

test('pharmacy MFA login and medicine batch intake', async ({ page }) => {
    await page.goto('/tenant/login');
    await page.locator('#email').fill('pharmacist.cbd@afyacare.test');
    await page.locator('#password').fill('AfyaCBD@2026!');
    await page.getByRole('button', { name: 'Sign In' }).click();

    await expect(page).toHaveURL(/\/tenant\/mfa\/setup$/);
    const secret = (await page.locator('p').filter({ hasText: 'Manual key:' }).locator('code').innerText()).trim();
    await page.locator('input[name="code"]').fill(totp(secret));
    await page.getByRole('button', { name: 'Enable MFA' }).click();

    await page.goto('/tenant/pharmacy');
    await expect(page.getByRole('heading', { name: 'Pharmacy Operations' })).toBeVisible();
    const productSelect = page.locator('form[action$="/tenant/pharmacy/batches"] select[name="product_id"]');
    const productOptions = await productSelect.locator('option').allTextContents();
    const product = productOptions.find(option => option.includes('Paracetamol'));
    await productSelect.selectOption({ label: product });

    const form = page.locator('form[action$="/tenant/pharmacy/batches"]');
    await form.locator('input[name="batch_number"]').fill(`E2E-${Date.now()}`);
    await form.locator('input[name="expiry_date"]').fill(new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10));
    await form.locator('input[name="initial_quantity"]').fill('12');
    await form.getByRole('button', { name: 'Record batch' }).click();
    await expect(page.getByText('Medicine batch recorded and shop stock updated.')).toBeVisible();
});

test('parcel counter clerk receives a parcel and cannot dispatch it', async ({ page }) => {
    await page.goto('/tenant/login');
    await page.locator('#email').fill('counter.nairobi@parcelway.test');
    await page.locator('#password').fill('ParcelCounter@2026!');
    await page.getByRole('button', { name: 'Sign In' }).click();
    await expect(page).toHaveURL(/\/tenant(?:\?.*)?$/);

    await page.goto('/tenant/parcels');
    await expect(page.getByRole('heading', { name: 'Parcel Desk' })).toBeVisible();
    await page.getByRole('link', { name: 'Receive parcel' }).click();
    await expect(page.getByRole('heading', { name: 'Receive a parcel' })).toBeVisible();

    await page.locator('input[name="sender_name"]').fill('Mary Sender');
    await page.locator('input[name="sender_phone"]').fill('+254700111222');
    await page.locator('input[name="recipient_name"]').fill('John Recipient');
    await page.locator('input[name="recipient_phone"]').fill('+254711222333');
    await page.locator('input[name="contents"]').fill('Signed parcel documents');
    await page.locator('select[name="destination_shop_id"]').selectOption({ label: 'Mombasa Destination Office — Mombasa' });
    await page.locator('select[name="service_level"]').selectOption('express');
    await page.locator('input[name="fee"]').fill('450');
    await page.getByRole('button', { name: 'Receive parcel' }).click();

    await expect(page.getByText(/PW-\d{6}-[A-Z0-9]{6}/)).toBeVisible();
    await expect(page.getByText('Parcel tracking and office handoff history')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Dispatch to destination' })).toHaveCount(0);
});
