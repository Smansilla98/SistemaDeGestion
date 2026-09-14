#!/usr/bin/env bash
# Arranque limpio de Expo (evita ENOSPC / puertos ocupados cuando se puede).
set -euo pipefail
cd "$(dirname "$0")"

echo "=== inotify watches: $(cat /proc/sys/fs/inotify/max_user_watches) ==="
if [ "$(cat /proc/sys/fs/inotify/max_user_watches)" -lt 200000 ]; then
  echo ""
  echo "⚠️  Límite de file watchers bajo (causa Error ENOSPC)."
  echo "   Ejecutá UNA vez en tu terminal (pide sudo):"
  echo ""
  echo "   sudo sysctl -w fs.inotify.max_user_watches=524288"
  echo "   sudo sysctl -w fs.inotify.max_user_instances=1024"
  echo "   echo fs.inotify.max_user_watches=524288 | sudo tee /etc/sysctl.d/99-expo-inotify.conf"
  echo "   echo fs.inotify.max_user_instances=1024 | sudo tee -a /etc/sysctl.d/99-expo-inotify.conf"
  echo ""
fi

# Polling como fallback (más CPU, menos inotify)
export CHOKIDAR_USEPOLLING="${CHOKIDAR_USEPOLLING:-1}"
export CHOKIDAR_INTERVAL="${CHOKIDAR_INTERVAL:-1000}"

PORT="${PORT:-8088}"
echo "=== Expo en puerto ${PORT} → API: ${EXPO_PUBLIC_API_URL:-ver .env} ==="
exec npx expo start -c --port "$PORT"
