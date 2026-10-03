# R83 – Byznio Intelligence, více firem a typ podnikání

R83 rozšiřuje Byznio bez zásahu do `.env`:

- týdenní a měsíční přehledy s detailním cash-flow z období,
- oddělené pohledávky a závazky – včetně neuhrazených přijatých faktur,
- sklad: počet položek, hodnota skladu, minimální zásoby a nejhodnotnější položky,
- detailní stránka `/reports?period=week|month`,
- nový modul `ReportService`,
- připravené automatické e-mailové reporty `bin/send-reports.php`,
- instalace denního cron runneru přes `bin/install-report-cron.sh`,
- typ podnikání firmy a vizuální karty pro 10 typů podnikání,
- přizpůsobené doporučené moduly podle typu podnikání,
- více firem pod jedním přihlášením přes `workspace_members`,
- rychlé přepínání firmy na desktopu i mobilu,
- založení další firmy s vlastním workspace, schránkou, skladem, bankou a předplatným,
- další firma je nastavena na 300 Kč/měsíc a dostává standardní zkušební období,
- výchozí firma stávajících uživatelů automaticky získá membership,
- žádná změna `.env`.

## Automatické reporty

Po nasazení jednou spusť:

```bash
sudo /var/www/byznio/bin/install-report-cron.sh
```

Cron běží denně v 07:05 Europe/Prague. Skript sám odešle týdenní report v pondělí a měsíční report první den měsíce a chrání před duplicitou pomocí `last_weekly_report_at` / `last_monthly_report_at`.

Pro ruční test:

```bash
sudo -u www-data php /var/www/byznio/bin/send-reports.php --force-weekly
sudo -u www-data php /var/www/byznio/bin/send-reports.php --force-monthly
```

## Poznámka k cash-flow

Pokud má firma v období bankovní transakce, report použije jejich čistý součet. Pokud banka zatím není napojena nebo v období nejsou bankovní pohyby, použije evidované příjmy z plateb a evidované výdaje a v UI to označí jako odhad z evidovaných dat.

## Bezpečnost

Přepínání firem ověřuje membership uživatele. Data zůstávají oddělená přes `workspace_id`. Stávající `.env` ani databázový soubor nejsou součástí release ZIPu.
