# Byznio R57

- Opraveno Salt Edge Open Banking Connect pro API v6 (správný consent/return_to/widget flow).
- Bankovní transakce z Open Banking lépe mapují VS, účet a referenci; příchozí platby se párují, odchozí se nepokoušejí párovat s fakturami.
- Nový zákazník vytvořený přímo při faktuře je standardní CRM zákazník a faktura je na něj navázaná; dokumentový seznam načítá i e-mail zákazníka.
- Automatické upomínky mají viditelný výsledek kontroly a deduplikaci.
- Automatizace mají vysvětlení „KDYŽ → PAK“, poslední běhy a reálné akce pro párování plateb, nízký sklad, překročení rozpočtu a upomínky.
- Faktura může být navázána na skladovou položku; při vystavení faktury se odečte množství ze skladu a vznikne skladový pohyb.
- PDF faktury zachovává QR a zobrazení bankovního účtu/IBANu z předchozí verze.
