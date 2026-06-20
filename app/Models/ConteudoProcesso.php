<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConteudoProcesso extends Model
{
    use BelongsToTenant;

    protected $table = 'conteudo_processos';

    protected $fillable = [
        'processo_id',
        'empresa_id',
        'tenant',
        'numero_processo',
        'conteudo',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }
}
