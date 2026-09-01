<?php

use App\Models\Area;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesRepresentative;
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

function makeInvoiceWithItems($company, $customer, $manager, $invoiceService, $stockService, $product, $qty, $unitPrice) {
    $stockService->receiveStock($product, $qty + 50, 1000, now()->subDays(10)->toDateString());
    $invoice = Invoice::factory()->for($company)->for($customer)->create();
    $invoice->items()->create(['product_id' => $product->id, 'qty' => $qty, 'unit_price' => $unitPrice, 'line_total' => $qty * $unitPrice]);
    return $invoiceService->finalize($invoice, $manager);
}

// --- 1-6: Area report ---

test('area report contains all customers assigned to that area', function () {
    $area = Area::factory()->for($this->company)->create();
    $c1 = Customer::factory()->for($this->company)->for($area)->create();
    $c2 = Customer::factory()->for($this->company)->for($area)->create();
    $c3 = Customer::factory()->for($this->company)->create(); // different area, must NOT appear

    $response = $this->actingAs($this->manager)->get("/reports/areas/{$area->id}");
    $response->assertInertia(fn ($page) => $page
        ->where('customers.0.id', $c1->id)
        ->where('customers.1.id', $c2->id)
        ->where('totals.customers_count', 2)
    );
});

test('area report contains invoices for those customers with full line item detail', function () {
    $area = Area::factory()->for($this->company)->create();
    $customer = Customer::factory()->for($this->company)->for($area)->create();
    $product = Product::factory()->for($this->company)->create();
    $invoice = makeInvoiceWithItems($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 3, 2000);

    $response = $this->actingAs($this->manager)->get("/reports/areas/{$area->id}");
    $response->assertInertia(fn ($page) => $page
        ->where('customers.0.lines.0.type', 'invoice')
        ->where('customers.0.lines.0.detail.items.0.product_name', $product->name)
        ->where('customers.0.lines.0.detail.items.0.qty', 3)
        ->where('customers.0.lines.0.detail.invoice_total', $invoice->fresh()->grand_total)
    );
});

test('area report contains payments', function () {
    $area = Area::factory()->for($this->company)->create();
    $customer = Customer::factory()->for($this->company)->for($area)->create();
    Payment::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'payment_date' => now()->toDateString(), 'amount' => 250000, 'method' => 'cash']);

    $response = $this->actingAs($this->manager)->get("/reports/areas/{$area->id}");
    $response->assertInertia(fn ($page) => $page->where('customers.0.lines.0.type', 'payment'));
});

test('area report contains returns', function () {
    $area = Area::factory()->for($this->company)->create();
    $customer = Customer::factory()->for($this->company)->for($area)->create();
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 5, 2000);

    $return = SalesReturn::factory()->for($this->company)->for($customer)->create();
    $return->items()->create(['product_id' => $product->id, 'qty' => 2, 'unit_price' => 2000, 'line_total' => 4000]);
    $this->returnService->finalize($return, $this->manager);

    $response = $this->actingAs($this->manager)->get("/reports/areas/{$area->id}");
    $response->assertInertia(fn ($page) => $page->where('customers.0.lines.1.type', 'return'));
});

test('area totals reconcile with the sum of its customers totals', function () {
    $area = Area::factory()->for($this->company)->create();
    $c1 = Customer::factory()->for($this->company)->for($area)->create();
    $c2 = Customer::factory()->for($this->company)->for($area)->create();
    $product = Product::factory()->for($this->company)->create();

    $inv1 = makeInvoiceWithItems($this->company, $c1, $this->manager, $this->invoiceService, $this->stockService, $product, 2, 5000);
    $inv2 = makeInvoiceWithItems($this->company, $c2, $this->manager, $this->invoiceService, $this->stockService, $product, 3, 5000);

    $response = $this->actingAs($this->manager)->get("/reports/areas/{$area->id}");
    $response->assertInertia(fn ($page) => $page->where('totals.gross_sales', $inv1->fresh()->grand_total + $inv2->fresh()->grand_total));
});

test('area report PDF route uses the same dataset as the on-screen report', function () {
    $area = Area::factory()->for($this->company)->create();
    Customer::factory()->for($this->company)->for($area)->create();

    $this->actingAs($this->manager)->get("/reports/areas/{$area->id}/pdf")->assertStatus(200);
});

// --- 7-12: Sales Representative report ---

test('sales representative report contains all assigned customers', function () {
    $rep = SalesRepresentative::factory()->for($this->company)->create();
    $c1 = Customer::factory()->for($this->company)->create(['assigned_rep_id' => $rep->id]);
    $c2 = Customer::factory()->for($this->company)->create(['assigned_rep_id' => $rep->id]);
    Customer::factory()->for($this->company)->create(); // unassigned, must not appear

    $response = $this->actingAs($this->manager)->get("/reports/sales-representatives/{$rep->id}");
    $response->assertInertia(fn ($page) => $page->where('totals.customers_count', 2));
});

test('sales representative report contains all customer invoices with line detail', function () {
    $rep = SalesRepresentative::factory()->for($this->company)->create();
    $customer = Customer::factory()->for($this->company)->create(['assigned_rep_id' => $rep->id]);
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 4, 3000);

    $response = $this->actingAs($this->manager)->get("/reports/sales-representatives/{$rep->id}");
    $response->assertInertia(fn ($page) => $page
        ->where('customers.0.lines.0.type', 'invoice')
        ->where('customers.0.lines.0.detail.items.0.qty', 4)
    );
});

test('sales representative report contains payments', function () {
    $rep = SalesRepresentative::factory()->for($this->company)->create();
    $customer = Customer::factory()->for($this->company)->create(['assigned_rep_id' => $rep->id]);
    Payment::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'payment_date' => now()->toDateString(), 'amount' => 150000, 'method' => 'bank_transfer']);

    $response = $this->actingAs($this->manager)->get("/reports/sales-representatives/{$rep->id}");
    $response->assertInertia(fn ($page) => $page->where('customers.0.lines.0.type', 'payment'));
});

test('sales representative report contains returns', function () {
    $rep = SalesRepresentative::factory()->for($this->company)->create();
    $customer = Customer::factory()->for($this->company)->create(['assigned_rep_id' => $rep->id]);
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 5, 2000);
    $return = SalesReturn::factory()->for($this->company)->for($customer)->create();
    $return->items()->create(['product_id' => $product->id, 'qty' => 1, 'unit_price' => 2000, 'line_total' => 2000]);
    $this->returnService->finalize($return, $this->manager);

    $response = $this->actingAs($this->manager)->get("/reports/sales-representatives/{$rep->id}");
    $response->assertInertia(fn ($page) => $page->where('customers.0.lines.1.type', 'return'));
});

test('sales representative totals reconcile with the sum of assigned customers', function () {
    $rep = SalesRepresentative::factory()->for($this->company)->create();
    $c1 = Customer::factory()->for($this->company)->create(['assigned_rep_id' => $rep->id]);
    $product = Product::factory()->for($this->company)->create();
    $inv = makeInvoiceWithItems($this->company, $c1, $this->manager, $this->invoiceService, $this->stockService, $product, 2, 4000);

    $response = $this->actingAs($this->manager)->get("/reports/sales-representatives/{$rep->id}");
    $response->assertInertia(fn ($page) => $page->where('totals.gross_sales', $inv->fresh()->grand_total));
});

test('sales representative report PDF route works', function () {
    $rep = SalesRepresentative::factory()->for($this->company)->create();
    $this->actingAs($this->manager)->get("/reports/sales-representatives/{$rep->id}/pdf")->assertStatus(200);
});

// --- 13-14: Customer report ---

test('customer report contains complete invoice contents', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 2, 6000);

    $response = $this->actingAs($this->manager)->get("/customers/{$customer->id}/statement");
    $response->assertInertia(fn ($page) => $page->where('lines.0.detail.items.0.product_name', $product->name));
});

test('customer report contains payments and returns', function () {
    $customer = Customer::factory()->for($this->company)->create();
    Payment::create(['company_id' => $this->company->id, 'customer_id' => $customer->id, 'payment_date' => now()->toDateString(), 'amount' => 100000, 'method' => 'other']);

    $response = $this->actingAs($this->manager)->get("/customers/{$customer->id}/statement");
    $response->assertInertia(fn ($page) => $page->where('lines.0.type', 'payment'));
});

// --- 15: Product report ---

test('product report contains transaction-level sales data', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    $invoice = makeInvoiceWithItems($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 3, 2500);

    $response = $this->actingAs($this->manager)->get("/reports/products/{$product->id}");
    $response->assertInertia(fn ($page) => $page
        ->where('transactions.0.invoice_number', $invoice->fresh()->invoice_number)
        ->where('transactions.0.customer_name', $customer->name)
        ->where('transactions.0.qty', 3)
    );
});

// --- 16-17: Owner/Manager cost security ---

test('manager cannot receive cost, COGS, or profit fields in any hierarchical report', function () {
    $area = Area::factory()->for($this->company)->create();
    Customer::factory()->for($this->company)->for($area)->create();
    $product = Product::factory()->for($this->company)->create();

    $this->actingAs($this->manager)->get("/reports/areas/{$area->id}")
        ->assertInertia(fn ($page) => $page->missing('customers.0.cogs')->missing('customers.0.gross_profit'));

    $this->actingAs($this->manager)->get("/reports/products/{$product->id}")
        ->assertInertia(fn ($page) => $page->where('isOwner', false)->missing('product.cogs')->missing('product.gross_profit'));
});

test('owner can receive cost, COGS, and profit fields', function () {
    $product = Product::factory()->for($this->company)->create();
    $customer = Customer::factory()->for($this->company)->create();
    makeInvoiceWithItems($this->company, $customer, $this->owner, $this->invoiceService, $this->stockService, $product, 2, 5000);

    $this->actingAs($this->owner)->get("/reports/products/{$product->id}")
        ->assertInertia(fn ($page) => $page->where('isOwner', true)->has('product.cogs')->has('product.gross_profit'));
});

// --- 18: Cross-company isolation ---

test('cross-company isolation is enforced for area, sales-rep, and product report routes', function () {
    $companyB = Company::factory()->create();
    $foreignArea = Area::factory()->for($companyB)->create();
    $foreignRep = SalesRepresentative::factory()->for($companyB)->create();
    $foreignProduct = Product::factory()->for($companyB)->create();

    $this->actingAs($this->manager)->get("/reports/areas/{$foreignArea->id}")->assertStatus(404);
    $this->actingAs($this->manager)->get("/reports/sales-representatives/{$foreignRep->id}")->assertStatus(404);
    $this->actingAs($this->manager)->get("/reports/products/{$foreignProduct->id}")->assertStatus(404);
});

// --- 19: PDF uses the same dataset ---

test('customer statement PDF route uses the same dataset as the on-screen statement', function () {
    $customer = Customer::factory()->for($this->company)->create();
    $product = Product::factory()->for($this->company)->create();
    makeInvoiceWithItems($this->company, $customer, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 1000);

    $this->actingAs($this->manager)->get("/customers/{$customer->id}/statement/pdf")->assertStatus(200);
});

// --- Acceptance scenario (Section 17) ---

test('acceptance scenario: an area with 3 customers shows each customers full statement, not one aggregate row', function () {
    $area = Area::factory()->for($this->company)->create();
    $c1 = Customer::factory()->for($this->company)->for($area)->create();
    $c2 = Customer::factory()->for($this->company)->for($area)->create();
    $c3 = Customer::factory()->for($this->company)->for($area)->create();
    $product = Product::factory()->for($this->company)->create();

    makeInvoiceWithItems($this->company, $c1, $this->manager, $this->invoiceService, $this->stockService, $product, 2, 1000);
    makeInvoiceWithItems($this->company, $c2, $this->manager, $this->invoiceService, $this->stockService, $product, 1, 1000);
    makeInvoiceWithItems($this->company, $c3, $this->manager, $this->invoiceService, $this->stockService, $product, 3, 1000);

    $response = $this->actingAs($this->manager)->get("/reports/areas/{$area->id}");
    $response->assertInertia(fn ($page) => $page
        ->has('customers', 3)
        ->has('customers.0.lines')
        ->has('customers.1.lines')
        ->has('customers.2.lines')
        ->where('customers.0.lines.0.type', 'invoice')
        ->where('customers.1.lines.0.type', 'invoice')
        ->where('customers.2.lines.0.type', 'invoice')
    );
});
