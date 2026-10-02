# Byznio R75 – Nia confirmation flow

- Nia action confirmation is now conversational inside the same chat/panel.
- Example: `Odešli upomínku Jakubu Majerovi.` → Nia asks for confirmation → `Jo, chci.` executes the action.
- Reminder confirmation shows customer, invoice number and amount.
- Reminder email uses the same plain-text reminder wording/subject pattern as the automatic reminder worker.
- Customer-name reminder lookup handles common Czech declension variants.
- Removed the "Otevřít celý chat s Niou" link from the Nia panel.
- `/ai` no longer opens the old full-screen Nia chat; it redirects to the dashboard.
- Removed the old "prepared action" preview/card flow from the active Nia UX.
- Actual execution still happens only after explicit confirmation.
- `.env` is not included in this release.
- Service worker cache bumped to `byznio-shell-r75`.
