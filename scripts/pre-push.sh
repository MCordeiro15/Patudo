#!/bin/bash

# Pre-push hook: exporta automaticamente todos os dados antes de fazer push
# Isto garante que quando alguém faz push, todos os dados (users, petsitters, fotos, etc.)
# são incluídos no commit para que os colegas recebam tudo com git pull

# Caminho para o artisan (o hook está em .git/hooks/, o projeto está 2 níveis acima)
PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ARTISAN="$PROJECT_ROOT/artisan"

# Verificar se o artisan existe
if [ ! -f "$ARTISAN" ]; then
    echo "Erro: artisan não encontrado em $ARTISAN"
    exit 1
fi

# Mudar para o diretório do projeto
cd "$PROJECT_ROOT"

# Exportar todos os dados
echo "Exportando dados do banco de dados..."
php "$ARTISAN" db:export

# Verificar se o export foi bem-sucedido
if [ $? -ne 0 ]; then
    echo "Erro ao exportar dados. Push cancelado."
    exit 1
fi

# Adicionar o seeder exportado
git add database/seeders/ExportedDataSeeder.php

# Verificar se há mudanças para commitar
if git diff --cached --quiet; then
    echo "Nenhuma mudança nos dados. Continuando com o push."
else
    # Commitar as mudanças
    git commit -m "Auto-export: atualizar dados do banco de dados" --trailer "Co-authored-by: Junie <junie@jetbrains.com>"

    if [ $? -ne 0 ]; then
        echo "Erro ao commitar dados. Push cancelado."
        exit 1
    fi
fi

# Continuar com o push
exit 0
