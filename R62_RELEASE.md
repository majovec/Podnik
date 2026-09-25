# R62 – Přijaté faktury: import, OCR a příjem e-mailem

- Opravená stránka Přijaté faktury rozšířena o nahrání PDF/fotografie.
- Přidán automatický OCR import údajů faktury přes existující AI konfiguraci.
- Přidána možnost ruční kontroly a úpravy načtené faktury.
- Přidán bezpečný download originálního dokladu.
- Přidán Brevo Inbound Parsing webhook `/webhooks/brevo/inbound`.
- Přijaté PDF/obrázkové přílohy se ukládají k workspace a OCR se zpracovává přes cron.
- Přidána idempotence proti opakovaným webhookům.
- Přidány migrační sloupce `ocr_status`, `source_type`, `source_email_message_id`.
- Přidán `.env.example` pro `MAIL_INBOUND_DOMAIN` a `BREVO_INBOUND_WEBHOOK_TOKEN`.
- Odesílání zůstává přes existující Brevo API.

## Nasazení

Na VPS je po nasazení potřeba nastavit `BREVO_API_KEY`, `MAIL_INBOUND_DOMAIN` a `BREVO_INBOUND_WEBHOOK_TOKEN`. V Brevo se následně nastaví Inbound Parsing pro `inbox.byznio.cz` a webhook na `/webhooks/brevo/inbound`.
