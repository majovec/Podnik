<?php
namespace App\Services;
use App\Core\Env;

final class OcrService {
 public static function extract(string $path):array {
  $key=trim((string)Env::get('AI_API_KEY','')); if(!$key)return ['status'=>'not_configured','message'=>'AI_API_KEY není nakonfigurován.'];
  if(!is_file($path))throw new \RuntimeException('Soubor neexistuje.');
  $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path);
  $prompt='Z faktury vytěž JSON. Pole: supplier, ico, dic, document_number, variable_symbol, issue_date, due_date, total, amount_without_vat, vat_amount, currency, bank_account, iban. Datum vracej jako YYYY-MM-DD. Částky vracej jako čísla. Pokud údaj neexistuje, použij null. Vrať pouze validní JSON.';
  if($mime==='application/pdf'){
      $text=self::pdfText($path);
      if($text!=='') return self::askText($key,$prompt,$text);
      $image=self::pdfFirstPageImage($path);
      if($image) { try { return self::askImage($key,$prompt,$image); } finally { @unlink($image); } }
      return ['status'=>'not_configured','message'=>'PDF nelze automaticky přečíst: na serveru chybí pdftotext/pdftoppm.'];
  }
  return self::askImage($key,$prompt,$path);
 }
 private static function askText(string $key,string $prompt,string $text):array {
  $payload=['model'=>Env::get('AI_MODEL','gpt-5-mini'),'messages'=>[['role'=>'system','content'=>'Jsi OCR účetních dokladů. Vracej pouze validní JSON.'],['role'=>'user','content'=>$prompt."\n\nText faktury:\n".mb_substr($text,0,50000)]],'temperature'=>0];
  return self::request($key,$payload);
 }
 private static function askImage(string $key,string $prompt,string $path):array {
  $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path);$data=base64_encode((string)file_get_contents($path));
  $content=[['type'=>'text','text'=>$prompt],['type'=>'image_url','image_url'=>['url'=>'data:'.$mime.';base64,'.$data]]];
  $payload=['model'=>Env::get('AI_VISION_MODEL',Env::get('AI_MODEL','gpt-5-mini')),'messages'=>[['role'=>'system','content'=>'Jsi OCR účetních dokladů. Vracej pouze validní JSON.'],['role'=>'user','content'=>$content]],'temperature'=>0];
  return self::request($key,$payload);
 }
 private static function request(string $key,array $payload):array {
  $ch=curl_init(rtrim(Env::get('AI_BASE_URL','https://api.openai.com/v1'),'/').'/chat/completions');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>120,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE)]);$body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);$j=json_decode((string)$body,true);if($code>=400)throw new \RuntimeException('OCR HTTP '.$code.': '.($err?:($j['error']['message']??$body)));$text=$j['choices'][0]['message']['content']??'{}';$text=preg_replace('/^```(?:json)?|```$/m','',trim($text));$parsed=json_decode($text,true);return ['status'=>'ok','data'=>is_array($parsed)?$parsed:[]];
 }
 private static function pdfText(string $path):string {
  $bin=trim((string)shell_exec('command -v pdftotext 2>/dev/null'));if($bin==='')return ''; $tmp=tempnam(sys_get_temp_dir(),'byznio_pdf_');$cmd=escapeshellcmd($bin).' -layout '.escapeshellarg($path).' '.escapeshellarg($tmp);@exec($cmd,$out,$code);$txt=$code===0?(string)@file_get_contents($tmp):'';@unlink($tmp);return trim($txt);
 }
 private static function pdfFirstPageImage(string $path):?string {
  $bin=trim((string)shell_exec('command -v pdftoppm 2>/dev/null'));if($bin==='')return null;$base=tempnam(sys_get_temp_dir(),'byznio_pdfimg_');@unlink($base);$cmd=escapeshellcmd($bin).' -f 1 -singlefile -jpeg -r 150 '.escapeshellarg($path).' '.escapeshellarg($base);@exec($cmd,$out,$code);$file=$base.'.jpg';return $code===0&&is_file($file)?$file:null;
 }
}
