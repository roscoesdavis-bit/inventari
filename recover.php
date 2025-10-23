<?php
session_start();
include('includes/db.php');

$etapa = 1;
$preguntas = ["", "", ""]; // Inicializa para evitar errores

if (isset($_POST['buscar'])) {
  $email = $_POST['email'];
  $sql = "SELECT * FROM usuarios WHERE email = '$email'";
  $resultado = $conn->query($sql);

  if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc();
    $_SESSION['recuperar_id'] = $usuario['id'];
    $_SESSION['recuperar_email'] = $usuario['email'];
    $_SESSION['respuesta_1'] = $usuario['respuesta_1'];
    $_SESSION['respuesta_2'] = $usuario['respuesta_2'];
    $_SESSION['respuesta_3'] = $usuario['respuesta_3'];
    $_SESSION['pregunta_1'] = $usuario['pregunta_seguridad_1'];
    $_SESSION['pregunta_2'] = $usuario['pregunta_seguridad_2'];
    $_SESSION['pregunta_3'] = $usuario['pregunta_seguridad_3'];
    $preguntas = [
      $_SESSION['pregunta_1'],
      $_SESSION['pregunta_2'],
      $_SESSION['pregunta_3']
    ];
    $etapa = 2;
  } else {
    $error = "Correo no encontrado.";
  }
}

if (isset($_POST['validar'])) {
  $r1 = $_POST['r1'];
  $r2 = $_POST['r2'];
  $r3 = $_POST['r3'];

  if (
    password_verify($r1, $_SESSION['respuesta_1']) &&
    password_verify($r2, $_SESSION['respuesta_2']) &&
    password_verify($r3, $_SESSION['respuesta_3'])
  ) {
    $etapa = 3;
  } else {
    $error = "Respuestas incorrectas. Intenta nuevamente.";
    $etapa = 2;
    $preguntas = [
      $_SESSION['pregunta_1'],
      $_SESSION['pregunta_2'],
      $_SESSION['pregunta_3']
    ];
  }
}

if (isset($_POST['actualizar'])) {
  $nueva = password_hash($_POST['nueva'], PASSWORD_DEFAULT);
  $id = $_SESSION['recuperar_id'];
  $sql = "UPDATE usuarios SET contraseña = '$nueva' WHERE id = $id";
  if ($conn->query($sql)) {
    session_destroy();
    header("Location: login.php");
    exit();
  } else {
    $error = "Error al actualizar la contraseña.";
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar contraseña - Omega Cars</title>
  <link rel="stylesheet" href="assets/css/recover.css">
</head>
<body>
  <div class="container">
    <h2>Recuperar contraseña</h2>

    <?php if ($etapa === 1): ?>
      <p class="stage-indicator">Paso 1 de 3: Buscar cuenta</p>
    <?php elseif ($etapa === 2): ?>
      <p class="stage-indicator">Paso 2 de 3: Responder preguntas de seguridad</p>
    <?php elseif ($etapa === 3): ?>
      <p class="stage-indicator">Paso 3 de 3: Crear nueva contraseña</p>
    <?php endif; ?>

    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

    <?php if ($etapa === 1): ?>
      <form method="POST">
        <input type="email" name="email" placeholder="Correo registrado" required>
        <button type="submit" name="buscar"><span>Buscar cuenta</span></button>
      </form>
      <p><a href="login.php">Volver al inicio de sesión</a></p>
    <?php elseif ($etapa === 2): ?>
      <form method="POST">
        <label class="question-label"><?php echo htmlspecialchars($preguntas[0]); ?></label>
        <input type="text" name="r1" placeholder="Respuesta 1" required>

        <label class="question-label"><?php echo htmlspecialchars($preguntas[1]); ?></label>
        <input type="text" name="r2" placeholder="Respuesta 2" required>

        <label class="question-label"><?php echo htmlspecialchars($preguntas[2]); ?></label>
        <input type="text" name="r3" placeholder="Respuesta 3" required>

        <button type="submit" name="validar"><span>Validar respuestas</span></button>
      </form>
    <?php elseif ($etapa === 3): ?>
      <form method="POST">
        <input type="password" name="nueva" placeholder="Nueva contraseña" required minlength="6">
        <button type="submit" name="actualizar"><span>Actualizar contraseña</span></button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
