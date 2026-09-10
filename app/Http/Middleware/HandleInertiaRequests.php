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
        $user = $request->user();
        if ($user) {
            $user->loadMissing('branch:id,name');
        }
        return array_merge(parent::share($request), [
            'auth' => [
                'user'  => $user ?? null,
                'roles' => $user?->getRoleNames()->values()->all() ?? [],
                'permissions' => $user
                    ? $user->getAllPermissions()
                        ->pluck('name')
                        ->values()
                        ->all()
                    : [],
                'primary_role' => $user?->primaryRole(),
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