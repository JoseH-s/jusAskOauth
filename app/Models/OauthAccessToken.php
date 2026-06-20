<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthAccessToken extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'tenant',
        'client_id',
        'scope',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(OauthClient::class, 'client_id');
    }

    public function isValid(): bool
    {
        return is_null($this->revoked_at) && $this->expires_at->isFuture();
    }
}
