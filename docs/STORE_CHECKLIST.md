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

Proyecto: [@smansilla/conurbania](https://expo.dev/accounts/smansilla/projects/conurbania)  
ID: `8079310e-3205-4c1e-88d3-22964fb97c60` (en `app.config.ts` + `mobile/.env`).

Si hay que recrear:
1. Quitar `extra.eas.projectId` (o el UUID `0000…`) de la config
2. `npx eas-cli init --account smansilla --force`
3. `eas env:create --name EAS_PROJECT_ID --value <uuid> --environment production`

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
