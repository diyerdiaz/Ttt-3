<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->setDefault($response, 'X-Frame-Options', 'DENY');
        $this->setDefault($response, 'X-Content-Type-Options', 'nosniff');
        $this->setDefault($response, 'Referrer-Policy', 'same-origin');
        $this->setFrameAncestors($response);

        if ($request->isSecure() && app()->isProduction()) {
            $this->setDefault($response, 'Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function setDefault(Response $response, string $header, string $value): void
    {
        if (! $response->headers->has($header)) {
            $response->headers->set($header, $value);
        }
    }

    private function setFrameAncestors(Response $response): void
    {
        $directive = "frame-ancestors 'none'";
        $existing = $response->headers->get('Content-Security-Policy');

        if ($existing === null || $existing === '') {
            $response->headers->set('Content-Security-Policy', $directive);
        } elseif (! str_contains($existing, 'frame-ancestors')) {
            $response->headers->set('Content-Security-Policy', $existing.'; '.$directive);
        }
    }
}
