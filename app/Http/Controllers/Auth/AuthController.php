<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use League\Config\Exception\ValidationException;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

class AuthController extends Controller
{
    // Connexion améliorée
    public function login(LoginRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::where('email', $request->email)
                        ->with('membre', 'role')
                        ->first();

            // Vérifier si l'utilisateur existe
            if (!$user) {
                return response()->json([
                    'message' => 'Email inconnu dans notre système',
                    'success' => false,
                    'status' => 400
                ], 400);
            }

            // Vérifier si le compte est actif
            if (!$user->is_active) {
                return response()->json([
                    'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.',
                    'success' => false,
                    'status' => 403
                ], 403);
            }

            // Vérifier si le compte est verrouillé
            if ($user->isLocked()) {
                $remainingMinutes = now()->diffInMinutes($user->locked_until);
                return response()->json([
                    'message' => "Compte verrouillé. Réessayez dans {$remainingMinutes} minutes",
                    'success' => false,
                    'status' => 423
                ], 423);
            }

            // Vérifier le mot de passe
            if (!Hash::check($request->password, $user->password)) {
                $user->incrementLoginAttempts();

                $remainingAttempts = 5 - $user->login_attempts;
                return response()->json([
                    'message' => "Mot de passe incorrect. Il vous reste {$remainingAttempts} tentative(s)",
                    'success' => false,
                    'status' => 400
                ], 400);
            }

            // Réinitialiser les tentatives après connexion réussie
            $user->resetLoginAttempts();

            // Générer token d'API (Sanctum)
            $token = $user->createToken('auth_token')->plainTextToken;

            $allNotifications = $user->notifications;

            DB::commit();

            return response()->json([
                'message' => 'Utilisateur connecté avec succès',
                'success' => true,
                'status' => 200,
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'all_notifications' => $allNotifications
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Login error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Une erreur est survenue lors de la connexion',
                'success' => false,
                'status' => 500
            ], 500);
        }
    }

    // Demande de réinitialisation de mot de passe
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            // Générer token unique
            $token = Str::random(64);
            $expiresAt = now()->addHours(24);

            // Stocker le token
            DB::table('password_resets')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            // Générer URL de réinitialisation
            $resetUrl = config('app.frontend_url') . "/reset-password?token={$token}&email={$user->email}";

            // Envoyer l'email (à implémenter)
            // $this->sendResetPasswordEmail($user, $resetUrl);

            // En environnement de développement, retourner le token
            if (env('APP_ENV') === 'local') {
                return response()->json([
                    'message' => 'Email de réinitialisation envoyé',
                    'reset_token' => $token, // Seulement en dev
                    'reset_url' => $resetUrl, // Seulement en dev
                    'success' => true,
                    'status' => 200
                ], 200);
            }

            return response()->json([
                'message' => 'Un email de réinitialisation a été envoyé à votre adresse',
                'success' => true,
                'status' => 200
            ], 200);

        } catch (\Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Impossible d\'envoyer l\'email de réinitialisation',
                'success' => false,
                'status' => 500
            ], 500);
        }
    }

    // Réinitialisation du mot de passe
    public function resetPassword(ResetPasswordRequest $request)
    {
        DB::beginTransaction();

        try {
            // Vérifier le token
            $resetRecord = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('expires_at', '>', now())
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'message' => 'Token invalide ou expiré',
                    'success' => false,
                    'status' => 400
                ], 400);
            }

            // Vérifier que le token correspond
            if (!Hash::check($request->token, $resetRecord->token)) {
                return response()->json([
                    'message' => 'Token invalide',
                    'success' => false,
                    'status' => 400
                ], 400);
            }

            // Mettre à jour le mot de passe
            $user = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->login_attempts = 0;
            $user->locked_until = null;
            $user->save();

            // Supprimer le token utilisé
            DB::table('password_resets')->where('email', $request->email)->delete();

            // Supprimer tous les tokens de l'utilisateur (déconnexion de tous les appareils)
            $user->tokens()->delete();

            DB::commit();

            return response()->json([
                'message' => 'Mot de passe réinitialisé avec succès',
                'success' => true,
                'status' => 200
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reset password error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Impossible de réinitialiser le mot de passe',
                'success' => false,
                'status' => 500
            ], 500);
        }
    }

    // Déconnexion
    public function logout(Request $request)
    {
        try {
            // Révoquer le token actuel
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Déconnexion réussie',
                'success' => true,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la déconnexion',
                'success' => false,
                'status' => 500
            ], 500);
        }
    }

    // Vérifier le token et récupérer l'utilisateur
    public function checkUser(Request $request)
    {
        try {
            $user = $request->user()->load('membre', 'role');

            return response()->json([
                'user' => $user,
                'is_authenticated' => true,
                'success' => true,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Non authentifié',
                'success' => false,
                'status' => 401
            ], 401);
        }
    }

    // Inscription (optionnel)
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'Compte créé avec succès',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'success' => true,
                'status' => 201
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur lors de la création du compte',
                'success' => false,
                'status' => 500
            ], 500);
        }
    }
}

