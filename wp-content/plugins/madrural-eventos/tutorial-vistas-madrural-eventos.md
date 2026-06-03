# Tutorial de uso de vistas — Plugin `madrural-eventos`

Este documento explica, de forma práctica, cómo usar todas las vistas principales del plugin y qué puede hacer cada rol.

---

## 1) Login y Logout

### ¿Cómo acceder al login desde el header?
- Si **no tienes sesión iniciada**, en el header verás:
  - `Agenda`
  - `Gestionar Eventos`
- Al hacer clic en **Gestionar Eventos**, se abre la vista de login de gestores.

### Vista de login
En la vista de acceso (`Acceso de Gestores`) debes completar:
- **Nombre** (`name`)
- **Contraseña** (`password`)

Luego presionas **Entrar**.

### ¿Qué pasa después de iniciar sesión?
- Si el perfil tiene permisos de gestión, en el header aparecen vistas de gestión.
- Si el perfil es **superadmin**, también aparece acceso a **Perfiles**.
- En interfaz autenticada se muestra menú de usuario (avatar flotante) con:
  - Gestión de Eventos
  - Perfiles (solo superadmin)
  - Cerrar Sesión

### ¿Cómo cerrar sesión?
- Desde el menú de usuario (avatar), clic en **Cerrar Sesión**.
- El sistema cierra la sesión y actualiza navegación/vistas disponibles para usuario no autenticado.

---

## 2) Vista Agenda

La Agenda es la vista pública de consulta de eventos.

### Filtros disponibles
- **Territorio**
- **Categoría**
- **Desde** (fecha)
- **Hasta** (fecha)

Con **Filtrar** aplicas los criterios y se actualiza el listado.

### Cómo se ordenan los eventos
El orden actual está optimizado y sigue esta lógica:

1. **Eventos futuros (incluyendo hoy)**
   - `fecha_inicio >= fecha actual`
   - Ordenados por fecha ascendente (el más próximo primero).

2. **Eventos pasados**
   - `fecha_inicio < fecha actual`
   - Ordenados por fecha descendente (los más cercanos a hoy primero).

3. Se concatenan ambas listas y luego se aplica paginación.

### Optimización aplicada
Para evitar lentitud con muchos eventos históricos:
- No se cargan todos los eventos en memoria.
- Se calculan totales por bloque (futuros/pasados).
- Se recuperan solo los IDs necesarios para la página actual.

### Qué muestra cada card de evento en Agenda
- Galería/imagen del evento
- Territorio (si existe)
- **Fecha de inicio** (si existe)
- Título
- Resumen de descripción
- Link al detalle del evento

---

## 3) Vista Detalle de Evento

Al abrir un evento se muestra una vista completa con:

### Cabecera visual del evento
- Galería principal
- Categoría destacada (badge)
- Título
- Meta principal (si existe):
  - Fecha de inicio
  - Hora
  - Territorio

### Contenido principal
- Sección **Sobre el evento** (descripción completa)
- Botón **Ver ubicación** (si hay URL de ubicación)

### Panel de información
- Fecha de inicio
- Fecha de fin
- Hora
- Categoría(s)
- Territorio / entidad gestora

### Galería extendida
- Grid de imágenes con vista ampliada (modal)

### Acciones disponibles
- **Ver lista de eventos** (volver a agenda)
- **Editar evento** (solo si el usuario tiene permisos sobre ese evento)

---

## 4) Vista Gestión de Eventos

Esta vista es para usuarios autenticados con permisos de gestión.

### Qué eventos se muestran según rol
- **admin**:
  - Ve/gestiona eventos de sus territorios asignados.
  - No accede a gestión de perfiles.
- **superadmin**:
  - Acceso total a gestión de eventos.
  - También puede acceder a gestión de perfiles.

### Qué muestra la tabla
- Título
- Estado de moderación
- Territorio
- Fecha
- Acciones: **Editar | Eliminar | Ver**

### Acciones
- **Editar**: abre el formulario de edición.
- **Eliminar**: solicita confirmación y elimina el evento.
- **Ver**: abre el detalle público del evento.

---

## 5) Vista Crear / Editar Evento

Esta vista se usa para alta y edición de eventos.

### Campos del formulario
- **Título**
- **Descripción**
- **Fecha inicio**
- **Fecha fin**
- **Hora**
- **Ubicación** (URL)
- **Estado de moderación**
- **Imágenes del evento** (galería)
- **Categoría**
- **Territorio**

### Categorías
- Puedes seleccionar una categoría existente.
- También puedes usar **Agregar categoría** para crear una nueva desde el formulario.
- La categoría creada queda disponible para futuros eventos.

### Estado de publicación / moderación
- **Borrador**:
  - Evento en construcción, le falta información o validación.
  - Normalmente aún no listo para publicación.
- **Pendiente**:
  - Evento completo o casi completo, pendiente de revisión/aprobación.
- **Publicado**:
  - Evento visible en la **Agenda pública**.

> Nota: la Agenda pública lista eventos publicados.

### Consideraciones por rol
- En perfiles **admin**, el territorio está restringido a sus territorios asignados.
- Un admin no tiene permisos para ampliar permisos globales ni para administrar perfiles.

---

## 6) Vista Perfiles (solo superadmin)

La vista de Perfiles está disponible únicamente para rol **superadmin**.

### Qué incluye
#### Panel izquierdo
- Lista paginada de perfiles registrados.
- Columnas:
  - Nombre
  - Rol
  - Territorio
  - Acción (Editar/Eliminar)

#### Panel derecho
- Formulario para **crear** o **editar** perfil.

### Campos de perfil
- **Nombre**
- **Contraseña**
  - Obligatoria al crear
  - Opcional al editar (solo cambias si quieres actualizarla)
- **Rol** (`admin` o `superadmin`)
- **Territorios** (selección múltiple)

### Qué permite cada rol
#### admin
- Puede crear/editar eventos dentro de sus territorios asignados.
- No tiene acceso al panel de perfiles.
- Su alcance está limitado por territorio.

#### superadmin
- Acceso completo a eventos (todos los territorios).
- Acceso completo a perfiles (crear, editar, eliminar).
- Puede asignar roles y territorios a otros perfiles.

---

## Resumen rápido de navegación
- **Sin sesión**: Agenda + Gestionar Eventos.
- **Con sesión admin**: Agenda + Gestión de Eventos.
- **Con sesión superadmin**: Agenda + Gestión de Eventos + Perfiles.

Este flujo mantiene separado el uso público (Agenda/Detalle) del uso de gestión interna (Eventos/Perfiles).