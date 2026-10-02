<?php
namespace App\Services;
final class EmailTemplateService
{
    public static function fragment(string $type,array $data): string
    {
        $company=self::e($data['company']??'Byznio');
        $title=self::e($data['title']??'Zpráva z Byznia');
        $introHtml=nl2br(self::e($data['intro']??''));
        $action=$data['action_url']??'';
        $actionText=self::e($data['action_text']??'Otevřít doklad');
        $details=$data['details']??[];
        $rows='';
        foreach($details as $k=>$v){
            $rows.='<tr><td style="padding:10px 0;color:#6b7890;font-size:14px">'.self::e($k).'</td><td style="padding:10px 0;text-align:right;font-weight:700;color:#10213f;font-size:14px">'.self::e($v).'</td></tr>';
        }
        $badge=$type==='reminder'?'UPOMÍNKA':($type==='payment'?'UHRAZENO':($type==='verification'?'AKTIVACE ÚČTU':($type==='invoice'?'FAKTURA':'')));
        return '<div style="font-family:Arial,Helvetica,sans-serif;color:#17233c">'
            .($badge?'<div style="font-size:11px;letter-spacing:1.5px;color:#5673a3;font-weight:800;margin-bottom:7px">'.$badge.'</div>':'')
            .'<h1 style="font-size:26px;line-height:1.2;margin:0 0 18px;color:#10213f">'.$title.'</h1>'
            .'<div style="font-size:15px;line-height:1.7">'.$introHtml.'</div>'
            .($rows?'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #e8edf4;border-bottom:1px solid #e8edf4;margin:22px 0">'.$rows.'</table>':'')
            .($action?'<p style="margin:24px 0 4px"><a href="'.self::e($action).'" style="display:inline-block;background:#2867d8;color:#fff;text-decoration:none;padding:13px 20px;border-radius:11px;font-weight:700">'.$actionText.'</a></p>':'')
            .'<p style="font-size:13px;line-height:1.6;color:#7a8799;margin:26px 0 0;padding-top:18px;border-top:1px solid #edf1f6">S pozdravem,<br><strong>'.$company.'</strong></p>'
            .'</div>';
    }

    public static function render(string $type,array $data): array
    {
        $company=self::e($data['company']??'Byznio');
        $logo=self::e($data['logo']??'');
        $fragment=self::fragment($type,$data);
        $html='<!doctype html><html lang="cs"><body style="margin:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#17233c"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb"><tr><td align="center" style="padding:28px 12px"><table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden"><tr><td style="padding:24px 28px;background:#0b1f41">'.($logo?'<img src="'.$logo.'" alt="'.$company.'" style="max-width:170px;max-height:54px">':'<div style="font-size:22px;font-weight:800;color:#fff">'.$company.'</div>').'</td></tr><tr><td style="padding:30px 28px">'.$fragment.'</td></tr></table></td></tr></table></body></html>';
        $intro=trim((string)($data['intro']??''));
        $text=$intro."\n\n";
        foreach(($data['details']??[]) as $k=>$v) $text.=(string)$k.': '.(string)$v."\n";
        if(!empty($data['action_url'])) $text.="\n".(string)($data['action_text']??'Otevřít doklad').': '.(string)$data['action_url']."\n";
        $text.="\nS pozdravem,\n".($data['company']??'Byznio');
        return ['html'=>$html,'text'=>trim($text)];
    }
    private static function e($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
}
