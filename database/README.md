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
- `php artisan storage:link` — Criar symlink para as fotos aparecerem
- `php artisan users:export-to-seeders` — Exportar users novos para os seeders automaticamente

## Adicionar users novos automaticamente

Se criaste uma conta nova e queres que os teus colegas também tenham:

1. **Cria a conta** normalmente na aplicação
2. **Roda o comando**:
   ```bash
   php artisan users:export-to-seeders
   ```
   O comando deteta automaticamente os users que não estão nos seeders e os adiciona.
3. **Faz commit e push**:
   ```bash
   git add -A
   git commit -m "Adicionar novos users aos seeders"
   git push origin main
   ```
4. **Os colegas recebem** com `git pull origin main` e `php artisan migrate:fresh --seed`

## Importante

- **Nunca** versiones `database/database.sqlite` no Git — está no `.gitignore`
- Cada colega gera a sua própria base de dados com `migrate:fresh --seed`
- Os seeders usam `firstOrCreate`, então rodar `db:seed` não apaga users que já existem
- O admin usa `updateOrCreate` para garantir que a password é sempre `admin1234`
- Se as fotos de perfil não aparecem, roda `php artisan storage:link`
