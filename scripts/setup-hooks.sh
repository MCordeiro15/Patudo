#!/bin/bash

# Script de setup para configurar os git hooks automáticos
# Roda este script uma vez após clonar o repositório

echo "Configurando git hooks automáticos..."

# Copiar o pre-push hook para .git/hooks/
cp scripts/pre-push.sh .git/hooks/pre-push
chmod +x .git/hooks/pre-push

# Copiar o post-merge hook para .git/hooks/
cp scripts/post-merge.sh .git/hooks/post-merge
chmod +x .git/hooks/post-merge

echo "Git hooks configurados com sucesso!"
echo ""
echo "O que acontece agora:"
echo ""
echo "1. Quando fizeres git push:"
echo "   - Todos os dados (users, petsitters, fotos, etc.) são exportados automaticamente"
echo ""
echo "2. Quando os colegas fizerem git pull:"
echo "   - O banco de dados é atualizado automaticamente"
echo "   - O storage link é criado automaticamente"
echo "   - Tudo pronto, sem comandos manuais"
echo ""
echo "Resumo: git push e git pull são 100% automáticos. Zero comandos manuais."
