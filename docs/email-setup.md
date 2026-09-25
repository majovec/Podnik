# Byznio – e-mailová infrastruktura

Byznio používá pro odchozí transakční e-maily **Brevo Email API**. Pro příjem faktur používá **Brevo Inbound Parsing**, který doručí přijatý e-mail jako strukturovaný JSON webhook a umožňuje stáhnout přílohy přes `DownloadToken`.

## 1. Odchozí e-maily

Aplikace používá frontu `email_queue` a `MailerService`, který volá Brevo API přes cURL. API klíč je pouze v `.env`.

```dotenv
BREVO_API_KEY=...
MAIL_FROM=info@byznio.cz
MAIL_FROM_NAME=Byznio
```

Firemní odesílací adresy jsou vytvářené jako `email_localpart@mail_domain`, kde `mail_domain` je standardně `byznio.cz`.

## 2. Příjem faktur e-mailem

Přijaté faktury používají Brevo Inbound Parsing. Brevo aktuálně vyžaduje, aby receiving domain/subdomain byl odlišný od domény používané pro odesílání. Proto je výchozí receiving domain v Byzniu `inbox.byznio.cz`.

```dotenv
MAIL_INBOUND_DOMAIN=inbox.byznio.cz
BREVO_INBOUND_WEBHOOK_TOKEN=dlouhy-nahodny-token
```

Každý workspace má svůj `email_localpart`, takže například `nova` dostane adresu:

```text
nova@inbox.byznio.cz
```

Pokud bude později potřeba přesně `nova@byznio.cz`, lze nad touto vrstvou doplnit alias/přeposílání na úrovni poštovní infrastruktury.

### DNS

V Brevo Inbound Parsing nastavte receiving domain `inbox.byznio.cz`. Brevo dokumentuje MX záznamy pro inbound server jako `inbound1.sendinblue.com` s prioritou 10 a `inbound2.sendinblue.com` s prioritou 20. DNS změny mohou trvat několik hodin.

### Webhook

URL webhooku:

```text
https://VAŠE-DOMÉNA/webhooks/brevo/inbound
```

Webhook nastavte jako typ `inbound` s událostí `inboundEmailProcessed`. Brevo podporuje vlastní hlavičky webhooku; Byznio očekává:

```text
X-Byznio-Inbound-Token: <stejná hodnota jako BREVO_INBOUND_WEBHOOK_TOKEN>
```

Přijatý payload obsahuje odesílatele, příjemce, předmět, text/HTML a seznam příloh. Byznio přílohy PDF/JPG/PNG/TIFF/WebP stáhne přes Brevo API a uloží je do workspace.

## 3. Automatické načtení faktury

Webhook pouze rychle uloží e-mail a přílohu. OCR se spouští následně přes `cron/worker.php`, takže příjem e-mailu nemusí čekat na AI.

Z faktury se automaticky pokoušíme načíst:

- dodavatele,
- IČO a DIČ,
- číslo faktury,
- variabilní symbol,
- datum vystavení,
- datum splatnosti,
- částku bez DPH,
- DPH,
- celkovou částku,
- měnu,
- bankovní účet a IBAN do OCR JSON pro další rozšíření.

Uživatel má vždy možnost údaje zkontrolovat a ručně upravit.

## 4. Ruční import

Na stránce **Přijaté faktury** lze také nahrát PDF nebo fotografii faktury. Ruční import používá stejný OCR mechanismus.

## 5. Bezpečnost

- webhook je chráněn samostatným tajným tokenem,
- API klíče nejsou v GitHubu, ale pouze v `.env`,
- přílohy jsou ukládány odděleně podle workspace,
- každý přijatý e-mail je proti opakovanému webhooku chráněn idempotentní kontrolou,
- OCR běží mimo webhook v cron workeru.
