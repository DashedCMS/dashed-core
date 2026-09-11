<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Account vergrendeld</title>
</head>
<body style="margin:0; padding:40px 16px; font-family: Arial, sans-serif; background:#f4f4f5; color:#111827;">
<div style="max-width:520px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; border-top:4px solid #16a34a;">
    <h1 style="font-size:22px; margin:0 0 16px 0;">Het account is vergrendeld</h1>
    <p style="line-height:1.5;">Het account <strong>{{ $user->email }}</strong> kan niet meer inloggen. Neem contact op met een superadmin om het te herstellen: die zet een nieuw wachtwoord, waarna je MFA opnieuw instelt en je rol terugkrijgt.</p>
</div>
</body>
</html>
