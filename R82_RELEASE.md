# R82 – ruční platby a oddělené e-mailové složky

R82 navazuje na R81 systémové e-mailové schránky.

## Přidáno
- U vystavených faktur je nově akce **Zaznamenat platbu**.
- Ruční platba podporuje částku, datum úhrady a způsob úhrady (bankovní převod, hotově, kartou, jiné).
- Podporovány jsou i částečné úhrady; stav faktury se přepočítá z tabulky plateb.
- Potvrzení o úhradě se odešle až při úplném uhrazení faktury.
- E-mailová schránka má oddělené složky **Přijaté** a **Odeslané** s počty zpráv.
- Zachováno otevírání zpráv, odpovědi a přílohy.
- Opraveno zpracování Postfix argumentu `--recipient=` (`substr(...,12)`).
- Opraveno MIME sestavení mailbox e-mailů, aby `Content-Type` hlavičky nebyly součástí těla zprávy.

## Konfigurace
- `.env` není součástí ZIPu a nesmí být přepisován.
- Systémové časové pásmo ani PHP konfigurace se tímto releasem nemění.
