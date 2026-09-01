<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerVisit;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\SalesReturn;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * "Cash on hand" — the current, cumulative balance of cash physically
     * in the cash box, not a period figure like the other dashboard
     * metrics. Deliberately NOT: total sales, total invoices, total
     * collections, profit, or outstanding balance (all explicitly ruled
     * out by the approved requirement).
     *
     * Per the client's authoritative business-rule addendum: Cash, Bank
     * Transfer, and Other are ALL actual money received and increase
     * this figure identically — UTU's real-world practice is that "أخرى"
     * still represents funds that arrived, just through a channel that
     * doesn't fit the other two labels, and bank transfers are real
     * received funds even though they don't sit in the physical cash
     * box (the metric is named "Cash on Hand / Net Cash" precisely to
     * cover both). Only Settlement and Discount are excluded — both are
     * balance-adjustment mechanisms (a negotiated write-off), not money
     * that was ever received. Customer::outstandingBalance() and
     * CollectionController's cached invoice status are unaffected by
     * this distinction and correctly count every method's amount
     * toward the customer's balance regardless.
     *
     * DOCUMENTED ASSUMPTION: the current `expenses` table has no field
     * distinguishing a cash expense from a non-cash one (no payment
     * method, no equivalent column) — inspected directly, confirmed
     * absent. Every expense that has ever been recorded in this system
     * was necessarily entered assuming cash payment (there is no other
     * mechanism to model an expense today), so this treats every expense
     * as a cash outflow. This is the smallest-safe-change reading of the
     * requirement's own "if unclear... document the assumption"
     * instruction — it does not invent a new expense-payment-method
     * concept or schema change to resolve the ambiguity.
     */
    private function cashOnHand(): int
    {
        return \App\Models\Payment::whereIn('method', ['cash', 'bank_transfer', 'other'])->sum('amount')
            - Expense::sum('amount');
    }

    private function monthlyExpenses(): int
    {
        return Expense::where('date', '>=', now()->startOfMonth())->sum('amount');
    }

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isSalesRep()) {
            return $this->salesRepDashboard($user);
        }

        if ($user->isOwner()) {
            return $this->ownerDashboard();
        }

        return $user->isAdmin() ? $this->managerDashboard() : $this->staffDashboard();
    }

    private function staffDashboard(): Response
    {
        return Inertia::render('Dashboard', [
            'role' => 'staff',
            'salesToday' => Invoice::where('status', 'finalized')->whereDate('invoice_date', now())->sum('grand_total'),
            'outstandingReceivables' => Customer::get()->sum(fn ($c) => $c->outstandingBalance()),
            'lowStockProducts' => Product::whereColumn('cached_stock_qty', '<=', 'low_stock_threshold')->where('cached_stock_qty', '>', 0)->limit(5)->get(['id', 'name', 'cached_stock_qty']),
            'recentInvoices' => Invoice::with('customer:id,name')->orderByDesc('id')->limit(5)->get(['id', 'invoice_number', 'customer_id', 'status', 'grand_total']),
        ]);
    }

    /**
     * Admin (Manager tier): the same operational figures Owner sees, but
     * with every profit/COGS/inventory-value figure never computed or
     * included in the response at all — not merely omitted from the Vue
     * template. Per the approved Phase 15B correction, this is a real
     * behavior change from before Owner existed (Admin's dashboard used
     * to include these).
     */
    private function managerDashboard(): Response
    {
        $monthStart = now()->startOfMonth();

        $salesToday = Invoice::where('status', 'finalized')->whereDate('invoice_date', now())->sum('grand_total');
        $salesMonth = Invoice::where('status', 'finalized')->where('invoice_date', '>=', $monthStart)->sum('grand_total');
        $returnsMonth = SalesReturn::where('status', 'finalized')->where('return_date', '>=', $monthStart)->sum('total_value');

        return Inertia::render('Dashboard', [
            'role' => 'admin',
            'salesToday' => $salesToday,
            'salesMonth' => $salesMonth,
            'netSalesMonth' => $salesMonth - $returnsMonth,
            'outstandingReceivables' => Customer::get()->sum(fn ($c) => $c->outstandingBalance()),
            'cashOnHand' => $this->cashOnHand(),
            'monthlyExpenses' => $this->monthlyExpenses(),
            'negativeStockCount' => Product::where('cached_stock_qty', '<', 0)->count(),
            'lowStockCount' => Product::whereColumn('cached_stock_qty', '<=', 'low_stock_threshold')->where('cached_stock_qty', '>', 0)->count(),
            'expiringSoonCount' => \App\Models\StockBatch::where('quantity_remaining', '>', 0)->whereNotNull('expiry_date')->where('expiry_date', '<=', now()->addDays(90))->count(),
            'topProducts' => InvoiceItem::whereHas('invoice', fn ($q) => $q->where('status', 'finalized')->where('invoice_date', '>=', $monthStart))
                ->selectRaw('product_id, SUM(qty) as total_qty')->groupBy('product_id')->orderByDesc('total_qty')->limit(5)
                ->with('product:id,name')->get(),
            'topCustomers' => Invoice::where('status', 'finalized')->where('invoice_date', '>=', $monthStart)
                ->selectRaw('customer_id, SUM(grand_total) as total')->groupBy('customer_id')->orderByDesc('total')->limit(5)
                ->with('customer:id,name')->get(),
        ]);
    }

    private function ownerDashboard(): Response
    {
        $today = now();
        $monthStart = now()->startOfMonth();

        $salesToday = Invoice::where('status', 'finalized')->whereDate('invoice_date', $today)->sum('grand_total');
        $salesMonth = Invoice::where('status', 'finalized')->where('invoice_date', '>=', $monthStart)->sum('grand_total');
        $returnsMonth = SalesReturn::where('status', 'finalized')->where('return_date', '>=', $monthStart)->sum('total_value');
        $cogsMonth = InvoiceItem::whereHas('invoice', fn ($q) => $q->where('status', 'finalized')->where('invoice_date', '>=', $monthStart))->sum('cogs_total');
        $expensesMonth = $this->monthlyExpenses();
        $grossProfit = $salesMonth - $returnsMonth - $cogsMonth;

        $inventoryValue = Product::with(['stockBatches' => fn ($q) => $q->where('quantity_remaining', '>', 0)])->get()
            ->sum(fn ($p) => $p->stockBatches->sum(fn ($b) => $b->quantity_remaining * $b->unit_cost));

        return Inertia::render('Dashboard', [
            'role' => 'owner',
            'salesToday' => $salesToday,
            'salesMonth' => $salesMonth,
            'netSalesMonth' => $salesMonth - $returnsMonth,
            'outstandingReceivables' => Customer::get()->sum(fn ($c) => $c->outstandingBalance()),
            'cashOnHand' => $this->cashOnHand(),
            'monthlyExpenses' => $expensesMonth,
            'inventoryValue' => $inventoryValue,
            'grossProfitMonth' => $grossProfit,
            'operatingExpensesMonth' => $expensesMonth,
            'operatingProfitMonth' => $grossProfit - $expensesMonth,
            'negativeStockCount' => Product::where('cached_stock_qty', '<', 0)->count(),
            'lowStockCount' => Product::whereColumn('cached_stock_qty', '<=', 'low_stock_threshold')->where('cached_stock_qty', '>', 0)->count(),
            'expiringSoonCount' => \App\Models\StockBatch::where('quantity_remaining', '>', 0)->whereNotNull('expiry_date')->where('expiry_date', '<=', now()->addDays(90))->count(),
            'topProducts' => InvoiceItem::whereHas('invoice', fn ($q) => $q->where('status', 'finalized')->where('invoice_date', '>=', $monthStart))
                ->selectRaw('product_id, SUM(qty) as total_qty')->groupBy('product_id')->orderByDesc('total_qty')->limit(5)
                ->with('product:id,name')->get(),
            'topCustomers' => Invoice::where('status', 'finalized')->where('invoice_date', '>=', $monthStart)
                ->selectRaw('customer_id, SUM(grand_total) as total')->groupBy('customer_id')->orderByDesc('total')->limit(5)
                ->with('customer:id,name')->get(),
        ]);
    }

    private function salesRepDashboard($user): Response
    {
        $rep = $user->salesRepresentative;

        if (! $rep) {
            return Inertia::render('Dashboard', ['role' => 'sales_rep', 'unlinked' => true]);
        }

        $myCustomers = Customer::where('assigned_rep_id', $rep->id)->orderBy('name')->get(['id', 'name', 'phone', 'active']);

        $upcomingFollowUps = CustomerVisit::where('sales_representative_id', $rep->id)
            ->where('follow_up_status', 'pending')->whereNotNull('follow_up_date')
            ->with('customer:id,name')->orderBy('follow_up_date')->limit(10)->get();

        $recentVisits = CustomerVisit::where('sales_representative_id', $rep->id)
            ->with('customer:id,name')->orderByDesc('visit_date')->limit(10)->get();

        $recentInvoices = Invoice::whereIn('customer_id', $myCustomers->pluck('id'))
            ->orderByDesc('id')->limit(10)->get(['id', 'invoice_number', 'customer_id', 'status', 'payment_status'])
            ->load('customer:id,name');

        return Inertia::render('Dashboard', [
            'role' => 'sales_rep',
            'repName' => $rep->name,
            'myCustomersCount' => $myCustomers->count(),
            'myCustomers' => $myCustomers,
            'upcomingFollowUps' => $upcomingFollowUps,
            'recentVisits' => $recentVisits,
            'recentInvoices' => $recentInvoices,
        ]);
    }
}
