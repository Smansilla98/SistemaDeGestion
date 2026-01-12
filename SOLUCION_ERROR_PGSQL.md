# 🔧 Solución: Error pdo_pgsql en Railway

## ❌ Error

```
configure: error: Cannot find libpq-fe.h. Please specify correct PostgreSQL installation path
```

## 🔍 Causa

El Dockerfile intenta compilar la extensión `pdo_pgsql` de PHP, pero falta la librería de desarrollo de PostgreSQL (`libpq-dev`).

## ✅ Solución

Se agregó `libpq-dev` a las dependencias del Dockerfile:

```dockerfile
apt-get install -y --no-install-recommends libpq-dev
```

## 📋 Cambios Aplicados

1. ✅ Agregado `libpq-dev` en la instalación de paquetes
2. ✅ Dockerfile actualizado y listo para Railway

## 🚀 Próximos Pasos

1. Hacer commit y push del Dockerfile corregido
2. Railway debería detectar el cambio automáticamente
3. El build debería completarse exitosamente

---

**Nota**: Si solo usas MySQL y no necesitas PostgreSQL, puedes eliminar `pdo_pgsql` del Dockerfile para acelerar el build.

