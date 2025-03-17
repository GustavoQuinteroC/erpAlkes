
<?php
session_start();
require_once("controladores/login.php");
if (!isset($_SESSION['usuario'])) {
  if (isset($_COOKIE['recuerdame_alkes'])) {
    global $database;
    $token = $_COOKIE['recuerdame_alkes'];

    // Realizamos la consulta unificada
    $usuario = $database->select("usuarios", [
      "[>]sucursales" => ["idsucursal" => "id"], // Unión con sucursales
      "[>]empresas" => ["sucursales.idempresa" => "id"] // Unión con empresas
    ], [
      "usuarios.id(idusuario)",
      "usuarios.idsucursal",
      "empresas.id(empresa_id)"
    ], [
      "usuarios.token_recuerdame" => $token
    ]);

    if (!empty($usuario)) {
      // Asignamos las variables de sesión
      $_SESSION['idusuario'] = $usuario[0]['idusuario'];
      $_SESSION['idsucursal'] = $usuario[0]['idsucursal'];
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
  <!-- Estilos del fondo animado -->
  <style>
    html, body {
      height: 100%;
      margin: 0;
      overflow: hidden;
      background-image: radial-gradient(ellipse at top, #080e21 0%, #1b2735 95%);
    }
    
    .star {
      width: 1px;
      height: 1px;
      background: transparent;
      position: absolute;
      box-shadow:
          123px 456px #fff,
          789px 321px #fff,
          564px 987px #fff;
      /* Agrega más estrellas manualmente si lo deseas */
      z-index: -1;
    }
    
    @keyframes meteor {
      0% {
        opacity: 1;
        margin-top: -300px;
        margin-right: -300px;
      }
      12% {
        opacity: 0;
      }
      15% {
        margin-top: 300px;
        margin-left: -600px;
        opacity: 0;
      }
      100% {
        opacity: 0;
      }
    }
    
    .meteor {
      position: absolute;
      width: 300px;
      height: 1px;
      transform: rotate(-45deg);
      background-image: linear-gradient(to right, #fff, rgba(255,255,255,0));
      animation: meteor 5s linear infinite;
      z-index: -1;
    }
    
    .meteor:before {
      content: "";
      position: absolute;
      width: 4px;
      height: 5px;
      border-radius: 50%;
      margin-top: -2px;
      background: rgba(255,255,255,0.7);
      box-shadow: 0 0 15px 3px #fff;
    }
  </style>
  <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="/assets/css/login.styles.css">
  <?php
  echo $jaxon->getScript(true); // Cambiado para Jaxon 4.x
  ?>
</head>

<body>
  <!-- Contenido del Login -->
  <section class="ftco-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 text-center mb-5">
          <h2 class="heading-section">ERP ALKES</h2>
        </div>
      </div>
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
          <div class="login-wrap p-0">
            <h3 class="mb-4 text-center">Iniciar Sesión</h3>
            <form id="login" method="post" autocomplete="off" onsubmit="return jaxon_login(jaxon.getFormValues('login'));" class="form-group">
              <div class="form-group">
                <input name="user" id="user" type="text" class="form-control" placeholder="Usuario" required>
              </div>
              <div class="form-group">
                <input name="clave" id="clave" type="password" class="form-control" placeholder="Contraseña" required>
                <span toggle="#clave" class="fa fa-fw fa-eye field-icon toggle-password"></span>
              </div>
              <div class="form-group">
                <button type="submit" class="form-control btn btn-primary submit px-3">Iniciar</button>
              </div>
              <div class="form-group d-md-flex">
                <div class="w-50">
                  <label class="checkbox-wrap checkbox-primary">Recuerdame
                    <input id="rememberPasswordCheck" name="rememberPasswordCheck" type="checkbox" checked>
                    <span class="checkmark"></span>
                  </label>
                </div>
                <div class="w-50 text-md-right">
                  <a href="#" style="color: #fff">Olvide mi contraseña</a>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- Elemento para las estrellas (se mantiene el div original) -->
  <div class="star"></div>

   <!-- Scripts del fondo animado -->
   <script>
    // Generar estrellas aleatorias
    const starContainer = document.querySelector(".star");
    for (let i = 0; i < 300; i++) {
      let star = document.createElement("div");
      star.style.position = "absolute";
      star.style.width = "1px";
      star.style.height = "1px";
      star.style.background = "#fff";
      star.style.left = Math.random() * window.innerWidth + "px";
      star.style.top = Math.random() * window.innerHeight + "px";
      star.style.zIndex = "-1";
      document.body.appendChild(star);
    }

    // Generar meteoros aleatorios
    for (let i = 0; i < 15; i++) {
      let meteor = document.createElement("div");
      meteor.className = "meteor";
      meteor.style.top = Math.random() * window.innerHeight * 0.8 + "px";
      meteor.style.left = Math.random() * 90 + "%";
      meteor.style.animationDuration = (Math.random() * 4 + 3) + "s";
      meteor.style.zIndex = "-1";
      document.body.appendChild(meteor);
    }
  </script>

  <!-- Scripts del Login -->
  <script src="/assets/js/login.jquery.min.js"></script>
  <script src="/assets/js/login.popper.js"></script>
  <script src="/assets/js/login.bootstrap.min.js"></script>
  <script src="/assets/js/login.main.js"></script>

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