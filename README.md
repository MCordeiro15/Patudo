# Setup do Laravel depois de clonar do GitHub

Um repositório clonado não traz `vendor/`, `node_modules/` nem `.env` (estão no `.gitignore`). Os passos abaixo criam tudo isso.

## Requisitos

- PHP 8.2 ou superior (`php -v`)
- Composer (`composer -V`)
- Node.js e npm (`node -v`, `npm -v`), para o front-end
- Extensões PHP ativas: `zip`, `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_sqlite` e `sqlite3`

Verificar extensões:

```powershell
php -m | findstr /i "zip sqlite mbstring openssl curl fileinfo"
```

## Passo a passo

### 1. Clonar e entrar na pasta

```bash
git clone <url-do-repositorio>
cd <pasta-do-projeto>
```

### 2. Instalar as dependências PHP

```bash
composer install
```

### 3. Criar o `.env` e a chave da aplicação

```bash
cp .env.example .env
php artisan key:generate
```

No PowerShell, se o `cp` falhar: `Copy-Item .env.example .env`.

### 4. Configurar a base de dados (SQLite)

1. No `.env`, deixa só esta linha do bloco `DB_`:

   ```env
   DB_CONNECTION=sqlite
   ```

   Apaga ou comenta (com `#`) `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`.

2. Cria o ficheiro `database.sqlite` **dentro da pasta `database/`**. Qualquer uma destas formas serve:

    - **No IDE:** clica com o botão direito na pasta `database` > *New* > *File* > `database.sqlite`. Também podes arrastar para lá um ficheiro `database.sqlite` vazio (criado no Explorador de Ficheiros) e largá-lo na pasta `database` do IDE.
    - **PowerShell:** `New-Item database/database.sqlite -ItemType File`
    - **Linux/macOS:** `touch database/database.sqlite`

   O caminho final tem de ser `database/database.sqlite`, ao lado das pastas `migrations/`, `seeders/` e `factories/`. Se ficar na raiz do projeto ou noutra pasta, o Laravel não o encontra.

   Nas versões recentes do Laravel, se o ficheiro não existir, o `php artisan migrate` pergunta se o queres criar. Responde `yes`.

### 5. Limpar a cache e correr as migrations

```bash
php artisan config:clear
php artisan migrate
php artisan migrate:status
```

Todas as migrations devem aparecer como `Ran`.

Para apagar tudo e recomeçar (só em desenvolvimento, apaga os dados):

```bash
php artisan migrate:fresh
```

Com dados de teste:

```bash
php artisan migrate:fresh --seed
```

### 6. Ficheiros públicos (fotos, uploads)

```bash
php artisan storage:link
```

### 7. Front-end

Este projeto usa Inertia, por isso precisa de assets compilados:

```bash
npm install
npm run dev
```

Noutro terminal:

```bash
php artisan serve
```

Abre `http://localhost:8000`.

## Resumo rápido

```bash
composer install
cp .env.example .env
php artisan key:generate
# no .env: DB_CONNECTION=sqlite (e apagar as outras linhas DB_)
# criar o ficheiro database/database.sqlite
php artisan config:clear
php artisan migrate
npm install
npm run dev          # terminal 1
php artisan serve    # terminal 2
```

## Problemas comuns

| Erro | Causa | Solução |
|---|---|---|
| `The zip extension and unzip/7z commands are both missing` | Extensão `zip` desligada no PHP | Abre o `php.ini` indicado no erro **como administrador**, muda `;extension=zip` para `extension=zip`, guarda, abre um terminal novo e corre `composer install` outra vez |
| Alterei o `php.ini` e nada mudou | Terminal antigo, ou editaste outro `php.ini` | Fecha e abre o terminal. Confirma o ficheiro certo com `php --ini` |
| Não consigo guardar o `php.ini` | O ficheiro está em `Program Files` | Abre o editor com *Executar como administrador* |
| `Your PHP version does not satisfy the requirement` | PHP mais antigo que o exigido | Atualiza o PHP ou confirma a versão pedida no `composer.json` |
| `Could not open input file: artisan` | Estás na pasta errada | Entra na pasta do projeto (onde está o ficheiro `artisan`) |
| `Failed to open stream: vendor/autoload.php` | Falta o `composer install` | Corre `composer install` |
| `No application encryption key has been specified` | Falta a chave | `php artisan key:generate` |
| `Database file at path [...] does not exist` | O `database.sqlite` não existe ou está na pasta errada | Cria-o dentro de `database/` (ver passo 4) |
| `Database file at path [laravel] does not exist` | `DB_DATABASE=laravel` ficou no `.env` com SQLite | Apaga ou comenta a linha `DB_DATABASE` |
| `could not find driver` | Falta a extensão `pdo_sqlite` | Ativa `extension=pdo_sqlite` e `extension=sqlite3` no `php.ini` e abre um terminal novo |
| O `.env` ainda aponta para MySQL (`DB_CONNECTION=mysql`) | O `.env.example` pode vir com outro valor | Muda para `DB_CONNECTION=sqlite` e corre `php artisan config:clear` |
| `Base table or view not found` | Falta correr as migrations | `php artisan migrate` |
| `Cannot add foreign key constraint` | Migrations por ordem errada ou tipos diferentes entre FK e PK | Renomeia o ficheiro da migration com um timestamp posterior ao da tabela referenciada |
| `Table already exists` | Migration parcialmente executada | `php artisan migrate:fresh` |
| Alterei o `.env` e não tem efeito | Configuração em cache | `php artisan config:clear` |
| `Vite manifest not found` | O front-end não foi compilado | `npm install` e `npm run dev` |
| `Class "X" not found` | Autoload desatualizado | `composer dump-autoload` |
| Fotos não aparecem | Falta o link do storage | `php artisan storage:link` |

## Checklist final

- [ ] `vendor/` existe
- [ ] `.env` existe e `APP_KEY` está preenchida
- [ ] `DB_CONNECTION=sqlite` no `.env` e `database/database.sqlite` existe
- [ ] `php artisan migrate:status` mostra tudo como `Ran`
- [ ] `npm run dev` e `php artisan serve` a correr
