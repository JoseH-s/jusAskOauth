<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use BelongsToTenant;

    protected $table = 'clientes';

    protected $fillable = [
        'empresa_id',
        'tenant',
        'nome',
        'telefone',
        'email',
        'cpf',
        'endereco',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'chave_gemini_id',
    ];

    public function processos(): HasMany
    {
        return $this->hasMany(Processo::class);
    }
}
