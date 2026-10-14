# Modelo de Datos - SmartStylist

Este documento describe la estructura básica de la base de datos para la aplicación **SmartStylist**.

---

## 1. Estructura y Relaciones

El sistema consta de 3 tablas principales para gestionar los usuarios, las prendas de su armario y los conjuntos guardados:

- **`usuarios` 1 : N `prendas`**: Cada usuario puede guardar varias prendas en su armario.
- **`usuarios` 1 : N `outfits`**: Cada usuario puede crear varios conjuntos de ropa (outfits).
- **`outfits` M : N `prendas`**: Un conjunto está formado por varias prendas, y una prenda puede usarse en varios conjuntos (relación resuelta con una tabla intermedia).

---

## 2. Tablas y Atributos

### Tabla: `usuarios`
* Almacena los datos de acceso de las personas que usan la app.
* **Atributos:**
  * `id` (Identificador único)
  * `nombre`
  * `email`
  * `password`

### Tabla: `prendas`
* Guarda la información de la ropa que sube el usuario.
* **Atributos:**
  * `id` (Identificador único)
  * `usuario_id` (Dueño de la prenda)
  * `nombre` (Ej: Camisa blanca)
  * `categoria` (Ej: Parte superior, Calzado, etc. Guardado como texto simple)
  * `color`
  * `imagen` (Ruta de la foto)

### Tabla: `outfits`
* Guarda los conjuntos creados por el usuario.
* **Atributos:**
  * `id` (Identificador único)
  * `usuario_id` (Dueño del conjunto)
  * `nombre` (Ej: Look de verano)

### Tabla Intermedia: `outfit_prenda`
* Conecta los conjuntos con las prendas que los forman.
* **Atributos:**
  * `id` (Identificador único)
  * `outfit_id`
  * `prenda_id`
