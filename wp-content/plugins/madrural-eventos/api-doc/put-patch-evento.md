# PUT/PATCH /api-eventos/{id}

## URL

- Producción: `https://www.madrural.com/wp-json/madrural/v1/api-eventos/{id}`
- Desarrollo: `http://localhost/madrural/wp-json/madrural/v1/api-eventos/{id}`

## Auth

- Requiere token Bearer del endpoint login.
- Header: `Authorization: Bearer <token>`

## Path param

- `id` (int)

## Body (JSON)

Mismos campos que creación; se actualizan solo los enviados.

## Permisos

- `superadmin`: acceso total.
- `admin`: solo si el evento pertenece a su territorio y el territorio enviado (si aplica) también está permitido.

## Response 200

```json
{
  "message": "Evento actualizado correctamente.",
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
- `403` sin permisos.
- `404` evento no encontrado.
