@php
    $rows = $rows ?? [];
@endphp
<tr><td style="padding: 8px 24px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-family: Arial, sans-serif;">
        @foreach($rows as $row)
            @php
                $label = (string) ($row['label'] ?? '');
                $value = (string) ($row['value'] ?? '');
                $sub = (string) ($row['sub'] ?? '');
                $bg = $loop->even ? '#f9fafb' : '#ffffff';
            @endphp
            <tr>
                <td style="padding: 10px 12px; background:{{ $bg }}; border-bottom:1px solid #e5e7eb; font-size:14px; color:#374151; vertical-align:top; width:60%;">
                    {{ $label }}
                </td>
                <td style="padding: 10px 12px; background:{{ $bg }}; border-bottom:1px solid #e5e7eb; font-size:14px; color:#111827; font-weight:bold; text-align:right; vertical-align:top;">
                    {{ $value }}
                    @if($sub !== '')
                        <div style="font-size:12px; color:#6b7280; font-weight:normal; margin-top:2px;">{{ $sub }}</div>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
</td></tr>
