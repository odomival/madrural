# API Eventos - MADRURAL

Esta guía quedó dividida en un fichero por endpoint dentro de `api-doc/`.

## Base URLs

- Producción: `https://www.madrural.com/wp-json/madrural/v1`
- Desarrollo: `http://localhost/madrural/wp-json/madrural/v1`

## Índice de documentación por endpoint

- Login (token): `api-doc/login.md`
- GET eventos: `api-doc/get-eventos.md`
- GET evento por id: `api-doc/get-evento-by-id.md`
- POST subir imagen: `POST /api-eventos/upload-image`
- POST evento: `api-doc/post-evento.md`
- PUT/PATCH evento: `api-doc/put-patch-evento.md`
- DELETE evento: `api-doc/delete-evento.md`

## Resumen de seguridad

- GET endpoints: públicos.
- POST/PUT/PATCH/DELETE y upload-image: requieren `Authorization: Bearer <token>` obtenido en login.

## Contrato de payload para POST/PUT/PATCH

Para crear/actualizar eventos solo se aceptan campos del formulario de crear/editar:

- `title`
- `description`
- `fecha_inicio`
- `fecha_fin`
- `hora_evento`
- `ubicacion`
- `estado_moderacion`
- `territorio`
- `categoria` (única)
- `galeria_ids` (array de IDs de imágenes ya subidas)

Si se envían propiedades fuera de este listado, la API retorna error `400`.
