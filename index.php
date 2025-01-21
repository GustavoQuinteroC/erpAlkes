<?php
session_start();
require_once("controladores/login.php");
if (!isset($_SESSION['usuario'])) {
  if (isset($_COOKIE['recuerdame_alkes'])) {
    global $database;
    $token = $_COOKIE['recuerdame_alkes'];

    // Realizamos la consulta unificada
    $usuario = $database->select("usuarios", [
      "[>]entidades" => ["identidad" => "id"], // Unión con entidades
      "[>]empresas" => ["entidades.idempresa" => "id"] // Unión con empresas
    ], [
      "usuarios.id(idusuario)",
      "usuarios.identidad",
      "empresas.id(empresa_id)"
    ], [
      "usuarios.token_recuerdame" => $token
    ]);

    if (!empty($usuario)) {
      // Asignamos las variables de sesión
      $_SESSION['idusuario'] = $usuario[0]['idusuario'];
      $_SESSION['identidad'] = $usuario[0]['identidad'];
      $_SESSION['idempresa'] = $usuario[0]['empresa_id'];

      // Redirigir al inicio
      header("Location: vistas/inicio/index.php");
      exit;
    }
  }
}




?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Alkes - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/login_styles.css">
  <?php
  echo $jaxon->getScript(true); // Cambiado para Jaxon 4.x
  ?>
</head>

<body>
  <div class="login">
    <img src="assets/images/login-bg.png" alt="login image" class="login__img">
    <form id="login" method="post" autocomplete="off" onsubmit="return jaxon_login(jaxon.getFormValues('login'));" class="login__form">
      <h1 class="login__title">ERP ALKES</h1>

      <div class="login__content">
        <div class="login__box">
          <i class="ri-user-3-line login__icon"></i>

          <div class="login__box-input">
            <input type="text" name="user" class="login__input" id="user" placeholder=" " autocomplete="new-username">
            <label for="user" class="login__label">Usuario</label>
          </div>
        </div>

        <div class="login__box">
          <i class="ri-lock-2-line login__icon"></i>

          <div class="login__box-input">
            <input type="password" name="clave" class="login__input" id="password-field" placeholder=" " autocomplete="new-password">
            <label for="password-field" class="login__label">Contraseña</label>
            <i class="ri-eye-off-line login__eye" id="login-eye"></i>
          </div>
        </div>
      </div>

      <div class="login__check">
        <div class="login__check-group">
          <input type="checkbox" class="login__check-input" id="rememberPasswordCheck" name="rememberPasswordCheck" checked>
          <label for="login-check" class="login__check-label">Recuerdame</label>
        </div>

        <a href="#" class="login__forgot">Olvide mi contraseña</a>
      </div>

      <button type="submit" class="login__button">Iniciar sesión</button>

      <p class="login__register">
        Necesitas una prueba para tu negocio? <a href="#">Registrate</a>
      </p>
      
    </form>
  </div>

  <!-- Custom JS -->
  <script src="/assets/js/login_js/main.js"></script>
  <!-- jQuery -->
  <script src="/plugins/jquery/jquery.min.js"></script>
  <!-- AdminLTE App -->
  <script src="/dist/js/adminlte.min.js"></script>
  <link rel="stylesheet" href="/plugins/sweetalert2/sweetalert2.min.css">
  <script src="/plugins/sweetalert2/sweetalert2.min.js"></script>
  <!-- Jaxon -->
  <script>
    $("#login").submit(function (e) {
      return false;
    });
  </script>
</body>

</html>