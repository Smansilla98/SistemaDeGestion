# Checklist para Producción

## 🔒 Seguridad

- [ ] Cambiar `APP_DEBUG=false` en `.env`
- [ ] Cambiar `APP_ENV=production` en `.env`
- [ ] Generar nueva `APP_KEY` si es necesario
- [ ] Configurar HTTPS
- [ ] Revisar permisos de archivos y carpetas (`storage/`, `bootstrap/cache/`)
- [ ] Configurar firewall del servidor
- [ ] Cambiar contraseñas por defecto de usuarios
- [ ] Habilitar autenticación de dos factores (si se implementa)
- [ ] Revisar y actualizar dependencias (`composer update`, `npm update`)
- [ ] Configurar CORS si se usa API

## 🗄️ Base de Datos

- [ ] Crear backup de la base de datos
- [ ] Configurar respaldos automáticos
- [ ] Optimizar índices de base de datos
- [ ] Configurar conexión de base de datos en producción
- [ ] Verificar que todas las migraciones estén ejecutadas
- [ ] Revisar que los seeders no se ejecuten en producción

## ⚙️ Configuración

- [ ] Configurar variables de entorno de producción
- [ ] Configurar queue workers si se usan
- [ ] Configurar cache (Redis/Memcached)
- [ ] Configurar sesiones (database/redis)
- [ ] Configurar logs (rotación, nivel)
- [ ] Configurar email (SMTP)
- [ ] Configurar storage para archivos (S3/local)

## 🚀 Performance

- [ ] Ejecutar `php artisan config:cache`
- [ ] Ejecutar `php artisan route:cache`
- [ ] Ejecutar `php artisan view:cache`
- [ ] Ejecutar `php artisan event:cache`
- [ ] Optimizar autoloader (`composer install --optimize-autoloader --no-dev`)
- [ ] Compilar assets (`npm run build`)
- [ ] Configurar OPcache en PHP
- [ ] Configurar CDN para assets estáticos (opcional)

## 📊 Monitoreo

- [ ] Configurar logs de errores
- [ ] Configurar monitoreo de servidor (CPU, RAM, Disco)
- [ ] Configurar alertas de errores críticos
- [ ] Configurar monitoreo de base de datos
- [ ] Configurar uptime monitoring

## 🔄 Mantenimiento

- [ ] Documentar proceso de despliegue
- [ ] Documentar proceso de respaldo
- [ ] Configurar cron jobs si es necesario
- [ ] Configurar tareas programadas de Laravel
- [ ] Documentar procedimientos de recuperación

## 📱 Funcionalidades

- [ ] Probar flujo completo de pedidos
- [ ] Probar apertura/cierre de caja
- [ ] Probar impresión de tickets
- [ ] Probar reportes
- [ ] Probar control de stock
- [ ] Verificar permisos de usuarios
- [ ] Probar multi-sucursal (si aplica)

## 🌐 Servidor

- [ ] Configurar servidor web (Nginx/Apache)
- [ ] Configurar PHP-FPM
- [ ] Configurar SSL/TLS
- [ ] Configurar dominio y DNS
- [ ] Configurar firewall
- [ ] Verificar que puertos necesarios estén abiertos

## 📝 Documentación

- [ ] Documentar configuración específica del entorno
- [ ] Documentar credenciales de acceso (en lugar seguro)
- [ ] Crear manual de usuario
- [ ] Crear manual de administrador
- [ ] Documentar procedimientos de respaldo

## ✅ Pre-lanzamiento

- [ ] Pruebas de carga
- [ ] Pruebas de seguridad
- [ ] Pruebas de integración
- [ ] Revisión de código
- [ ] Pruebas con usuarios reales (beta)
- [ ] Plan de rollback

## 🎯 Post-lanzamiento

- [ ] Monitorear logs las primeras 24 horas
- [ ] Verificar que todos los módulos funcionen correctamente
- [ ] Recopilar feedback de usuarios
- [ ] Planificar mejoras y correcciones

---

**Importante**: Este checklist debe adaptarse según las necesidades específicas de tu entorno de producción.

