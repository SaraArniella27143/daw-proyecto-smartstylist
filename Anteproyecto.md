# Anteproyecto: SmartStylist

**Módulo:** Proyecto Intermodular (0379 · PIDW2) — 2º DAW Virtual 

**Centro:** CIFP Avilés (Curso 2026/2027) 

**Autor/a:** Sara Arniella Casar

**Repositorio GitHub:** https://github.com/SaraArniella27143/daw-proyecto-smartstylist 

**Tablero GitHub Projects:** https://github.com/users/SaraArniella27143/projects/1

## 1. Justificación y dominio funcional 
### 1.1. Elevator pitch 

¿Cuántas veces nos hemos parado frente al armario con la sensación de "no tener qué ponernos", a pesar de estar lleno de ropa? 
SmartStylist es una aplicación web interactiva diseñada para actuar como un estilista personal en el bolsillo. La plataforma permite a los usuarios digitalizar su propio armario mediante la subida de fotografías o escaneo rápido de sus prendas. Gracias a un sistema inteligente de categorización y reglas de combinación de estilo (por colores, temporadas, ocasiones y tipos de ropa), la aplicación genera automáticamente sugerencias y "outfits" completos combinando lo que el usuario ya tiene. SmartStylist resuelve el problema de la fatiga de decisión diaria y fomenta la moda circular y el consumo consciente, ayudando a redescubrir y sacar el máximo partido a la ropa existente en el propio vestidor sin necesidad de comprar más.

### 1.2. Público objetivo y contexto de uso
Usuarios / Amantes de la moda y particulares: Personas que desean optimizar su tiempo al vestirse cada mañana, organizar mejor su ropa o buscar inspiración a partir de las prendas que ya poseen.

Contexto de uso: Se utiliza de forma cotidiana desde dispositivos móviles u ordenadores (principalmente móviles), ya sea al planificar los conjuntos para la semana, al ordenar el armario de forma digital, o justo antes de salir de casa, al ir de compras para ver qué combinaciones posibles existen.


 --- 
## 2. Roles del sistema y casos de uso 
### 2.1. Matriz de control de acceso (RBAC)

El sistema aplica el control de acceso según el rol autenticado. El usuario y el administrador solo acceden a sus propios recursos personales; el administrador gestiona además los catálogos compartidos.

| Operación | Público | Usuario | Admin |
|---|---|---|---|
| Consultar la página pública y los formularios de autenticación | Sí | Sí | Sí |
| Registrar una cuenta | Sí | No | No |
| Iniciar sesión | Sí | Sí | Sí |
| Gestionar prendas y outfits propios | No | Sí, solo recursos propios | Sí, solo recursos propios |
| Generar sugerencias para el armario propio | No | Sí | Sí |
| Planificar outfits y consultar estadísticas propias | No | Sí, solo recursos propios | Sí, solo recursos propios |
| Crear, modificar y eliminar categorías, colores y estilos compartidos | No | No | Sí |
| Consultar o modificar prendas y outfits de otros usuarios | No | No | No |

### 2.2. Casos de uso principales

#### CU-01: Registro e inicio de sesión

**Actor principal:** Público (visitante no autenticado).

**Flujo principal:**
1. El visitante selecciona el registro o el inicio de sesión.
2. Para registrarse, el visitante introduce su nombre, correo electrónico y contraseña.
3. El sistema valida los datos y crea la cuenta con el rol estándar de usuario.

**Flujo alternativo (inicio de sesión):**
1. El visitante introduce las credenciales de una cuenta existente.
2. El sistema valida las credenciales y emite un token JWT válido.

#### CU-02: Gestión del inventario de prendas

**Actor principal:** Usuario autenticado.

**Flujo principal:**
1. El usuario solicita consultar, registrar, actualizar o eliminar una prenda.
2. Para registrar o actualizar una prenda, el usuario proporciona sus datos, incluidos la categoría, el color y la temporada; también puede indicar un estilo y una URL de imagen.
3. El sistema valida los datos y comprueba que la prenda pertenece al usuario cuando la operación afecta a una prenda existente.
4. El sistema guarda los cambios o devuelve los datos solicitados, siempre dentro del armario del usuario.

#### CU-03: Generación de sugerencias y gestión de outfits

**Actor principal:** Usuario autenticado.

**Flujo principal:**
1. El usuario solicita sugerencias e indica una temporada; también puede seleccionar un color y un estilo.
2. El sistema filtra las prendas propias por la temporada solicitada o por la opción de todo el año y aplica los filtros de color y estilo cuando se indican.
3. El sistema combina una prenda superior con una inferior o propone un vestido como prenda principal. También añade, cuando existen y cumplen los filtros, hasta un calzado, un abrigo y un accesorio.
4. El sistema devuelve las sugerencias sin guardarlas como outfits.
5. Cuando el usuario decide guardar una sugerencia, el sistema registra el outfit y sus prendas asociadas.

**Flujo alternativo:** El usuario selecciona prendas propias para crear un outfit manualmente. El sistema valida la propiedad de las prendas y guarda el outfit cuando el usuario confirma la operación.

#### CU-04: Planificación de outfits

**Actor principal:** Usuario autenticado.

**Flujo principal:**
1. El usuario selecciona uno de sus outfits completos y una fecha del calendario.
2. El sistema comprueba que el outfit pertenece al usuario y que está completo.
3. El sistema registra la planificación y confirma la fecha asignada.

#### CU-05: Registro de uso y consulta de estadísticas

**Actor principal:** Usuario autenticado.

**Flujo principal:**
1. El usuario registra el uso de uno de sus outfits e indica cuándo lo utiliza.
2. El sistema comprueba que el outfit pertenece al usuario y registra el evento de uso.
3. El usuario solicita las estadísticas de su armario.
4. El sistema calcula las estadísticas a partir del historial de uso y las muestra al usuario.

#### CU-06: Gestión de catálogos compartidos

**Actor principal:** Administrador autenticado.

**Flujo principal:**
1. El administrador solicita crear, modificar o eliminar una categoría, un color o un estilo.
2. El sistema comprueba el rol del administrador y valida los datos y las restricciones de unicidad.
3. El sistema guarda el cambio y devuelve su resultado. Si una prenda utiliza el elemento, el sistema impide su eliminación.

### 2.3. Alcance del MVP 
- **Incluido:** Sistema de autenticación, interfaz para subir prendas y catalogarlas (con subida de imágenes), motor básico de filtrado y sugerencias de combinación basadas en etiquetas de color/estilo, calendario semanal de outfits y panel básico de administración. - **Fuera del MVP:** Reconocimiento de prendas mediante Inteligencia Artificial, o la integración con tiendas externas para sugerir compras complementarias (quedan para add-ons o versiones futuras). 
---  

## 3. Modelo de datos 
- **Diagrama Entidad-Relación en 3FN:** [docs/der.md](docs/der.md)

 --- 

   ## 4. Stack tecnológico previsto 
   - **Motor de base de datos:** PostgreSQL

   Justificación: PostgreSQL es un sistema relacional robusto y está soportado por Laravel. Además, Render ofrece PostgreSQL gestionado y documentación para desplegar aplicaciones Laravel, lo que facilita utilizar una base de datos administrada en el mismo proveedor.

   - **Backend API:** Laravel

   Justificación: Es uno de los frameworks más potentes y ágiles que existen hoy en día. Nos ahorra mucho tiempo de desarrollo porque ya viene con herramientas integradas para la seguridad.

   - **Frontend:** React

   Justificación: Es la biblioteca de JavaScript más conocida para crear interfaces web dinámicas y modernas. Tiene una navegación fluida y rápida, permitiendo que el usuario interactúe con su armario virtual sin que la página tenga que recargarse constantemente.

   - **Alojamiento cloud previsto:** Render

   Justificación: Se valorará Render como primera opción porque permite desplegar aplicaciones Laravel mediante Docker y ofrece PostgreSQL gestionado, por lo que puede simplificar la configuración y publicación del proyecto frente a AWS.

   ---   
   ## 5. Selección tentativa de Add-ons (mínimo 2) 
    - [X] Dockerización multicontenedor con Docker Compose (+1.0)
    - [X] Pipeline CI/CD automatizado con GitHub Actions (+1.0) 
    - [X] Almacenamiento desacoplado en AWS S3 / Cloudinary (+0.5) 
    - [ ] Pasarela de pagos Stripe en entorno Sandbox con Webhooks (+1.0) 
    - [ ] Generación de informes en PDF/Excel en backend (+0.5) 
    - [ ] WebSockets / Comunicación en tiempo real (+1.0) 
    - [ ] Batería de pruebas automatizadas (>40 % de endpoints) (+1.0) 
    - [X] Documentación OpenAPI / Swagger interactiva (+0.5) 