#!/bin/bash
# deploy.sh - Theme deployment script

set -e

echo "Starting TMU Theme deployment..."

# Configuration
THEME_NAME="tmu-theme"
BUILD_DIR="build"
DIST_DIR="dist"

# Clean previous builds
echo "Cleaning previous builds..."
rm -rf $BUILD_DIR $DIST_DIR
mkdir -p $BUILD_DIR $DIST_DIR

# Install dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader
npm ci --production

# Build assets
echo "Building assets..."
npm run build:production

# Copy theme files
echo "Copying theme files..."
cp -r src/ $BUILD_DIR/
cp -r templates/ $BUILD_DIR/
cp -r assets/dist/ $BUILD_DIR/assets/
cp functions.php style.css index.php $BUILD_DIR/

# Generate version info
echo "Generating version info..."
COMMIT_HASH=$(git rev-parse --short HEAD)
BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

cat > $BUILD_DIR/version.json << EOF
{
    "version": "2.0.0",
    "commit": "$COMMIT_HASH",
    "build_date": "$BUILD_DATE",
    "php_version": "$(php -r 'echo PHP_VERSION;')",
    "wp_version": "6.0+"
}
EOF

# Run tests
echo "Running tests..."
vendor/bin/phpunit --testsuite=production
npm run test:browser:ci

# Create distribution package
echo "Creating distribution package..."
cd $BUILD_DIR
zip -r "../$DIST_DIR/$THEME_NAME-$COMMIT_HASH.zip" .
cd ..

# Deploy to staging (if configured)
if [ "$DEPLOY_STAGING" = "true" ]; then
    echo "Deploying to staging..."
    rsync -avz --delete $BUILD_DIR/ $STAGING_SERVER:$STAGING_PATH/
fi

# Deploy to production (if configured)
if [ "$DEPLOY_PRODUCTION" = "true" ]; then
    echo "Deploying to production..."
    rsync -avz --delete $BUILD_DIR/ $PRODUCTION_SERVER:$PRODUCTION_PATH/
fi

echo "Deployment completed successfully!"
echo "Package created: $DIST_DIR/$THEME_NAME-$COMMIT_HASH.zip"