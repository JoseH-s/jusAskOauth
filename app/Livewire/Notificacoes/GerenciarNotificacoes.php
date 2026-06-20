<?php

namespace App\Livewire\Notificacoes;

use App\Models\Notificacao;
use App\Services\TenantManager;
use Livewire\Component;

class GerenciarNotificacoes extends Component
{
    public bool $apenasNaoLidas = false;
    public ?int $notificacaoAbertaId = null;

    public function mount(): void
    {
        if (! app(TenantManager::class)->check()) {
            redirect()->route('home');
        }
    }

    public function marcarLida(int $id): void
    {
        Notificacao::findOrFail($id)->update(['lida' => true]);
    }

    public function marcarTodasLidas(): void
    {
        Notificacao::where('lida', false)->update(['lida' => true]);
        session()->flash('status', 'Todas as notificações foram marcadas como lidas.');
    }

    public function abrirModal(int $id): void
    {
        $notificacao = Notificacao::findOrFail($id);
        $notificacao->update(['lida' => true]);

        $this->notificacaoAbertaId = $id;
    }

    public function fecharModal(): void
    {
        $this->notificacaoAbertaId = null;
    }

    public function render()
    {
        $query = Notificacao::with('processo')
            ->orderByDesc('created_at');

        if ($this->apenasNaoLidas) {
            $query->where('lida', false);
        }

        return view('livewire.notificacoes.gerenciar-notificacoes', [
            'notificacoes' => $query->paginate(20),
            'totalNaoLidas' => Notificacao::where('lida', false)->count(),
            'notificacaoAberta' => $this->notificacaoAbertaId
                ? Notificacao::with('processo')->find($this->notificacaoAbertaId)
                : null,
        ])->extends('layouts.app');
    }
}
