<?php
include('includes/auth.php');
include('includes/db.php');

// Eliminar proveedor
if (isset($_GET['eliminar'])) {
  $id = intval($_GET['eliminar']);
  $conn->query("DELETE FROM proveedores WHERE id = $id");
  header('Location: providers.php');
  exit;
}

// Actualizar proveedor
if (isset($_POST['actualizar'])) {
  $id = intval($_POST['id']);
  $nombre = $_POST['nombre'];
  $empresa = $_POST['empresa'];
  $email = $_POST['email'];
  $telefono = $_POST['telefono'];
  $direccion = $_POST['direccion'];
  $pais = $_POST['pais'];
  $notas = $_POST['notas'];

  $sql = "UPDATE proveedores SET
          nombre = '$nombre',
          empresa = '$empresa',
          email = '$email',
          telefono = '$telefono',
          direccion = '$direccion',
          pais = '$pais',
          notas = '$notas'
          WHERE id = $id";
  $conn->query($sql);
  header('Location: providers.php');
  exit;
}

// Guardar nuevo proveedor
if (isset($_POST['guardar'])) {
  $nombre = $_POST['nombre'];
  $empresa = $_POST['empresa'];
  $email = $_POST['email'];
  $telefono = $_POST['telefono'];
  $direccion = $_POST['direccion'];
  $pais = $_POST['pais'];
  $notas = $_POST['notas'];

  $sql = "INSERT INTO proveedores (nombre, empresa, email, telefono, direccion, pais, notas)
          VALUES ('$nombre', '$empresa', '$email', '$telefono', '$direccion', '$pais', '$notas')";
  $conn->query($sql);
  header('Location: providers.php');
  exit;
}

// Obtener proveedor a editar
$proveedorEditar = null;
if (isset($_GET['editar'])) {
  $id = intval($_GET['editar']);
  $result = $conn->query("SELECT * FROM proveedores WHERE id = $id");
  $proveedorEditar = $result->fetch_assoc();
}

// Obtener proveedores con estadísticas
$proveedores = $conn->query("
  SELECT p.*,
         COUNT(pr.id) as total_productos,
         SUM(pr.stock) as total_stock
  FROM proveedores p
  LEFT JOIN productos pr ON p.id = pr.proveedor_id
  GROUP BY p.id
  ORDER BY p.nombre ASC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Proveedores - Omega Cars</title>
  <link rel="stylesheet" href="assets/css/providers.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h2>Gestión de Proveedores</h2>
      <a href="dashboard.php" class="back-link">← Volver al Dashboard</a>
    </div>

    <div class="form-section">
      <h2><?php echo $proveedorEditar ? 'Editar Proveedor' : 'Agregar Nuevo Proveedor'; ?></h2>
      <form method="POST">
        <?php if ($proveedorEditar): ?>
          <input type="hidden" name="id" value="<?php echo $proveedorEditar['id']; ?>">
        <?php endif; ?>

        <div class="form-row">
          <div class="form-field">
            <label>Nombre del Contacto *</label>
            <input type="text" name="nombre" placeholder="Nombre completo" required
                   value="<?php echo $proveedorEditar ? htmlspecialchars($proveedorEditar['nombre']) : ''; ?>">
          </div>

          <div class="form-field">
            <label>Empresa</label>
            <input type="text" name="empresa" placeholder="Nombre de la empresa"
                   value="<?php echo $proveedorEditar ? htmlspecialchars($proveedorEditar['empresa']) : ''; ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label>Email</label>
            <input type="email" name="email" placeholder="correo@ejemplo.com"
                   value="<?php echo $proveedorEditar ? htmlspecialchars($proveedorEditar['email']) : ''; ?>">
          </div>

          <div class="form-field">
            <label>Teléfono</label>
            <input type="tel" name="telefono" placeholder="+1 (555) 123-4567"
                   value="<?php echo $proveedorEditar ? htmlspecialchars($proveedorEditar['telefono']) : ''; ?>">
          </div>
        </div>

        <div class="form-field">
          <label>Dirección</label>
          <input type="text" name="direccion" placeholder="Dirección completa"
                 value="<?php echo $proveedorEditar ? htmlspecialchars($proveedorEditar['direccion']) : ''; ?>">
        </div>

        <div class="form-field">
          <label>País</label>
          <input type="text" name="pais" placeholder="País"
                 value="<?php echo $proveedorEditar ? htmlspecialchars($proveedorEditar['pais']) : ''; ?>">
        </div>

        <div class="form-field">
          <label>Notas</label>
          <textarea name="notas" placeholder="Notas adicionales sobre el proveedor" rows="3"><?php echo $proveedorEditar ? htmlspecialchars($proveedorEditar['notas']) : ''; ?></textarea>
        </div>

        <div class="form-buttons">
          <?php if ($proveedorEditar): ?>
            <button type="submit" name="actualizar">Actualizar Proveedor</button>
            <a href="providers.php" class="btn-cancelar">Cancelar</a>
          <?php else: ?>
            <button type="submit" name="guardar">Guardar Proveedor</button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="lista-section">
      <h2>Lista de Proveedores</h2>
      <div class="proveedores-lista">
        <?php if ($proveedores->num_rows > 0): ?>
          <?php while ($p = $proveedores->fetch_assoc()): ?>
            <div class="proveedor-card">
              <div class="proveedor-header">
                <h3><?php echo htmlspecialchars($p['nombre']); ?></h3>
                <?php if ($p['empresa']): ?>
                  <span class="empresa-badge"><?php echo htmlspecialchars($p['empresa']); ?></span>
                <?php endif; ?>
              </div>

              <div class="proveedor-info">
                <?php if ($p['email']): ?>
                  <div class="info-item">
                    <span class="info-label">📧</span>
                    <span><?php echo htmlspecialchars($p['email']); ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($p['telefono']): ?>
                  <div class="info-item">
                    <span class="info-label">📱</span>
                    <span><?php echo htmlspecialchars($p['telefono']); ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($p['direccion']): ?>
                  <div class="info-item">
                    <span class="info-label">📍</span>
                    <span><?php echo htmlspecialchars($p['direccion']); ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($p['pais']): ?>
                  <div class="info-item">
                    <span class="info-label">🌍</span>
                    <span><?php echo htmlspecialchars($p['pais']); ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($p['notas']): ?>
                  <div class="info-item notas">
                    <span class="info-label">📝</span>
                    <span><?php echo htmlspecialchars($p['notas']); ?></span>
                  </div>
                <?php endif; ?>
              </div>

              <div class="proveedor-stats">
                <div class="stat">
                  <span class="stat-value"><?php echo $p['total_productos']; ?></span>
                  <span class="stat-label">Productos</span>
                </div>
                <div class="stat">
                  <span class="stat-value"><?php echo $p['total_stock'] ?? 0; ?></span>
                  <span class="stat-label">Stock Total</span>
                </div>
              </div>

              <div class="acciones">
                <a href="providers.php?editar=<?php echo $p['id']; ?>" class="btn-editar">Editar</a>
                <a href="providers.php?eliminar=<?php echo $p['id']; ?>"
                   class="btn-eliminar"
                   onclick="return confirm('¿Estás seguro de eliminar este proveedor? Los productos asociados no se eliminarán.')">Eliminar</a>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="empty-state">
            No hay proveedores registrados aún. ¡Agrega tu primer proveedor!
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
