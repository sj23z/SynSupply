<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectionRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** "Customer Collections" — Admin only (PaymentPolicy). */
class CollectionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['customer:id,name', 'invoice:id,invoice_number'])
            ->when($request->integer('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Collections/Index', [
            'payments' => $payments,
            'filters' => $request->only('customer_id'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Payment::class);

        $customerId = $request->integer('customer_id');

        return Inertia::render('Collections/Create', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'openInvoices' => $customerId
                ? Invoice::where('customer_id', $customerId)->where('status', 'finalized')->get(['id', 'invoice_number', 'grand_total', 'amount_paid_cached'])
                : [],
            'prefillCustomerId' => $customerId ?: null,
        ]);
    }

    public function store(CollectionRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validated();

        $payment = Payment::create([...$data, 'company_id' => $request->user()->company_id, 'created_by' => $request->user()->id]);

        if ($payment->invoice_id) {
            $this->recalculateInvoicePaymentStatus($payment->invoice_id);
        }

        return redirect()->route('collections.index')->with('success', 'تم تسجيل الدفعة بنجاح.');
    }

    public function edit(Payment $payment): Response
    {
        $this->authorize('update', $payment);

        $payment->load('customer:id,name');

        return Inertia::render('Collections/Edit', [
            'payment' => $payment,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'openInvoices' => Invoice::where('customer_id', $payment->customer_id)
                ->where(fn ($q) => $q->where('status', 'finalized')->orWhere('id', $payment->invoice_id))
                ->get(['id', 'invoice_number', 'grand_total', 'amount_paid_cached']),
        ]);
    }

    public function update(CollectionRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $data = $request->validated();
        $oldInvoiceId = $payment->invoice_id;

        DB::transaction(function () use ($payment, $data, $oldInvoiceId) {
            $payment->update($data);

            // Recalculate every invoice this edit could have affected: the
            // one it was linked to before (its cached paid amount must
            // reflect this payment leaving), and the one it's linked to
            // now (must reflect this payment arriving) — the same
            // invoice in the common case where the link didn't change.
            foreach (array_unique(array_filter([$oldInvoiceId, $payment->invoice_id])) as $invoiceId) {
                $this->recalculateInvoicePaymentStatus($invoiceId);
            }
        });

        return redirect()->route('collections.index')->with('success', 'تم تحديث الدفعة بنجاح.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        $invoiceId = $payment->invoice_id;
        $payment->delete();

        if ($invoiceId) {
            $this->recalculateInvoicePaymentStatus($invoiceId);
        }

        return back()->with('success', 'تم حذف الدفعة.');
    }

    private function recalculateInvoicePaymentStatus(int $invoiceId): void
    {
        $invoice = Invoice::find($invoiceId);
        if (! $invoice) {
            return;
        }

        $paid = Payment::where('invoice_id', $invoiceId)->sum('amount');
        $status = $paid <= 0 ? 'unpaid' : ($paid >= $invoice->grand_total ? 'paid' : 'partial');

        $invoice->update(['amount_paid_cached' => $paid, 'payment_status' => $status]);
    }
}
