<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Account vergrendelen</title>
</head>
<body style="margin:0; padding:40px 16px; font-family: Arial, sans-serif; background:#f4f4f5; color:#111827;">
<div style="max-width:520px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; border-top:4px solid #dc2626;">
    <h1 style="font-size:22px; margin:0 0 16px 0;">Was jij dit niet?</h1>
    @if ($alreadyLocked)
        <p style="line-height:1.5;">Het account <strong>{{ $user->email }}</strong> is al vergrendeld of heeft geen beheerrechten meer. Er is niets meer te doen.</p>
    @else
        <p style="line-height:1.5;">Je staat op het punt het account <strong>{{ $user->email }}</strong> te vergrendelen. Het wachtwoord wordt vervangen door een willekeurig wachtwoord, elke lopende sessie stopt, en het account verliest zijn beheerrechten. Een superadmin kan het daarna herstellen.</p>
        <form method="POST" action="{{ url()->full() }}" style="margin-top:24px;">
            @csrf
            <button type="submit" style="background:#dc2626; color:#fff; border:0; padding:12px 20px; font-size:16px; border-radius:6px; cursor:pointer;">Vergrendel dit account</button>
        </form>
    @endif
</div>
</body>
</html>
