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
        // Legacy customer rows from older installations could have a NULL active flag.
        // Treat those rows as active so they remain visible in CRM after an update.
        self::$pdo->exec("UPDATE customers SET active=1 WHERE active IS NULL");
        // Forward-compatible migrations for existing installations.
        $cols=self::$pdo->query("PRAGMA table_info(tax_profiles)")->fetchAll();
        $names=array_column($cols,'name');
        $adds=['flat_monthly_tax'=>'REAL DEFAULT 0','flat_social_monthly'=>'REAL DEFAULT 0','flat_health_monthly'=>'REAL DEFAULT 0','tax_credit'=>'REAL DEFAULT 0','expense_lump_rate'=>'REAL DEFAULT 0.6','year'=>'INTEGER DEFAULT 2026'];
        foreach($adds as $name=>$type) if(!in_array($name,$names,true)) self::$pdo->exec("ALTER TABLE tax_profiles ADD COLUMN $name $type");
        $ws=self::$pdo->query("PRAGMA table_info(workspaces)")->fetchAll();$wn=array_column($ws,'name');foreach(['email_localpart'=>'TEXT','mail_enabled'=>'INTEGER DEFAULT 1','mail_invoices'=>'INTEGER DEFAULT 1','mail_reminders'=>'INTEGER DEFAULT 1','mail_receipts'=>'INTEGER DEFAULT 1','mail_offers'=>'INTEGER DEFAULT 1','mail_orders'=>'INTEGER DEFAULT 1','mail_proformas'=>'INTEGER DEFAULT 1','mail_credits'=>'INTEGER DEFAULT 1','weekly_report_enabled'=>'INTEGER DEFAULT 1','monthly_report_enabled'=>'INTEGER DEFAULT 1','last_weekly_report_at'=>'TEXT','last_monthly_report_at'=>'TEXT','onboarding_completed_at'=>'TEXT'] as $n=>$t)if(!in_array($n,$wn,true))self::$pdo->exec("ALTER TABLE workspaces ADD COLUMN $n $t");
        $uc=self::$pdo->query("PRAGMA table_info(users)")->fetchAll();$un=array_column($uc,'name');foreach(['email_verified_at'=>'TEXT','email_verification_token_hash'=>'TEXT','email_verification_expires_at'=>'TEXT'] as $n=>$t)if(!in_array($n,$un,true))self::$pdo->exec("ALTER TABLE users ADD COLUMN $n $t");self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_verification_hash ON users(email_verification_token_hash)");
        $eq=self::$pdo->query("PRAGMA table_info(email_queue)")->fetchAll();$en=array_column($eq,'name');if(!in_array('action_url',$en,true))self::$pdo->exec("ALTER TABLE email_queue ADD COLUMN action_url TEXT");if(!in_array('html_body',$en,true))self::$pdo->exec("ALTER TABLE email_queue ADD COLUMN html_body TEXT");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS email_messages(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL,customer_id INTEGER,document_id INTEGER,direction TEXT NOT NULL DEFAULT 'inbound',from_email TEXT,to_email TEXT,subject TEXT,body TEXT,html_body TEXT,message_id TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,FOREIGN KEY(document_id) REFERENCES documents(id) ON DELETE SET NULL)"); self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_email_messages_workspace ON email_messages(workspace_id,created_at)");
        $ri=self::$pdo->query("PRAGMA table_info(received_invoices)")->fetchAll();$rin=array_column($ri,'name');foreach(['ocr_status'=>'TEXT DEFAULT \"pending\"','source_type'=>'TEXT DEFAULT \"manual\"','source_email_message_id'=>'TEXT'] as $n=>$t)if(!in_array($n,$rin,true))self::$pdo->exec("ALTER TABLE received_invoices ADD COLUMN $n $t");self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_received_invoices_workspace ON received_invoices(workspace_id,created_at)");self::$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_received_invoices_email_source ON received_invoices(workspace_id,source_email_message_id) WHERE source_email_message_id IS NOT NULL");
        $eq=self::$pdo->query("PRAGMA table_info(email_queue)")->fetchAll();$en=array_column($eq,'name');if(!in_array('reply_to',$en,true))self::$pdo->exec("ALTER TABLE email_queue ADD COLUMN reply_to TEXT");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS gopay_accounts(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL UNIQUE,goid_encrypted TEXT NOT NULL,client_id_encrypted TEXT NOT NULL,client_secret_encrypted TEXT NOT NULL,active INTEGER NOT NULL DEFAULT 1,created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");
        $tc=self::$pdo->query("PRAGMA table_info(tax_profiles)")->fetchAll();$tn=array_column($tc,'name');if(!in_array('default_vat_rate',$tn,true))self::$pdo->exec("ALTER TABLE tax_profiles ADD COLUMN default_vat_rate REAL DEFAULT 21");
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
        $ss=self::$pdo->query("PRAGMA table_info(saas_settings)")->fetchAll();$ssn=array_column($ss,'name');if(!in_array('last_automatic_backup_at',$ssn,true))self::$pdo->exec("ALTER TABLE saas_settings ADD COLUMN last_automatic_backup_at TEXT");
        $tc=self::$pdo->query("PRAGMA table_info(tax_profiles)")->fetchAll();$tn=array_column($tc,'name');if(!in_array('default_vat_rate',$tn,true))self::$pdo->exec("ALTER TABLE tax_profiles ADD COLUMN default_vat_rate REAL DEFAULT 21");
        $dc=self::$pdo->query("PRAGMA table_info(documents)")->fetchAll();$dn=array_column($dc,'name');
        foreach(['currency'=>"TEXT DEFAULT 'CZK'",'cnb_rate'=>'REAL DEFAULT 1','tax_regime'=>"TEXT DEFAULT 'standard'",'language'=>"TEXT DEFAULT 'cs'",'advance_applied'=>'REAL DEFAULT 0'] as $n=>$t) if(!in_array($n,$dn,true)) self::$pdo->exec("ALTER TABLE documents ADD COLUMN $n $t");
        $ds=self::$pdo->query("PRAGMA table_info(document_series)")->fetchAll();$dsn=array_column($ds,'name');
        foreach(['series_year'=>'INTEGER','year_prefix'=>"TEXT DEFAULT ''"] as $n=>$t) if(!in_array($n,$dsn,true)) self::$pdo->exec("ALTER TABLE document_series ADD COLUMN $n $t");
        self::$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_document_series_year ON document_series(workspace_id,doc_type,series_year)");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS currency_rates(id INTEGER PRIMARY KEY AUTOINCREMENT,currency TEXT NOT NULL,rate_date TEXT NOT NULL,rate REAL NOT NULL,source TEXT DEFAULT 'CNB',UNIQUE(currency,rate_date))");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS received_invoices(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL,supplier TEXT,ico TEXT,dic TEXT,document_number TEXT,variable_symbol TEXT,issue_date TEXT,due_date TEXT,currency TEXT DEFAULT 'CZK',cnb_rate REAL DEFAULT 1,amount_without_vat REAL DEFAULT 0,vat_amount REAL DEFAULT 0,total_amount REAL DEFAULT 0,payment_status TEXT DEFAULT 'unpaid',paid_amount REAL DEFAULT 0,attachment_path TEXT,ocr_json TEXT,note TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP,updated_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");
        self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_received_invoices_workspace_due ON received_invoices(workspace_id,due_date,payment_status)");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS accountant_shares(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL,user_id INTEGER,token_hash TEXT UNIQUE,expires_at TEXT,active INTEGER DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL)");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS comgate_accounts(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER UNIQUE,merchant TEXT,secret_encrypted TEXT,active INTEGER DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP,updated_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS document_chain(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL,from_document_id INTEGER NOT NULL,to_document_id INTEGER NOT NULL,relation TEXT NOT NULL,created_at TEXT DEFAULT CURRENT_TIMESTAMP,UNIQUE(from_document_id,to_document_id,relation),FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");
        $ws=self::$pdo->query("PRAGMA table_info(workspaces)")->fetchAll();$wn=array_column($ws,'name');if(!in_array('mail_display_name',$wn,true))self::$pdo->exec("ALTER TABLE workspaces ADD COLUMN mail_display_name TEXT");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS email_mailboxes(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER,localpart TEXT NOT NULL UNIQUE,display_name TEXT,password_hash TEXT,active INTEGER NOT NULL DEFAULT 1,is_system INTEGER NOT NULL DEFAULT 0,created_at TEXT DEFAULT CURRENT_TIMESTAMP,updated_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");
        self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_email_mailboxes_workspace ON email_mailboxes(workspace_id,active)");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS mailbox_messages(id INTEGER PRIMARY KEY AUTOINCREMENT,mailbox_id INTEGER NOT NULL,direction TEXT NOT NULL DEFAULT 'inbound',from_email TEXT,to_email TEXT,cc TEXT,bcc TEXT,subject TEXT,body TEXT,html_body TEXT,message_id TEXT,in_reply_to TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(mailbox_id) REFERENCES email_mailboxes(id) ON DELETE CASCADE)");
        self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_mailbox_messages ON mailbox_messages(mailbox_id,created_at)");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS mailbox_attachments(id INTEGER PRIMARY KEY AUTOINCREMENT,message_id INTEGER NOT NULL,filename TEXT NOT NULL,mime TEXT,size INTEGER NOT NULL DEFAULT 0,path TEXT NOT NULL,created_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(message_id) REFERENCES mailbox_messages(id) ON DELETE CASCADE)");
        self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_mailbox_attachments_message ON mailbox_attachments(message_id)");
        $seed=self::$pdo->query("SELECT id,email_localpart,name,mail_enabled,mail_display_name FROM workspaces WHERE email_localpart IS NOT NULL AND trim(email_localpart)!=''")->fetchAll();$ins=self::$pdo->prepare("INSERT OR IGNORE INTO email_mailboxes(workspace_id,localpart,display_name,active,is_system) VALUES(?,?,?,?,0)");foreach($seed as $row){$ins->execute([(int)$row['id'],strtolower(trim((string)$row['email_localpart'])),trim((string)($row['mail_display_name']?:$row['name'])),!empty($row['mail_enabled'])?1:0]);}
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS contract_templates(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER,slug TEXT,name TEXT NOT NULL,body TEXT NOT NULL,is_system INTEGER NOT NULL DEFAULT 0,created_at TEXT DEFAULT CURRENT_TIMESTAMP,updated_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE)");
        self::$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_contract_templates_system_slug ON contract_templates(slug) WHERE is_system=1");
        self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_contract_templates_workspace ON contract_templates(workspace_id)");
        self::$pdo->exec("CREATE TABLE IF NOT EXISTS contracts(id INTEGER PRIMARY KEY AUTOINCREMENT,workspace_id INTEGER NOT NULL,customer_id INTEGER,template_id INTEGER,name TEXT NOT NULL,body TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'final',file_id INTEGER,created_by INTEGER,created_at TEXT DEFAULT CURRENT_TIMESTAMP,updated_at TEXT DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,FOREIGN KEY(template_id) REFERENCES contract_templates(id) ON DELETE SET NULL,FOREIGN KEY(file_id) REFERENCES documents_files(id) ON DELETE SET NULL)");
        self::$pdo->exec("CREATE INDEX IF NOT EXISTS idx_contracts_workspace ON contracts(workspace_id,created_at)");
        $contractSeeds=[
            'kupni-smlouva'=>['Kupní smlouva','KUPNÍ SMLOUVA
uzavřená podle § 2079 a násl. zákona č. 89/2012 Sb., občanský zákoník, v platném znění (dále jen „občanský zákoník")

Prodávající: {{dodavatel_nazev}}, se sídlem {{dodavatel_adresa}}, IČO: {{dodavatel_ico}}{{dodavatel_dic_radek}}
(dále jen „prodávající")

Kupující: {{zakaznik_nazev}}, {{zakaznik_adresa}}{{zakaznik_ico_radek}}
(dále jen „kupující")

Článek I. – Předmět smlouvy
1. Prodávající se touto smlouvou zavazuje odevzdat kupujícímu následující zboží/věc: {{predmet}}, a umožnit mu nabytí vlastnického práva k němu.
2. Kupující se zavazuje předmět koupě převzít a zaplatit prodávajícímu sjednanou kupní cenu.

Článek II. – Kupní cena a platební podmínky
1. Kupní cena byla stranami sjednána ve výši {{cena}} Kč (slovy dle aktuálního kurzu), a to včetně DPH, je-li prodávající plátcem DPH.
2. Kupní cena je splatná na základě daňového dokladu (faktury) vystaveného prodávajícím, nedohodnou-li se strany jinak.

Článek III. – Předání a přechod vlastnického práva
1. Místem předání předmětu koupě je {{misto}}, nedohodnou-li se strany jinak.
2. Vlastnické právo k předmětu koupě přechází na kupujícího okamžikem jeho převzetí, případně úplným zaplacením kupní ceny, bude-li sjednána výhrada vlastnického práva.
3. Nebezpečí škody na věci přechází na kupujícího okamžikem převzetí předmětu koupě.

Článek IV. – Práva z vadného plnění
Práva a povinnosti stran týkající se práv z vadného plnění se řídí příslušnými ustanoveními občanského zákoníku.

Článek V. – Závěrečná ustanovení
1. Tato smlouva nabývá platnosti a účinnosti dnem jejího podpisu oběma stranami.
2. Smlouvu lze měnit pouze písemnými dodatky podepsanými oběma stranami.
3. Právní vztahy touto smlouvou výslovně neupravené se řídí občanským zákoníkem a dalšími obecně závaznými právními předpisy České republiky.
4. Tento vzor je obecným návrhem smluvního textu. Doporučujeme jej před použitím v konkrétním případě nechat zkontrolovat advokátem, zejména u vyšších částek nebo nestandardních podmínek.

V {{misto}} dne {{datum}}


_______________________________          _______________________________
            Prodávající                                Kupující'],
            'smlouva-o-dilo'=>['Smlouva o dílo','SMLOUVA O DÍLO
uzavřená podle § 2586 a násl. zákona č. 89/2012 Sb., občanský zákoník, v platném znění (dále jen „občanský zákoník")

Zhotovitel: {{dodavatel_nazev}}, se sídlem {{dodavatel_adresa}}, IČO: {{dodavatel_ico}}{{dodavatel_dic_radek}}
(dále jen „zhotovitel")

Objednatel: {{zakaznik_nazev}}, {{zakaznik_adresa}}{{zakaznik_ico_radek}}
(dále jen „objednatel")

Článek I. – Předmět smlouvy
1. Zhotovitel se zavazuje na vlastní náklady a nebezpečí provést pro objednatele dílo: {{predmet}}.
2. Objednatel se zavazuje dílo řádně dokončené a bez vad převzít a zaplatit zhotoviteli sjednanou cenu.

Článek II. – Cena díla
1. Cena za dílo byla stranami sjednána ve výši {{cena}} Kč, a to včetně DPH, je-li zhotovitel plátcem DPH.
2. Cena je splatná na základě daňového dokladu vystaveného zhotovitelem po dokončení a předání díla, nedohodnou-li se strany na zálohách nebo dílčím fakturačním plánu.

Článek III. – Místo a termín plnění
1. Místem provedení díla je {{misto}}, nedohodnou-li se strany jinak.
2. Dílo bude provedeno v termínu dohodnutém mezi stranami počínaje dnem {{datum}}.

Článek IV. – Předání díla a odpovědnost za vady
1. Dílo je provedeno, je-li dokončeno a objednatelem převzato. O předání a převzetí díla sepíší strany předávací protokol, pokud si to povaha díla žádá.
2. Práva a povinnosti z vadného plnění se řídí příslušnými ustanoveními občanského zákoníku.

Článek V. – Závěrečná ustanovení
1. Tato smlouva nabývá platnosti a účinnosti dnem jejího podpisu oběma stranami.
2. Smlouvu lze měnit pouze písemnými dodatky podepsanými oběma stranami.
3. Právní vztahy touto smlouvou výslovně neupravené se řídí občanským zákoníkem a dalšími obecně závaznými právními předpisy České republiky.
4. Tento vzor je obecným návrhem smluvního textu. Doporučujeme jej před použitím v konkrétním případě nechat zkontrolovat advokátem, zejména u rozsáhlejších zakázek.

V {{misto}} dne {{datum}}


_______________________________          _______________________________
             Zhotovitel                               Objednatel'],
            'smlouva-o-sluzbach'=>['Smlouva o poskytování služeb','SMLOUVA O POSKYTOVÁNÍ SLUŽEB
uzavřená podle § 1746 odst. 2 zákona č. 89/2012 Sb., občanský zákoník, v platném znění (dále jen „občanský zákoník")

Poskytovatel: {{dodavatel_nazev}}, se sídlem {{dodavatel_adresa}}, IČO: {{dodavatel_ico}}{{dodavatel_dic_radek}}
(dále jen „poskytovatel")

Objednatel: {{zakaznik_nazev}}, {{zakaznik_adresa}}{{zakaznik_ico_radek}}
(dále jen „objednatel")

Článek I. – Předmět smlouvy
1. Poskytovatel se zavazuje pro objednatele zajišťovat tyto služby: {{predmet}}.
2. Objednatel se zavazuje za řádně poskytnuté služby zaplatit sjednanou cenu.

Článek II. – Cena a platební podmínky
1. Cena za služby byla sjednána ve výši {{cena}} Kč, a to včetně DPH, je-li poskytovatel plátcem DPH, nedohodnou-li se strany na jiném způsobu určení ceny (např. hodinová sazba, paušál).
2. Cena je splatná na základě daňových dokladů vystavovaných poskytovatelem, obvykle měsíčně nebo po dokončení jednotlivých etap plnění, nedohodnou-li se strany jinak.

Článek III. – Místo a doba plnění
1. Služby budou poskytovány v {{misto}}, případně distančně, nedohodnou-li se strany jinak.
2. Smluvní vztah vzniká dnem {{datum}} a trvá do splnění sjednaného rozsahu služeb, případně na dobu neurčitou s výpovědní dobou 1 měsíc, nedohodnou-li se strany jinak.

Článek IV. – Mlčenlivost
Obě strany se zavazují zachovávat mlčenlivost o důvěrných informacích, které si v souvislosti s plněním této smlouvy vzájemně poskytnou.

Článek V. – Závěrečná ustanovení
1. Tato smlouva nabývá platnosti a účinnosti dnem jejího podpisu oběma stranami.
2. Smlouvu lze měnit pouze písemnými dodatky podepsanými oběma stranami.
3. Právní vztahy touto smlouvou výslovně neupravené se řídí občanským zákoníkem a dalšími obecně závaznými právními předpisy České republiky.
4. Tento vzor je obecným návrhem smluvního textu. Doporučujeme jej před použitím v konkrétním případě nechat zkontrolovat advokátem, zejména u dlouhodobé spolupráce.

V {{misto}} dne {{datum}}


_______________________________          _______________________________
            Poskytovatel                               Objednatel'],
            'smlouva-o-mlcenlivosti'=>['Smlouva o mlčenlivosti (NDA)','SMLOUVA O MLČENLIVOSTI (NDA)
uzavřená podle § 1746 odst. 2 zákona č. 89/2012 Sb., občanský zákoník, v platném znění (dále jen „občanský zákoník")

Strana 1: {{dodavatel_nazev}}, se sídlem {{dodavatel_adresa}}, IČO: {{dodavatel_ico}}{{dodavatel_dic_radek}}

Strana 2: {{zakaznik_nazev}}, {{zakaznik_adresa}}{{zakaznik_ico_radek}}

(společně dále jen „strany")

Článek I. – Účel smlouvy
Strany spolu hodlají jednat o/spolupracovat na: {{predmet}}, a v této souvislosti si mohou vzájemně poskytnout důvěrné informace, které si přejí chránit touto smlouvou.

Článek II. – Důvěrné informace
1. Důvěrnou informací se rozumí jakákoliv informace obchodní, technické, finanční nebo jiné povahy, kterou jedna strana sdělí druhé v souvislosti s účelem dle čl. I, ať už ústně, písemně nebo elektronicky, a která je označena jako důvěrná nebo z povahy věci jako důvěrná vyplývá.
2. Za důvěrné informace se nepovažují informace, které jsou veřejně dostupné, nebo které strana prokazatelně znala před jejich sdělením druhou stranou.

Článek III. – Povinnost mlčenlivosti
1. Strany se zavazují nakládat s důvěrnými informacemi jako s obchodním tajemstvím, nezpřístupnit je třetím osobám a nepoužít je k jinému účelu, než je účel dle čl. I.
2. Tato povinnost trvá po dobu trvání jednání/spolupráce a dále {{cena}} měsíců/let po jejím ukončení (doplňte dle dohody).

Článek IV. – Sankce
V případě porušení povinnosti mlčenlivosti má poškozená strana právo na náhradu škody v plné výši, případně na smluvní pokutu, pokud ji strany samostatně sjednají.

Článek V. – Závěrečná ustanovení
1. Tato smlouva nabývá platnosti a účinnosti dnem jejího podpisu oběma stranami.
2. Smlouvu lze měnit pouze písemnými dodatky podepsanými oběma stranami.
3. Právní vztahy touto smlouvou výslovně neupravené se řídí občanským zákoníkem a dalšími obecně závaznými právními předpisy České republiky.
4. Tento vzor je obecným návrhem smluvního textu. Doporučujeme jej před použitím v konkrétním případě nechat zkontrolovat advokátem.

V {{misto}} dne {{datum}}


_______________________________          _______________________________
               Strana 1                                Strana 2'],
        ];
        $insCt=self::$pdo->prepare("INSERT OR IGNORE INTO contract_templates(workspace_id,slug,name,body,is_system) VALUES(NULL,?,?,?,1)");
        foreach($contractSeeds as $slug=>[$name,$body]) $insCt->execute([$slug,$name,$body]);
    }
}
