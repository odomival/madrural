# POST /api-eventos

## URL

- Producción: `https://www.madrural.com/wp-json/madrural/v1/api-eventos`
- Desarrollo: `http://localhost/madrural/wp-json/madrural/v1/api-eventos`

## Auth

- Requiere token Bearer del endpoint login.
- Header: `Authorization: Bearer <token>`

## Body (JSON) - ejemplo

```json
{
  "title": "Título ES",
  "description": "Descripción ES",
  "categoria": "musica",
  "categorias": ["musica"],
  "territorio": "sierra-norte",
  "fecha_inicio": "2026-01-10",
  "fecha_fin": "2026-01-11",
  "hora_evento": "10:00",
  "ubicacion": "https://maps.google.com/...",
  "estado_moderacion": "borrador"
}
```

## Permisos

- `superadmin`: puede crear en cualquier territorio permitido.
- `admin`: solo en sus territorios asignados.

## Response 200

```json
{
  "message": "Evento creado correctamente.",
  "event": {
	"id": 123,
	"titulo": "...",
	"descripcion": "...",
	"categoria": "...",
	"titulo_en": "...",
	"descripcion_en": "...",
	"categoria_en": "..."
  }
}
```

## Errores

- `401` token faltante/inválido/expirado.
- `403` sin permisos por rol o territorio.
- `400` campos inválidos.
