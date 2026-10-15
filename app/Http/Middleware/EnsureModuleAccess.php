<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = (string) $request->route()?->getName();
        $module = explode('.', $routeName, 2)[0];

        abort_unless(
            $user
                && array_key_exists($module, User::availableModules())
                && $user->canAccessModule($module),
            403,
            'You are not authorized to access this HMS module.',
        );

        return $next($request);
    }
}
