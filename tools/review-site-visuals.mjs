import {chromium} from 'playwright';
import {spawn,spawnSync} from 'node:child_process';
import {mkdtempSync,mkdirSync,writeFileSync} from 'node:fs';
import {tmpdir} from 'node:os';
import path from 'node:path';
const root=path.resolve(import.meta.dirname,'..');
const data=mkdtempSync(path.join(tmpdir(),'hukuk-visual-'));
const output=path.join(root,'tests/artifacts/identity-review');mkdirSync(output,{recursive:true});
const snapshot=spawnSync('php',[],{cwd:root,input:'<?php $s=new SQLite3("storage/site.sqlite",SQLITE3_OPEN_READONLY);$t=new SQLite3('+JSON.stringify(path.join(data,'site.sqlite'))+');if(!$s->backup($t))exit(1);',encoding:'utf8'});
if(snapshot.status!==0)throw new Error('Visual snapshot failed');
const port=28000+Math.floor(Math.random()*1000),base='http://127.0.0.1:'+port;
const env={...process.env,HUKUK_DATA_DIR:data,HUKUK_DATABASE_URL:'',HUKUK_SITE_URL:'',VERCEL:'0'};
const server=spawn('php',['-S','127.0.0.1:'+port,'router.php'],{cwd:root,env,stdio:'ignore'});let browser;
try{
 for(let i=0;i<50;i++){try{if((await fetch(base)).ok)break;}catch{}await new Promise(r=>setTimeout(r,100));}
 browser=await chromium.launch({channel:'chrome',headless:true});const page=await browser.newPage();const results=[];
 for(const width of [320,390,768,1440,1920]){
  await page.setViewportSize({width,height:1000});await page.goto(base,{waitUntil:'networkidle'});
  results.push(await page.evaluate(()=>({width:innerWidth,overflow:document.documentElement.scrollWidth>innerWidth+1,heroSize:getComputedStyle(document.querySelector('.juris-hero h1')).fontSize,weight:getComputedStyle(document.querySelector('.juris-hero h1')).fontWeight,color:getComputedStyle(document.querySelector('.juris-hero h1')).color,logo:document.querySelector('.hero-identity-mark img').getAttribute('src'),about:document.querySelector('.juris-about-visual img').getAttribute('src')})));
  for(const image of await page.locator('.service-image img,.juris-about-visual img,.article-image img,.lawyer-tile-photo img').all()){await image.scrollIntoViewIfNeeded();await image.evaluate(img=>img.decode());}
  await page.evaluate(()=>scrollTo(0,0));
  if([390,1440].includes(width))await page.screenshot({path:path.join(output,'home-'+width+'.png'),fullPage:true,animations:'disabled'});
 }
 await page.setViewportSize({width:600,height:600});await page.goto(base+'/assets/images/yurtdas-seal-v2.svg');await page.screenshot({path:path.join(output,'new-logo.png')});
 writeFileSync(path.join(output,'results.json'),JSON.stringify(results,null,2));console.log(JSON.stringify(results));
}finally{if(browser)await browser.close();server.kill();}
