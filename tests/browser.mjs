import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { spawn, spawnSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, readFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import http from 'node:http';

const root = path.resolve(import.meta.dirname, '..');
const data = mkdtempSync(path.join(tmpdir(), 'hukuk-browser-'));
const output = path.join(root, 'tests/artifacts/professional-review');
mkdirSync(output, { recursive: true });
const php = process.env.PHP_BIN || 'php';
const modules = spawnSync(php, ['-m'], { encoding: 'utf8' }).stdout;
const flags = [];
for (const extension of ['pdo_sqlite', 'gd']) if (!modules.split(/\r?\n/).includes(extension)) flags.push('-d', process.platform === 'win32' ? `extension=php_${extension}.dll` : `extension=${extension}`);
const env = { ...process.env, HUKUK_DATA_DIR: data, HUKUK_DATABASE_URL: '', HUKUK_SITE_URL: '', VERCEL: '0' };
const setup = spawnSync(php, [...flags, 'tools/install.php'], { cwd: root, env, encoding: 'utf8' });
assert.equal(setup.status, 0, setup.stderr);
const access = readFileSync(path.join(data, 'admin-access.txt'), 'utf8');
const email = /E-posta: (.+)/.exec(access)[1].trim();
const password = /Parola: (.+)/.exec(access)[1].trim();
const port = 20000 + Math.floor(Math.random() * 3000);
const base = `http://127.0.0.1:${port}`;
const server = spawn(php, [...flags, '-S', `127.0.0.1:${port}`, 'router.php'], { cwd: root, env, stdio: 'ignore', windowsHide: true });
let browser;
const errors = [];
let checked = 0;
try {
  for (let attempt = 0; attempt < 50; attempt++) {
    try {
      const ready = await new Promise((resolve, reject) => { const request=http.get(base,{agent:false,headers:{Connection:'close'}},response=>{response.resume();response.on('end',()=>resolve(response.statusCode===200));});request.on('error',reject); });
      if(ready)break;
    } catch { /* The isolated server is starting. */ }
    await new Promise(resolve => setTimeout(resolve, 100));
  }
  browser = await chromium.launch({ channel: process.env.HUKUK_BROWSER_CHANNEL || (process.platform === 'win32' ? 'msedge' : undefined), headless: true });
  const context = await browser.newContext();
  context.on('page', page => page.on('pageerror', error => errors.push(error.message)));
  for (const width of process.env.HUKUK_BROWSER_FOCUS ? [] : [320, 360, 390, 430, 700, 768, 820, 1024, 1100, 1200, 1280, 1440, 1920]) {
    const page = await context.newPage();
    await page.setViewportSize({ width, height: 900 });
    await page.goto(base, { waitUntil: 'networkidle' });
    const routes = await page.evaluate(() => ['/', '/kurumsal', '/calisma-alanlari', '/makaleler', '/iletisim', '/sikca-sorulan-sorular', '/makaleler?kategori=' + encodeURIComponent('İş Hukuku'), '/makaleler?q=olmayan-yayin', document.querySelector('.lawyer-tile').getAttribute('href'), document.querySelector('.article-card h3 a').getAttribute('href'), document.querySelector('.service-body h3 a').getAttribute('href')]);
    for (const route of routes) {
      const response = await page.goto(base + route, { waitUntil: 'networkidle' });
      assert.equal(response.status(), 200);
      const size = await page.evaluate(() => ({ width: innerWidth, page: document.documentElement.scrollWidth, h1: document.querySelectorAll('h1').length, brand: document.querySelector('.site-header .brand').getBoundingClientRect().right, actions: document.querySelector('.header-actions').getBoundingClientRect().left, nav: document.querySelector('.main-nav').getBoundingClientRect().toJSON(), compact: document.querySelector('.site-header').classList.contains('is-compact-nav'), header: document.querySelector('.site-header').getBoundingClientRect().height }));
      assert.ok(size.page <= width + 1, `Public overflow: ${width} ${route} ${JSON.stringify(size)}`);
      assert.equal(size.h1, 1);
      assert.ok(size.brand <= size.actions + 1, `Header collision: ${width} ${route}`);
      assert.ok(await page.locator('.header-payment').isVisible(), `Payment is visible: ${width} ${route}`);
      if (width > 1200 && !size.compact) {
        assert.ok(size.brand <= size.nav.left + 1 && size.nav.right <= size.actions + 1, 'Desktop navigation does not collide');
      }
      if (width <= 700) assert.ok(size.header <= 94, `Compact mobile header: ${JSON.stringify(size)}`);
      if (width === 390 || width === 1440) {
        const slug = route.split('?')[0].replaceAll('/', '-') || 'home';
        await page.screenshot({ path: path.join(output, `${width}${slug}${route.includes('?') ? '-filtered' : ''}.png`) });
      }
      checked++;
    }
    await page.goto(base, { waitUntil: 'networkidle' });
    for (const photo of await page.locator('.service-image img, .juris-about-visual img, .lawyer-tile-photo img, .article-image img').all()) {
      await photo.scrollIntoViewIfNeeded();
      await photo.evaluate(image => image.decode());
      assert.ok(await photo.isVisible());
    }
    await page.evaluate(() => scrollTo(0, 0));
    if (await page.locator('.menu-toggle').isVisible()) {
      await page.locator('.menu-toggle').click();
      assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'true');
      await page.locator('.submenu-toggle').first().click();
      assert.ok(await page.locator('.nav-submenu').first().isVisible());
      if (width === 390) await page.screenshot({ path: path.join(output, '390-menu.png') });
      await page.keyboard.press('Escape');
      assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'false');
    }
    if (width <= 700) {
      await page.locator('.menu-toggle').click();
      await page.locator('.mobile-menu-search').click();
    } else await page.locator('.search-toggle').click();
    assert.ok(await page.locator('#search-dialog').isVisible());
    await page.keyboard.press('Escape');
    await page.locator('.faq-list summary').first().click();
    assert.equal(await page.locator('.faq-list details').first().getAttribute('open'), '');
    if (width === 390 || width === 1440) await page.screenshot({ path: path.join(output, `${width}-full.png`), fullPage: true });
    console.log(`PASS public: ${width}px, ${routes.length} pages, payment/menu/search/photos`);
    await page.close();
  }
  const page = await context.newPage();
  await page.addInitScript(() => {
    window.__transitionStates = [];
    new MutationObserver(records => { for (const record of records) if (record.target === document.documentElement && document.documentElement.classList.contains('page-entering')) window.__transitionStates.push(document.documentElement.className); }).observe(document, { subtree: true, attributes: true, attributeFilter: ['class'] });
  });
  await page.goto(base, { waitUntil: 'networkidle' });
  await page.locator('.juris-about-copy .text-link').click();
  await page.waitForURL(base + '/kurumsal');
  await page.waitForFunction(() => window.__transitionStates.some(state => state.includes('page-entering-signature')));
  await page.waitForFunction(() => !document.documentElement.classList.contains('page-entering'));
  await page.goBack({ waitUntil: 'networkidle' });
  assert.equal(await page.locator('.page-transition-logo').evaluate(element => getComputedStyle(element).visibility), 'hidden');
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.locator('.juris-about-copy .text-link').click();
  await page.waitForURL(base + '/kurumsal');
  assert.deepEqual(await page.evaluate(() => window.__transitionStates), []);
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  console.log('PASS transitions: signature, cleanup, back navigation and reduced motion');

  await page.goto(base + '/admin/login.php');
  await page.locator('[name=email]').fill(email);
  await page.locator('[name=password]').fill(password);
  await page.locator('button[type=submit]').click();
  await page.waitForURL(base + '/admin/');
  for (const width of [320, 390, 768, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    for (const route of ['/admin/', '/admin/?view=setup', '/admin/?view=content&type=article', '/admin/?view=edit&type=team', '/admin/?view=settings&group=identity', '/admin/?view=settings&group=appearance', '/admin/?view=settings&group=layout', '/admin/?view=settings&group=footer', '/admin/?view=settings&group=payment', '/admin/?view=media', '/admin/?view=messages', '/admin/?view=backup', '/admin/?view=account', '/admin/?view=seo', '/admin/?view=redirects', '/admin/?view=sitemap', '/admin/?view=content&type=practice', '/admin/?view=edit&type=article']) {
      const response = await page.goto(base + route, { waitUntil: 'networkidle' });
      assert.equal(response.status(), 200);
      const overflow = await page.evaluate(() => ({ width: innerWidth, page: document.documentElement.scrollWidth, elements: [...document.querySelectorAll('body *')].filter(element => { const rect=element.getBoundingClientRect();return rect.right>innerWidth+1&&getComputedStyle(element).position!=='fixed'; }).slice(0,15).map(element => ({ tag:element.tagName,classes:element.className,width:element.getBoundingClientRect().width,right:element.getBoundingClientRect().right })) }));
      if(overflow.page>width+1)await page.screenshot({path:path.join(output,'admin-overflow.png')});
      assert.ok(overflow.page <= width + 1, `Admin overflow: ${width} ${route} ${JSON.stringify(overflow)}`);
      const slug = new URL(route, base).searchParams;
      if (['identity', 'appearance', 'layout', 'payment'].includes(slug.get('group'))) await page.screenshot({ path: path.join(output, `admin-${width}-${slug.get('group')}.png`) });
    }
    console.log(`PASS admin: ${width}px, 18 screens`);
  }
  await page.setViewportSize({width:1440,height:1000});
  await page.goto(base + '/admin/?view=redirects');
  const redirectForm=page.locator('.redirect-form').first();
  await redirectForm.locator('[name=source_path]').fill('/browser-eski');
  await redirectForm.locator('[name=target_path]').fill('/kurumsal');
  await redirectForm.locator('[name=status_code]').selectOption('302');
  await redirectForm.locator('button[type=submit]').click();
  await page.waitForLoadState('networkidle');
  assert.ok((await page.locator('.notice').textContent()).includes('güncellendi'));
  assert.equal((await context.request.get(base+'/browser-eski',{maxRedirects:0})).status(),302);
  await page.goto(base + '/admin/?view=settings&group=seo');
  await page.locator('[name=indexing]').check();
  await page.locator('.settings-save button').click();
  await page.waitForLoadState('networkidle');
  await page.goto(base + '/admin/?view=sitemap');
  const aboutPolicy=page.locator('details.seo-item').filter({has:page.locator('summary small',{hasText:'/kurumsal'})}).first();
  await aboutPolicy.locator('summary').click();
  await aboutPolicy.locator('[name=noindex]').check();
  await aboutPolicy.locator('form').first().locator('button[type=submit]').click();
  await page.waitForLoadState('networkidle');
  assert.ok((await (await context.request.get(base+'/kurumsal')).text()).includes('noindex,follow'));
  for(const width of [320,390,1440]){
    await page.setViewportSize({width,height:900});
    for(const view of ['redirects','sitemap']){
      await page.goto(base+'/admin/?view='+view);
      await page.locator('details.seo-item').first().locator('summary').click();
      assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Expanded SEO form fits on '+width+'px');
      await page.screenshot({path:path.join(output,view+'-'+width+'.png'),fullPage:true,animations:'disabled'});
    }
  }
  console.log('PASS SEO forms: create 302, save noindex and responsive expanded forms');
  await page.goto(base + '/admin/?view=setup');
  await page.locator('[data-setup-field="email"] a').click();
  await page.waitForURL('**/admin/?view=settings&group=identity#field-email');
  await page.waitForFunction(() => document.activeElement?.id === 'field-email');
  await page.locator('#field-email').fill('office@example.test');
  await page.locator('.settings-save button').click();
  await page.waitForLoadState('networkidle');
  await page.goto(base + '/admin/?view=setup');
  assert.equal(await page.locator('[data-setup-field="email"]').getAttribute('data-filled'), 'true');
  assert.equal((await page.locator('[data-setup-field="email"] dd').textContent()).trim(), 'office@example.test');
  await page.locator('[data-setup-field="profile-baro"] a').first().click();
  await page.waitForFunction(() => document.activeElement?.id === 'field-profile-baro');
  await page.locator('#field-profile-baro').fill('Test Barosu');
  await page.locator('.editor-sidebar button[type=submit]').click();
  await page.waitForLoadState('networkidle');
  await page.goto(base + '/admin/?view=setup');
  assert.equal(await page.locator('[data-setup-field="profile-baro"]').getAttribute('data-filled'), 'true');
  assert.ok((await page.locator('[data-setup-field="profile-baro"] dd').textContent()).includes('Test Barosu'));
  assert.equal((await page.locator('[data-setup-privacy]').textContent()).trim(), 'Metin taslak');
  for (const width of [320, 390, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Publication guide fits the viewport');
    if (width <= 760) await page.waitForFunction(() => document.querySelector('.admin-sidebar').getBoundingClientRect().right <= 1);
    await page.screenshot({ path: path.join(output, 'setup-' + width + '.png'), fullPage: true, animations: 'disabled' });
  }
  console.log('PASS publication guide: field navigation, focus, saved email/profile and responsive layout');
  await page.goto(base + '/admin/?view=settings&group=appearance');
  await page.locator('[data-settings-filter]').fill('yazı');
  assert.equal(await page.locator('.save-status').textContent(), 'Değişiklikler kaydettiğinizde siteye yansır.');
  await page.locator('[data-settings-filter]').fill('');
  for (const [key, value] of Object.entries({ background_color: '#f0f5f8', surface_color: '#ffffff', text_color: '#263840', muted_color: '#536b75', footer_color: '#283c37', footer_text_color: '#f2f6f3', card_radius: '0', body_font_size: '18', mobile_hero_height: '380', nav_font_size: '16' })) {
    const field = page.locator(`[name=${key}]`);
    await field.evaluate((input, value) => { input.value = value; input.dispatchEvent(new Event('input', { bubbles: true })); }, value);
  }
  await page.locator('.settings-save button').click();
  await page.waitForLoadState('networkidle');
  await page.goto(base + '/admin/?view=settings&group=identity');
  await page.locator('[name=logo_width]').fill('300');
  await page.locator('.settings-save button').click();
  await page.waitForLoadState('networkidle');
  await page.goto(base + '/admin/?view=settings&group=layout');
  for (const name of ['show_principles', 'show_topbar', 'show_mobile_practice_text']) await page.locator(`[name=${name}]`).check();
  await page.locator('.settings-save button').click();
  await page.waitForLoadState('networkidle');
  await page.goto(base + '/admin/?view=settings&group=sections');
  for (const name of ['show_approach', 'show_office']) await page.locator(`[name=${name}]`).check();
  await page.locator('.settings-save button').click();
  await page.waitForLoadState('networkidle');
  for (const width of [320, 390, 1280, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto(base, { waitUntil: 'networkidle' });
    const style = await page.evaluate(() => ({ surface: getComputedStyle(document.body).getPropertyValue('--surface').trim(), ink: getComputedStyle(document.body).getPropertyValue('--ink').trim(), font: getComputedStyle(document.body).fontSize, footer: getComputedStyle(document.querySelector('.site-footer')).backgroundColor, logo: getComputedStyle(document.querySelector('.brand-signature strong')).color, hero: document.querySelector('.juris-hero').getBoundingClientRect().height, overflow: document.documentElement.scrollWidth > innerWidth + 1 }));
    assert.equal(style.ink, '#263840');assert.equal(style.font, '18px');assert.equal(style.footer, 'rgb(40, 60, 55)');assert.equal(style.logo, 'rgb(32, 74, 67)');assert.equal(style.overflow, false);
    for (const section of ['principles', 'approach', 'office']) assert.ok(await page.locator(`[data-home-section=${section}]`).isVisible(), `${section} is not forced hidden on mobile`);
    if (width <= 700) { assert.equal(await page.locator('.juris-hero').evaluate(hero => getComputedStyle(hero).minHeight), '380px');assert.ok(await page.locator('.service-body p').first().isVisible());assert.ok(await page.locator('.topbar').isVisible()); }
    const header = await page.locator('.header-inner').evaluate(inner => ({ brand: inner.querySelector('.brand').getBoundingClientRect().right, actions: inner.querySelector('.header-actions').getBoundingClientRect().left }));
    assert.ok(header.brand <= header.actions + 1, 'Large typography and logos remain separated');
    await page.screenshot({ path: path.join(output, `custom-theme-${width}.png`) });
  }
  await page.setViewportSize({width:1440,height:900});
  await page.goto(base+'/admin/?view=settings&group=identity');
  await page.locator('.media-picker-open[data-target="field-logo"]').click();
  await page.locator('.media-choice[data-path="/assets/images/yurtdas-logo-v2.svg"]').click();
  await page.locator('.media-picker-open[data-target="field-logo_mark"]').click();
  await page.locator('.media-choice[data-path="/assets/images/yurtdas-seal-v2.svg"]').click();
  await page.locator('.settings-save button').click();await page.waitForLoadState('networkidle');
  for(const width of [320,390,768,1440]){
    await page.setViewportSize({width,height:900});await page.goto(base,{waitUntil:'networkidle'});
    assert.equal(await page.locator('.site-header .brand-logo').getAttribute('src'),'/assets/images/yurtdas-logo-v2.svg');
    assert.equal(await page.locator('.hero-identity-mark img').getAttribute('src'),'/assets/images/yurtdas-seal-v2.svg');
    assert.equal(await page.locator('.page-transition-logo img').getAttribute('src'),'/assets/images/yurtdas-seal-v2.svg');
    const header=await page.locator('.header-inner').evaluate(inner=>({brand:inner.querySelector('.brand').getBoundingClientRect().right,actions:inner.querySelector('.header-actions').getBoundingClientRect().left,overflow:document.documentElement.scrollWidth>innerWidth+1}));
    assert.ok(!header.overflow&&header.brand<=header.actions+1,'Custom uploaded logo fits at '+width+'px');
  }
  console.log('PASS editable logo: media picker, separate hero mark and mobile header fit');
  const vector = await page.evaluate(async () => { const image = new Image();image.src = '/assets/images/yurtdas-logo-v2.svg';await image.decode();const canvas = document.createElement('canvas');canvas.width = 398;canvas.height = 100;const context = canvas.getContext('2d');context.drawImage(image, 0, 0, 398, 100);const pixels = context.getImageData(0, 0, 398, 100).data;let colored = 0;for (let index = 0; index < pixels.length; index += 4) if (pixels[index + 3] > 0 && pixels[index] < 150 && pixels[index + 1] < 180) colored++;return colored; });
  assert.ok(vector > 2000, 'Outlined vector logo has nonblank pixels');
  assert.deepEqual(errors, []);
  console.log(`PASS browser: ${checked} public page/viewport checks, 72 admin screens, editable mobile theme and vector pixels. Isolated database: ${data}`);
} finally {
  if (browser) await browser.close();
  server.kill();
}
