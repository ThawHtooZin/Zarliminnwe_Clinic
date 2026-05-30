<?php

namespace App\Http\Middleware;

use App\Domain\Administration\Services\PermissionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRoutePermission
{
    /**
     * @var array<int, string>
     */
    private array $exemptRoutes = [
        'login',
        'login.store',
        'help.index',
    ];

    public function __construct(private readonly PermissionResolver $permissionResolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if ($user === null) {
            abort(403);
        }

        if (in_array($routeName, $this->exemptRoutes, true)) {
            return $next($request);
        }

        if (! $this->permissionResolver->canAccessRoute($user, $routeName)) {
            abort(403);
        }

        return $next($request);
    }
}
