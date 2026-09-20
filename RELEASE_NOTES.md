# Byznio – Dashboard + Nia v2

Tato verze opravuje první spuštění po registraci a přepracovává dashboard podle dodané vizualizace.

## Co je v této verzi

- nový samostatný onboarding `/uvod`, který se spouští pouze bezprostředně po nové registraci;
- onboarding má 7 jasných kroků a ve druhém kroku umí doplnit firmu přes IČO/ARES;
- po dokončení nebo přeskočení se onboarding označí jako dokončený a při běžném přihlášení se znovu automaticky nespouští;
- dashboard je rozšířený na KPI, příjmy/výdaje, poslední aktivitu, dnešní úkoly, pohledávky po splatnosti, finance/cashflow, kalendář, sklad a rychlý přístup ke všem hlavním modulům;
- odstraněno samostatné tlačítko Nia/AI z dashboardu a navigace;
- Nia je trvale přítomná jako plovoucí robot, který se náhodně pohybuje po obrazovce;
- Nia automaticky zobrazuje kontextové hlášky podle dat firmy (úkoly, splatnosti, zakázky, příjmy);
- Nia umí přijmout úkol po kliknutí a poslat ho do existujícího AI endpointu;
- přidán volitelný hlas přes browser SpeechSynthesis; prohlížeč může první automatické přehrání zvuku zablokovat do první interakce uživatele;
- důležité/finanční AI akce nadále používají návrh → potvrzení → provedení;
- zachována tenant izolace, CSRF, existující PHP architektura a ostatní moduly.

## Ověření

- PHP syntaxe: 62 PHP souborů bez syntax errors.
- Nový onboarding je oddělený od dashboardu a není závislý na query `?tour=`.
- Dashboard již neobsahuje tlačítko „Nia · AI asistent“.
