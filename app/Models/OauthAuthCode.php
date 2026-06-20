<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthAuthCode extends Model
{
    protected $fillable = [
        'code',
        'user_id',
        'tenant',
        'client_id',
        'redirect_uri',
        'code_challenge',
        'code_challenge_method',
        'scope',
        'used',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'used'       => 'boolean',
            'expires_at' => 'datetime',
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

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
