#!/bin/bash

# Script de setup para configurar os git hooks automáticos
# Roda este script uma vez após clonar o repositório

echo "Configurando git hooks automáticos..."

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
if ! cd "$PROJECT_ROOT"; then
    echo "Erro: não foi possível aceder a $PROJECT_ROOT"
    exit 1
fi

# Configurar Git para usar scripts/ como diretório de hooks
if ! git config core.hooksPath scripts; then
    echo "Erro: não foi possível configurar core.hooksPath."
    exit 1
fi

# Garantir que os hooks são executáveis
if ! chmod +x scripts/pre-push scripts/post-merge; then
    echo "Erro: não foi possível tornar os hooks executáveis."
    exit 1
fi

echo "Git hooks configurados com sucesso!"
echo ""

# Na primeira vez, apagar o banco de dados antigo e criar o novo
echo "Configurando banco de dados..."
if [ -f "artisan" ]; then
    if [ -f "database/seeders/ExportedDataSeeder.php" ]; then
        echo "ExportedDataSeeder encontrado. Criando banco de dados..."
        if ! php artisan migrate:fresh --seed --no-interaction; then
            echo "Erro ao criar a base de dados a partir do snapshot."
            exit 1
        fi
        php artisan storage:link 2>/dev/null
        echo "Banco de dados criado com sucesso!"
        echo ""
    else
        echo "Aviso: ExportedDataSeeder não encontrado."
        echo "Roda: php artisan migrate:fresh --seed"
        echo ""
    fi
fi

echo "Tudo configurado! A partir de agora:"
echo ""
echo "- git push: exporta os dados automaticamente (se criar um commit, repete o push para o enviar)"
echo "- git pull: atualiza o banco de dados automaticamente"
echo "- Zero comandos manuais necessários"
