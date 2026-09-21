<?php
namespace App\Services;
use App\Core\Env;
final class AnthropicProvider implements AiProvider {
 public function ask(string $prompt,array $ctx=[]): string {
  $key=Env::get('ANTHROPIC_API_KEY'); if(!$key) throw new \RuntimeException('Anthropic API klíč není nastaven.');
  $url=rtrim(Env::get('ANTHROPIC_BASE_URL','https://api.anthropic.com/v1'),'/').'/messages';
  $payload=['model'=>Env::get('ANTHROPIC_MODEL','claude-sonnet-4-5'),'max_tokens'=>1200,'system'=>'Jsi administrativní asistent českého podnikatele. Neprováděj nevratné finanční akce bez potvrzení. Kontext: '.json_encode($ctx,JSON_UNESCAPED_UNICODE),'messages'=>[['role'=>'user','content'=>$prompt]]];
  $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>60,CURLOPT_HTTPHEADER=>['x-api-key: '.$key,'anthropic-version: 2023-06-01','Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE)]);
  $body=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch); $j=json_decode((string)$body,true);
  if($code>=400) throw new \RuntimeException('Anthropic vrátil HTTP '.$code.'.');
  return (string)($j['content'][0]['text']??'');
 }
}
