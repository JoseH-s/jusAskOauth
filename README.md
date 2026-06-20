
## Como executar

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=ClaudeDesktopClientSeeder
npm install
npm run build
php artisan serve
```

Para expor localmente via URL pública:

```bash
cloudflared tunnel --url http://localhost:8000
```
