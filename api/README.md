# SmartStylist API

API REST de SmartStylist, construida con Laravel 13 y PHP 8.3. La aplicación usa PostgreSQL para persistir usuarios y datos relacionados con el armario y las sugerencias de conjuntos. La autenticación de la API se realiza con JWT (JSON Web Token).

> **Estado actual:** están disponibles el health check, el registro y autenticación JWT, el borrado de usuarios por administradores y la consulta de los catálogos de categorías, colores y estilos para cualquier usuario autenticado. Los administradores pueden crear, actualizar y eliminar esos catálogos. No hay todavía endpoints HTTP para prendas, conjuntos ni agenda. No se permite eliminar al último administrador ni borrar categorías, colores o estilos asociados a prendas.

## Requisitos

- PHP 8.3 o posterior, con Composer.
- Extensiones PHP para Laravel y PostgreSQL, en particular `pdo_pgsql`.
- PostgreSQL en ejecución y una base de datos creada para la aplicación.
- Node.js no es necesario para ejecutar estos endpoints de API.

En Windows, comprueba la instalación desde PowerShell:

```powershell
php -v
composer --version
php -m | findstr /i "pdo pgsql"
```

La lista debe incluir `PDO` y `pdo_pgsql`. Si falta `pdo_pgsql`, habilita la extensión en el `php.ini` que muestra `php --ini` y vuelve a abrir la terminal.

## Puesta en marcha local

Los comandos de esta sección se ejecutan desde la carpeta `api` del proyecto.

### 1. Crear la base de datos PostgreSQL

La base de datos debe existir antes de ejecutar las migraciones. Por ejemplo, desde `psql`:

```sql
CREATE DATABASE smartstylist;
```

También se puede crear desde pgAdmin. Utiliza una base local y separada de cualquier base con datos importantes.

### 2. Configurar el entorno

Si todavía no existe `.env`, crea una copia de la plantilla:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

Edita `.env` y configura, como mínimo, la conexión PostgreSQL:

```dotenv
APP_NAME=SmartStylist
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=smartstylist
DB_USERNAME=postgres
DB_PASSWORD=tu_contraseña_local

JWT_SECRET=
```

Usa el nombre, usuario y contraseña que configuraste en PostgreSQL. No compartas ni subas `.env` al repositorio. `APP_KEY` y `JWT_SECRET` se generan en el paso de instalación.

### 3. Instalar dependencias, claves, tablas y datos iniciales

```powershell
composer setup
```

El script `setup` del proyecto ejecuta, en este orden:

1. `composer install`, para instalar las dependencias declaradas en `composer.lock`.
2. Crea `.env` desde `.env.example` solo si todavía no existe.
3. Genera la clave de Laravel (`APP_KEY`).
4. Genera el secreto de firma JWT (`JWT_SECRET`).
5. Ejecuta las migraciones y carga los seeders (`php artisan migrate --seed --force`).

Este comando **no crea la base de datos PostgreSQL**; por eso hay que crearla y configurar `.env` antes. Las migraciones crean las tablas dentro de la base ya existente.

> **Importante:** `composer setup` está pensado para una instalación nueva. Genera claves y carga datos de demostración, así que no lo vuelvas a ejecutar sobre una instalación en uso ni en producción. El seeder `UserSeeder` crea cuentas demo con contraseñas conocidas; no uses esas credenciales públicamente. Para aplicar cambios de esquema en una instalación existente, revisa el cambio y ejecuta `php artisan migrate` de forma controlada, sin `--seed`.

Si solo necesitas regenerar el secreto JWT en un entorno local:

```powershell
php artisan jwt:secret
```

### 4. Iniciar el servidor

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

La API quedará disponible en `http://127.0.0.1:8000`. Laravel registra las rutas de `routes/api.php` con el prefijo `/api`.

## Endpoints disponibles

Todas las solicitudes y respuestas de la API usan JSON. En las peticiones `POST`, envía `Content-Type: application/json` y, para recibir errores en formato JSON, `Accept: application/json`.

| Método | Ruta | Autenticación | Descripción |
|---|---|---|---|
| `GET` | `/api/health` | No | Comprueba que la API responde. |
| `POST` | `/api/register` | No | Registra una cuenta estándar y devuelve un JWT. |
| `POST` | `/api/login` | No | Valida credenciales y devuelve un JWT. |
| `POST` | `/api/logout` | Sí | Invalida el JWT enviado. |
| `DELETE` | `/api/users/{id}` | Sí, rol `admin` | Elimina una cuenta por su ID; no permite eliminar al último administrador. |
| `GET` | `/api/categories`, `/api/categories/{id}` | Sí | Lista o consulta una categoría. |
| `POST` | `/api/categories` | Sí, rol `admin` | Crea una categoría. |
| `PUT`, `PATCH` | `/api/categories/{id}` | Sí, rol `admin` | Actualiza una categoría. |
| `DELETE` | `/api/categories/{id}` | Sí, rol `admin` | Elimina una categoría si no está asociada a prendas. |
| `GET` | `/api/colors`, `/api/colors/{id}` | Sí | Lista o consulta un color. |
| `POST` | `/api/colors` | Sí, rol `admin` | Crea un color. |
| `PUT`, `PATCH` | `/api/colors/{id}` | Sí, rol `admin` | Actualiza un color. |
| `DELETE` | `/api/colors/{id}` | Sí, rol `admin` | Elimina un color si no está asociado a prendas. |
| `GET` | `/api/styles`, `/api/styles/{id}` | Sí | Lista o consulta un estilo. |
| `POST` | `/api/styles` | Sí, rol `admin` | Crea un estilo. |
| `PUT`, `PATCH` | `/api/styles/{id}` | Sí, rol `admin` | Actualiza un estilo. |
| `DELETE` | `/api/styles/{id}` | Sí, rol `admin` | Elimina un estilo si no está asociado a prendas. |
| `GET` | `/up` | No | Health check de Laravel. |

### Health check de la API

```http
GET /api/health
```

Respuesta `200 OK`:

```json
{
  "status": "ok"
}
```

### Registro

```http
POST /api/register
Content-Type: application/json
Accept: application/json
```

Cuerpo:

```json
{
  "name": "Ana García",
  "email": "ana@example.com",
  "password": "una-clave-segura-123",
  "password_confirmation": "una-clave-segura-123"
}
```

Reglas actuales:

- `name`: obligatorio, texto de hasta 255 caracteres.
- `email`: obligatorio, formato de correo válido y único en `users`.
- `password`: obligatorio, texto de al menos 12 caracteres.
- `password_confirmation`: opcional; si se envía, debe coincidir con `password`.
- `role`: el cliente no puede enviarlo. Las nuevas cuentas reciben el rol `user`.

Una respuesta correcta es `201 Created`:

```json
{
  "access_token": "eyJ...<token>...Qss",
  "token_type": "Bearer",
  "expires_in": 3600,
  "user": {
    "id": 4,
    "name": "Ana García",
    "email": "ana@example.com",
    "role": "user"
  }
}
```

El token se puede usar inmediatamente en las rutas protegidas. Nunca se incluye la contraseña en la respuesta.

### Login

```http
POST /api/login
Content-Type: application/json
Accept: application/json
```

Cuerpo:

```json
{
  "email": "ana@example.com",
  "password": "una-clave-segura-123"
}
```

Una respuesta correcta es `200 OK` y tiene el mismo formato de token y usuario que el registro. El token dura 60 minutos por defecto; `expires_in` está expresado en segundos. Este valor puede cambiarse con `JWT_TTL` en `.env`.

Si el correo o la contraseña no coinciden, la respuesta es `401 Unauthorized` y no revela cuál de los dos datos falló:

```json
{
  "type": "about:blank",
  "title": "Unauthorized",
  "status": 401,
  "detail": "El correo electrónico o la contraseña no son válidos.",
  "instance": "/api/login"
}
```

### Logout

Logout requiere enviar el token en la cabecera `Authorization`:

```http
POST /api/logout
Authorization: Bearer eyJ...<token>...Qss
Accept: application/json
```

Si el token es válido, devuelve `204 No Content` sin cuerpo e invalida ese JWT. El guard `auth:api` protege la ruta. Si no se incluye un token válido, la API responde `401 Unauthorized`.

### Eliminar usuario

Solo un usuario autenticado con rol `admin` puede eliminar usuarios. Envía el ID numérico de la cuenta en la URL y el JWT de administrador en la cabecera `Authorization`. No se necesita un cuerpo JSON:

```http
DELETE /api/users/42
Authorization: Bearer eyJ...<token>...Qss
Accept: application/json
```

Si la eliminación se completa, la API responde `204 No Content` sin cuerpo. La ruta devuelve `401 Unauthorized` si el token falta o no es válido y `403 Forbidden` si el usuario autenticado no tiene rol `admin`. Si la cuenta solicitada es el último administrador, no se elimina y se responde `409 Conflict`:

```json
{
  "type": "about:blank",
  "title": "Conflict",
  "status": 409,
  "detail": "No se puede eliminar al último administrador.",
  "instance": "/api/users/42"
}
```

La eliminación se ejecuta en una transacción y comprueba el número de administradores antes de borrar, para conservar siempre al menos uno.

### Catálogos de categorías, colores y estilos

Las rutas de lectura requieren un JWT válido y están disponibles tanto para `admin` como para `user`. Crear, actualizar o eliminar elementos requiere el rol `admin`. Envía el token en la cabecera `Authorization: Bearer <token>` y utiliza `Accept: application/json`.

Las rutas de listado responden `200 OK` con una propiedad `data` que contiene los elementos ordenados por ID. Las rutas por ID también responden `200 OK` y devuelven el elemento en `data`. Los campos de cada recurso son:

- Categoría y estilo: `id`, `name` y `description`.
- Color: `id`, `name` y `hex_code`.

Para crear, envía `POST` a la colección. Por ejemplo:

```http
POST /api/categories
Authorization: Bearer <token-admin>
Content-Type: application/json
Accept: application/json
```

```json
{
  "name": "Calzado",
  "description": "Prendas para los pies."
}
```

La respuesta es `201 Created` con el recurso creado dentro de `data`. Para crear un estilo se usan los mismos campos. Para crear un color, envía `name` y `hex_code`, por ejemplo `"#00FF00"`.

Para actualizar, usa `PUT` o `PATCH` en la ruta del recurso y envía los campos que quieres cambiar. Las categorías y estilos aceptan `name` y `description`; el color acepta `name` y `hex_code`. La respuesta es `200 OK` con el recurso actualizado en `data`. Debe enviarse al menos un campo. Los nombres son obligatorios al crear, únicos y de hasta 80 caracteres; las descripciones son opcionales y admiten hasta 255 caracteres. `hex_code` es obligatorio al crear un color; en actualizaciones puede omitirse para conservar el valor actual, pero si se envía debe ser único, no nulo y tener formato `#RRGGBB`.

Eliminar un recurso devuelve `204 No Content`. Si la categoría, el color o el estilo se utiliza en alguna prenda, no se elimina y la API responde `409 Conflict` en formato Problem Details. Un ID inexistente responde `404 Not Found`; la falta de autenticación responde `401 Unauthorized` y un rol distinto de `admin` recibe `403 Forbidden` al intentar modificar.

### Errores de validación

Los errores de validación devuelven `422 Unprocessable Content` con formato `application/problem+json`. El objeto `errors` identifica los campos con problemas:

```json
{
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  },
  "type": "about:blank",
  "title": "Unprocessable Content",
  "status": 422,
  "detail": "Los datos enviados no son válidos.",
  "instance": "/api/login"
}
```

Los mensajes de validación de Laravel están actualmente en inglés; los mensajes generales de autenticación están en español.

## Probar la API con Postman

La colección [SmartStylist API](../docs/api/api.postman_collection.json) contiene solicitudes listas para importar. En Postman, selecciona **Import**, elige **Files** y abre `docs/api/api.postman_collection.json` del repositorio. La colección usa `http://localhost:8000` como `baseUrl` inicial; cámbiala en las variables de la colección si el servidor escucha en otra dirección o puerto.

La colección cubre autenticación y health checks, eliminación de usuarios y operaciones sobre los catálogos de categorías, estilos y colores. Incluye pruebas de permisos, validación, recursos inexistentes y conflictos:

| Grupo | Solicitud | Resultado esperado |
|---|---|---|
| Autenticación | Health checks, registro, login y logout | Comprueba estado, obtiene tokens y los invalida. |
| Administración de usuarios | Eliminar usuario como estándar, administrador y usuario inexistente | Comprueba `403 Forbidden`, `204 No Content` y `404 Not Found`. |
| Catálogos | Listar y consultar categorías, estilos y colores | `200 OK` con los recursos dentro de `data`. |
| Catálogos | Crear, reemplazar, actualizar parcialmente y eliminar como administrador | Comprueba las operaciones CRUD de cada catálogo. |
| Catálogos | Intentar modificar como usuario estándar | `403 Forbidden`. |
| Catálogos | Enviar datos inválidos o consultar un ID inexistente | Comprueba los errores `422 Unprocessable Content` y `404 Not Found`. |

### Variables y orden recomendado

La colección ya define `baseUrl`, las contraseñas demo y las variables donde guardar tokens (`adminToken`, `user1Token`, `user2Token`). También incluye `categoryId`, `styleId` y `colorId` para las operaciones sobre catálogos. No tiene scripts que copien automáticamente tokens ni IDs: copia los valores de las respuestas y actualiza las variables desde **Edit collection → Variables**.

La colección también define estas variables, que debes completar antes de ejecutar las solicitudes correspondientes:

- `registeredEmail`: una dirección de correo todavía no registrada. Cámbiala cada vez que repitas el registro, ya que el correo debe ser único.
- `userToDeleteId`: el ID numérico de un usuario que quieras eliminar. Para probar el caso positivo, crea primero una cuenta estándar y copia su `user.id` de la respuesta de registro. Para la prueba `403`, usa el token `user1Token`; para la prueba de borrado correcto, usa `adminToken`.
- `categoryId`, `styleId` y `colorId`: los IDs de los recursos que quieras consultar, actualizar o eliminar. Después de crear un recurso, copia su `data.id` a la variable correspondiente.

Orden sugerido:

1. Ejecuta **Health check**.
2. Ejecuta los tres casos de login y guarda cada `access_token` en la variable indicada.
3. Ejecuta **Registrar usuario** con un `registeredEmail` nuevo y copia su `user.id` a `userToDeleteId`.
4. Prueba primero **Eliminar usuario (debe dar 403 a user)** y luego **Eliminar usuario (admin)**. La segunda solicitud borra la cuenta indicada.
5. Ejecuta **Eliminar usuario inexistente** para probar el caso 404.
6. Ejecuta logout para invalidar el token de usuario 1. Para seguir usando esa variable, vuelve a iniciar sesión.
7. Para probar cada catálogo, crea un recurso como administrador y copia su ID a la variable correspondiente antes de ejecutar las solicitudes de consulta, actualización o borrado.

La colección usa las cuentas de demostración `admin@cifpaviles.es`, `user1@cifpaviles.es` y `user2@cifpaviles.es`. Solo funcionarán si se han cargado esos datos con los seeders. Las contraseñas conocidas y los datos demo son para desarrollo: no ejecutes las solicitudes contra producción.

## Base de datos y datos iniciales

Las migraciones preparan estas tablas:

| Tabla | Propósito y relaciones principales |
|---|---|
| `users` | Cuentas, correo único, contraseña cifrada y rol (`admin` o `user`). |
| `categories` | Catálogo de categorías de prendas. |
| `colors` | Catálogo de colores con código hexadecimal. |
| `styles` | Catálogo de estilos. |
| `garments` | Prendas de cada usuario; vinculadas a categoría y color, y opcionalmente a un estilo. |
| `outfits` | Conjuntos pertenecientes a un usuario. |
| `outfit_garments` | Relación muchos-a-muchos entre conjuntos y prendas; incluye `is_primary` y una nota opcional. |
| `schedules` | Fecha en que se ha programado un conjunto. |
| `outfit_logs` | Historial de uso de conjuntos. |

También se crea una tabla de cache mediante migración. Las migraciones de prendas incluyen restricciones para temporadas; colores y roles también tienen restricciones de formato/valores.

`php artisan db:seed` ejecuta `DatabaseSeeder`, que llama a los seeders de usuarios, categorías, colores, estilos, prendas, conjuntos, prendas de conjuntos, historial y agenda. El seeder de usuarios inserta cuentas de demostración:

| Correo | Contraseña inicial | Rol |
|---|---|---|
| `admin@cifpaviles.es` | `Admin1234!` | `admin` |
| `user1@cifpaviles.es` | `User1234!` | `user` |
| `user2@cifpaviles.es` | `User1234!` | `user` |

Estas credenciales son públicas y solo sirven para desarrollo/demostración. Antes de cargar datos en un entorno real, revisa los seeders y reemplaza o elimina las cuentas y contraseñas de demostración. La API permite registrar usuarios también en producción; el seeder no es el mecanismo para crear cuentas reales.

## Pruebas

Ejecuta la suite con:

```powershell
composer test
```

El script limpia la configuración cacheada y lanza `php artisan test`. La configuración de PHPUnit usa SQLite en memoria (`:memory:`), por lo que las pruebas no necesitan conectarse al PostgreSQL local ni crear una base PostgreSQL. Sí necesitan que PHP tenga disponible `pdo_sqlite`. Las pruebas de autenticación crean la tabla `users` que necesitan y cubren login, registro, validaciones, logout y el seeder.

Comprueba el driver SQLite con:

```powershell
php -m | findstr /i sqlite
```

## Estructura relevante

```text
api/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/AuthController.php
│   │   └── Requests/                 # Validación de login y registro
│   ├── Models/                       # User y modelos del dominio
│   ├── Services/AuthService.php      # Login, registro, logout y JWT
│   └── Support/ProblemDetails.php    # Respuestas de error API
├── bootstrap/app.php                 # Registro de rutas y formato de errores
├── config/
│   ├── auth.php
│   └── jwt.php
├── database/
│   ├── migrations/                   # Esquema PostgreSQL
│   └── seeders/                      # Catálogos y datos iniciales
├── routes/api.php                    # Endpoints HTTP disponibles
├── tests/                            # Pruebas automatizadas
├── .env.example
├── composer.json
└── phpunit.xml
```

## Comandos útiles

```powershell
php artisan route:list --path=api  # Ver rutas de la API
php artisan migrate:status         # Ver estado de las migraciones
php artisan migrate                # Aplicar migraciones pendientes
php artisan db:seed                # Insertar datos iniciales/demo
php artisan test                   # Ejecutar pruebas
php artisan serve                  # Servidor local
```
