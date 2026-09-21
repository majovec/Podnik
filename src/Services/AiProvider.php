<?php
namespace App\Services;
interface AiProvider { public function ask(string $prompt,array $ctx=[]): string; }
