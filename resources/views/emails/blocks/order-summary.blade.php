@php
    use Dashed\DashedEcommerceCore\Classes\CurrencyHelper;
    use Dashed\DashedEcommerceCore\Classes\SKUs;

    $products = method_exists($order, 'orderProducts')
        ? $order->orderProducts()->whereNotIn('sku', SKUs::hideOnConfirmationEmail())->get()
        : collect();
    $shippingProduct = $order->orderProducts()->where('sku', 'shipping_costs')->first();
    $paymentProduct = $order->orderProducts()->where('sku', 'payment_costs')->first();
@endphp
<tr><td style="padding:16px 24px; font-family: Arial, sans-serif; font-size:14px; color:#374151;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        @foreach($products as $line)
            <tr>
                <td width="80" valign="top" style="padding:12px 8px 12px 0; border-bottom:1px dashed #D8D8D8;">
                    @php
                        $img = optional(optional($line->product)->firstImage) ? mediaHelper()->getSingleMedia($line->product->firstImage, 'small') : null;
                    @endphp
                    @if($img)
                        <img src="{{ $img->url }}" width="80" style="display:block; width:80px; max-width:80px; height:auto;">
                    @endif
                </td>
                <td valign="top" style="padding:12px 0; border-bottom:1px dashed #D8D8D8;">
                    <strong style="color:#111827;">{{ $line->name }}</strong>
                    @if(!empty($line->product_extras) && is_array($line->product_extras))
                        @foreach($line->product_extras as $option)
                            <br><span style="font-size:13px; color:#6b7280;">{{ $option['name'] ?? '' }}: {{ $option['value'] ?? '' }}</span>
                        @endforeach
                    @endif
                    <div style="margin-top:6px;">
                        <span style="font-weight:bold;">{{ $line->quantity }}×</span>
                        <span style="float:right; font-weight:bold;">{{ CurrencyHelper::formatPrice($line->price) }}</span>
                    </div>
                </td>
            </tr>
        @endforeach

        @if($showTotals)
            <tr>
                <td style="padding-top:12px; color:#6b7280;">Subtotaal</td>
                <td align="right" style="padding-top:12px;">{{ CurrencyHelper::formatPrice($order->subtotal) }}</td>
            </tr>
            @if(($order->btw ?? 0) > 0)
                <tr>
                    <td style="padding:4px 0; color:#6b7280;">BTW</td>
                    <td align="right" style="padding:4px 0;">{{ CurrencyHelper::formatPrice($order->btw) }}</td>
                </tr>
            @endif
            <tr>
                <td style="padding:4px 0; color:#6b7280;">Verzendkosten</td>
                <td align="right" style="padding:4px 0;">
                    @if($shippingProduct && $shippingProduct->price > 0)
                        {{ CurrencyHelper::formatPrice($shippingProduct->price) }}
                    @else
                        Gratis
                    @endif
                </td>
            </tr>
            @if($paymentProduct && $paymentProduct->price > 0)
                <tr>
                    <td style="padding:4px 0; color:#6b7280;">Betaalkosten</td>
                    <td align="right" style="padding:4px 0;">{{ CurrencyHelper::formatPrice($paymentProduct->price) }}</td>
                </tr>
            @endif
            @if(($order->discount ?? 0) > 0.01)
                @php
                    $discountCode = $order->discountCode ?? null;
                    $discountLabel = 'Korting';
                    if ($discountCode) {
                        $discountLabel = 'Korting (' . $discountCode->code;
                        if ($discountCode->type === 'percentage' && $discountCode->discount_percentage) {
                            $discountLabel .= ' - ' . (int) $discountCode->discount_percentage . '%';
                        }
                        $discountLabel .= ')';
                    }
                @endphp
                <tr>
                    <td style="padding:4px 0; color:#6b7280;">{{ $discountLabel }}</td>
                    <td align="right" style="padding:4px 0;">- {{ CurrencyHelper::formatPrice($order->discount) }}</td>
                </tr>
            @endif
            <tr>
                <td style="padding-top:10px; border-top:1px solid #e5e7eb; font-weight:bold; color:#111827;">Totaal</td>
                <td align="right" style="padding-top:10px; border-top:1px solid #e5e7eb; font-weight:bold; color:#111827;">
                    {{ CurrencyHelper::formatPrice($order->total) }}
                </td>
            </tr>
        @endif
    </table>
</td></tr>
