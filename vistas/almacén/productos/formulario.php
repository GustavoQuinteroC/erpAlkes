<?php
session_start();

// Obtén la ruta actual dividida en segmentos
$ruta = explode(DIRECTORY_SEPARATOR, getcwd());

// Calcular nombres de módulos semidinámicamente
$modulo = $ruta[(count($ruta) - 2)];
$submodulo = $ruta[(count($ruta) - 1)];
$subsubmodulo = null;
$pathControlador = __DIR__ . "/../../../controladores/individuales/$modulo.$submodulo.php";

// Verifica si el archivo existe antes de incluirlo
if (file_exists($pathControlador)) {
    require_once($pathControlador);
    validarSesion();
    echo $jaxon->getScript(true);
} else {
    // Manejo de errores si el archivo controlador no existe
    die("No se encontró el archivo del controlador en: $pathControlador");
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= getBackground(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select2 con Tema Dinámico y Bootstrap</title>

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />

    <style>
        /* Estilo adicional para corregir bordes y colores */
        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(2.375rem + 2px);
            /* Ajuste para altura de Bootstrap */
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--bs-border-color, #ced4da);
            border-radius: var(--bs-border-radius, 0.375rem);
            background-color: var(--bs-body-bg, #ffffff);
            color: var(--bs-body-color, #212529);
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
        }

        /* Ajustes para el dropdown de opciones */
        .select2-container--bootstrap-5 .select2-dropdown {
            border-radius: var(--bs-border-radius, 0.375rem);
            border: 1px solid var(--bs-border-color, #ced4da);
            background-color: var(--bs-body-bg, #ffffff);
        }

        .select2-container--bootstrap-5 .select2-results__option {
            color: var(--bs-body-color, #212529);
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: var(--bs-primary-bg-subtle, #e9ecef);
            color: var(--bs-primary-color-emphasis, #0d6efd);
        }

        /* Tema oscuro */
        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection--single {
            border: 1px solid #495057;
            background-color: #343a40;
            color: #ffffff;
        }

        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option {
            background-color: #343a40;
            color: #ffffff;
        }

        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: #495057;
            color: #ffffff;
        }

        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-dropdown {
            border: 1px solid #6c757d;
            background-color: #343a40;
            color: #ffffff;
        }

        /* Ajustar el color del texto en el campo de búsqueda */
        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            background-color: #343a40;
            /* Fondo oscuro acorde al tema */
            color: #ffffff;
            /* Texto blanco para visibilidad */
            border: 1px solid #6c757d;
            /* Borde acorde al tema oscuro */
        }

        /* Ajustar el texto del elemento seleccionado */
        [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered {
            color: #ffffff;
            /* Texto blanco para el tema oscuro */
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">Select2 con Tema Dinámico y Bootstrap</h2>
        <form>
            <!-- Primer Select2 con nombres mexicanos -->
            <div class="mb-3">
                <label for="select1" class="form-label">Nombres Mexicanos - Select 1:</label>
                <select class="form-select select2-field" id="select1" data-placeholder="Seleccione un nombre">
                    <option></option>
                    <option value="Alejandro">Alejandro</option>
                    <option value="Antonio">Antonio</option>
                    <option value="Benito">Benito</option>
                    <option value="Carlos">Carlos</option>
                    <option value="Diego">Diego</option>
                    <option value="Emiliano">Emiliano</option>
                    <option value="Francisco">Francisco</option>
                    <option value="Gabriela">Gabriela</option>
                    <option value="Guadalupe">Guadalupe</option>
                    <option value="Héctor">Héctor</option>
                    <option value="Ignacio">Ignacio</option>
                    <option value="Javier">Javier</option>
                    <option value="José">José</option>
                    <option value="Juan">Juan</option>
                    <option value="Laura">Laura</option>
                    <option value="Luz">Luz</option>
                    <option value="Manuel">Manuel</option>
                    <option value="María">María</option>
                    <option value="Miguel">Miguel</option>
                    <option value="Patricia">Patricia</option>
                    <option value="Pedro">Pedro</option>
                    <option value="Ricardo">Ricardo</option>
                    <option value="Rosa">Rosa</option>
                    <option value="Santiago">Santiago</option>
                    <option value="Silvia">Silvia</option>
                    <option value="Teresa">Teresa</option>
                    <option value="Valeria">Valeria</option>
                </select>
            </div>

            <!-- Segundo Select2 con apellidos mexicanos -->
            <div class="mb-3">
                <label for="select2" class="form-label">Apellidos Mexicanos - Select 2:</label>
                <select class="form-select select2-field" id="select2" data-placeholder="Seleccione un apellido">
                    <option></option>
                    <option value="Aguilar">Aguilar</option>
                    <option value="Alvarado">Alvarado</option>
                    <option value="Álvarez">Álvarez</option>
                    <option value="Camacho">Camacho</option>
                    <option value="Castillo">Castillo</option>
                    <option value="Cervantes">Cervantes</option>
                    <option value="Chávez">Chávez</option>
                    <option value="Cruz">Cruz</option>
                    <option value="Domínguez">Domínguez</option>
                    <option value="Fernández">Fernández</option>
                    <option value="García">García</option>
                    <option value="Gómez">Gómez</option>
                    <option value="González">González</option>
                    <option value="Hernández">Hernández</option>
                    <option value="López">López</option>
                    <option value="Martínez">Martínez</option>
                    <option value="Mendoza">Mendoza</option>
                    <option value="Morales">Morales</option>
                    <option value="Ortiz">Ortiz</option>
                    <option value="Pérez">Pérez</option>
                    <option value="Ramírez">Ramírez</option>
                    <option value="Ramos">Ramos</option>
                    <option value="Reyes">Reyes</option>
                    <option value="Rodríguez">Rodríguez</option>
                    <option value="Sánchez">Sánchez</option>
                    <option value="Torres">Torres</option>
                    <option value="Vázquez">Vázquez</option>
                    <option value="Zamora">Zamora</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // Inicializar Select2 con tema Bootstrap 5
            $('.select2-field').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: function () {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });
        });
    </script>
</body>

</html>