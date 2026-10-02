# R81 – systémové e-mailové schránky a jednotná šablona

R81 navazuje na R80 Super Admin.

## Přidáno
- Super Admin automaticky dostane systémové schránky `info@` a `help@` na doméně z `MAIL_DOMAIN`.
- Systémové schránky jsou oddělené od zákaznických schránek.
- Z administrace lze přijímat, číst, odpovídat a odesílat zprávy z těchto schránek.
- Odeslané systémové e-maily používají stejný Byznio branded shell jako ostatní systémové e-maily: logo nahoře, jednotné rozvržení a Byznio footer dole.
- Opraveno sestavení `To:` hlavičky v mailbox odesílání.
- `sync-mail-recipients.php` již automaticky zahrnuje aktivní systémové schránky, takže po synchronizaci Postfix přijímá `info@` a `help@`.

## Konfigurace
Není potřeba přidávat nové tajné hodnoty do `.env`. Používá se stávající:
- `MAIL_DOMAIN`
- `MAIL_POSTFIX_MAP`
- `SENDMAIL_PATH`

`.env` není součástí ZIPu.
