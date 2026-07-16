<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Show login page
     */
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect based on role
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->isInterviewer()) {
                return redirect()->route('interviewer.dashboard');
            } else {
                return redirect()->route('candidate.dashboard');
            }
        }

        return back()
            ->withErrors(['email' => 'Invalid credentials'])
            ->onlyInput('email');
    }

    /**
     * Show registration page
     */
    public function showRegister()
    {
        return Inertia::render('Auth/Register', [
            'branches' => Branch::active()->get(['id', 'name']),
        ]);
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['required', 'string', 'unique:users'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'role' => ['required', 'in:interviewer,candidate'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        // Only interviewers need branch assignment during registration
        if ($validated['role'] === 'interviewer' && !$validated['branch_id']) {
            return back()
                ->withErrors(['branch_id' => 'Branch is required for interviewers'])
                ->onlyInput('email');
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Show forgot password page
     */
    public function showForgotPassword()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', trans($status));
        }

        return back()
            ->withErrors(['email' => trans($status)])
            ->onlyInput('email');
    }

    /**
     * Show reset password page
     */
    public function showResetPassword(string $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => request('email'),
            'token' => $token,
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', trans($status));
        }

        return back()
            ->withErrors(['email' => [trans($status)]])
            ->onlyInput('email');
    }

    /**
     * Show user profile
     */
    public function showProfile()
    {
        $user = Auth::user();

        return Inertia::render('Profile/Show', [
            'user' => $user,
            'branch' => $user->branch,
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['required', 'string', 'unique:users,phone,' . $user->id],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully');
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => [
                'required',
                'current_password',
            ],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::defaults(),
            ],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully');
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Get user notifications
     */
    public function notifications(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(15);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(Request $request, $notificationId)
    {
        $notification = $request->user()
            ->notifications()
            ->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notification marked as read');
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}