<?php

namespace App\Livewire\ChavesGemini;

use App\Models\ChaveGemini;
use App\Services\TenantManager;
use Livewire\Component;

class GerenciarChavesGemini extends Component
{
    public ?int $chaveId = null;
    public bool $mostrarForm = false;

    public string $apelido = '';
    public string $chave = '';

    public function mount(): void
    {
        if (! app(TenantManager::class)->check()) {
            redirect()->route('home');
        }
    }

    protected function rules(): array
    {
        return [
            'apelido' => ['required', 'string', 'max:100'],
            'chave'   => ['required', 'string', 'max:500'],
        ];
    }

    public function novo(): void
    {
        $this->resetForm();
        $this->mostrarForm = true;
    }

    public function editar(int $id): void
    {
        $chaveGemini = ChaveGemini::findOrFail($id);

        $this->chaveId = $chaveGemini->id;
        $this->apelido = $chaveGemini->apelido;
        $this->chave   = $chaveGemini->chave;   // exibe a chave real no form de edição

        $this->mostrarForm = true;
    }

    public function salvar(): void
    {
        $this->validate();

        if ($this->chaveId) {
            ChaveGemini::findOrFail($this->chaveId)->update([
                'apelido' => $this->apelido,
                'chave'   => $this->chave,
            ]);
            session()->flash('status', 'Chave atualizada com sucesso.');
        } else {
            ChaveGemini::create([
                'apelido' => $this->apelido,
                'chave'   => $this->chave,
            ]);
            session()->flash('status', 'Chave cadastrada com sucesso.');
        }

        $this->resetForm();
        $this->mostrarForm = false;
    }

    public function excluir(int $id): void
    {
        ChaveGemini::findOrFail($id)->delete();
        session()->flash('status', 'Chave removida. Clientes vinculados foram desvinculados automaticamente.');
    }

    public function cancelar(): void
    {
        $this->resetForm();
        $this->mostrarForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['chaveId', 'apelido', 'chave']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.chaves-gemini.gerenciar-chaves-gemini', [
            'chaves' => ChaveGemini::orderBy('apelido')->get(),
        ])->extends('layouts.app');
    }
}
