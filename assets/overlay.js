let last=null,loading=0,audioId='',ready=false;
const verse=$('verse'), player=$('recitation');
async function refresh(){try{const s=await state();if(last&&last.revision===s.revision)return;const prev=last;last=s;
 const changed=!prev||s.surah!==prev.surah||s.ayah!==prev.ayah||s.translation!==prev.translation||s.reciter!==prev.reciter;
 verse.className='theme-'+s.theme+' anim-'+s.animation+(s.visible?' on':'');
 $('ref').style.display=s.showReference?'flex':'none';$('translation').style.display=s.showTranslation?'block':'none';$('arabic').style.fontSize=s.fontSize+'px';player.volume=s.volume;
 if(changed){let token=++loading;player.pause();player.removeAttribute('src');player.load();audioId='';try{const payload=await loadSurah(s.surah,s.translation,s.reciter);if(token!==loading)return;const ayah=payload.ayahs.find(a=>a.number===s.ayah);if(!ayah)throw Error('Ayah missing');$('surahName').textContent=payload.name;$('refCount').textContent=s.surah+':'+s.ayah;$('arabic').textContent=ayah.arabic;$('translation').textContent=ayah.translation;audioId=ayah.audio||'';ready=true;
 if(s.playing)playAudio();}catch(e){ready=false;console.error('Quran text error:',e);$('arabic').textContent='';$('translation').textContent='Unable to load Quran text. Check your connection.';}}
 else if(s.playing!==prev.playing){if(s.playing)playAudio();else player.pause()}
 }catch(e){console.error('Quran overlay polling:',e)}}
async function playAudio(){if(!audioId||!ready)return;try{if(player.src!==audioId){player.src=audioId;player.load()}await player.play()}catch(e){console.warn('OBS browser audio playback:',e)}}
player.addEventListener('ended',async()=>{try{const s=await state();if(!s.playing)return;if(s.autoNext){const payload=await loadSurah(s.surah,s.translation,s.reciter);if(s.ayah<payload.ayahs.length){await update({ayah:s.ayah+1,playing:true});return}}await update({playing:false})}catch(e){console.error(e)}});
// A quick poll is sufficient for presentations and avoids native OBS plugin installation.
refresh();setInterval(refresh,650);
