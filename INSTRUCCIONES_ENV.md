# 📋 Instrucciones para Configurar .env

## ✅ Archivo .env Creado

He creado un archivo `.env` completo con todas las configuraciones necesarias.

---

## 🔧 Pasos para Configurar

### 1. Obtener Valores de Railway

1. Ve a Railway → Tu **base de datos PostgreSQL** → **"Variables"**
2. Copia estos valores:
   - `RAILWAY_PRIVATE_DOMAIN` → Este será tu `DB_HOST`
   - `POSTGRES_PASSWORD` → Este será tu `DB_PASSWORD`

**Ejemplo**:
- `RAILWAY_PRIVATE_DOMAIN` = `containers-us-west-xxx.railway.app`
- `POSTGRES_PASSWORD` = `abc123xyz...`

---

### 2. Editar .env

Abre el archivo `.env` y reemplaza:

#### Opción A: Usar DATABASE_URL (Recomendado)

```env
DATABASE_URL=postgresql://postgres:TU_PASSWORD_AQUI@TU_HOST_AQUI:5432/railway
```

**Reemplaza**:
- `TU_PASSWORD_AQUI` → Con el valor de `POSTGRES_PASSWORD`
- `TU_HOST_AQUI` → Con el valor de `RAILWAY_PRIVATE_DOMAIN`

**Ejemplo real**:
```env
DATABASE_URL=postgresql://postgres:abc123xyz@containers-us-west-xxx.railway.app:5432/railway
```

#### Opción B: Variables Individuales

Si `DATABASE_URL` no funciona, usa:

```env
DB_HOST=TU_HOST_AQUI
DB_PASSWORD=TU_PASSWORD_AQUI
```

**Reemplaza** con los valores reales.

---

### 3. Verificar APP_URL

Asegúrate de que `APP_URL` sea correcto:

```env
APP_URL=https://sistemadegestion-production-5d57.up.railway.app
```

Si tu URL de Railway es diferente, cámbiala.

---

### 4. Configuraciones Opcionales

#### Mail (Correo electrónico)

Si necesitas enviar correos, configura un servicio de email:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls
```

#### Filesystem (Almacenamiento)

Si quieres usar S3 para almacenar archivos:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=tu_key
AWS_SECRET_ACCESS_KEY=tu_secret
AWS_BUCKET=tu_bucket
```

---

## 🚀 Después de Configurar

### Localmente

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate
php artisan db:seed
```

### En Railway

1. Sube el `.env` actualizado (o configura las variables en Railway)
2. Railway debería hacer redeploy automáticamente
3. O ejecuta en Shell:

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate --force
php artisan db:seed --force
```

---

## ⚠️ Importante

### No Subir .env a Git

El archivo `.env` ya está en `.gitignore`, así que no se subirá al repositorio.

### Variables en Railway

En Railway, puedes:
1. **Opción 1**: Configurar las variables directamente en Railway → Variables (recomendado)
2. **Opción 2**: Usar el `.env` local para desarrollo y configurar Railway manualmente

**Recomendación**: Configura las variables directamente en Railway para producción, no uses el archivo `.env` en el servidor.

---

## 📋 Checklist

- [ ] Valores de `DB_HOST` y `DB_PASSWORD` reemplazados con valores reales de Railway
- [ ] `DATABASE_URL` configurada correctamente (o variables individuales)
- [ ] `APP_URL` configurada con la URL correcta de Railway
- [ ] `APP_KEY` configurado (ya está generado)
- [ ] Variables opcionales configuradas si es necesario (mail, filesystem, etc.)

---

## 🔍 Verificar Configuración

### Localmente

```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

Si funciona, verás información de la conexión PDO.

### En Railway

En Railway Shell:

```bash
env | grep DB_
php artisan tinker
>>> DB::connection()->getPdo();
```

---

**Después de configurar, tu aplicación debería funcionar correctamente en Railway.**

