# Base de Dados Patudo

## Como ter os dados de teste

Os dados de teste são gerados pelos **seeders** do Laravel. Não versionamos o arquivo SQLite no Git.

### Passo a passo (após `git pull`)

1. **Instalar dependências**
   ```bash
   composer install
   npm install
   ```

2. **Configurar o ambiente** (se não tiveres `.env`)
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Rodar migrations e seeders**
   ```bash
   php artisan migrate:fresh --seed
   ```
   Isso cria todas as tabelas e popula com os dados de teste.

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
| Admin | `admin@admin.com` | `admin1234` |
| Teste | `test@example.com` | `password` |
| Sofia (Petsitter) | `sofia@patudo.test` | `password` |
| Tiago (Petsitter) | `tiago@patudo.test` | `password` |
| João (Petsitter) | `joao@patudo.test` | `password` |
| Inês (Petsitter) | `ines@patudo.test` | `password` |
| Maria (Petsitter) | `maria@patudo.test` | `password` |
| Rui (Petsitter) | `rui@patudo.test` | `password` |

## Importante

- Roda `php artisan migrate:fresh --seed` sempre que quiseres resetar os dados para o estado dos seeders
- Os seeders usam `firstOrCreate`, então rodar `php artisan db:seed` não apaga users que já existem
- Se as fotos não aparecem, roda `php artisan storage:link`
