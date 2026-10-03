<?php
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/src/bootstrap.php';
use App\Core\Database;
use App\Core\Env;
use App\Services\MailerService;
use App\Services\ReportService;

$pdo=Database::pdo();
$now=new \DateTimeImmutable('now',new \DateTimeZone('Europe/Prague'));
$forceWeekly=in_array('--force-weekly',$argv,true);
$forceMonthly=in_array('--force-monthly',$argv,true);
$isMonday=$now->format('N')==='1';
$isFirst=$now->format('j')==='1';
if(!$isMonday && !$isFirst && !$forceWeekly && !$forceMonthly){echo "Report schedule: dnes není plánovaný report.\n";exit(0);}

$workspaces=$pdo->query('SELECT * FROM workspaces WHERE status NOT IN ("suspended","cancelled") AND (weekly_report_enabled=1 OR monthly_report_enabled=1)')->fetchAll();
$sent=0;$failed=0;
$reportText=function(array $r,string $label):string{
    $lines=[$label." za {$r['start']} až {$r['end']}.","Vystaveno: ".number_format($r['invoiceIssued'],0,',',' ' )." Kč"];
    if(in_array('bank',$r['modules']??[],true))$lines[]="Přijato: ".number_format($r['received'],0,',',' ' )." Kč";
    if(in_array('expenses',$r['modules']??[],true))$lines[]="Výdaje: ".number_format($r['expenses'],0,',',' ' )." Kč";
    if(in_array('bank',$r['modules']??[],true)||in_array('expenses',$r['modules']??[],true))$lines[]="Cash flow z období: ".($r['cashflow']>=0?'+':'').number_format($r['cashflow'],0,',',' ' )." Kč";
    $lines[]="Neuhrazené vystavené faktury: ".number_format($r['receivables'],0,',',' ' )." Kč";
    if(in_array('received_invoices',$r['modules']??[],true))$lines[]="Neuhrazené přijaté faktury: ".number_format($r['receivedInvoices'],0,',',' ' )." Kč";
    if(in_array('inventory',$r['modules']??[],true))$lines[]="Hodnota skladu: ".number_format($r['stock'],0,',',' ' )." Kč";
    return implode("\n",$lines);
};
foreach($workspaces as $w){
    $wid=(int)$w['id'];
    $recipients=$pdo->prepare('SELECT email,name FROM users WHERE workspace_id=? AND active=1 AND role IN ("owner","admin") ORDER BY CASE WHEN role="owner" THEN 0 ELSE 1 END,id LIMIT 5');
    $recipients->execute([$wid]);$toRows=$recipients->fetchAll();
    if(!$toRows)continue;
    $logoPath=!empty($w['logo_path'])?dirname(__DIR__).'/'.$w['logo_path']:null;
    $fromLocal=trim((string)($w['email_localpart']??''));
    $mailDomain=(string)Env::get('MAIL_DOMAIN','byznio.cz');
    $from=filter_var($fromLocal.'@'.$mailDomain,FILTER_VALIDATE_EMAIL)?$fromLocal.'@'.$mailDomain:Env::get('MAIL_FROM','info@byznio.cz');
    $name=trim((string)($w['mail_display_name']??$w['name']??'Byznio'));
    if(($isMonday||$forceWeekly) && !empty($w['weekly_report_enabled'])){
        $today=$now->format('Y-m-d');
        if($forceWeekly || (string)($w['last_weekly_report_at']??'')!==$today){
            [$start,$end]=ReportService::ranges('week');$r=ReportService::summarize($pdo,$wid,$start,$end);
            $body=$reportText($r,"Týdenní přehled Byznia");
            $html=ReportService::html($r,'Týdenní přehled · '.($w['name']??'Byznio'));
            foreach($toRows as $to){if(MailerService::sendReport((string)$to['email'],'Byznio · Týdenní přehled',$body,$html,$from,$name,rtrim((string)Env::get('APP_URL',''),'/').'/reports?period=week',$logoPath,$wid)){ $sent++; } else {$failed++;}}
            $pdo->prepare('UPDATE workspaces SET last_weekly_report_at=? WHERE id=?')->execute([$today,$wid]);
        }
    }
    if(($isFirst||$forceMonthly) && !empty($w['monthly_report_enabled'])){
        $today=$now->format('Y-m-d');
        if($forceMonthly || (string)($w['last_monthly_report_at']??'')!==$today){
            [$start,$end]=ReportService::ranges('month');$r=ReportService::summarize($pdo,$wid,$start,$end);
            $body=$reportText($r,"Měsíční přehled Byznia");
            $html=ReportService::html($r,'Měsíční přehled · '.($w['name']??'Byznio'));
            foreach($toRows as $to){if(MailerService::sendReport((string)$to['email'],'Byznio · Měsíční přehled',$body,$html,$from,$name,rtrim((string)Env::get('APP_URL',''),'/').'/reports?period=month',$logoPath,$wid)){ $sent++; } else {$failed++;}}
            $pdo->prepare('UPDATE workspaces SET last_monthly_report_at=? WHERE id=?')->execute([$today,$wid]);
        }
    }
}
echo "Reports sent: {$sent}; failed: {$failed}\n";
