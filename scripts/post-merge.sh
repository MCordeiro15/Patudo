#!/bin/bash

# Post-merge hook: atualiza automaticamente o banco de dados após git pull
# Isto garante que quando os colegas fazem pull, recebem todos os dados novos

# Caminho para o artisan (o hook está em .git/hooks/, o projeto está 2 níveis acima)
PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ARTISAN="$PROJECT_ROOT/artisan"

# Verificar se o artisan existe
if [ ! -f "$ARTISAN" ]; then
    echo "Erro: artisan não encontrado em $ARTISAN"
    exit 0
fi

# Mudar para o diretório do projeto
cd "$PROJECT_ROOT"

# Verificar se o ExportedDataSeeder mudou
if git diff-tree -r --name-only HEAD^1 HEAD | grep -q "ExportedDataSeeder.php"; then
    echo "Detetadas mudanças nos dados do banco de dados..."
    echo "Atualizando banco de dados automaticamente..."
    
    php "$ARTISAN" migrate:fresh --seed --no-interaction
    
    if [ $? -eq 0 ]; then
        echo "Banco de dados atualizado com sucesso!"
        echo ""
        echo "Agora roda:"
        echo "  php artisan storage:link"
        echo "  npm run dev"
        echo "  php artisan serve"
    else
        echo "Erro ao atualizar o banco de dados."
        echo "Roda manualmente: php artisan migrate:fresh --seed"
    fi
fi

exit 0
