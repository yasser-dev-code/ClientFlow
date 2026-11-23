<?php

namespace Database\Seeders;

use App\InvoiceStatus;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\PaymentMethod;
use App\ProjectStatus;
use App\TaskPriority;
use App\TaskStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Yassine El Mansouri',
            'email' => 'admin@clientflow.ma',
            'password' => 'ClientFlow2026!',
        ]);

        $employees = collect([
            ['name' => 'Salma Benali', 'email' => 'salma@clientflow.ma'],
            ['name' => 'Omar Alaoui', 'email' => 'omar@clientflow.ma'],
            ['name' => 'Imane Berrada', 'email' => 'imane@clientflow.ma'],
        ])->map(fn (array $employee): User => User::factory()->create([
            ...$employee,
            'password' => 'ClientFlow2026!',
        ]));

        $clients = collect([
            ['name' => 'Nadia Amrani', 'email' => 'nadia@atlasconcept.ma', 'phone' => '+212 661 22 45 90', 'company' => 'Atlas Concept', 'address' => '42 boulevard Zerktouni, Casablanca'],
            ['name' => 'Mehdi Chraibi', 'email' => 'mehdi@riadprestige.ma', 'phone' => '+212 662 48 11 73', 'company' => 'Riad Prestige', 'address' => '18 rue Riad Zitoun, Marrakech'],
            ['name' => 'Sara El Fassi', 'email' => 'sara@novatech.ma', 'phone' => '+212 663 09 71 28', 'company' => 'NovaTech Maroc', 'address' => 'Technopark, Rabat'],
            ['name' => 'Hamza Tazi', 'email' => 'hamza@tazilogistics.ma', 'phone' => '+212 664 35 82 16', 'company' => 'Tazi Logistics', 'address' => 'Zone franche, Tanger'],
            ['name' => 'Lina Idrissi', 'email' => 'lina@arganabio.ma', 'phone' => '+212 665 90 14 67', 'company' => 'Argana Bio', 'address' => 'Avenue Hassan II, Agadir'],
            ['name' => 'Karim Bennani', 'email' => 'karim@casahabitat.ma', 'phone' => '+212 666 71 03 54', 'company' => 'Casa Habitat', 'address' => 'Maarif, Casablanca'],
        ])->map(fn (array $client): Client => Client::create([
            ...$client,
            'notes' => 'Client professionnel suivi par l’équipe ClientFlow.',
        ]));

        $projects = collect([
            ['client' => 0, 'name' => 'Plateforme e-commerce Atlas', 'status' => ProjectStatus::InProgress, 'progress' => 68, 'budget' => 78000, 'deadline' => now()->addDays(42)],
            ['client' => 1, 'name' => 'Site de réservation multilingue', 'status' => ProjectStatus::InProgress, 'progress' => 42, 'budget' => 52000, 'deadline' => now()->addDays(65)],
            ['client' => 2, 'name' => 'Portail SaaS NovaDesk', 'status' => ProjectStatus::Planned, 'progress' => 12, 'budget' => 125000, 'deadline' => now()->addMonths(5)],
            ['client' => 3, 'name' => 'Tableau de bord logistique', 'status' => ProjectStatus::Completed, 'progress' => 100, 'budget' => 64000, 'deadline' => now()->subDays(12)],
            ['client' => 4, 'name' => 'Campagne digitale Argana', 'status' => ProjectStatus::OnHold, 'progress' => 35, 'budget' => 28000, 'deadline' => now()->addDays(28)],
        ])->map(function (array $projectData) use ($clients, $employees): Project {
            $project = Project::create([
                'client_id' => $clients[$projectData['client']]->id,
                'name' => $projectData['name'],
                'description' => 'Projet prioritaire avec suivi hebdomadaire et livrables validés par le client.',
                'status' => $projectData['status'],
                'progress' => $projectData['progress'],
                'budget' => $projectData['budget'],
                'start_date' => now()->subMonths(2),
                'deadline' => $projectData['deadline'],
            ]);
            $project->employees()->sync($employees->take(2)->pluck('id'));

            return $project;
        });

        $taskTitles = [
            'Valider les maquettes de la page d’accueil',
            'Intégrer le catalogue produits',
            'Configurer les paiements sécurisés',
            'Préparer les traductions françaises et anglaises',
            'Optimiser les requêtes du tableau de bord',
            'Organiser la recette client',
            'Documenter le déploiement',
            'Créer les rapports mensuels',
            'Finaliser les tests mobiles',
            'Préparer la démonstration commerciale',
        ];

        foreach ($taskTitles as $index => $title) {
            $completed = $index > 6;
            Task::create([
                'project_id' => $projects[$index % $projects->count()]->id,
                'assigned_to' => $employees[$index % $employees->count()]->id,
                'title' => $title,
                'description' => 'Tâche opérationnelle suivie dans le cadre du projet.',
                'priority' => $index % 3 === 0 ? TaskPriority::High : TaskPriority::Medium,
                'status' => $completed ? TaskStatus::Completed : ($index % 2 === 0 ? TaskStatus::InProgress : TaskStatus::Pending),
                'due_date' => now()->addDays(($index + 1) * 4),
                'completed_at' => $completed ? now()->subDays(2) : null,
            ]);
        }

        $invoiceDefinitions = [
            ['client' => 0, 'project' => 0, 'number' => 'CF-2026-0001', 'subtotal' => 30000, 'status' => InvoiceStatus::PartiallyPaid, 'payment' => 18000],
            ['client' => 1, 'project' => 1, 'number' => 'CF-2026-0002', 'subtotal' => 22000, 'status' => InvoiceStatus::Sent, 'payment' => 0],
            ['client' => 3, 'project' => 3, 'number' => 'CF-2026-0003', 'subtotal' => 64000, 'status' => InvoiceStatus::Paid, 'payment' => 76800],
            ['client' => 4, 'project' => 4, 'number' => 'CF-2026-0004', 'subtotal' => 14000, 'status' => InvoiceStatus::Overdue, 'payment' => 0],
            ['client' => 2, 'project' => 2, 'number' => 'CF-2026-0005', 'subtotal' => 15000, 'status' => InvoiceStatus::Draft, 'payment' => 0],
        ];

        foreach ($invoiceDefinitions as $index => $definition) {
            $taxAmount = $definition['subtotal'] * 0.2;
            $invoice = Invoice::create([
                'client_id' => $clients[$definition['client']]->id,
                'project_id' => $projects[$definition['project']]->id,
                'number' => $definition['number'],
                'issue_date' => now()->subDays(35 - ($index * 5)),
                'due_date' => $definition['status'] === InvoiceStatus::Overdue ? now()->subDays(8) : now()->addDays(15 + ($index * 3)),
                'status' => $definition['status'],
                'subtotal' => $definition['subtotal'],
                'tax_rate' => 20,
                'tax_amount' => $taxAmount,
                'total' => $definition['subtotal'] + $taxAmount,
                'currency' => 'MAD',
                'notes' => 'Paiement par virement bancaire. Merci pour votre confiance.',
            ]);

            $invoice->items()->create([
                'description' => 'Prestation projet — acompte et réalisation',
                'quantity' => 1,
                'unit_price' => $definition['subtotal'],
                'line_total' => $definition['subtotal'],
            ]);

            if ($definition['payment'] > 0) {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'recorded_by' => $admin->id,
                    'amount' => $definition['payment'],
                    'paid_at' => now()->subDays(6),
                    'method' => PaymentMethod::BankTransfer,
                    'reference' => 'VIR-CF-'.$invoice->id,
                    'notes' => 'Paiement confirmé sur le compte professionnel.',
                ]);
            }
        }

        ActivityLog::factory()->count(8)->create(['user_id' => $admin->id]);
    }
}
