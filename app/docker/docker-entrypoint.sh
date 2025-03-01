#!/bin/bash
set -e

# Ensure utils directory exists in public
mkdir -p /var/www/html/public/utils

# Create symlink if it doesn't exist
if [ ! -L "/var/www/html/public/utils/tagVideoThroughUrl" ]; then
    ln -sf /var/www/html/src/Utils/tagVideoThroughUrl /var/www/html/public/utils/
fi

# Fix permissions
chown -R www-data:www-data /var/www/html

# Execute the CMD from Dockerfile
exec "$@"
