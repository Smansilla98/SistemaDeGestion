# Store readiness — Conurbania

## Android (Play)

- Package: `com.conurbania.app`
- Artifact producción: **AAB** (`npm run build:android` / `eas build --profile production --platform android`)
- APK producción (sideload): `eas build --profile production-apk --platform android`
- Preview interno: APK (`npm run build:preview`)
- Iconos: `mobile/assets/icon.png` + adaptive icons
- Splash: teal `#082822`

## iOS (App Store)

- Bundle ID: `com.conurbania.app`
- `npm run build:ios` / `eas build --profile production --platform ios` → Archive via EAS
- Simulator builds deshabilitados (`ios.simulator: false`) en perfiles EAS
- Requiere Apple Developer + certificados gestionados por EAS

## EAS_PROJECT_ID (obligatorio para prod)

1. En `mobile/`: `npx eas-cli login` y `npx eas init`
2. Copiar el `projectId` generado a `.env` / secrets:
   ```bash
   eas secret:create --name EAS_PROJECT_ID --value <uuid-del-proyecto>
   ```
3. También setear la API de producción:
   ```bash
   eas secret:create --name EXPO_PUBLIC_API_URL --value https://conurbaniabar.up.railway.app/api
   ```
4. `app.config.ts` lee `EAS_PROJECT_ID` (hay fallback UUID solo para no romper builds locales; **no** usar el fallback en store).
5. Opcional: campo `owner` en `app.config.ts` si el proyecto pertenece a una org Expo.

## Push (FCM / APNs)

- [ ] Android: subir `google-services.json` / configurar FCM en Expo dashboard
- [ ] iOS: APNs key en Expo / EAS credentials (`eas credentials`)
- [ ] Verificar registro de device token vía `api.registerDevice` post-login

## Checklist antes de submit

- [ ] `APP_ENV=production` y `EXPO_PUBLIC_API_URL=https://conurbaniabar.up.railway.app/api`
- [ ] `EAS_PROJECT_ID` real (no el UUID `00000000-…`)
- [ ] Sin secretos de servidor en el binario
- [ ] Privacy policy URL
- [ ] Capturas por dispositivo
- [ ] Push: FCM (Android) / APNs (iOS) configurados
- [ ] Version `1.0.0` + autoIncrement EAS

## Comandos útiles

```bash
cd mobile
npm run build:preview    # APK staging interno
npm run build:android    # AAB producción Play
npm run build:ios        # iOS producción
eas build --profile production-apk --platform android
eas submit --platform android
eas submit --platform ios
```

## Deep link

Scheme: `conurbania://`
