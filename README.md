# Avanza.CentroVet

Sistema web veterinario desarrollado en PHP, MySQL, Nginx y Docker Compose.

## Inicio rápido

1. Descomprime el proyecto.
2. Abre una terminal dentro de la carpeta `avanza-centrovet-corregido`.
3. Si ya habías ejecutado una versión anterior del proyecto, elimina primero el volumen viejo de MySQL:

```bash
docker compose down -v
```

> Este comando elimina la base de datos de pruebas anterior. Es necesario al cambiar los usuarios iniciales de `init.sql`.

4. Construye e inicia el proyecto:

```bash
docker compose up -d --build
```

5. Abre:
   - Sistema: http://localhost:8080
   - phpMyAdmin: http://localhost:8081

## Usuarios de demostración

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@avanzacentrovet.local | password |
| Veterinario | vet@avanzacentrovet.local | password |
| Cliente | cliente@avanzacentrovet.local | password |

## Base de datos / phpMyAdmin

- Base: `avanza_centrovet`
- Usuario root: `root`
- Contraseña root: `root123`
- Usuario de aplicación: `avanza`
- Contraseña de aplicación: `avanza123`

## Imágenes de productos

Los productos iniciales incluyen imágenes locales en:

`uploads/productos/`

El administrador puede subir imágenes JPG, JPEG, PNG o WEBP desde Gestión de productos. Las imágenes se muestran tanto en el catálogo como en la página de inicio.
