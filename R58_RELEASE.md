# Byznio R58 – e-mailová aktivace registrace

- Nová registrace už uživatele automaticky nepřihlásí.
- Po registraci vznikne účet v neověřeném stavu a odešle se aktivační e-mail přes Postmark.
- Aktivační odkaz je jednorázový, uložený pouze jako SHA-256 hash a platí 24 hodin.
- Přidána stránka `/verify-email` s možností poslat aktivační e-mail znovu.
- Neověřený účet se nemůže přihlásit.
- Po úspěšném potvrzení e-mailu se uživatel přihlásí a pokračuje do Byznio onboardingu.
- Stávající účty bez hodnoty `email_verified_at` jsou migrací ponechány jako neověřené; před ostrým přechodem je potřeba rozhodnout, zda je jednorázově označit jako ověřené, nebo je nechat potvrdit e-mail.
- `.env.example` zůstává připravený pro finální produkční konfiguraci: doména, APP_URL, APP_KEY, Postmark, AI/Gemini, Salt Edge, Stripe a GoPay.
