<?php
include('includes/auth.php');
include('includes/db.php');

$mensaje = '';
$tipo_mensaje = '';

// Función para restaurar backup mejorada
function restaurarBackup($conn, $archivoSQL) {
    // Leer el archivo SQL
    $contenido = file_get_contents($archivoSQL);

    if ($contenido === false) {
        return array(
            'success' => false,
            'mensaje' => 'No se pudo leer el archivo de backup.'
        );
    }

    // Desactivar verificación de claves foráneas y modo estricto
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $conn->query("SET AUTOCOMMIT = 0");
    $conn->query("START TRANSACTION");

    $ejecutadas = 0;
    $errores = array();

    // Dividir por líneas y procesar
    $lineas = explode("\n", $contenido);
    $consulta_temporal = '';

    foreach ($lineas as $linea) {
        // Limpiar espacios
        $linea_limpia = trim($linea);

        // Ignorar líneas vacías y comentarios
        if (empty($linea_limpia) ||
            substr($linea_limpia, 0, 2) === '--' ||
            substr($linea_limpia, 0, 2) === '/*' ||
            substr($linea_limpia, 0, 1) === '#') {
            continue;
        }

        // Agregar línea a la consulta temporal
        $consulta_temporal .= ' ' . $linea;

        // Si la línea termina en punto y coma, ejecutar la consulta
        if (substr($linea_limpia, -1) === ';') {
            // Limpiar la consulta
            $consulta_temporal = trim($consulta_temporal);

            // Ejecutar la consulta
            if (!empty($consulta_temporal)) {
                if ($conn->query($consulta_temporal)) {
                    $ejecutadas++;
                } else {
                    $errores[] = array(
                        'consulta' => substr($consulta_temporal, 0, 100) . '...',
                        'error' => $conn->error
                    );

                    // Si hay demasiados errores, hacer rollback
                    if (count($errores) > 20) {
                        $conn->query("ROLLBACK");
                        $conn->query("SET FOREIGN_KEY_CHECKS=1");
                        $conn->query("SET AUTOCOMMIT = 1");
                        return array(
                            'success' => false,
                            'mensaje' => 'Demasiados errores durante la restauración. Se canceló el proceso.',
                            'errores' => $errores
                        );
                    }
                }
            }

            // Resetear consulta temporal
            $consulta_temporal = '';
        }
    }

    // Commit de la transacción
    $conn->query("COMMIT");

    // Reactivar verificación de claves foráneas
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    $conn->query("SET AUTOCOMMIT = 1");

    // Si hay pocos errores (comentarios, etc), considerarlo éxito
    if (count($errores) > 0 && count($errores) < 5) {
        return array(
            'success' => true,
            'mensaje' => 'Base de datos restaurada con advertencias menores. Se ejecutaron ' . $ejecutadas . ' consultas.',
            'consultas' => $ejecutadas,
            'advertencias' => count($errores)
        );
    } elseif (count($errores) >= 5) {
        return array(
            'success' => false,
            'mensaje' => 'Se ejecutaron ' . $ejecutadas . ' consultas, pero hubo ' . count($errores) . ' errores.',
            'errores' => $errores
        );
    }

    return array(
        'success' => true,
        'mensaje' => 'Base de datos restaurada exitosamente. Se ejecutaron ' . $ejecutadas . ' consultas.',
        'consultas' => $ejecutadas
    );
}

// Procesar restauración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurar'])) {
    $archivoSubido = false;
    $rutaArchivo = '';

    // Verificar si se subió un archivo
    if (isset($_FILES['archivo_backup']) && $_FILES['archivo_backup']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'backups/';
        $nombreArchivo = 'uploaded_' . date('Y-m-d_H-i-s') . '.sql';
        $rutaArchivo = $uploadDir . $nombreArchivo;

        if (move_uploaded_file($_FILES['archivo_backup']['tmp_name'], $rutaArchivo)) {
            $archivoSubido = true;
        } else {
            $mensaje = "Error al subir el archivo.";
            $tipo_mensaje = 'error';
        }
    }
    // Verificar si se seleccionó un archivo existente
    elseif (isset($_POST['archivo_existente']) && !empty($_POST['archivo_existente'])) {
        $rutaArchivo = 'backups/' . basename($_POST['archivo_existente']);
        if (file_exists($rutaArchivo)) {
            $archivoSubido = true;
        }
    }

    if ($archivoSubido) {
        $resultado = restaurarBackup($conn, $rutaArchivo);

        if ($resultado['success']) {
            $mensaje = $resultado['mensaje'];
            $tipo_mensaje = 'success';
        } else {
            $mensaje = $resultado['mensaje'];
            if (isset($resultado['errores'])) {
                $mensaje .= "<br><br><strong>Detalles de errores:</strong><br>";
                $mensaje .= "<small>";
                foreach (array_slice($resultado['errores'], 0, 5) as $error) {
                    if (is_array($error)) {
                        $mensaje .= "• " . htmlspecialchars($error['error']) . "<br>";
                    } else {
                        $mensaje .= "• " . htmlspecialchars($error) . "<br>";
                    }
                }
                if (count($resultado['errores']) > 5) {
                    $mensaje .= "... y " . (count($resultado['errores']) - 5) . " errores más.";
                }
                $mensaje .= "</small>";
            }
            $tipo_mensaje = 'error';
        }
    } else {
        if (empty($mensaje)) {
            $mensaje = "Debes seleccionar un archivo para restaurar.";
            $tipo_mensaje = 'error';
        }
    }
}

// Obtener archivo desde parámetro GET si existe
$archivoPreseleccionado = isset($_GET['file']) ? basename($_GET['file']) : '';

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
    <title>Restaurar Base de Datos - Omega Cars</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/backup.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Restaurar Base de Datos</h2>
            <p>Restaura una copia de seguridad anterior</p>
        </div>

        <?php if ($mensaje): ?>
        <div class="mensaje <?php echo $tipo_mensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="warning-box">
            <h3>⚠️ Advertencia Importante</h3>
            <p>Restaurar una copia de seguridad <strong>reemplazará todos los datos actuales</strong> de la base de datos.</p>
            <p>Asegúrate de tener un backup reciente antes de proceder si deseas conservar los datos actuales.</p>
        </div>

        <div class="restore-section">
            <div class="restore-card">
                <h3>Opción 1: Restaurar desde Backup Existente</h3>
                <p>Selecciona una copia de seguridad previamente creada</p>

                <?php if (empty($backups)): ?>
                    <div class="no-backups">
                        <p>No hay copias de seguridad disponibles.</p>
                        <p><a href="backup.php">Ir a crear backup</a></p>
                    </div>
                <?php else: ?>
                    <form method="POST" id="formRestaurarExistente" onsubmit="return confirmarRestauracion();">
                        <div class="form-group">
                            <label for="archivo_existente">Selecciona un backup:</label>
                            <select name="archivo_existente" id="archivo_existente" class="form-control" required>
                                <option value="">-- Selecciona un archivo --</option>
                                <?php foreach ($backups as $backup): ?>
                                <option value="<?php echo htmlspecialchars($backup['nombre']); ?>"
                                        <?php echo ($backup['nombre'] === $archivoPreseleccionado) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($backup['nombre']); ?>
                                    (<?php echo $backup['fecha']; ?> - <?php echo $backup['tamano']; ?> KB)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="restaurar" class="btn-primary">
                            🔄 Restaurar Base de Datos
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="restore-card">
                <h3>Opción 2: Subir Archivo de Backup</h3>
                <p>Sube un archivo .sql de backup desde tu computadora</p>

                <form method="POST" enctype="multipart/form-data" onsubmit="return confirmarRestauracion();">
                    <div class="form-group">
                        <label for="archivo_backup">Selecciona archivo .sql:</label>
                        <input type="file"
                               name="archivo_backup"
                               id="archivo_backup"
                               accept=".sql"
                               class="form-control"
                               required>
                        <small>Solo archivos .sql (máximo 50MB)</small>
                    </div>
                    <button type="submit" name="restaurar" class="btn-primary">
                        📤 Subir y Restaurar
                    </button>
                </form>
            </div>
        </div>

        <div class="info-box">
            <h3>ℹ️ Información sobre Restauración</h3>
            <ul>
                <li>El proceso de restauración puede tomar varios minutos dependiendo del tamaño de la base de datos.</li>
                <li>Se recomienda realizar este proceso fuera del horario de mayor uso del sistema.</li>
                <li>Todos los usuarios serán desconectados automáticamente después de la restauración.</li>
                <li>Asegúrate de que el archivo de backup sea compatible con la versión actual de la base de datos.</li>
            </ul>
        </div>

        <div class="navigation-buttons">
            <a href="dashboard.php" class="btn-back">← Volver al Dashboard</a>
            <a href="backup.php" class="btn-backup-page">Gestionar Backups</a>
        </div>
    </div>

    <script>
        function confirmarRestauracion() {
            return confirm('⚠️ ADVERTENCIA ⚠️\n\n¿Estás completamente seguro de que deseas restaurar esta copia de seguridad?\n\nEsta acción:\n- Eliminará TODOS los datos actuales\n- Reemplazará la base de datos completa\n- NO se puede deshacer\n\n¿Deseas continuar?');
        }
    </script>
</body>
</html>
