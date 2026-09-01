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
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { text-align: left; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #6B6B66; background: #F8F7F4; padding: 6px 8px; border-bottom: 1.5px solid #C6A85A; }
    td { padding: 5px 8px; border-bottom: 1px solid #EDEDEB; font-size: 10px; }
    th.num, td.num { text-align: right; }
    .totals td { font-weight: bold; border-top: 1.5px solid #17171A; }

    /* Per-customer hierarchical statement block — reuses the exact
       red/blue/green/neutral semantics already proven on the standalone
       Customer Statement PDF (resources/views/pdf/statement.blade.php),
       scoped under a stmt- prefix so it can't collide with the summary
       table styles above. dir="rtl" is applied only to this block (each
       customer's own statement content is Arabic-labeled), not the
       document as a whole — the outer report stays English/LTR,
       consistent with the existing invoice/return PDF convention this
       template already followed. */
    .customer-block { page-break-inside: avoid; margin-top: 16px; border: 1px solid #EDEDEB; border-radius: 3px; padding: 10px; }
    .customer-name { font-size: 12px; font-weight: bold; }
    .customer-meta { font-size: 9px; color: #6B6B66; margin-top: 2px; }
    .stmt-wrap { direction: rtl; margin-top: 6px; }
    .stmt-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .stmt-table th, .stmt-table td { padding: 4px 6px; border-bottom: 1px solid #EDEDEB; text-align: right; font-size: 9px; }
    .stmt-table th { background: #F6F6F5; font-size: 8.5px; text-transform: none; color: #6B6B66; }
    .stmt-totals td { font-weight: bold; background: #FBF9F3; }
    .stmt-type-invoice { border-right: 2px solid #8B2E2E; }
    .stmt-type-return { border-right: 2px solid #3E7A4F; }
    .stmt-type-payment { border-right: 2px solid #2E5FA3; }
    .stmt-type-settlement, .stmt-type-discount { border-right: 2px solid #D8D8D4; }
    .stmt-type-label { font-weight: bold; }
    .stmt-type-invoice .stmt-type-label { color: #8B2E2E; }
    .stmt-type-return .stmt-type-label { color: #3E7A4F; }
    .stmt-type-payment .stmt-type-label { color: #2E5FA3; }
    .stmt-type-settlement .stmt-type-label, .stmt-type-discount .stmt-type-label { color: #6B6B66; }
    .stmt-detail-table { margin: 2px 0 4px; width: 100%; }
    .stmt-detail-table th, .stmt-detail-table td { font-size: 8px; padding: 2px 5px; background: #FAFAF9; }
</style>
</head>
<body>
    <div class="header">
        <div class="brand">UTU LTD</div>
        <div class="doc-title">AREA REPORT</div>
        <div class="clear"></div>
        <div class="meta">
            {{ \App\Support\ArabicPdfText::shape($area['name']) }} &middot; {{ $from }} &rarr; {{ $to }}
        </div>
    </div>

    <table>
        <thead>
            <tr><th>Customer</th><th class="num">Gross Sales</th><th class="num">Returns</th><th class="num">Net Sales</th><th class="num">Actual Cash</th><th class="num">Outstanding</th></tr>
        </thead>
        <tbody>
            @foreach ($customers as $c)
                <tr>
                    <td>{{ \App\Support\ArabicPdfText::shape($c['name']) }}</td>
                    <td class="num">{{ number_format($c['totals']['gross_sales']) }}</td>
                    <td class="num">{{ number_format($c['totals']['returns']) }}</td>
                    <td class="num">{{ number_format($c['totals']['net_sales']) }}</td>
                    <td class="num">{{ number_format($c['totals']['actual_cash_received']) }}</td>
                    <td class="num">{{ number_format($c['outstanding_balance']) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td>Total ({{ $totals['customers_count'] }} customers)</td>
                <td class="num">{{ number_format($totals['gross_sales']) }}</td>
                <td class="num">{{ number_format($totals['returns']) }}</td>
                <td class="num">{{ number_format($totals['net_sales']) }}</td>
                <td class="num">{{ number_format($totals['actual_cash_received']) }}</td>
                <td class="num">{{ number_format($totals['outstanding_balance']) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <tbody>
            <tr class="totals">
                <td>Cash</td><td class="num">{{ number_format($totals['cash']) }}</td>
                <td>Bank Transfer</td><td class="num">{{ number_format($totals['bank_transfer']) }}</td>
                <td>Other</td><td class="num">{{ number_format($totals['other']) }}</td>
                <td>Settlement</td><td class="num">{{ number_format($totals['settlement']) }}</td>
                <td>Discount</td><td class="num">{{ number_format($totals['discount']) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- HIERARCHY: every customer's full transaction statement, not just a summary row -->
    @foreach ($customers as $c)
        <div class="customer-block">
            <div class="customer-name">{{ \App\Support\ArabicPdfText::shape($c['name']) }}</div>
            <div class="customer-meta">{{ $c['phone'] }}@if($c['address']) &middot; {{ \App\Support\ArabicPdfText::shape($c['address']) }}@endif</div>
            <div class="stmt-wrap">
                @include('pdf.partials.customer-statement', ['lines' => $c['lines'], 'opening' => $c['opening_balance'], 'closing' => $c['closing_balance']])
            </div>
        </div>
    @endforeach
</body>
</html>
