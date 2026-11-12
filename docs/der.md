# Modelo Entidad-Relacion de SmartStylist - Diagrama entidad-relacion en Mermaid

El diagrama representa las entidades funcionales del proyecto. Excluye tablas internas de infraestructura creadas por Laravel, como `cache` y `cache_locks`.

```mermaid
erDiagram
    users ||--o{ garments : posee
    users ||--o{ outfits : crea
    categories ||--o{ garments : clasifica
    colors ||--o{ garments : identifica_color
    styles o|--o{ garments : identifica_estilo
    outfits ||--o{ outfit_garments : contiene
    garments ||--o{ outfit_garments : incluye
    outfits ||--o{ schedules : asignado
    outfits ||--o{ outfit_logs : utilizado

    users {
        BIGINT id PK
        VARCHAR name
        VARCHAR email UK
        TIMESTAMPTZ email_verified_at
        VARCHAR password
        VARCHAR role
        VARCHAR remember_token
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    categories {
        BIGINT id PK
        VARCHAR name UK
        VARCHAR description
    }

    colors {
        BIGINT id PK
        VARCHAR name UK
        CHAR hex_code UK
    }

    styles {
        BIGINT id PK
        VARCHAR name UK
        VARCHAR description
    }

    garments {
        BIGINT id PK
        BIGINT user_id FK
        BIGINT category_id FK
        BIGINT color_id FK
        BIGINT style_id FK
        VARCHAR name
        VARCHAR season
        VARCHAR image_url
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    outfits {
        BIGINT id PK
        BIGINT user_id FK
        VARCHAR name
        VARCHAR description
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    outfit_garments {
        BIGINT outfit_id PK, FK
        BIGINT garment_id PK, FK
        BOOLEAN is_primary
        VARCHAR note
    }

    schedules {
        BIGINT id PK
        BIGINT outfit_id FK
        DATE scheduled_date
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }

    outfit_logs {
        BIGINT id PK
        BIGINT outfit_id FK
        TIMESTAMPTZ used_at
        TIMESTAMPTZ created_at
        TIMESTAMPTZ updated_at
    }
```