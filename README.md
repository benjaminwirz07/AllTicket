# Proyecto Base - PDISC 7° Año 4° División
Alumnos: Benjamin Wirz y Moira Marin

Este es un proyecto base en PHP pensado para que los alumnos trabajen con una aplicación simple de autenticación. Incluye:

- Un formulario de registro.
- Un formulario de login.
- Un área privada protegida por sesión.
- Conexión a base de datos usando PDO y configuración desde un archivo `.env`.

El objetivo es comprender cómo se organiza un proyecto PHP con separación de vistas, controladores y configuración.

## Requisitos previos

- PHP instalado (versión 8.0 o superior).
- Extensión `pdo_mysql` habilitada en `php.ini`.
- Extensiones `mbstring` y `fileinfo` habilitadas (validación de texto y cargas de imágenes).
- MySQL/MariaDB local o remoto para crear la base de datos.
- Editor de código (por ejemplo Visual Studio Code).

## Primeros pasos

1. Clonar este repositorio en tu computadora.
2. Abrir la carpeta del proyecto con Visual Studio Code.
3. Copiar `.env.example` y renombrarlo a `.env`.
4. Crear una base de datos y completar los valores de conexión en `.env`.
5. Si usas PHP integrado, ejecutar desde la raíz del proyecto:

   ```bash
   php -S localhost:8000
   ```

6. Abrir en el navegador:

   ```
   http://localhost:8000
   ```

## Base de datos

- Van a tener que abrir la terminal de mySQL
- Dentro del archivo `schema.sql` se van a encontrar con contenido que pueden pegar dentro de la terminal
- Asegurense de luego configurar los valores en el .env
- `schema.sql` borra y recrea la base de datos. Si ya tenés datos y solo necesitás el límite de intentos del newsletter, ejecutá `newsletter_rate_limit.sql` una vez sobre la base existente.

### Instalar el historial del chatbot

El asistente guarda los mensajes en MySQL. Después de crear la base de datos, ejecutá una sola vez `chatbot.sql` en esa misma base. Este archivo solo crea la tabla del chat y no borra ni modifica las demás tablas. El asistente es automático y responde preguntas frecuentes sobre eventos, entradas, compras y noticias; no es un servicio de inteligencia artificial.

## Estructura del repositorio

```text
.
├── .env.example
├── .htaccess
├── index.php
├── README.md
├── schema.sql
├── assets/
│   ├── css/
│   ├── img/
│   └── js/
└── src/
    ├── config/
    ├── controllers/
    └── views/
```

### Archivos principales

- `index.php`
  - Punto de entrada público del proyecto.
  - Redirige directamente a `src/views/index.php`.

- `.env.example`
  - Ejemplo de archivo de configuración para la conexión a la base de datos.
  - No debe contener datos reales.

- `schema.sql`
  - Contiene la definición de tablas necesarias para el proyecto.

## Responsabilidad de cada sección

- `assets/`: contiene archivos estáticos como CSS, JS e imágenes.
- `src/config/`: configuración global y conexión a la base de datos.
- `src/controllers/`: código de backend que procesa formularios y cambios de estado.
- `src/views/`: vistas HTML/PHP que se muestran al usuario.
- `src/views/_layouts/`: layouts compartidos que envuelven las páginas.

## Flujo inicial de la aplicación

1. El navegador entra por `index.php`.
2. `index.php` redirige a `src/views/index.php`.
3. `src/views/index.php` consulta los datos públicos y renderiza el layout compartido.
4. Las páginas públicas pueden visitarse sin iniciar sesión; las rutas privadas/admin comprueban los permisos desde sus controladores.
5. Desde `src/views/auth/login.php`, el usuario inicia sesión; desde `src/views/auth/register.php`, puede crear una cuenta.
6. `src/controllers/auth/logout.php` cierra la sesión mediante un formulario POST protegido con CSRF.

## Reglas de seguridad del proyecto

- `src/config/bootstrap.php` es el único punto que inicia la sesión.
- Las operaciones privadas usan `requireAuth()`; las rutas administrativas también verifican el rol actual en la base con `requireAdmin($pdo)`.
- Login y registro requieren CSRF; todas las acciones que cambian datos deben aceptar POST y comprobar el token CSRF.
- Los IDs de entidades se validan como UUID antes de consultar o modificar registros.
- La sesión solo guarda el ID y el rol del usuario. Los datos de perfil se consultan desde la base cuando hacen falta.
- Las imágenes subidas aceptan únicamente JPEG, PNG y WebP, con límite de 2 MB y validación del MIME/contenido.
- Newsletter limita a cinco intentos por dirección IP cada quince minutos. En una base existente, instalar la tabla mediante `newsletter_rate_limit.sql`.
- Las rutas internas se deben construir con `url()` y las redirecciones con `redirectTo()` / `redirectWithFlash()`.

## Notas para los alumnos

- El flujo de la aplicación está pensado para que primero se entienda la separación entre vista, controlador y configuración.
- Los datos sensibles deben guardarse en `.env`, nunca en el repositorio.
