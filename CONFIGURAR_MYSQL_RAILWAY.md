# 🚀 Configurar MySQL en Railway

Esta guía te ayudará a configurar MySQL en Railway para el Sistema de Gestión de Restaurante.

---

## ✅ Paso 1: Crear Base de Datos MySQL en Railway

1. En Railway → Tu proyecto
2. Click en **"New"** → **"Database"** → **"Add MySQL"**
3. Railway creará automáticamente la base de datos MySQL
4. **¡Importante!** Railway generará automáticamente la variable `DATABASE_URL`

---

## ✅ Paso 2: Configurar Variables de Entorno

En Railway → Tu servicio web → **"Variables"** → Agregar estas variables:

### Variables Mínimas Requeridas:

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:TU_APP_KEY_AQUI
APP_URL=https://tu-app.railway.app

DB_CONNECTION=mysql
```

### Variables de Base de Datos (si Railway no las agrega automáticamente):

Railway debería agregar automáticamente `DATABASE_URL` cuando creas la base de datos MySQL. Si no aparece:

1. Ve a la base de datos MySQL que creaste
2. Click en **"Variables"**
3. Copia el valor de `DATABASE_URL` (formato: `mysql://user:password@host:port/database`)
4. Agrégalo en las variables de tu servicio web

**O configura manualmente:**

```env
DB_CONNECTION=mysql
DB_HOST=TU_HOST_MYSQL
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=TU_PASSWORD_MYSQL
```

---

## ✅ Paso 3: Formato de DATABASE_URL para MySQL

Si Railway proporciona `DATABASE_URL`, debe tener este formato:

```
mysql://usuario:contraseña@host:puerto/nombre_base_datos
```

Ejemplo:
```
mysql://root:abc123xyz@containers-us-west-xxx.railway.app:3306/railway
```

Laravel leerá automáticamente esta URL y configurará la conexión.

---

## ✅ Paso 4: Verificar Variables de Entorno

Después de configurar las variables, verifica que estén correctas:

1. En Railway → Tu servicio web → **"Variables"**
2. Verifica que existan:
   - `DB_CONNECTION=mysql`
   - `DATABASE_URL` (o las variables individuales: `DB_HOST`, `DB_PORT`, etc.)

---

## ✅ Paso 5: Ejecutar Migraciones

El script `start.sh` ejecutará automáticamente las migraciones al iniciar el servicio. Si necesitas ejecutarlas manualmente:

1. En Railway → Tu servicio web → **"Deployments"** → Click en el último deployment
2. Click en **"View Logs"** → **"Shell"**
3. Ejecuta:

```bash
php artisan migrate --force
php artisan db:seed --force
```

---

## 🔍 Solución de Problemas

### Error: "SQLSTATE[HY000] [2002] Connection refused"

**Causa**: No se puede conectar al host de MySQL.

**Solución**:
1. Verifica que `DB_HOST` sea correcto (no `127.0.0.1` o `localhost`)
2. Verifica que `DB_PORT` sea `3306`
3. Asegúrate de que la base de datos MySQL esté en el mismo proyecto de Railway

### Error: "SQLSTATE[HY000] [1045] Access denied"

**Causa**: Credenciales incorrectas.

**Solución**:
1. Verifica `DB_USERNAME` y `DB_PASSWORD` en las variables de entorno
2. Si usas `DATABASE_URL`, verifica que la contraseña esté correctamente codificada en la URL

### Error: "SQLSTATE[HY000] [1049] Unknown database"

**Causa**: La base de datos no existe.

**Solución**:
1. Verifica que `DB_DATABASE` sea correcto
2. En Railway, la base de datos generalmente se llama `railway` o `mysql`

### Error 500 al iniciar

**Causa**: Problemas de conexión a la base de datos o migraciones fallidas.

**Solución**:
1. Revisa los logs en Railway → **"View Logs"**
2. Verifica que todas las variables de entorno estén configuradas
3. Verifica que la base de datos MySQL esté activa y funcionando
4. El script `start.sh` esperará hasta 60 segundos para que la base de datos esté disponible

---

## 📋 Checklist de Configuración

- [ ] Base de datos MySQL creada en Railway
- [ ] `DB_CONNECTION=mysql` configurado
- [ ] `DATABASE_URL` configurado (o variables individuales)
- [ ] `APP_KEY` generado y configurado
- [ ] `APP_URL` configurado con la URL de Railway
- [ ] Migraciones ejecutadas (automático con `start.sh`)
- [ ] Servicio funcionando sin errores 500

---

## 🔗 Referencias

- [Documentación de Railway - MySQL](https://docs.railway.app/databases/mysql)
- [Laravel Database Configuration](https://laravel.com/docs/database)

---

**Nota**: Railway puede tardar 2-3 minutos en aplicar los cambios. Si no funciona inmediatamente, espera un poco y recarga.

