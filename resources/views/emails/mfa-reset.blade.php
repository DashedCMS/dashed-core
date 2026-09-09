<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Je tweestapsverificatie is gereset</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f5; color:#111827;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f5; padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:8px; max-width:600px;">
                <tr>
                    <td style="padding:24px 32px; border-bottom:4px solid #f59e0b;">
                        <div style="font-size:14px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                            {{ $siteName ?? 'Dashed CMS' }}
                        </div>
                        <div style="font-size:22px; font-weight:bold; color:#111827;">
                            Je tweestapsverificatie is gereset
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 32px;">
                        <p style="font-size:16px; line-height:1.5; margin:0 0 16px 0;">
                            @if ($name) Beste {{ $name }}, @endif
                        </p>
                        <p style="font-size:16px; line-height:1.5; margin:0 0 16px 0;">
                            Op {{ $at }} heeft <strong>{{ $actor ?: 'een beheerder' }}</strong> je tweestapsverificatie (MFA) voor het CMS gereset.
                            Je authenticator-koppeling en je herstelcodes zijn gewist.
                        </p>
                        <p style="font-size:16px; line-height:1.5; margin:0 0 16px 0;">
                            Bij je volgende bezoek aan het CMS stel je MFA opnieuw in: log in met je wachtwoord, scan de QR-code
                            met je authenticator-app en bewaar de nieuwe herstelcodes op een veilige plek.
                        </p>
                        <p style="font-size:16px; line-height:1.5; margin:0 0 16px 0;">
                            <a href="{{ $loginUrl }}" style="color:#2563eb;">Naar het inlogscherm</a>
                        </p>
                        <p style="font-size:14px; line-height:1.5; color:#6b7280; margin:24px 0 0 0;">
                            Heb je hier niet om gevraagd, neem dan direct contact op met een beheerder.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
