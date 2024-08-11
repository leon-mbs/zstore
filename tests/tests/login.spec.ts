import { test, expect } from '@playwright/test';
test.setTimeout(120000)
test('Login', async ({ page }) => {
  await page.goto('/');
  await page.getByLabel('Логін').click();
  await page.getByLabel('Логін').fill('admin');
  await page.getByLabel('Пароль').click();
  await page.getByLabel('Пароль').fill('admin');
  await page.getByRole('button', { name: 'Увійти' }).click();
 
   await page.getByRole('link', { name: '' }).click();
 
  await page.getByRole('link', { name: ' Сидоров' }).click();
  await page.getByRole('link', { name: ' Вийти' }).click();
});