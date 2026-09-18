<?php
namespace App\Core;
use PDO;
final class Database {
    private static PDO $pdo;
    public static function boot(string $path): void {
        $dir=dirname($path); if(!is_dir($dir)) @mkdir($dir,0775,true);
        self::$pdo=new PDO('sqlite:'.$path,null,null,[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false
        ]);
        self::$pdo->exec('PRAGMA foreign_keys=ON');
        self::migrate();
    }
    public static function pdo(): PDO { return self::$pdo; }
    private static function migrate(): void {
        $sql=file_get_contents(dirname(__DIR__,2).'/database/schema.sql');
        self::$pdo->exec($sql);
        // Forward-compatible migrations for existing installations.
        $cols=self::$pdo->query("PRAGMA table_info(tax_profiles)")->fetchAll();
        $names=array_column($cols,'name');
        $adds=['flat_monthly_tax'=>'REAL DEFAULT 0','flat_social_monthly'=>'REAL DEFAULT 0','flat_health_monthly'=>'REAL DEFAULT 0','tax_credit'=>'REAL DEFAULT 0','expense_lump_rate'=>'REAL DEFAULT 0.6','year'=>'INTEGER DEFAULT 2026'];
        foreach($adds as $name=>$type) if(!in_array($name,$names,true)) self::$pdo->exec("ALTER TABLE tax_profiles ADD COLUMN $name $type");
        $ws=self::$pdo->query("PRAGMA table_info(workspaces)")->fetchAll();$wn=array_column($ws,'name');foreach(['email_localpart'=>'TEXT','mail_enabled'=>'INTEGER DEFAULT 1','mail_invoices'=>'INTEGER DEFAULT 1','mail_reminders'=>'INTEGER DEFAULT 1','mail_receipts'=>'INTEGER DEFAULT 1','mail_offers'=>'INTEGER DEFAULT 1'] as $n=>$t)if(!in_array($n,$wn,true))self::$pdo->exec("ALTER TABLE workspaces ADD COLUMN $n $t");
        $dc=self::$pdo->query("PRAGMA table_info(documents)")->fetchAll();$dn=array_column($dc,'name');if(!in_array('gopay_payment_id',$dn,true))self::$pdo->exec("ALTER TABLE documents ADD COLUMN gopay_payment_id INTEGER");
        $sc=self::$pdo->query("PRAGMA table_info(subscriptions)")->fetchAll();$sn=array_column($sc,'name');foreach(['billing_interval'=>"TEXT DEFAULT 'month'",'free_until'=>'TEXT'] as $n=>$t)if(!in_array($n,$sn,true))self::$pdo->exec("ALTER TABLE subscriptions ADD COLUMN $n $t");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS saas_settings(id INTEGER PRIMARY KEY CHECK(id=1),monthly_price_czk REAL NOT NULL DEFAULT 300,yearly_price_czk REAL NOT NULL DEFAULT 3240,trial_days INTEGER NOT NULL DEFAULT 14,mail_domain TEXT NOT NULL DEFAULT 'nasystem.cz',mail_enabled_default INTEGER NOT NULL DEFAULT 1,updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");self::$pdo->exec("INSERT OR IGNORE INTO saas_settings(id) VALUES(1)");
    }
}
