<php

public function handle($request, Closure $next, ...$guards)
{
    $guards = empty($guards) ? [null] : $guards;

    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check()) {
            // Redirect berdasarkan guard
            if ($guard === 'karyawan') {
                return redirect('/dashboard');
            } elseif ($guard === 'user') {
                return redirect('/panel/dashboardadmin');
            }
        }
    }

    return $next($request);
}
