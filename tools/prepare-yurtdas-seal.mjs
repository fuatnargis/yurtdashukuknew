import opentype from 'opentype.js';
import {readFileSync,writeFileSync,existsSync} from 'node:fs';
import path from 'node:path';
const root=path.resolve(import.meta.dirname,'..');
const candidates=[process.argv[2],'/System/Library/Fonts/Supplemental/Arial Bold.ttf','C:/Windows/Fonts/arialbd.ttf',path.join(root,'assets/fonts/manrope-semibold.ttf')].filter(Boolean);
const fontFile=candidates.find(existsSync);if(!fontFile)throw new Error('Logo yazı tipi bulunamadı.');
const bytes=readFileSync(fontFile);const font=opentype.parse(bytes.buffer.slice(bytes.byteOffset,bytes.byteOffset+bytes.byteLength));
function arcText(text,size,radius,lower=false){
 const chars=[...text];const widths=chars.map(char=>font.getAdvanceWidth(char,size));const space=2.4;const length=widths.reduce((a,b)=>a+b,0)+space*(chars.length-1);let offset=-length/2;let out='';
 chars.forEach((char,i)=>{const center=offset+widths[i]/2;const angle=(lower?Math.PI/2-center/radius:-Math.PI/2+center/radius);const x=256+radius*Math.cos(angle),y=256+radius*Math.sin(angle);const rotation=angle*180/Math.PI+(lower?-90:90);const glyph=font.getPath(char,-widths[i]/2,0,size);glyph.fill='#172c35';out+='<g transform="translate('+x.toFixed(2)+' '+y.toFixed(2)+') rotate('+rotation.toFixed(2)+')">'+glyph.toSVG(2)+'</g>';offset+=widths[i]+space;});return out;
}
function laurel(side,color){
 const mirror=side==='right';const point=(x,y)=>[(mirror?512-x:x),y];
 const p0=[204,350],p1=[119,319],p2=[111,224],p3=[163,160];
 let out='<g fill="'+color+'"><path d="M'+point(...p0).join(' ')+' C'+point(...p1).join(' ')+' '+point(...p2).join(' ')+' '+point(...p3).join(' ')+'" fill="none" stroke="'+color+'" stroke-width="2.8"/>';
 for(let i=0;i<9;i++){
  const t=.07+i*.105,a=1-t;
  const x=a*a*a*p0[0]+3*a*a*t*p1[0]+3*a*t*t*p2[0]+t*t*t*p3[0];
  const y=a*a*a*p0[1]+3*a*a*t*p1[1]+3*a*t*t*p2[1]+t*t*t*p3[1];
  const dx=3*a*a*(p1[0]-p0[0])+6*a*t*(p2[0]-p1[0])+3*t*t*(p3[0]-p2[0]);
  const dy=3*a*a*(p1[1]-p0[1])+6*a*t*(p2[1]-p1[1])+3*t*t*(p3[1]-p2[1]);
  const length=Math.hypot(dx,dy),ux=dx/length,uy=dy/length;
  for(const sign of [-1,1]){
   const vx=ux*.7-uy*sign*.72,vy=uy*.7+ux*sign*.72;
   const tip=point(x+vx*25,y+vy*25),c1=point(x+vx*15-vy*6,y+vy*15+vx*6),c2=point(x+vx*10+vy*6,y+vy*10-vx*6),base=point(x,y);
   out+='<path d="M'+base.join(' ')+' Q'+c1.join(' ')+' '+tip.join(' ')+' Q'+c2.join(' ')+' '+base.join(' ')+'Z"/>';
  }
 }
 return out+'</g>';
}
const scale='<g fill="#172c35" stroke="#172c35" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><path d="M246 180h20l-6 25v117l14 18h-36l14-18V205Z"/><path d="M217 350h78v8h-78Z"/><path d="M168 202q44 12 88-2q44 14 88 2" fill="none"/><circle cx="256" cy="191" r="8"/><path d="m184 208-30 75h60Zm144 0-30 75h60Z" fill="none" stroke-width="3"/><path d="M151 283q33 29 66 0Zm144 0q33 29 66 0Z" stroke="none"/></g>';
const body='<circle cx="256" cy="256" r="248" fill="#fff" stroke="#204a43" stroke-width="3"/><circle cx="256" cy="256" r="239" fill="none" stroke="#204a43" stroke-width="1.5"/><circle cx="256" cy="256" r="174" fill="none" stroke="#172c35" stroke-width="1.5"/>'+arcText('YURTDAŞ',46,191)+arcText('HUKUK BÜROSU',34,215,true)+laurel('left','#b72d38')+laurel('right','#287450')+scale;
const seal='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" role="img" aria-labelledby="seal-title"><title id="seal-title">Yurtdaş Hukuk Bürosu</title>'+body+'</svg>\n';
writeFileSync(path.join(root,'assets/images/yurtdas-seal-v2.svg'),seal);
const name=font.getPath('Yurtdaş Hukuk',96,49,33);name.fill='#172c35';const sub=font.getPath('AVUKATLIK VE HUKUKİ DANIŞMANLIK',97,72,8.3);sub.fill='#40545b';
const horizontal='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 390 96" role="img" aria-labelledby="logo-title"><title id="logo-title">Yurtdaş Hukuk - Avukatlık ve hukuki danışmanlık</title><g transform="translate(2 3) scale(.175)">'+body+'</g>'+name.toSVG(2)+sub.toSVG(2)+'</svg>\n';
writeFileSync(path.join(root,'assets/images/yurtdas-logo-v2.svg'),horizontal);
console.log('Yeni dairesel logo ve yatay sürümü oluşturuldu.');
