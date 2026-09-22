# R30 release notes

- Steps 2 and 7 now use a completely standalone server-rendered onboarding page.
- No shared app layout or legacy onboarding CSS participates in those two screens.
- Added no-cache response headers and `X-Byznio-Onboarding: 2026.09.22-r30`.
- Step 2 contains the company form directly in the returned HTML.
- Step 7 contains the completion form directly in the returned HTML.
- Mobile layout keeps the primary action visible.
