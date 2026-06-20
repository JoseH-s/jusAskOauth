<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    /**
     * Troca a empresa (tenant) ativa do usuario. So permite empresas
     * das quais o usuario e membro ativo.
     */
    public function trocar(Request $request)
    {
        $validated = $request->validate([
            'empresa_id' => ['required', 'integer'],
        ]);

        $membro = $request->user()->membros()
            ->where('empresa_id', $validated['empresa_id'])
            ->where('ativo', true)
            ->first();

        abort_unless($membro, 403, 'Você não é membro desta empresa.');

        $request->session()->put('empresa_ativa_id', $membro->empresa_id);

        return redirect()->route('home')
            ->with('status', 'Empresa ativa alterada com sucesso.');
    }
}
