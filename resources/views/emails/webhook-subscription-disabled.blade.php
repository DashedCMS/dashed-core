<!DOCTYPE html>
<html lang="nl">
<body style="font-family: Arial, sans-serif; color: #111827;">
    <p>{{ $intro }}</p>
    <table cellpadding="4">
        <tr><td><strong>{{ $labelFrom }}</strong></td><td>{{ $ownerLabel }}</td></tr>
        <tr><td><strong>{{ $labelUrl }}</strong></td><td>{{ $webhookUrl }}</td></tr>
        <tr><td><strong>{{ $labelReason }}</strong></td><td>{{ $reason }}</td></tr>
    </table>
    <p>{{ $outro }}</p>
    <p>{{ $siteName }}</p>
</body>
</html>
