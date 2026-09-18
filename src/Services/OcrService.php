<?php
namespace App\Services;
use App\Core\Env;
final class OcrService {
 public static function extract(string $path):array {
  $key=Env::get('AI_API_KEY'); if(!$key)return ['status'=>'not_configured','message'=>'AI_API_KEY není nakonfigurován.'];
  if(!is_file($path))throw new \RuntimeException('Soubor neexistuje.'); $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path); $data=base64_encode(file_get_contents($path));
  $content=[['type'=>'text','text'=>'Z dokumentu vytěž JSON: supplier, document_number, date, total, vat_amount, currency. Pokud údaj neexistuje, použij null. Vrať pouze JSON.'],['type'=>'image_url','image_url'=>['url'=>'data:'.$mime.';base64,'.$data]]];
  $payload=['model'=>Env::get('AI_VISION_MODEL',Env::get('AI_MODEL','gpt-5-mini')),'messages'=>[['role'=>'system','content'=>'Jsi OCR účetních dokladů. Vracej pouze validní JSON.'],['role'=>'user','content'=>$content]],'temperature'=>0];
  $ch=curl_init(rtrim(Env::get('AI_BASE_URL','https://api.openai.com/v1'),'/').'/chat/completions');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>120,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE)]);$body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);$j=json_decode((string)$body,true);if($code>=400)throw new \RuntimeException('OCR HTTP '.$code.': '.($err?:($j['error']['message']??$body)));$text=$j['choices'][0]['message']['content']??'{}';$text=preg_replace('/^```(?:json)?|```$/m','',trim($text));$parsed=json_decode($text,true);return ['status'=>'ok','data'=>is_array($parsed)?$parsed:[]];
 }
}
