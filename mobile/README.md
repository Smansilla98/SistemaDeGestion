# Conurbania Mobile (Expo)

App nativa Android/iOS. Consume la API JWT de Laravel (`/api`).

## Desarrollo

```bash
cp .env.example .env
# EXPO_PUBLIC_API_URL=http://TU_IP:8000/api
npm install
npm start
```

## Builds store

```bash
npm i -g eas-cli
eas login
eas build --platform android --profile production   # AAB
eas build --platform ios --profile production
```

Perfiles en `eas.json`: development / preview / production.

Bundle IDs: `com.conurbania.app` — ver `docs/MOBILE_PLATFORM.md`.
