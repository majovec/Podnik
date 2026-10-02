# R69 – CRM zákazníci + mobilní menu

- CRM `/customers` načítá zákazníky pro aktuální workspace s explicitní normalizací `active` a stabilním řazením.
- Výpis zákazníků je vykreslen jako přehledné karty, které jsou klikatelné na detail zákazníka i na mobilu.
- Zachováno vyhledávání, počet zákazníků, archivovaný stav a tlačítko pro nového zákazníka.
- Mobilní hamburger používá pouze jeden `click` handler; odstraněna kombinace `pointerup` + `click`, která mohla na Safari způsobit dvojité přepnutí nebo nereagující stav.
- Zvýšen build/cache marker na `2026.10.02-r69`, aby se po nasazení načetla aktuální šablona.
