<?php
// Quran OBS Studio - minimal, filesystem-backed local control endpoint.
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$dir = __DIR__ . '/data';
if (!is_dir($dir)) mkdir($dir, 0775, true);
$stateFile = $dir . '/state.json';
$default = ['surah'=>1,'ayah'=>1,'visible'=>false,'playing'=>false,'revision'=>0,'translation'=>'en.sahih','reciter'=>'ar.alafasy','theme'=>'royal','animation'=>'slide','fontSize'=>48,'showTranslation'=>true,'showReference'=>true,'volume'=>0.8,'autoNext'=>false];
function respond($value, int $status=200): void {http_response_code($status);echo json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
function stateRead(string $file, array $fallback): array { $s = is_file($file) ? json_decode(file_get_contents($file) ?: '{}',true) : null;return is_array($s)?array_merge($fallback,$s):$fallback; }
$action=$_GET['action']??'state';
if($action==='state') respond(stateRead($stateFile,$default));
if($action==='update') {
 if($_SERVER['REQUEST_METHOD']!=='POST') respond(['error'=>'POST required'],405);
 $raw=file_get_contents('php://input');if(strlen($raw)>16000)respond(['error'=>'Request too large'],413);
 $input=json_decode($raw,true);if(!is_array($input))respond(['error'=>'Invalid JSON'],400);
 $state=stateRead($stateFile,$default);
 $boolKeys=['visible','playing','showTranslation','showReference','autoNext'];
 foreach($boolKeys as $k)if(array_key_exists($k,$input))$state[$k]=(bool)$input[$k];
 foreach(['surah'=>[1,114],'ayah'=>[1,286],'fontSize'=>[24,100]] as $k=>$range)if(isset($input[$k]))$state[$k]=max($range[0],min($range[1],(int)$input[$k]));
 if(isset($input['volume']))$state['volume']=max(0,min(1,(float)$input['volume']));
 foreach(['translation'=>['en.sahih','de.bubenheim','fa.fooladvand','tr.diyanet','ur.jalandhry'],'reciter'=>['ar.alafasy','ar.husary','ar.minshawi'],'theme'=>['royal','minimal','glass','emerald','gold','kaaba','ottoman','desert','arch','parchment'],'animation'=>['slide','fade','zoom','flip','rise','none']] as $k=>$values)if(isset($input[$k])&&in_array($input[$k],$values,true))$state[$k]=$input[$k];
 $state['revision']=(int)$state['revision']+1;
 $tmp=tempnam($dir,'state_');file_put_contents($tmp,json_encode($state,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX);rename($tmp,$stateFile);respond($state);
}
if($action==='meta') {
 $cache=$dir.'/metadata.json';
 if(is_file($cache)&&time()-filemtime($cache)<86400*14){readfile($cache);exit;}
 $url='https://api.alquran.cloud/v1/meta';
 $result=false;
 if(function_exists('curl_init')){$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>18,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_USERAGENT=>'Quran-OBS-Studio/1.0']);$result=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);if($status!==200)$result=false;}
 else {$result=@file_get_contents($url,false,stream_context_create(['http'=>['timeout'=>18]]));}
 if($result&&($parsed=json_decode($result,true))&&($parsed['code']??0)===200){file_put_contents($cache,$result,LOCK_EX);echo $result;exit;}
 if(is_file($cache)){readfile($cache);exit;}
 respond(['error'=>'Unable to retrieve surah metadata'],502);
}
if($action==='surah') {
 $surah=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
 if(!$surah || $surah<1 || $surah>114)respond(['error'=>'Invalid surah'],400);
 $translation=$_GET['translation']??'en.sahih';
 if(!in_array($translation,['en.sahih','de.bubenheim','fa.fooladvand','tr.diyanet','ur.jalandhry'],true))$translation='en.sahih';
 $reciter=$_GET['reciter']??'ar.alafasy';
 if(!in_array($reciter,['ar.alafasy','ar.husary','ar.minshawi'],true))$reciter='ar.alafasy';
 $key=$surah.'-'.$translation.'-'.$reciter;$cache=$dir.'/surah-'.preg_replace('/[^a-z0-9.-]/','',$key).'.json';
 if(is_file($cache)&&time()-filemtime($cache)<86400*14){header('X-Quran-Cache: HIT');readfile($cache);exit;}
 // AlQuran Cloud multi-edition endpoint, whose data array aligns by ayah index.
 $url='https://api.alquran.cloud/v1/surah/'.$surah.'/editions/quran-uthmani,'.rawurlencode($translation).','.rawurlencode($reciter);
 $result=false;
 if(function_exists('curl_init')){$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>18,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_USERAGENT=>'Quran-OBS-Studio/1.0']);$result=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);if($status!==200)$result=false;}
 else {$context=stream_context_create(['http'=>['timeout'=>18,'header'=>"User-Agent: Quran-OBS-Studio/1.0\r\n"]]);$result=@file_get_contents($url,false,$context);}
 if(!$result){if(is_file($cache)){header('X-Quran-Cache: STALE');readfile($cache);exit;}respond(['error'=>'Quran source unavailable. Check internet connection and try again.'],502);}
 $parsed=json_decode($result,true);
 if(!is_array($parsed)||($parsed['code']??0)!==200||count($parsed['data']??[])<3)respond(['error'=>'Unexpected Quran API response'],502);
 $arabic=$parsed['data'][0]['ayahs']??[];$translationAyahs=$parsed['data'][1]['ayahs']??[];$audioAyahs=$parsed['data'][2]['ayahs']??[];
 $output=['surah'=>(int)$surah,'name'=>$parsed['data'][0]['englishName']??('Surah '.$surah),'arabicName'=>$parsed['data'][0]['name']??'','translation'=>$translation,'reciter'=>$reciter,'ayahs'=>[]];
 foreach($arabic as $i=>$ayah){$translationObj=$translationAyahs[$i]??[];$audioObj=$audioAyahs[$i]??[];$output['ayahs'][]=['number'=>(int)($ayah['numberInSurah']??($i+1)),'global'=>(int)($ayah['number']??0),'arabic'=>$ayah['text']??'','translation'=>$translationObj['text']??'','audio'=>$audioObj['audio']??''];}
 $serialized=json_encode($output,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
 file_put_contents($cache,$serialized,LOCK_EX);echo $serialized;exit;
}
respond(['error'=>'Unknown action'],404);
