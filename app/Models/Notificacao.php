<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacao extends Model
{
    use BelongsToTenant;

    protected $table = 'notificacoes';

    protected $fillable = [
        'processo_id',
        'empresa_id',
        'tenant',
        'titulo',
        'mensagem',
        'lida',
    ];

    protected function casts(): array
    {
        return ['lida' => 'boolean'];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }
}
