<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(12)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
            'birth_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'birth_city_name' => ['nullable', 'string', 'max:120'],
            'current_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'current_city_name' => ['nullable', 'string', 'max:120'],
            'current_city_ibge_code' => ['nullable', 'integer', 'between:1000000,9999999'],
            'current_city_state' => ['nullable', 'string', 'size:2'],
            'cariri_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'city_preference' => ['nullable', 'string', 'max:120'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $consent = (bool) ($data['marketing_consent'] ?? false);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'role' => 'user',
            'status' => 'active',
            'birth_city_id' => $data['birth_city_id'] ?? null,
            'birth_city_name' => $data['birth_city_name'] ?? null,
            'current_city_id' => $data['current_city_id'] ?? null,
            'current_city_name' => $data['current_city_name'] ?? null,
            'current_city_ibge_code' => $data['current_city_ibge_code'] ?? null,
            'current_city_state' => isset($data['current_city_state']) ? strtoupper($data['current_city_state']) : null,
            'cariri_city_id' => $data['cariri_city_id'] ?? null,
            'city_preference' => $data['city_preference'] ?? null,
            'marketing_consent' => $consent,
            'marketing_consent_at' => $consent ? now() : null,
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json([
            'user' => $this->userPayload($user),
            'requires_verification' => true,
            'message' => 'Conta criada. Verifique seu e-mail antes de entrar.',
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower(trim($data['email'])))->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'E-mail ou senha inválidos.'], 422);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Confirme seu e-mail antes de entrar. Solicite um novo link de verificação se necessário.',
                'requires_verification' => true,
            ], 403);
        }

        if (($user->status ?? 'active') !== 'active') {
            return response()->json(['message' => 'Usuário inativo.'], 403);
        }

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('kariri-web')->plainTextToken,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($this->userPayload($request->user()));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'birth_city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'birth_city_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'current_city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'current_city_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'current_city_ibge_code' => ['sometimes', 'nullable', 'integer', 'between:1000000,9999999'],
            'current_city_state' => ['sometimes', 'nullable', 'string', 'size:2'],
            'cariri_city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'city_preference' => ['sometimes', 'nullable', 'string', 'max:120'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('marketing_consent', $data)) {
            $data['marketing_consent'] = (bool) $data['marketing_consent'];
            $data['marketing_consent_at'] = $data['marketing_consent'] ? now() : null;
        }
        if (array_key_exists('current_city_state', $data) && $data['current_city_state'] !== null) {
            $data['current_city_state'] = strtoupper($data['current_city_state']);
        }

        $user = $request->user();
        $user->update($data);

        return response()->json($this->userPayload($user->fresh()));
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => strtolower(trim($request->string('email')->toString()))]);

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, enviaremos as instruções de recuperação.',
        ]);
    }

    public function resendVerification(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        $user = User::where('email', strtolower(trim($data['email'])))->first();
        if ($user && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Se a conta puder receber confirmação, um novo link será enviado.',
        ]);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(12)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Senha alterada. Faça login novamente.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(12)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        $status = Password::reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => 'Senha redefinida com sucesso.']);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['password' => ['required', 'string']]);
        $user = $request->user();

        if (!Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Senha incorreta.'], 422);
        }

        if ($user->role === 'admin') {
            return response()->json(['message' => 'Contas de administrador não podem ser excluídas por aqui.'], 403);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Conta excluída com sucesso.']);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing(['birthCity', 'currentCity', 'caririCity']);

        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'status' => $user->status,
            'city_preference' => $user->city_preference,
            'birth_city_id' => $user->birth_city_id,
            'birth_city_name' => $user->birth_city_name ?? $user->birthCity?->name,
            'current_city_id' => $user->current_city_id,
            'current_city_name' => $user->current_city_name ?? $user->currentCity?->name,
            'current_city_ibge_code' => $user->current_city_ibge_code,
            'current_city_state' => $user->current_city_state ?? $user->currentCity?->state,
            'cariri_city_id' => $user->cariri_city_id,
            'marketing_consent' => (bool) $user->marketing_consent,
            'marketing_consent_at' => $user->marketing_consent_at,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
        ];
    }
}
