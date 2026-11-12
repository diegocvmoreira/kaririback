# Patch de sincronização do Kariri API

Este pacote contém arquivos completos para atualizar o backend Laravel hospedado na Hostinger, considerando PHP 8.5.12.

## Arquivos incluídos

- `database/migrations/2026_10_23_000002_create_reviews_table.php`
- `database/migrations/2026_10_25_000001_sync_production_schema.php`
- `app/Models/User.php`
- `app/Models/Place.php`
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/PlaceController.php`
- `app/Http/Requests/StorePlaceRequest.php`
- `app/Http/Requests/UpdatePlaceRequest.php`
- `app/Http/Resources/PlaceResource.php`
- `deploy/hostinger_schema_patch.sql`

A migration de reviews foi tornada idempotente porque o dump SQL já contém a tabela `reviews`, mas a tabela `migrations` não registra a migration correspondente.

A migration nova é idempotente e adiciona campos de perfil, capa/galeria de locais e `business_claims`. Também converte locais `active` para `published` e `inactive` para `archived`.

## Escolha UMA estratégia de banco

### Estratégia A — PHP CLI disponível no painel

1. Faça backup completo pelo phpMyAdmin.
2. Substitua os arquivos PHP deste pacote mantendo os caminhos.
3. Não execute `deploy/hostinger_schema_patch.sql`.
4. Na raiz do Laravel, execute:

```bash
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
```

A migration idempotente de reviews deve ser enviada antes de executar `migrate`.

### Estratégia B — sem SSH e sem PHP CLI

1. Faça backup completo pelo phpMyAdmin.
2. No phpMyAdmin, selecione o banco correto.
3. Abra a aba **SQL**.
4. Cole e execute `deploy/hostinger_schema_patch.sql` uma única vez.
5. Confirme que as colunas de `users`, a tabela `business_claims`, as colunas de mídia em `places` e o enum de status foram criados.
6. Não execute `php artisan migrate` depois desse SQL, a menos que você tenha conferido a tabela `migrations` e os arquivos enviados.
7. Envie os arquivos PHP do pacote pelo Gerenciador de Arquivos/FTP.

O SQL marca como aplicadas somente migrations que ele próprio cobre. Não execute o SQL depois de uma execução bem-sucedida de `php artisan migrate`.

## Segurança antes do deploy

1. Faça backup do banco atual.
2. Revogue tokens Sanctum existentes.
3. Troque credenciais que tenham sido incluídas no `.env` ou no SQL compartilhado.
4. Não envie `.env`, dumps SQL, `database/database.sqlite` ou logs junto com o código.
5. Mantenha `APP_DEBUG=false`.
6. Confirme PHP 8.5.12 e extensões Laravel no painel da Hostinger.

## Upload do código

1. O document root do domínio deve apontar para `kariri-api/public`.
2. Não deixe `.env`, `app`, `routes` ou `vendor` acessíveis pela URL pública.
3. Envie os arquivos para a raiz Laravel, preservando a estrutura de diretórios.
4. Não sobrescreva o `.env` de produção.
5. Remova arquivos `.backup.php`, dumps, banco SQLite e logs do pacote final.

## Configuração do frontend

No Google AI Studio, use:

```env
VITE_API_BASE_URL=https://kariri.app.br/kariri-api/public/index.php/api
VITE_USE_MOCK_DATA=false
```

No CORS do backend, mantenha somente o domínio publicado do frontend e previews necessários. Depois que o frontend sair do AI Studio, remova padrões amplos de preview.

## Validação pós-deploy

Faça primeiro testes de leitura:

```text
GET /api/health
GET /api/places
GET /api/places?city=crato
GET /api/events
GET /api/cities
GET /api/categories
GET /api/search?q=crato
```

Depois teste em staging: cadastro, login, `/me`, recuperação de senha, favoritos, review, claim e painel administrativo.
