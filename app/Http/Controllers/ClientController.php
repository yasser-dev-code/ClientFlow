<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Models\ActivityLog;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $clients = Client::query()
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->withCount(['projects', 'invoices'])
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('clients.index', compact('clients', 'search'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('clients.form', ['client' => new Client]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());
        ActivityLog::create(['user_id' => $request->user()->id, 'subject_type' => Client::class, 'subject_id' => $client->id, 'action' => 'created', 'description' => __('app.activity.client_created', ['name' => $client->name])]);

        return redirect()->route('clients.show', $client)->with('success', __('app.saved'));
    }

    public function show(Client $client): View
    {
        $client->load(['projects' => fn ($query) => $query->latest()->limit(8), 'invoices' => fn ($query) => $query->latest()->limit(8)]);

        return view('clients.show', compact('client'));
    }

    public function edit(Request $request, Client $client): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('clients.form', compact('client'));
    }

    public function update(StoreClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('clients.show', $client)->with('success', __('app.saved'));
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $client->delete();

        return redirect()->route('clients.index')->with('success', __('app.deleted'));
    }
}
