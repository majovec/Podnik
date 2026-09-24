# Podnikatel SaaS – testovatelný finální build

Moderní český SaaS pro OSVČ a malé firmy: CRM, zakázky, fakturace, banky, párování plateb, výdaje/OCR, sklad, tým, kalendář, dokumenty, opakované faktury, upomínky, daně, API, AI a automatizace.

## Co je v buildu

- Multi-tenant workspace + izolace dat
- Registrace, přihlášení, role, vlastní permissions JSON, audit log
- Dashboard: příjmy, výdaje, cashflow, splatnosti, zakázky, sklad, úkoly, AI doporučení
- CRM: detail, editace, ARES, doklady, zakázky, výdaje a komunikační historie
- Zakázky: rozpočet, náklady práce, materiál, marže, zisk, editace, doklady a fakturace
- Fakturace: faktury, nabídky, objednávky, proforma, dobropisy, DPH, PDF, ISDOC, QR/SPAYD
- Automatické párování plateb: VS, částka, reference, částečné platby, přeplatky, ruční párování
- Banky: přímé Fio read-only API + multi-bank Open Banking přes Salt Edge; přihlašování probíhá u banky a bankovní hesla se neukládají
- Výdaje: upload a AI/OCR vytěžení dodavatele, data, částky, DPH a čísla dokladu
- Sklad: produkty, editace, příjem/výdej, minimální zásoba, materiál na zakázce, inventura
- Zaměstnanci/spolupracovníci: uživatelé, role, permissions_json
- Kalendář: zákazník, zakázka a uživatel
- Centrální dokumenty s tenant-scoped bezpečným stažením
- Opakované faktury + e-mailová fronta
- Upomínky: -3, 0, +3, +7 dní s deduplikací
- Daně: příjmy, výdaje, zisk, DPH, daň z příjmu, sociální, zdravotní, paušální režim – orientační výpočet s uvedením roku/pravidla
- Export CSV + ISDOC
- AI asistent: firemní kontext, návrhy a potvrzované akce; aktuálně bezpečně podporuje vytvoření zákazníka a faktury
- Automatizace: platby, upomínky, nízký sklad, překročení rozpočtu → úkoly/notifikace
- API + vytvoření prvního API klíče přes web; GET/POST/PUT/DELETE pro zákazníky a doklady
- Jednotné předplatné 300 Kč/měsíc a kontrola aktivního předplatného
- GoPay subscription checkout + automatické opakované platby a webhooky
- GoPay payment gateway pro faktury, připojitelný samostatně pro každou firmu
- Mobile-first UI
- CSRF, prepared statements, tenant scoping, upload MIME/size guard, audit, základní rate-limit loginu

## Spuštění

1. Zkopírujte `.env.example` do `.env`.
2. Nastavte alespoň `APP_KEY` a `APP_URL`.
3. Spusťte Docker Compose. Composer nainstaluje PHP závislosti včetně QR knihovny, Dompdf a GoPay REST API.
4. Otevřete aplikaci a vytvořte workspace.

### Integrace

- Fio: `FIO_API_TOKEN`
- Multi-bank Open Banking: `SALTEDGE_APP_ID`, `SALTEDGE_SECRET`
- AI/OCR: `AI_API_KEY`, případně `AI_VISION_MODEL`
- GoPay SaaS: `GOPAY_SAAS_GOID`, `GOPAY_SAAS_CLIENT_ID`, `GOPAY_SAAS_CLIENT_SECRET`
- GoPay: `GOPAY_BASE_URL` (GOID/Client ID/Client Secret se zadávají a šifrovaně ukládají pro každou firmu v Nastavení)
- E-mail: `MAIL_FROM` / serverní mail transport

## Worker

Spouštějte `php cron/worker.php` pravidelně (např. každou minutu). Worker synchronizuje Fio, generuje opakované faktury, vytváří upomínky, provádí pravidla automatizací a zpracovává e-mailovou frontu.

## Poznámka k českým bankám

Aplikace nepoužívá bankovní hesla/PIN/SMS. Pro více bank je použit Open Banking agregátor; dostupnost konkrétní banky je dána aktuálním katalogem poskytovatele a PSD2/SCA podmínkami. Fio má navíc vlastní read-only API.

Daňový modul je informativní a musí uvádět aktuální pravidla; před podáním přiznání nebo závazným daňovým rozhodnutím je vhodné ověření účetním/daňovým poradcem.

## Produkční provoz V9

### Zálohy
Aplikace umí vytvořit konzistentní SQLite backup přes `VACUUM INTO`, gzipovat jej a spočítat SHA-256. Pokud je nastaveno `BACKUP_SCP_TARGET` a `BACKUP_REMOTE_DIR`, odešle komprimovanou zálohu na oddělený server přes SCP. Doporučené je spouštět endpoint/CLI z důvěryhodného cron workeru a mít SSH klíč pouze s omezeným přístupem do backup adresáře. Retence lokálních záloh se řídí `BACKUP_RETENTION_DAYS`.

### AI provider
`AI_PROVIDER=openai` používá OpenAI-compatible `/chat/completions`. `AI_PROVIDER=anthropic` používá Anthropic Messages API. Klíče patří pouze do serverového `.env`; nikdy do databáze ani browseru.

### Super Admin
`SUPER_ADMIN_EMAILS` obsahuje čárkou oddělené e-mailové adresy provozních administrátorů SaaS. Super Admin má oddělený systémový přehled od administrace konkrétní firmy.

### Produkční databáze
V9 stále používá SQLite jako runtime databázi. Pro přechod na PostgreSQL je potřeba samostatná migrační fáze, protože část dotazů používá SQLite-specific SQL funkce. V9 proto PostgreSQL nepředstírá jako hotovou kompatibilní volbu.


## Předplatné
Byznio používá jeden tarif 300 Kč měsíčně. GoPay slouží pro checkout a automatické opakované platby; zrušení zastaví další opakování a přístup zůstává do konce zaplaceného období.
