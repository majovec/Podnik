# R79 — PDF odběratel + oprava QR platby

- Opraveno zobrazení odběratele v PDF: pokud není vyplněný název firmy, použije se jméno a příjmení.
- Opravena generace QR platby pro veřejný odkaz `/d/{token}/qr`.
- Opravena generace QR platby v přihlášené části `/documents/{id}/qr`.
- QR generování je přizpůsobeno API `endroid/qr-code` 6.x (`Builder`).
- Nemění se vzhled faktury ani logika upomínek.
- `.env` není součástí release.
