<?php
namespace App\Services;
use App\Core\Env;

final class GeminiProvider implements AiProvider {
    public function ask(string $prompt,array $ctx=[]): string {
        $key=Env::get('AI_API_KEY');
        if(!$key) throw new \RuntimeException('Gemini API klíč není nastaven.');
        $model=Env::get('AI_MODEL','gemini-3.8-flash');
        $base=rtrim(Env::get('AI_BASE_URL','https://generativelanguage.googleapis.com/v1beta'),'/');
        $url=$base.'/models/'.rawurlencode($model).':generateContent?key='.rawurlencode($key);
        $payload=[
            'systemInstruction'=>['parts'=>[['text'=>'Jsi administrativní asistent českého podnikatele. Neprováděj nevratné finanční akce bez potvrzení. Odpovídej česky, stručně a prakticky. Kontext firmy: '.json_encode($ctx,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]]],
            'contents'=>[['role'=>'user','parts'=>[['text'=>$prompt]]]],
            'generationConfig'=>['temperature'=>0.2]
        ];
        $ch=curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_TIMEOUT=>60,
            CURLOPT_IPRESOLVE=>CURL_IPRESOLVE_V4,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
        ]);
        $body=curl_exec($ch);
        $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $err=curl_error($ch);
        curl_close($ch);
        $j=json_decode((string)$body,true);
        if($body===false || $code>=400){
            $message=$j['error']['message']??$err??$body;
            throw new \RuntimeException('Gemini vrátil HTTP '.$code.': '.$message);
        }
        $text=$j['candidates'][0]['content']['parts'][0]['text']??'';
        if($text==='') throw new \RuntimeException('Gemini nevrátil textovou odpověď.');
        return (string)$text;
    }
}
