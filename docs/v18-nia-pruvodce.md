# Byznio v18 – Nia jako průvodce a AI asistent

v18 rozšiřuje v17 o jednotného vizuálního průvodce Nia.

- Nia je vykreslená jako jednoduchá lidská postavička pomocí inline SVG, bez obrázkových/CDN závislostí.
- Na každé přihlášené stránce je Nia vpravo dole. Kliknutí otevře komiksovou bublinu s textem a vstupem pro požadavek.
- Odpověď se získává přes existující `/ai/ask`; není potřeba hlasová služba.
- Při přemýšlení a odpovědi se animuje hlava/ústa.
- Samostatné tlačítko AI v hlavní navigaci je odstraněno; celý chat `/ai` zůstává dostupný z Nia bubliny.
- Onboarding `/uvod` je nově sedmikrokový průvodce. První krok nastaví firmu/ARES, další kroky zvýrazňují skutečné části aplikace (CRM, Doklady, Zakázky, Banka, Kalendář) a poslední krok vysvětlí používání Nia.
- Průvodce lze kdykoli přeskočit a stav dál používá `workspaces.onboarding_completed_at`.
- Vše je čisté PHP/HTML/CSS/vanilla JS bez Reactu a bez externího CDN.
