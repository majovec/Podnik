<?php
namespace App\Services;
final class AiService {
 public static function ask(string $prompt,array $ctx=[]):string { try { return AiProviderFactory::make()->ask($prompt,$ctx); } catch(\Throwable $e) { return 'AI se nepodařilo kontaktovat: '.$e->getMessage(); } }
}
