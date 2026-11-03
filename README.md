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
- `schema.sql` borra y recrea la base de datos e incluye **todas** las tablas del proyecto (usuarios, recitales, ventas, reservas, entradas QR, chatbot y newsletter). No lo ejecutes si querés conservar los datos actuales.
- Si la base ya existía, no vuelvas a ejecutar todo `schema.sql`. Para agregar la tabla del QR sin borrar datos, copiá y ejecutá solamente el bloque `CREATE TABLE ticket_entries` de ese archivo.

### Asistente virtual

El asistente guarda los mensajes en la tabla `chatbot_messages` (creada por `schema.sql`) y responde preguntas frecuentes sobre eventos, entradas, compras y noticias. Es automático: no es un servicio de inteligencia artificial.

Los archivos y el endpoint públicos del asistente usan rutas con `assistant` en el nombre porque InfinityFree puede bloquear URLs que contienen `chat`. La tabla de la base de datos conserva su nombre y no requiere migración.

### Newsletter

El formulario guarda las direcciones en `newsletter_subscribers`, pero el proyecto no envía correos ni avisos automáticos.

### Pagos de demostración

Los pagos no son reales y no se conecta ninguna pasarela. “Pago de prueba” marca una compra como pagada para poder probar el QR; “Transferencia simulada” la deja pendiente para que un administrador pueda simular su confirmación. No ingreses datos reales de tarjeta ni transfieras dinero. En el panel, solo se pueden cancelar pedidos pendientes; una compra marcada como pagada no se puede cancelar ni reembolsar desde esta demo.

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

### Noticias

Las noticias se muestran en una página propia, con filtros, búsqueda y paginación. Los administradores pueden crearlas desde el panel y elegir categoría, resumen, imagen opcional y si se destacan. El tiempo de lectura se calcula automáticamente.

`schema.sql` incluye todos los campos de noticias para una instalación nueva. Si las noticias ya funcionan en una base existente, no vuelvas a ejecutar el esquema ni hagas cambios en esa base por esta sección.

### Entradas con código QR

Cada unidad comprada recibe un QR propio en “Mis entradas”. El QR solo permite ingresar si el pedido está pagado. Al escanearlo con la cámara de un celular, el personal administrador revisa los datos y confirma el ingreso; una entrada ya marcada como usada no se puede validar otra vez.

- En una instalación nueva, `schema.sql` crea la tabla `ticket_entries`.
- Si la base ya existe, copiá y ejecutá solamente el bloque `CREATE TABLE ticket_entries` de `schema.sql`. No pegues ni ejecutes el resto del esquema porque borra la base.
- Las compras antiguas reciben sus entradas QR la primera vez que se abre “Mis entradas”.
- La imagen QR se genera en el navegador usando QRCode.js desde CDN, por eso hace falta conexión a internet para verla.
- Para probar el escaneo con un celular, abrí el sitio desde una dirección que el celular pueda alcanzar (por ejemplo, la IP local de la computadora, no `localhost`). El administrador debe tener la sesión iniciada para marcar el ingreso.
- Cada QR también tiene un botón “Imprimir entrada”. La impresión incluye solo esa entrada, no el menú ni el resto de la página.

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
- Newsletter limita a cinco intentos por dirección IP cada quince minutos (tabla `newsletter_rate_limits`).
- Las rutas internas se deben construir con `url()` y las redirecciones con `redirectTo()` / `redirectWithFlash()`.
- Para cancelar un recital, usá la acción “Cancelar” desde la lista de recitales; el formulario de edición no permite cancelarlo ni reactivar un recital ya cancelado, para evitar dejar pedidos pendientes sin liberar.

## Notas para los alumnos

- El flujo de la aplicación está pensado para que primero se entienda la separación entre vista, controlador y configuración.
- Los datos sensibles deben guardarse en `.env`, nunca en el repositorio.
