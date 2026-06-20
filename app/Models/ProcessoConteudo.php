<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoConteudo extends Model
{
    use BelongsToTenant;

    protected $table = 'processos_conteudos';

    protected $fillable = [
        'processo_id',
        'empresa_id',
        'tenant',
        'numero_processo',
        'data_hora_ajuizamento',
        'valor_acao',
        'data_hora_ultima_distribuicao',
        'assunto',
        'conteudo_json',
    ];

    protected function casts(): array
    {
        return [
            'data_hora_ajuizamento'        => 'datetime',
            'data_hora_ultima_distribuicao' => 'datetime',
            'valor_acao'                   => 'decimal:2',
            'conteudo_json'                => 'array',
        ];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }
}
