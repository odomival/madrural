# GET /api-eventos

## URL

- Producción: `https://www.madrural.com/wp-json/madrural/v1/api-eventos`
- Desarrollo: `http://localhost/madrural/wp-json/madrural/v1/api-eventos`

## Auth

- Público (sin token).

## Query params

- `territorio` (id/slug permitido)
- `categoria` (id/slug)
- `desde` (`YYYY-MM-DD`)
- `hasta` (`YYYY-MM-DD`)
- `paged`
- `per_page` (default 12, max 100)

Compatibilidad Agenda:
- `me_territorio`, `me_categoria`, `me_desde`, `me_hasta`, `me_paged`

## Reglas

- Retorna eventos incluso no publicados (no filtra por estado de moderación/publicación).
- Solo incluye eventos con territorios permitidos:
  - Sierra Norte
  - Sierra de Guadarrama
  - Sierra Oeste
  - Las Vegas & La Alcarria

## Response 200 (resumen)

```json
{
  "items": [
	{
	  "id": 123,
	  "titulo": "...",
	  "descripcion": "...",
	  "categoria": "...",
	  "titulo_en": "...",
	  "descripcion_en": "...",
	  "categoria_en": "..."
	}
  ],
  "total": 1,
  "total_pages": 1,
  "page": 1,
  "per_page": 12
}
```
