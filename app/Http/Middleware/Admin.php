<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseFormatter;
use App\Models\User\Admin as UserAdmin;
use App\Models\User\UserTailor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->user('sanctum')->uuid, UserAdmin::pluck('uuid')->toArray())) {
            return ResponseFormatter::error(["message" => "Kamu tidak memiliki akses"], 'Forbidden', 403);
        }
        return $next($request);
    }
}
