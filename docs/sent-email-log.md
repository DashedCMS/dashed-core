# Verzonden-mail-logboek

Logt elke door de CMS verzonden mail (volledige HTML + metadata) in
`dashed__sent_emails` en koppelt aflever-/interactiestatus via Postmark-webhooks.
Zichtbaar onder "Verzonden mails" in het admin-panel.

## Aanzetten

In `.env` (defaults staan al aan):

```
DASHED_SENT_EMAILS_ENABLED=true
DASHED_SENT_EMAILS_RETENTION_DAYS=90
DASHED_SENT_EMAILS_TRACK=true
POSTMARK_WEBHOOK_SECRET=<kies-een-sterk-secret>
```

## Postmark-console (eenmalig per server)

1. Zet in de Postmark-server open- en link-tracking aan.
2. Voeg een webhook toe naar:
   `https://<basic-auth>@<domein>/dashed/webhooks/postmark`
   waarbij `<basic-auth>` het `POSTMARK_WEBHOOK_SECRET` is (als wachtwoord).
   Alternatief voor simpele setups: `?secret=<POSTMARK_WEBHOOK_SECRET>` in de URL.
3. Vink de events aan: Delivery, Bounce, Spam Complaint, Open, Click.

## Opschonen

`dashed:prune-sent-emails` draait dagelijks via de scheduler en verwijdert
logs ouder dan `retention_days`.

## Privacy

Open-tracking gebruikt een onzichtbare tracking-pixel; link-tracking herschrijft
links. Zet `DASHED_SENT_EMAILS_TRACK=false` om alleen aflever-/bounce-status te
loggen zonder open/click-tracking.
