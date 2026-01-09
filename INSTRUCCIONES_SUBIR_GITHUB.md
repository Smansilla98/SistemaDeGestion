# 📤 Instrucciones para Subir el Proyecto a GitHub

## ✅ Preparación Completada

El proyecto ya está preparado para subir a GitHub con:
- ✅ `.gitignore` actualizado
- ✅ `.gitattributes` creado
- ✅ `README.md` completo y actualizado
- ✅ Archivos sensibles excluidos

---

## 🚀 Pasos para Subir a GitHub

### 1. Inicializar Git (si no está inicializado)

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel

# Verificar si ya existe un repositorio Git
git status

# Si no existe, inicializar
git init
```

### 2. Agregar el Remote de GitHub

```bash
# Agregar el repositorio remoto
git remote add origin https://github.com/Smansilla98/SistemaDeGestion.git

# Verificar que se agregó correctamente
git remote -v
```

### 3. Verificar Archivos a Subir

```bash
# Ver qué archivos se van a subir (debe excluir .env, vendor, node_modules, etc.)
git status

# Ver archivos que están siendo ignorados
git status --ignored
```

### 4. Agregar Archivos al Staging

```bash
# Agregar todos los archivos (respetando .gitignore)
git add .

# Verificar qué se agregó
git status
```

### 5. Hacer el Primer Commit

```bash
git commit -m "Initial commit: Sistema de Gestión de Restaurante completo

- Sistema completo de gestión gastronómica con Laravel 12
- Gestión de mesas, pedidos, cocina, caja y stock
- Sistema de roles y permisos
- Impresión PDF y exportación a Excel
- API REST básica
- Notificaciones en tiempo real
- Tests implementados"
```

### 6. Subir a GitHub

```bash
# Subir a la rama main (o master)
git branch -M main
git push -u origin main
```

**Nota**: Si GitHub requiere autenticación, puedes usar:
- Personal Access Token (recomendado)
- SSH keys
- GitHub CLI

---

## 🔐 Autenticación con GitHub

### Opción 1: Personal Access Token (Recomendado)

1. Ir a GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generar nuevo token con permisos `repo`
3. Usar el token como contraseña cuando Git lo solicite

```bash
# Cuando pida usuario y contraseña:
# Username: tu_usuario_github
# Password: tu_personal_access_token
```

### Opción 2: SSH Keys

```bash
# Generar SSH key (si no tienes una)
ssh-keygen -t ed25519 -C "tu_email@example.com"

# Agregar la clave pública a GitHub
# Settings → SSH and GPG keys → New SSH key
# Copiar el contenido de ~/.ssh/id_ed25519.pub

# Cambiar el remote a SSH
git remote set-url origin git@github.com:Smansilla98/SistemaDeGestion.git
```

### Opción 3: GitHub CLI

```bash
# Instalar GitHub CLI
sudo apt install gh  # Ubuntu/Debian
# o desde: https://cli.github.com/

# Autenticarse
gh auth login

# Subir cambios
git push -u origin main
```

---

## 📋 Verificación Post-Subida

### 1. Verificar en GitHub

1. Ir a: https://github.com/Smansilla98/SistemaDeGestion
2. Verificar que todos los archivos estén presentes
3. Verificar que `.env` NO esté en el repositorio
4. Verificar que `vendor/` NO esté en el repositorio
5. Verificar que `node_modules/` NO esté en el repositorio

### 2. Verificar README

- El README debe mostrarse correctamente
- Los badges deben funcionar
- Los enlaces deben ser válidos

---

## ⚠️ Archivos que NO Deben Subirse

Asegúrate de que estos archivos NO estén en el repositorio:

- ❌ `.env` (archivo de configuración local)
- ❌ `.env.backup`
- ❌ `.env.local`
- ❌ `vendor/` (instalar con `composer install`)
- ❌ `node_modules/` (instalar con `npm install`)
- ❌ `storage/logs/*.log`
- ❌ `storage/framework/cache/*`
- ❌ `storage/framework/sessions/*`
- ❌ `storage/framework/views/*`
- ❌ `.phpunit.cache`
- ❌ `public/storage` (symlink, no el contenido)

---

## 🔄 Actualizar el Repositorio

Para futuras actualizaciones:

```bash
# Ver cambios
git status

# Agregar cambios
git add .

# Hacer commit
git commit -m "Descripción de los cambios"

# Subir cambios
git push origin main
```

---

## 📝 Estructura Recomendada del Repositorio

El repositorio debe tener esta estructura:

```
SistemaDeGestion/
└── restaurante-laravel/
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/
    ├── resources/
    ├── routes/
    ├── scripts/
    ├── storage/
    ├── tests/
    ├── .env.example          ✅ (sí subir)
    ├── .gitignore            ✅ (sí subir)
    ├── .gitattributes         ✅ (sí subir)
    ├── README.md              ✅ (sí subir)
    ├── composer.json          ✅ (sí subir)
    ├── composer.lock          ✅ (sí subir)
    ├── package.json           ✅ (sí subir)
    ├── phpunit.xml            ✅ (sí subir)
    └── vite.config.js         ✅ (sí subir)
```

---

## 🎯 Comandos Rápidos

```bash
# Todo en uno (después de la primera vez)
git add . && git commit -m "Actualización del proyecto" && git push origin main
```

---

## 🆘 Solución de Problemas

### Error: "remote origin already exists"
```bash
# Ver el remote actual
git remote -v

# Cambiar la URL
git remote set-url origin https://github.com/Smansilla98/SistemaDeGestion.git
```

### Error: "failed to push some refs"
```bash
# Hacer pull primero
git pull origin main --allow-unrelated-histories

# Luego push
git push origin main
```

### Error: "Permission denied"
- Verificar autenticación (token o SSH)
- Verificar permisos en el repositorio de GitHub

### Archivos sensibles subidos por error
```bash
# Eliminar del historial (CUIDADO: esto reescribe el historial)
git rm --cached .env
git commit -m "Remove .env from repository"
git push origin main --force
```

---

## ✅ Checklist Final

Antes de subir, verifica:

- [ ] `.env` no está en el repositorio
- [ ] `vendor/` no está en el repositorio
- [ ] `node_modules/` no está en el repositorio
- [ ] `README.md` está actualizado
- [ ] `.gitignore` está configurado correctamente
- [ ] `.env.example` existe y tiene valores de ejemplo
- [ ] Todos los archivos de código están presentes
- [ ] La documentación está completa

---

**¡Listo para subir! 🚀**

