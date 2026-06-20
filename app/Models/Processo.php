<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Processo extends Model
{
    use BelongsToTenant;

    protected $table = 'processos';

    protected $attributes = ['ativo' => true];

    protected $fillable = [
        'cliente_id',
        'empresa_id',
        'tenant',
        'numero',
        'ultima_atualizacao',
        'ativo',
        'data_hora_ajuizamento',
        'valor_acao',
        'data_hora_ultima_distribuicao',
        'assunto',
    ];

    protected function casts(): array
    {
        return [
            'ultima_atualizacao'            => 'datetime',
            'ativo'                         => 'boolean',
            'data_hora_ajuizamento'         => 'datetime',
            'data_hora_ultima_distribuicao' => 'datetime',
            'valor_acao'                    => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function conteudos(): HasMany
    {
        return $this->hasMany(ConteudoProcesso::class);
    }

    public function processosConteudos(): HasMany
    {
        return $this->hasMany(ProcessoConteudo::class);
    }

    public function contatos(): HasMany
    {
        return $this->hasMany(ProcessoContato::class);
    }

    public function notificacoes(): HasMany
    {
        return $this->hasMany(Notificacao::class);
    }
}
