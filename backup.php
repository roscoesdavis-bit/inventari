<?php
include('includes/auth.php');
include('includes/db.php');

$mensaje = '';
$tipo_mensaje = '';

// Función para crear backup de la base de datos
function crearBackup($conn, $db) {
    $backupDir = 'backups/';

    // Crear directorio si no existe
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $fecha = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . 'backup_' . $fecha . '.sql';

    $contenido = "-- Backup de Base de Datos: $db\n";
    $contenido .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $contenido .= "-- Generado por Sistema de Inventario Omega Cars\n\n";
    $contenido .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $contenido .= "SET time_zone = \"+00:00\";\n\n";

    // Obtener todas las tablas
    $tablas = array();
    $result = $conn->query("SHOW TABLES");

    while ($row = $result->fetch_array()) {
        $tablas[] = $row[0];
    }

    // Para cada tabla
    foreach ($tablas as $tabla) {
        $contenido .= "\n-- --------------------------------------------------------\n";
        $contenido .= "-- Estructura de tabla para la tabla `$tabla`\n";
        $contenido .= "-- --------------------------------------------------------\n\n";

        // Obtener estructura de la tabla
        $result = $conn->query("SHOW CREATE TABLE `$tabla`");
        $row = $result->fetch_array();

        $contenido .= "DROP TABLE IF EXISTS `$tabla`;\n";
        $contenido .= $row[1] . ";\n\n";

        // Obtener datos de la tabla
        $contenido .= "-- Volcado de datos para la tabla `$tabla`\n\n";

        $result = $conn->query("SELECT * FROM `$tabla`");

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $contenido .= "INSERT INTO `$tabla` VALUES (";

                $valores = array();
                foreach ($row as $valor) {
                    if ($valor === null) {
                        $valores[] = "NULL";
                    } else {
                        $valores[] = "'" . $conn->real_escape_string($valor) . "'";
                    }
                }

                $contenido .= implode(", ", $valores);
                $contenido .= ");\n";
            }
        }

        $contenido .= "\n";
    }

    // Guardar archivo
    if (file_put_contents($backupFile, $contenido)) {
        return array(
            'success' => true,
            'file' => $backupFile,
            'size' => filesize($backupFile)
        );
    } else {
        return array('success' => false);
    }
}

// Función para eliminar backup
function eliminarBackup($archivo) {
    $backupDir = 'backups/';
    $rutaCompleta = $backupDir . basename($archivo);

    if (file_exists($rutaCompleta)) {
        return unlink($rutaCompleta);
    }
    return false;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_backup'])) {
        $resultado = crearBackup($conn, $db);

        if ($resultado['success']) {
            $tamano = round($resultado['size'] / 1024, 2);
            $mensaje = "¡Backup creado exitosamente! Archivo: " . basename($resultado['file']) . " (Tamaño: {$tamano} KB)";
            $tipo_mensaje = 'success';
        } else {
            $mensaje = "Error al crear el backup. Verifica los permisos de escritura.";
            $tipo_mensaje = 'error';
        }
    } elseif (isset($_POST['eliminar_backup'])) {
        if (eliminarBackup($_POST['archivo'])) {
            $mensaje = "Backup eliminado correctamente.";
            $tipo_mensaje = 'success';
        } else {
            $mensaje = "Error al eliminar el backup.";
            $tipo_mensaje = 'error';
        }
    } elseif (isset($_POST['descargar_backup'])) {
        $archivo = 'backups/' . basename($_POST['archivo']);

        if (file_exists($archivo)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($archivo) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($archivo));
            readfile($archivo);
            exit;
        }
    }
}

// Listar backups existentes
$backups = array();
if (is_dir('backups/')) {
    $archivos = scandir('backups/');
    foreach ($archivos as $archivo) {
        if (pathinfo($archivo, PATHINFO_EXTENSION) === 'sql') {
            $rutaCompleta = 'backups/' . $archivo;
            $backups[] = array(
                'nombre' => $archivo,
                'fecha' => date("Y-m-d H:i:s", filemtime($rutaCompleta)),
                'tamano' => round(filesize($rutaCompleta) / 1024, 2)
            );
        }
    }
    // Ordenar por fecha descendente
    usort($backups, function($a, $b) {
        return strcmp($b['fecha'], $a['fecha']);
    });
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup de Base de Datos - Omega Cars</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/backup.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Copias de Seguridad</h2>
            <p>Gestiona las copias de seguridad de la base de datos</p>
        </div>

        <?php if ($mensaje): ?>
        <div class="mensaje <?php echo $tipo_mensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="backup-section">
            <div class="backup-card">
                <h3>Crear Nueva Copia de Seguridad</h3>
                <p>Genera una copia completa de la base de datos actual</p>
                <form method="POST" style="margin-top: 20px;">
                    <button type="submit" name="crear_backup" class="btn-primary">
                        📦 Crear Backup Ahora
                    </button>
                </form>
            </div>
        </div>

        <div class="backups-list">
            <h3>Copias de Seguridad Existentes (<?php echo count($backups); ?>)</h3>

            <?php if (empty($backups)): ?>
                <div class="no-backups">
                    <p>No hay copias de seguridad disponibles.</p>
                    <p>Crea tu primera copia de seguridad usando el botón de arriba.</p>
                </div>
            <?php else: ?>
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>Nombre del Archivo</th>
                            <th>Fecha de Creación</th>
                            <th>Tamaño</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($backup['nombre']); ?></td>
                            <td><?php echo $backup['fecha']; ?></td>
                            <td><?php echo $backup['tamano']; ?> KB</td>
                            <td class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="archivo" value="<?php echo htmlspecialchars($backup['nombre']); ?>">
                                    <button type="submit" name="descargar_backup" class="btn-download" title="Descargar">
                                        ⬇️
                                    </button>
                                </form>
                                <a href="restore.php?file=<?php echo urlencode($backup['nombre']); ?>"
                                   class="btn-restore"
                                   title="Restaurar"
                                   onclick="return confirm('¿Estás seguro de que deseas restaurar esta copia? Se reemplazarán todos los datos actuales.');">
                                    🔄
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="archivo" value="<?php echo htmlspecialchars($backup['nombre']); ?>">
                                    <button type="submit"
                                            name="eliminar_backup"
                                            class="btn-delete"
                                            title="Eliminar"
                                            onclick="return confirm('¿Estás seguro de que deseas eliminar esta copia de seguridad?');">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="navigation-buttons">
            <a href="dashboard.php" class="btn-back">← Volver al Dashboard</a>
            <a href="restore.php" class="btn-restore-page">Restaurar desde Archivo</a>
        </div>
    </div>
</body>
</html>
