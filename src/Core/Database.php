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
        $adds=['flat_monthly_tax'=>'REAL DEFAULT 0','flat_social_monthly'=>'REAL DEFAULT 0','flat_health_monthly'=>'REAL DEFAULT 0','tax_credit'=>'REAL DEFAULT 0','expense_lump_rate'=>'REAL DEFAULT 0.6','year'=>'INTEGER DEFAULT 2026','tax_threshold'=>'REAL DEFAULT 1762812'];
        foreach($adds as $name=>$type) if(!in_array($name,$names,true)) self::$pdo->exec("ALTER TABLE tax_profiles ADD COLUMN $name $type");
        $ws=self::$pdo->query("PRAGMA table_info(workspaces)")->fetchAll();$wn=array_column($ws,'name');foreach(['email_localpart'=>'TEXT','mail_enabled'=>'INTEGER DEFAULT 1','mail_invoices'=>'INTEGER DEFAULT 1','mail_reminders'=>'INTEGER DEFAULT 1','mail_receipts'=>'INTEGER DEFAULT 1','mail_offers'=>'INTEGER DEFAULT 1','mail_orders'=>'INTEGER DEFAULT 1','mail_proformas'=>'INTEGER DEFAULT 1','mail_credits'=>'INTEGER DEFAULT 1','weekly_report_enabled'=>'INTEGER DEFAULT 1','monthly_report_enabled'=>'INTEGER DEFAULT 1','last_weekly_report_at'=>'TEXT','last_monthly_report_at'=>'TEXT','onboarding_completed_at'=>'TEXT'] as $n=>$t)if(!in_array($n,$wn,true))self::$pdo->exec("ALTER TABLE workspaces ADD COLUMN $n $t");
        $uc=self::$pdo->query("PRAGMA table_info(users)")->fetchAll();$un=array_column($uc,'name');foreach(['email_verified_at'=>'TEXT','email_verification_token_hash'=>'TEXT','email_verification_expires_at'=>'TEXT'] as $n=>$t)if(!in_array($n,$un,true))self::$pdo->exec("ALTER TABLE users ADD COLUMN $n $t");self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_verification_hash ON users(email_verification_token_hash)");
        $eq=self::$pdo->query("PRAGMA table_info(email_queue)")->fetchAll();$en=array_column($eq,'name');if(!in_array('action_url',$en,true))self::$pdo->exec("ALTER TABLE email_queue ADD COLUMN action_url TEXT");if(!in_array('html_body',$en,true))self::$pdo->exec("ALTER TABLE email_queue ADD COLUMN html_body TEXT");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS email_messages(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL,customer_id INTEGER,document_id INTEGER,direction TEXT NOT NULL DEFAULT 'inbound',from_email TEXT,to_email TEXT,subject TEXT,body TEXT,html_body TEXT,message_id TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE SET NULL)"); self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_email_messages_workspace ON email_messages(workspace_id,created_at)");
        $eq=self::$pdo->query("PRAGMA table_info(email_queue)")->fetchAll();$en=array_column($eq,'name');if(!in_array('reply_to',$en,true))self::$pdo->exec("ALTER TABLE email_queue ADD COLUMN reply_to TEXT");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS gopay_accounts(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL UNIQUE,goid_encrypted TEXT NOT NULL,client_id_encrypted TEXT NOT NULL,client_secret_encrypted TEXT NOT NULL,active INTEGER NOT NULL DEFAULT 1,created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");
        $dc=self::$pdo->query("PRAGMA table_info(documents)")->fetchAll();$dn=array_column($dc,'name');if(!in_array('gopay_payment_id',$dn,true))self::$pdo->exec("ALTER TABLE documents ADD COLUMN gopay_payment_id INTEGER");
        if(!in_array('payment_method',$dn,true))self::$pdo->exec("ALTER TABLE documents ADD COLUMN payment_method TEXT DEFAULT 'bank_transfer'");
        if(!in_array('public_token',$dn,true))self::$pdo->exec("ALTER TABLE documents ADD COLUMN public_token TEXT");
        $existing=self::$pdo->query("SELECT id FROM documents WHERE public_token IS NULL OR public_token=''")->fetchAll();
        $fill=self::$pdo->prepare('UPDATE documents SET public_token=? WHERE id=?');
        foreach($existing as $row){do{$token=bin2hex(random_bytes(32));$check=self::$pdo->prepare('SELECT COUNT(*) FROM documents WHERE public_token=?');$check->execute([$token]);}while((int)$check->fetchColumn()>0);$fill->execute([$token,(int)$row['id']]);}
        self::$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_documents_public_token ON documents(public_token)");
        $sc=self::$pdo->query("PRAGMA table_info(subscriptions)")->fetchAll();$sn=array_column($sc,'name');foreach(['billing_interval'=>"TEXT DEFAULT 'month'",'free_until'=>'TEXT'] as $n=>$t)if(!in_array($n,$sn,true))self::$pdo->exec("ALTER TABLE subscriptions ADD COLUMN $n $t");
        $sc=self::$pdo->query("PRAGMA table_info(subscriptions)")->fetchAll();$sn=array_column($sc,'name');foreach(['gopay_subscription_payment_id'=>'INTEGER'] as $n=>$t)if(!in_array($n,$sn,true))self::$pdo->exec("ALTER TABLE subscriptions ADD COLUMN $n $t");self::$pdo->exec("CREATE TABLE IF NOT EXISTS gopay_subscription_payments(id INTEGER PRIMARY KEY AUTOINCREMENT,payment_id INTEGER NOT NULL UNIQUE,workspace_id INTEGER NOT NULL,parent_payment_id INTEGER,state TEXT,amount REAL DEFAULT 0,created_at TEXT DEFAULT CURRENT_TIMESTAMP,paid_at TEXT,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_gopay_subscription_payments_workspace ON gopay_subscription_payments(workspace_id,created_at)");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS saas_settings(id INTEGER PRIMARY KEY CHECK(id=1),monthly_price_czk REAL NOT NULL DEFAULT 300,yearly_price_czk REAL NOT NULL DEFAULT 3240,trial_days INTEGER NOT NULL DEFAULT 14,mail_domain TEXT NOT NULL DEFAULT 'byznio.cz',last_automatic_backup_at TEXT,mail_enabled_default INTEGER NOT NULL DEFAULT 1,updated_at TEXT DEFAULT CURRENT_TIMESTAMP)");self::$pdo->exec("INSERT OR IGNORE INTO saas_settings(id) VALUES(1)");
        $ds=self::$pdo->query("PRAGMA table_info(document_series)")->fetchAll();$dsn=array_column($ds,'name');if(!in_array('series_year',$dsn,true))self::$pdo->exec("ALTER TABLE document_series ADD COLUMN series_year INTEGER DEFAULT 0");if(!in_array('prefix',array_column($ds,'name'),true)){} 
        $dc2=self::$pdo->query("PRAGMA table_info(documents)")->fetchAll();$dn2=array_column($dc2,'name');foreach(['tax_regime'=>"TEXT DEFAULT 'standard'",'currency'=>"TEXT DEFAULT 'CZK'",'exchange_rate'=>"REAL DEFAULT 1",'language'=>"TEXT DEFAULT 'cs'"] as $n=>$t)if(!in_array($n,$dn2,true))self::$pdo->exec("ALTER TABLE documents ADD COLUMN $n $t");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS tax_rates(id INTEGER PRIMARY KEY AUTOINCREMENT,rate REAL NOT NULL,valid_from TEXT NOT NULL,valid_to TEXT,active INTEGER DEFAULT 1,UNIQUE(rate,valid_from))");self::$pdo->exec("INSERT OR IGNORE INTO tax_rates(rate,valid_from,active) VALUES(21,'2024-01-01',1),(12,'2024-01-01',1),(0,'2024-01-01',1)");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS received_invoices(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL,supplier_name TEXT,supplier_ico TEXT,document_number TEXT,variable_symbol TEXT,issue_date TEXT,due_date TEXT,amount REAL DEFAULT 0,vat_amount REAL DEFAULT 0,currency TEXT DEFAULT 'CZK',payment_status TEXT DEFAULT 'unpaid',paid_at TEXT,attachment_path TEXT,ocr_json TEXT,note TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP,updated_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS accounting_exports(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL,format TEXT NOT NULL,period_from TEXT,period_to TEXT,file_path TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");

        $ss=self::$pdo->query("PRAGMA table_info(saas_settings)")->fetchAll();$ssn=array_column($ss,'name');if(!in_array('last_automatic_backup_at',$ssn,true))self::$pdo->exec("ALTER TABLE saas_settings ADD COLUMN last_automatic_backup_at TEXT");
    }
}
