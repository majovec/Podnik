<?php
declare(strict_types=1);

use App\Core\Env;
use App\Core\Database;
use App\Core\Auth;

Env::load(dirname(__DIR__).'/.env');
date_default_timezone_set('Europe/Prague');

$storage = dirname(__DIR__).'/storage';
if (!is_dir($storage.'/uploads')) @mkdir($storage.'/uploads', 0775, true);
if (!is_dir($storage.'/logs')) @mkdir($storage.'/logs', 0775, true);

Database::boot(dirname(__DIR__).'/'.Env::get('DB_PATH','database/app.sqlite'));
Auth::boot();
