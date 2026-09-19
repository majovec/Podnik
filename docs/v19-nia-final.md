# Byznio v19 – Nia + mobilní průvodce

Tato verze nahrazuje původního SVG panáčka Nii skutečným generovaným vizuálem Nii v `public/assets/nia.png`.

## Co je v této verzi
- nový vizuál Nii použitý v onboarding průvodci, plovoucím asistentovi a AI stránce;
- CSS animace Nii: jemné plovoucí/pohybové stavy, talking/thinking stav a vstupní animace;
- mobilně responzivní onboarding 1–7;
- krok 1 nastavení firmy přes IČO/ARES;
- kroky 2–6 vedou na skutečné obrazovky aplikace a používají spotlight nad reálnou navigací;
- automatické posunutí zvýrazněného prvku do záběru na mobilu;
- krok 7 dokončení průvodce a trvalá Nia v aplikaci;
- kliknutí na Niu v průvodci zobrazí další tip a krátkou animaci;
- zachované API Nia `/ai/ask`, potvrzování akcí a celý AI chat;
- zachované routy `/dashboard` a `/crm` z předchozí opravy;
- hlavní landing page nebyla touto úpravou redesignována.

## Kontrola
- PHP lint: OK pro `src`, `cron`, `public`.
- JavaScript syntax check: OK pro upravené inline skripty.
