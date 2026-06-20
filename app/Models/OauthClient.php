<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OauthClient extends Model
{
    protected $fillable = [
        'name',
        'client_id',
        'client_secret',
        'redirect_uris',
    ];

    protected function casts(): array
    {
        return [
            'redirect_uris' => 'array',
        ];
    }

    public function authCodes(): HasMany
    {
        return $this->hasMany(OauthAuthCode::class, 'client_id');
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(OauthAccessToken::class, 'client_id');
    }

    public function allowsRedirectUri(string $uri): bool
    {
        // Aceita qualquer porta no loopback (127.0.0.1:XXXX ou localhost:XXXX)
        $allowed = $this->redirect_uris ?? [];

        foreach ($allowed as $allowed_uri) {
            if (str_starts_with($uri, $allowed_uri)) {
                return true;
            }
        }

        return false;
    }
}
