<?php

namespace Tests\Feature;

use App\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\PaymentMethod;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InvoicePaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_invoice_totals_are_calculated_from_validated_line_items(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();

        $this->actingAs($admin)->post(route('invoices.store'), [
            'client_id' => $client->id, 'issue_date' => '2026-11-01', 'due_date' => '2026-12-01', 'status' => InvoiceStatus::Sent->value,
            'tax_rate' => 20, 'items' => [['description' => 'Development', 'quantity' => 2, 'unit_price' => 5000]],
        ])->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('10000.00', $invoice->subtotal);
        $this->assertSame('2000.00', $invoice->tax_amount);
        $this->assertSame('12000.00', $invoice->total);
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'line_total' => 10000]);
    }

    public function test_payment_cannot_exceed_outstanding_balance(): void
    {
        $admin = User::factory()->admin()->create();
        $invoice = Invoice::factory()->create(['total' => 12000]);

        $this->actingAs($admin)->post(route('payments.store'), [
            'invoice_id' => $invoice->id, 'amount' => 12000.01, 'paid_at' => '2026-11-20 12:00:00', 'method' => PaymentMethod::Cash->value,
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_full_payment_marks_invoice_paid_and_pdf_downloads(): void
    {
        $admin = User::factory()->admin()->create();
        $invoice = Invoice::factory()->create(['total' => 12000]);
        $invoice->items()->create(['description' => 'Service', 'quantity' => 1, 'unit_price' => 10000, 'line_total' => 10000]);

        $this->actingAs($admin)->post(route('payments.store'), [
            'invoice_id' => $invoice->id, 'amount' => 12000, 'paid_at' => '2026-11-20 12:00:00', 'method' => PaymentMethod::BankTransfer->value,
        ])->assertRedirect();
        $this->assertSame(InvoiceStatus::Paid, $invoice->refresh()->status);

        $this->actingAs($admin)->get(route('invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
