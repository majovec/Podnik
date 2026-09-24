# Byznio – audit proti původnímu zadání

Datum auditu: 20. 9. 2026

## Co bylo zkontrolováno

Projekt byl staticky projit proti všem 29 bodům původního zadání. Součástí kontroly byly routy, controllery, služby, databázové schéma, role/oprávnění, tenant scoping, AI, banky, fakturace, automatizace, onboarding a UI.

## Opravy provedené v tomto buildu

- Nia průvodce po registraci se spouští automaticky přes dashboard a má 14 navazujících kroků.
- Zavření průvodce nyní průvodce trvale ukončí uložením `onboarding_completed_at`; po běžné navigaci se už nevrací.
- Nia má přímo v panelu odkaz **Spustit průvodce znovu**.
- Dashboard byl přepracován směrem k dodané vizualizaci: tmavě modrý SaaS shell, modré/violetové akcenty, KPI karty, příjmy/výdaje, poslední aktivita, dnešní úkoly, pohledávky, AI doporučení, kalendář a sklad.
- Dashboard nově počítá skutečnou hodnotu skladu z nákupních cen a přehled posledních 6 měsíců.
- CRM při založení zákazníka nyní ukládá i dodací adresu, web a kompletní kontaktní údaje.
- Vlastní číselné řady dokladů lze nastavit v Nastavení firmy.
- Vlastní role lze vytvořit v Administraci a jejich oprávnění se skutečně načítají při autorizaci.
- API klíče lze vytvořit i přes JSON API a API je nově chráněno oprávněním `api`.
- Opraveno několik tenant/security hranic: validace zákazníka u dokladů a opakovaných faktur, kontrola vlastnictví zakázky při práci/materiálu, validace skladového pohybu, omezení Nastavení na owner/admin a GDPR exportu na owner/admin.
- Salt Edge customer ID se už necastuje na integer.
- Upload účtenek má MIME whitelist, velikostní limit a příponu odvozenou od skutečného MIME typu.
- Číslování dokladů je chráněné proti kolizi při souběžném vytvoření dokladů v SQLite.
- Přidán `.env.example`, protože README jej vyžaduje.
- PHP syntaxe byla zkontrolována pro všech 62 PHP souborů.

## Stav jednotlivých oblastí

| Oblast | Stav | Poznámka |
|---|---|---|
| Dashboard | Hotovo | KPI, cashflow, pohledávky, úkoly, události, sklad, AI doporučení, aktivita |
| CRM | Hotovo | Zákazníci, ARES, historie komunikace, doklady, zakázky, výdaje |
| Zakázky | Hotovo | Rozpočet, práce, materiál, náklady, zisk/marže, fakturace |
| Fakturace | Hotovo / rozšířitelné | Faktury, nabídky, objednávky, zálohy, dobropisy jako dokladové typy, PDF, QR, ISDOC |
| Párování plateb | Hotovo | VS, částka, reference, částečné úhrady, ruční párování |
| Banky | Hotovo s externí konfigurací | Fio + Salt Edge; vyžadují vlastní API přístupy |
| Výdaje/OCR | Hotovo s externí AI | Upload + OCR adapter; AI klíč je nutný pro automatické vytěžení |
| Sklad | Hotovo | Pohyby, inventura, minimum, materiál na zakázce |
| Tým/role | Hotovo | Owner/admin/employee/accountant + vlastní role/permissions |
| Kalendář | Hotovo | Události, zákazník, zakázka, uživatel |
| Dokumenty | Hotovo | Tenant-scoped upload/download |
| Nabídky → zakázky | Hotovo | Veřejná nabídka, přijetí, převod na zakázku |
| Opakované faktury | Hotovo | Worker vytváří faktury a frontuje e-mail |
| Upomínky | Hotovo | -3/0/+3/+7 dní + deduplikace |
| Daně | Informativní | Výpočet je orientační a označený jako takový; sazby/režimy je nutné před ostrým použitím ověřovat |
| Exporty | Hotovo | CSV, ISDOC, GDPR export |
| AI asistent | Částečně hotovo | Kontext firmy je rozšířen; bezpečné potvrzované akce jsou implementované pro zákazníka a fakturu, další akce lze dál rozšiřovat |
| Automatizace | Hotovo / pravidlové | Platby, nízký sklad, překročení rozpočtu, úkoly a worker |
| SaaS | Hotovo | Workspace, trial, subscription, GoPay, role, audit, tenant scoping |
| Tarify | Zjednodušené | Aktuální produkt používá jednotný tarif 300 Kč/měsíc; konfigurace je v administraci |
| Mobil | Hotovo | Responzivní shell, mobilní menu, mobilní landing, Nia |
| Design | Hotovo / průběžně rozšiřitelný | Vizuální směr je sjednocen s dodanou vizualizací |
| API | Hotovo / rozšiřitelné | CRUD pro zákazníky/doklady, joby, produkty, bankovní transakce, AI a API keys |
| Bezpečnost | Hotovo / produkční hardening stále závisí na infrastruktuře | CSRF, prepared statements, tenant scoping, upload guards, šifrované bankovní tokeny, audit |

## Důležitá provozní poznámka

Projekt používá SQLite runtime databázi. Docker image instaluje `pdo_sqlite`, Composer závislosti a spouští aplikaci přes PHP built-in server. PostgreSQL v tomto buildu není vydáváno za hotovou kompatibilní variantu.

V tomto pracovním prostředí nebylo možné provést plný runtime smoke test, protože lokální PHP CLI nemá nainstalovaný SQLite PDO driver. Syntaxe všech PHP souborů však prošla kontrolou bez chyb. Plný runtime test je připraven v `bin/smoke.php` a v Dockeru se spouští po `composer install`.

## Vizuální směr

Dodanou vizualizaci jsem použil jako referenci pro aplikaci: tmavě modrý levý panel, světlé pracovní plochy, modré/violetové CTA, zaoblené karty, výrazné KPI, čisté tabulky a mobilní responzivní layout. Dashboard byl v tomto buildu upraven přímo tímto směrem.
