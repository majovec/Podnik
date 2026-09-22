# Release notes — R29

## Onboarding definitive repair
- Step 2 company form is rendered by a dedicated server view with self-contained CSS and forced visible controls.
- Step 7 completion button is rendered by a dedicated server view with a mobile sticky action bar.
- Onboarding no longer depends on `byznio_new_registration` session state; an authenticated workspace with incomplete onboarding can continue the wizard.
- `/uvod` sends `Cache-Control: no-store` and an R29 diagnostic response header.
