# Store readiness — Conurbania

## Android (Play)

- Package: `com.conurbania.app`
- Artifact producción: **AAB** (`eas build --profile production --platform android`)
- Preview interno: APK (`preview` profile)
- Iconos: `mobile/assets/icon.png` + adaptive icons
- Splash: teal `#0F766E`

## iOS (App Store)

- Bundle ID: `com.conurbania.app`
- `eas build --profile production --platform ios` → Archive via EAS
- Requiere Apple Developer + certificados gestionados por EAS

## Checklist antes de submit

- [ ] `APP_ENV=production` y `EXPO_PUBLIC_API_URL` HTTPS de prod
- [ ] Sin secretos de servidor en el binario
- [ ] Privacy policy URL
- [ ] Capturas por dispositivo
- [ ] Push: configurar FCM (Android) / APNs (iOS) en proyecto Expo
- [ ] Version `1.0.0` + autoIncrement EAS

## Deep link

Scheme: `conurbania://`
