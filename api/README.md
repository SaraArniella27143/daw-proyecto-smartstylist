# SmartStylist API

API REST de SmartStylist, construida con Laravel 13 y PHP 8.3. La aplicación usa PostgreSQL para persistir usuarios y datos relacionados con el armario y las sugerencias de conjuntos. La autenticación de la API se realiza con JWT (JSON Web Token).

> **Estado actual:** están implementados el health check y los endpoints de registro, login y logout. Existen tablas y datos iniciales para prendas, categorías, colores, estilos, conjuntos y agenda, pero todavía no hay endpoints HTTP para consultar o modificar esos recursos.

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

## Ejemplos desde PowerShell

Comprobar el health check:

```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/health'
```

Registrar un usuario:

```powershell
$body = @{
    name = 'Ana García'
    email = 'ana@example.com'
    password = 'una-clave-segura-123'
    password_confirmation = 'una-clave-segura-123'
} | ConvertTo-Json

$registro = Invoke-RestMethod `
    -Uri 'http://127.0.0.1:8000/api/register' `
    -Method Post `
    -ContentType 'application/json' `
    -Headers @{ Accept = 'application/json' } `
    -Body $body

$token = $registro.access_token
$token
```

Iniciar sesión y cerrar sesión:

```powershell
$body = @{
    email = 'ana@example.com'
    password = 'una-clave-segura-123'
} | ConvertTo-Json

$login = Invoke-RestMethod `
    -Uri 'http://127.0.0.1:8000/api/login' `
    -Method Post `
    -ContentType 'application/json' `
    -Headers @{ Accept = 'application/json' } `
    -Body $body

Invoke-RestMethod `
    -Uri 'http://127.0.0.1:8000/api/logout' `
    -Method Post `
    -Headers @{
        Accept = 'application/json'
        Authorization = "Bearer $($login.access_token)"
    }
```

El último comando no muestra contenido si devuelve correctamente `204 No Content`.

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
