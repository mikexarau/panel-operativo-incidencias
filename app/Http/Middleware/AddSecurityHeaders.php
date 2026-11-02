<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * Sin scripts en línea: el tema se aplica desde public/theme-init.js. Los estilos en
     * línea se permiten porque los usan React (`style`) y la barra de progreso de Inertia.
     */
    private const CONTENT_SECURITY_POLICY = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
        ."img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; "
        ."base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->add([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ]);

        // El servidor de Vite en desarrollo necesita otro origen y un script en línea.
        if (! Vite::isRunningHot()) {
            $response->headers->set('Content-Security-Policy', self::CONTENT_SECURITY_POLICY);
        }

        return $response;
    }
}
