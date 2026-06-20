<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use Livewire\Component;

class ListaEmpresas extends Component
{
    public string $busca = '';

    public function render()
    {
        $empresas = Empresa::query()
            ->withCount(['membros', 'clientes', 'processos'])
            ->when($this->busca, fn ($q) => $q->where(function ($sub) {
                $sub->where('nome', 'like', "%{$this->busca}%")
                    ->orWhere('tenant', 'like', "%{$this->busca}%")
                    ->orWhere('cnpj', 'like', "%{$this->busca}%")
                    ->orWhere('oab', 'like', "%{$this->busca}%");
            }))
            ->orderBy('nome')
            ->get();

        return view('livewire.admin.lista-empresas', compact('empresas'))
            ->extends('layouts.app');
    }
}
