<tr><td style="padding:24px; font-family: Arial, Helvetica, sans-serif; color:#111827;">
    <div style="font-size:18px; font-weight:bold; margin-bottom:4px;">{{ $report->name }}</div>
    <div style="font-size:13px; color:#6b7280; margin-bottom:16px;">{{ $result->from->format('d-m-Y') }} - {{ $result->to->format('d-m-Y') }}</div>
    <table role="presentation" cellpadding="6" cellspacing="0" border="0" width="100%" style="font-size:13px; border-collapse:collapse;">
        <tr style="background:#f3f4f6;">
            <th align="left">{{ $result->groupLabel }}</th>
            @foreach($result->columns as $column)
                <th align="right">{{ $column['label'] }}</th>
            @endforeach
        </tr>
        @foreach($rows as $row)
            <tr style="border-bottom:1px solid #e5e7eb;">
                <td>{{ $row['group'] }}</td>
                @foreach($result->columns as $key => $column)
                    <td align="right">{{ \Dashed\DashedCore\Reports\ReportResult::format($row[$key], $column['format']) }}</td>
                @endforeach
            </tr>
        @endforeach
        <tr style="font-weight:bold;">
            <td>{{ __('Totaal') }}</td>
            @foreach($result->columns as $key => $column)
                <td align="right">{{ \Dashed\DashedCore\Reports\ReportResult::format($result->totals[$key], $column['format']) }}</td>
            @endforeach
        </tr>
    </table>
    @if($more > 0)
        <div style="font-size:12px; color:#6b7280; margin-top:10px;">{{ __('En nog :aantal regels, zie de bijlage.', ['aantal' => $more]) }}</div>
    @endif
</td></tr>
