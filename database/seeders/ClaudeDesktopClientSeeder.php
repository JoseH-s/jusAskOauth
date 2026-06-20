<?php

namespace Database\Seeders;

use App\Models\OauthClient;
use Illuminate\Database\Seeder;

/**
 * Cria (ou atualiza) o OAuth client público do Claude Desktop.
 *
 * Execute com:
 *   php artisan db:seed --class=ClaudeDesktopClientSeeder
 *
 * O Claude Desktop usa PKCE (sem client_secret), então client_secret fica null.
 * O redirect_uri aceito é o loopback que o Claude Desktop abre localmente.
 */
class ClaudeDesktopClientSeeder extends Seeder
{
    public function run(): void
    {
        OauthClient::updateOrCreate(
            ['client_id' => 'claude-desktop'],
            [
                'name'          => 'Claude Desktop',
                'client_secret' => null, // PKCE — public client
                'redirect_uris' => json_encode([
                    'http://localhost',
                    'http://127.0.0.1',
                ]),
            ]
        );

        $this->command->info('Claude Desktop OAuth client criado/atualizado.');
    }
}
