<tr><td style="padding:16px 24px; font-family: Arial, sans-serif; font-size:14px; color:#374151;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        @foreach($order->orderProducts ?? [] as $line)
            <tr>
                <td style="padding:6px 0; border-bottom:1px solid #f3f4f6;">
                    {{ $line->quantity }}× {{ $line->name }}
                </td>
                <td align="right" style="padding:6px 0; border-bottom:1px solid #f3f4f6;">
                    {{ $line->priceFormatted ?? '' }}
                </td>
            </tr>
        @endforeach
        @if($showTotals)
            <tr>
                <td style="padding-top:12px; font-weight:bold;">Totaal</td>
                <td align="right" style="padding-top:12px; font-weight:bold;">
                    {{ $order->totalFormatted ?? '' }}
                </td>
            </tr>
        @endif
    </table>
</td></tr>
