import assert from 'node:assert/strict';
import {spawn, spawnSync} from 'node:child_process';
import {mkdtempSync, readFileSync, existsSync, writeFileSync, mkdirSync, unlinkSync} from 'node:fs';
import {tmpdir} from 'node:os';
import path from 'node:path';
import http from 'node:http';

async function networkFetch(url,options={}) {
  const headers=new Headers(options.headers||{});let payload;
  if(options.body instanceof URLSearchParams){payload=Buffer.from(options.body.toString());headers.set('Content-Type','application/x-www-form-urlencoded');}
  else if(options.body instanceof FormData){const encoded=new Response(options.body);payload=Buffer.from(await encoded.arrayBuffer());headers.set('Content-Type',encoded.headers.get('content-type'));}
  if(payload)headers.set('Content-Length',String(payload.length));headers.set('Connection','close');
  return new Promise((resolve,reject)=>{const req=http.request(url,{method:options.method||'GET',headers:Object.fromEntries(headers),agent:false},res=>{const chunks=[];res.on('data',c=>chunks.push(c));res.on('end',()=>{const buffer=Buffer.concat(chunks);const responseHeaders=new Headers();for(const [k,v] of Object.entries(res.headers)){if(Array.isArray(v))for(const value of v)responseHeaders.append(k,value);else if(v!==undefined)responseHeaders.set(k,v);}resolve({status:res.statusCode,ok:res.statusCode>=200&&res.statusCode<300,headers:responseHeaders,text:async()=>buffer.toString('utf8')});});res.on('error',reject);});req.on('error',reject);req.setTimeout(10000,()=>req.destroy(new Error('HTTP timeout')));req.end(payload);});
}

// Runs against an isolated database. Real website data is never edited.
const root=path.resolve(import.meta.dirname,'..');
const testData=mkdtempSync(path.join(tmpdir(),'hukuk-integration-'));
const env={...process.env,HUKUK_DATA_DIR:testData,HUKUK_DATABASE_URL:'',HUKUK_SITE_URL:'',VERCEL:'0'};
const php=process.env.PHP_BIN||'php';
const modules=spawnSync(php,['-m'],{encoding:'utf8'}).stdout;
const phpArgs=[];
if(!modules.includes('pdo_sqlite'))phpArgs.push('-d',process.platform==='win32'?'extension=php_pdo_sqlite.dll':'extension=pdo_sqlite');
if(!modules.split(/\r?\n/).includes('gd'))phpArgs.push('-d',process.platform==='win32'?'extension=php_gd.dll':'extension=gd');
const installed=spawnSync(php,[...phpArgs,'tools/install.php'],{cwd:root,env,encoding:'utf8'});
assert.equal(installed.status,0,installed.stderr);
const access=readFileSync(path.join(testData,'admin-access.txt'),'utf8');
const email=/E-posta: (.+)/.exec(access)[1].trim();
const password=/Parola: (.+)/.exec(access)[1].trim();
const port=18000+Math.floor(Math.random()*2000),base=`http://127.0.0.1:${port}`;
const server=spawn(php,[...phpArgs,'-d','upload_max_filesize=6M','-d','post_max_size=12M','-S',`127.0.0.1:${port}`,'router.php'],{cwd:root,env,stdio:['ignore','ignore','pipe'],windowsHide:true});
let logs='';server.stderr.on('data',x=>{logs+=x.toString();});
let checks=0;const results=[];
let generatedUpload='';
const generatedVectorUploads=[];
function ok(test,description){assert.ok(test,description);checks++;results.push(description);process.stdout.write(`PASS ${description}\n`);}
function client(){let cookie='';return async (route,options={})=>{const headers={...options.headers};if(cookie)headers.Cookie=cookie;const response=await networkFetch(base+route,{...options,headers});const cookies=response.headers.getSetCookie();if(cookies.length)cookie=cookies.at(-1).split(';')[0];return response;};}
const visitor=client(),admin=client();
async function html(get,route){const r=await get(route);return {status:r.status,body:await r.text(),headers:r.headers};}
function token(body){const match=/name="csrf" value="([a-f0-9]+)"/.exec(body);assert.ok(match,'CSRF field exists');return match[1];}
function formFields(body){
  const result={};for(const tag of body.matchAll(/<input\b[^>]*>/g)){const n=/\bname="([^"]+)"/.exec(tag[0]),v=/\bvalue="([^"]*)"/.exec(tag[0]);if(n&&v)result[n[1]]=v[1].replaceAll('&amp;','&').replaceAll('&quot;','"').replaceAll('&#039;',"'");}return result;
}
function decode(value){return value.replaceAll('&amp;','&').replaceAll('&quot;','"').replaceAll('&#039;',"'").replaceAll('&lt;','<').replaceAll('&gt;','>');}
function settingsValues(body){
 const form=/<form[^>]*class="[^"]*settings-form[^"]*"[^>]*>([\s\S]*?)<\/form>/.exec(body)?.[1];assert.ok(form,'Settings form exists');const out={};
 for(const match of form.matchAll(/<input\b[^>]*>/g)){const tag=match[0],n=/\bname="([^"]+)"/.exec(tag),v=/\bvalue="([^"]*)"/.exec(tag);if(!n)continue;if(/type="checkbox"/.test(tag)&&!tag.includes('checked'))continue;out[n[1]]=decode(v?.[1]||'');}
 for(const m of form.matchAll(/<textarea[^>]*name="([^"]+)"[^>]*>([\s\S]*?)<\/textarea>/g))out[m[1]]=decode(m[2]);
 for(const m of form.matchAll(/<select[^>]*name="([^"]+)"[^>]*>([\s\S]*?)<\/select>/g)){const opts=[...m[2].matchAll(/<option value="([^"]*)"([^>]*)>/g)];out[m[1]]=decode((opts.find(o=>o[2].includes('selected'))||opts[0])[1]);}return out;
}
async function post(get,route,data){return get(route,{method:'POST',body:new URLSearchParams(data)});}
async function action(data){const dash=await html(admin,'/admin/');return post(admin,'/admin/action.php',{csrf:token(dash.body),...data});}
const newEntry=(overrides={})=>({action:'save-entry',type:'article',id:'0',title:'Entegrasyon Deneme İçeriği',slug:'entegrasyon-deneme',excerpt:'Otomatik entegrasyon testi için örnek metin.',body:'## Deneme başlığı\nGüvenli içerik testi.\n<script>alert(1)</script>',body_format:'text',image:'/assets/images/library.jpg',category:'Deneme',author:'Test Editörü',status:'draft',published_at:'2026-01-01T10:00',sort_order:'0',meta_title:'',meta_description:'',og_title:'',og_description:'',og_image:'',link:'',...overrides});
try {
  for(let i=0;i<50;i++){try{const r=await networkFetch(base);if(r.ok)break;}catch{}await new Promise(r=>setTimeout(r,100));}
  const routes=['/','/makaleler','/calisma-alanlari','/kurumsal','/avukatlar','/avukatlar/halil-ibrahim-yurtdas','/sikca-sorulan-sorular','/iletisim','/sayfa/kvkk','/sayfa/cerez-politikasi','/sayfa/yasal-bilgilendirme','/makaleler/hukuki-gorusmeye-hazirlik','/calisma-alanlari/aile-hukuku'];
  const linkedAssets=new Set(),internalLinks=new Set();
  for(const route of routes){const r=await html(visitor,route);ok(r.status===200,`${route}: 200`);ok(!/Fatal error|Warning:|Parse error|\\n/.test(r.body),`${route}: no PHP errors or escaped line breaks`);ok((r.body.match(/<h1\b/g)||[]).length===1,`${route}: one h1`);for(const m of r.body.matchAll(/(?:src|href)="(\/assets\/[^"?]+)/g))linkedAssets.add(m[1]);for(const m of r.body.matchAll(/href="(\/[^"]*)"/g)){const url=new URL(decode(m[1]),base);if(!url.pathname.startsWith('/assets/')&&!url.pathname.startsWith('/admin/'))internalLinks.add(url.pathname+url.search);}}
  for(const asset of linkedAssets){const r=await visitor(asset);ok(r.status===200,`${asset}: asset exists`);}
  for(const link of internalLinks){const r=await visitor(link);ok(r.status>=200&&r.status<400,`${link}: internal link resolves`);}
  ok((await visitor('/missing-page')).status===404,'Unknown pages return 404');
  for(const blocked of ['/storage/site.sqlite','/storage/admin-access.txt','/app/seed.php','/tools/install.php','/.gitignore','/assets/uploads/.htaccess','/tests/integration.mjs'])ok((await visitor(blocked)).status===404,`${blocked}: protected`);
  ok((await visitor('/blog-list.html')).status===301,'Legacy article URL redirects');
  ok((await visitor('/basinda-biz')).status===301&&!(await html(visitor,'/')).body.includes('basinda-biz'),'Removed press page redirects and leaves no links');
  for(const route of ['/home-3.html','/about-me.html','/attorneys-4-cols.html','/practice-image-3-cols.html','/blog-single-post.html','/contact-2.html','/faq.html','/sayfa/kurumsal'])ok((await visitor(route)).status===301,`${route}: canonical redirect`);
  const home=await html(visitor,'/');ok(home.headers.get('content-security-policy')?.includes("script-src 'self'"),'CSP prevents untrusted scripts');ok(home.body.includes('noindex,nofollow'),'Sample site is not indexed by default');
  ok(home.body.includes('Yurtdaş Hukuk')&&!home.body.includes('Mizan Hukuk')&&!home.body.includes('İstanbul, Türkiye'),'Fresh site uses the correct office identity');
  ok(home.body.includes('--navy:#204a43')&&home.body.includes('--header-text:#253238')&&home.body.includes('--footer-bg:#eef3f0'),'Fresh site uses a light theme with green accents');
  ok(home.body.includes('href="/avukatlar/halil-ibrahim-yurtdas"')&&home.body.includes('class="home-lawyer-section"'),'Homepage links the lawyer card to a public profile');
  ok(home.body.includes('class="home-contact-strip"')&&home.body.includes('class="juris-about-copy"')&&!home.body.includes('class="home-consultation"'),'Homepage keeps contact and office identity ahead of optional process content');
  ok(home.body.indexOf('data-home-section="intro"')<home.body.indexOf('data-home-section="home_profile"')&&home.body.includes('class="article-grid"'),'Reference-inspired homepage shows office, founder and a concise publication grid');
  ok(home.body.includes('A Plaza, Antakya, Küçükdalyan, Antakya/Hatay')&&home.body.includes('www.google.com/maps/search/?api=1')&&home.body.includes('data-map-url="https://maps.google.com/maps?q=')&&home.body.includes('data-load-map')&&!home.body.includes('<iframe')&&home.headers.get('content-security-policy')?.includes('frame-src https://maps.google.com'),'Map contacts Google only after the visitor requests it');
  ok(home.body.includes('href="tel:05308552522"')&&home.body.includes('href="https://wa.me/905308552522"'),'Phone and WhatsApp use the supplied mobile number');
  ok(home.body.includes('class="mobile-contact-call"')&&home.body.includes('class="mobile-contact-whatsapp"')&&home.body.includes('src="/assets/images/whatsapp-brand.svg"'),'Mobile contact bar uses separate call and branded WhatsApp actions');
  ok(home.body.includes('src="/assets/images/yurtdas-seal-v2.svg"')&&home.body.includes('brand-signature')&&home.body.includes('class="header-payment"'),'Public header has the independent vector identity and payment action');
  ok(home.body.includes('data-page-transition="signature"')&&home.body.includes('@view-transition{navigation:auto}')&&home.body.includes('page-transitions.js'),'Public navigation progressively enhances normal page transitions');
  const lawyer=await html(visitor,'/avukatlar/halil-ibrahim-yurtdas');
  ok(lawyer.body.includes('Halil İbrahim Yurtdaş tarafından yazılanlar')&&lawyer.body.includes('class="article-grid"'),'Lawyer profile lists the lawyer’s published articles');
  ok(!lawyer.body.includes('| Yurtdaş Hukuk | Yurtdaş Hukuk'),'Profile title does not duplicate the office name');
  const authored=await html(visitor,'/makaleler/hukuki-gorusmeye-hazirlik');
  ok(authored.body.includes('href="/avukatlar/halil-ibrahim-yurtdas" rel="author"'),'Article byline links to the lawyer profile');
  ok(authored.body.includes('<a class="author-card')&&authored.body.includes('href="/avukatlar/halil-ibrahim-yurtdas"'),'Article author card is entirely clickable');
  ok(!/<p[^>]*>\s*<p[ >]/.test(home.body),'Homepage rich text does not nest paragraph elements');
  ok(!home.body.includes('data-juris-slider')&&!home.body.includes('data-slider-pause'),'Homepage uses a static hero without autoplay');
  ok(home.body.includes('--sans:Arial,Helvetica,sans-serif;--serif:Georgia,"Times New Roman",serif;--heading:Georgia,"Times New Roman",serif'),'Fresh site pairs readable Arial body text with classic headings');
  ok(home.body.includes('href="/calisma-alanlari"')&&home.body.includes('href="/calisma-alanlari/aile-hukuku"'),'Homepage links to the practice directory and published practice pages');
  ok(home.body.includes('<h1 id="hero-title">Yurtdaş Hukuk</h1>')&&!home.body.includes('Büyük bürolarda')&&!home.body.includes('aynı gün'),'Homepage identifies the office without comparisons or response promises');
  const search=await html(visitor,'/makaleler?q='+encodeURIComponent('sözleşme'));ok(search.body.includes('Sözleşme imzalamadan')&&!search.body.includes('Aradığınız konuda yayın bulunamadı.'),'Turkish search finds matching publication');
  ok((await html(visitor,'/makaleler?q=imkansiz-xyz')).body.includes('Aradığınız konuda yayın bulunamadı.'),'Search has an empty state');
  ok((await html(visitor,'/makaleler?kategori='+encodeURIComponent('İş Hukuku'))).body.includes('class="list-count">1 yazı'),'Category filter reduces result set');
  ok((await visitor('/admin/')).status===303,'Admin access requires authentication');
  ok((await visitor('/admin/?view=setup')).status===303,'Publication guide requires authentication');
  ok((await post(visitor,'/admin/action.php',{action:'save-settings'})).status===303,'Unauthenticated mutation is rejected');
  let login=await html(admin,'/admin/login.php');
  ok((await post(admin,'/admin/login.php',{csrf:'invalid',email,password})).status===419,'Login rejects invalid CSRF');
  login=await html(admin,'/admin/login.php');
  ok((await post(admin,'/admin/login.php',{csrf:token(login.body),email,password:'wrong-password'})).status===401,'Wrong password is rejected');
  login=await html(admin,'/admin/login.php');
  ok((await post(admin,'/admin/login.php',{csrf:token(login.body),email,password})).status===303,'Administrator can sign in');
  const setupGuide=await html(admin,'/admin/?view=setup');
  ok(setupGuide.status===200&&setupGuide.body.includes('data-setup-field="email" data-filled="false"')&&setupGuide.body.includes('data-setup-field="profile-baro" data-filled="false"'),'Publication guide identifies missing email and professional information');
  ok(setupGuide.body.includes('group=identity#field-email')&&setupGuide.body.includes('#field-profile-baro')&&setupGuide.body.includes('Yayın sırasında tamamlanacak'),'Publication guide links directly to fields and defers hosting setup');
  ok(setupGuide.body.includes('data-setup-privacy>Metin taslak')&&setupGuide.body.includes('KVKK tamamlanmasını bekliyor'),'Publication guide explains why the contact form is unavailable');
  ok(!(await html(visitor,'/iletisim')).body.includes('class="contact-form"'),'Draft privacy notice does not collect contact data');
  await action({action:'save-settings',group:'privacy',privacy_notice_reviewed:'1'});
  ok(!(await html(visitor,'/iletisim')).body.includes('class="contact-form"'),'Unfinished privacy template cannot be approved');
  const privacyRows=(await html(admin,'/admin/?view=content&type=page')).body;
  const privacyId=/href="\/admin\/\?view=edit&amp;type=page&amp;id=(\d+)"><span>Kişisel Verilerin Korunması/.exec(privacyRows)?.[1];
  assert.ok(privacyId,'Privacy page exists');
  const privacyEdit=await html(admin,`/admin/?view=edit&type=page&id=${privacyId}`);
  const policy={type:'page',id:privacyId,title:'Kişisel Verilerin Korunması',slug:'kvkk',excerpt:'İzole test ortamı.',body:'Yalnızca otomatik test ortamında kullanılan aydınlatma metni. Gerçek başvuru alınmaz.',status:'published'};
  await action(newEntry({...policy,original_updated_at:formFields(privacyEdit.body).original_updated_at}));
  await action({action:'save-settings',group:'privacy',privacy_notice_reviewed:'1'});
  ok((await html(visitor,'/iletisim')).body.includes('class="contact-form"'),'Reviewed privacy notice enables the contact form');
  const reviewedGuide=(await html(admin,'/admin/?view=setup')).body;
  ok(reviewedGuide.includes('data-setup-privacy>Mevcut metin doğrulandı')&&reviewedGuide.includes('İletişim formu: Açık'),'Publication guide reflects the reviewed notice and enabled form');
  for(const route of ['/admin/','/admin/?view=content&type=article','/admin/?view=content&type=team','/admin/?view=edit&type=article','/admin/?view=settings','/admin/?view=settings&group=home','/admin/?view=settings&group=sections','/admin/?view=settings&group=appearance','/admin/?view=settings&group=footer','/admin/?view=settings&group=texts','/admin/?view=settings&group=seo','/admin/?view=media','/admin/?view=messages','/admin/?view=backup','/admin/?view=account']){const r=await html(admin,route);ok(r.status===200&&!/Fatal error|Warning:|Parse error/.test(r.body),`${route}: authenticated screen renders`);}
  const teamRows=await html(admin,'/admin/?view=content&type=team');const profileId=/view=edit&amp;type=team&amp;id=(\d+)/.exec(teamRows.body)?.[1];assert.ok(profileId,'Owner profile is available in the admin');
  const profileEditor=await html(admin,`/admin/?view=edit&type=team&id=${profileId}`);const profileFields=formFields(profileEditor.body);
  ok(profileEditor.body.includes('name="category"')&&profileEditor.body.includes('name="image"'),'Admin can edit the lawyer role and portrait');
  const profileSave=await action(newEntry({type:'team',id:profileId,original_updated_at:profileFields.original_updated_at,title:'Halil İbrahim Yurtdaş',slug:'halil-ibrahim-yurtdas',excerpt:'Panelden düzenlenen avukat profili.',body:'Avukat profili panelden düzenlendi.',image:'',category:'Avukat',author:'',status:'published',published_at:'2026-01-01T10:00'}));
  ok(profileSave.status===303&&(await html(visitor,'/avukatlar/halil-ibrahim-yurtdas')).body.includes('Panelden düzenlenen avukat profili.'),'Admin profile changes appear on the public page');
  ok(profileEditor.body.includes('name="profile[baro]"')&&profileEditor.body.includes('name="profile[tbb_number]"'),'Profile editor exposes professional information');
  let factsEdit=await html(admin,`/admin/?view=edit&type=team&id=${profileId}`);
  await action(newEntry({type:'team',id:profileId,original_updated_at:formFields(factsEdit.body).original_updated_at,title:'Halil İbrahim Yurtdaş',slug:'halil-ibrahim-yurtdas',image:'',category:'Avukat',status:'published','profile[baro]':'Test Barosu','profile[baro_number]':'TEST-123','profile[started]':'2020-01-15','profile[kep]':'test@example.test'}));
  const facts=(await html(visitor,'/avukatlar/halil-ibrahim-yurtdas')).body;
  ok(facts.includes('Test Barosu')&&facts.includes('TEST-123')&&facts.includes('15 Ocak 2020'),'Professional information is stored and displayed');
  ok(!facts.includes('<dt>Mezun olunan üniversite</dt>'),'Empty professional information is hidden');
  const savedProfileGuide=(await html(admin,'/admin/?view=setup')).body;
  ok(savedProfileGuide.includes('data-setup-field="profile-baro" data-filled="true"')&&savedProfileGuide.includes('TEST-123')&&savedProfileGuide.includes('15 Ocak 2020'),'Publication guide updates after saving professional information');
  factsEdit=await html(admin,`/admin/?view=edit&type=team&id=${profileId}`);
  await action(newEntry({type:'team',id:profileId,original_updated_at:formFields(factsEdit.body).original_updated_at,title:'Halil İbrahim Yurtdaş',slug:'halil-ibrahim-yurtdas',status:'published','profile[started]':'2099-01-01'}));
  ok((await html(visitor,'/avukatlar/halil-ibrahim-yurtdas')).body.includes('TEST-123'),'Invalid professional date cannot overwrite saved profile');
  ok((await html(admin,'/admin/?view=edit&type=article')).body.includes('list="profile-authors"'),'Article editor offers published lawyer profiles as authors');
  const initialLayout={...settingsValues((await html(admin,'/admin/?view=settings&group=layout')).body)};
  await action({...initialLayout,show_home_contacts:'0'});
  ok(!(await html(visitor,'/')).body.includes('class="home-contact-strip"'),'Admin can hide the homepage contact strip');
  await action({...initialLayout,show_home_consultation:'1'});
  ok((await html(visitor,'/')).body.includes('class="home-consultation-steps"'),'Admin can enable the optional consultation process');
  const homeSettings={...settingsValues((await html(admin,'/admin/?view=settings&group=home')).body)};
  ok('home_profile_title' in homeSettings&&'home_map_title' in homeSettings,'Admin exposes profile and map section headings');
  ok('consultation_step_1_title' in homeSettings&&'consultation_step_3_text' in homeSettings,'Admin exposes all consultation step text');
  await action({...homeSettings,consultation_step_1_title:'Test görüşme adımı'});
  ok((await html(visitor,'/')).body.includes('Test görüşme adımı'),'Admin consultation text changes appear on the homepage');
  await action(homeSettings);
  await action({...homeSettings,home_map_title:'Test harita başlığı'});
  ok((await html(visitor,'/')).body.includes('Test harita başlığı'),'Admin map heading change appears on the homepage');
  await action(homeSettings);
  const identity={...settingsValues((await html(admin,'/admin/?view=settings&group=identity')).body)};
  await action({...identity,email:'office@example.test',address:'Deneme Adresi, Antakya/Hatay',map_query:'',phone:'0530 123 45 67',whatsapp:'0530 123 45 67'});
  const changedContact=(await html(visitor,'/')).body;
  ok(changedContact.includes('Deneme Adresi, Antakya/Hatay')&&changedContact.includes('query=Deneme%20Adresi')&&changedContact.includes('maps.google.com/maps?q=Deneme%20Adresi%2C%20Antakya%2FHatay')&&changedContact.includes('tel:05301234567')&&changedContact.includes('wa.me/905301234567'),'Admin address and phone updates propagate to the map, call and WhatsApp links');
  const savedIdentityGuide=(await html(admin,'/admin/?view=setup')).body;
  ok(savedIdentityGuide.includes('data-setup-field="email" data-filled="true"')&&savedIdentityGuide.includes('office@example.test'),'Publication guide updates after saving the office email');
  await action(identity);
  ok((await post(admin,'/admin/action.php',{action:'save-entry',csrf:'invalid'})).status===419,'Mutation rejects invalid CSRF');
  let saved=await action(newEntry());ok(saved.status===303&&saved.headers.get('location').includes('id='),'Draft can be created');
  let editURL=saved.headers.get('location');let edit=await html(admin,editURL);let fields=formFields(edit.body);const id=fields.id;
  ok(edit.body.includes('id="rich-editor"')&&edit.body.includes('data-rich="link"')&&edit.body.includes('data-rich="unlink"')&&edit.body.includes('data-rich="table"')&&edit.body.includes('/assets/rich-editor.js'),'Article editor exposes visual formatting and link controls');
  ok(edit.body.includes('name="body_format"')&&edit.body.includes('name="og_title"')&&edit.body.includes('name="og_image"')&&edit.body.includes('name="slug"'),'Article editor exposes text/HTML, slug, metadata and OG fields');
  ok((await visitor('/makaleler/entegrasyon-deneme')).status===404,'Draft is hidden from public');
  ok((await html(admin,`/admin/?view=preview&id=${id}`)).body.includes('&lt;script&gt;alert(1)&lt;/script&gt;'),'Preview escapes script markup');
  saved=await action(newEntry({id,original_updated_at:fields.original_updated_at,status:'published'}));
  let published=await html(visitor,'/makaleler/entegrasyon-deneme');ok(published.status===200,'Published entry appears on website');ok(published.body.includes('&lt;script&gt;alert(1)&lt;/script&gt;')&&!published.body.includes('<script>alert(1)</script>'),'Public content escapes HTML');
  const htmlSaved=await action(newEntry({title:'HTML Makale Denemesi',slug:'html-makale-denemesi',body_format:'html',body:'<h2>HTML bölüm başlığı</h2><p>Güvenli <strong>vurgulu</strong> metin. <a href="https://example.com">Kaynak</a></p><script>alert(1)</script><img src="/assets/images/library.jpg" onerror="alert(1)" alt="Kütüphane">',status:'published',meta_title:'Özel meta başlığı',meta_description:'Özel meta açıklaması',og_title:'Özel paylaşım başlığı',og_description:'Özel paylaşım açıklaması',og_image:'/assets/images/library.jpg'}));
  ok(htmlSaved.status===303,'HTML article with separate SEO and OG fields saves');
  const htmlArticle=await html(visitor,'/makaleler/html-makale-denemesi');
  ok(htmlArticle.body.includes('<h2 id="html-bolum-basligi">HTML bölüm başlığı</h2>')&&htmlArticle.body.includes('<strong>vurgulu</strong>')&&htmlArticle.body.includes('href="https://example.com" rel="noopener noreferrer"'),'Allowed HTML renders with article headings and safe links');
  ok(!htmlArticle.body.includes('<script>alert(1)</script>')&&!htmlArticle.body.includes('onerror='),'HTML sanitizer removes scripts and event handlers');
  ok(htmlArticle.body.includes('<title>Özel meta başlığı |')&&htmlArticle.body.includes('property="og:title" content="Özel paylaşım başlığı"')&&htmlArticle.body.includes('property="og:description" content="Özel paylaşım açıklaması"'),'SEO and OG metadata render independently');
  await action(newEntry({id,original_updated_at:'stale',title:'Yanlış Üzerine Yazma',status:'published'}));
  ok(!(await html(visitor,'/makaleler/entegrasyon-deneme')).body.includes('Yanlış Üzerine Yazma'),'Stale edit cannot overwrite current content');
  // Consume the failed form state before reading fresh optimistic-lock token.
  await html(admin,editURL);edit=await html(admin,editURL);fields=formFields(edit.body);
  await action(newEntry({id,original_updated_at:fields.original_updated_at,status:'published',title:'Yeni İçerik Başlığı'}));
  ok((await html(visitor,'/makaleler/entegrasyon-deneme')).body.includes('Yeni İçerik Başlığı'),'Edit is immediately reflected on website');
  edit=await html(admin,editURL);const revisionId=/name="revision_id" value="(\d+)"/.exec(edit.body)[1];
  await action({action:'restore-revision',id,type:'article',revision_id:revisionId});
  ok((await html(visitor,'/makaleler/entegrasyon-deneme')).body.includes('Entegrasyon Deneme İçeriği'),'Previous revision can be restored');
  await action({action:'archive-entry',id,type:'article'});ok((await visitor('/makaleler/entegrasyon-deneme')).status===404,'Archived content leaves public website');
  await action({action:'restore-entry',id,type:'article'});ok((await visitor('/makaleler/entegrasyon-deneme')).status===404,'Restored archive becomes a private draft');
  await action(newEntry({slug:'gelecek-yayin',title:'Gelecekteki Yayın',status:'published',published_at:'2099-01-01T10:00'}));ok((await visitor('/makaleler/gelecek-yayin')).status===404,'Scheduled publication remains hidden until date');
  for(const [type,slug,url,category] of [['practice','yeni-alan','/calisma-alanlari/yeni-alan','scale'],['page','ek-sayfa','/sayfa/ek-sayfa','']]){
    await action(newEntry({type,slug,title:'Otomatik '+type,category,status:'published'}));
    ok((await html(visitor,url)).body.includes('Otomatik '+type),`${type}: content type publishes successfully`);
  }
  await action(newEntry({type:'menu',slug:'ek-menu',title:'Ek menü',status:'published',link:'/sayfa/ek-sayfa'}));
  ok((await html(visitor,'/')).body.includes('>Ek menü</a>'),'Menu changes appear in public navigation');
  await action(newEntry({type:'faq',slug:'ek-soru',title:'Test için ek bir soru?',status:'published',body:'Otomatik soru yanıtı.'}));
  const faqEditor=(await html(admin,'/admin/?view=edit&type=faq')).body;
  ok(faqEditor.includes('id="rich-editor"')&&faqEditor.includes('type="hidden" name="body_format" id="body-format"'),'FAQ editor can save rich HTML');
  ok((await html(visitor,'/')).body.includes('Otomatik soru yanıtı.'),'FAQ content is editable');
  const textSettings=await html(admin,'/admin/?view=settings&group=texts');
  const textValues={action:'save-settings',group:'texts'};for(const m of textSettings.body.matchAll(/<textarea name="([^"]+)"[^>]*>([\s\S]*?)<\/textarea>/g))textValues[m[1]]=m[2].replaceAll('&amp;','&').replaceAll('&quot;','"').replaceAll('&#039;',"'");
  textValues.text_profile_link='Profil ayrıntılarını aç';textValues.text_founder_title='Avukatımız';textValues.text_profile_contact='Ofise ulaşın';
  textValues.text_principle_1_title='Panelden Güncel İlke';textValues.text_contact_form_button='Yeni form butonu';
  await action(textValues);ok((await html(visitor,'/kurumsal')).body.includes('Panelden Güncel İlke'),'Shared section text is editable');ok((await html(visitor,'/iletisim')).body.includes('Yeni form butonu'),'Public form button text is editable');
  ok((await html(visitor,'/')).body.includes('Profil ayrıntılarını aç')&&(await html(visitor,'/avukatlar')).body.includes('<h1>Avukatımız</h1>')&&(await html(visitor,'/avukatlar/halil-ibrahim-yurtdas')).body.includes('Ofise ulaşın'),'Profile and directory interface labels are editable');
  // Sitemap links for reserved pages must use their canonical route.
  const pageRows=await html(admin,'/admin/?view=content&type=page');const kurumsalId=/href="\/admin\/\?view=edit&amp;type=page&amp;id=(\d+)"><span>Hakkımızda/.exec(pageRows.body)?.[1];
  assert.ok(kurumsalId,'Corporate page exists');const corporateEdit=await html(admin,`/admin/?view=edit&type=page&id=${kurumsalId}`);const corporateFields=formFields(corporateEdit.body);
  await action(newEntry({id:kurumsalId,type:'page',title:'Kurumsal',slug:'kurumsal',status:'published',original_updated_at:corporateFields.original_updated_at,meta_title:'Özel Kurumsal Başlığı',meta_description:'Özel kurumsal açıklaması'}));
  ok((await html(visitor,'/kurumsal')).body.includes('<title>Özel Kurumsal Başlığı'),'Corporate page respects its own SEO fields');
  await action({action:'archive-entry',id:kurumsalId,type:'page'});ok((await visitor('/kurumsal')).status===200,'Core corporate page cannot be archived');
  const footerSettings={...settingsValues((await html(admin,'/admin/?view=settings&group=footer')).body)};
  await action({...footerSettings,footer_text:'Entegrasyon testi alt bilgisi',footer_extra_links:'Özel Kaynak|/sayfa/kvkk\nTekrarlanan Makaleler|/makaleler'});
  const changedFooter=(await html(visitor,'/')).body;ok(changedFooter.includes('Entegrasyon testi alt bilgisi')&&changedFooter.includes('>Özel Kaynak</a>'),'Footer text and additional links are admin editable');
  ok(!changedFooter.slice(changedFooter.indexOf('<footer')).includes('>Tekrarlanan Makaleler</a>'),'Footer avoids repeating a main menu link as an extra link');
  await action(footerSettings);
  const appearance={...settingsValues((await html(admin,'/admin/?view=settings&group=appearance')).body),primary_color:'#142b36',accent_color:'#aa8855'};
  await action(appearance);ok((await html(visitor,'/')).body.includes('--gold:#aa8855'),'Appearance settings persist and appear publicly');
  await action({...appearance,hero_overlay_opacity:'25'});ok((await html(visitor,'/')).body.includes('--hero-opacity:0.25;'),'Admin can adjust the hero overlay after its natural-color default');
  await action(appearance);
  await action({...appearance,body_font:'sans',body_font_size:'18',section_heading_size:'36',mobile_hero_height:'380',page_transition:'off'});
  const customTypography=(await html(visitor,'/')).body;
  ok(customTypography.includes('--sans:Manrope,Arial,sans-serif;')&&customTypography.includes('--body-font-size:18px;')&&customTypography.includes('--section-heading-size:36px;')&&customTypography.includes('--mobile-hero-height:380px;'),'Body typography, heading size and mobile hero height are editable');
  ok(customTypography.includes('data-page-transition="off"')&&!customTypography.includes('@view-transition{navigation:auto}'),'Administrator can disable page transitions');
  await action({...appearance,body_font_size:'50',page_transition:'bad'});
  ok((await html(visitor,'/')).body.includes('--body-font-size:18px;'),'Out-of-range typography cannot replace the saved theme');
  await action(appearance);
  await action({...appearance,card_radius:'0'});
  ok(settingsValues((await html(admin,'/admin/?view=settings&group=appearance')).body).card_radius==='0','Zero corner radius survives reopening the theme editor');
  await action(appearance);
  await action({...appearance,accent_color:'red;display:none'});ok((await html(visitor,'/')).body.includes('--gold:#aa8855'),'Invalid theme CSS is rejected');
  const themeState=await html(visitor,'/');ok(themeState.body.includes('theme-reference header-inline'),'New theme uses inline header');
  ok(/class="hero-image is-active"[^>]*fetchpriority="high"/.test(themeState.body),'Configured hero image renders with loading priority');
  ok(!themeState.body.includes('data-slide-to')&&themeState.body.includes('class="juris-hero-media"'),'Homepage keeps one static image instead of a rotating slider');
  const sections={...settingsValues((await html(admin,'/admin/?view=settings&group=sections')).body)};
  await action({...sections,show_contact:'1',show_intro:'1'});
  const homeForm=await html(visitor,'/');
  ok(homeForm.body.includes('class="juris-contact"')&&homeForm.body.includes('action="/iletisim#iletisim-formu"')&&!homeForm.body.includes('name="notice"'),'Optional homepage form uses the validated endpoint without mandatory acknowledgement');
  const layout={...settingsValues((await html(admin,'/admin/?view=settings&group=layout')).body)};
  await action({...layout,show_home_consultation:'0'});ok(!(await html(visitor,'/')).body.includes('class="home-consultation"'),'Admin can hide the consultation section');
  await action(layout);
  await action({...layout,corporate_show_image:'0',corporate_show_principles:'0'});
  const hiddenCorporate=(await html(visitor,'/kurumsal')).body;
  ok(!hiddenCorporate.includes('class="editorial-visual"')&&!hiddenCorporate.includes('class="principle-strip"'),'Corporate image and principles visibility follow administrator settings');
  await action({...footerSettings,show_footer_practices:'0',show_footer_discover:'0',show_footer_contact:'0'});
  const hiddenFooter=(await html(visitor,'/')).body.split('<footer')[1].split('</footer>')[0];
  ok(!hiddenFooter.includes('class="footer-col"'),'Optional footer columns can all be hidden without changing office identity');
  await action(footerSettings);
  await action(layout);
  ok(layout.home_section_order.includes('home_profile')&&layout.home_section_order.includes('home_map'),'Every homepage section is available in the order editor');
  const originalOrder=layout.home_section_order;layout.home_section_order='articles,home_map,home_consultation,home_profile,principles,intro,approach,office,practices,faq,contact';layout.home_articles_layout='editorial';
  await action(layout);let ordered=(await html(visitor,'/')).body;ok(ordered.indexOf('data-home-section="articles"')<ordered.indexOf('data-home-section="intro"'),'Admin section order controls rendered content order');ok(ordered.includes('class="journal-grid"'),'Admin can change article layout');
  ok(ordered.indexOf('data-home-section="home_map"')<ordered.indexOf('data-home-section="home_profile"'),'Profile and location sections follow the saved order');
  await action({...layout,home_section_order:'articles,articles,<script>'});ok((await html(visitor,'/')).body.indexOf('data-home-section="articles"')<(await html(visitor,'/')).body.indexOf('data-home-section="intro"'),'Invalid section order cannot replace saved configuration');
  await action({...layout,home_section_order:originalOrder,home_articles_layout:'editorial',show_principles:'0'});ok(!(await html(visitor,'/')).body.includes('data-home-section="principles"'),'Section visibility hides public section');
  await action({...layout,home_section_order:originalOrder,home_articles_layout:'editorial'});
  await action({...appearance,background_color:'#eee9e1',header_color:'#fafafa',footer_color:'#123456',heading_font:'serif',header_layout:'inline'});const themed=(await html(visitor,'/')).body;
  ok(themed.includes('--background:#eee9e1')&&themed.includes('--header-bg:#fafafa')&&themed.includes('--footer-bg:#123456')&&themed.includes('header-inline'),'Separate page, header and footer colors and layout persist');
  ok(themed.includes('--sans:Arial,Helvetica,sans-serif;--serif:"Cormorant Garamond",Georgia,serif;--heading:"Cormorant Garamond",Georgia,serif'),'Body and heading font choices stay independent');
  await action({...appearance,heading_font:'<script>',header_layout:'invalid'});ok((await html(visitor,'/')).body.includes('header-inline'),'Invalid font and header values rejected');
  await action(appearance);
  const contact=await html(visitor,'/iletisim');
  const message={csrf:token(contact.body),name:'Test Ziyaretçisi',email:'visitor@example.test',phone:'',subject:'Diğer',message:'Bu mesaj yalnızca otomatik yerel uygulama testi içindir.',website:''};
  ok((await post(visitor,'/iletisim',{...message,email:'invalid'})).status===422,'Contact form validates email');
  ok(contact.body.includes('class="form-privacy"')&&contact.body.includes('href="/sayfa/kvkk"')&&!contact.body.includes('name="notice"'),'Contact form presents notice without making acknowledgement a service condition');
  ok((await post(visitor,'/iletisim',{...message,website:'bot.example'})).status===422,'Contact honeypot rejects spam');
  ok((await post(visitor,'/iletisim',message)).status===303,'Valid contact request is saved');
  const inbox=await html(admin,'/admin/?view=messages');ok(inbox.body.includes('Test Ziyaretçisi'),'Contact request is visible in admin inbox');
  const mid=/view=messages&amp;id=(\d+)#message-detail/.exec(inbox.body)[1];
  await action({action:'message-status',id:mid,status:'read'});ok((await html(admin,`/admin/?view=messages&id=${mid}`)).body.includes('class="badge read"'),'Message status can be updated');
  const uploadPage=await html(admin,'/admin/?view=media');const upload=new FormData();upload.set('csrf',token(uploadPage.body));upload.set('action','upload');upload.set('image',new Blob([readFileSync(path.join(root,'assets/images/library.jpg'))],{type:'image/jpeg'}),'library-test.jpg');
  ok((await admin('/admin/action.php',{method:'POST',body:upload})).status===303,'Image can be uploaded');
  const library=await html(admin,'/admin/?view=media');ok(library.body.includes('library-test.jpg'),'Uploaded image appears in media library');generatedUpload=/src="(\/assets\/uploads\/[a-f0-9]+\.webp)" alt="library-test.jpg"/.exec(library.body)?.[1]||'';
  const badUpload=new FormData();badUpload.set('csrf',token(library.body));badUpload.set('action','upload');badUpload.set('image',new Blob(['<?php echo 1; ?>'],{type:'image/jpeg'}),'malicious.jpg');await admin('/admin/action.php',{method:'POST',body:badUpload});ok((await html(admin,'/admin/?view=media')).body.includes('Yalnızca JPG, PNG, WebP veya güvenli SVG'),'Disguised executable upload is rejected');
  const safeVector=new FormData();safeVector.set('csrf',token(library.body));safeVector.set('action','upload');safeVector.set('image',new Blob([readFileSync(path.join(root,'assets/images/yurtdas-logo-v1.svg'))],{type:'image/svg+xml'}),'vector-test.svg');
  await admin('/admin/action.php',{method:'POST',body:safeVector});
  const vectors=(await html(admin,'/admin/?view=media')).body;
  const vectorPath=/src="(\/assets\/uploads\/[a-f0-9]+\.svg)" alt="vector-test.svg"/.exec(vectors)?.[1];
  ok(!!vectorPath,'Safe vector logo can be uploaded and selected from the library');generatedVectorUploads.push(vectorPath);
  const vectorFile=await html(visitor,vectorPath);ok(vectorFile.status===200&&vectorFile.headers.get('content-type')?.includes('image/svg+xml')&&!vectorFile.body.includes('<text'),'Vector upload remains SVG with outlined lettering');
  for(const payload of ['<script>alert(1)</script>','<image href="https://example.test/a.png"/>','<path onload="alert(1)" d="M0 0H2"/>','<foreignObject><div>HTML</div></foreignObject>','<style>path{fill:url(https://example.test/a)}</style>']){
    const unsafe=new FormData();unsafe.set('csrf',token(library.body));unsafe.set('action','upload');unsafe.set('image',new Blob([`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80">${payload}</svg>`],{type:'image/svg+xml'}),'unsafe-vector.svg');
    await admin('/admin/action.php',{method:'POST',body:unsafe});
    const result=(await html(admin,'/admin/?view=media')).body;ok(result.includes('SVG yalnızca güvenli')&&!result.includes('alt="unsafe-vector.svg"'),'SVG rejects active or external content: '+payload.split(' ')[0]);
  }
  // End-to-end checks for the new SEO controls and simplified content operations.
  for(const view of ['redirects','sitemap'])ok((await html(admin,'/admin/?view='+view)).status===200,view+' management screen loads');
  const seoSettings=settingsValues((await html(admin,'/admin/?view=settings&group=seo')).body);
  await action({...seoSettings,site_url:'https://example.test',indexing:'1',sitemap_enabled:'1'});
  await action({action:'save-redirect',source_path:'/eski-buro',target_path:'/kurumsal',status_code:'301',enabled:'1'});
  let redirectResponse=await visitor('/eski-buro');ok(redirectResponse.status===301&&redirectResponse.headers.get('location')==='/kurumsal','Manual permanent redirect returns the correct HTTP status and target');
  let contentExport=JSON.parse(await (await action({action:'export'})).text());
  ok(contentExport.url_redirects.some(r=>r.source_path==='/eski-buro'),'Backup exports manual redirects');
  let redirectScreen=await html(admin,'/admin/?view=redirects');
  let redirectForm=[...redirectScreen.body.matchAll(/<form[^>]*class="[^"]*redirect-form[^"]*"[^>]*>([\s\S]*?)<\/form>/g)].map(m=>formFields(m[1])).find(r=>r.source_path==='/eski-buro');
  await action({...redirectForm,action:'save-redirect',status_code:'302',enabled:'1'});
  ok((await visitor('/eski-buro')).status===302,'Manual redirects can be changed to temporary redirects');
  redirectScreen=await html(admin,'/admin/?view=redirects');redirectForm=[...redirectScreen.body.matchAll(/<form[^>]*class="[^"]*redirect-form[^"]*"[^>]*>([\s\S]*?)<\/form>/g)].map(m=>formFields(m[1])).find(r=>r.source_path==='/eski-buro');
  await action({...redirectForm,action:'save-redirect',status_code:'302',enabled:'0'});
  ok((await visitor('/eski-buro')).status===404,'Disabling a redirect removes it from public routing');
  for(const bad of [
    {source_path:'/admin/login.php',target_path:'/kurumsal'},
    {source_path:'/',target_path:'/kurumsal'},
    {source_path:'/unsafe',target_path:'https://other.example'},
    {source_path:'/self-loop',target_path:'/self-loop'},
    {source_path:'/kurumsal',target_path:'/about-us.html'},
    {source_path:'/malformed',target_path:'/kurumsal',status_code:'301x'}
  ]){await action({action:'save-redirect',status_code:'301',enabled:'1',...bad});ok((await html(admin,'/admin/?view=redirects')).body.includes('notice error'),'Invalid or cyclic redirect is rejected: '+bad.source_path);}
  await action({action:'save-redirect',source_path:'/loop-a',target_path:'/loop-b',status_code:'301',enabled:'1'});
  await action({action:'save-redirect',source_path:'/loop-b',target_path:'/loop-a',status_code:'301',enabled:'1'});
  ok((await visitor('/loop-b')).status===404,'Two-node redirect cycle cannot be saved');
  const featurePractice=await action(newEntry({type:'practice',title:'Yeni alan testi',slug:'yeni-alan-testi',sort_order:'-999',status:'published'}));
  const practiceFields=formFields((await html(admin,featurePractice.headers.get('location'))).body);
  ok((await html(visitor,'/')).body.includes('/calisma-alanlari/yeni-alan-testi')&&(await html(visitor,'/calisma-alanlari')).body.includes('Yeni alan testi'),'New practice appears on the homepage, menu and practice directory');
  await action(newEntry({type:'practice',id:practiceFields.id,original_updated_at:practiceFields.original_updated_at,title:'Düzenlenen alan testi',slug:'duzenlenen-alan-testi',image:'/assets/images/law-about-study-v1.webp',sort_order:'-999',status:'published'}));
  ok((await visitor('/calisma-alanlari/yeni-alan-testi')).status===301&&(await html(visitor,'/calisma-alanlari')).body.includes('Düzenlenen alan testi'),'Practice edit updates the directory and redirects its previous URL');
  const policyPath='/calisma-alanlari/duzenlenen-alan-testi';
  await action({action:'save-sitemap-url',path:policyPath,sitemap_enabled:'0',noindex:'0',lastmod:''});
  ok(!(await html(visitor,'/sitemap.xml')).body.includes(policyPath)&&(await visitor(policyPath)).status===200,'Removing a URL from the sitemap keeps its public page available');
  await action({action:'save-sitemap-url',path:policyPath,sitemap_enabled:'1',noindex:'1',lastmod:'2026-01-01'});
  ok((await html(visitor,policyPath)).body.includes('noindex,follow')&&!(await html(visitor,'/sitemap.xml')).body.includes(policyPath),'Page-level noindex is applied to the page and excludes it from the sitemap');
  contentExport=JSON.parse(await (await action({action:'export'})).text());ok(contentExport.seo_urls.some(r=>r.path===policyPath&&r.noindex===1),'Content backup retains sitemap and noindex controls');
  await action({action:'save-sitemap-url',path:policyPath,sitemap_enabled:'1',noindex:'0',lastmod:'2026-01-01'});
  ok((await html(visitor,'/sitemap.xml')).body.includes(policyPath+'</loc><lastmod>2026-01-01</lastmod>'),'A hidden page can be re-added with an explicit last modification date');
  await action({action:'save-sitemap-url',path:policyPath,sitemap_enabled:'1',noindex:'0',lastmod:'2099-01-01'});
  ok((await html(admin,'/admin/?view=sitemap')).body.includes('notice error'),'Future sitemap dates are rejected');
  await action({action:'save-sitemap-url',path:'/olmayan-sayfa',sitemap_enabled:'1',noindex:'0',lastmod:''});
  ok(!(await html(visitor,'/sitemap.xml')).body.includes('/olmayan-sayfa'),'Nonexistent pages cannot be manually added to the sitemap');
  await action({action:'toggle-sitemap',sitemap_enabled:'0'});ok((await visitor('/sitemap.xml')).status===404&&!(await html(visitor,'/robots.txt')).body.includes('Sitemap:'),'Global sitemap hiding also updates robots.txt');
  await action({action:'toggle-sitemap',sitemap_enabled:'1'});ok((await visitor('/sitemap.xml')).status===200&&(await html(visitor,'/robots.txt')).body.includes('Sitemap:'),'Global sitemap can be restored');
  await action({action:'save-sitemap-url',path:'/makaleler',sitemap_enabled:'1',noindex:'1',lastmod:''});
  ok((await html(visitor,'/makaleler')).body.includes('noindex,follow'),'Sitemap noindex control works for static archive pages');
  await action({action:'save-seo-static',route:'makaleler',meta_title:'',meta_description:'',noindex:'0'});
  ok((await html(visitor,'/makaleler')).body.includes('index,follow')&&(await html(visitor,'/sitemap.xml')).body.includes('/makaleler</loc>'),'Meta and sitemap static noindex controls stay synchronized');
  await action({action:'reset-sitemap-url',path:policyPath});
  ok(!JSON.parse(await (await action({action:'export'})).text()).seo_urls.some(r=>r.path===policyPath),'Sitemap override can be deleted without deleting content');
  await action({action:'archive-entry',type:'practice',id:practiceFields.id});
  ok((await visitor(policyPath)).status===404&&!(await html(visitor,'/calisma-alanlari')).body.includes('Düzenlenen alan testi')&&!(await html(visitor,'/sitemap.xml')).body.includes(policyPath),'Practice archive removes it from the directory, public page and sitemap');
  await action({action:'restore-entry',type:'practice',id:practiceFields.id});ok((await visitor(policyPath)).status===404,'Restoring archived content returns it as a draft');
  let draftFields=formFields((await html(admin,'/admin/?view=edit&type=practice&id='+practiceFields.id)).body);
  await action(newEntry({type:'practice',id:practiceFields.id,original_updated_at:draftFields.original_updated_at,title:'Tekrar yayınlanan alan',slug:'duzenlenen-alan-testi',sort_order:'-999',status:'published'}));
  ok((await visitor(policyPath)).status===200,'Restored practice can be republished');
  let articlesForBulk=[];
  for(const slug of ['toplu-bir','toplu-iki']){const saved=await action(newEntry({slug,title:'Toplu içerik '+slug,status:'published'}));articlesForBulk.push(formFields((await html(admin,saved.headers.get('location'))).body));}
  const bulkParams={action:'bulk-content',type:'article',mode:'archive'};for(const f of articlesForBulk)bulkParams['versions['+f.id+']']=f.original_updated_at;
  const bulkData=new URLSearchParams({...bulkParams,csrf:token((await html(admin,'/admin/')).body)});for(const f of articlesForBulk)bulkData.append('ids[]',f.id);
  await admin('/admin/action.php',{method:'POST',body:bulkData});
  ok((await visitor('/makaleler/toplu-bir')).status===404&&(await visitor('/makaleler/toplu-iki')).status===404,'Selected sample articles can be archived together');
  const archivedList=(await html(admin,'/admin/?view=content&type=article&status=archived')).body;ok(archivedList.includes('Kalıcı sil')&&archivedList.includes('Geri al'),'Archive list exposes restore and permanent delete actions');
  const permanentFields=formFields((await html(admin,'/admin/?view=edit&type=article&id='+articlesForBulk[0].id)).body);
  await action({action:'delete-entry',type:'article',id:articlesForBulk[0].id,original_updated_at:permanentFields.original_updated_at});
  ok(!JSON.parse(await (await action({action:'export'})).text()).entries.some(r=>r.id===Number(articlesForBulk[0].id)),'Archived article can be permanently deleted with a pre-deletion backup');
  await action({action:'delete-entry',type:'practice',id:practiceFields.id,original_updated_at:formFields((await html(admin,'/admin/?view=edit&type=practice&id='+practiceFields.id)).body).original_updated_at});
  ok((await visitor(policyPath)).status===200,'A live practice cannot be permanently deleted directly');
  await action({action:'archive-entry',type:'practice',id:practiceFields.id});
  const localHome=(await html(visitor,'/')).body;const localGraph=JSON.parse(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/.exec(localHome)[1]);
  const localOffice=localGraph['@graph'].find(n=>Array.isArray(n['@type'])&&n['@type'].includes('LegalService'));
  ok(localOffice.address.addressLocality==='Antakya'&&localOffice.address.addressRegion==='Hatay'&&localHome.includes('content="Antakya, Hatay"'),'Local office information uses Antakya district and Hatay province consistently');
  const seoAliasEntry=await action(newEntry({title:'Adres politikası testi',slug:'policy-old',status:'published'}));
  const seoAliasFields=formFields((await html(admin,seoAliasEntry.headers.get('location'))).body);
  await action({action:'save-sitemap-url',path:'/makaleler/policy-old',sitemap_enabled:'1',noindex:'1',lastmod:''});
  await action(newEntry({id:seoAliasFields.id,original_updated_at:seoAliasFields.original_updated_at,title:'Adres politikası testi',slug:'policy-new',status:'published'}));
  ok((await html(visitor,'/makaleler/policy-new')).body.includes('noindex,follow')&&!(await html(visitor,'/sitemap.xml')).body.includes('/makaleler/policy-new'),'Noindex policy follows an edited content URL');
  const graphEntry=await action(newEntry({title:'Yönlendirme döngü testi',slug:'graph-before',status:'published'}));
  const graphFields=formFields((await html(admin,graphEntry.headers.get('location'))).body);
  await action({action:'save-redirect',source_path:'/makaleler/graph-after',target_path:'/makaleler/graph-before',status_code:'301',enabled:'1'});
  await action(newEntry({id:graphFields.id,original_updated_at:graphFields.original_updated_at,title:'Yönlendirme döngü testi',slug:'graph-after',status:'published'}));
  ok((await visitor('/makaleler/graph-before')).status===200&&(await visitor('/makaleler/graph-after')).headers.get('location')==='/makaleler/graph-before','Slug changes that would create a manual/automatic redirect cycle are rolled back');
  redirectScreen=await html(admin,'/admin/?view=redirects');redirectForm=[...redirectScreen.body.matchAll(/<form[^>]*class="[^"]*redirect-form[^"]*"[^>]*>([\s\S]*?)<\/form>/g)].map(m=>formFields(m[1])).find(r=>r.source_path==='/eski-buro');
  await action({action:'delete-redirect',id:redirectForm.id,original_updated_at:redirectForm.original_updated_at});
  ok(!JSON.parse(await (await action({action:'export'})).text()).url_redirects.some(r=>r.source_path==='/eski-buro'),'Saved manual redirects can be deleted');
  const futureRedirectEntry=await action(newEntry({title:'Planlı adres değişimi',slug:'scheduled-old',status:'published',published_at:'2099-01-01T10:00'}));
  const futureRedirectFields=formFields((await html(admin,futureRedirectEntry.headers.get('location'))).body);
  await action(newEntry({id:futureRedirectFields.id,original_updated_at:futureRedirectFields.original_updated_at,title:'Planlı adres değişimi',slug:'scheduled-new',status:'published',published_at:'2099-01-01T10:00'}));
  await action({action:'save-redirect',source_path:'/makaleler/scheduled-new',target_path:'/makaleler/scheduled-old',status_code:'301',enabled:'1'});
  ok((await visitor('/makaleler/scheduled-new')).status===404,'Redirect cycles that would activate on a scheduled publication date are rejected');
  const backup=await action({action:'export'});ok(backup.status===200&&backup.headers.get('content-disposition')?.includes('attachment'),'Backup download is authenticated');
  const backupText=await backup.text();const backupData=JSON.parse(backupText);ok(backupData.format==='mizan-cms'&&!backupText.includes('private_salt')&&!backupText.includes('password')&&!backupText.includes('Test Ziyaretçisi'),'Backup excludes credentials and contact data');
  ok(backupData.entries.some(row=>row.type==='team'&&JSON.parse(row.profile_details||'{}').baro_number==='TEST-123'),'Backup preserves professional profile fields');
  const bp=await html(admin,'/admin/?view=backup');const importForm=new FormData();importForm.set('csrf',token(bp.body));importForm.set('action','import');importForm.set('backup',new Blob([backupText],{type:'application/json'}),'backup.json');await admin('/admin/action.php',{method:'POST',body:importForm});ok((await html(admin,'/admin/?view=backup')).body.includes('Yedek birleştirildi.'),'Content backup imports successfully');
  await action({action:'save-settings',group:'seo',site_url:'https://example.test',seo_title:'Test Hukuk',seo_description:'Test açıklaması',indexing:'1',sitemap_enabled:'1'});
  ok((await html(visitor,'/makaleler/hukuki-gorusmeye-hazirlik')).body.includes('https://example.test/avukatlar/halil-ibrahim-yurtdas#person'),'Article structured author data links to the profile');
  ok((await html(visitor,'/sitemap.xml')).body.includes('https://example.test/makaleler/'),'Sitemap includes canonical content URLs');
  ok((await html(visitor,'/robots.txt')).body.includes('Disallow: /admin/'),'Robots protects administrative pages');
  const faqs=(await html(visitor,'/sikca-sorulan-sorular')).body;const graph=JSON.parse(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/.exec(faqs)[1]);ok(graph['@graph'].some(n=>n['@type']==='FAQPage'&&n.mainEntity.some(q=>q.name==='Test için ek bir soru?')),'FAQ structured data reflects visible admin-managed questions');
  const geo={...settingsValues((await html(admin,'/admin/?view=settings&group=geo')).body),geo_summary:'Büro için doğrulanabilir kısa özet.',seo_service_area:'İstanbul',seo_postal_code:'34000'};await action(geo);ok((await html(visitor,'/llms.txt')).body.includes(geo.geo_summary),'GEO summary appears in machine-readable site guide');
  const siteMap=(await html(visitor,'/sitemap.xml')).body;ok(siteMap.includes('/sikca-sorulan-sorular</loc>')&&!siteMap.includes('basinda-biz'),'Sitemap includes public routes and excludes the removed press page');
  const articleHtml=(await html(visitor,'/makaleler/hukuki-gorusmeye-hazirlik')).body;const articleGraph=JSON.parse(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/.exec(articleHtml)[1]);ok(articleGraph['@graph'].some(n=>n['@type']==='Article'&&n.speakable.cssSelector.includes('.post-title')),'Article schema selectors match visible article layout');
  ok((await html(visitor,'/makaleler?q=test')).body.includes('noindex,follow'),'Search pages stay out of index without blocking link discovery');
  const legacyBackup={...backupData,settings:{...backupData.settings,home_section_order:'principles,intro,approach,office,practices,articles,faq,contact'}};
  legacyBackup.entries=legacyBackup.entries.map(({profile_details,...entry})=>entry);
  const legacyPage=await html(admin,'/admin/?view=backup');const legacyForm=new FormData();legacyForm.set('csrf',token(legacyPage.body));legacyForm.set('action','import');legacyForm.set('backup',new Blob([JSON.stringify(legacyBackup)],{type:'application/json'}),'legacy.json');
  await admin('/admin/action.php',{method:'POST',body:legacyForm});
  ok((await html(admin,'/admin/?view=backup')).body.includes('Yedek birleştirildi.'),'Older backups with the original section order remain importable');
  const redirectEntry=await action(newEntry({title:'Adres değişimi testi',slug:'eski-adres-testi',status:'published'}));
  const redirectEditor=await html(admin,redirectEntry.headers.get('location'));const redirectFields=formFields(redirectEditor.body);
  await action(newEntry({id:redirectFields.id,original_updated_at:redirectFields.original_updated_at,title:'Adres değişimi testi',slug:'yeni-adres-testi',status:'published'}));
  const oldAddress=await visitor('/makaleler/eski-adres-testi');
  ok(oldAddress.status===301&&oldAddress.headers.get('location')==='/makaleler/yeni-adres-testi','Renaming a published URL creates a permanent redirect');
  const aliasesBackup=JSON.parse(await (await action({action:'export'})).text());
  ok(aliasesBackup.redirects.some(row=>row.slug==='eski-adres-testi'&&row.target_slug==='yeni-adres-testi'),'Content backups retain permanent redirects by target slug');
  await action({action:'archive-entry',id:redirectFields.id,type:'article'});
  ok((await visitor('/makaleler/eski-adres-testi')).status===404,'Historical redirects cannot expose archived content');
  const changedPolicyEdit=await html(admin,`/admin/?view=edit&type=page&id=${privacyId}`);
  await action(newEntry({...policy,body:policy.body+' Metin değişikliği.',original_updated_at:formFields(changedPolicyEdit.body).original_updated_at}));
  const closedContact=await html(visitor,'/iletisim');
  ok(!closedContact.body.includes('class="contact-form"'),'Changing the privacy notice invalidates its previous review');
  const visitorToken=token((await html(visitor,'/admin/login.php')).body);
  ok((await post(visitor,'/iletisim',{...message,csrf:visitorToken})).status===422,'Server rejects contact submissions until the new notice is reviewed');
  const secondAdmin=client();const l2=await html(secondAdmin,'/admin/login.php');await post(secondAdmin,'/admin/login.php',{csrf:token(l2.body),email,password});
  await action({action:'account',name:'Güncel Yönetici',email,current_password:password,new_password:'New-Testing-Password-829!',confirm_password:'New-Testing-Password-829!'});
  ok((await secondAdmin('/admin/')).status===303,'Password change invalidates other sessions');
  ok((await html(admin,'/admin/')).body.includes('Güncel Yönetici'),'Current session retains account update');
  await action({action:'logout'});ok((await admin('/admin/')).status===303,'Logout clears administrative access');
  const attacker=client();let rateStatus=0;for(let i=0;i<9;i++){const l=await html(attacker,'/admin/login.php');rateStatus=(await post(attacker,'/admin/login.php',{csrf:token(l.body),email:'invalid@example.test',password:'incorrect'})).status;}
  ok(rateStatus===429,'Repeated login attempts are rate limited');
  const phpLog=path.join(testData,'php-error.log');const errorLog=existsSync(phpLog)?readFileSync(phpLog,'utf8'):'';ok(!/PHP (?:Fatal error|Warning|Notice|Deprecated|Parse error)/.test(errorLog),'No PHP runtime warnings or fatal errors');
  mkdirSync(path.join(root,'tests/artifacts'),{recursive:true});writeFileSync(path.join(root,'tests/artifacts/integration-results.json'),JSON.stringify({passed:checks,date:new Date().toISOString(),checks:results},null,2));
  process.stdout.write(`\n${checks} checks passed. Isolated database: ${testData}\n`);
} catch(error){console.error(error);console.error(logs.slice(-3500));process.exitCode=1;}
finally{server.kill();for(const upload of [generatedUpload,...generatedVectorUploads])if(upload&&/^\/assets\/uploads\/[a-f0-9]+\.(webp|svg)$/.test(upload)){const generatedPath=path.join(root,upload);if(generatedPath.startsWith(path.join(root,'assets','uploads'))&&existsSync(generatedPath))unlinkSync(generatedPath);}}
