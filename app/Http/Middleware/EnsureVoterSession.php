<?php

namespace App\Http\Middleware;

use App\Models\Voter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVoterSession
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $voterId = $request->session()->get('voter_id');

        if (! $voterId) {
            return redirect()->route('bilik.login')->with('error', 'Silakan masukkan token pemilih terlebih dahulu.');
        }

        $voter = Voter::find($voterId);

        if (! $voter || $voter->has_voted) {
            $request->session()->forget(['voter_id', 'voter_name', 'voter_class', 'voter_category', 'voter_category_label']);

            return redirect()->route('bilik.login')->with('error', 'Token ini sudah digunakan untuk memilih dan tidak bisa digunakan lagi.');
        }

        return $next($request);
    }
}
