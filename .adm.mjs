import { chromium } from '@playwright/test';
const b = await chromium.launch();
const out = process.argv[2];
for (const [name, port] of [['marketing', 8081], ['journal', 8082], ['studios', 8083]]) {
  const p = await b.newPage({ viewport: { width: 1280, height: 900 } });
  await p.goto(`http://localhost:${port}/wp-login.php`);
  await p.fill('#user_login', 'admin'); await p.fill('#user_pass', 'admin'); await p.click('#wp-submit');
  await p.waitForLoadState();
  const r = await p.goto(`http://localhost:${port}/wp-admin/admin.php?page=td-popups`);
  console.log(name, r.status(), await p.locator('table.widefat tbody tr').count(), await p.locator('#adminmenu a[href*="td-popups"]').count());
  if (name === 'journal') {
    await p.goto(`http://localhost:${port}/wp-admin/admin.php?page=td-popups&edit=p1`);
    await p.fill('#td-pop-title_b', 'Test *variant* B');
    await p.click('#submit');
    await p.waitForLoadState();
    console.log('saved', await p.locator('.notice-success').count(), await p.locator('text=A/B').count());
    await p.screenshot({ path: out + '/admin.png', fullPage: false });
  }
  await p.close();
}
await b.close();
