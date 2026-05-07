@php
    $headers = $headers ?? [];
    $rows = $rows ?? [];
    $colCount = max(count($headers), 1);
@endphp
<tr><td style="padding: 8px 24px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-family: Arial, sans-serif;">
        @if(count($headers) > 0)
            <tr>
                @foreach($headers as $header)
                    <td style="padding: 10px 12px; background:#111827; color:#ffffff; font-size:13px; font-weight:bold; text-align:left; vertical-align:top;">
                        {{ $header }}
                    </td>
                @endforeach
            </tr>
        @endif
        @foreach($rows as $row)
            @php $bg = $loop->even ? '#f9fafb' : '#ffffff'; @endphp
            <tr>
                @for($i = 0; $i < $colCount; $i++)
                    @php $cell = (string) ($row[$i] ?? ''); @endphp
                    <td style="padding: 8px 12px; background:{{ $bg }}; border-bottom:1px solid #e5e7eb; font-size:13px; color:#374151; vertical-align:top;">
                        {{ $cell }}
                    </td>
                @endfor
            </tr>
        @endforeach
    </table>
</td></tr>
