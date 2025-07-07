#!/bin/bash
# migrate.sh - Database Migration Script

set -e

ENVIRONMENT=${1:-staging}
BACKUP_DIR="/var/backups/tmu-theme"

echo "Running database migrations for $ENVIRONMENT environment..."

# Create backup directory
mkdir -p $BACKUP_DIR

# Create database backup
echo "Creating database backup..."
wp db export "$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).sql"

# Run migrations
echo "Running database migrations..."
wp eval-file migrations/migrate.php

# Verify migrations
echo "Verifying migrations..."
wp eval-file migrations/verify.php

echo "✅ Database migrations completed successfully!"