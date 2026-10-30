<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequirement;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DocumentRequirementController extends Controller
{
    
    public function index(): InertiaResponse
    {
        $requirements = DocumentRequirement::ordered()
            ->withTrashed()
            ->get();

        return Inertia::render('Admin/DocumentRequirements/Index', [
            'requirements' => $requirements,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/DocumentRequirements/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'slug' => 'required|string|max:150|alpha_dash|unique:document_requirements,slug',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['slug'] = strtolower(str_replace('_', '-', $data['slug']));
        $data['created_by'] = auth()->id();

        DocumentRequirement::create($data);

        return redirect()->route('admin.document-requirements.index')
            ->with('success', 'Document requirement created.');
    }

    public function edit(DocumentRequirement $documentRequirement): InertiaResponse
    {
        return Inertia::render('Admin/DocumentRequirements/Edit', [
            'requirement' => $documentRequirement,
        ]);
    }

    public function update(Request $request, DocumentRequirement $documentRequirement): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'slug' => 'required|string|max:150|alpha_dash|unique:document_requirements,slug,' . $documentRequirement->id,
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['slug'] = strtolower(str_replace('_', '-', $data['slug']));

        $documentRequirement->update($data);

        return redirect()->route('admin.document-requirements.index')
            ->with('success', 'Document requirement updated.');
    }

    public function destroy(DocumentRequirement $documentRequirement): RedirectResponse
    {
        $documentRequirement->delete();

        return redirect()->route('admin.document-requirements.index')
            ->with('success', 'Document requirement deleted.');
    }

    public function restore($id): RedirectResponse
    {
        $requirement = DocumentRequirement::withTrashed()->findOrFail($id);
        $requirement->restore();

        return redirect()->route('admin.document-requirements.index')
            ->with('success', 'Document requirement restored.');
    }
}
