<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use League\Config\Exception\ValidationException;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

class AuthController extends Controller
{
    // 1. Obtenir le cookie CSRF pour Sanctum
    public function getCsrfCookie(Request $request)
    {
        // La méthode CsrfCookieController::__invoke gère l'envoi du cookie
        return (new CsrfCookieController())($request);
    }

    // 2. Connexion
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            // Laravel gère déjà le taux de tentatives échouées via le middleware 'throttle:3,2'
            // Si le taux est dépassé, une exception est lancée avant d'atteindre ce code.
            // Sinon, on retourne l'erreur standard de connexion.
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification sont incorrectes.'],
            ]);
        }

        // Récupère l'utilisateur et génère un token si nécessaire (pour les requêtes API après la première connexion)
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $request->user(),
        ]);
    }

    // 3. Déconnexion
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    // 4. Mot de passe oublié (Demande de lien)
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // La logique complète de réinitialisation est déjà dans Laravel.
        // Utilisez l'API de Laravel pour l'envoi de l'email.
        // Assurez-vous que l'envoi d'e-mails est configuré dans .env

        // Logique simplifiée :
        $status = $this->broker()->sendResetLink(
            $request->only('email')
        );

        if ($status == \Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Lien de réinitialisation envoyé par email.'], 200);
        }

        return response()->json(['email' => [trans($status)]], 422);
    }

    // 5. Réinitialisation effective
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        // Utiliser la façade Password::
        $status = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->setRememberToken(null)->save();
            }
        );

        if ($status == \Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mot de passe réinitialisé avec succès.'], 200);
        }

        return response()->json(['email' => [trans($status)]], 422);
    }

    // Aide pour accéder au "Password Broker" de Laravel
    protected function broker()
    {
        return \Password::broker();
    }
}

