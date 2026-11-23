<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\InvoiceStatus;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::with(['invoice.client:id,name,company', 'recorder:id,name'])
            ->latest('paid_at')
            ->paginate(12);
        $openInvoices = Invoice::with(['client:id,name', 'payments'])
            ->whereNotIn('status', [InvoiceStatus::Paid, InvoiceStatus::Draft])
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Invoice $invoice): bool => $invoice->remainingBalance() > 0);

        return view('payments.index', ['payments' => $payments, 'openInvoices' => $openInvoices, 'methods' => PaymentMethod::cases()]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = DB::transaction(function () use ($request): Payment {
            $invoice = Invoice::whereKey($request->integer('invoice_id'))->lockForUpdate()->firstOrFail();
            $outstanding = round((float) $invoice->total - (float) $invoice->payments()->sum('amount'), 2);
            $amount = round($request->float('amount'), 2);

            if ($amount > $outstanding) {
                throw ValidationException::withMessages(['amount' => __('app.payment_exceeds_balance', ['amount' => number_format($outstanding, 2)])]);
            }

            $payment = Payment::create([...$request->validated(), 'amount' => $amount, 'recorded_by' => $request->user()->id]);
            $this->refreshInvoiceStatus($invoice);

            return $payment;
        });

        ActivityLog::create(['user_id' => $request->user()->id, 'subject_type' => Payment::class, 'subject_id' => $payment->id, 'action' => 'payment_recorded', 'description' => __('app.activity.payment_recorded', ['amount' => number_format((float) $payment->amount, 2)])]);

        return back()->with('success', __('app.saved'));
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        DB::transaction(function () use ($payment): void {
            $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
            $payment->delete();
            $this->refreshInvoiceStatus($invoice);
        });

        return back()->with('success', __('app.deleted'));
    }

    private function refreshInvoiceStatus(Invoice $invoice): void
    {
        $paid = (float) $invoice->payments()->sum('amount');
        $status = match (true) {
            $paid >= (float) $invoice->total => InvoiceStatus::Paid,
            $paid > 0 => InvoiceStatus::PartiallyPaid,
            $invoice->due_date->isPast() => InvoiceStatus::Overdue,
            default => InvoiceStatus::Sent,
        };
        $invoice->update(['status' => $status]);
    }
}
