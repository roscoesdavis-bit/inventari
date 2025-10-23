# Carpeta de Backups

Esta carpeta almacena las copias de seguridad de la base de datos del sistema de inventario Omega Cars.

## Seguridad

- Los archivos en esta carpeta están protegidos mediante `.htaccess` para prevenir acceso directo desde el navegador.
- Los archivos de backup **NO** deben ser subidos al repositorio de GitHub (están excluidos en `.gitignore`).
- Se recomienda mantener copias de seguridad en ubicaciones externas al servidor.

## Uso

Las copias de seguridad se generan automáticamente desde la interfaz web del sistema:

1. **Crear Backup**: Accede a `backup.php` desde el dashboard
2. **Restaurar**: Accede a `restore.php` desde el dashboard

## Formato de Archivos

Los archivos de backup siguen el formato:
- `backup_YYYY-MM-DD_HH-MM-SS.sql`

## Recomendaciones

- Crear backups periódicamente (diario, semanal, mensual según necesidad)
- Probar la restauración de backups regularmente
- Mantener múltiples versiones de backup
- Eliminar backups antiguos para liberar espacio
