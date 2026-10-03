import {spawnSync} from 'node:child_process';
import {existsSync, readFileSync, readdirSync} from 'node:fs';
import path from 'node:path';

const root=path.resolve(import.meta.dirname,'..');
const required=['api/index.php','index.php','app/bootstrap.php','app/schema.sql','app/views/admin-setup.php','assets/site.css','assets/site.js','assets/profile.css','tests/integration.mjs','vercel.json'];
for(const file of required){
  if(!existsSync(path.join(root,file)))throw new Error(`Dağıtım için gerekli dosya eksik: ${file}`);
}

const config=JSON.parse(readFileSync(path.join(root,'vercel.json'),'utf8'));
if(!config.functions?.['api/index.php']?.runtime||!config.routes?.some(route=>route.dest==='/api/index.php')){
  throw new Error('Vercel PHP işlevi veya sayfa yönlendirmesi eksik.');
}

function assetFiles(dir){
  return readdirSync(path.join(root,dir),{withFileTypes:true}).flatMap(item=>{
    const relative=path.join(dir,item.name);
    if(relative===path.join('assets','uploads'))return [];
    return item.isDirectory()?assetFiles(relative):item.isFile()&&/\.(css|js|jpg|jpeg|webp|svg|ttf|woff2)$/.test(item.name)?[relative]:[];
  });
}
function destination(url){
  for(const route of config.routes){
    if(!route.dest)continue;
    const pattern=new RegExp('^(?:'+route.src+')$');
    if(pattern.test(url))return url.replace(pattern,route.dest);
  }
}
const assets=assetFiles('assets');
for(const file of assets){
  const url='/'+file.split(path.sep).join('/');
  if(destination(url)!==url)throw new Error('Statik dosya Vercel yönlendirmesinde sunulmuyor: '+url);
}
for(const url of ['/storage/site.sqlite','/app/bootstrap.php','/.env.local','/assets/uploads/.htaccess','/assets/images/../private.txt','/assets/images/code.php']){
  if(destination(url)!=='/api/index.php')throw new Error('Özel dosya statik olarak sunulmamalı: '+url);
}
console.log(assets.length+' statik dosya: Vercel yönlendirmeleri doğru.');

function phpFiles(dir){
  if(!existsSync(path.join(root,dir)))return [];
  return readdirSync(path.join(root,dir),{withFileTypes:true}).flatMap(item=>{
    const relative=path.join(dir,item.name);
    return item.isDirectory()?phpFiles(relative):item.isFile()&&item.name.endsWith('.php')?[relative]:[];
  });
}

const php=process.env.PHP_BIN||'php';
const files=['index.php','router.php','makaleler.php',...['app','admin','api','tools','include'].flatMap(phpFiles)];
for(const file of files){
  const result=spawnSync(php,['-l',file],{cwd:root,encoding:'utf8'});
  if(result.status!==0)throw new Error(`PHP sözdizimi hatası: ${file}\n${result.stderr||result.stdout}`);
}
console.log(`${files.length} PHP dosyası: sözdizimi doğru.`);

const test=spawnSync(process.execPath,['tests/integration.mjs'],{cwd:root,encoding:'utf8',timeout:120000});
if(test.status!==0)throw new Error(`Entegrasyon testleri başarısız.\n${test.stdout||''}\n${test.stderr||''}`);
console.log(test.stdout.trim().split(/\r?\n/).at(-1));
console.log('Dağıtım öncesi kontroller tamamlandı.');
