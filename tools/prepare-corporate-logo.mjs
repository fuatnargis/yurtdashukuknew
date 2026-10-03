import opentype from 'opentype.js';
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const parseFont = file => {
  const bytes = readFileSync(file);
  return opentype.parse(bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength));
};
const heading = parseFont(process.argv[2] || 'C:/Windows/Fonts/georgiab.ttf');
const body = parseFont(process.argv[3] || 'C:/Windows/Fonts/arial.ttf');
const name = heading.getPath('Yurtdaş Hukuk', 92, 51, 34);
const subtitle = body.getPath('AVUKATLIK VE HUKUKİ DANIŞMANLIK', 94, 74, 10);
name.fill = '#204a43';
subtitle.fill = '#204a43';
const mark = readFileSync(path.join(root, 'assets/images/yurtdas-mark-v1.svg'), 'utf8');
const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 398 100" role="img" aria-labelledby="logoTitle">
<title id="logoTitle">Yurtdaş Hukuk - Avukatlık ve hukuki danışmanlık</title>
<rect width="398" height="100" fill="#ffffff"/>
<g transform="translate(6 6)">${mark}</g>
${name.toSVG(2)}
${subtitle.toSVG(2)}
</svg>\n`;
const target = path.join(root, 'assets/images/yurtdas-logo-v1.svg');
writeFileSync(target, svg);
console.log(`Vektorel logo: ${target}`);
