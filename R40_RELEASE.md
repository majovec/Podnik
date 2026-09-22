# Byznio R40

- Fixed the root cause of blank authenticated pages: view templates used unqualified `View::`, `Auth::` and `Env::` references when included from the shared layout.
- All affected view templates now use fully-qualified `\App\Core\...` references without changing already-qualified references.
- Dashboard remains enabled on both `/` and `/dashboard`.
- Mobile hamburger/drawer and Nia markup are now reached after the view renders successfully.
- Dashboard build marker: 2026.09.22-r40.
