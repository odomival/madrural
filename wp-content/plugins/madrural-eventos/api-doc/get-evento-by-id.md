# GET /api-eventos/{id}

## URL

- Producción: `https://www.madrural.com/wp-json/madrural/v1/api-eventos/{id}`
- Desarrollo: `http://localhost/madrural/wp-json/madrural/v1/api-eventos/{id}`

## Auth

- Público (sin token).

## Path param

- `id` (int)

## Response 200

Devuelve un evento con, entre otros, estos campos:

- `titulo`
- `descripcion`
- `categoria`
- `titulo_en`
- `descripcion_en`
- `categoria_en`

## Errores

- `404` si no existe o no es un evento válido para API.
