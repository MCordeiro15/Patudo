# Base de Dados e Arquivos Patudo

## O que está incluído no Git

- **Base de dados SQLite** (`database/database.sqlite`) — já vem com todos os dados de teste
- **Fotos de petsitters** (`storage/app/public/petsitters/`) — fotos de perfil já incluídas
- **Symlink de storage** (`public/storage`) — link para as fotos e arquivos públicos

## Para começar (após `git pull`)

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

3. **Garantir que o symlink de storage existe**
   ```bash
   php artisan storage:link
   ```
   > O symlink já vem do Git, mas este comando garante que está correto no teu sistema.

4. **Rodar a aplicação**
   ```bash
   npm run dev
   php artisan serve
   ```

5. **Fazer login** com as credenciais abaixo

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

- **A base de dados já vem do Git** — não precisas rodar `migrate` nem `seed` na primeira vez
- **Não modifiques a base de dados diretamente** se quiseres manter os dados sincronizados com a equipa
- Se precisares de adicionar novos dados de teste, atualiza os **seeders** em vez de modificar o SQLite diretamente
- Para resetar a base de dados para o estado do Git:
  ```bash
  git checkout database/database.sqlite
  ```

## Seeders (opcional)

Os seeders estão configurados para não sobrescrever users existentes. Para adicionar dados de teste sem perder users locais:

```bash
php artisan db:seed
```

Para reconstruir tudo do zero (apaga todos os dados e reseta para o Git):

```bash
git checkout database/database.sqlite
```

## Se as fotos não aparecem

1. Verifica se `public/storage` existe e aponta para `storage/app/public`:
   ```bash
   ls -la public/storage
   ```
2. Se não existe, cria o symlink:
   ```bash
   php artisan storage:link
   ```
3. Verifica se as fotos estão em `storage/app/public/petsitters/`:
   ```bash
   ls storage/app/public/petsitters/
   ```
4. Se as fotos não estão, faz `git pull` novamente:
   ```bash
   git pull origin main
   ```

## Erro: "unlink of file 'database/database.sqlite' failed: Should I try again"

Este erro aparece quando o Laravel (`php artisan serve`) está a rodar e mantém o banco de dados SQLite aberto, impedindo o Git de atualizar o arquivo.

**Solução — segue estes passos em ordem:**

1. **Para o servidor Laravel** (Ctrl+C no terminal onde rodou `php artisan serve`)
2. **Faz git pull novamente:**
   ```bash
   git pull origin main
   ```
3. **Se o erro persistir, reseta o arquivo manualmente:**
   ```bash
   git checkout database/database.sqlite
   ```
4. **Reinicia o servidor:**
   ```bash
   php artisan serve
   ```

**Importante:** Sempre para o `php artisan serve` antes de fazer `git pull` para evitar este erro.
