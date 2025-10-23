# Manual de Usuario - Sistema de Backup y Restore

## 📦 Sistema de Copias de Seguridad y Restauración

Este manual explica cómo usar el sistema de backup y restore implementado en el sistema de inventario Omega Cars.

---

## 🚀 Acceso al Sistema

Desde el **Dashboard principal**, encontrarás dos nuevas opciones:

1. **📦 Backup de Datos** - Para crear y gestionar copias de seguridad
2. **🔄 Restaurar Datos** - Para restaurar copias de seguridad

---

## 📦 Crear una Copia de Seguridad

### Pasos:

1. Desde el dashboard, haz clic en **"Backup de Datos"**
2. En la sección superior, haz clic en el botón **"📦 Crear Backup Ahora"**
3. El sistema generará automáticamente un archivo `.sql`

### Información mostrada:

- **Nombre del archivo**: `backup_YYYY-MM-DD_HH-MM-SS.sql`
- **Fecha de creación**
- **Tamaño del archivo** en KB

---

## 📥 Gestionar Copias de Seguridad

Desde la página de **Backup de Datos**, puedes:

### 1. **Descargar un Backup** ⬇️
   - Haz clic en el icono de descarga
   - El archivo se descargará en tu computadora

### 2. **Restaurar un Backup** 🔄
   - Haz clic en el icono de restaurar
   - Confirma la acción

### 3. **Eliminar un Backup** 🗑️
   - Haz clic en el icono de eliminar
   - Confirma la eliminación

---

## 🔄 Restaurar una Copia de Seguridad

### ⚠️ ADVERTENCIA

Al restaurar una copia de seguridad se eliminarán TODOS los datos actuales.

### Opción 1: Restaurar desde Backup Existente

1. Ve a **"Restaurar Datos"**
2. Selecciona un backup del menú desplegable
3. Haz clic en **"Restaurar Base de Datos"**
4. Confirma la acción

### Opción 2: Subir Archivo de Backup

1. Ve a **"Restaurar Datos"**
2. Selecciona un archivo `.sql` de tu computadora
3. Haz clic en **"Subir y Restaurar"**
4. Confirma la acción

---

## 🔒 Seguridad

- Los archivos de backup están protegidos mediante `.htaccess`
- No son accesibles directamente desde el navegador
- Los archivos `.sql` NO se suben al repositorio de GitHub

---

## 📋 Mejores Prácticas

1. **Crear backups regularmente** (diario, semanal o antes de cambios importantes)
2. **Mantener múltiples versiones** de backups
3. **Descargar copias externas** a tu computadora o nube
4. **Siempre crear un backup nuevo** antes de restaurar
5. **Probar la restauración ocasionalmente** para verificar que funciona

---

**Versión**: 1.0  
**Sistema**: Omega Cars Inventory System
