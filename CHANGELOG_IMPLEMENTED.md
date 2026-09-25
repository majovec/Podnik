# Implemented feature pack

This build starts from `byznio-r60-gopay-public-pages-final.zip` and includes the source-level fixes previously made on the VPS that were known in the development session (Brevo mail transport and the SQLite backup fix), plus the requested feature work from the external development brief.

Secrets and the live `.env` are intentionally not included. Configure `BREVO_API_KEY` and other environment credentials on the VPS.

Implemented areas include: tax-year/payment-aware tax calculations, VAT payer handling and configurable default VAT rate, credit-note parent linkage and negative totals, stock validation/rollback, bank CSV normalization and deduplication, yearly document series, document editing/duplication and document chain conversions, advance invoice conversion, delivery-note document type, VAT regimes/DUZP/currency/language fields, VAT recap in public/PDF/ISDOC output, CNB rate service, English PDF labels, Comgate connector foundation, ISDOC import, accounting CSV/Pohoda XML/ZIP exports, received invoices, accountant read-only sharing, receipt camera capture/PWA shell, modern email core with Brevo transport, email preview, and domain-specific controllers for new tax/accounting/received-invoice areas.
