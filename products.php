<?php
include('includes/auth.php');
include('includes/db.php');

// Eliminar producto
if (isset($_GET['eliminar'])) {
  $id = intval($_GET['eliminar']);
  $conn->query("DELETE FROM productos WHERE id = $id");
  header('Location: products.php');
  exit;
}

// Actualizar producto
if (isset($_POST['actualizar'])) {
  $id = intval($_POST['id']);
  $marca = $_POST['marca'];
  $descripcion = $_POST['descripcion'];
  $precio = $_POST['precio'];
  $precio_descuento = !empty($_POST['precio_descuento']) ? $_POST['precio_descuento'] : 'NULL';
  $categoria = $_POST['categoria'];
  $stock = $_POST['stock'];
  $origen = $_POST['origen'];
  $proveedor_id = (!empty($_POST['proveedor_id']) && $_POST['origen'] == 'comprado') ? intval($_POST['proveedor_id']) : 'NULL';
  $imagen_sql = '';
if (!empty($_FILES['imagen']['name'])) {
  $imagen_nombre = time() . '_' . basename($_FILES['imagen']['name']);
  $ruta_destino = 'uploads/' . $imagen_nombre;
  if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
    $imagen_sql = ", imagen = '$imagen_nombre'";
  }
}

  $sql = "UPDATE productos SET
        marca = '$marca',
        descripcion = '$descripcion',
        precio = '$precio',
        precio_descuento = $precio_descuento,
        categoria = '$categoria',
        stock = '$stock',
        origen = '$origen',
        proveedor_id = $proveedor_id
        $imagen_sql
        WHERE id = $id";
  $conn->query($sql);
  header('Location: products.php');
  exit;
}

// Guardar nuevo producto
if (isset($_POST['guardar'])) {
  $marca = $_POST['marca'];
  $descripcion = $_POST['descripcion'];
  $precio = $_POST['precio'];
  $precio_descuento = !empty($_POST['precio_descuento']) ? $_POST['precio_descuento'] : 'NULL';
  $categoria = $_POST['categoria'];
  $stock = $_POST['stock'];
  $origen = $_POST['origen'];
  $proveedor_id = (!empty($_POST['proveedor_id']) && $_POST['origen'] == 'comprado') ? intval($_POST['proveedor_id']) : 'NULL';
  $imagen_nombre = null;
if (!empty($_FILES['imagen']['name'])) {
  $imagen_nombre = time() . '_' . basename($_FILES['imagen']['name']);
  $ruta_destino = 'uploads/' . $imagen_nombre;
  move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino);
}

  $sql = "INSERT INTO productos (marca, descripcion, precio, precio_descuento, categoria, stock, origen, proveedor_id, imagen)
        VALUES ('$marca', '$descripcion', '$precio', $precio_descuento, '$categoria', '$stock', '$origen', $proveedor_id, " . ($imagen_nombre ? "'$imagen_nombre'" : "NULL") . ")";
  $conn->query($sql);
  header('Location: products.php');
  exit;
}

// Obtener producto a editar
$productoEditar = null;
if (isset($_GET['editar'])) {
  $id = intval($_GET['editar']);
  $result = $conn->query("SELECT * FROM productos WHERE id = $id");
  $productoEditar = $result->fetch_assoc();
}

// Obtener proveedores
$proveedores = $conn->query("SELECT * FROM proveedores ORDER BY nombre ASC");

// Obtener productos
$productos = $conn->query("SELECT p.*, prov.nombre as proveedor_nombre, prov.empresa as proveedor_empresa FROM productos p LEFT JOIN proveedores prov ON p.proveedor_id = prov.id ORDER BY p.marca, p.precio ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de productos - Omega Cars</title>
  <link rel="stylesheet" href="assets/css/products.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h2>Gestión de Productos</h2>
      <a href="dashboard.php" class="back-link">← Volver al Dashboard</a>
    </div>

    <div class="form-section">
      <h2><?php echo $productoEditar ? 'Editar Producto' : 'Agregar Nuevo Producto'; ?></h2>
      <form method="POST" enctype="multipart/form-data">

        <?php if ($productoEditar): ?>
          <input type="hidden" name="id" value="<?php echo $productoEditar['id']; ?>">
        <?php endif; ?>

        <input type="text" name="marca" placeholder="Marca" required
               value="<?php echo $productoEditar ? htmlspecialchars($productoEditar['marca']) : ''; ?>">

        <input type="text" name="descripcion" placeholder="Descripción" required
               value="<?php echo $productoEditar ? htmlspecialchars($productoEditar['descripcion']) : ''; ?>">

        <div class="precio-grid">
          <div class="precio-field">
            <label>Precio Original</label>
            <input type="number" step="0.01" name="precio" placeholder="Precio original" required
                   value="<?php echo $productoEditar ? $productoEditar['precio'] : ''; ?>">
          </div>

          <div class="precio-field">
            <label>Precio con Descuento (opcional)</label>
            <input type="number" step="0.01" name="precio_descuento" placeholder="Precio con descuento"
                   value="<?php echo $productoEditar && $productoEditar['precio_descuento'] ? $productoEditar['precio_descuento'] : ''; ?>">
          </div>
        </div>

        <input type="text" name="categoria" placeholder="Categoría (opcional)"
               value="<?php echo $productoEditar ? htmlspecialchars($productoEditar['categoria']) : ''; ?>">

        <input type="number" name="stock" placeholder="Stock (opcional)"
               value="<?php echo $productoEditar ? $productoEditar['stock'] : ''; ?>">

        <div class="form-field">
          <label>Origen del Producto</label>
          <div class="radio-group">
            <label class="radio-label">
              <input type="radio" name="origen" value="comprado" required
                     <?php echo (!$productoEditar || $productoEditar['origen'] == 'comprado') ? 'checked' : ''; ?>>
              Comprado
            </label>
            <label class="radio-label">
              <input type="radio" name="origen" value="fabricado"
                     <?php echo ($productoEditar && $productoEditar['origen'] == 'fabricado') ? 'checked' : ''; ?>>
              Fabricado
            </label>
          </div>
        </div>

        <div class="form-field" id="proveedor-field">
          <label>Proveedor (solo para productos comprados)</label>
          <select name="proveedor_id">
            <option value="">Ninguno</option>
            <?php
            if ($proveedores):
              while ($prov = $proveedores->fetch_assoc()):
            ?>
              <option value="<?php echo $prov['id']; ?>"
                      <?php echo ($productoEditar && $productoEditar['proveedor_id'] == $prov['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($prov['nombre']) . ($prov['empresa'] ? ' - ' . htmlspecialchars($prov['empresa']) : ''); ?>
              </option>
            <?php
              endwhile;
            endif;
            ?>
          </select>
        </div>
        <div class="form-field">
            <label>Imagen del producto</label>
            <input type="file" name="imagen" accept="image/*">
        </div>
        <div class="form-buttons">
          <?php if ($productoEditar): ?>
            <button type="submit" name="actualizar">Actualizar Producto</button>
            <a href="products.php" class="btn-cancelar">Cancelar</a>
          <?php else: ?>
            <button type="submit" name="guardar">Guardar Producto</button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="catalogo-section">
      <h2>Catálogo de Productos</h2>
      <div class="catalogo">
        <?php if ($productos->num_rows > 0): ?>
          <?php while ($p = $productos->fetch_assoc()): ?>
            <div class="tarjeta">
              <h3><?php echo htmlspecialchars($p['marca']); ?></h3>
              <p><?php echo htmlspecialchars($p['descripcion']); ?></p>
              <?php if (!empty($p['imagen'])): ?>
  <img src="uploads/<?php echo htmlspecialchars($p['imagen']); ?>" alt="Imagen de <?php echo htmlspecialchars($p['marca']); ?>" class="producto-imagen">
<?php endif; ?>

              
              <div class="precios-container">
                <?php if ($p['precio_descuento']): ?>
                  <p class="precio-original">$<?php echo number_format($p['precio'], 2); ?></p>
                  <p class="precio-descuento">$<?php echo number_format($p['precio_descuento'], 2); ?></p>
                  <?php
                    $descuento_porcentaje = (($p['precio'] - $p['precio_descuento']) / $p['precio']) * 100;
                  ?>
                  <span class="etiqueta-descuento">-<?php echo round($descuento_porcentaje); ?>%</span>
                <?php else: ?>
                  <p class="precio">$<?php echo number_format($p['precio'], 2); ?></p>
                <?php endif; ?>
              </div>

              <?php if ($p['categoria']): ?>
                <p><strong>Categoría:</strong> <?php echo htmlspecialchars($p['categoria']); ?></p>
              <?php endif; ?>
              <?php if ($p['stock'] !== null): ?>
                <p><strong>Stock:</strong> <?php echo $p['stock']; ?> unidades</p>
              <?php endif; ?>
              <p><strong>Origen:</strong> <?php echo ucfirst($p['origen']); ?></p>
              <?php if ($p['proveedor_nombre']): ?>
                <p><strong>Proveedor:</strong> <?php echo htmlspecialchars($p['proveedor_nombre']); ?><?php echo $p['proveedor_empresa'] ? ' - ' . htmlspecialchars($p['proveedor_empresa']) : ''; ?></p>
              <?php endif; ?>

              <div class="acciones">
                <a href="products.php?editar=<?php echo $p['id']; ?>" class="btn-editar">Editar</a>
                <a href="products.php?eliminar=<?php echo $p['id']; ?>"
                   class="btn-eliminar"
                   onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</a>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="empty-state">
            No hay productos registrados aún. ¡Agrega tu primer producto!
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
