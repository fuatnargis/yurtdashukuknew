import assert from 'node:assert/strict';
import {chromium} from 'playwright';
import {spawn,spawnSync} from 'node:child_process';
import {mkdtempSync,mkdirSync,readFileSync,writeFileSync} from 'node:fs';
import {tmpdir} from 'node:os';
import path from 'node:path';
const root=path.resolve(import.meta.dirname,'..');
const data=mkdtempSync(path.join(tmpdir(),'hukuk-motion-'));
const output=path.join(root,'tests/artifacts/motion-review');mkdirSync(output,{recursive:true});
const env={...process.env,HUKUK_DATA_DIR:data,HUKUK_DATABASE_URL:'',HUKUK_SITE_URL:'',VERCEL:'0'};
const installed=spawnSync('php',['tools/install.php'],{cwd:root,env,encoding:'utf8'});assert.equal(installed.status,0,installed.stderr);
const access=readFileSync(path.join(data,'admin-access.txt'),'utf8');
const email=/E-posta: (.+)/.exec(access)[1].trim(),password=/Parola: (.+)/.exec(access)[1].trim();
const port=29000+Math.floor(Math.random()*1000),base='http://127.0.0.1:'+port;
const server=spawn('php',['-S','127.0.0.1:'+port,'router.php'],{cwd:root,env,stdio:'ignore'});
let browser;const errors=[],results=[];let checks=0;
const ok=(test,message)=>{assert.ok(test,message);checks++;results.push(message);console.log('PASS '+message);};
const hidden=page=>page.waitForFunction(()=>!document.documentElement.classList.contains('is-site-loading')&&getComputedStyle(document.querySelector('.site-loader')).visibility==='hidden');
try{
 for(let i=0;i<50;i++){try{if((await fetch(base)).ok)break;}catch{}await new Promise(r=>setTimeout(r,100));}
 browser=await chromium.launch({channel:process.env.HUKUK_BROWSER_CHANNEL||'chrome',headless:true});
 const context=await browser.newContext();context.on('page',page=>page.on('pageerror',error=>errors.push(error.message)));
 // Hold the hero image to observe the loading identity while a genuine resource is pending.
 const page=await context.newPage();await page.setViewportSize({width:1440,height:900});
 await page.route('**/law-hero-light-v1.webp',async route=>{await new Promise(r=>setTimeout(r,1100));await route.continue();});
 await page.goto(base,{waitUntil:'domcontentloaded'});
 ok(await page.evaluate(()=>document.documentElement.classList.contains('is-site-loading')),'Opening identity is visible while the main photograph loads');
 ok(await page.locator('.site-loader img').getAttribute('src')==='/assets/images/yurtdas-seal-v2.svg','Opening animation uses the configured Yurtdaş identity');
 await page.screenshot({path:path.join(output,'loading-1440.png')});await page.waitForLoadState('load');await hidden(page);
 ok(await page.locator('.site-loader').evaluate(el=>getComputedStyle(el).pointerEvents)==='none','Loading identity never captures pointer input');
 await page.unroute('**/law-hero-light-v1.webp');
 // Different speeds, stable text and no image-edge gaps.
 for(const width of [320,390,768,1440,1920]){
  await page.setViewportSize({width,height:900});await page.goto(base,{waitUntil:'networkidle'});await hidden(page);
  await page.waitForFunction(()=>document.documentElement.classList.contains('parallax-active'));
  await page.evaluate(()=>scrollTo({top:0,behavior:'instant'}));
  const baseline=await page.locator('.juris-hero h1').evaluate(el=>el.getBoundingClientRect().top-el.closest('.juris-hero').getBoundingClientRect().top);
  await page.evaluate(()=>scrollTo({top:220,behavior:'instant'}));
  await page.waitForFunction(()=>parseFloat(document.querySelector('[data-parallax=background]').style.getPropertyValue('--parallax-y'))>0);
  const geometry=await page.evaluate(()=>{
   const bg=document.querySelector('[data-parallax=background]'),mark=document.querySelector('[data-parallax=mark]'),rect=bg.getBoundingClientRect(),frame=bg.parentElement.getBoundingClientRect();
   return {background:parseFloat(bg.style.getPropertyValue('--parallax-y')),mark:parseFloat(mark.style.getPropertyValue('--parallax-y')),heading:document.querySelector('.juris-hero h1').getBoundingClientRect().top-document.querySelector('.juris-hero').getBoundingClientRect().top,covered:rect.top<=frame.top+1&&rect.bottom>=frame.bottom-1,overflow:document.documentElement.scrollWidth>innerWidth+1};
  });
  ok(geometry.background>0&&geometry.mark<0,'Foreground and background move at different speeds at '+width+'px');
  ok(Math.abs(geometry.heading-baseline)<2&&!geometry.overflow&&geometry.covered,'Text remains stable, viewport fits and the hero stays covered at '+width+'px: '+JSON.stringify({baseline,...geometry}));
  const about=page.locator('.juris-about-visual');await about.scrollIntoViewIfNeeded();await page.locator('[data-parallax=foreground]').first().evaluate(image=>image.decode());
  await page.waitForFunction(()=>document.querySelector('[data-parallax=foreground]').style.getPropertyValue('--parallax-y')!=='');
  const first=await page.locator('[data-parallax=foreground]').first().evaluate(el=>parseFloat(el.style.getPropertyValue('--parallax-y')));
  await page.evaluate(()=>scrollBy({top:100,behavior:'instant'}));
  await page.waitForFunction(before=>parseFloat(document.querySelector('[data-parallax=foreground]').style.getPropertyValue('--parallax-y'))!==before,first);
  ok(true,'About photograph follows scrolling at '+width+'px');
  if([390,1440].includes(width))await page.screenshot({path:path.join(output,'parallax-'+width+'.png')});
 }
 // Reduced motion is honored both at navigation and when the preference changes live.
 await page.emulateMedia({reducedMotion:'reduce'});await page.goto(base,{waitUntil:'networkidle'});
 ok(await page.evaluate(()=>!document.documentElement.classList.contains('is-site-loading')&&!document.documentElement.classList.contains('parallax-active')),'Reduced motion disables loading and parallax');
 await page.emulateMedia({reducedMotion:'no-preference'});await page.waitForFunction(()=>document.documentElement.classList.contains('parallax-active'));
 await page.emulateMedia({reducedMotion:'reduce'});
 ok(await page.locator('[data-parallax=background]').evaluate(el=>el.style.getPropertyValue('--parallax-y')===''),'Changing reduced-motion preference clears image displacement');
 await page.emulateMedia({reducedMotion:'no-preference'});
 await page.goto(base,{waitUntil:'networkidle'});await hidden(page);
 await page.addInitScript(()=>{window.__loaderSeen=false;new MutationObserver(()=>{if(document.documentElement.classList.contains('is-site-loading'))window.__loaderSeen=true;}).observe(document,{subtree:true,attributes:true,attributeFilter:['class']});});
 await page.locator('.juris-about-copy .text-link').click();await page.waitForURL(base+'/kurumsal');await page.waitForLoadState('networkidle');
 ok(!(await page.evaluate(()=>window.__loaderSeen)),'Opening animation is not repeated during internal navigation');
 ok(await page.locator('.editorial-visual [data-parallax]').count()===1,'About page shares the same photo depth effect');
 await page.goBack({waitUntil:'networkidle'});ok(!(await page.evaluate(()=>document.documentElement.classList.contains('is-site-loading'))),'Back navigation restores an unobstructed page');
 const slow=await context.newPage();await slow.route('**/law-hero-light-v1.webp',async route=>{await new Promise(r=>setTimeout(r,2400));await route.abort();});
 await slow.goto(base,{waitUntil:'domcontentloaded'});await slow.waitForFunction(()=>!document.documentElement.classList.contains('is-site-loading'));
 const releaseTime=await slow.evaluate(()=>performance.now());ok(releaseTime<1800,'A stalled image cannot prolong the opening animation');await slow.close();
 const keyboard=await context.newPage();await keyboard.route('**/law-hero-light-v1.webp',async route=>{await new Promise(r=>setTimeout(r,1500));await route.continue();});
 await keyboard.goto(base,{waitUntil:'domcontentloaded'});await keyboard.keyboard.press('Tab');
 ok(!(await keyboard.evaluate(()=>document.documentElement.classList.contains('is-site-loading'))),'Keyboard interaction immediately dismisses the opening animation');await keyboard.close();
 const noJS=await browser.newContext({javaScriptEnabled:false});const staticPage=await noJS.newPage();await staticPage.goto(base,{waitUntil:'load'});
 ok(await staticPage.locator('.site-loader').evaluate(el=>getComputedStyle(el).visibility)==='hidden'&&await staticPage.locator('.juris-hero h1').isVisible(),'Without JavaScript the page remains readable and the loader stays hidden');await noJS.close();
 const dataSaving=await browser.newContext();await dataSaving.addInitScript(()=>Object.defineProperty(navigator,'connection',{value:{saveData:true,addEventListener(){}}}));
 const dataPage=await dataSaving.newPage();await dataPage.goto(base,{waitUntil:'networkidle'});
 ok(await dataPage.evaluate(()=>!document.documentElement.classList.contains('is-site-loading')&&!document.documentElement.classList.contains('parallax-active')),'Data-saving preference keeps the site static');await dataSaving.close();
 const scriptFailure=await browser.newContext();const failurePage=await scriptFailure.newPage();
 await failurePage.route('**/motion-start.js*',route=>route.fulfill({contentType:'text/javascript',body:'document.documentElement.classList.add("is-site-loading");'}));
 await failurePage.goto(base,{waitUntil:'domcontentloaded'});
 await failurePage.waitForFunction(()=>getComputedStyle(document.querySelector('.site-loader')).visibility==='hidden');
 ok(await failurePage.evaluate(()=>performance.now())<2000,'CSS fallback releases the loader if JavaScript cleanup is unavailable');await scriptFailure.close();
 const storageBlocked=await browser.newContext();await storageBlocked.addInitScript(()=>{Object.defineProperty(window,'sessionStorage',{get(){throw new Error('Storage disabled');}});});
 const storagePage=await storageBlocked.newPage();await storagePage.goto(base,{waitUntil:'networkidle'});await hidden(storagePage);
 ok(await storagePage.locator('.juris-hero h1').isVisible(),'Opening and scrolling work when browser storage is blocked');await storageBlocked.close();
 // Switch both controls through the real admin form and verify the public result.
 await page.goto(base+'/admin/login.php');await page.locator('[name=email]').fill(email);await page.locator('[name=password]').fill(password);await page.locator('button[type=submit]').click();await page.waitForURL(base+'/admin/');
 await page.goto(base+'/admin/?view=settings&group=appearance');await page.locator('[name=parallax_effect]').selectOption('off');await page.locator('[name=loading_animation]').selectOption('off');await page.locator('.settings-save button').click();await page.waitForLoadState('networkidle');
 await page.goto(base,{waitUntil:'networkidle'});
 ok(await page.locator('.site-loader').count()===0&&await page.evaluate(()=>!document.documentElement.classList.contains('parallax-active')),'Admin can disable both effects without changing page content');
 await page.goto(base+'/admin/?view=settings&group=appearance');await page.locator('[name=parallax_effect]').selectOption('gentle');await page.locator('[name=loading_animation]').selectOption('signature');await page.locator('.settings-save button').click();await page.waitForLoadState('networkidle');
 await page.goto(base,{waitUntil:'networkidle'});await hidden(page);
 ok(await page.locator('.site-loader').count()===1&&await page.evaluate(()=>document.documentElement.classList.contains('parallax-active')),'Admin can re-enable the effects');
 assert.deepEqual(errors,[]);ok(true,'No JavaScript runtime errors');
 writeFileSync(path.join(output,'results.json'),JSON.stringify({checks,results,date:new Date().toISOString()},null,2));console.log(checks+' motion checks passed.');
}finally{if(browser)await browser.close();server.kill();}
