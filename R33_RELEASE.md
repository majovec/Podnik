# Byznio R33

- Dashboard rebuilt to match the supplied premium Byznio visual direction: KPI cards, finance chart, recent activity, tasks, overdue invoices, finance, upcoming events, Nia recommendations and quick-access tools.
- Dashboard stylesheet is served as a dedicated cache-busted asset `dashboard.css?v=2026.09.22-r33`.
- Dashboard has explicit build marker `2026.09.22-r33`.
- Nia robot is always layered above dashboard content and supports touch dragging with `touch-action:none`.
- Dragging Nia pins her position for 120 seconds; optional `Připnout Niu` control pins her until released.
- Autonomous movement is slower and restricted to predefined safe positions so Nia does not randomly cover important controls.
- Removed the old behavior that immediately moved Nia after a simple tap.
- Nia position is persisted in localStorage.
