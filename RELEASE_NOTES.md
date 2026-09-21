# Byznio v21 – oprava onboardingu, Nia a fakturace

## Opraveno
- Opraven PSR-4 autoload AI providerů: `AiProviderFactory` a oba providery mají vlastní soubory. Chyba `class App\\Services\\AiProviderFactory not found` je odstraněna.
- Registrace -> `/uvod` zůstává jediný automatický vstup do onboardingu pro nového uživatele.
- Opraven krok 2 onboardingu, který po uložení firmy omylem vracel zpět na krok 2; nyní pokračuje na krok 3.
- Onboarding má všech 7 kroků, každý obsahuje vlastní vizuální kontext / preview a vždy viditelnou navigaci Zpět / Pokračovat / Dokončit.
- Mobilní hamburger menu nyní skutečně otevírá postranní menu i během onboardingu.
- Nia není položka hlavního menu; zůstává jako plovoucí robot.
- Nia lze myší/prstem přetáhnout na jiné místo obrazovky. Poloha se uloží do `localStorage`.
- Nia se po obrazovce sama přesouvá, ale během otevřeného panelu a ručního přesouvání neruší uživatele.
- Nia zobrazuje proaktivní hlášky podle aktuálních dat firmy a po interakci může používat hlas prohlížeče.
- Dashboard dostal viditelnou sekci „Nia doporučuje“ a rychlé akce navíc k KPI, grafu, úkolům, pohledávkám, financím, kalendáři, skladu a modulům.
- Fakturu/doklad lze vytvořit s novým zákazníkem přímo ve formuláři. Není nutné nejdřív opouštět fakturu a jít do CRM.

## Kontrola
- Všech 63 PHP souborů v release prošlo `php -l` bez syntaktické chyby.
- Plný end-to-end běh s reálným `.env`/API klíčem vyžaduje prostředí VPS, protože release ZIP neobsahuje `vendor/` ani produkční tajné údaje.
