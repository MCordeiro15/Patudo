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

## Exportação automática de dados

Quando fazes `git push`, um **git hook** exporta automaticamente todos os dados do teu banco de dados (users, petsitters, animais, posts, bookings, reviews, fotos) para um seeder.

**Como funciona:**

1. Criaste uma conta nova, fizeste uma reserva, subiste uma foto de perfil, etc.
2. Rodas `git push origin main`
3. O git hook roda `php artisan db:export` automaticamente
4. Todos os dados são exportados para `ExportedDataSeeder.php`
5. O seeder é commitado e pushado
6. Os colegas fazem `git pull` e `php artisan migrate:fresh --seed` para receberem tudo

**Para os colegas configurarem o git hook (uma vez após `git pull`):**

```bash
bash scripts/setup-hooks.sh
```

Ou manualmente:

```bash
cp scripts/pre-push.sh .git/hooks/pre-push
chmod +x .git/hooks/pre-push
```

## O que é criado pelos seeders

- **12 Users**: Admin, Test User, Miguel, 6 Petsitters (Sofia, Tiago, João, Inês, Maria, Rui) e 3 Clientes (Ana, Pedro, Carla)
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
| Miguel | `miguel@gmail.com` | `password` |
| Ana (Cliente) | `client1@patudo.test` | `password` |
| Pedro (Cliente) | `client2@patudo.test` | `password` |
| Carla (Cliente) | `client3@patudo.test` | `password` |

## Comandos úteis

- `php artisan migrate:fresh --seed` — Resetar tudo e recomeçar do zero
- `php artisan db:seed` — Adicionar dados de teste sem apagar o que já existe
- `php artisan db:export` — Exportar manualmente todos os dados para o seeder
- `php artisan storage:link` — Criar symlink para as fotos aparecerem

## Importante

- **Nunca** versiones `database/database.sqlite` no Git — está no `.gitignore`
- Cada colega gera a sua própria base de dados com `migrate:fresh --seed`
- O git hook exporta automaticamente os dados antes de cada push
- Se as fotos de perfil não aparecem, roda `php artisan storage:link`
