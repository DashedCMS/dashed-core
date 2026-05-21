<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>{{ $isRecovery ? 'Koppeling hersteld' : 'Probleem met koppeling' }}</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f5; color:#111827;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f5; padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:8px; max-width:600px;">
                <tr>
                    <td style="padding:24px 32px; border-bottom:4px solid {{ $isRecovery ? '#10b981' : '#f43f5e' }};">
                        <div style="font-size:14px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                            {{ $siteName ?? 'Dashed CMS' }}
                        </div>
                        <div style="font-size:22px; font-weight:bold; color:{{ $isRecovery ? '#047857' : '#be123c' }};">
                            {{ $isRecovery ? 'Koppeling weer verbonden' : 'Probleem met koppeling' }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 32px;">
                        <p style="font-size:16px; line-height:1.5; margin:0 0 16px 0;">
                            De koppeling <strong>{{ $label }}</strong> is van status
                            <strong>{{ $oldStatusLabel }}</strong> naar
                            <strong>{{ $newStatusLabel }}</strong> gegaan.
                        </p>

                        @if ($message)
                            <div style="padding:12px 16px; background-color:#f9fafb; border-radius:6px; border-left:4px solid #9ca3af; margin:16px 0;">
                                <div style="font-size:13px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Foutmelding</div>
                                <div style="font-family: monospace; font-size:13px; color:#111827; word-break:break-word;">{{ $message }}</div>
                            </div>
                        @endif

                        <table cellpadding="0" cellspacing="0" border="0" style="font-size:14px; color:#374151; margin-top:16px;">
                            <tr>
                                <td style="padding:6px 12px 6px 0; color:#6b7280;">Slug</td>
                                <td style="padding:6px 0;"><code>{{ $slug }}</code></td>
                            </tr>
                            @if ($siteId)
                                <tr>
                                    <td style="padding:6px 12px 6px 0; color:#6b7280;">Site</td>
                                    <td style="padding:6px 0;"><code>{{ $siteId }}</code></td>
                                </tr>
                            @endif
                            <tr>
                                <td style="padding:6px 12px 6px 0; color:#6b7280;">Tijdstip</td>
                                <td style="padding:6px 0;">{{ now()->format('d-m-Y H:i') }}</td>
                            </tr>
                        </table>

                        <div style="margin-top:24px;">
                            <a href="{{ $integrationsDashboardUrl }}" style="display:inline-block; padding:12px 24px; background-color:#111827; color:#ffffff; text-decoration:none; border-radius:6px; font-weight:bold;">
                                Open integrations-dashboard
                            </a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 32px; background-color:#f9fafb; border-top:1px solid #e5e7eb; font-size:12px; color:#6b7280; border-bottom-left-radius:8px; border-bottom-right-radius:8px;">
                        Deze melding wordt automatisch verzonden door <code>dashed:check-integrations-health</code> zodra een koppeling van status verandert. Je krijgt geen herhaalde mail zolang de status hetzelfde blijft.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
