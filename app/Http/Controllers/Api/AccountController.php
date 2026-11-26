<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeleteLearnerAccountRequest;
use App\Http\Requests\Api\ForgotLearnerPasswordRequest;
use App\Http\Requests\Api\LoginLearnerRequest;
use App\Http\Requests\Api\RegisterLearnerRequest;
use App\Http\Requests\Api\StoreConsentRequest;
use App\Http\Requests\Api\UpdateLearnerPasswordRequest;
use App\Http\Requests\Api\UpdateLearnerProfileRequest;
use App\Http\Resources\Api\LearnerResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AccountController extends Controller
{
    // FR-L01, FR-S01, AC-02: public registration always creates a Learner.
    public function register(RegisterLearnerRequest $request): JsonResponse
    {
        $user = User::query()->create([
            ...$request->validated(),
            'role' => Role::Learner->value,
            'is_temp_password' => false,
            'consent_given' => false,
        ]);
        $token = $user->createToken('learner-api')->plainTextToken;

        return response()->json([
            'message' => 'Pendaftaran berhasil.',
            'data' => [
                'user' => new LearnerResource($user),
                'token' => $token,
                'consent_required' => true,
            ],
        ], 201);
    }

    // FR-L03, FR-S02, EC-01, AC-05, AC-07: never disclose whether an account exists or its role.
    public function login(LoginLearnerRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();
        $passwordMatches = $user !== null && Hash::check($request->validated('password'), $user->password);

        if (! $passwordMatches || $user->role !== Role::Learner->value) {
            return response()->json(['message' => 'Email atau password tidak sesuai.'], 401);
        }

        $token = $user->createToken('learner-api')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'data' => [
                'user' => new LearnerResource($user),
                'token' => $token,
                'consent_required' => ! $user->consent_given,
            ],
        ]);
    }

    // FR-L03: revoke the bearer token used for this request.
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Anda berhasil keluar.']);
    }

    // FR-L03, FR-L05, AB-11: return the authenticated Learner through a resource only.
    public function me(Request $request): LearnerResource
    {
        return new LearnerResource($request->user());
    }

    // FR-L02, AB-11, AC-04: persist consent and its server-side timestamp together.
    public function consent(StoreConsentRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill([
            'consent_given' => true,
            'consent_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Persetujuan penggunaan data berhasil disimpan.',
            'data' => [
                'consent_given' => $user->consent_given,
                'consent_at' => $user->consent_at->toIso8601String(),
                'consent_required' => false,
            ],
        ]);
    }

    // FR-L05, AB-16: do not allow profile updates to alter the account email.
    public function updateProfile(UpdateLearnerProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'business_name']))->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data' => new LearnerResource($user),
        ]);
    }

    // FR-L05, AB-16: verify the current password and preserve only this device's token.
    public function updatePassword(UpdateLearnerPasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $user->forceFill(['password' => $request->validated('password')])->save();

        $tokens = $user->tokens();
        if ($currentToken instanceof PersonalAccessToken) {
            $tokens->where('id', '!=', $currentToken->getKey());
        }
        $tokens->delete();

        return response()->json([
            'message' => 'Password berhasil diubah. Perangkat lain sudah keluar dari akun.',
        ]);
    }

    // FR-L04, AC-06: use Laravel's expiring, single-use password broker without enumeration.
    public function forgotPassword(ForgotLearnerPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $learnerExists = User::query()
            ->where('email', $email)
            ->where('role', Role::Learner->value)
            ->exists();

        if ($learnerExists) {
            Password::sendResetLink(['email' => $email]);
        }

        return response()->json([
            'message' => 'Jika email terdaftar pada Sinau, tautan reset password telah dikirim.',
        ]);
    }

    // FR-L06, keputusan 6, AC-43: anonymize identity and forum content atomically.
    public function deleteAccount(DeleteLearnerAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $user->forumPosts()->update([
                'content' => 'Konten dihapus karena akun penulis telah dihapus.',
            ]);

            $user->forceFill([
                'name' => 'Pengguna dihapus',
                'email' => 'deleted-'.$user->id.'-'.Str::random(24).'@deleted.invalid',
                'business_name' => null,
                'password' => Str::password(64, true, true, true),
                'is_temp_password' => false,
                'consent_given' => false,
                'consent_at' => null,
                'email_verified_at' => null,
                'remember_token' => null,
            ])->save();

            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json(['message' => 'Akun dan data personal Anda berhasil dihapus.']);
    }
}
