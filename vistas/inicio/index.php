<?php
session_start();

// Obtén la ruta actual dividida en segmentos
$ruta = explode(DIRECTORY_SEPARATOR, getcwd());

//calcular nombres de modulos semidinamicamente
$modulo = $ruta[(count($ruta) - 1)];
$pathControlador = __DIR__ . "/../../controladores/individuales/$modulo.php";

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
    <?= headHtml($modulo, $submodulo); ?>
</head>

<body class="layout-fixed-complete sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <?= menuLateral($modulo, $submodulo, $subsubmodulo); ?>
        <div class="app-main-wrapper">
            <?= encabezado(); ?>
            <main class="app-main">
                <?= titulos($modulo, $submodulo, $subsubmodulo); ?>
                <div class="app-content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                        <h3 class="card-title">Cosas por hacer globales</h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                data-lte-toggle="card-collapse" title="Collapse">
                                                <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                                                <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                data-lte-toggle="card-remove" title="Remove">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            <li class="list-group-item">
                                                <strong>global</strong>
                                                <ul>
                                                    <li>Agregar tour iniciar a cada modulo por usuario y ver una forma
                                                        de guardar los tour que ya se dieron en la base de datos o
                                                        dejarlo en algun icono para que el usuario pueda recorrer
                                                        siempre ese tour, esto se puede hacer con shepherd.js</li>
                                                    <li>Siempre que se vaya a procesar algo que genere una salida de
                                                        almacen validar si hay existencia para poder procesar la
                                                        solicitud (esto siempre y cuando la empresa no permita el
                                                        inventario negativo)</li>
                                                    <li>Hacer la funcion global validarPermisoPorModulo($modulo,
                                                        $submodulo, $subsubmodulo), la cual nos retornara true si el
                                                        usuario tiene el permiso de ver el modulo y false si es que el
                                                        usuario no tiene el permioso de ver el modulo</li>
                                                    <li>llevar backorders de documentos como solicitudes, ordenes,
                                                        cotizaciones y remisiones con triggers en la base de datos</li>
                                                    <li>al hacer documentos que tengan que ver con dinero validar
                                                        descuento del cliente/proveedor o descuento de la sucursal</li>
                                                    <li>Al mostrar lotes, en todos los casos mostrar alerta con lotes
                                                        proximos a vencer</li>
                                                    <li>Hacer una bitacora</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Almacenes</strong>
                                                <ul>
                                                    <li>quitar la sucursal a la que pertenece el almacen, ya que se debe
                                                        de registra (solo la primera vez) con la sucursal a la que
                                                        pertenece el usuario</li>
                                                    <li>agregar stock minimo a cada producto en el almacen, por defecto
                                                        poner 0 para que si es 0, no mande la alerta</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Roles</strong>
                                                <ul>
                                                    <li>poner 4 columnas en los permisos (ver, editar, ver todos los
                                                        registros en el listado, ver dashboard)</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Usuarios</strong>
                                                <ul>
                                                    <li>poner 4 columnas en los permisos (ver, editar, ver todos los
                                                        registros en el listado (aunque aqui pudiera haber subcolumnas
                                                        por ejemplo: ver listado: completo, sucursal, usuario), ver
                                                        dashboard)</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Sucursales</strong>
                                                <ul>
                                                    <li>cuando se cree una nueva sucursal se tienen que agregar sus
                                                        registros en consecutivos</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Empresa</strong>
                                                <ul>
                                                    <li>direcciones (lugares o direcciones que se enlistaran en las
                                                        recepciones, ordenes y solicitudes)</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Empresas - Registro</strong>
                                                <ul>
                                                    <li>cuando se cree una nueva empresa se tiene que crear una sucursal
                                                        con todo lo que conlleva por ejemplo crear los consecutivos</li>
                                                    <li>cuando se cree una nueva empresa se tiene que crear un usuario
                                                        con todos los permisos comprados</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Usuarios - Registro</strong>
                                                <ul>
                                                    <li>Cuando se cree un usuario se tienen que crear los permisos para
                                                        ese usuario</li>
                                                    <li>Cuando se cree un usuario se tienen que crear los registros de
                                                        la tabla tablas_columnas_x_usuario</li>
                                                </ul>
                                            </li>
                                    </div>
                                    </ul>
                                </div>
                                <div class="card-footer">Footer</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                        <h3 class="card-title">Modulos terminados</h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                data-lte-toggle="card-collapse" title="Collapse">
                                                <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                                                <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                data-lte-toggle="card-remove" title="Remove">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            <li class="list-group-item">
                                                <strong>Compras</strong>
                                                <ul>
                                                    <li>Proveedores</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Ventas</strong>
                                                <ul>
                                                    <li>Clientes</li>
                                                </ul>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>Almacén</strong>
                                                <ul>
                                                    <li>Productos</li>
                                                    <li>Almacenes</li>
                                                </ul>
                                            </li>

                                        </ul>
                                    </div>
                                    <div class="card-footer">Footer</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header text-bg-<?= getEnfasis(); ?>">
                                        <h3 class="card-title">Ventajas y desventajas</h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                data-lte-toggle="card-collapse" title="Collapse">
                                                <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                                                <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-tool <?= getTextColor(); ?>"
                                                data-lte-toggle="card-remove" title="Remove">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            <ul class="list-group">
                                                <li class="list-group-item">
                                                    <strong>Ventajas</strong>
                                                    <ul>
                                                        <!-- Categoría: Personalización -->
                                                        <li><strong>Personalización</strong>
                                                            <ul>
                                                                <li>Personalización de colores al nivel de usuario
                                                                    (fondo y
                                                                    énfasis).</li>
                                                                <li>Columnas de tablas personalizables al nivel de
                                                                    usuario.
                                                                </li>
                                                                <li>Personalización de procesos administrativos (por
                                                                    ejemplo, elegir si los movimientos se facturan
                                                                    directamente o si se hace un vale de almacén antes
                                                                    de
                                                                    facturar).</li>
                                                            </ul>
                                                        </li>

                                                        <!-- Categoría: Seguridad -->
                                                        <li><strong>Seguridad</strong>
                                                            <ul>
                                                                <li>Validación de registros repetidos.</li>
                                                                <li>Validación en tiempo real sobre usuarios inactivos
                                                                    para
                                                                    prevenir fraudes, deshabilitando el acceso
                                                                    inmediatamente.</li>
                                                                <li>Recuperación de contraseña vía correo electrónico.
                                                                </li>
                                                                <li>Validación de tipo de dato (números enteros,
                                                                    decimales,
                                                                    texto, expresiones regulares, etc.).</li>
                                                                <li>Validación de campos requeridos mínimos para el
                                                                    funcionamiento del sistema.</li>
                                                                <li>Contraseñas encriptadas.</li>
                                                                <li>Protección contra ataques de fuerza bruta.</li>
                                                                <li>Protección contra inyeccion SQL.</li>
                                                            </ul>
                                                        </li>

                                                        <!-- Categoría: Funcionalidad General -->
                                                        <li><strong>Funcionalidad General</strong>
                                                            <ul>
                                                                <li>Carga rápida de datos, sin importar la cantidad de
                                                                    registros.</li>
                                                                <li>Opción "Recuérdame" en el inicio de sesión.</li>
                                                                <li>Exportación de listados a Excel, portapapeles o
                                                                    impresión directa.</li>
                                                                <li>Control de consecutivos (folios, cantidad de
                                                                    dígitos,
                                                                    prefijos, etc.).</li>
                                                                <li>Gestión de usuarios, roles y permisos.</li>
                                                            </ul>
                                                        </li>

                                                        <!-- Categoría: Soporte y Capacitación -->
                                                        <li><strong>Soporte y Capacitación</strong>
                                                            <ul>
                                                                <li>Soporte extendido vía WhatsApp (lunes a sábado de
                                                                    8:00
                                                                    a.m. a 8:00 p.m.).</li>
                                                                <li>Academia con cursos sobre:
                                                                    <ul>
                                                                        <li>Uso del sistema.</li>
                                                                        <li>Protección contra ciberamenazas.</li>
                                                                        <li>Buenas prácticas para redactar correos
                                                                            empresariales.</li>
                                                                        <li>Etc.</li>
                                                                    </ul>
                                                                </li>
                                                            </ul>
                                                        </li>

                                                        <!-- Categoría: Gestión de Inventarios y Productos -->
                                                        <li><strong>Gestión de Inventarios y Productos</strong>
                                                            <ul>
                                                                <li>Manejo de múltiples sucursales.</li>
                                                                <li>Manejo de ubicaciones específicas en el almacén para
                                                                    cada producto.</li>
                                                                <li>Soporte para productos con lotes y números de serie.
                                                                </li>
                                                                <li>Soporte para productos tipo kit (compuestos de
                                                                    varios
                                                                    productos).</li>
                                                                <li>Soporte para productos de tipo servicio.</li>
                                                                <li>Gestión de productos con venta a granel (por
                                                                    ejemplo,
                                                                    vender 0.385 kg).</li>
                                                                <li>Categorización de productos en tres niveles:
                                                                    categoría,
                                                                    subcategoría y subsubcategoría.</li>
                                                                <li>Control de inventarios por almacén.</li>
                                                                <li>Manejo de múltiples almacenes por sucursal.</li>
                                                                <li>Trazabilidad de productos (kardex).</li>
                                                                <li>Alerta de inventario bajo (stock mínimo configurado
                                                                    por
                                                                    producto y por almacén).</li>
                                                            </ul>
                                                        </li>

                                                        <!-- Categoría: Reportes y Análisis -->
                                                        <li><strong>Reportes y Análisis</strong>
                                                            <ul>
                                                                <li>Generación de reportes personalizables (ventas,
                                                                    inventarios, movimientos, etc.).</li>
                                                                <li>Dashboard interactivo con métricas clave del
                                                                    negocio.
                                                                </li>
                                                                <li>Facturación electrónica (Facturas, complementos de
                                                                    pago,
                                                                    notas de crédito, carta porte).</li>
                                                            </ul>
                                                        </li>

                                                        <!-- Categoría: Facilidad de Uso -->
                                                        <li><strong>Facilidad de Uso</strong>
                                                            <ul>
                                                                <li>Tutorial interactivo inicial al ingresar al sistema
                                                                    por
                                                                    primera vez.</li>
                                                                <li>Función de búsqueda avanzada para encontrar
                                                                    rápidamente
                                                                    registros.</li>
                                                            </ul>
                                                        </li>
                                                    </ul>
                                                </li>
                                            </ul>

                                            <li class="list-group-item">
                                                <strong>Desventajas</strong>
                                                <ul>
                                                    <li></li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">Footer</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?= botones(); ?>
        </div>
    </div>
    <?= scriptsHtml(); ?>
</body>

</html>