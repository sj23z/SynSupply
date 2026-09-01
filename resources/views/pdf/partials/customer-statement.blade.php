{{--
    Shared statement table partial — same structure used by the standalone
    Customer Statement PDF and, per customer, by the Area/Sales-Rep PDFs.
    Expects: $lines, $opening, $closing already computed by
    CustomerStatementService (never recalculated here).
--}}
@php $typeLabels = ['invoice' => 'فاتورة', 'return' => 'مردود', 'payment' => 'دفعة', 'settlement' => 'تسوية', 'discount' => 'خصم']; @endphp
<table class="stmt-table">
    <thead>
        <tr>
            <th>{{ \App\Support\ArabicPdfText::shape('التاريخ') }}</th>
            <th>{{ \App\Support\ArabicPdfText::shape('البيان') }}</th>
            <th>{{ \App\Support\ArabicPdfText::shape('مدين') }}</th>
            <th>{{ \App\Support\ArabicPdfText::shape('دائن') }}</th>
            <th>{{ \App\Support\ArabicPdfText::shape('الرصيد') }}</th>
        </tr>
    </thead>
    <tbody>
        <tr class="stmt-totals">
            <td colspan="4">{{ \App\Support\ArabicPdfText::shape('الرصيد الافتتاحي') }}</td>
            <td>{{ number_format($opening) }}</td>
        </tr>
        @foreach ($lines as $line)
        <tr class="stmt-type-{{ $line['type'] }}">
            <td>{{ $line['date'] }}</td>
            <td><span class="stmt-type-label">{{ \App\Support\ArabicPdfText::shape($typeLabels[$line['type']]) }}</span> — {{ \App\Support\ArabicPdfText::shape($line['label']) }}</td>
            <td>{{ $line['debit'] ? number_format($line['debit']) : '' }}</td>
            <td>{{ $line['credit'] ? number_format($line['credit']) : '' }}</td>
            <td>{{ number_format($line['running_balance']) }}</td>
        </tr>
        @if (!empty($line['detail']['items']))
        <tr>
            <td colspan="5" style="padding: 0 0 4px 0; border-bottom: none;">
                <table class="stmt-detail-table">
                    <thead>
                        @if ($line['type'] === 'invoice')
                        <tr><th>{{ \App\Support\ArabicPdfText::shape('المنتج') }}</th><th>{{ \App\Support\ArabicPdfText::shape('الكمية') }}</th><th>{{ \App\Support\ArabicPdfText::shape('السعر') }}</th><th>{{ \App\Support\ArabicPdfText::shape('الخصم') }}</th><th>{{ \App\Support\ArabicPdfText::shape('الإجمالي') }}</th></tr>
                        @else
                        <tr><th>{{ \App\Support\ArabicPdfText::shape('المنتج') }}</th><th>{{ \App\Support\ArabicPdfText::shape('الكمية') }}</th><th>{{ \App\Support\ArabicPdfText::shape('القيمة') }}</th></tr>
                        @endif
                    </thead>
                    <tbody>
                        @foreach ($line['detail']['items'] as $item)
                        <tr>
                            <td>{{ \App\Support\ArabicPdfText::shape($item['product_name']) }}</td>
                            <td>{{ $item['qty'] }}</td>
                            @if ($line['type'] === 'invoice')
                                <td>{{ number_format($item['unit_price']) }}</td>
                                <td>{{ $item['discount_type'] ? ($item['discount_type'] === 'percent' ? $item['discount_value'].'%' : number_format($item['discount_value'])) : '—' }}</td>
                                <td>{{ number_format($item['line_total']) }}</td>
                            @else
                                <td>{{ number_format($item['value']) }}</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
        @endif
        @endforeach
        <tr class="stmt-totals">
            <td colspan="4">{{ \App\Support\ArabicPdfText::shape('الرصيد الختامي') }}</td>
            <td>{{ number_format($closing) }}</td>
        </tr>
    </tbody>
</table>
