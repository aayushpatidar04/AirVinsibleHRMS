<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Integration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        $query = Integration::query()
            ->with('client');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%");
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $integrations = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get();
        $types = Integration::distinct()->pluck('type');

        return Inertia::render('Admin/Integrations/Index', [
            'integrations' => $integrations,
            'clients' => $clients,
            'types' => $types,
            'filters' => $request->only(['search', 'client_id', 'type', 'is_active']),
        ]);
    }

    public function create()
    {
        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Admin/Integrations/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'provider' => 'required|string|max:100',
            'config' => 'nullable|array',
            'api_key' => 'nullable|string|max:255',
            'api_secret' => 'nullable|string',
            'webhook_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['config'] = $data['config'] ?? [];

        $integration = Integration::create($data);

        return redirect()
            ->route('admin.integrations.show', $integration)
            ->with('success', 'Integration created successfully.');
    }

    public function show(Integration $integration)
    {
        $integration->load('client');

        return Inertia::render('Admin/Integrations/Show', [
            'integration' => $integration,
        ]);
    }

    public function edit(Integration $integration)
    {
        $clients = Client::active()->orderBy('name')->get();

        return Inertia::render('Admin/Integrations/Edit', [
            'integration' => $integration,
            'clients' => $clients,
        ]);
    }

    public function update(Request $request, Integration $integration)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'provider' => 'required|string|max:100',
            'config' => 'nullable|array',
            'api_key' => 'nullable|string|max:255',
            'api_secret' => 'nullable|string',
            'webhook_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['config'] = $data['config'] ?? [];

        $integration->update($data);

        return redirect()
            ->route('admin.integrations.show', $integration)
            ->with('success', 'Integration updated successfully.');
    }

    public function destroy(Integration $integration)
    {
        $integration->delete();

        return redirect()
            ->route('admin.integrations.index')
            ->with('success', 'Integration deleted successfully.');
    }
}
