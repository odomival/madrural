# Swagger / OpenAPI Integration

Esta integración agrega una interfaz tipo Swagger para consumir y probar los endpoints de `api-eventos`.

## 1) OpenAPI JSON

Endpoint de especificación:

- Producción: `https://www.madrural.com/wp-json/madrural/v1/api-docs`
- Desarrollo: `http://localhost/madrural/wp-json/madrural/v1/api-docs`

## 2) Interfaz Swagger UI

Se agregó el shortcode:

- `[madrural_api_docs]`

También se agregó una URL directa (página virtual):

- Producción: `https://www.madrural.com/api-eventos-docs`
- Desarrollo: `http://localhost/madrural/api-eventos-docs`

Para visualizar la interfaz:

1. Crea una página en WordPress (ejemplo: `API Docs`).
2. Coloca el shortcode `[madrural_api_docs]` en el contenido.
3. Publica la página.

Swagger UI cargará la especificación desde `/wp-json/madrural/v1/api-docs`.

## 3) Autenticación en Swagger

Los endpoints `POST/PUT/PATCH/DELETE` requieren Bearer token.

Flujo:

1. Ejecuta `POST /api-eventos/login` con `name` y `password`.
2. Copia el valor de `token` del response.
3. En Swagger, usa **Authorize** y pega: `Bearer <token>`.
4. Prueba endpoints protegidos.

## 4) Endpoints cubiertos

- `POST /api-eventos/login`
- `GET /api-eventos`
- `GET /api-eventos/{id}`
- `POST /api-eventos`
- `PUT /api-eventos/{id}`
- `PATCH /api-eventos/{id}`
- `DELETE /api-eventos/{id}`

## 5) Notas

- Swagger UI se carga por CDN (`unpkg`).
- Si no visualizas cambios, limpia caché del navegador y caché del sitio.
