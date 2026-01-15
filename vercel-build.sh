#!/usr/bin/env sh
# Vercel Build Script - Create required directories in /tmp

mkdir -p /tmp/storage/framework/{sessions,views,cache}
mkdir -p /tmp/storage/logs
mkdir -p /tmp/bootstrap/cache

# Symlink if needed (optional, may not work on Vercel)
# ln -sf /tmp/storage storage
# ln -sf /tmp/bootstrap/cache bootstrap/cache

echo "Vercel: Temporary directories created successfully"
