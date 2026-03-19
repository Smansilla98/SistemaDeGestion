# Sistema de Gestión

Aplicación web desarrollada con **Laravel** para la administración y
gestión de información mediante una interfaz web sencilla y funcional.

Este proyecto fue desarrollado como práctica y mejora continua en el
desarrollo backend utilizando **PHP y Laravel**, aplicando buenas
prácticas como arquitectura MVC, manejo de base de datos relacional y
desarrollo de funcionalidades CRUD.

## 🌐 Demo Online

Podés probar la aplicación en línea:

https://base-sistema-production.up.railway.app/login

## 📌 Características

-   Gestión de registros mediante operaciones **CRUD**
-   Arquitectura basada en **MVC (Laravel)**
-   Sistema de autenticación de usuarios
-   Validación de formularios
-   Interfaz administrativa simple
-   Base de datos relacional
-   Aplicación desplegada en la nube

## 🛠 Tecnologías utilizadas

-   PHP
-   Laravel
-   MySQL
-   HTML
-   CSS
-   JavaScript
-   Bootstrap
-   Git
-   Railway (deploy)

## 📂 Estructura del proyecto

El proyecto sigue la estructura estándar de Laravel:

    app/
    bootstrap/
    config/
    database/
    public/
    resources/
    routes/
    storage/

Componentes principales:

-   **Models** → acceso a base de datos
-   **Controllers** → lógica de negocio
-   **Views** → interfaz de usuario
-   **Routes** → endpoints de la aplicación

## ⚙️ Instalación

1.  Clonar el repositorio

``` bash
git clone https://github.com/Smansilla98/SistemaDeGestion.git
```

2.  Entrar al proyecto

``` bash
cd SistemaDeGestion
```

3.  Instalar dependencias

``` bash
composer install
```

4.  Copiar archivo de entorno

``` bash
cp .env.example .env
```

5.  Configurar base de datos en `.env`

```{=html}
<!-- -->
```
    DB_DATABASE=nombre_db
    DB_USERNAME=usuario
    DB_PASSWORD=password

6.  Generar key de Laravel

``` bash
php artisan key:generate
```

7.  Ejecutar migraciones

``` bash
php artisan migrate
```

8.  Levantar servidor

``` bash
php artisan serve
```

La aplicación estará disponible en:

    http://localhost:8000

## 📖 Objetivo del proyecto

El objetivo de este proyecto es practicar y demostrar conocimientos en:

-   Desarrollo backend con Laravel
-   Arquitectura MVC
-   Integración con bases de datos relacionales
-   Deploy de aplicaciones web
-   Gestión de código con Git y GitHub

## 👨‍💻 Autor

**Santiago Mansilla**\
Backend Developer

Portfolio:\
https://smansilla98.github.io/

GitHub:\
https://github.com/Smansilla98

Email:\
samansilla.998@gmail.com
