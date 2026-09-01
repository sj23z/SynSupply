<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerStatementController;
use App\Http\Controllers\CustomerVisitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesRepresentativeController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // --- Profile / password change (every role reaches this the same way) ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // --- Users (Owner only — Admin/Manager lost access per Phase 15B) ---
    Route::middleware('owner')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
        Route::put('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
    });

    // --- Areas ---
    Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');
    Route::middleware('admin')->group(function () {
        Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
        Route::put('/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
        Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');
    });

    // --- Sales Representatives ---
    Route::get('/sales-representatives', [SalesRepresentativeController::class, 'index'])->name('sales-representatives.index');
    Route::middleware('admin')->group(function () {
        Route::post('/sales-representatives', [SalesRepresentativeController::class, 'store'])->name('sales-representatives.store');
        Route::put('/sales-representatives/{salesRepresentative}', [SalesRepresentativeController::class, 'update'])->name('sales-representatives.update');
        Route::delete('/sales-representatives/{salesRepresentative}', [SalesRepresentativeController::class, 'destroy'])->name('sales-representatives.destroy');
        Route::post('/sales-representatives/{salesRepresentative}/grant-access', [SalesRepresentativeController::class, 'grantAccess'])->name('sales-representatives.grant-access');
    });

    // --- Customers ---
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::patch('/customers/{customer}/toggle-active', [CustomerController::class, 'toggleActive'])->name('customers.toggle-active');
    Route::get('/customers/{customer}/statement', [CustomerStatementController::class, 'show'])->name('customers.statement');
    Route::get('/customers/{customer}/statement/pdf', [CustomerStatementController::class, 'pdf'])->name('customers.statement.pdf');

    // --- Visit Notes ---
    Route::post('/customers/{customer}/visits', [CustomerVisitController::class, 'store'])->name('customer-visits.store');
    Route::patch('/customer-visits/{visit}/complete-follow-up', [CustomerVisitController::class, 'completeFollowUp'])->name('customer-visits.complete-follow-up');

    // --- Products ---
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::middleware('admin')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle-active');
    });

    // --- Inventory: viewing = Admin(Manager)+Owner, receiving = Owner only ---
    Route::middleware('admin')->prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/products/{product}', [InventoryController::class, 'show'])->name('show');
    });
    Route::middleware('owner')->prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/create', [InventoryController::class, 'create'])->name('create');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
    });

    // --- Sales Invoices (Admin + Staff; Sales Rep has no routes here except
    //     the status-only view baked into their own dashboard/index shape) ---
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('/invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // --- Sales Returns (Admin + Staff only) ---
    Route::get('/sales-returns', [SalesReturnController::class, 'index'])->name('sales-returns.index');
    Route::get('/sales-returns/create', [SalesReturnController::class, 'create'])->name('sales-returns.create');
    Route::post('/sales-returns', [SalesReturnController::class, 'store'])->name('sales-returns.store');
    Route::get('/sales-returns/{salesReturn}', [SalesReturnController::class, 'show'])->name('sales-returns.show');
    Route::get('/sales-returns/{salesReturn}/edit', [SalesReturnController::class, 'edit'])->name('sales-returns.edit');
    Route::put('/sales-returns/{salesReturn}', [SalesReturnController::class, 'update'])->name('sales-returns.update');
    Route::post('/sales-returns/{salesReturn}/finalize', [SalesReturnController::class, 'finalize'])->name('sales-returns.finalize');
    Route::post('/sales-returns/{salesReturn}/cancel', [SalesReturnController::class, 'cancel'])->name('sales-returns.cancel');
    Route::get('/sales-returns/{salesReturn}/pdf', [SalesReturnController::class, 'pdf'])->name('sales-returns.pdf');
    Route::delete('/sales-returns/{salesReturn}', [SalesReturnController::class, 'destroy'])->name('sales-returns.destroy');
    Route::get('/invoices/{invoice}/items-for-return', [SalesReturnController::class, 'invoiceItems'])->name('invoices.items-for-return');

    // --- Customer Collections (Admin only) ---
    Route::middleware('admin')->prefix('collections')->name('collections.')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('index');
        Route::get('/create', [CollectionController::class, 'create'])->name('create');
        Route::post('/', [CollectionController::class, 'store'])->name('store');
        Route::get('/{payment}/edit', [CollectionController::class, 'edit'])->name('edit');
        Route::put('/{payment}', [CollectionController::class, 'update'])->name('update');
        Route::delete('/{payment}', [CollectionController::class, 'destroy'])->name('destroy');
    });

    // --- المصروفات / Expenses (Admin only) ---
    Route::middleware('admin')->prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::get('/create', [ExpenseController::class, 'create'])->name('create');
        Route::post('/', [ExpenseController::class, 'store'])->name('store');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
        Route::post('/categories', [ExpenseCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('categories.update');
    });

    // --- Reports: operational = Admin(Manager)+Owner, profitability = Owner only ---
    Route::middleware('owner')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/profitability', [ReportController::class, 'profitability'])->name('profitability');
    });
    Route::middleware('admin')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('/customers/pdf', [ReportController::class, 'customersPdf'])->name('customers.pdf');
        Route::get('/sales-representatives', [ReportController::class, 'salesRepresentatives'])->name('sales-representatives');
        Route::get('/sales-representatives/{salesRepresentative}', [ReportController::class, 'salesRepresentativeDetail'])->name('sales-representatives.detail');
        Route::get('/sales-representatives/{salesRepresentative}/pdf', [ReportController::class, 'salesRepresentativeDetailPdf'])->name('sales-representatives.detail.pdf');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/products/{product}', [ReportController::class, 'productDetail'])->name('products.detail');
        Route::get('/products/{product}/pdf', [ReportController::class, 'productDetailPdf'])->name('products.detail.pdf');
        Route::get('/areas', [ReportController::class, 'areas'])->name('areas');
        Route::get('/areas/{area}', [ReportController::class, 'areaDetail'])->name('areas.detail');
        Route::get('/areas/{area}/pdf', [ReportController::class, 'areaDetailPdf'])->name('areas.detail.pdf');
    });
});

require __DIR__.'/auth.php';
