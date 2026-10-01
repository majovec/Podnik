# R67 – Vlastní e-mailové schránky

- Webová schránka Byznio `/mail` pro zákaznické adresy.
- Příjem, odesílání, odpovědi, historie a přílohy.
- Sender name per workspace (`mail_display_name`).
- Super Admin správa systémových a zákaznických schránek `/admin/mailboxes`.
- Vyhrazené adresy (`info`, `help`, `support`, `admin`, `contact`, `office`, `billing`, `sales`, `ceo`, `director`, `team`, `service`, `noreply` atd.) nelze použít jako zákaznickou adresu.
- Postfix recipient sync nyní čte aktivní schránky z `email_mailboxes`.
- Stávající příjem faktur zůstává zachován a příloha se zároveň ukládá do historie schránky.
