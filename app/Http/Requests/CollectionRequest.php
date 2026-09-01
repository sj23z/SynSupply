<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** "Customer Collections" — Admin-only, backed by the payments table. */
class CollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasFullOperationalAccess();
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'invoice_id' => ['nullable', Rule::exists('invoices', 'id')->where('company_id', $companyId)],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'in:cash,bank_transfer,other,settlement,discount'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** A payment linked to an invoice must actually be paying off that customer's own invoice. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $invoiceId = $this->input('invoice_id');
            $customerId = $this->input('customer_id');

            if ($invoiceId && $customerId) {
                $invoice = Invoice::find($invoiceId);
                if ($invoice && $invoice->customer_id != $customerId) {
                    $validator->errors()->add('invoice_id', 'الفاتورة المحددة لا تخص هذا العميل.');
                }
            }
        });
    }
}
