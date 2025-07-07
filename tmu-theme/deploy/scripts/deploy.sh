#!/bin/bash
# deploy.sh - Enhanced Automated Deployment Script

set -e

ENVIRONMENT=${1:-staging}
VERSION=${2:-latest}

echo "Deploying TMU Theme to $ENVIRONMENT environment..."

# Configuration
case $ENVIRONMENT in
    staging)
        SERVER="staging.example.com"
        PATH="/var/www/staging/wp-content/themes/tmu"
        ;;
    production)
        SERVER="production.example.com"
        PATH="/var/www/production/wp-content/themes/tmu"
        ;;
    *)
        echo "Invalid environment: $ENVIRONMENT"
        exit 1
        ;;
esac

# Pre-deployment checks
echo "Running pre-deployment checks..."
npm run lint
npm run test
composer install --no-dev --optimize-autoloader

# Build assets
echo "Building assets for $ENVIRONMENT..."
npm run build:$ENVIRONMENT

# Create backup
echo "Creating backup..."
ssh user@$SERVER "cp -r $PATH ${PATH}_backup_$(date +%Y%m%d_%H%M%S)"

# Deploy files
echo "Deploying files..."
rsync -avz --delete \
    --exclude 'node_modules' \
    --exclude '.git' \
    --exclude '.github' \
    --exclude 'tests' \
    --exclude 'docs' \
    --exclude '*.md' \
    --exclude 'package*.json' \
    --exclude 'webpack.config.js' \
    ./ user@$SERVER:$PATH/

# Run post-deployment tasks
echo "Running post-deployment tasks..."
ssh user@$SERVER "cd $PATH && php wp-cli.phar theme activate tmu"
ssh user@$SERVER "cd $PATH && php wp-cli.phar cache flush"

# Health check
echo "Performing health check..."
HEALTH_URL="https://$SERVER/wp-json/tmu/v1/health"
HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" $HEALTH_URL)

if [ $HEALTH_STATUS -eq 200 ]; then
    echo "✅ Deployment successful! Health check passed."
else
    echo "❌ Deployment failed! Health check returned status $HEALTH_STATUS"
    echo "Rolling back..."
    ssh user@$SERVER "rm -rf $PATH && mv ${PATH}_backup_* $PATH"
    exit 1
fi

echo "🎉 Deployment completed successfully!"