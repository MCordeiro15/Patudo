#!/bin/bash

# Script de setup para configurar o git hook de exportação automática
# Roda este script uma vez após clonar o repositório

echo "Configurando git hook de exportação automática..."

# Copiar o pre-push hook para .git/hooks/
cp scripts/pre-push.sh .git/hooks/pre-push
chmod +x .git/hooks/pre-push

echo "Git hook configurado com sucesso!"
echo ""
echo "Agora, quando fizeres git push, todos os dados do banco de dados"
echo "(users, petsitters, animais, posts, bookings, reviews, fotos)"
echo "são automaticamente exportados e partilhados com os teus colegas."
echo ""
echo "Para os colegas receberem os dados:"
echo "  git pull origin main"
echo "  php artisan migrate:fresh --seed"
echo "  php artisan storage:link"
