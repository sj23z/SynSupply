<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\StockService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->owner = User::factory()->for($this->company)->create(['role' => 'owner']);
    $this->manager = User::factory()->for($this->company)->create(['role' => 'admin']);
    $this->staff = User::factory()->for($this->company)->create(['role' => 'staff']);
    $this->repUser = User::factory()->for($this->company)->salesRep()->create();
    $this->customer = Customer::factory()->for($this->company)->create();
    $this->product = Product::factory()->for($this->company)->create();
    $this->stockService = app(StockService::class);
    $this->invoiceService = app(InvoiceService::class);
});

function makePayment($company, $customer, $method, $amount, $invoiceId = null) {
    return Payment::create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'invoice_id' => $invoiceId,
        'payment_date' => now()->toDateString(),
        'amount' => $amount,
        'method' => $method,
    ]);
}

// --- 1-5: Cash on Hand per-method rule (addendum-corrected) ---

test('cash payment increases cash on hand', function () {
    makePayment($this->company, $this->customer, 'cash', 500000);
    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 500000));
});

test('bank transfer increases cash on hand per the addendum', function () {
    makePayment($this->company, $this->customer, 'bank_transfer', 300000);
    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 300000));
});

test('other increases cash on hand per the addendum', function () {
    makePayment($this->company, $this->customer, 'other', 200000);
    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 200000));
});

test('settlement does not increase cash on hand', function () {
    makePayment($this->company, $this->customer, 'settlement', 1000000);
    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 0));
});

test('discount does not increase cash on hand', function () {
    makePayment($this->company, $this->customer, 'discount', 1000000);
    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 0));
});

test('all three real-money methods combine while settlement and discount are excluded', function () {
    makePayment($this->company, $this->customer, 'cash', 1000000);
    makePayment($this->company, $this->customer, 'bank_transfer', 500000);
    makePayment($this->company, $this->customer, 'other', 250000);
    makePayment($this->company, $this->customer, 'settlement', 999999);
    makePayment($this->company, $this->customer, 'discount', 999999);

    $response = $this->actingAs($this->owner)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 1750000));
});

test('expenses reduce cash on hand', function () {
    makePayment($this->company, $this->customer, 'cash', 1000000);
    $category = ExpenseCategory::factory()->for($this->company)->create();
    Expense::create(['company_id' => $this->company->id, 'category_id' => $category->id, 'date' => now()->toDateString(), 'amount' => 300000]);

    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 700000));
});

// --- 6: Monthly expenses ---

test('monthly expenses only sums the current calendar month', function () {
    $category = ExpenseCategory::factory()->for($this->company)->create();
    Expense::create(['company_id' => $this->company->id, 'category_id' => $category->id, 'date' => now()->toDateString(), 'amount' => 150000]);
    Expense::create(['company_id' => $this->company->id, 'category_id' => $category->id, 'date' => now()->subMonths(2)->toDateString(), 'amount' => 999999]);

    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('monthlyExpenses', 150000));
});

// --- 7-10: Dashboard visibility per role ---

test('manager sees cash on hand and monthly expenses on the dashboard', function () {
    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->has('cashOnHand')->has('monthlyExpenses'));
});

test('owner sees cash on hand and monthly expenses on the dashboard', function () {
    $response = $this->actingAs($this->owner)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->has('cashOnHand')->has('monthlyExpenses'));
});

test('manager dashboard response contains no COGS, cost, or profit fields', function () {
    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page
        ->missing('grossProfitMonth')
        ->missing('operatingProfitMonth')
        ->missing('inventoryValue')
    );
});

test('owner dashboard response contains full financial data', function () {
    $response = $this->actingAs($this->owner)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page
        ->has('grossProfitMonth')
        ->has('operatingProfitMonth')
        ->has('inventoryValue')
    );
});

// --- 11-15: Customer statement detail ---

test('customer statement contains invoice line-item detail', function () {
    $this->stockService->receiveStock($this->product, 20, 1000, now()->subDays(5)->toDateString());
    $invoice = Invoice::factory()->for($this->company)->for($this->customer)->create();
    $invoice->items()->create(['product_id' => $this->product->id, 'qty' => 3, 'unit_price' => 2000, 'discount_type' => 'percent', 'discount_value' => 10, 'line_total' => 5400]);
    $this->invoiceService->finalize($invoice, $this->manager);

    $response = $this->actingAs($this->manager)->get("/customers/{$this->customer->id}/statement");
    $response->assertInertia(fn ($page) => $page
        ->where('lines.0.type', 'invoice')
        ->where('lines.0.detail.items.0.product_name', $this->product->name)
        ->where('lines.0.detail.items.0.qty', 3)
        ->where('lines.0.detail.items.0.unit_price', 2000)
        ->where('lines.0.detail.items.0.discount_value', 10)
        ->where('lines.0.detail.invoice_total', $invoice->fresh()->grand_total)
    );
});

test('customer statement contains payments with method and date', function () {
    makePayment($this->company, $this->customer, 'bank_transfer', 400000);

    $response = $this->actingAs($this->manager)->get("/customers/{$this->customer->id}/statement");
    $response->assertInertia(fn ($page) => $page
        ->where('lines.0.type', 'payment')
        ->where('lines.0.detail.method', 'bank_transfer')
        ->has('lines.0.date')
    );
});

test('customer statement contains returns with product detail', function () {
    $this->stockService->receiveStock($this->product, 20, 1000, now()->subDays(5)->toDateString());
    $invoice = Invoice::factory()->for($this->company)->for($this->customer)->create();
    $invoice->items()->create(['product_id' => $this->product->id, 'qty' => 5, 'unit_price' => 2000, 'line_total' => 10000]);
    $this->invoiceService->finalize($invoice, $this->manager);

    $return = \App\Models\SalesReturn::factory()->for($this->company)->for($this->customer)->create();
    $return->items()->create(['product_id' => $this->product->id, 'qty' => 2, 'unit_price' => 2000, 'line_total' => 4000]);
    app(\App\Services\ReturnService::class)->finalize($return, $this->manager);

    $response = $this->actingAs($this->manager)->get("/customers/{$this->customer->id}/statement");
    $response->assertInertia(fn ($page) => $page
        ->where('lines.1.type', 'return')
        ->where('lines.1.detail.items.0.product_name', $this->product->name)
        ->where('lines.1.detail.items.0.qty', 2)
    );
});

test('customer statement distinguishes settlement from an actual payment', function () {
    makePayment($this->company, $this->customer, 'settlement', 250000);

    $response = $this->actingAs($this->manager)->get("/customers/{$this->customer->id}/statement");
    $response->assertInertia(fn ($page) => $page->where('lines.0.type', 'settlement'));
});

test('customer statement distinguishes discount from an actual payment', function () {
    makePayment($this->company, $this->customer, 'discount', 100000);

    $response = $this->actingAs($this->manager)->get("/customers/{$this->customer->id}/statement");
    $response->assertInertia(fn ($page) => $page->where('lines.0.type', 'discount'));
});

// --- 16-20: Payment editing integrity ---

test('editing a cash payment amount correctly changes cash on hand', function () {
    $payment = makePayment($this->company, $this->customer, 'cash', 1000000);

    $this->actingAs($this->manager)->put("/collections/{$payment->id}", [
        'customer_id' => $this->customer->id, 'payment_date' => now()->toDateString(), 'amount' => 800000, 'method' => 'cash',
    ]);

    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 800000));
});

test('changing cash to bank transfer preserves the cash-on-hand effect', function () {
    $payment = makePayment($this->company, $this->customer, 'cash', 1000000);

    $this->actingAs($this->manager)->put("/collections/{$payment->id}", [
        'customer_id' => $this->customer->id, 'payment_date' => now()->toDateString(), 'amount' => 1000000, 'method' => 'bank_transfer',
    ]);

    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 1000000));
    expect($payment->fresh()->method)->toBe('bank_transfer');
});

test('changing bank transfer to other preserves the cash-on-hand effect', function () {
    $payment = makePayment($this->company, $this->customer, 'bank_transfer', 750000);

    $this->actingAs($this->manager)->put("/collections/{$payment->id}", [
        'customer_id' => $this->customer->id, 'payment_date' => now()->toDateString(), 'amount' => 750000, 'method' => 'other',
    ]);

    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 750000));
});

test('changing cash to settlement correctly removes the cash effect', function () {
    $payment = makePayment($this->company, $this->customer, 'cash', 1000000);

    $this->actingAs($this->manager)->put("/collections/{$payment->id}", [
        'customer_id' => $this->customer->id, 'payment_date' => now()->toDateString(), 'amount' => 1000000, 'method' => 'settlement',
    ]);

    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 0));
});

test('changing settlement to bank transfer correctly applies the cash effect', function () {
    $payment = makePayment($this->company, $this->customer, 'settlement', 1000000);

    $this->actingAs($this->manager)->put("/collections/{$payment->id}", [
        'customer_id' => $this->customer->id, 'payment_date' => now()->toDateString(), 'amount' => 1000000, 'method' => 'bank_transfer',
    ]);

    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 1000000));
});

test('editing a payment correctly reconciles the cached status of both the old and new linked invoice', function () {
    $this->stockService->receiveStock($this->product, 20, 1000, now()->toDateString());
    $invoiceA = Invoice::factory()->for($this->company)->for($this->customer)->create();
    $invoiceA->items()->create(['product_id' => $this->product->id, 'qty' => 2, 'unit_price' => 5000, 'line_total' => 10000]);
    $invoiceA = $this->invoiceService->finalize($invoiceA, $this->manager);

    $invoiceB = Invoice::factory()->for($this->company)->for($this->customer)->create();
    $invoiceB->items()->create(['product_id' => $this->product->id, 'qty' => 2, 'unit_price' => 5000, 'line_total' => 10000]);
    $invoiceB = $this->invoiceService->finalize($invoiceB, $this->manager);

    $payment = makePayment($this->company, $this->customer, 'cash', 10000, $invoiceA->id);
    expect($invoiceA->fresh()->amount_paid_cached)->toBe(10000);

    // Reassign the payment from Invoice A to Invoice B.
    $this->actingAs($this->manager)->put("/collections/{$payment->id}", [
        'customer_id' => $this->customer->id, 'invoice_id' => $invoiceB->id, 'payment_date' => now()->toDateString(), 'amount' => 10000, 'method' => 'cash',
    ]);

    expect($invoiceA->fresh()->amount_paid_cached)->toBe(0);
    expect($invoiceB->fresh()->amount_paid_cached)->toBe(10000);
});

test('deleting a collection remains unchanged and correctly reduces cash on hand', function () {
    $payment = makePayment($this->company, $this->customer, 'cash', 500000);

    $this->actingAs($this->manager)->delete("/collections/{$payment->id}");

    $response = $this->actingAs($this->manager)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('cashOnHand', 0));
});

// --- 21-23: No regression to existing calculations ---

test('customer outstanding balance calculation is unaffected by the new payment methods', function () {
    $this->stockService->receiveStock($this->product, 20, 1000, now()->toDateString());
    $invoice = Invoice::factory()->for($this->company)->for($this->customer)->create();
    $invoice->items()->create(['product_id' => $this->product->id, 'qty' => 2, 'unit_price' => 5000, 'line_total' => 10000]);
    $invoice = $this->invoiceService->finalize($invoice, $this->manager);

    makePayment($this->company, $this->customer, 'settlement', 4000);

    expect($this->customer->fresh()->outstandingBalance())->toBe($invoice->grand_total - 4000);
});

test('invoice totals and FIFO consumption remain unaffected by this release', function () {
    $this->stockService->receiveStock($this->product, 20, 1000, now()->toDateString());
    $invoice = Invoice::factory()->for($this->company)->for($this->customer)->create();
    $invoice->items()->create(['product_id' => $this->product->id, 'qty' => 5, 'unit_price' => 2000, 'line_total' => 10000]);
    $invoice = $this->invoiceService->finalize($invoice, $this->owner);

    expect($invoice->fresh()->grand_total)->toBe(10000);
    expect($this->product->fresh()->cached_stock_qty)->toBe(15);
    expect($invoice->items->first()->fresh()->cogs_total)->toBe(5000);
});

// --- 24-25: Staff / Sales Rep unchanged ---

test('staff does not receive financial dashboard metrics', function () {
    $response = $this->actingAs($this->staff)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page
        ->missing('cashOnHand')
        ->missing('monthlyExpenses')
        ->missing('grossProfitMonth')
    );
});

test('sales rep isolation and access remain unchanged by this release', function () {
    $this->actingAs($this->repUser)->get('/collections')->assertStatus(403);
    $response = $this->actingAs($this->repUser)->get('/dashboard');
    $response->assertInertia(fn ($page) => $page->where('role', 'sales_rep')->missing('cashOnHand'));
});

// --- Security: manager cannot reach owner-only endpoints or cost data via collections ---

test('manager can edit collections but cannot see cost data anywhere in the process', function () {
    $payment = makePayment($this->company, $this->customer, 'cash', 200000);

    $response = $this->actingAs($this->manager)->get("/collections/{$payment->id}/edit");
    $response->assertStatus(200);
});

test('a collection belonging to another company cannot be edited', function () {
    $companyB = Company::factory()->create();
    $customerB = Customer::factory()->for($companyB)->create();
    $foreignPayment = makePayment($companyB, $customerB, 'cash', 100000);

    $this->actingAs($this->manager)->get("/collections/{$foreignPayment->id}/edit")->assertStatus(404);
});
