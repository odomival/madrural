# API Eventos - MADRURAL

Esta guía quedó dividida en un fichero por endpoint dentro de `api-doc/`.

## Base URLs

- Producción: `https://www.madrural.com/wp-json/madrural/v1`
- Desarrollo: `http://localhost/madrural/wp-json/madrural/v1`

## Índice de documentación por endpoint

- Login (token): `api-doc/login.md`
- GET eventos: `api-doc/get-eventos.md`
- GET evento por id: `api-doc/get-evento-by-id.md`
- POST evento: `api-doc/post-evento.md`
- PUT/PATCH evento: `api-doc/put-patch-evento.md`
- DELETE evento: `api-doc/delete-evento.md`

## Resumen de seguridad

- GET endpoints: públicos.
- POST/PUT/PATCH/DELETE: requieren `Authorization: Bearer <token>` obtenido en login.
