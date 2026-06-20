<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChaveGemini extends Model
{
    use BelongsToTenant;

    protected $table = 'chaves_gemini';

    protected $fillable = [
        'empresa_id',
        'tenant',
        'apelido',
        'chave',
    ];

    /**
     * Oculta a chave em arrays/JSON por padrão (ex: respostas de API).
     * Para acessar: $model->chave
     */
    protected $hidden = ['chave'];

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    /**
     * Retorna a chave mascarada para exibição segura na UI.
     * Ex: "AIzaSy***...***xQbT"
     */
    public function chaveMascarada(): string
    {
        $chave = $this->chave;
        $len = mb_strlen($chave);

        if ($len <= 8) {
            return str_repeat('*', $len);
        }

        return mb_substr($chave, 0, 4) . str_repeat('*', max(4, $len - 8)) . mb_substr($chave, -4);
    }
}
