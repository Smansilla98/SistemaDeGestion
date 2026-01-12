# 🔧 Solución: Error de Conexión a 127.0.0.1

## ❌ Error

```
connection to server at "127.0.0.1", port 5432 failed: Connection refused
```

Laravel está intentando conectarse a `localhost` en lugar de usar las variables de Railway.

---

## 🔍 Causa

Las variables de entorno no están siendo leídas correctamente por Laravel. Esto puede pasar porque:

1. Las variables no están configuradas en Railway
2. Laravel está usando valores por defecto del `.env` o `config/database.php`
3. Las variables no están en el formato correcto

---

## ✅ Solución

### Opción 1: Usar DATABASE_URL (Recomendado)

Laravel lee automáticamente `DATABASE_URL` si está configurada. En Railway:

1. Ve a tu **servicio web** → **"Variables"**
2. Agrega o verifica:

```env
DATABASE_URL=postgresql://postgres:PASSWORD@HOST:5432/railway
```

**⚠️ IMPORTANTE**: Reemplaza:
- `PASSWORD` con el valor real de `POSTGRES_PASSWORD` (cópialo desde la base de datos)
- `HOST` con el valor real de `RAILWAY_PRIVATE_DOMAIN` (cópialo desde la base de datos)

**Ejemplo real**:
```env
DATABASE_URL=postgresql://postgres:abc123xyz@containers-us-west-xxx.railway.app:5432/railway
```

3. También agrega:

```env
DB_CONNECTION=pgsql
```

---

### Opción 2: Variables Individuales (Si DATABASE_URL no funciona)

Si `DATABASE_URL` no funciona, usa variables individuales con valores REALES (no referencias):

1. Ve a la **base de datos PostgreSQL** → **"Variables"**
2. Copia los valores REALES (no `${{...}}`)
3. En tu **servicio web** → **"Variables"** → Agrega:

```env
DB_CONNECTION=pgsql
DB_HOST=containers-us-west-xxx.railway.app
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=abc123xyz...
```

**⚠️ IMPORTANTE**: Usa valores REALES, no referencias como `${{RAILWAY_PRIVATE_DOMAIN}}`

---

### Opción 3: Verificar Variables en Railway

1. Ve a tu **servicio web** → **"Variables"**
2. Verifica que existan estas variables:
   - `DATABASE_URL` O las variables individuales (`DB_HOST`, `DB_PORT`, etc.)
   - `DB_CONNECTION=pgsql`
3. Si usas `DATABASE_URL`, NO necesitas las variables individuales

---

## 🔍 Verificar Variables en Railway Shell

En Railway → Tu servicio web → **"Deployments"** → **"View Logs"** → **"Shell"**:

```bash
# Ver todas las variables de entorno
env | grep DB_

# Verificar DATABASE_URL
echo $DATABASE_URL

# Verificar variables individuales
echo $DB_HOST
echo $DB_PORT
echo $DB_DATABASE
```

Si no aparecen, significa que no están configuradas.

---

## 🚀 Después de Configurar

1. Railway debería hacer redeploy automáticamente
2. Espera 2-3 minutos
3. Verifica los logs en Railway
4. Si sigue fallando, ejecuta en Shell:

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate --force
```

---

## 📋 Checklist

- [ ] `DATABASE_URL` configurada con valores REALES (no referencias)
- [ ] O variables individuales (`DB_HOST`, `DB_PORT`, etc.) con valores REALES
- [ ] `DB_CONNECTION=pgsql` configurado
- [ ] Variables verificadas en Shell (`env | grep DB_`)
- [ ] Cache limpiado (`php artisan config:clear`)

---

## 💡 Tip: Copiar Valores Reales

1. Ve a la **base de datos PostgreSQL** → **"Variables"**
2. Busca `RAILWAY_PRIVATE_DOMAIN` y copia su valor (ej: `containers-us-west-xxx.railway.app`)
3. Busca `POSTGRES_PASSWORD` y copia su valor (ej: `abc123xyz...`)
4. Construye `DATABASE_URL` manualmente:
   ```
   postgresql://postgres:VALOR_PASSWORD@VALOR_HOST:5432/railway
   ```

---

**El problema más común es usar referencias `${{...}}` en lugar de valores reales. Railway a veces no resuelve estas referencias correctamente en el runtime.**

