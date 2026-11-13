# Base de Dados Patudo

## Como ter os dados de teste

Tudo é **100% automático**. Zero comandos manuais para o banco de dados.

### Primeira configuração (após clonar o repositório)

```bash
# 1. Instalar dependências
composer install
npm install

# 2. Configurar o ambiente (se não tiveres .env)
cp .env.example .env
php artisan key:generate

# 3. Configurar os git hooks (só uma vez!)
# Este comando configura tudo automaticamente
bash scripts/setup-hooks.sh

# 4. Rodar a aplicação
npm run dev
php artisan serve
```

### Após `git pull` (atualizações futuras)

**Tudo é automático.** Só precisas de:

```bash
git pull origin main
npm run dev
php artisan serve
```

O banco de dados é atualizado automaticamente a cada `git pull`. O storage link é criado sozinho. Zero comandos manuais.

## Como funciona o sistema automático

**Quando tu fazes `git push`:**
- Um git hook exporta automaticamente todos os dados (users, petsitters, animais, posts, bookings, reviews, fotos)
- Os dados são guardados em `ExportedDataSeeder.php` e incluídos no push

**Quando os colegas fazem `git pull`:**
- Um git hook deteta que os dados mudaram
- Roda automaticamente `migrate:fresh --seed` para atualizar o banco de dados
- Cria automaticamente o storage link para as fotos aparecerem
- Os colegas recebem tudo sem rodar nenhum comando manual

**Resumo:** Criaste uma conta, fizeste uma reserva, subiste uma foto? Faz `git push` e todos os colegas recebem tudo com `git pull`.

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
- Tudo é automático: git push exporta os dados, git pull atualiza o banco de dados e o storage link
- Zero comandos manuais necessários após `git pull`
- `npm run dev` e `php artisan serve` são os únicos comandos para rodar a aplicação
