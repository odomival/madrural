# DELETE /api-eventos/{id}

## URL

- Producción: `https://www.madrural.com/wp-json/madrural/v1/api-eventos/{id}`
- Desarrollo: `http://localhost/madrural/wp-json/madrural/v1/api-eventos/{id}`

## Auth

- Requiere token Bearer del endpoint login.
- Header: `Authorization: Bearer <token>`

## Path param

- `id` (int)

## Permisos

- `superadmin`: acceso total.
- `admin`: solo si el evento pertenece a su territorio.

## Response 200

```json
{
  "message": "Evento enviado a la papelera.",
  "id": 123
}
```

## Errores

- `401` token faltante/inválido/expirado.
- `403` sin permisos.
- `404` evento no encontrado.
