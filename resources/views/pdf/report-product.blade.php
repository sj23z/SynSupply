<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<style>
    @font-face { font-family: 'UTUSans'; src: url('{{ $arabicFontPath }}'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'UTUSans'; src: url('{{ $arabicFontPath }}'); font-weight: bold; font-style: normal; }
    * { box-sizing: border-box; }
    body { font-family: 'UTUSans', sans-serif; color: #17171A; font-size: 10.2px; line-height: 1.3; margin: 0; padding: 24px 34px; }
    .header { overflow: hidden; padding-bottom: 10px; border-bottom: 2px solid #C6A85A; }
    .brand { font-size: 18px; font-weight: bold; }
    .doc-title { float: right; font-size: 16px; font-weight: bold; color: #6B6B66; }
    .meta { margin-top: 8px; font-size: 10px; color: #444; }
    .clear { clear: both; }
    .summary { margin-top: 10px; overflow: hidden; }
    .summary .card { float: left; width: 24%; margin-right: 1%; }
    .summary .card p:first-child { color: #6B6B66; font-size: 9px; }
    .summary .card p:last-child { font-weight: bold; font-size: 12px; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { text-align: left; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #6B6B66; background: #F8F7F4; padding: 6px 8px; border-bottom: 1.5px solid #C6A85A; }
    td { padding: 5px 8px; border-bottom: 1px solid #EDEDEB; font-size: 10px; }
    th.num, td.num { text-align: right; }
    .fm-row td { border-bottom: 1px solid #EDEDEB; padding: 0 8px 6px; }
    .fm-table { width: 100%; margin-top: 2px; }
    .fm-table td { font-size: 8.5px; padding: 2px 6px; border-bottom: none; background: #FAFAF9; }
    .fm-payment { border-right: 2px solid #2E5FA3; color: #2E5FA3; }
    .fm-return { border-right: 2px solid #3E7A4F; color: #3E7A4F; }
</style>
</head>
<body>
    <div class="header">
        <div class="brand">UTU LTD</div>
        <div class="doc-title">PRODUCT REPORT</div>
        <div class="clear"></div>
        <div class="meta">{{ \App\Support\ArabicPdfText::shape($product['name']) }} &middot; {{ $from }} &rarr; {{ $to }}</div>
    </div>

    <div class="summary">
        <div class="card"><p>Qty Sold</p><p>{{ number_format($product['units_sold']) }}</p></div>
        <div class="card"><p>Qty Returned</p><p>{{ number_format($product['returned_qty']) }}</p></div>
        <div class="card"><p>Net Qty Sold</p><p>{{ number_format($product['net_qty_sold']) }}</p></div>
        <div class="card"><p>Sales</p><p>{{ number_format($product['gross_sales']) }}</p></div>
        @if ($isOwner)
            <div class="card"><p>COGS</p><p>{{ number_format($product['cogs']) }}</p></div>
            <div class="card"><p>Gross Profit</p><p>{{ number_format($product['gross_profit']) }}</p></div>
        @endif
        <div class="card"><p>Current Stock</p><p>{{ number_format($product['current_stock']) }}</p></div>
        <div class="clear"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Invoice</th><th>Date</th><th>Customer</th><th>Area</th><th>Rep</th>
                <th class="num">Qty</th><th class="num">Unit Price</th><th class="num">Discount</th>
                @if ($isOwner)<th class="num">COGS</th>@endif
                <th class="num">Total</th><th class="num">Returned</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $t)
                <tr>
                    <td>{{ $t['invoice_number'] }}</td>
                    <td>{{ $t['date'] }}</td>
                    <td>{{ \App\Support\ArabicPdfText::shape($t['customer_name']) }}</td>
                    <td>{{ $t['area_name'] ? \App\Support\ArabicPdfText::shape($t['area_name']) : '—' }}</td>
                    <td>{{ $t['sales_rep_name'] ? \App\Support\ArabicPdfText::shape($t['sales_rep_name']) : '—' }}</td>
                    <td class="num">{{ $t['qty'] }}</td>
                    <td class="num">{{ number_format($t['unit_price']) }}</td>
                    <td class="num">{{ $t['discount_type'] ? ($t['discount_type'] === 'percent' ? $t['discount_value'].'%' : number_format($t['discount_value'])) : '—' }}</td>
                    @if ($isOwner)<td class="num">{{ number_format($t['cogs_total']) }}</td>@endif
                    <td class="num">{{ number_format($t['line_total']) }}</td>
                    <td class="num">{{ $t['returned_qty'] > 0 ? $t['returned_qty'] : '—' }}</td>
                </tr>
                @if (!empty($t['payments']) || !empty($t['returns']))
                <tr class="fm-row">
                    <td colspan="{{ $isOwner ? 11 : 10 }}">
                        {{-- Related financial movements: every payment row linked to
                             this invoice via the existing Payment.invoice_id
                             relationship, shown separately — never collapsed into
                             one "Paid" figure, since one invoice can have multiple
                             payment rows (e.g. a cash payment and a later discount). --}}
                        <table class="fm-table">
                            @foreach ($t['payments'] as $p)
                            <tr><td class="fm-payment">{{ $p['date'] }} — {{ \App\Support\ArabicPdfText::shape($p['method_label']) }} — {{ number_format($p['amount']) }}</td></tr>
                            @endforeach
                            @foreach ($t['returns'] as $r)
                            <tr><td class="fm-return">{{ $r['return_date'] }} — {{ $r['return_number'] }} — {{ $r['qty'] }} &times; {{ number_format($r['value']) }}</td></tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</body>
</html>
