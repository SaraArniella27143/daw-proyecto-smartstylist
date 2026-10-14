# Anteproyecto: SmartStylist 

**Módulo:** Proyecto Intermodular (0379 · PIDW2) — 2º DAW Virtual 
**Centro:** CIFP Avilés (Curso 2026/2027) 
**Autor/a:** Sara Arniella Casar
**Repositorio GitHub:** [https://github.com/saraarniellacasar/daw-proyecto-smartstylist] 
**Tablero GitHub Projects:** [https://github.com/users/saraarniellacasar/projects/1] 

## 1. Justificación y dominio funcional 
### 1.1. Elevator pitch (100–150 palabras) 

¿Cuántas veces nos hemos parado frente al armario con la sensación de "no tener qué ponernos", a pesar de estar lleno de ropa? 
SmartStylist es una aplicación web interactiva diseñada para actuar como un estilista personal en el bolsillo. La plataforma permite a los usuarios digitalizar su propio armario mediante la subida de fotografías o escaneo rápido de sus prendas. Gracias a un sistema inteligente de categorización y reglas de combinación de estilo (por colores, temporadas, ocasiones y tipos de ropa), la aplicación genera automáticamente sugerencias y "outfits" completos combinando lo que el usuario ya tiene. SmartStylist resuelve el problema de la fatiga de decisión diaria y fomenta la moda circular y el consumo consciente, ayudando a redescubrir y sacar el máximo partido a la ropa existente en el propio vestidor sin necesidad de comprar más.

### 1.2. Público objetivo y contexto de uso [A quién va dirigida la aplicación y en qué situaciones se utiliza.]  
Usuarios / Amantes de la moda y particulares: Personas que desean optimizar su tiempo al vestirse cada mañana, organizar mejor su ropa o buscar inspiración a partir de las prendas que ya poseen.

Contexto de uso: Se utiliza de forma cotidiana desde dispositivos móviles u ordenadores (principalmente móviles), ya sea al planificar los conjuntos para la semana, al ordenar el armario de forma digital, o justo antes de salir de casa, al ir de compras para ver qué combinaciones posibles existen.


 --- 
## 2. Roles del sistema y casos de uso 
### 2.1. Matriz de roles (RBAC) 
- **Administrador:** Control total de la plataforma. Gestiona los catálogos maestros de estilos, colores, tipos de prendas y tendencias globales, además de supervisar cuentas de usuario y métricas de uso del sistema.
- **Usuario registrado:** Funcionalidad principal del sistema. Sube y escanea sus prendas, crea y guarda conjuntos/outfits favoritos, recibe sugerencias automáticas de combinación y planifica su calendario de estilismos.
- **Público / invitado:** Visitantes no autenticados que pueden explorar la página de presentación de la aplicación, ver ejemplos de cómo funciona el asistente y acceder a los formularios de registro o inicio de sesión.

### 2.2. Casos de uso principales (mínimo 6) 
1. **CU-01:** Registro e inicio de sesión seguro de usuarios con autenticación basada en tokens JWT.
2. **CU-02:** Subida, escaneo y gestión del inventario personal de prendas (CRUD de armario: categoría, color, temporada, estilo).
3. **CU-03:** Generación automática o manual de sugerencias y outfits combinando las prendas del usuario.
4. **CU-04:** Planificador semanal de estilismos (asignar un outfit a un día concreto del calendario).
5. **CU-05:** Gestión y mantenimiento del catálogo maestro de estilos y etiquetas por parte del administrador.
6. **CU-06:** Visualización de estadísticas de uso del armario (prendas más usadas, menos utilizadas o combinaciones frecuentes).

### 2.3. Alcance del MVP (Hito 3, diciembre) 
- **Incluido:** Sistema de autenticación, interfaz para subir prendas y catalogarlas (con subida de imágenes), motor básico de filtrado y sugerencias de combinación basadas en etiquetas de color/estilo, calendario semanal de outfits y panel básico de administración. - **Fuera del MVP:** Reconocimiento de prendas mediante Inteligencia Artificial, o la integración con tiendas externas para sugerir compras complementarias (quedan para add-ons o versiones futuras). 
---  

## 3. Modelo de datos 
- **Diagrama Entidad-Relación en 3FN:** [docs/der.md] 

- **Relación 1:N:** [USUARIO] 1 ---- N [PRENDA] (Un usuario puede tener registradas muchas prendas de su armario (camisetas, vaqueros,..). Pero cada prenda concreta de ese armario le pertenece a un solo usuario.)
- **Relación M:N con atributos:** [OUTFIT] M ----- N [PRENDA] (Un conjunto u outfit está formado por varias prendas (una camiseta y un pantalón). A su vez, una misma prenda (por ejemplo, esa camiseta) puede aparecer en muchos conjuntos diferentes que se creen a lo largo de la semana).
En este caso, se podría crear una tabla intermedia (outfit_prenda, por ejemplo) que sirve de puente para apuntar que prendas forman parte de que conjuntos.

 --- 

   ## 4. Stack tecnológico previsto 
   - **Motor de base de datos:** MySQL — [Es el gestor de bases de datos relacionales más utilizado y compatible con la mayoria de servidores web. Se amolda perfectamente a este proyecto porque nos permite organizar la informacion en tablas conectadas entre sí, de forma ordenada y segura.] 
   - **Backend API:** Laravel — [Es uno de los frameworks más potentes y ágiles que existen hoy en día. Nos ahorra mucho tiempo de desarrollo porque ya viene con herramientas integradas para la seguridad]
   - **Frontend:** React — [Es la biblioteca de JavaScript más conocida para crear interfaces web dinámicas y modernas. Tiene una navegación fluida y rápida, permitiendo que el usuario interactúe con su armario virtual sin que la página tenga que recargarse constantemente.] 
   - **Alojamiento cloud previsto:** AWS — [Ofrece un entorno profesional real donde poder desplegar nuestra aplicación y nos da la flexibilidad de escalar el proyecto en el futuro si SmartStylist tuviera miles de usuarios.] 

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