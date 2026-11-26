<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Aplica cabeçalhos de segurança HTTP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Impede que a aplicação seja embutida em iframes externos, mitigando ataques de Clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Obriga o navegador a seguir o Content-Type estrito, bloqueando ataques de MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Envia apenas a origem nas requisições cross-origin, protegendo dados sensíveis na URL de referência
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Bloqueia o acesso a APIs de hardware desnecessárias na aplicação (câmera, microfone e localização do dispositivo)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Para usuários autenticados, proíbe o armazenamento em caches intermediários para evitar vazamento de dados privados
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
