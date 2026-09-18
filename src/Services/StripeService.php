<?php
namespace App\Services;
use App\Core\{Database,Env};
final class StripeService {
    private static function request(string $method,string $path,array $data=[]):array{
        $key=Env::get('STRIPE_SECRET_KEY'); if(!$key) throw new \RuntimeException('STRIPE_SECRET_KEY není nastaven.');
        $ch=curl_init('https://api.stripe.com/v1'.$path); $headers=['Authorization: Bearer '.$key,'Content-Type: application/x-www-form-urlencoded'];
        $opts=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>$headers,CURLOPT_CUSTOMREQUEST=>$method]; if($data){$opts[CURLOPT_POSTFIELDS]=http_build_query($data);} curl_setopt_array($ch,$opts); $body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
        $json=json_decode((string)$body,true); if($body===false||$code>=400) throw new \RuntimeException('Stripe HTTP '.$code.': '.($err?:($json['error']['message']??$body))); return is_array($json)?$json:[];
    }
    public static function checkout(int $workspaceId,string $price,string $success,string $cancel):?string {
        if(!Env::get('STRIPE_SECRET_KEY'))return null;
        [$interval,$raw]=array_pad(explode(':',$price,2),2,'0'); if(!in_array($interval,['month','year'],true))throw new \RuntimeException('Neplatné období předplatného.');$amount=(int)round((float)$raw*100);if($amount<1)throw new \RuntimeException('Cena musí být kladná.');
        $pdo=Database::pdo();$st=$pdo->prepare('SELECT stripe_customer_id FROM subscriptions WHERE workspace_id=?');$st->execute([$workspaceId]);$customer=$st->fetchColumn();
        $d=['mode'=>'subscription','line_items[0][price_data][currency]'=>'czk','line_items[0][price_data][unit_amount]'=>$amount,'line_items[0][price_data][recurring][interval]'=>$interval,'line_items[0][price_data][product_data][name]'=>'Podnikatel SaaS','line_items[0][quantity]'=>1,'success_url'=>$success,'cancel_url'=>$cancel,'client_reference_id'=>$workspaceId,'metadata[workspace_id]'=>$workspaceId,'metadata[billing_interval]'=>$interval,'subscription_data[metadata][workspace_id]'=>$workspaceId,'subscription_data[metadata][billing_interval]'=>$interval];if($customer)$d['customer']=$customer;$x=self::request('POST','/checkout/sessions',$d);return $x['url']??null;
    }
    public static function webhook(string $payload,string $signature):void {
        $secret=Env::get('STRIPE_WEBHOOK_SECRET'); if(!$secret) throw new \RuntimeException('STRIPE_WEBHOOK_SECRET není nastaven.'); if(!self::verifySignature($payload,$signature,$secret)) throw new \RuntimeException('Neplatný Stripe podpis.');
        $e=json_decode($payload,true); if(!is_array($e)||empty($e['id']))throw new \RuntimeException('Neplatný webhook.'); $pdo=Database::pdo(); $q=$pdo->prepare('INSERT OR IGNORE INTO webhook_events(provider,event_id,payload_json,processed_at) VALUES(?,?,?,CURRENT_TIMESTAMP)');$q->execute(['stripe',$e['id'],$payload]);if($q->rowCount()===0)return;
        $obj=$e['data']['object']??[];$wid=(int)($obj['metadata']['workspace_id']??$obj['client_reference_id']??0); if(!$wid)return;
        if(in_array($e['type'],['checkout.session.completed','customer.subscription.updated','customer.subscription.deleted'],true)){ $status=$e['type']==='customer.subscription.deleted'?'canceled':($obj['status']??'active');$sid=$obj['id']??($obj['subscription']??null);$customer=$obj['customer']??null;$interval=$obj['metadata']['billing_interval']??'month';$end=isset($obj['current_period_end'])?date('Y-m-d H:i:s',(int)$obj['current_period_end']):null;$pdo->prepare('UPDATE subscriptions SET status=?,plan="all",billing_interval=?,stripe_customer_id=COALESCE(?,stripe_customer_id),stripe_subscription_id=COALESCE(?,stripe_subscription_id),current_period_end=?,updated_at=CURRENT_TIMESTAMP WHERE workspace_id=?')->execute([$status,$interval,$customer,$sid,$end,$wid]);$pdo->prepare('UPDATE workspaces SET plan="all",status=? WHERE id=?')->execute([$e['type']==='customer.subscription.deleted'?'trial':'active',$wid]);}
    }
    private static function subscriptionPlan(string $subscriptionId):string {
        try{$s=self::request('GET','/subscriptions/'.rawurlencode($subscriptionId));$m=$s['metadata']['plan']??'';return in_array($m,['start','business','pro','enterprise'],true)?$m:'';}catch(\Throwable $e){return ''; }
    }
    private static function verifySignature(string $payload,string $header,string $secret):bool { $parts=[];foreach(explode(',',$header) as $p){[$k,$v]=array_pad(explode('=',$p,2),2,'');$parts[$k][]=$v;} $ts=(int)($parts['t'][0]??0);$sig=$parts['v1']??[];if(!$ts||abs(time()-$ts)>300)return false;$expected=hash_hmac('sha256',$ts.'.'.$payload,$secret);foreach($sig as $s)if(hash_equals($expected,$s))return true;return false; }
}
