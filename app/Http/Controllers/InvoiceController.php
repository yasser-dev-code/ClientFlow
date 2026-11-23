<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\InvoiceStatus;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $invoices = Invoice::query()
            ->with(['client:id,name,company', 'project:id,name', 'payments'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            }))
            ->when(in_array($status, array_column(InvoiceStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $status))
            ->latest('issue_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'search', 'status'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('invoices.form', $this->formData(new Invoice));
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = DB::transaction(fn (): Invoice => $this->persistInvoice($request, new Invoice));
        ActivityLog::create(['user_id' => $request->user()->id, 'subject_type' => Invoice::class, 'subject_id' => $invoice->id, 'action' => 'created', 'description' => __('app.activity.invoice_created', ['number' => $invoice->number])]);

        return redirect()->route('invoices.show', $invoice)->with('success', __('app.saved'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['client', 'project', 'items', 'payments.recorder']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Request $request, Invoice $invoice): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $invoice->load('items');

        return view('invoices.form', $this->formData($invoice));
    }

    public function update(StoreInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        DB::transaction(fn (): Invoice => $this->persistInvoice($request, $invoice));

        return redirect()->route('invoices.show', $invoice)->with('success', __('app.saved'));
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', __('app.deleted'));
    }

    public function pdf(Invoice $invoice): Response
    {
        $invoice->load(['client', 'project', 'items', 'payments']);

        return Pdf::loadView('invoices.pdf', compact('invoice'))
            ->setPaper('a4')
            ->download($invoice->number.'.pdf');
    }

    private function persistInvoice(StoreInvoiceRequest $request, Invoice $invoice): Invoice
    {
        $items = collect($request->validated('items'))->map(function (array $item): array {
            $item['line_total'] = round((float) $item['quantity'] * (float) $item['unit_price'], 2);

            return $item;
        });
        $subtotal = round((float) $items->sum('line_total'), 2);
        $taxAmount = round($subtotal * ((float) $request->validated('tax_rate') / 100), 2);
        $total = $subtotal + $taxAmount;
        $paidAmount = $invoice->exists ? (float) $invoice->payments()->sum('amount') : 0;

        if ($paidAmount > $total) {
            throw ValidationException::withMessages(['items' => __('app.invoice_total_below_payments')]);
        }

        $data = $request->safe()->except('items');
        $data['number'] = $data['number'] ?? $this->nextNumber();
        $data['subtotal'] = $subtotal;
        $data['tax_amount'] = $taxAmount;
        $data['total'] = $total;
        $data['currency'] = 'MAD';
        $invoice->fill($data)->save();
        $invoice->items()->delete();
        $invoice->items()->createMany($items->all());

        return $invoice;
    }

    private function nextNumber(): string
    {
        $next = (int) Invoice::query()->max('id') + 1;

        return sprintf('CF-%s-%04d', now()->format('Y'), $next);
    }

    /** @return array<string, mixed> */
    private function formData(Invoice $invoice): array
    {
        return [
            'invoice' => $invoice,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'company']),
            'projects' => Project::orderBy('name')->get(['id', 'name', 'client_id']),
            'statuses' => InvoiceStatus::cases(),
        ];
    }
}
