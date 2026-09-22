# Byznio release

## 2026.09.22-r29
- Definitive server-rendered onboarding step 2 and step 7 with self-contained mobile-safe markup/CSS.
- Onboarding access is based on workspace `onboarding_completed_at`, not the transient registration session flag.
- `/uvod` sends no-cache headers and `X-Byznio-Onboarding: 2026.09.22-r29`.
- Step 2 form and step 7 completion action use unique R29 selectors and inline CSS so older cached onboarding CSS cannot hide them.
