# R74 – NIU full action execution

- NIU now has a real backend action layer instead of relying on Gemini text to claim that an action happened.
- Actions are proposed first and require user confirmation before execution.
- Implemented confirmed actions: customers, invoices, offers, orders, proforma invoices, credit notes, delivery notes, tasks, task completion, calendar events, jobs, products, expenses, marking invoices paid, sending invoices, sending overdue reminders, and document cancellation.
- Invoice/reminder sending uses the real Byznio mailer and records the outbound message.
- Invoice sending creates and attaches the document PDF.
- Reminder sending reports success only after the mailer confirms delivery submission; mail errors are returned to the user.
- AI context now includes documents, tasks, jobs, products, customers, payments, expenses, stock and upcoming events.
- Existing query/report behavior remains available when no safe action is detected.
- `.env` is not included in the release archive.
