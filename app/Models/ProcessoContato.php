<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoContato extends Model
{
    use BelongsToTenant;

    protected $table = 'processo_contatos';

    protected $fillable = [
        'processo_id',
        'empresa_id',
        'tenant',
        'tipo',
        'valor',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }
}
