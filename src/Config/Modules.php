<?php
namespace App\Config;

final class Modules {
    public static function all(): array {
        return [
            'reports'=>['name'=>'Reporty a Byznio Intelligence','description'=>'Týdenní a měsíční přehledy, cash-flow, pohledávky a doporučení.','icon'=>'spark'],
            'crm'=>['name'=>'CRM','description'=>'Zákazníci, kontakty a historie komunikace.','icon'=>'users'],
            'mail'=>['name'=>'E-mail','description'=>'Přijaté a odeslané e-maily firmy.','icon'=>'mail'],
            'communication'=>['name'=>'Komunikace','description'=>'Interní komunikace a poznámky u zákazníků.','icon'=>'mail'],
            'invoicing'=>['name'=>'Doklady a fakturace','description'=>'Faktury, nabídky, objednávky, zálohy a dobropisy.','icon'=>'file'],
            'jobs'=>['name'=>'Zakázky','description'=>'Zakázky, termíny, rozpočty, práce a materiál.','icon'=>'briefcase'],
            'expenses'=>['name'=>'Výdaje','description'=>'Náklady, účtenky a přijaté faktury.','icon'=>'receipt'],
            'received_invoices'=>['name'=>'Přijaté faktury','description'=>'Evidence závazků a přijatých faktur odděleně od výdajů.','icon'=>'receipt'],
            'inventory'=>['name'=>'Sklad','description'=>'Položky, zásoby, minima a pohyby skladu.','icon'=>'box'],
            'bank'=>['name'=>'Banka','description'=>'Bankovní účty, pohyby a párování plateb.','icon'=>'bank'],
            'calendar'=>['name'=>'Kalendář','description'=>'Termíny, schůzky a události.','icon'=>'calendar'],
            'recurring'=>['name'=>'Opakované doklady','description'=>'Pravidelně vytvářené doklady a jejich automatizace.','icon'=>'repeat'],
            'tax'=>['name'=>'Daně','description'=>'Daňové přehledy a výpočty.','icon'=>'percent'],
            'tasks'=>['name'=>'Úkoly','description'=>'Úkoly, priority a termíny.','icon'=>'check'],
            'contracts'=>['name'=>'Smlouvy','description'=>'Smluvní šablony a dokumenty.','icon'=>'contract'],
            'documents'=>['name'=>'Dokumenty','description'=>'Soubory připojené k firmě a dokladům.','icon'=>'folder'],
            'reminders'=>['name'=>'Upomínky','description'=>'Přehled a ruční spuštění upomínek.','icon'=>'bell'],
            'automation'=>['name'=>'Automatizace','description'=>'Pravidla, která za vás spouštějí opakované akce.','icon'=>'workflow'],
            'export'=>['name'=>'Exporty','description'=>'Export dokladů a účetních dat.','icon'=>'download'],
        ];
    }

    public static function recommendedForBusinessType(string $businessType): array {
        $bt=BusinessTypes::get($businessType);
        $map=[
            'Zakázky'=>'jobs','Fakturace'=>'invoicing','Kalendář'=>'calendar','Banka'=>'bank','CRM'=>'crm','Sklad'=>'inventory','Dodavatelé'=>'received_invoices'
        ];
        $out=['reports','mail','tasks','documents'];
        foreach(($bt['modules']??[]) as $label){if(isset($map[$label]))$out[]=$map[$label];}
        return array_values(array_unique($out));
    }

    public static function normalize(array $modules): array {
        $all=array_keys(self::all());
        $set=[]; foreach($modules as $m){$m=(string)$m;if(in_array($m,$all,true))$set[$m]=true;}
        // These are core navigation areas and stay available in every firm.
        foreach(['reports','invoicing','mail','documents'] as $core)$set[$core]=true;
        return array_values(array_keys($set));
    }

    public static function fromWorkspace(array $workspace): array {
        $raw=trim((string)($workspace['modules_json']??''));
        if($raw!==''){
            $decoded=json_decode($raw,true);
            if(is_array($decoded))return self::normalize($decoded);
        }
        return self::recommendedForBusinessType((string)($workspace['business_type']??'services'));
    }
}
