<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user'  => $request->user() ? [
                    'id'         => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name'  => $request->user()->last_name,
                    'email'      => $request->user()->email,
                    'phone'      => $request->user()->phone,
                    'branch_id'  => $request->user()->branch_id,
                    'is_active'  => $request->user()->is_active,
                ] : null,
                'roles' => $request->user()?->getRoleNames() ?? [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ]);
    }
}