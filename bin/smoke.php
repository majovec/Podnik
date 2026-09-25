<?php
declare(strict_types=1);
// Run inside the Docker/container environment after composer install.
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/src/bootstrap.php';
$pdo=App\Core\Database::pdo();
$tables=$pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table'")->fetchColumn();
if((int)$tables<20)throw new RuntimeException('Schema smoke test failed: too few tables.');
foreach(['workspace_id','status'] as $col){$cols=$pdo->query('PRAGMA table_info(subscriptions)')->fetchAll();if(!in_array($col,array_column($cols,'name'),true))throw new RuntimeException('Schema smoke test failed: subscriptions missing '.$col);}
$pdo->beginTransaction();$pdo->exec("INSERT INTO workspaces(name) VALUES('Smoke A'),('Smoke B')");$a=(int)$pdo->lastInsertId();$b=$a-1;$pdo->exec("INSERT INTO customers(workspace_id,company_name) VALUES($a,'A customer'),($b,'B customer')");$q=$pdo->prepare('SELECT COUNT(*) FROM customers WHERE workspace_id=?');$q->execute([$a]);if((int)$q->fetchColumn()!==1)throw new RuntimeException('Tenant isolation smoke test failed.');$pdo->rollBack();
foreach(['workspaces','users','customers','documents','payments','jobs','expenses','products','bank_accounts','bank_transactions','calendar_events','recurring_invoices','tax_profiles','webhook_events'] as $t){$pdo->query('SELECT 1 FROM '.$t.' LIMIT 1');}
foreach(['received_invoices','currency_rates','accountant_shares','document_chain','comgate_accounts'] as $t){$pdo->query('SELECT 1 FROM '.$t.' LIMIT 1');}
$docCols=$pdo->query('PRAGMA table_info(documents)')->fetchAll();foreach(['parent_document_id','tax_date','currency','cnb_rate','tax_regime','language','advance_applied'] as $c)if(!in_array($c,array_column($docCols,'name'),true))throw new RuntimeException('documents missing '.$c);
$seriesCols=$pdo->query('PRAGMA table_info(document_series)')->fetchAll();foreach(['series_year','year_prefix'] as $c)if(!in_array($c,array_column($seriesCols,'name'),true))throw new RuntimeException('document_series missing '.$c);
$pdo->exec("INSERT INTO workspaces(name) VALUES('Series Smoke')");$sw=(int)$pdo->lastInsertId();$n1=App\Services\DocumentService::nextNumber($pdo,$sw,'invoice');if(!str_contains($n1,(string)date('Y')))throw new RuntimeException('Series year smoke failed.');
echo "SMOKE OK: {$tables} tables + feature migrations\n";
