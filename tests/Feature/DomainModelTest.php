<?php

namespace Tests\Feature;

use App\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\PaymentMethod;
use App\ProjectStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DomainModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_invoice_reports_paid_amount_and_remaining_balance(): void
    {
        $invoice = Invoice::factory()->create([
            'total' => 12000,
            'status' => InvoiceStatus::PartiallyPaid,
        ]);
        Payment::factory()->for($invoice)->create(['amount' => 3500]);
        Payment::factory()->for($invoice)->create(['amount' => 2500]);

        $invoice->load('payments');

        $this->assertSame(6000.0, $invoice->paidAmount());
        $this->assertSame(6000.0, $invoice->remainingBalance());
    }

    public function test_project_exposes_its_client_employees_and_tasks(): void
    {
        $client = Client::factory()->create();
        $employee = User::factory()->create();
        $project = Project::factory()->for($client)->create();
        $task = Task::factory()->for($project)->create(['assigned_to' => $employee->id]);
        $project->employees()->attach($employee);

        $project->load(['client', 'employees', 'tasks']);

        $this->assertTrue($project->client->is($client));
        $this->assertTrue($project->employees->contains($employee));
        $this->assertTrue($project->tasks->contains($task));
    }

    public function test_domain_values_are_cast_to_enums_and_dates(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'deadline' => '2026-12-31',
        ]);
        $payment = Payment::factory()->create([
            'method' => PaymentMethod::Cash,
            'paid_at' => '2026-11-20 10:30:00',
        ]);

        $this->assertSame(ProjectStatus::InProgress, $project->status);
        $this->assertSame('2026-12-31', $project->deadline->toDateString());
        $this->assertSame(PaymentMethod::Cash, $payment->method);
        $this->assertSame('2026-11-20 10:30:00', $payment->paid_at->format('Y-m-d H:i:s'));
    }

    public function test_deleting_invoice_removes_its_items_and_payments(): void
    {
        $invoice = Invoice::factory()->create();
        $item = $invoice->items()->create([
            'description' => 'Développement',
            'quantity' => 1,
            'unit_price' => 10000,
            'line_total' => 10000,
        ]);
        $payment = Payment::factory()->for($invoice)->create();

        $invoice->delete();

        $this->assertModelMissing($item);
        $this->assertModelMissing($payment);
    }
}
