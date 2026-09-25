# Byznio vlastní mail server

Tato složka připravuje vlastní SMTP příjem i odesílání pro `@byznio.cz` bez klasických mailboxů.

## Architektura

- Postfix přijímá SMTP na VPS.
- `MX byznio.cz` směřuje na VPS.
- Adresy `jmeno@byznio.cz` se synchronizují z tabulky `workspaces`.
- Známé adresy jsou předány přes Postfix pipe přímo do Byznia.
- E-mail se neukládá do IMAP schránky.
- Přílohy faktur se uloží do `storage/received-invoices/{workspace_id}` a e-mail se zapíše do komunikace.
- OCR se následně zpracuje workerem.
- Odesílání Byznia používá lokální `/usr/sbin/sendmail`; Brevo zůstává volitelným fallbackem.

## Instalace na VPS

Nejdřív nahrajte tento projekt na GitHub a nasaďte ho na VPS stejně jako běžnou verzi. Potom spusťte jako root:

```bash
cd /var/www/byznio
bash mailserver/install.sh /var/www/byznio
```

Instalátor:

1. nainstaluje Postfix, OpenDKIM a Python,
2. nastaví Postfix pro `byznio.cz`,
3. vytvoří DKIM klíč,
4. nastaví pipe do Byznia,
5. vytvoří synchronizaci adres,
6. nastaví cron pro synchronizaci příjemců,
7. vypíše DNS záznamy, které je nutné přidat ve WEDOSu.

## DNS

Instalátor vypíše konkrétní SPF a DKIM hodnoty. MX záznamy se záměrně nepřidávají automaticky.

Před přepnutím MX je potřeba ověřit, že VPS přijímá TCP/25 a že reverse DNS (PTR) IP adresy je nastavený na mail hostname. PTR se nastavuje u poskytovatele VPS, ne v DNS zóně.

## Důležité

Toto není klasický mailhosting. Byznio schránky nejsou IMAP schránky a nemají limit typu „zaplněná schránka“. Přijaté zprávy jsou zpracovány jako aplikační vstup a po úspěšném zpracování se nedrží v mailboxu.

Vlastní odesílání je možné provozovat bez Breva, ale doručitelnost je nutné otestovat na Gmailu, Seznamu a Outlooku. DKIM/SPF/DMARC a PTR musí být správně nastavené.
