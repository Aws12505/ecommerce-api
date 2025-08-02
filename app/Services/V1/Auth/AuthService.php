<?php

namespace App\Services\V1\Auth;

use App\Models\User;
use App\Models\Currency;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Mail\WelcomeEmail; 
use Illuminate\Support\Facades\Mail; 

class AuthService
{
    public function register(array $data): array
    {
        // Get default currency or fallback to USD
        $defaultCurrency = Currency::default()->first()?->code ?? 'USD';
        
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => null,
            'currency' => $data['currency'] ?? $defaultCurrency,
        ]);

        $user->assignRole('customer');

        $user->sendEmailVerificationNotification();

        return [
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'currency']),
                'currency_details' => $user->getCurrencyDetails(),
                'requires_verification' => true
            ],
            'message' => 'Registration successful. Please check your email for verification.'
        ];
    }

    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Please verify your email address before logging in.']
            ]);
        }

        // Revoke existing tokens if needed (optional)
        if (config('auth.single_session', false)) {
            $user->tokens()->delete();
        }

        $token = $user->createToken('api-token', ['*'], now()->addDays(30))->plainTextToken;
        
        return [
            'data' => [
                'user' => $user->load(['roles.permissions', 'preferredCurrency']),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(30)->toISOString(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'roles' => $user->getRoleNames(),
                'currency_details' => $user->getCurrencyDetails(),
            ],
            'message' => 'Login successful'
        ];
    }

    public function logout(): void
    {
        Auth::user()->currentAccessToken()->delete();
    }

    public function logoutEverywhere(): void
    {
        Auth::user()->tokens()->delete();
    }

    public function refreshToken(): array
    {
        $user = Auth::user();
        $user->currentAccessToken()->delete();
        
        $token = $user->createToken('api-token', ['*'], now()->addDays(30))->plainTextToken;
        
        return [
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(30)->toISOString(),
                'currency_details' => $user->getCurrencyDetails(),
            ],
            'message' => 'Token refreshed successfully'
        ];
    }

    public function resendVerification(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.']
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return [
                'data' => null,
                'message' => 'Email is already verified.'
            ];
        }

        $user->sendEmailVerificationNotification();

        return [
            'data' => null,
            'message' => 'Verification email sent successfully.'
        ];
    }

    public function verifyEmail(Request $request, $id, $hash): array
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw ValidationException::withMessages([
                'verification' => ['Invalid verification link.']
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return [
                'data' => null,
                'message' => 'Email is already verified.'
            ];
        }

        $user->markEmailAsVerified();
        Mail::to($user)->send(new WelcomeEmail($user));

        return [
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'email_verified_at', 'currency']),
                'currency_details' => $user->getCurrencyDetails(),
                'verified' => true
            ],
            'message' => 'Email verified successfully.'
        ];
    }

    public function sendResetLink(array $data): array
    {
        $status = Password::sendResetLink(['email' => $data['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)]
            ]);
        }

        return [
            'data' => null,
            'message' => 'Password reset link sent to your email.'
        ];
    }

    public function resetPassword(array $data): array
    {
        $status = Password::reset(
            $data,
            function (User $user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all tokens after password reset
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)]
            ]);
        }

        return [
            'data' => null,
            'message' => 'Password reset successfully. Please login with your new password.'
        ];
    }
}
