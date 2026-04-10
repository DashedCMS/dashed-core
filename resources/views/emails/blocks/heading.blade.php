@php
    $sizes = ['h1' => '28px', 'h2' => '22px', 'h3' => '18px'];
    $size = $sizes[$level] ?? '22px';
@endphp
<tr><td style="padding: 16px 24px 8px 24px;">
    <{{ $level }} style="margin:0; font-family: Arial, sans-serif; font-size:{{ $size }}; color:#111827;">
        {{ $text }}
    </{{ $level }}>
</td></tr>
