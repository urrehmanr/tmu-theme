#!/bin/bash
# rollback.sh - Automated Rollback Script

set -e

ENVIRONMENT=${1:-staging}
BACKUP_VERSION=${2}

echo "Rolling back TMU Theme in $ENVIRONMENT environment..."

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

if [ -z "$BACKUP_VERSION" ]; then
    # Use latest backup
    BACKUP_VERSION=$(ssh user@$SERVER "ls -t ${PATH}_backup_* | head -1")
fi

echo "Rolling back to: $BACKUP_VERSION"

# Rollback files
ssh user@$SERVER "rm -rf $PATH && mv $BACKUP_VERSION $PATH"

# Rollback database if needed
if [ -f "database_backup.sql" ]; then
    echo "Rolling back database..."
    ssh user@$SERVER "cd $PATH && wp db import database_backup.sql"
fi

# Clear cache
ssh user@$SERVER "cd $PATH && wp cache flush"

echo "✅ Rollback completed successfully!"