<?php
namespace App\Services;
use App\Core\Env;
final class OpenAiProvider implements AiProvider {
 public function ask(string $prompt,array $ctx=[]): string {
  $key=Env::get('AI_API_KEY'); if(!$key) throw new \RuntimeException('AI API klíč není nastaven.');
  $url=rtrim(Env::get('AI_BASE_URL','https://api.openai.com/v1'),'/').'/chat/completions';
  $payload=['model'=>Env::get('AI_MODEL','gpt-5-mini'),'messages'=>[['role'=>'system','content'=>'Jsi administrativní asistent českého podnikatele. Neprováděj nevratné finanční akce bez potvrzení. Odpovídej česky, stručně a prakticky. Kontext firmy: '.json_encode($ctx,JSON_UNESCAPED_UNICODE)],['role'=>'user','content'=>$prompt]],'temperature'=>0.2];
  return self::call($url,['Authorization: Bearer '.$key],$payload);
 }
 private static function call(string $url,array $headers,array $payload): string {
  $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>60,CURLOPT_HTTPHEADER=>array_merge($headers,['Content-Type: application/json']),CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE)]);
  $body=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch); $j=json_decode((string)$body,true);
  if($code>=400) throw new \RuntimeException('AI provider vrátil HTTP '.$code.'.');
  return (string)($j['choices'][0]['message']['content']??'');
 }
}
