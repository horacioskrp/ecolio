<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirige vers l'écran de changement de mot de passe tant que l'utilisateur
 * porte le drapeau `must_change_password` (comptes de démonstration, mots de
 * passe réinitialisés par un administrateur).
 *
 * Les routes indispensables (changement du mot de passe lui-même, déconnexion,
 * 2FA) restent accessibles pour ne pas enfermer l'utilisateur.
 */
class ForcePasswordChange
{
    /** Routes toujours autorisées, même avec un mot de passe à changer. */
    private const ALLOWED = [
        'user-password.edit',
        'user-password.update',
        'logout',
        'verification.notice',
        'verification.send',
        'verification.verify',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        $route = $request->route()?->getName();

        if ($route && (in_array($route, self::ALLOWED, true) || str_starts_with($route, 'two-factor'))) {
            return $next($request);
        }

        // Ne jamais intercepter l'API du portail ni les requêtes non-GET hors allowlist.
        if ($request->is('api/*')) {
            return $next($request);
        }

        return redirect()
            ->route('user-password.edit')
            ->with('warning', 'Pour des raisons de sécurité, vous devez définir un nouveau mot de passe avant de continuer.');
    }
}
