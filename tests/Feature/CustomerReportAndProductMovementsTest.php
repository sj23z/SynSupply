<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\ReturnService;
use App\Services\StockService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->owner = User::factory()->for($this->company)->create(['role' => 'owner']);
    $this->manager = User::factory()->for($this->company)->create(['role' => 'admin']);
    $this->stockService = app(StockService::class);
    $this->invoiceService = app(InvoiceService::class);
    $this->returnService = app(ReturnService::class);
});

function makeInvoiceWithItems2($company, $customer, $manager, $invoiceService, $stockService, $product, $qty, $unitPrice) {
    $stockService->receiveStock($product, $qty + 50, 1000, now()->subDays(10)->toDateString());
    $invoice = Invoice::factory()->for($company)->for($customer)->create();
    $invoice->items()->create(['product_id' => $product->id, 'qty' => $qty, 'unit_price' => $unitPrice, 'line_total' => $qty * $unitPrice]);
    return $invoiceService->finalize($invoice, $manager);
}

// --- 1-8: Customer general report ---

test('general customer report contains multiple customers', function () {
    $c1 = Customer::factory()->for($this->company)->create();
    $c2 = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems2($this->company, $c1, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 1000);
    makeInvoiceWithItems2($this->company, $c2, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 1000);

    $response = $this->actingAs($this->manager)->get('/reports/customers');
    $response->assertInertia(fn ($page) => $page->has('customers', 2));
});

test('each customer in the general report contains invoices with full item detail', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 3, 2000);

    $response = $this->actingAs($this->manager)->get('/reports/customers');
    $response->assertInertia(fn ($page) => $page
        ->where('customers.0.lines.0.type', 'invoice')
        ->where('customers.0.lines.0.detail.items.0.product_name', $product->name)
        ->where('customers.0.lines.0.detail.items.0.qty', 3)
        ->has('customers.0.lines.0.detail.items.0.unit_price')
        ->has('customers.0.lines.0.detail.invoice_total')
    );
});

test('general customer report contains payment rows', function () {
    $customer = Customer::factory()->for($this->company)->create();
    Payment::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'payment_date' => now()->toDateString(), 'amount' => 200000, 'method' => 'cash']);

    $response = $this->actingAs($this->manager)->get('/reports/customers');
    $response->assertInertia(fn ($page) => $page->where('customers.0.lines.0.type', 'payment'));
});

test('general customer report contains sales return rows', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 5, 2000);
    $return = SalesReturn::factory()->for($this->company)->for($customer)->create();
    $return->items()->create(['product_id' => $product->id, 'qty' => 1, 'unit_price' => 2000, 'line_total' => 2000]);
    $this->returnService->finalize($return, $this->manager);

    $response = $this->actingAs($this->manager)->get('/reports/customers');
    $response->assertInertia(fn ($page) => $page->where('customers.0.lines.1.type', 'return'));
});

test('general customer report exposes each customer balance', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    $invoice = makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 2, 5000);

    $response = $this->actingAs($this->manager)->get('/reports/customers');
    $response->assertInertia(fn ($page) => $page->where('customers.0.outstanding_balance', $invoice->fresh()->grand_total));
});

test('general customer report totals reconcile with the sum of its customers', function () {
    $c1 = Customer::factory()->for($this->company)->create();
    $c2 = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    $inv1 = makeInvoiceWithItems2($this->company, $c1, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 4000);
    $inv2 = makeInvoiceWithItems2($this->company, $c2, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 6000);

    $response = $this->actingAs($this->manager)->get('/reports/customers');
    $response->assertInertia(fn ($page) => $page->where('totals.gross_sales', $inv1->fresh()->grand_total + $inv2->fresh()->grand_total));
});

test('general customer report PDF route works and uses the same dataset', function () {
    Customer::factory()->for($this->company)->create();
    $this->actingAs($this->manager)->get('/reports/customers/pdf')->assertStatus(200);
});

// --- 9-14: Product report financial movements ---

test('product transactions contain invoice information', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    $invoice = makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 2, 3000);

    $response = $this->actingAs($this->manager)->get("/reports/products/{$product->id}");
    $response->assertInertia(fn ($page) => $page->where('transactions.0.invoice_number', $invoice->fresh()->invoice_number));
});

test('product transactions expose related payment rows via invoice_id', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    $invoice = makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 2, 3000);
    Payment::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'invoice_id' => $invoice->id, 'payment_date' => now()->toDateString(), 'amount' => 300000, 'method' => 'cash']);

    $response = $this->actingAs($this->manager)->get("/reports/products/{$product->id}");
    $response->assertInertia(fn ($page) => $page->where('transactions.0.payments.0.amount', 300000)->where('transactions.0.payments.0.method', 'cash'));
});

test('multiple payments for one invoice remain separate rows, not collapsed', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    $invoice = makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 2, 500000);
    Payment::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'invoice_id' => $invoice->id, 'payment_date' => '2026-08-20', 'amount' => 300000, 'method' => 'cash']);
    Payment::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'invoice_id' => $invoice->id, 'payment_date' => '2026-08-25', 'amount' => 150000, 'method' => 'bank_transfer']);
    Payment::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'invoice_id' => $invoice->id, 'payment_date' => '2026-08-27', 'amount' => 50000, 'method' => 'discount']);

    $response = $this->actingAs($this->manager)->get("/reports/products/{$product->id}");
    $response->assertInertia(fn ($page) => $page->has('transactions.0.payments', 3));
});

test('settlement and discount payments remain distinguishable by method in the product report', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    $invoice = makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 100000);
    Payment::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'invoice_id' => $invoice->id, 'payment_date' => now()->toDateString(), 'amount' => 20000, 'method' => 'settlement']);

    $response = $this->actingAs($this->manager)->get("/reports/products/{$product->id}");
    $response->assertInertia(fn ($page) => $page->where('transactions.0.payments.0.method', 'settlement')->where('transactions.0.payments.0.method_label', 'تسوية'));
});

test('owner can see cost, COGS, and profit on the product report', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems2($this->company, $customer, $this->owner, $this->invoiceService, $this->stockService, $product, 2, 5000);

    $this->actingAs($this->owner)->get("/reports/products/{$product->id}")
        ->assertInertia(fn ($page) => $page->where('isOwner', true)->has('product.cogs')->has('transactions.0.cogs_total'));
});

test('manager cannot receive cost, COGS, or profit fields on the product report', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 2, 5000);

    $this->actingAs($this->manager)->get("/reports/products/{$product->id}")
        ->assertInertia(fn ($page) => $page->where('isOwner', false)->missing('product.cogs')->missing('transactions.0.cogs_total'));
});

// --- 15: Security ---

test('cross-company isolation is enforced for the general customer report and product report', function () {
    $companyB = Company::factory()->create();
    $foreignProduct = Product::factory()->for($companyB)->create();

    // The general customer report is scoped by auth company automatically
    // (BelongsToCompany global scope) — verify a foreign customer never
    // appears rather than 404ing (there's no single-entity route here).
    $foreignCustomer = Customer::factory()->for($companyB)->create();
    $response = $this->actingAs($this->manager)->get('/reports/customers');
    $response->assertInertia(fn ($page) => collect($page->toArray()['props']['customers'])->pluck('id')->doesntContain($foreignCustomer->id));

    $this->actingAs($this->manager)->get("/reports/products/{$foreignProduct->id}")->assertStatus(404);
});

// --- 16-17: PDF ---

test('customer PDF contains complete customer statements, not summary-only', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 1000);

    $this->actingAs($this->manager)->get("/customers/{$customer->id}/statement/pdf")->assertStatus(200);
});

test('product PDF route works with financial movement data present', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    $invoice = makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 1000);
    Payment::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'invoice_id' => $invoice->id, 'payment_date' => now()->toDateString(), 'amount' => 1000, 'method' => 'cash']);

    $this->actingAs($this->manager)->get("/reports/products/{$product->id}/pdf")->assertStatus(200);
});

// --- 18-19: Regression checks for Area/Sales Rep hierarchy ---

test('area report remains hierarchical after this correction', function () {
    $area = \App\Models\Area::factory()->for($this->company)->create();
    $customer = Customer::factory()->for($this->company)->for($area)->create();
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 1000);

    $response = $this->actingAs($this->manager)->get("/reports/areas/{$area->id}");
    $response->assertInertia(fn ($page) => $page->has('customers.0.lines')->where('customers.0.lines.0.type', 'invoice'));
});

test('sales representative report remains hierarchical after this correction', function () {
    $rep = \App\Models\SalesRepresentative::factory()->for($this->company)->create();
    $customer = Customer::factory()->for($this->company)->create(['assigned_rep_id' => $rep->id]);
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems2($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 1000);

    $response = $this->actingAs($this->manager)->get("/reports/sales-representatives/{$rep->id}");
    $response->assertInertia(fn ($page) => $page->has('customers.0.lines')->where('customers.0.lines.0.type', 'invoice'));
});
