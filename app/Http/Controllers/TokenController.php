<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\TokenCnj;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function telaCnj(string $tenant, string $data)
    {
        $hoje = Carbon::now()->toDateString();
        if ($data !== $hoje) {
            abort(404);
        }
        abort_unless(Empresa::where('tenant', $tenant)->exists(), 404);

        return view('token-cnj', compact('tenant'));
    }

    public function store(Request $request, string $tenant)
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        abort_if(trim($tenant) === '', 404);
        abort_unless(Empresa::where('tenant', $tenant)->exists(), 404);

        TokenCnj::create([
            'token' => $request->input('token'),
            'tenant' => $tenant,
        ]);

        return back()->with('status', 'Token salvo.');
    }
}
