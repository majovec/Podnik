# R32 release

## Fix
The dashboard previously put its complete CSS inside a `<style>` element emitted in the page body. This can be ignored or handled inconsistently by mobile Safari. R32 moves the dashboard CSS to `public/assets/dashboard.css` and loads it from the document `<head>` for `/`.

## Verification
- Dashboard view no longer contains a body `<style>` block.
- Layout loads `/assets/dashboard.css?v=2026.09.22-r32` for the dashboard.
- Build marker: `2026.09.22-r32`.
