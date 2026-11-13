#!/bin/bash

# Script de setup para configurar os git hooks automáticos
# Roda este script uma vez após clonar o repositório

echo "Configurando git hooks automáticos..."

# Configurar Git para usar scripts/ como diretório de hooks
git config core.hooksPath scripts

# Garantir que os hooks são executáveis
chmod +x scripts/pre-push scripts/post-merge

echo "Git hooks configurados com sucesso!"
echo ""

# Na primeira vez, apagar o banco de dados antigo e criar o novo
echo "Configurando banco de dados..."
if [ -f "artisan" ]; then
    if [ -f "database/seeders/ExportedDataSeeder.php" ]; then
        echo "ExportedDataSeeder encontrado. Criando banco de dados..."
        php artisan migrate:fresh --seed --no-interaction
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
echo "- git push: exporta todos os dados automaticamente"
echo "- git pull: atualiza o banco de dados automaticamente"
echo "- Zero comandos manuais necessários"
