<?php
namespace App\Services;

use App\Core\Env;

final class GeminiProvider implements AiProvider {
 public function ask(string $prompt,array $ctx=[]): string {
  $key=trim((string)Env::get('AI_API_KEY',''));
  if($key==='') throw new \RuntimeException('AI API klíč není nastaven.');

  $base=rtrim((string)Env::get('AI_BASE_URL','https://generativelanguage.googleapis.com/v1beta'),'/');
  $model=(string)Env::get('AI_MODEL','gemini-2.5-flash');
  $url=$base.'/models/'.rawurlencode($model).':generateContent?key='.rawurlencode($key);
  $system='Jsi administrativní asistent českého podnikatele. Neprováděj nevratné finanční akce bez potvrzení. Odpovídej česky, stručně a prakticky. Kontext firmy: '.json_encode($ctx,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $payload=[
   'systemInstruction'=>['parts'=>[['text'=>$system]]],
   'contents'=>[['role'=>'user','parts'=>[['text'=>$prompt]]]],
   'generationConfig'=>['temperature'=>0.2]
  ];
  return $this->call($url,$payload);
 }

 private function call(string $url,array $payload): string {
  $ch=curl_init($url);
  curl_setopt_array($ch,[
   CURLOPT_RETURNTRANSFER=>true,
   CURLOPT_POST=>true,
   CURLOPT_TIMEOUT=>60,
   CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
   CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
  ]);
  $body=curl_exec($ch);
  $err=curl_error($ch);
  $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
  curl_close($ch);
  $j=json_decode((string)$body,true);
  if($code>=400) {
   $msg=$j['error']['message']??($err?:'Neznámá chyba Gemini API.');
   throw new \RuntimeException('Gemini vrátil HTTP '.$code.': '.$msg);
  }
  if(!is_array($j)) throw new \RuntimeException('Gemini vrátil neplatnou odpověď.');
  $parts=$j['candidates'][0]['content']['parts']??[];
  $text='';
  foreach($parts as $part) if(isset($part['text'])) $text.=(string)$part['text'];
  if($text==='') throw new \RuntimeException('Gemini nevrátil textovou odpověď.');
  return $text;
 }
}
