<?php
namespace App\Services;
use App\Core\Env;
final class AiProviderFactory {
 public static function make(): AiProvider {
  return strtolower((string)Env::get('AI_PROVIDER','openai'))==='anthropic' ? new AnthropicProvider() : new OpenAiProvider();
 }
}
