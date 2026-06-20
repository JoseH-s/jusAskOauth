<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Processo;
use App\Services\TenantManager;

class HomeController extends Controller
{
    public function index(TenantManager $tenant)
    {
        $dados = [
            'empresa' => $tenant->empresa(),
            'totalClientes' => null,
            'totalProcessos' => null,
            'processosAbertos' => null,
            'totalEmpresas' => null,
        ];

        if ($tenant->check()) {
            $dados['totalClientes'] = Cliente::count();
            $dados['totalProcessos'] = Processo::count();
            $dados['processosAbertos'] = Processo::where('ativo', true)->count();
        }

        if (auth()->user()->isSuperAdmin()) {
            $dados['totalEmpresas'] = Empresa::count();
        }

        return view('home', $dados);
    }
}
