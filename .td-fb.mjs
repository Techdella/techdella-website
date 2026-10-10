import { chromium } from '@playwright/test';
const b = await chromium.launch();
const p = await (await b.newContext({ viewport: { width: 1440, height: 1400 } })).newPage();
await p.goto('http://localhost:8081/wp-login.php'); await p.fill('#user_login','admin'); await p.fill('#user_pass','admin');
await Promise.all([p.waitForURL(/wp-admin/), p.click('#wp-submit')]);
await p.goto('http://localhost:8081/wp-admin/post-new.php?post_type=service'); await p.waitForTimeout(5000);
await p.evaluate(() => {
  document.querySelector('textarea[name="td_fields[faq]"]').value = '[{"q":"x"}]';
  wp.data.dispatch('core/editor').editPost({ title: 'E2E fallback service' });
});
await p.evaluate(() => wp.data.dispatch('core/editor').savePost()); await p.waitForTimeout(5000);
const id = await p.evaluate(() => wp.data.select('core/editor').getCurrentPostId());
await p.goto('http://localhost:8081/wp-admin/post.php?action=edit&post=' + id); await p.waitForTimeout(5000);
const t = await p.evaluate(() => document.getElementById('td-fields-fallback').innerText);
console.log(t.match(/FAQ was not saved[^\n]*/)?.[0], '\n', t.match(/Bento tiles[\s\S]{0,400}/)?.[0]);
await b.close();
