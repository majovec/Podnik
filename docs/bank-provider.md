# Bankovní integrace

## První provider: Fio API read-only

Pro první testovací a český provoz je v aplikaci implementováno přímé Fio API v režimu pouze pro čtení. Fio poskytuje API zdarma a token může být nastaven jako „Sledování účtu“; token nahrazuje přihlášení/heslo a má vlastní životnost. Dokumentace Fio uvádí endpointy pro pohyby a další bankovní data. Systém ukládá pouze token v zašifrované podobě a nikdy neukládá bankovní heslo, PIN, SMS ani údaje karty.

## Multi-bank strategie

Pro další banky je vhodné přidat samostatný PSD2/AISP adapter přes licencovaného poskytovatele open-banking služeb. Aplikace proto používá tabulku `bank_accounts` s providerem a izolovanou synchronizační službou. Nedoporučujeme obcházet bankovní přihlášení nebo ukládat uživatelská hesla.

## Párování

Matcher kombinuje:

- přesnou částku / zbývající nedoplatek
- variabilní symbol
- číslo dokladu v referenci/zprávě
- historii a stav dokladu

Výsledek je `matched`, `suggested` nebo `unmatched`. Automaticky se provede jen dostatečně silná shoda; ostatní čekají na ruční potvrzení.
