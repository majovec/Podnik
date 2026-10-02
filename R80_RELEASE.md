# R80 – Super Admin centrum

- Nové centrální Super Admin řídicí centrum na `/admin`.
- Přehled firem, uživatelů, aktivních/neaktivních účtů, trialů, expirovaných trialů a orientačního MRR.
- Provozní aktivita je agregovaná; Super Admin nevidí obsah faktur, zákazníků, bankovních transakcí ani e-mailů firem.
- Správa uživatelů: aktivovat/deaktivovat účet a měnit roli.
- Detail účtu: stav předplatného, individuální měsíční/roční cena, trial, free období a aktuální období.
- Systém a zdraví: PHP, SQLite, DomPDF, QR, DB, disk, AI/GoPay/mail konfigurace bez zobrazování tajných hodnot, zálohy.
- Globální SaaS ceny a délka trialu zůstávají v Super Admin části.
- `subscriptions` nově podporuje individuální měsíční a roční cenu; runtime migrace je bezpečně doplní i do existující DB.
- `SUPER_ADMIN_EMAILS` zůstává jediným vstupním mechanismem pro Super Admina.
