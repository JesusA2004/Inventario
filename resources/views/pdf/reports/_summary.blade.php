@if (!empty($kpis))
    <table class="kpis">
        <tr>
            @foreach ($kpis as $kpi)
                <td class="kpi-cell">
                    <div class="kpi-value">{{ $kpi['value'] }}</div>
                    <div class="kpi-label">{{ $kpi['label'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>
@endif

@if (!empty($breakdown) && $breakdown['items']->count() > 0)
    @php($maxTotal = max(1, $breakdown['items']->max('total')))
    <div class="breakdown">
        <div class="breakdown-title">{{ $breakdown['title'] }}</div>
        @foreach ($breakdown['items'] as $item)
            <div class="bar-row">
                <div class="bar-label">{{ $item['label'] }}</div>
                <div class="bar-track">
                    <div class="bar-fill" style="width: {{ max(4, round(($item['total'] / $maxTotal) * 100)) }}%;"></div>
                </div>
                <div class="bar-value">{{ $item['total'] }}</div>
            </div>
        @endforeach
    </div>
@endif
