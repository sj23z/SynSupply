<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\SalesRepresentative;
use App\Models\SalesReturn;
use App\Services\CustomerStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin-only management reporting. Area and Sales Representative reports
 * are genuinely hierarchical (Entity -> Customers -> full transaction
 * statement per customer -> aggregate totals), built entirely from
 * CustomerStatementService — the same service the Customer Statement page
 * itself uses — so a customer's numbers can never disagree between their
 * own statement and their appearance inside an Area/Rep report. Cost/
 * profit columns live only on the Product report (Owner-gated) and the
 * Profitability view, never anywhere else here.
 */
class ReportController extends Controller
{
    public function __construct(private CustomerStatementService $statements)
    {
    }

    private function dateRange(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->date('from')) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->date('to')) : now()->endOfDay();

        return [$from, $to];
    }

    /**
     * Summary-row figures for the Customers list — a lighter-weight shape
     * than the full statement, used only for that one summary table.
     */
    private function customerFinancials(Customer $c, Carbon $from, Carbon $to): array
    {
        $sales = $c->invoices()->where('status', 'finalized')->whereBetween('invoice_date', [$from, $to])->sum('grand_total');
        $returns = $c->salesReturns()->where('status', 'finalized')->whereBetween('return_date', [$from, $to])->sum('total_value');
        $payments = $c->payments()->whereBetween('payment_date', [$from, $to])->sum('amount');

        return [
            'id' => $c->id,
            'name' => $c->name,
            'gross_sales' => $sales,
            'returns' => $returns,
            'net_sales' => $sales - $returns,
            'payments' => $payments,
            'outstanding_balance' => $c->outstandingBalance(),
        ];
    }

    /**
     * The actual hierarchical payload: one full statement (lines + opening/
     * closing balance + per-method totals) per customer, from the exact
     * same CustomerStatementService a standalone Customer Statement uses.
     */
    private function hierarchicalCustomers(\Illuminate\Support\Collection $customers, Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        return $customers->map(function (Customer $c) use ($from, $to) {
            $statement = $this->statements->build($c, $from, $to);
            $totals = $this->statements->totals($statement['lines']);

            return [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'address' => $c->address,
                'lines' => $statement['lines'],
                'opening_balance' => $statement['opening'],
                'closing_balance' => $statement['closing'],
                'outstanding_balance' => $c->outstandingBalance(),
                'totals' => $totals,
            ];
        });
    }

    public function profitability(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);

        $finalizedInvoiceIds = Invoice::where('status', 'finalized')->whereBetween('invoice_date', [$from, $to])->pluck('id');

        $netSales = Invoice::whereIn('id', $finalizedInvoiceIds)->sum('grand_total');
        $cogs = InvoiceItem::whereIn('invoice_id', $finalizedInvoiceIds)->sum('cogs_total');
        $returnsValue = SalesReturn::where('status', 'finalized')->whereBetween('return_date', [$from, $to])->sum('total_value');
        $grossProfit = $netSales - $cogs - $returnsValue;
        $operatingExpenses = Expense::whereBetween('date', [$from, $to])->sum('amount');
        $operatingProfit = $grossProfit - $operatingExpenses;

        return Inertia::render('Reports/Profitability', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'netSales' => $netSales,
            'cogs' => $cogs,
            'returnsValue' => $returnsValue,
            'grossProfit' => $grossProfit,
            'operatingExpenses' => $operatingExpenses,
            'operatingProfit' => $operatingProfit,
        ]);
    }

    public function customers(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);

        $activeCustomerIds = Customer::query()->get()
            ->map(fn (Customer $c) => $this->customerFinancials($c, $from, $to))
            ->filter(fn ($r) => $r['gross_sales'] > 0 || $r['outstanding_balance'] != 0)
            ->pluck('id');

        $customers = $this->hierarchicalCustomers(Customer::whereIn('id', $activeCustomerIds)->get(), $from, $to);

        return Inertia::render('Reports/Customers', [
            'customers' => $customers->values(),
            'totals' => [
                'customers_count' => $customers->count(),
                ...$this->statements->sumTotals($customers->pluck('totals')),
                'outstanding_balance' => $customers->sum('outstanding_balance'),
            ],
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function customersPdf(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $activeCustomerIds = Customer::query()->get()
            ->map(fn (Customer $c) => $this->customerFinancials($c, $from, $to))
            ->filter(fn ($r) => $r['gross_sales'] > 0 || $r['outstanding_balance'] != 0)
            ->pluck('id');

        $customers = $this->hierarchicalCustomers(Customer::whereIn('id', $activeCustomerIds)->get(), $from, $to);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report-customers', [
            'customers' => $customers->values(),
            'totals' => [
                'customers_count' => $customers->count(),
                ...$this->statements->sumTotals($customers->pluck('totals')),
                'outstanding_balance' => $customers->sum('outstanding_balance'),
            ],
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'arabicFontPath' => resource_path('fonts/NotoSansArabic.ttf'),
        ], [], 'UTF-8')
            ->setPaper('a4')
            ->setOption(['isFontSubsettingEnabled' => false])
            ->stream('customers-report.pdf');
    }

    public function salesRepresentatives(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);

        $rows = SalesRepresentative::query()->withCount('customers')->get()->map(function (SalesRepresentative $rep) use ($from, $to) {
            $customerIds = $rep->customers()->pluck('id');
            $sales = Invoice::whereIn('customer_id', $customerIds)->where('status', 'finalized')->whereBetween('invoice_date', [$from, $to])->sum('grand_total');
            $returns = SalesReturn::whereIn('customer_id', $customerIds)->where('status', 'finalized')->whereBetween('return_date', [$from, $to])->sum('total_value');
            $payments = \App\Models\Payment::whereIn('customer_id', $customerIds)->whereBetween('payment_date', [$from, $to])->sum('amount');

            return [
                'id' => $rep->id,
                'name' => $rep->name,
                'customers_count' => $rep->customers_count,
                'gross_sales' => $sales,
                'returns' => $returns,
                'net_sales' => $sales - $returns,
                'payments_collected' => $payments,
            ];
        });

        return Inertia::render('Reports/SalesRepresentatives', ['rows' => $rows, 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    public function salesRepresentativeDetail(Request $request, SalesRepresentative $salesRepresentative): Response
    {
        [$from, $to] = $this->dateRange($request);

        return Inertia::render('Reports/SalesRepresentativeDetail', $this->salesRepDetailData($salesRepresentative, $from, $to));
    }

    public function salesRepresentativeDetailPdf(Request $request, SalesRepresentative $salesRepresentative)
    {
        [$from, $to] = $this->dateRange($request);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report-sales-representative', [
            ...$this->salesRepDetailData($salesRepresentative, $from, $to),
            'arabicFontPath' => resource_path('fonts/NotoSansArabic.ttf'),
        ], [], 'UTF-8')
            ->setPaper('a4')
            ->setOption(['isFontSubsettingEnabled' => false])
            ->stream("sales-rep-report-{$salesRepresentative->id}.pdf");
    }

    private function salesRepDetailData(SalesRepresentative $rep, Carbon $from, Carbon $to): array
    {
        $customers = $this->hierarchicalCustomers($rep->customers()->get(), $from, $to);

        return [
            'rep' => ['id' => $rep->id, 'name' => $rep->name, 'phone' => $rep->phone],
            'customers' => $customers->values(),
            'totals' => [
                'customers_count' => $customers->count(),
                ...$this->statements->sumTotals($customers->pluck('totals')),
                'outstanding_balance' => $customers->sum('outstanding_balance'),
            ],
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }

    /** Shared by products() and productDetail() — cogs/gross_profit keys are entirely absent from the array for non-Owner, not merely null. */
    private function productRow($product, $finalizedInvoiceIds, bool $isOwner): array
    {
        $items = InvoiceItem::where('product_id', $product->id)->whereIn('invoice_id', $finalizedInvoiceIds)->get();
        $unitsSold = $items->sum('qty');
        $grossSales = $items->sum('line_total');
        $returnedQty = \App\Models\SalesReturnItem::where('product_id', $product->id)
            ->whereIn('sales_return_id', SalesReturn::where('status', 'finalized')->pluck('id'))
            ->sum('qty');

        $row = [
            'id' => $product->id,
            'name' => $product->name,
            'units_sold' => $unitsSold,
            'returned_qty' => $returnedQty,
            'net_qty_sold' => $unitsSold - $returnedQty,
            'gross_sales' => $grossSales,
            'current_stock' => $product->cached_stock_qty,
        ];

        if ($isOwner) {
            $cogs = $items->sum('cogs_total');
            $row['cogs'] = $cogs;
            $row['gross_profit'] = $grossSales - $cogs;
        }

        return $row;
    }

    public function products(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);
        $isOwner = $request->user()->isOwner();

        $finalizedInvoiceIds = Invoice::where('status', 'finalized')->whereBetween('invoice_date', [$from, $to])->pluck('id');

        $rows = Product::query()->get()
            ->map(fn ($product) => $this->productRow($product, $finalizedInvoiceIds, $isOwner))
            ->filter(fn ($r) => $r['units_sold'] > 0)->values();

        return Inertia::render('Reports/Products', ['rows' => $rows, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'isOwner' => $isOwner]);
    }

    public function productDetail(Request $request, Product $product): Response
    {
        [$from, $to] = $this->dateRange($request);
        $isOwner = $request->user()->isOwner();

        return Inertia::render('Reports/ProductDetail', $this->productDetailData($product, $from, $to, $isOwner));
    }

    public function productDetailPdf(Request $request, Product $product)
    {
        [$from, $to] = $this->dateRange($request);
        $isOwner = $request->user()->isOwner();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report-product', [
            ...$this->productDetailData($product, $from, $to, $isOwner),
            'arabicFontPath' => resource_path('fonts/NotoSansArabic.ttf'),
        ], [], 'UTF-8')
            ->setPaper('a4')
            ->setOption(['isFontSubsettingEnabled' => false])
            ->stream("product-report-{$product->id}.pdf");
    }

    private function productDetailData($product, Carbon $from, Carbon $to, bool $isOwner): array
    {
        $finalizedInvoiceIds = Invoice::where('status', 'finalized')->whereBetween('invoice_date', [$from, $to])->pluck('id');

        $items = InvoiceItem::where('product_id', $product->id)->whereIn('invoice_id', $finalizedInvoiceIds)
            ->with([
                'invoice:id,invoice_number,invoice_date,customer_id,sales_rep_id',
                'invoice.customer:id,name,area_id',
                'invoice.customer.area:id,name',
                'invoice.salesRepresentative:id,name',
            ])
            ->get()
            ->map(function ($item) use ($isOwner) {
                $row = [
                    'invoice_number' => $item->invoice->invoice_number,
                    'date' => $item->invoice->invoice_date->toDateString(),
                    'customer_name' => $item->invoice->customer->name,
                    'area_name' => $item->invoice->customer->area?->name,
                    'sales_rep_name' => $item->invoice->salesRepresentative?->name,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'discount_type' => $item->discount_type,
                    'discount_value' => $item->discount_value,
                    'line_total' => $item->line_total,
                    'invoice_payment_status' => $item->invoice->payment_status,
                    // "Related financial movements... using the actual
                    // existing Payment.invoice_id relationship": every
                    // payment row linked to this line's invoice, shown
                    // separately — one invoice can have multiple payment
                    // rows (e.g. a cash payment, a later bank transfer,
                    // and a discount), and they are never collapsed into
                    // a single "Paid" figure.
                    'payments' => $this->statements->paymentsForInvoice($item->invoice_id),
                    // Full return detail (return number, date, qty,
                    // value) for this specific line item, not just a
                    // bare count.
                    'returns' => $this->statements->returnDetailsFor($item->id),
                    'returned_qty' => $this->statements->returnedQuantityFor($item->id),
                ];
                if ($isOwner) {
                    $row['cogs_total'] = $item->cogs_total;
                }

                return $row;
            });

        return [
            'product' => $this->productRow($product, $finalizedInvoiceIds, $isOwner),
            'transactions' => $items,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'isOwner' => $isOwner,
        ];
    }

    public function areas(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);

        $rows = Area::query()->withCount('customers')->get()->map(function (Area $area) use ($from, $to) {
            $customerIds = $area->customers()->pluck('id');
            $sales = Invoice::whereIn('customer_id', $customerIds)->where('status', 'finalized')->whereBetween('invoice_date', [$from, $to])->sum('grand_total');
            $returns = SalesReturn::whereIn('customer_id', $customerIds)->where('status', 'finalized')->whereBetween('return_date', [$from, $to])->sum('total_value');
            $outstanding = Customer::whereIn('id', $customerIds)->get()->sum(fn ($c) => $c->outstandingBalance());

            return [
                'id' => $area->id,
                'name' => $area->name,
                'customers_count' => $area->customers_count,
                'gross_sales' => $sales,
                'returns' => $returns,
                'net_sales' => $sales - $returns,
                'outstanding_receivables' => $outstanding,
            ];
        });

        return Inertia::render('Reports/Areas', ['rows' => $rows, 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    public function areaDetail(Request $request, Area $area): Response
    {
        [$from, $to] = $this->dateRange($request);

        return Inertia::render('Reports/AreaDetail', $this->areaDetailData($area, $from, $to));
    }

    public function areaDetailPdf(Request $request, Area $area)
    {
        [$from, $to] = $this->dateRange($request);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report-area', [
            ...$this->areaDetailData($area, $from, $to),
            'arabicFontPath' => resource_path('fonts/NotoSansArabic.ttf'),
        ], [], 'UTF-8')
            ->setPaper('a4')
            ->setOption(['isFontSubsettingEnabled' => false])
            ->stream("area-report-{$area->id}.pdf");
    }

    private function areaDetailData(Area $area, Carbon $from, Carbon $to): array
    {
        $customers = $this->hierarchicalCustomers($area->customers()->get(), $from, $to);

        return [
            'area' => ['id' => $area->id, 'name' => $area->name],
            'customers' => $customers->values(),
            'totals' => [
                'customers_count' => $customers->count(),
                ...$this->statements->sumTotals($customers->pluck('totals')),
                'outstanding_balance' => $customers->sum('outstanding_balance'),
            ],
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }
}
