<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\QrCode;
use App\Models\RegistrationForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QRCodeController extends Controller
{
    public function index(Request $request)
    {
        $qrCodes = QrCode::with('branch', 'form', 'creator')
            ->when($request->branch_id, fn ($q, $b) => $q->where('branch_id', $b))
            ->when($request->status === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/QRCodes/Index', [
            'qrCodes'  => $qrCodes->through(fn ($q) => [
                'id'          => $q->id,
                'uuid'        => $q->uuid,
                'label'       => $q->label,
                'branch'      => $q->branch->name,
                'form'        => $q->form->name,
                'usage_count' => $q->usage_count,
                'expiry'      => $q->expiry_date?->format('d M Y'),
                'is_active'   => $q->is_active,
                'is_expired'  => $q->isExpired(),
                'url'         => $q->getRegistrationUrl(),
            ]),
            'branches' => Branch::active()->get(['id', 'name']),
            'filters'  => $request->only(['branch_id', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/QRCodes/Create', [
            'branches' => Branch::active()->get(['id', 'name']),
            'forms'    => RegistrationForm::active()->with('branch')->get()->map(fn ($f) => [
                'id'   => $f->id,
                'name' => $f->name . ($f->branch ? " ({$f->branch->name})" : ' (Global)'),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'       => 'required|string|max:255',
            'branch_id'   => 'required|exists:branches,id',
            'form_id'     => 'required|exists:registration_forms,id',
            'expiry_date' => 'nullable|date|after:now',
        ]);

        $qr = QrCode::create(array_merge($data, [
            'uuid'       => Str::uuid()->toString(),
            'is_active'  => true,
            'created_by' => Auth::id(),
        ]));

        return redirect()
            ->route('admin.qrcodes.show', $qr)
            ->with('success', 'QR Code generated.');
    }

    public function show(QrCode $qrcode)
    {
        $url = $qrcode->getRegistrationUrl();

        return Inertia::render('Admin/QRCodes/Show', [
            'qrCode' => [
                'id'          => $qrcode->id,
                'uuid'        => $qrcode->uuid,
                'label'       => $qrcode->label,
                'branch'      => $qrcode->branch->name,
                'form'        => $qrcode->form->name,
                'url'         => $url,
                'usage_count' => $qrcode->usage_count,
                'expiry'      => $qrcode->expiry_date?->format('d M Y H:i'),
                'is_active'   => $qrcode->is_active,
                'is_expired'  => $qrcode->isExpired(),
            ],
        ]);
    }

    public function toggle(QrCode $qrcode)
    {
        $qrcode->update(['is_active' => ! $qrcode->is_active]);
        $label = $qrcode->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "QR Code {$label}.");
    }

    public function destroy(QrCode $qrcode)
    {
        $qrcode->delete();

        return redirect()
            ->route('admin.qrcodes.index')
            ->with('success', 'QR Code deleted.');
    }
}