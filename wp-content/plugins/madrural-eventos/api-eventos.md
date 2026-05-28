# API Eventos - MADRURAL

Este documento describe los endpoints públicos de eventos.

Base URL:

- `https://www.madrural.com/wp-json/madrural/v1/api-eventos`

## 1) GET eventos

Obtiene eventos con filtros opcionales y paginado.

### Endpoint

- `GET https://www.madrural.com/wp-json/madrural/v1/api-eventos`

### Filtros soportados

Si un filtro viene vacío o `null`, no se aplica.

- `territorio`: id/slug del territorio permitido.
- `categoria`: id o slug de categoría.
- `desde`: fecha inicio de rango (`YYYY-MM-DD`).
- `hasta`: fecha fin de rango (`YYYY-MM-DD`).
- `paged`: página actual.
- `per_page`: elementos por página (por defecto 12, máximo 100).

Compatibilidad con parámetros usados en agenda:

- `me_territorio`, `me_categoria`, `me_desde`, `me_hasta`, `me_paged`.

### Reglas importantes

- No se filtra por estado de moderación/publicación (pueden retornar eventos no publicados).
- Los resultados solo incluyen eventos cuyo territorio sea uno de:
  - `Sierra Norte`
  - `Sierra de Guadarrama`
  - `Sierra Oeste`
  - `Las Vegas & La Alcarria`

### Ejemplos

Sin filtros:

- `GET https://www.madrural.com/wp-json/madrural/v1/api-eventos`

Con filtros:

- `GET https://www.madrural.com/wp-json/madrural/v1/api-eventos?territorio=3&categoria=musica&desde=2026-01-01&hasta=2026-12-31&paged=1&per_page=12`

Con parámetros de agenda:

- `GET https://www.madrural.com/wp-json/madrural/v1/api-eventos?me_territorio=3&me_categoria=musica&me_desde=2026-01-01&me_hasta=2026-12-31&me_paged=1`

### Respuesta (estructura)

Campos en inglés:

- `title`: valor de `titulo_en` desde la tabla `mod145_madrural_eventos`.
- `description`: valor de `descripcion_en` desde la tabla `mod145_madrural_eventos`.
- `category`: valor de `categoria_en` desde la tabla `mod145_madrural_eventos`.

```json
{
  "items": [
	{
	  "id": 123,
	  "titulo": "..."
	}
  ],
  "total": 1,
  "total_pages": 1,
  "page": 1,
  "per_page": 12
}
```

## 2) GET evento por id

Obtiene un evento por id.

### Endpoint

- `GET https://www.madrural.com/wp-json/madrural/v1/api-eventos/{id}`

### Ejemplo

- `GET https://www.madrural.com/wp-json/madrural/v1/api-eventos/123`

### Respuesta

- Devuelve el objeto evento si existe.
- Devuelve `404` si no existe o está en papelera (`trash`).
