let current=null,chapters=[],busy=false;
const setStatus=t=>$('status').textContent=t;
async function init(){try{
 const r=await fetch('api.php?action=meta');const j=await r.json();if(j.code!==200)throw Error('Surah metadata could not be loaded');chapters=j.data.surahs.references;
 chapters.forEach(c=>{let o=document.createElement('option');o.value=c.number;o.textContent=`${c.number}. ${c.englishName} — ${c.name}`;$('surah').appendChild(o)});
 current=await state();for(let id of ['surah','reciter','translation','theme','animation','fontSize','volume','showTranslation','showReference','autoNext']){
 let el=$(id);if(id==='volume')el.value=Math.round(current.volume*100);else if(el.type==='checkbox')el.checked=!!current[id];else el.value=current[id];}
 let language=localStorage.getItem('quran-obs-lang')||'de';$('lang').value=language;translate(language);populateAyahs();setStatus(i18n[language]?.ready||'Ready');
 }catch(e){setStatus('⚠ '+e.message)}}
function populateAyahs(){const c=chapters.find(x=>x.number===Number($('surah').value));let max=c?.numberOfAyahs||7;$('ayah').replaceChildren();for(let n=1;n<=max;n++){const o=document.createElement('option');o.value=n;o.textContent=String(n);$('ayah').append(o)}$('ayah').value=Math.min(Number(current?.ayah||1),max)}
async function patch(value){try{current=await update(value);setStatus('✓');}catch(e){setStatus('⚠ '+e.message)}}
$('lang').addEventListener('change',e=>{localStorage.setItem('quran-obs-lang',e.target.value);translate(e.target.value)});
$('surah').addEventListener('change',()=>{current.ayah=1;populateAyahs();patch({surah:Number($('surah').value),ayah:1,playing:false})});
$('ayah').addEventListener('change',()=>patch({ayah:Number($('ayah').value),playing:false}));
for(const id of ['reciter','translation','theme','animation'])$(id).addEventListener('change',()=>patch({[id]:$(id).value}));
for(const id of ['showTranslation','showReference','autoNext'])$(id).addEventListener('change',()=>patch({[id]:$(id).checked}));
$('fontSize').addEventListener('change',()=>patch({fontSize:Number($('fontSize').value)}));
$('volume').addEventListener('change',()=>patch({volume:Number($('volume').value)/100}));
$('show').onclick=()=>patch({visible:true});$('hide').onclick=()=>patch({visible:false});
$('play').onclick=()=>patch({visible:true,playing:true});$('pause').onclick=()=>patch({playing:false});$('stop').onclick=()=>patch({playing:false,visible:false});
function step(direction){const n=Number($('ayah').value);const c=chapters.find(x=>x.number===Number($('surah').value));let next=Math.min(c.numberOfAyahs,Math.max(1,n+direction));$('ayah').value=next;patch({ayah:next,playing:false})}
$('prev').onclick=()=>step(-1);$('next').onclick=()=>step(1);
// Sync dock with any overlay-driven auto-advance. Avoid overwriting fields while user is interacting.
setInterval(async()=>{if(!current)return;try{const s=await state();if(s.revision!==current.revision){current=s;if(document.activeElement!==$('surah'))$('surah').value=s.surah;populateAyahs();if(document.activeElement!==$('ayah'))$('ayah').value=s.ayah;}}catch{}},1500);
init();
