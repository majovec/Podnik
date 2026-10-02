# R68 – Oprava zobrazení zákazníků

- CRM `/customers` má nově stabilní a mobilně čitelné zobrazení zákazníků.
- Přidáno vyhledávání, počet zákazníků a prázdný stav s jasným tlačítkem pro založení zákazníka.
- Na mobilu se zákazníci zobrazují jako karty místo široké tabulky.
- Výpis zákazníků používá `COALESCE(active,1)`, aby starší záznamy s `active = NULL` nezmizely.
- Při startu databáze se starším zákazníkům s `active = NULL` nastaví `active = 1`.
- Stejná ochrana je použita i při načítání zákazníků do formulářů a API.
