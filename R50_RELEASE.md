# Byznio R50 – faktura a QR platba

- Přepracovaný layout PDF faktury: kompaktnější hlavička, odběratel, údaje dokladu, položky, souhrn a samostatný blok úhrady.
- QR platba v PDF používá kompatibilní API `endroid/qr-code` 6.x.
- Pokud není nastaven bankovní účet, PDF místo prázdného místa zobrazí jasnou instrukci k nastavení účtu.
- Tlačítko `QR` v seznamu faktur už neotevírá samotný obrázek na bílé stránce. Otevře moderní modal s QR, uložením a možností otevřít QR samostatně.
- Přidána centralizovaná služba `QrService` pro SPAYD, validaci českého účtu/IBAN a generování QR.
- Opraveno generování QR pro aktuální API Endroid QR Code 6.x.
- Zachována veřejná QR adresa pro QR odkaz vložený do PDF/e-mailu.
