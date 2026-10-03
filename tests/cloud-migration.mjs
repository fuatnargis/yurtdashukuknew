import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { existsSync, mkdtempSync, readFileSync, writeFileSync, unlinkSync } from 'node:fs';
import { randomBytes, createHash } from 'node:crypto';
import { tmpdir } from 'node:os';
import path from 'node:path';

// A new local PostgreSQL cluster and a freshly seeded SQLite database.
// No existing PostgreSQL service or website database is changed.
const root=path.resolve(import.meta.dirname,'..');
const php=process.env.PHP_BIN||'php';
const pg=process.env.HUKUK_PG_BIN||'';
const workspace=mkdtempSync(path.join(process.platform==='win32'?tmpdir():'/tmp','hukuk-cloud-'));
const data=path.join(workspace,'source');
const cluster=path.join(workspace,'postgres');
const env={...process.env,HUKUK_DATA_DIR:data,HUKUK_DATABASE_URL:'',HUKUK_SITE_URL:'',VERCEL:'0'};
const pgPort=23000+Math.floor(Math.random()*2000);
const httpPort=25000+Math.floor(Math.random()*2000);
const databaseUrl='postgresql://hukuk_test@127.0.0.1:'+pgPort+'/postgres';
const targetEnv={...env,HUKUK_DATABASE_URL:databaseUrl};
const fixture='/assets/uploads/'+randomBytes(16).toString('hex')+'.svg';
const binary=name=>pg?path.join(pg,name):name;
function run(command,args,options={}){
  const result=spawnSync(command,args,{cwd:root,env,encoding:'utf8',timeout:60000,...options});
  assert.equal(result.status,0,result.stderr||result.stdout||result.error?.message);
  return result.stdout;
}
function sourcePhp(code){return run(php,[],{input:'<?php require "app/bootstrap.php"; '+code});}
function targetPhp(code,overrides={}){return run(php,[],{env:{...targetEnv,...overrides},input:'<?php define("ROOT",getcwd());define("DATA_DIR",getenv("HUKUK_DATA_DIR"));require "app/runtime.php";$db=database_connection();'+code});}
let started=false,server;
try {
  run(php,['tools/install.php']);
  sourcePhp('$db=db();save_setting("indexing","1");$id=entry("team","halil-ibrahim-yurtdas")["id"];$db->prepare("INSERT INTO entry_redirects(type,slug,entry_id) VALUES(?,?,?)")->execute(["team","eski-profil",$id]);$db->prepare("INSERT INTO content_backups(name,data,created_at) VALUES(?,?,?)")->execute(["test-backup.json","{}",date("Y-m-d H:i:s")]);$db->prepare("INSERT INTO messages(name,email,subject,message,created_at,notice_version) VALUES(?,?,?,?,?,?)")->execute(["Test","test@example.test","Test","Test mesajı",date("Y-m-d H:i:s"),"test"]);');
  sourcePhp('$db=db();$db->prepare("INSERT INTO url_redirects(source_path,target_path,status_code,enabled,updated_at) VALUES(?,?,?,?,?)")->execute(["/eski-test","/kurumsal",302,1,date("Y-m-d H:i:s")]);$db->prepare("INSERT INTO seo_urls(path,sitemap_enabled,noindex,lastmod,updated_at) VALUES(?,?,?,?,?)")->execute(["/kurumsal",0,1,"2026-01-01",date("Y-m-d H:i:s")]);');
  writeFileSync(path.join(root,fixture),readFileSync(path.join(root,'assets/images/yurtdas-mark-v1.svg')));
  const checksum=()=>createHash('sha256').update(readFileSync(path.join(data,'site.sqlite'))).digest('hex');
  const before=checksum();
  const preview=run(php,['tools/cloud-migrate.php','--check']);
  assert.ok(preview.includes('entry_redirects: 1'));
  assert.equal(checksum(),before);
  console.log('PASS migration: read-only source check preserves SQLite data');

  run(binary('initdb'),['-D',cluster,'-U','hukuk_test','-A','trust','--no-locale','-E','UTF8']);
  run(binary('pg_ctl'),['-D',cluster,'-l',path.join(workspace,'postgres.log'),'-o','-h 127.0.0.1 -p '+pgPort+' -k '+workspace,'-w','start']);
  started=true;
  run(php,['tools/cloud-migrate.php'],{env:targetEnv});
  assert.equal(checksum(),before);
  const imported=JSON.parse(targetPhp('echo json_encode(["redirects"=>$db->query("SELECT COUNT(*) FROM entry_redirects")->fetchColumn(),"messages"=>$db->query("SELECT COUNT(*) FROM messages")->fetchColumn(),"indexing"=>$db->query("SELECT value FROM settings WHERE key=\'indexing\'")->fetchColumn()]);'));
  assert.equal(Number(imported.redirects),1);assert.equal(Number(imported.messages),0);assert.equal(imported.indexing,'0');
  assert.equal(Number(targetPhp('echo $db->query("SELECT COUNT(*) FROM content_backups")->fetchColumn();')),1);
  assert.equal(Number(targetPhp('echo $db->query("SELECT status_code FROM url_redirects WHERE source_path=\'/eski-test\'")->fetchColumn();')),302);
  assert.equal(Number(targetPhp('echo $db->query("SELECT noindex FROM seo_urls WHERE path=\'/kurumsal\'")->fetchColumn();')),1);
  console.log('PASS migration: redirects and sitemap policies retained, messages opt-in and indexing disabled');
  const vector=JSON.parse(targetPhp('$q=$db->prepare("SELECT content_type,data,size FROM media_files WHERE path=?");$q->execute(['+JSON.stringify(fixture)+']);echo json_encode($q->fetch(PDO::FETCH_ASSOC));'));
  assert.equal(vector.content_type,'image/svg+xml');
  assert.equal(Buffer.from(vector.data,'base64').length,Number(vector.size));
  console.log('PASS migration: SVG uploads preserved with sanitized bytes and correct size');

  const repeated=spawnSync(php,['tools/cloud-migrate.php'],{cwd:root,env:targetEnv,encoding:'utf8'});
  assert.equal(repeated.status,1);assert.ok(repeated.stderr.includes('Hedef veritabanı boş değil'));
  assert.equal(Number(targetPhp('echo $db->query("SELECT COUNT(*) FROM entry_redirects")->fetchColumn();')),1);
  console.log('PASS migration: existing target is preserved');

  targetPhp('$db->exec("CREATE DATABASE hukuk_messages");');
  const messagesEnv={...targetEnv,HUKUK_DATABASE_URL:databaseUrl.replace('/postgres','/hukuk_messages')};
  run(php,['tools/cloud-migrate.php','--include-messages'],{env:messagesEnv});
  assert.equal(Number(targetPhp('echo $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();',{HUKUK_DATABASE_URL:messagesEnv.HUKUK_DATABASE_URL})),1);
  console.log('PASS migration: explicit message import works');

  const router=path.join(workspace,'router.php');
  writeFileSync(router,'<?php require '+JSON.stringify(path.join(root,'api/index.php'))+';');
  server=spawn(php,['-S','127.0.0.1:'+httpPort,router],{cwd:root,env:targetEnv,stdio:'ignore'});
  const base='http://127.0.0.1:'+httpPort;
  let ready=false;
  for(let i=0;i<50;i++){
    try {if((await fetch(base)).ok){ready=true;break;}}catch{}
    await new Promise(resolve=>setTimeout(resolve,100));
  }
  assert.ok(ready,'Cloud-backed PHP function starts');
  const response=await fetch(base+fixture);
  assert.equal(response.status,200);
  assert.equal(response.headers.get('content-type'),'image/svg+xml');
  assert.ok(response.headers.get('content-security-policy').includes('sandbox'));
  assert.ok((await response.text()).includes('<svg'));
  const head=await fetch(base+fixture,{method:'HEAD'});
  assert.equal(head.status,200);assert.equal(await head.text(),'');
  const redirect=await fetch(base+'/avukatlar/eski-profil',{redirect:'manual'});
  assert.equal(redirect.status,301);assert.equal(redirect.headers.get('location'),'/avukatlar/halil-ibrahim-yurtdas');
  for(const url of ['/storage/site.sqlite','/app/bootstrap.php','/assets/uploads/.htaccess','/assets/uploads/not-a-hash.svg']){
    assert.equal((await fetch(base+url)).status,404);
  }
  console.log('PASS cloud HTTP: uploaded SVG, HEAD, old profile redirect and private paths');
} finally {
  if(server)server.kill();
  if(started)spawnSync(binary('pg_ctl'),['-D',cluster,'-m','fast','-w','stop'],{encoding:'utf8'});
  if(existsSync(path.join(root,fixture)))unlinkSync(path.join(root,fixture));
}
