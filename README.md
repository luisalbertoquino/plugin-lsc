# Accesibilidad LSC - Bloques por Menú

Plugin de WordPress que muestra videos (MP4) o GIF en Lengua de Señas Colombiana (LSC) asociados a los ítems principales de cualquier menú de WordPress, mediante bloques configurables (menú de origen, tamaño, posición y páginas donde se muestran), al hacer hover en escritorio o tocar un ícono en móvil.

Ver [readme.txt](readme.txt) para la documentación completa (instalación, uso y changelog en formato estándar de plugin de WordPress).

## Desarrollo

- `includes/` — clases PHP del plugin (CPT de bloques, repositorio de datos, pantallas de administración, frontend, migración).
- `assets/` — CSS y JS del admin y del frontend.

## Empaquetar para subir a WordPress

```bash
./build.sh
```

Genera `build/accesibilidad-lsc.zip`, listo para subir desde **Plugins > Añadir nuevo > Subir plugin** en el panel de WordPress. La carpeta `build/` no se versiona (ver `.gitignore`); cada `git pull` requiere volver a correr `build.sh` para regenerar el zip.
