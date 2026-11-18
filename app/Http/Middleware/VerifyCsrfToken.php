<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Les URI exclues de la vérification CSRF automatique
     */
    protected $except = [
        '/',
        '',
        'login',
        'logout',
        'gate/verify',
        'cimcim',
        'cimcim/',
        'cimcim/login',
        'cimcim/logout',
        'api/*',
        'siadoc/*',
        'cimcim/siadoc/*',
        'impression',
        'impression/*',
        'cimcim/impression',
        'cimcim/impression/*',
        'impression.php',
        'cimcim/impression.php',
        'corbeille',
        'corbeille/*',
        'cimcim/corbeille',
        'cimcim/corbeille/*',
        'corbeille.php',
        'cimcim/corbeille.php',
        'corbeille_admin.php',
        'cimcim/corbeille_admin.php',
        'verification',
        'verification/*',
        'cimcim/verification',
        'cimcim/verification/*',
        'verification.php',
        'cimcim/verification.php',
        'admin/signatures',
        'admin/signatures/*',
        'cimcim/admin/signatures',
        'cimcim/admin/signatures/*',
        'securite/signatures',
        'securite/signatures/*',
        'cimcim/securite/signatures',
        'cimcim/securite/signatures/*',
    ];

    /**
     * Vérifie la concordance des jetons CSRF (tolère à la fois _token et csrf_token, sessions Laravel et natives).
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token && $request->has('csrf_token')) {
            $token = $request->input('csrf_token');
        }

        if ($token) {
            if (isset($_SESSION['_cimis_csrf_token']) && is_string($_SESSION['_cimis_csrf_token']) && hash_equals($_SESSION['_cimis_csrf_token'], $token)) {
                return true;
            }
            if (isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
                return true;
            }
        }

        try {
            $sessionToken = $request->session()->token();
            return is_string($sessionToken) &&
                   is_string($token) &&
                   hash_equals($sessionToken, $token);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
