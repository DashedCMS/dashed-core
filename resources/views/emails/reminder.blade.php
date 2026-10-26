<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $label }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#18181b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:24px;">
        <p style="margin:0 0 4px;font-size:13px;color:#71717a;">
            {{ $creatorName ? __('Taak van :naam', ['naam' => $creatorName]) : __('Herinnering') }}
        </p>
        <h1 style="margin:0 0 16px;font-size:20px;">{{ $label }}</h1>
        @if ($note)
            <p style="margin:0 0 16px;white-space:pre-line;">{{ $note }}</p>
        @endif
        @if ($recordUrl)
            <p style="margin:0 0 16px;">
                <a href="{{ $recordUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:8px;">{{ __('Openen') }}</a>
            </p>
        @endif
        @if ($tasksUrl)
            <p style="margin:0;font-size:13px;"><a href="{{ $tasksUrl }}" style="color:#2563eb;">{{ __('Mijn taken') }}</a></p>
        @endif
    </div>
    <p style="text-align:center;font-size:12px;color:#a1a1aa;margin-top:16px;">{{ $siteName }}</p>
</body>
</html>
