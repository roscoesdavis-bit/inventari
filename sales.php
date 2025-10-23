<?php
include('includes/auth.php');
include('includes/db.php');
require('includes/fpdf.php');

// Obtener productos para el desplegable
$productos = $conn->query("SELECT * FROM productos ORDER BY marca");

// Registrar venta
if (isset($_POST['vender'])) {
  $cliente_nombre = $_POST['cliente_nombre'];
  $cliente_email = $_POST['cliente_email'];
  $cliente_telefono = $_POST['cliente_telefono'];
  $productos_venta = json_decode($_POST['productos_json'], true);
  $total_general = floatval($_POST['total_general']);

  // Insertar venta principal
  $sql = "INSERT INTO ventas (cliente_nombre, cliente_email, cliente_telefono, total)
          VALUES ('$cliente_nombre', '$cliente_email', '$cliente_telefono', $total_general)";
  $conn->query($sql);
  $venta_id = $conn->insert_id;

  // Insertar detalle de productos
  foreach ($productos_venta as $item) {
    $producto_id = intval($item['producto_id']);
    $cantidad = intval($item['cantidad']);
    $precio_unitario = floatval($item['precio']);
    $subtotal = $precio_unitario * $cantidad;

    $sql_detalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal)
                    VALUES ($venta_id, $producto_id, $cantidad, $precio_unitario, $subtotal)";
    $conn->query($sql_detalle);

    // Actualizar stock
    $conn->query("UPDATE productos SET stock = stock - $cantidad WHERE id = $producto_id");
  }

  // Generar PDF
  $pdf = new FPDF();
  $pdf->AddPage();

  // Encabezado
  $pdf->SetFont('Arial','B',18);
  $pdf->SetTextColor(255, 107, 53);
  $pdf->Cell(0,10,'OMEGA CARS BOUTIQUE',0,1,'C');
  $pdf->SetFont('Arial','',10);
  $pdf->SetTextColor(0, 0, 0);
  $pdf->Cell(0,5,'Factura de Venta',0,1,'C');
  $pdf->Ln(5);

  // Información de venta
  $pdf->SetFont('Arial','B',11);
  $pdf->Cell(0,6,'DATOS DEL CLIENTE',0,1);
  $pdf->SetFont('Arial','',10);
  $pdf->Cell(40,6,'Nombre:',0,0);
  $pdf->Cell(0,6,$cliente_nombre,0,1);
  $pdf->Cell(40,6,'Email:',0,0);
  $pdf->Cell(0,6,$cliente_email,0,1);
  $pdf->Cell(40,6,'Telefono:',0,0);
  $pdf->Cell(0,6,$cliente_telefono,0,1);
  $pdf->Cell(40,6,'Fecha:',0,0);
  $pdf->Cell(0,6,date("d/m/Y H:i"),0,1);
  $pdf->Cell(40,6,'No. Factura:',0,0);
  $pdf->Cell(0,6,'#' . str_pad($venta_id, 6, '0', STR_PAD_LEFT),0,1);
  $pdf->Ln(5);

  // Tabla de productos
  $pdf->SetFont('Arial','B',11);
  $pdf->Cell(0,6,'PRODUCTOS',0,1);
  $pdf->SetFont('Arial','B',9);
  $pdf->SetFillColor(255, 107, 53);
  $pdf->SetTextColor(255, 255, 255);
  $pdf->Cell(70,8,'Producto',1,0,'C',true);
  $pdf->Cell(25,8,'P. Unit.',1,0,'C',true);
  $pdf->Cell(15,8,'Cant.',1,0,'C',true);
  $pdf->Cell(25,8,'Subtotal',1,1,'C',true);

  $pdf->SetFont('Arial','',9);
  $pdf->SetTextColor(0, 0, 0);

  $ahorro_total = 0;

  foreach ($productos_venta as $item) {
    $nombre_corto = substr($item['nombre'], 0, 35);
    $tiene_descuento = isset($item['tiene_descuento']) && $item['tiene_descuento'];

    $pdf->Cell(70,7,$nombre_corto,1,0);

    if ($tiene_descuento && isset($item['precio_original'])) {
      // Mostrar precio con descuento
      $pdf->SetTextColor(150, 150, 150);
      $pdf->Cell(25,7,'$' . number_format($item['precio_original'], 2),1,0,'R');
      $pdf->SetTextColor(0, 0, 0);

      $ahorro = ($item['precio_original'] - $item['precio']) * $item['cantidad'];
      $ahorro_total += $ahorro;
    } else {
      $pdf->Cell(25,7,'$' . number_format($item['precio'], 2),1,0,'R');
    }

    $pdf->Cell(15,7,$item['cantidad'],1,0,'C');
    $pdf->Cell(25,7,'$' . number_format($item['precio'] * $item['cantidad'], 2),1,1,'R');

    // Si hay descuento, agregar una línea adicional
    if ($tiene_descuento && isset($item['precio_original'])) {
      $pdf->SetFont('Arial','I',8);
      $pdf->SetTextColor(255, 107, 53);
      $pdf->Cell(70,5,'  Precio con descuento',0,0);
      $pdf->Cell(25,5,'$' . number_format($item['precio'], 2),0,0,'R');
      $pdf->Cell(15,5,'',0,0);
      $descuento_porcentaje = (($item['precio_original'] - $item['precio']) / $item['precio_original']) * 100;
      $pdf->Cell(25,5,'-' . round($descuento_porcentaje) . '%',0,1,'R');
      $pdf->SetFont('Arial','',9);
      $pdf->SetTextColor(0, 0, 0);
    }
  }

  // Ahorro total si aplica
  if ($ahorro_total > 0) {
    $pdf->SetFont('Arial','B',10);
    $pdf->SetTextColor(34, 139, 34);
    $pdf->Cell(110,7,'AHORRO TOTAL',1,0,'R');
    $pdf->Cell(25,7,'-$' . number_format($ahorro_total, 2),1,1,'R');
    $pdf->SetTextColor(0, 0, 0);
  }

  // Total
  $pdf->SetFont('Arial','B',11);
  $pdf->Cell(110,8,'TOTAL A PAGAR',1,0,'R');
  $pdf->SetTextColor(255, 107, 53);
  $pdf->Cell(25,8,'$' . number_format($total_general, 2),1,1,'R');

  $pdf->Ln(5);
  $pdf->SetFont('Arial','I',9);
  $pdf->SetTextColor(100, 100, 100);
  $pdf->Cell(0,5,'Gracias por su compra',0,1,'C');

  // Guardar y descargar PDF
  if (!file_exists('comprobantes')) {
    mkdir('comprobantes', 0777, true);
  }
  $archivo = "factura_" . $venta_id . "_" . time() . ".pdf";
  $pdf->Output("F", "comprobantes/$archivo");
  $pdf->Output("D", $archivo);
  exit();
}

// Obtener productos para JavaScript
$productos_array = [];
$productos_query = $conn->query("SELECT * FROM productos ORDER BY marca");
while ($p = $productos_query->fetch_assoc()) {
  $productos_array[] = $p;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar venta - Omega Cars</title>
  <link rel="stylesheet" href="assets/css/sales.css">
  <style>
    .price-selection {
      margin: 15px 0;
      padding: 15px;
      background: white;
      border-radius: 10px;
      border: 2px solid #e0e0e0;
      display: none;
    }

    .price-selection.active {
      display: block;
    }

    .price-selection h4 {
      color: #1a1a1a;
      font-size: 1em;
      margin-bottom: 12px;
      font-weight: 600;
    }

    .price-options {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .price-option {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      background: #f8f8f8;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .price-option:hover {
      border-color: #ff6b35;
      background: white;
    }

    .price-option input[type="radio"] {
      width: 20px;
      height: 20px;
      margin-right: 12px;
      cursor: pointer;
      accent-color: #ff6b35;
    }

    .price-option label {
      flex: 1;
      cursor: pointer;
      font-size: 15px;
      color: #333;
      font-weight: 500;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .price-option.selected {
      border-color: #ff6b35;
      background: linear-gradient(135deg, rgba(255, 107, 53, 0.05), rgba(247, 147, 30, 0.05));
    }

    .price-badge {
      background: linear-gradient(135deg, #ff6b35, #f7931e);
      color: white;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.75em;
      font-weight: 700;
      margin-left: 8px;
    }

    .price-normal {
      color: #666;
    }

    .price-discount {
      color: #ff6b35;
      font-weight: 700;
    }

    .savings-text {
      color: #4CAF50;
      font-size: 0.85em;
      margin-left: 8px;
    }

    .original-price {
      text-decoration: line-through;
      color: #999;
      font-size: 0.9em;
      margin-right: 8px;
    }

    .stock-warning {
      color: #f44336;
      font-size: 0.85em;
      margin-top: 5px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2>Registrar Nueva Venta</h2>
      <a href="dashboard.php" class="back-link">← Volver al Dashboard</a>
    </div>

    <div class="form-section">
      <h3>Datos del Cliente</h3>
      <form method="POST" id="ventaForm">
        <input type="text" name="cliente_nombre" id="cliente_nombre" placeholder="Nombre del cliente" required>
        <input type="email" name="cliente_email" id="cliente_email" placeholder="Correo del cliente" required>
        <input type="text" name="cliente_telefono" id="cliente_telefono" placeholder="Teléfono del cliente" required>

        <h3 style="margin-top: 25px;">Agregar Productos</h3>
        <div class="producto-selector">
          <select id="producto_select">
            <option value="">Seleccionar producto</option>
            <?php
            $productos_lista = $conn->query("SELECT * FROM productos ORDER BY marca");
            while ($p = $productos_lista->fetch_assoc()):
              $tiene_descuento = $p['precio_descuento'] ? true : false;
            ?>
              <option value="<?php echo $p['id']; ?>"
                      data-nombre="<?php echo htmlspecialchars($p['marca'] . ' - ' . $p['descripcion']); ?>"
                      data-precio="<?php echo $p['precio']; ?>"
                      data-precio-descuento="<?php echo $p['precio_descuento'] ?: '0'; ?>"
                      data-tiene-descuento="<?php echo $tiene_descuento ? '1' : '0'; ?>"
                      data-stock="<?php echo $p['stock']; ?>">
                <?php
                  echo htmlspecialchars($p['marca']) . " - " . htmlspecialchars($p['descripcion']);
                  if ($tiene_descuento) {
                    echo " - Stock: " . $p['stock'] . " - OFERTA disponible";
                  } else {
                    echo " - Stock: " . $p['stock'];
                  }
                ?>
              </option>
            <?php endwhile; ?>
          </select>

          <!-- Sección de selección de precio -->
          <div class="price-selection" id="priceSelection">
            <h4>Selecciona el precio de venta:</h4>
            <div class="price-options">
              <div class="price-option" id="priceOptionNormal" onclick="selectPriceOption('normal')">
                <input type="radio" name="price_type" id="price_normal" value="normal" checked>
                <label for="price_normal">
                  <span>Precio Normal</span>
                  <span class="price-normal" id="priceNormalValue">$0.00</span>
                </label>
              </div>
              <div class="price-option" id="priceOptionDiscount" onclick="selectPriceOption('discount')">
                <input type="radio" name="price_type" id="price_discount" value="discount">
                <label for="price_discount">
                  <span>
                    Precio con Descuento
                    <span class="price-badge">OFERTA</span>
                  </span>
                  <span>
                    <span class="original-price" id="priceOriginalStrike">$0.00</span>
                    <span class="price-discount" id="priceDiscountValue">$0.00</span>
                    <span class="savings-text" id="savingsText"></span>
                  </span>
                </label>
              </div>
            </div>
          </div>

          <div class="cantidad-wrapper">
            <input type="number" id="cantidad_input" placeholder="Cantidad" min="1" value="1">
            <button type="button" class="btn-agregar" onclick="agregarProducto()">+ Agregar al Carrito</button>
          </div>
        </div>

        <div class="carrito-section" id="carritoSection" style="display: none;">
          <h3>Carrito de Compra</h3>
          <div class="carrito-items" id="carritoItems"></div>
          <div class="total-section">
            <h3>Total: $<span id="totalGeneral">0.00</span></h3>
          </div>
        </div>

        <input type="hidden" name="productos_json" id="productos_json">
        <input type="hidden" name="total_general" id="total_general_input">

        <button type="submit" name="vender" id="btnVender" disabled>
          <span>Generar Factura PDF</span>
        </button>
      </form>
    </div>
  </div>

  <script>
    let carrito = [];
    const productos = <?php echo json_encode($productos_array); ?>;
    let selectedProduct = null;

    // Manejar selección de producto
    document.getElementById('producto_select').addEventListener('change', function() {
      const select = this;
      const priceSelection = document.getElementById('priceSelection');

      if (!select.value) {
        priceSelection.classList.remove('active');
        selectedProduct = null;
        return;
      }

      const option = select.options[select.selectedIndex];
      const precioNormal = parseFloat(option.dataset.precio);
      const precioDescuento = parseFloat(option.dataset.precioDescuento);
      const tieneDescuento = option.dataset.tieneDescuento === '1';
      const stock = parseInt(option.dataset.stock);

      selectedProduct = {
        id: select.value,
        nombre: option.dataset.nombre,
        precioNormal: precioNormal,
        precioDescuento: precioDescuento,
        tieneDescuento: tieneDescuento,
        stock: stock
      };

      // Mostrar opciones de precio
      document.getElementById('priceNormalValue').textContent = '$' + precioNormal.toFixed(2);

      if (tieneDescuento && precioDescuento > 0) {
        priceSelection.classList.add('active');
        document.getElementById('priceOriginalStrike').textContent = '$' + precioNormal.toFixed(2);
        document.getElementById('priceDiscountValue').textContent = '$' + precioDescuento.toFixed(2);

        const ahorro = precioNormal - precioDescuento;
        const porcentaje = ((ahorro / precioNormal) * 100).toFixed(0);
        document.getElementById('savingsText').textContent = '(Ahorras ' + porcentaje + '%)';

        // Mostrar opción de descuento
        document.getElementById('priceOptionDiscount').style.display = 'flex';

        // Resetear a precio normal por defecto
        document.getElementById('price_normal').checked = true;
        selectPriceOption('normal');
      } else {
        // Solo precio normal disponible
        priceSelection.classList.add('active');
        document.getElementById('priceOptionDiscount').style.display = 'none';
        document.getElementById('price_normal').checked = true;
        selectPriceOption('normal');
      }
    });

    function selectPriceOption(type) {
      document.querySelectorAll('.price-option').forEach(opt => {
        opt.classList.remove('selected');
      });

      if (type === 'normal') {
        document.getElementById('priceOptionNormal').classList.add('selected');
        document.getElementById('price_normal').checked = true;
      } else {
        document.getElementById('priceOptionDiscount').classList.add('selected');
        document.getElementById('price_discount').checked = true;
      }
    }

    function agregarProducto() {
      const select = document.getElementById('producto_select');
      const cantidadInput = document.getElementById('cantidad_input');

      if (!select.value) {
        alert('Por favor selecciona un producto');
        return;
      }

      const cantidad = parseInt(cantidadInput.value);
      if (cantidad < 1) {
        alert('La cantidad debe ser mayor a 0');
        return;
      }

      if (!selectedProduct) {
        alert('Error al seleccionar el producto');
        return;
      }

      if (cantidad > selectedProduct.stock) {
        alert('No hay suficiente stock. Stock disponible: ' + selectedProduct.stock);
        return;
      }

      // Determinar qué precio se seleccionó
      const usarDescuento = document.getElementById('price_discount').checked;
      const precioSeleccionado = usarDescuento ? selectedProduct.precioDescuento : selectedProduct.precioNormal;

      // Agregar al carrito
      carrito.push({
        producto_id: selectedProduct.id,
        nombre: selectedProduct.nombre,
        precio: precioSeleccionado,
        precio_original: selectedProduct.precioNormal,
        tiene_descuento: usarDescuento,
        cantidad: cantidad
      });

      actualizarCarrito();

      // Resetear formulario
      select.value = '';
      cantidadInput.value = 1;
      document.getElementById('priceSelection').classList.remove('active');
      selectedProduct = null;
    }

    function eliminarProducto(index) {
      carrito.splice(index, 1);
      actualizarCarrito();
    }

    function actualizarCarrito() {
      const carritoSection = document.getElementById('carritoSection');
      const carritoItems = document.getElementById('carritoItems');
      const totalGeneral = document.getElementById('totalGeneral');
      const btnVender = document.getElementById('btnVender');
      const productosJson = document.getElementById('productos_json');
      const totalInput = document.getElementById('total_general_input');

      if (carrito.length === 0) {
        carritoSection.style.display = 'none';
        btnVender.disabled = true;
        return;
      }

      carritoSection.style.display = 'block';
      btnVender.disabled = false;

      let html = '';
      let total = 0;

      carrito.forEach((item, index) => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;

        let precioHtml = '';
        if (item.tiene_descuento) {
          const ahorro = (item.precio_original - item.precio) * item.cantidad;
          const porcentaje = ((item.precio_original - item.precio) / item.precio_original * 100).toFixed(0);

          precioHtml = `
            <p style="margin: 4px 0;">
              <span style="text-decoration: line-through; color: #999; font-size: 0.9em;">Precio normal: $${item.precio_original.toFixed(2)}</span>
            </p>
            <p style="margin: 4px 0;">
              <span style="color: #ff6b35; font-weight: 700;">Precio con descuento: $${item.precio.toFixed(2)}</span>
              <span style="background: linear-gradient(135deg, #ff6b35, #f7931e); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75em; margin-left: 8px;">-${porcentaje}%</span>
            </p>
            <p style="margin: 4px 0; color: #4CAF50; font-size: 0.9em;">
              Ahorras: $${ahorro.toFixed(2)} en este producto
            </p>
          `;
        } else {
          precioHtml = `<p>Precio unitario: $${item.precio.toFixed(2)}</p>`;
        }

        html += `
          <div class="carrito-item">
            <div class="item-info">
              <h4>${item.nombre}</h4>
              ${precioHtml}
              <p>Cantidad: ${item.cantidad}</p>
            </div>
            <div class="item-total">
              <p class="subtotal">$${subtotal.toFixed(2)}</p>
              <button type="button" class="btn-eliminar-item" onclick="eliminarProducto(${index})">Eliminar</button>
            </div>
          </div>
        `;
      });

      carritoItems.innerHTML = html;
      totalGeneral.textContent = total.toFixed(2);
      productosJson.value = JSON.stringify(carrito);
      totalInput.value = total.toFixed(2);
    }
  </script>
</body>
</html>
