# Base de Dados Patudo

## Configuração

A base de dados SQLite (`database.sqlite`) já está incluída no repositório Git para que todos os membros da equipa tenham os mesmos dados de teste.

### Para começar

1. **Fazer pull do repositório**
   ```bash
   git pull origin main
   ```

2. **Instalar dependências**
   ```bash
   composer install
   npm install
   ```

3. **Configurar o ambiente**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Criar symlink para storage**
   ```bash
   php artisan storage:link
   ```

5. **Rodar a aplicação**
   ```bash
   npm run dev
   php artisan serve
   ```

## Credenciais de Teste

| Tipo | Email | Password |
|------|-------|----------|
| Admin | `admin@admin.com` | `password` |
| Teste | `test@example.com` | `password` |
| Sofia (Petsitter) | `sofia@patudo.test` | `password` |
| Tiago (Petsitter) | `tiago@patudo.test` | `password` |
| João (Petsitter) | `joao@patudo.test` | `password` |
| Inês (Petsitter) | `ines@patudo.test` | `password` |
| Maria (Petsitter) | `maria@patudo.test` | `password` |
| Rui (Petsitter) | `rui@patudo.test` | `password` |

## Importante

- **Não modifiques a base de dados diretamente** se quiseres manter os dados sincronizados com a equipa.
- Se precisares de adicionar novos dados de teste, atualiza os **seeders** em vez de modificar o SQLite diretamente.
- Para resetar a base de dados para o estado do Git:
  ```bash
  git checkout database/database.sqlite
  ```

## Seeders

Os seeders estão configurados para não sobrescrever users existentes. Para adicionar dados de teste sem perder users locais:

```bash
php artisan db:seed
```

Para reconstruir tudo do zero (apaga todos os dados):

```bash
php artisan migrate:fresh --seed
```
