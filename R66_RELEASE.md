# R66 – Gemini IPv4 + multi-tenant Open Banking

- Added native Gemini provider and switched the default AI provider to Gemini.
- Gemini requests force IPv4 on VPS environments where Google rejects the IPv6 geolocation.
- Gemini errors now expose the provider message for diagnostics.
- Salt Edge API calls force IPv4 as well.
- Kept Salt Edge customer/connection ownership isolated by workspace/customer.
- Added automatic Salt Edge transaction synchronization to the existing cron worker when Salt Edge credentials are configured.
- Existing Fio per-customer/workspace integration remains unchanged.

## Production prerequisites
- Set `AI_PROVIDER=gemini`, `AI_BASE_URL=https://generativelanguage.googleapis.com/v1beta`, `AI_MODEL=gemini-3.8-flash`, and the existing `AI_API_KEY`.
- Set `SALTEDGE_APP_ID` and `SALTEDGE_SECRET` from the Salt Edge Client Dashboard. These are Byznio application credentials; individual customers then authorize their own banks through the Salt Edge widget.
