# Byznio – bezpečné odesílání e-mailů přes Postmark

Byznio používá pro odchozí transakční e-maily **Postmark Email API**. Aplikace už nepoužívá PHP `mail()` ani neodesílá zprávy bez autentizace.

## Proč Postmark

Pro Byznio je vhodný hlavně proto, že jde o transakční e-mailovou službu a podporuje **ověření celé domény**. Po ověření `byznio.cz` lze posílat z libovolných adres na této doméně, takže aplikace může dynamicky používat například `firma1@byznio.cz`, `firma2@byznio.cz` atd. bez vytváření jednotlivých sender signature pro každou firmu. Postmark tuto možnost výslovně doporučuje pro velké množství odesílacích adres.

## 1. Založení Postmark

1. Vytvořte Postmark účet.
2. V Postmark otevřete **Sender Signatures / Domains**.
3. Přidejte doménu `byznio.cz` a zvolte **Domain Verification**.
4. Postmark zobrazí unikátní DNS hodnoty pro DKIM.
5. Přidejte je do DNS správce domény `byznio.cz`.
6. Po propagaci v Postmarku spusťte ověření.

Po ověření domény není nutné vytvářet sender signature pro každou adresu. Libovolná adresa `*@byznio.cz` může být použita jako From.

## 2. DKIM

Postmark pro doménu vygeneruje unikátní TXT záznam. **Nehádejte jeho hodnotu a nekopírujte příklad z této dokumentace** – použijte přesně hodnotu, kterou Postmark zobrazí v DNS Settings.

Typ: `TXT`

Host: hodnotu `DKIMPendingHost` / hostname z Postmarku

Value: hodnotu `DKIMPendingTextValue` / TXT value z Postmarku

Po ověření začne Postmark zprávy pro `byznio.cz` podepisovat DKIM.

## 3. Custom Return-Path / SPF

V Postmarku nastavte custom Return-Path podle hodnoty, kterou vám Postmark zobrazí. Typicky jde o:

- CNAME host: `pm_bounces`
- CNAME target: `pm.mtasv.net`

Tím Postmark používá vlastní Return-Path pod vaší doménou a zprávy mohou projít SPF alignmentem.

Postmark dnes uvádí, že není nutné přidávat `include:spf.mtasv.net` do vlastního SPF záznamu pouze kvůli Postmarku, protože SPF se vyhodnocuje přes Return-Path. Pokud už `byznio.cz` SPF používá, **nevytvářejte druhý SPF TXT záznam**; případné existující SPF záznamy sloučte podle skutečných odesílatelů domény.

## 4. DMARC

Doporučený začátek je monitorovací politika, například:

```text
Type: TXT
Host: _dmarc
Value: v=DMARC1; p=none; rua=mailto:dmarc@byznio.cz; adkim=r; aspf=r
```

Po ověření, že všechny legitimní zdroje pošty fungují, lze politiku zpřísnit na `quarantine` a následně podle výsledků na `reject`. DMARC slouží k ochraně domény před spoofingem a zneužitím identity odesílatele.

## 5. Nastavení VPS

Do `.env` vložte:

```dotenv
POSTMARK_SERVER_TOKEN=...
POSTMARK_MESSAGE_STREAM=outbound
MAIL_FROM=info@byznio.cz
MAIL_FROM_NAME=Byznio
```

Token nesmí být uložen v GitHubu.

Aplikace pak používá jako From například:

```text
Novák s.r.o. <novak@byznio.cz>
```

Podmínkou je, že `byznio.cz` je v Postmarku ověřená doména. Postmark výslovně podporuje odesílání z libovolných adres na ověřené doméně.

## 6. Co aplikace dělá

- všechny e-maily jdou přes autentizované Postmark API;
- nepoužívá se PHP `mail()`;
- fronta `email_queue` zůstává zachována;
- cron pouze vezme zprávy z fronty a odešle je přes Postmark;
- PDF přílohy se posílají jako Postmark attachment;
- firemní logo se používá v HTML e-mailu, pokud ho firma nahrála;
- při chybě se uloží Postmark HTTP/cURL chyba do `email_queue.last_error`;
- `POSTMARK_SERVER_TOKEN` je pouze v `.env`.

Postmark má oficiální PHP knihovnu, ale Byznio používá přímo jejich HTTPS API přes PHP cURL, takže není nutná další runtime závislost. Postmark API vyžaduje `X-Postmark-Server-Token`.
