# POST /api-eventos/login

## URL

- Producción: `https://www.madrural.com/wp-json/madrural/v1/api-eventos/login`
- Desarrollo: `http://localhost/madrural/wp-json/madrural/v1/api-eventos/login`

## Auth

- Público (no requiere token).

## Body (JSON)

```json
{
  "name": "superadmin",
  "password": "tu_password"
}
```

## Response 200

```json
{
  "token": "...",
  "token_type": "Bearer",
  "expires_in": 43200,
  "profile": {
	"id": 1,
	"name": "superadmin",
	"role": "superadmin",
	"territorio": "",
	"territorios": []
  }
}
```

## Errores

- `400` si faltan `name` o `password`.
- `401` si credenciales inválidas.
- `500` si el módulo de auth no está disponible.
