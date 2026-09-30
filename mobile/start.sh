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

# Evita ruido de DevTools en Linux (opcional)
export EXPO_NO_TELEMETRY="${EXPO_NO_TELEMETRY:-1}"

# CI vacío rompe getenv boolish de Expo — nunca exportar CI="" 
if [ -z "${CI:-}" ]; then
  unset CI 2>/dev/null || true
fi

# RN DevTools en Linux exige chrome-sandbox root:4755; sin eso solo ensucia el log.
# Lo deshabilitamos: Expo Go en el celu no lo necesita.
for sb in "$HOME"/.cache/dotslash/*/React\ Native\ DevTools-linux-x64/chrome-sandbox; do
  if [ -f "$sb" ] && [ ! -f "${sb}.disabled" ]; then
    mv "$sb" "${sb}.disabled" 2>/dev/null || true
    echo "✓ chrome-sandbox DevTools deshabilitado (no afecta Expo Go)"
  fi
done

PORT="${PORT:-8088}"
echo "=== Expo en puerto ${PORT} → API: ${EXPO_PUBLIC_API_URL:-ver .env} ==="
echo "    (Usá Expo Go en el celular; Proceed anonymously si pregunta login)"
echo "    El ERROR de chrome-sandbox se puede ignorar si ves el QR abajo."
exec npx expo start -c --port "$PORT"
