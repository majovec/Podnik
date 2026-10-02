# Byznio R78 – reminder delivery + clean email layout

- Manuální upomínka přes Niu používá stejný ReminderService jako automatické upomínky.
- Manuální upomínka přikládá aktuální PDF faktury.
- Automatické upomínky používají stejný text jako Nia a také přikládají PDF faktury.
- Opraveno dvojité/nested HTML u e-mailu s fakturou: šablona už se nevkládá jako celý HTML dokument dovnitř dalšího e-mailového obalu.
- Fakturační e-mail má čistý přehled, CTA „Otevřít doklad“ a PDF v příloze.
- sendReport nyní umí bezpečně přiložit soubor.
- `.env` není součástí release.
