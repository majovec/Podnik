# R31 release

This release fixes the onboarding ARES interaction and the dashboard rendering issue observed after completing onboarding.

## ARES
`POST /uvod/ares` validates the Czech IČO, calls ARES server-side, and returns company/address data as JSON. The onboarding step 2 has a visible **Načíst z ARES** button that fills the fields without leaving the page.

## Dashboard
Removed an unmatched CSS closing brace in `src/Views/dashboard/index.php` that caused Safari/mobile browsers to drop the dashboard-specific stylesheet, leaving the dashboard as unstyled raw text/buttons.
