# Byznio R76 – unified reminder sending

- NIU reminder sending now uses the exact same reminder subject/body as automatic reminders.
- Automatic reminders, manual reminder generation and NIU reminders share one ReminderService source of truth.
- An explicit request containing “upomínku” can no longer fall through to the generic AI planner and be misinterpreted as invoice sending.
- The reminder email uses the normal Byznio mail shell, not the invoice email template with invoice details/action button.
- Service worker cache bumped to byznio-shell-r76.
- `.env` is not included in this release.
