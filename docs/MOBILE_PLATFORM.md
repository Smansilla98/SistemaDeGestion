# Conurbania — plataforma Web + App nativa

## Decisiones

| Tema | Decisión |
|------|----------|
| Mobile | **Expo (React Native)** en `/mobile` — una codebase Android/iOS, EAS → AAB/IPA |
| Backend | Monolito Laravel intacto (no se mueve a `/backend`) |
| API canónica | **JWT** + `ApiResponse` + OpenAPI (`/api/*`). Sanctum `/api/v1` deprecated |
| Web desktop | Blade + sesión CSRF (sin rewrite SPA) |
| PWA `/m` | Se mantiene durante la transición |
| MVP store | Mozo + Cocina + Caja |
| Negocio | Solo en backend (`Services` / `Domain`) |

## Arquitectura

```text
restaurante-laravel/     Backend + web Blade
  app/ Controllers/Api   JWT REST
  docs/openapi.yaml
mobile/                  Expo app (Android + iOS)
  app/                   Expo Router
  src/api/               Cliente HTTP
  src/auth/              SecureStore tokens
```

## Entornos mobile

| Profile EAS | Variable | Uso |
|-------------|----------|-----|
| development | `EXPO_PUBLIC_API_URL` | API local/túnel |
| preview / staging | `EXPO_PUBLIC_API_URL` | staging HTTPS |
| production | `EXPO_PUBLIC_API_URL` | prod HTTPS |

**Nunca** embeber `JWT_SECRET`, DB ni claves de servidor en el binario.

## Identificadores de app

| Campo | Valor |
|-------|-------|
| Nombre | Conurbania |
| Android package | `com.conurbania.app` |
| iOS bundle | `com.conurbania.app` |
| Scheme | `conurbania` |

## Auth móvil

1. `POST /api/auth/login` → `access_token` + `refresh_token`
2. Tokens en **Expo SecureStore**
3. `POST /api/auth/refresh` rota refresh
4. `POST /api/auth/logout` revoca refresh
5. Interceptor 401 → refresh → reintento

Web sigue con sesión cookie.

## Permisos API (`config/rbac.php`)

Alineados con roles operativos web: `tables.*`, `kitchen.*`, `cash.*`, `devices.write`, además de products/orders/clients/users.

## Push

`POST /api/devices` registra Expo push token. El backend encola jobs hacia Expo Push API.

## Criterio de listo

- CI backend verde
- OpenAPI cubre auth + ops MVP
- EAS profiles configurados (AAB / iOS)
- App MVP usable contra staging
