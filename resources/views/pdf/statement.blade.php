<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<style>
    /*
        Same font/shaping approach as the invoice PDF (see
        resources/views/pdf/invoice.blade.php for the full rationale):
        dompdf does not implement Unicode BIDI reordering or Arabic
        contextual letter shaping, and does not do per-glyph font-family
        fallback — so every piece of text on this page, including the
        static Arabic labels typed directly below (not just the dynamic
        customer name), is routed through ArabicPdfText::shape() and
        rendered in one font that covers both scripts. dir="rtl" here is
        a right-alignment styling choice (this document's own labels are
        Arabic, unlike the invoice's English template) — it is NOT what
        makes the Arabic text render correctly; the shaping is.

        Color coding (red=invoice, blue=payment, green=return, neutral=
        settlement/discount), and the .stmt-* class names below, are
        shared with resources/views/pdf/partials/customer-statement.blade.php
        — the same partial this template includes below is also used,
        unmodified, by the Area and Sales Representative report PDFs, so
        one canonical style/markup produces every hierarchical statement
        in the system.
    */
    @font-face {
        font-family: 'UTUSans';
        src: url('{{ $arabicFontPath }}');
        font-weight: normal;
        font-style: normal;
    }
    @font-face {
        font-family: 'UTUSans';
        src: url('{{ $arabicFontPath }}');
        font-weight: bold;
        font-style: normal;
    }

    body { font-family: 'UTUSans', sans-serif; color: #17171A; font-size: 10.5px; line-height: 1.3; margin: 0; padding: 20px 30px; }
    h1 { font-size: 15px; border-bottom: 2px solid #C6A85A; padding-bottom: 8px; margin: 0 0 4px; }
    .period { color: #6B6B66; font-size: 10px; margin: 0 0 8px; }
    .legend { margin-bottom: 8px; font-size: 9px; color: #6B6B66; }
    .legend span { display: inline-block; margin-left: 12px; }
    .dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; margin-left: 3px; }

    .stmt-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .stmt-table th, .stmt-table td { padding: 5px 7px; border-bottom: 1px solid #EDEDEB; text-align: right; font-size: 10px; }
    .stmt-table th { background: #F6F6F5; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #6B6B66; }
    .stmt-totals td { font-weight: bold; background: #FBF9F3; }
    .stmt-type-invoice { border-right: 3px solid #8B2E2E; }
    .stmt-type-return { border-right: 3px solid #3E7A4F; }
    .stmt-type-payment { border-right: 3px solid #2E5FA3; }
    .stmt-type-settlement, .stmt-type-discount { border-right: 3px solid #D8D8D4; }
    .stmt-type-label { font-weight: bold; }
    .stmt-type-invoice .stmt-type-label { color: #8B2E2E; }
    .stmt-type-return .stmt-type-label { color: #3E7A4F; }
    .stmt-type-payment .stmt-type-label { color: #2E5FA3; }
    .stmt-type-settlement .stmt-type-label, .stmt-type-discount .stmt-type-label { color: #6B6B66; }
    .stmt-detail-table { margin: 2px 0 4px; width: 100%; }
    .stmt-detail-table th, .stmt-detail-table td { font-size: 8.5px; padding: 3px 6px; background: #FAFAF9; }
</style>
</head>
<body>
    <h1>{{ \App\Support\ArabicPdfText::shape('كشف حساب العميل') }} — {{ \App\Support\ArabicPdfText::shape($customer->name) }}</h1>
    <p class="period">{{ \App\Support\ArabicPdfText::shape('من') }} {{ $from->format('Y-m-d') }} {{ \App\Support\ArabicPdfText::shape('إلى') }} {{ $to->format('Y-m-d') }}</p>

    <p class="legend">
        <span><span class="dot" style="background:#8B2E2E"></span>{{ \App\Support\ArabicPdfText::shape('فاتورة') }}</span>
        <span><span class="dot" style="background:#2E5FA3"></span>{{ \App\Support\ArabicPdfText::shape('دفعة') }}</span>
        <span><span class="dot" style="background:#3E7A4F"></span>{{ \App\Support\ArabicPdfText::shape('مردود') }}</span>
        <span><span class="dot" style="background:#D8D8D4"></span>{{ \App\Support\ArabicPdfText::shape('تسوية / خصم') }}</span>
    </p>

    @include('pdf.partials.customer-statement', ['lines' => $lines, 'opening' => $opening, 'closing' => $closing])
</body>
</html>
