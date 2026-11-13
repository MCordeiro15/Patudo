# Base de Dados Patudo

## Como ter os dados de teste

Os dados de teste são gerados pelos **seeders** do Laravel. Não versionamos o arquivo SQLite no Git.

### Passo a passo (após `git pull`)

1. **Para o servidor** (se estiver a rodar)
   ```bash
   # Pressiona Ctrl+C no terminal onde php artisan serve está a rodar
   ```

2. **Instalar dependências**
   ```bash
   composer install
   npm install
   ```

3. **Configurar o ambiente** (se não tiveres `.env`)
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Rodar migrations e seeders**
   ```bash
   php artisan migrate:fresh --seed
   ```
   Isso cria todas as tabelas e popula com os dados de teste.

5. **Criar symlink para storage**
   ```bash
   php artisan storage:link
   ```

6. **Rodar a aplicação**
   ```bash
   npm run dev
   php artisan serve
   ```

## O que é criado pelos seeders

- **11 Users**: Admin, Test User, 6 Petsitters (Sofia, Tiago, João, Inês, Maria, Rui) e 3 Clientes (Ana, Pedro, Carla)
- **7 Petsitters**: Perfis completos com biografia, experiência, preço, localização e avaliações
- **10 Animais**: Pets associados a cada user
- **15 Posts**: Posts de exemplo dos petsitters
- **7 Bookings**: Reservas completadas com preços
- **7 Reviews**: Avaliações com estrelas e comentários

## Credenciais de Teste

| Tipo | Email | Password |
|------|-------|----------|
| **Admin** | `admin@admin.com` | `admin1234` |
| Teste | `test@example.com` | `password` |
| Sofia (Petsitter) | `sofia@patudo.test` | `password` |
| Tiago (Petsitter) | `tiago@patudo.test` | `password` |
| João (Petsitter) | `joao@patudo.test` | `password` |
| Inês (Petsitter) | `ines@patudo.test` | `password` |
| Maria (Petsitter) | `maria@patudo.test` | `password` |
| Rui (Petsitter) | `rui@patudo.test` | `password` |
| Ana (Cliente) | `client1@patudo.test` | `password` |
| Pedro (Cliente) | `client2@patudo.test` | `password` |
| Carla (Cliente) | `client3@patudo.test` | `password` |

## Comandos úteis

- `php artisan migrate:fresh --seed` — Resetar tudo e recomeçar do zero
- `php artisan db:seed` — Adicionar dados de teste sem apagar o que já existe
- `php artisan storage:link` — Criar symlink para as fotos aparecerem

## Importante

- **Nunca** versiones `database/database.sqlite` no Git — está no `.gitignore`
- Cada colega gera a sua própria base de dados com `migrate:fresh --seed`
- Os seeders usam `firstOrCreate`, então rodar `db:seed` não apaga users que já existem
- O admin usa `updateOrCreate` para garantir que a password é sempre `admin1234`
- Se as fotos de perfil não aparecem, roda `php artisan storage:link`
