# R84 – Volitelné funkce a moduly podle firmy

R84 rozšiřuje R83 o skutečně konfigurovatelné moduly firmy:

- typ podnikání je nadále výchozí doporučení, nikoli pevná šablona,
- při zakládání firmy lze upravit, které funkce se mají aktivovat,
- v Nastavení → Funkce a moduly lze kdykoli funkce zapnout nebo vypnout,
- tlačítko „Použít doporučené podle typu podnikání“ obnoví doporučené nastavení podle aktuálně zvoleného typu,
- aktivní moduly se ukládají do workspace jako `modules_json`,
- nové firmy dostávají doporučené moduly podle svého typu podnikání,
- navigace na desktopu i mobilu zobrazuje pouze aktivní moduly,
- přímý přístup na vypnutou funkci je chráněn a uživatel dostane informaci, že ji má zapnout v Nastavení,
- reporty respektují aktivní moduly – např. vypnutý Sklad se nezobrazuje v reportu ani v e-mailovém reportu,
- e-mailové týdenní a měsíční reporty používají stejné modulové nastavení firmy,
- fakturace zůstává základní funkcí firmy a nelze ji omylem vypnout,
- žádná změna `.env`.

## Databáze

R84 přidává do `workspaces` sloupec `modules_json`. `Database::migrate()` jej při nasazení doplní i na existujících instalacích.

## Bezpečnost a kompatibilita

- stávající data firem zůstávají zachována,
- pokud starší workspace nemá `modules_json`, Byznio použije doporučené moduly podle jeho `business_type`,
- release ZIP neobsahuje `.env`, `database/app.sqlite` ani `vendor/`.
