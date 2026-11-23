<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Identity\Abilities;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Gate::allows(Abilities::ACCESS_ADMIN), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
