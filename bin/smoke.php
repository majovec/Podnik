<?php
declare(strict_types=1);
// Run inside the Docker/container environment after composer install.
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/src/bootstrap.php';
$pdo=App\Core\Database::pdo();
$tables=$pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table'")->fetchColumn();
if((int)$tables<20)throw new RuntimeException('Schema smoke test failed: too few tables.');
foreach(['workspaces','users','customers','documents','payments','jobs','expenses','products','bank_accounts','bank_transactions','calendar_events','recurring_invoices','tax_profiles','webhook_events'] as $t){$pdo->query('SELECT 1 FROM '.$t.' LIMIT 1');}
echo "SMOKE OK: {$tables} tables\n";
