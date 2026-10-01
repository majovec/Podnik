<?php
namespace App\Services;
use App\Core\Env;
final class AiProviderFactory {
 public static function make(): AiProvider {
  $provider=strtolower((string)Env::get('AI_PROVIDER','gemini'));
  return match($provider){
   'gemini'=>new GeminiProvider(),
   'anthropic'=>new AnthropicProvider(),
   default=>new OpenAiProvider(),
  };
 }
}
