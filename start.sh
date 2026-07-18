#!/bin/bash

echo "🚀 Starting ServerPlus..."
docker compose up -d --build

echo "⏳ Waiting for app to be ready..."
until curl -s http://localhost:8000/admin/login > /dev/null; do
    sleep 1
done

echo "✅ ServerPlus is ready!"

if which xdg-open > /dev/null; then
    xdg-open http://localhost:8000/admin/login
elif which open > /dev/null; then
    open http://localhost:8000/admin/login
else
    echo "Open your browser at: http://localhost:8000/admin/login"
fi

docker compose logs -f
