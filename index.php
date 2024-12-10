<?php
session_start();
require_once("controladores/login.php");
use Medoo\Medoo;
if (!isset($_SESSION['usuario']))
{
  if (isset($_COOKIE['recuerdame_alkes'])) {
    $database = new Medoo();
    $token = $_COOKIE['recuerdame_alkes'];

    $usuario = $database->select("usuarios", [
        "id"
    ], [
        "token_recuerdame" => $token
    ]);

    if (!empty($usuario)) {
        $_SESSION['idusuario'] = $usuario[0]['id'];
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
  <link href="https://fonts.googleapis.com/css?family=Karla:400,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.materialdesignicons.com/4.8.95/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/css/login.css">
  <?php
  echo $jaxon->getScript(true); // Cambiado para Jaxon 4.x
  ?>
</head>
<body>
  <main class="d-flex align-items-center min-vh-100 py-3 py-md-0">
    <div class="container">
      <div class="card login-card">
        <div class="row no-gutters">
          <div class="col-md-5">
            <img src="assets/images/login.jpg" alt="login" class="login-card-img">
          </div>
          <div class="col-md-7">
            <div class="card-body">
              <div class="brand-wrapper">
                <img src="assets/images/logoSimple.svg" alt="logo" class="logo">
              </div>
              <p class="login-card-description">Iniciar sesión</p>
              <form id="login" method="post" onsubmit="return jaxon_login(jaxon.getFormValues('login'));">
                <div class="form-group">
                  <label for="user" class="sr-only">Usuario</label>
                  <input type="text" name="user" id="user" class="form-control" placeholder="Usuario" required>
                </div>
                <div class="form-group mb-4">
                  <label for="clave" class="sr-only">Contraseña</label>
                  <input type="password" name="clave" id="password-field" class="form-control" placeholder="Contraseña" required>
                  <span toggle="#password-field" class="fa fa-fw fa-eye field-icon toggle-password"></span>
                </div>
                <div class="form-group">
                  <button type="submit" class="btn btn-block login-btn mb-4">Iniciar sesión</button>
                </div>
                <div class="form-group d-md-flex">
                  <div class="w-50">
                    <label class="checkbox-wrap checkbox-primary">Recuerdame
                      <input type="checkbox" name="rememberPasswordCheck" checked>
                      <span class="checkmark"></span>
                    </label>
                  </div>
                  <div class="w-50 text-md-right">
                    <a href="#" class="forgot-password-link">Olvidé mi contraseña</a>
                  </div>
                </div>
              </form>
              <nav class="login-card-footer-nav">
                <a href="#!">Términos de uso · </a>
                <a href="#!">Política de privacidad</a>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
  <!-- Custom JS -->
  <script src="/assets/js/login_js/main.js"></script>
   <!-- jQuery -->
   <script src="/plugins/jquery/jquery.min.js"></script>
  <!-- Bootstrap 4 -->
  <script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <!-- AdminLTE App -->
  <script src="/dist/js/adminlte.min.js"></script>
  <link rel="stylesheet" href="/plugins/sweetalert2/sweetalert2.min.css">
  <script src="/plugins/sweetalert2/sweetalert2.min.js"></script>
  <!-- Jaxon -->
  <script>
    $("#login").submit(function(e){
        return false;
    });
  </script>
</body>
</html>
