# R59 – GoPay předplatné Byznio

- Stripe SaaS checkout byl odstraněn z produkčního toku Byznia.
- Předplatné Byznia nyní používá GoPay REST API.
- Měsíční předplatné zakládá automatickou opakovanou platbu GoPay každý měsíc.
- Roční předplatné zakládá automatickou opakovanou platbu GoPay po 12 měsících.
- První platba je autorizována zákazníkem přes GoPay platební bránu; další platby probíhají automaticky podle nastavené periody.
- Přidány callback/webhook endpointy pro první i následné platby.
- Přidána idempotentní evidence GoPay plateb, aby callback a webhook nemohly prodloužit předplatné dvakrát.
- Zrušení předplatného ruší GoPay recurrence přes `void-recurrence` a ponechává přístup do konce zaplaceného období.
- GoPay pro faktury zůstává oddělené od GoPay účtu používaného pro SaaS předplatné Byznia.
- Přidány produkční konfigurační proměnné `GOPAY_SAAS_GOID`, `GOPAY_SAAS_CLIENT_ID`, `GOPAY_SAAS_CLIENT_SECRET`.
- Stripe webhook, Stripe billing portal a Stripe závislost již nejsou součástí SaaS platebního toku.

## Důležité pro nasazení

Před ostrým použitím je potřeba v GoPay aktivovat opakované platby pro produkční prodejní místo. GoPay uvádí, že recurring payments jsou standardně dostupné v sandboxu a pro produkci je nutné jejich aktivaci řešit s GoPay. 
