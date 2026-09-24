<?php
namespace App\Core;
use App\Controllers\WebController;
use App\Controllers\ApiController;
use App\Core\Auth;
final class Kernel {
    public static function handle(): void {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data: blob:;");
        $r=new Router(); $w=new WebController(Database::pdo());
        $r->get('/health',function(){header('Content-Type: application/json');echo json_encode(['ok'=>true,'app'=>\App\Core\Env::get('APP_NAME','Byznio'),'time'=>date(DATE_ATOM)]);exit;});
        $r->get('/mail/logo/{id}',function($id){ $wid=(int)$id; $token=(string)($_GET['token']??''); $expected=hash_hmac('sha256',(string)$wid,(string)\App\Core\Env::get('APP_KEY','')); if($wid<1||$token===''||!hash_equals($expected,$token)){http_response_code(404);exit;} $pdo=\App\Core\Database::pdo(); $s=$pdo->prepare('SELECT logo_path FROM workspaces WHERE id=?'); $s->execute([$wid]); $path=$s->fetchColumn(); $file=$path?dirname(__DIR__,2).'/'.ltrim((string)$path,'/'):''; if(!$file||!is_file($file)){http_response_code(404);exit;} $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file); if(!in_array($mime,['image/png','image/jpeg','image/svg+xml'],true)){http_response_code(404);exit;} header('Content-Type: '.$mime); header('Cache-Control: public, max-age=86400'); readfile($file); exit; });
        $r->get('/',fn()=> Auth::check() ? $w->dashboard() : $w->landing());
        $r->get('/dashboard',fn()=> $w->dashboard());
        $r->get('/crm',fn()=> $w->customers());
        $r->get('/login',fn()=> $w->login()); $r->post('/login',fn()=> $w->loginPost());
        $r->get('/register',fn()=> $w->register()); $r->post('/register',fn()=> $w->registerPost());
        $r->post('/logout',fn()=> $w->logout());
        $r->get('/uvod',fn()=> $w->onboarding()); $r->post('/uvod/ares',fn()=> $w->onboardingAres()); $r->post('/uvod/company',fn()=> $w->onboardingCompany()); $r->post('/uvod/complete',fn()=> $w->onboardingComplete()); $r->post('/uvod/skip',fn()=> $w->onboardingSkip());
        $r->get('/communication',fn()=> $w->communication()); $r->post('/customers/ares',fn()=> $w->customerAres()); $r->post('/webhooks/postmark/inbound/{secret}',fn($secret)=>$w->postmarkInbound((string)$secret)); $r->get('/customers',fn()=> $w->customers()); $r->get('/customers/new',fn()=> $w->customerForm()); $r->post('/customers/save',fn()=> $w->customerSave());
        $r->get('/customers/{id}',fn($id)=>$w->customerDetail((int)$id)); $r->post('/customers/{id}/communication',fn($id)=>$w->communicationSave((int)$id)); $r->get('/customers/{id}/edit',fn($id)=>$w->customerEdit((int)$id)); $r->post('/customers/{id}/update',fn($id)=>$w->customerUpdate((int)$id));
        $r->get('/d/{token}',fn($token)=>$w->publicDocument($token)); $r->get('/d/{token}/qr',fn($token)=>$w->publicQr($token)); $r->post('/d/{token}/respond',fn($token)=>$w->publicOfferRespond($token)); $r->post('/d/{token}/pay',fn($token)=>$w->publicPay($token));
        $r->get('/documents',fn()=> $w->documents()); $r->get('/documents/new',fn()=> $w->documentForm()); $r->get('/documents/create',fn()=> $w->documentForm()); $r->get('/invoice/new',fn()=> $w->documentForm()); $r->post('/documents/save',fn()=> $w->documentSave()); $r->get('/documents/{id}/pdf',fn($id)=>$w->pdf((int)$id)); $r->get('/documents/{id}/qr',fn($id)=>$w->qr((int)$id)); $r->post('/documents/{id}/send-email',fn($id)=>$w->sendDocumentEmail((int)$id));
        $r->post('/documents/{id}/pay',fn($id)=>$w->markPaid((int)$id)); $r->post('/documents/{id}/cancel',fn($id)=>$w->documentCancel((int)$id)); $r->post('/documents/{id}/offer-status',fn($id)=>$w->offerStatus((int)$id)); $r->post('/documents/{id}/convert-job',fn($id)=>$w->convertToJob((int)$id));
        $r->get('/jobs',fn()=> $w->jobs()); $r->get('/jobs/new',fn()=> $w->jobForm()); $r->post('/jobs/save',fn()=> $w->jobSave()); $r->post('/jobs/hours',fn()=> $w->jobHoursSave());
        $r->get('/jobs/{id}',fn($id)=>$w->jobDetail((int)$id)); $r->get('/jobs/{id}/edit',fn($id)=>$w->jobEdit((int)$id)); $r->post('/jobs/{id}/update',fn($id)=>$w->jobUpdate((int)$id)); $r->post('/jobs/{id}/material',fn($id)=>$w->jobMaterialSave((int)$id)); $r->post('/jobs/{id}/invoice',fn($id)=>$w->jobInvoice((int)$id));
        $r->get('/expenses',fn()=> $w->expenses()); $r->post('/expenses/save',fn()=> $w->expenseSave());
        $r->get('/products',fn()=> $w->products()); $r->post('/products/save',fn()=> $w->productSave()); $r->post('/products/move',fn()=> $w->stockMove());
        $r->post('/products/inventory-count',fn()=> $w->inventoryCount()); $r->get('/products/{id}/edit',fn($id)=>$w->productEdit((int)$id)); $r->post('/products/{id}/update',fn($id)=>$w->productUpdate((int)$id));
        $r->get('/bank',fn()=> $w->bank()); $r->post('/bank/gopay/{id}',fn($id)=>$w->goPayLink((int)$id)); $r->get('/gopay/callback',fn()=> $w->goPayCallback()); $r->post('/gopay/webhook',fn()=> $w->goPayWebhook()); $r->post('/bank/import',fn()=> $w->bankImport()); $r->post('/bank/match/{id}',fn($id)=>$w->bankMatch((int)$id)); $r->get('/bank/accounts',fn()=> $w->bankSettings()); $r->post('/bank/accounts',fn()=> $w->bankConnect()); $r->post('/bank/accounts/{id}/sync',fn($id)=>$w->bankSync((int)$id));
        $r->post('/bank/saltedge/connect',fn()=> $w->bankSaltEdgeStart());
        $r->get('/bank/saltedge/callback',fn()=> $w->bankSaltEdgeCallback());
        $r->post('/bank/saltedge/{id}/sync',fn($id)=> $w->bankSaltEdgeSync((int)$id));
        $r->get('/calendar',fn()=> $w->calendar()); $r->post('/calendar/save',fn()=> $w->calendarSave());
        $r->get('/ai',fn()=> $w->ai()); $r->post('/ai/ask',fn()=> $w->aiAsk()); $r->post('/ai/confirm',fn()=> $w->aiConfirm()); $r->post('/ai/cancel',fn()=> $w->aiCancel());
        $r->get('/tasks',fn()=> $w->tasks()); $r->post('/tasks/save',fn()=> $w->taskSave()); $r->post('/tasks/{id}/done',fn($id)=>$w->taskDone((int)$id)); $r->get('/recurring',fn()=> $w->recurring()); $r->post('/recurring',fn()=> $w->recurringSave()); $r->get('/tax',fn()=> $w->tax()); $r->post('/tax',fn()=> $w->taxSave()); $r->get('/export',fn()=> $w->export()); $r->get('/gdpr/export',fn()=> $w->gdprExport()); $r->post('/gdpr/export',fn()=> $w->gdprExport()); $r->get('/documents/{id}/isdoc',fn($id)=>$w->isdoc((int)$id)); $r->get('/automation',fn()=> $w->automation()); $r->post('/automation/save',fn()=> $w->automationSave());
        $r->get('/settings',fn()=> $w->settings()); $r->post('/settings',fn()=> $w->settingsSave());
        $r->get('/documents/files',fn()=> $w->documentsFiles()); $r->post('/documents/files/upload',fn()=> $w->fileUpload()); $r->get('/documents/files/{id}',fn($id)=>$w->fileDownload((int)$id));
        $r->get('/settings/api-keys',fn()=> $w->apiKeys()); $r->post('/settings/api-keys',fn()=> $w->apiKeyCreateWeb()); $r->get('/reminders',fn()=> $w->reminders()); $r->post('/reminders/run',fn()=> $w->runReminders()); $r->get('/admin/plans',fn()=> $w->adminPlans()); $r->post('/admin/subscription/cancel',fn()=> $w->subscriptionCancel()); $r->get('/admin/billing-portal',fn()=> $w->billingPortal()); $r->get('/documents/{id}/qr',fn($id)=>$w->qr((int)$id));
        $r->get('/admin',fn()=> $w->admin()); $r->get('/admin/billing',fn()=> $w->adminBilling()); $r->post('/admin/billing/settings',fn()=> $w->saasBillingSave()); $r->post('/admin/billing/grant-free',fn()=> $w->grantFree()); $r->post('/admin/backup',fn()=> $w->backup()); $r->get('/admin/system',fn()=> $w->adminSystem()); $r->post('/admin/subscription',fn()=> $w->subscription()); $r->get('/admin/users',fn()=> $w->users()); $r->post('/admin/users',fn()=> $w->userSave()); $r->post('/admin/users/custom-role',fn()=> $w->customRoleSave());
        $r->get('/api/v1/customers',fn()=>ApiController::customers()); $r->post('/api/v1/customers',fn()=>ApiController::customerCreate()); $r->put('/api/v1/customers/{id}',fn($id)=>ApiController::customerUpdate((int)$id)); $r->delete('/api/v1/customers/{id}',fn($id)=>ApiController::customerDelete((int)$id));
        $r->get('/api/v1/documents',fn()=>ApiController::documents()); $r->post('/api/v1/documents',fn()=>ApiController::documentCreate()); $r->put('/api/v1/documents/{id}',fn($id)=>ApiController::documentUpdate((int)$id)); $r->delete('/api/v1/documents/{id}',fn($id)=>ApiController::documentDelete((int)$id));
        $r->get('/api/v1/jobs',fn()=>ApiController::jobs()); $r->get('/api/v1/bank/transactions',fn()=>ApiController::transactions());
        $r->get('/api/v1/products',fn()=>ApiController::products()); $r->post('/api/v1/ai/ask',fn()=>ApiController::aiAsk()); $r->post('/api/v1/keys',fn()=>ApiController::apiKeyCreate());
        $r->post('/billing/stripe/webhook',function(){ $payload=file_get_contents('php://input'); \App\Services\StripeService::webhook($payload,$_SERVER['HTTP_STRIPE_SIGNATURE']??''); http_response_code(200); echo 'ok'; });
        $r->dispatch($_SERVER['REQUEST_METHOD'],$_SERVER['REQUEST_URI']);
    }
}
