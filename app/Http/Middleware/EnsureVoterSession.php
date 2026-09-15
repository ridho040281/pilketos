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
            $request->session()->forget(['voter_id', 'voter_name', 'voter_class']);

            return redirect()->route('bilik.login')->with('error', 'Hak suara untuk token ini telah digunakan atau tidak valid.');
        }

        return $next($request);
    }
}
