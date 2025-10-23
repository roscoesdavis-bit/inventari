<?php include('includes/db.php'); ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Usuario</title>
  <link rel="stylesheet" href="assets/css/registro.css">
</head>
<body>
  <div class="container">
    <h2>Registro de Usuario</h2>
    <form method="POST" action="">
      <input type="text" name="nombre" placeholder="Nombre completo" required>
      <input type="email" name="email" placeholder="Correo electrónico" required>
      <input type="password" name="contraseña" placeholder="Contraseña" required>

      <h3>Preguntas de seguridad</h3>
      <input type="text" name="pregunta1" placeholder="Pregunta 1" required>
      <input type="text" name="respuesta1" placeholder="Respuesta 1" required>
      <input type="text" name="pregunta2" placeholder="Pregunta 2" required>
      <input type="text" name="respuesta2" placeholder="Respuesta 2" required>
      <input type="text" name="pregunta3" placeholder="Pregunta 3" required>
      <input type="text" name="respuesta3" placeholder="Respuesta 3" required>

      <button type="submit" name="registrar"><span>Registrarse</span></button>
    </form>

    <?php
    if (isset($_POST['registrar'])) {
      $nombre = $_POST['nombre'];
      $email = $_POST['email'];
      $contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);

      $pregunta1 = $_POST['pregunta1'];
      $respuesta1 = password_hash($_POST['respuesta1'], PASSWORD_DEFAULT);
      $pregunta2 = $_POST['pregunta2'];
      $respuesta2 = password_hash($_POST['respuesta2'], PASSWORD_DEFAULT);
      $pregunta3 = $_POST['pregunta3'];
      $respuesta3 = password_hash($_POST['respuesta3'], PASSWORD_DEFAULT);

      $sql = "INSERT INTO usuarios (nombre, email, contraseña, pregunta_seguridad_1, respuesta_1, pregunta_seguridad_2, respuesta_2, pregunta_seguridad_3, respuesta_3)
              VALUES ('$nombre', '$email', '$contraseña', '$pregunta1', '$respuesta1', '$pregunta2', '$respuesta2', '$pregunta3', '$respuesta3')";

      if ($conn->query($sql) === TRUE) {
        echo "<div class='success'>Registro exitoso. <a href='login.php'>Inicia sesión aquí</a></div>";
      } else {
        echo "<div class='error'>Error: " . $conn->error . "</div>";
      }
    }
    ?>

    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
  </div>
</body>
</html>
