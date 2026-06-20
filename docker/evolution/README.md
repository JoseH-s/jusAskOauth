# Evolution API + Jus-Ask

Stack Docker da Evolution API v2 para atendimento via WhatsApp nao-oficial.

Fluxo:

```text
WhatsApp -> Evolution API -> Laravel webhook -> PDPJ/CNJ -> Gemini -> Evolution API -> WhatsApp
```

O cliente envia o numero do processo. O Laravel consulta o PDPJ, monta o contexto do processo e usa o Gemini para responder. Depois da primeira consulta, o contato pode continuar perguntando sobre o mesmo processo sem reenviar o numero; o contexto fica em cache por 7 horas.

## 1. Subir a Evolution no WSL

Pre-requisitos:

- Docker Desktop com integracao WSL2 ativa.
- Laravel rodando no host em `0.0.0.0:8000` quando for receber webhooks locais.

No WSL:

```bash
cd docker/evolution
cp .env.example .env

# Gere uma chave forte e cole em AUTHENTICATION_API_KEY no .env
openssl rand -hex 32

# Defina tambem POSTGRES_PASSWORD no .env
docker compose up -d
docker compose logs -f evolution-api
```

Manager:

```text
http://localhost:8080/manager
```

Use a chave `AUTHENTICATION_API_KEY` para autenticar.

## 2. Criar a instancia WhatsApp

Exemplo criando a instancia `jusask`:

```bash
curl -X POST http://localhost:8080/instance/create \
  -H "apikey: SUA_AUTHENTICATION_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"instanceName":"jusask","integration":"WHATSAPP-BAILEYS","qrcode":true}'
```

Escaneie o QR Code pelo WhatsApp do numero que vai atender:

```text
WhatsApp > Aparelhos conectados
```

## 3. Configurar o webhook por tenant

Suba o Laravel assim para o container conseguir acessar o host:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Registre o webhook apontando para o tenant da empresa:

```bash
curl -X POST http://localhost:8080/webhook/set/jusask \
  -H "apikey: SUA_AUTHENTICATION_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "webhook": {
      "enabled": true,
      "url": "http://host.docker.internal:8000/SEU_TENANT/webhooks/whatsapp?token=SEU_EVOLUTION_WEBHOOK_TOKEN",
      "webhookByEvents": false,
      "events": ["MESSAGES_UPSERT"]
    }
  }'
```

Exemplo:

```text
http://host.docker.internal:8000/minha-empresa/webhooks/whatsapp?token=abc123
```

Existe tambem a rota fallback sem tenant:

```text
POST /webhooks/whatsapp?tenant=SEU_TENANT&token=SEU_EVOLUTION_WEBHOOK_TOKEN
```

Prefira a rota com tenant no path.

## 4. Configurar o Laravel

No `.env` da raiz do projeto:

```env
EVOLUTION_API_URL=http://localhost:8080
EVOLUTION_API_KEY=SUA_AUTHENTICATION_API_KEY
EVOLUTION_INSTANCE=jusask
EVOLUTION_WEBHOOK_TOKEN=SEU_EVOLUTION_WEBHOOK_TOKEN

# Atendimento
TOKEN_API_PROCESSO=
GEMINI_API_KEY=
```

Preferencialmente cadastre o token CNJ e a chave Gemini pelo sistema, vinculados ao tenant/empresa. O fallback por `.env` existe para desenvolvimento local.

## 5. Testar

Envie uma mensagem para o numero conectado:

```text
0011632-42.2024.5.15.0033
```

Depois pergunte algo como:

```text
qual foi o ultimo andamento?
```

O bot deve responder usando o mesmo processo salvo em cache para aquele contato.

## Comandos uteis

```bash
docker compose ps
docker compose logs -f
docker compose restart
docker compose down
docker compose down -v
```

## Observacoes

- A Evolution API usa WhatsApp Web/Baileys e nao e uma API oficial da Meta.
- Use numero dedicado. Ha risco de bloqueio do numero em uso abusivo ou automacao agressiva.
- Mensagens de grupo e mensagens sem texto sao ignoradas.
- O webhook retorna HTTP 200 em erro interno para evitar reentrega em loop; verifique `storage/logs/laravel.log`.
