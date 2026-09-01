<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CustomerStatementController extends Controller
{
    public function __construct(private CustomerStatementService $statements)
    {
    }

    public function show(Request $request, Customer $customer): Response
    {
        $this->authorize('view', $customer);

        [$from, $to] = $this->dateRange($request);
        $statement = $this->statements->build($customer, $from, $to);

        return Inertia::render('Customers/Statement', [
            'customer' => $customer,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'lines' => $statement['lines'],
            'opening_balance' => $statement['opening'],
            'closing_balance' => $statement['closing'],
        ]);
    }

    public function pdf(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        [$from, $to] = $this->dateRange($request);
        $statement = $this->statements->build($customer, $from, $to);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.statement', [
            'customer' => $customer,
            'from' => $from,
            'to' => $to,
            'lines' => $statement['lines'],
            'opening' => $statement['opening'],
            'closing' => $statement['closing'],
            'arabicFontPath' => resource_path('fonts/NotoSansArabic.ttf'),
        ], [], 'UTF-8')
            ->setPaper('a4')
            ->setOption(['isFontSubsettingEnabled' => false]);

        return $pdf->stream("statement-{$customer->id}.pdf");
    }

    private function dateRange(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->date('from')) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->date('to')) : now()->endOfDay();

        return [$from, $to];
    }
}
