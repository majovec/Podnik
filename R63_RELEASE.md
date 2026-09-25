# Byznio R63 – vlastní mail server

- Vlastní Postfix příjem pro `@byznio.cz` bez klasických mailboxů.
- Příjem je napojen přímo na `workspaces.email_localpart`.
- Příchozí PDF/obrázky se ukládají jako přijaté faktury a e-mail do komunikace.
- OCR příchozích faktur běží na pozadí workerem.
- Odesílání Byznia umí lokální `/usr/sbin/sendmail`; `MAIL_TRANSPORT=local` je výchozí.
- Brevo zůstává jako volitelný transport přes `MAIL_TRANSPORT=brevo`, aby byl bezpečný rollback při testování doručitelnosti.
- Přidán instalační balíček `mailserver/` pro Postfix + OpenDKIM a synchronizaci adres.
- `Přijaté faktury` nyní zobrazuje skutečnou `@byznio.cz` adresu místo `inbox.byznio.cz`.
