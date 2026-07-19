
#!/bin/bash

echo "🚀 Starting ServerPulse..."

# Free port 8000 if something else is using it (e.g. a leftover php artisan serve)
PORT_PID=$(lsof -ti:8000 2>/dev/null)
if [[ -n "$PORT_PID" ]]; then
    echo "⚠️  Port 8000 is in use by process $PORT_PID — stopping it..."
    kill "$PORT_PID" 2>/dev/null
    sleep 1
fi

# Build only on first run or when --rebuild is passed
if [[ "$1" == "--rebuild" ]] || [[ -z "$(docker images -q serverpulse-serverplus 2>/dev/null)" ]]; then
    echo "🔨 Building image (first run or --rebuild requested)..."
    docker compose up -d --build
else
    echo "⚡ Image already exists, starting without rebuild..."
    docker compose up -d
fi

echo "⏳ Waiting for app to be ready..."
until curl -s http://localhost:8000/server-plus-dashboard > /dev/null 2>&1; do
    sleep 1
done

echo "✅ ServerPulse is ready!"

if which xdg-open > /dev/null; then
    xdg-open http://localhost:8000/server-plus-dashboard/login
elif which open > /dev/null; then
    open http://localhost:8000/server-plus-dashboard/login
else
    echo "Open your browser at: http://localhost:8000/server-plus-dashboard/login"
fi

docker compose logs -f
