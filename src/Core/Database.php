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
        $ws=self::$pdo->query("PRAGMA table_info(workspaces)")->fetchAll();$wn=array_column($ws,'name');foreach(['email_localpart'=>'TEXT','mail_enabled'=>'INTEGER DEFAULT 1','mail_invoices'=>'INTEGER DEFAULT 1','mail_reminders'=>'INTEGER DEFAULT 1','mail_receipts'=>'INTEGER DEFAULT 1','mail_offers'=>'INTEGER DEFAULT 1','weekly_report_enabled'=>'INTEGER DEFAULT 1','monthly_report_enabled'=>'INTEGER DEFAULT 1','last_weekly_report_at'=>'TEXT','last_monthly_report_at'=>'TEXT','onboarding_completed_at'=>'TEXT'] as $n=>$t)if(!in_array($n,$wn,true))self::$pdo->exec("ALTER TABLE workspaces ADD COLUMN $n $t");
        $eq=self::$pdo->query("PRAGMA table_info(email_queue)")->fetchAll();$en=array_column($eq,'name');if(!in_array('action_url',$en,true))self::$pdo->exec("ALTER TABLE email_queue ADD COLUMN action_url TEXT");if(!in_array('html_body',$en,true))self::$pdo->exec("ALTER TABLE email_queue ADD COLUMN html_body TEXT");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS gopay_accounts(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL UNIQUE,goid_encrypted TEXT NOT NULL,client_id_encrypted TEXT NOT NULL,client_secret_encrypted TEXT NOT NULL,active INTEGER NOT NULL DEFAULT 1,created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");
        $dc=self::$pdo->query("PRAGMA table_info(documents)")->fetchAll();$dn=array_column($dc,'name');if(!in_array('gopay_payment_id',$dn,true))self::$pdo->exec("ALTER TABLE documents ADD COLUMN gopay_payment_id INTEGER");
        if(!in_array('public_token',$dn,true))self::$pdo->exec("ALTER TABLE documents ADD COLUMN public_token TEXT");
        $existing=self::$pdo->query("SELECT id FROM documents WHERE public_token IS NULL OR public_token=''")->fetchAll();
        $fill=self::$pdo->prepare('UPDATE documents SET public_token=? WHERE id=?');
        foreach($existing as $row){do{$token=bin2hex(random_bytes(32));$check=self::$pdo->prepare('SELECT COUNT(*) FROM documents WHERE public_token=?');$check->execute([$token]);}while((int)$check->fetchColumn()>0);$fill->execute([$token,(int)$row['id']]);}
        self::$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_documents_public_token ON documents(public_token)");
        $sc=self::$pdo->query("PRAGMA table_info(subscriptions)")->fetchAll();$sn=array_column($sc,'name');foreach(['billing_interval'=>"TEXT DEFAULT 'month'",'free_until'=>'TEXT'] as $n=>$t)if(!in_array($n,$sn,true))self::$pdo->exec("ALTER TABLE subscriptions ADD COLUMN $n $t");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS saas_settings(id INTEGER PRIMARY KEY CHECK(id=1),monthly_price_czk REAL NOT NULL DEFAULT 300,yearly_price_czk REAL NOT NULL DEFAULT 3240,trial_days INTEGER NOT NULL DEFAULT 14,mail_domain TEXT NOT NULL DEFAULT 'byznio.cz',last_automatic_backup_at TEXT,mail_enabled_default INTEGER NOT NULL DEFAULT 1,updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");self::$pdo->exec("INSERT OR IGNORE INTO saas_settings(id) VALUES(1)");
        $ss=self::$pdo->query("PRAGMA table_info(saas_settings)")->fetchAll();$ssn=array_column($ss,'name');if(!in_array('last_automatic_backup_at',$ssn,true))self::$pdo->exec("ALTER TABLE saas_settings ADD COLUMN last_automatic_backup_at TEXT");
    }
}
