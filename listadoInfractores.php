<?php

function comprobarErrores(array $datos): array
{
    $errores = [];
    if (empty($datos['fechainicial'])) {
        $errores['fechainicial'] = "Debes seleccionar una fecha inicial.";
    }
    if (empty($datos['fechafinal'])) {
        $errores['fechafinal'] = "Debes seleccionar una fecha final.";
    }
    if (empty($errores) && strtotime($datos['fechainicial']) > strtotime($datos['fechafinal'])) {
        $errores['fechainicial'] = "La fecha inicial no puede ser posterior a la final.";
    }
    return $errores;
}

function cargarAutorizados(): array
{
    $rutaBase = getcwd() . DIRECTORY_SEPARATOR . "archivosTexto";
    $autorizados = [];
    if (!is_dir($rutaBase)) {
        error_log("El directorio de autorizados no existe: " . $rutaBase);
        return [];
    }
    $archivos = array_diff(scandir($rutaBase), ['.', '..']);
    foreach ($archivos as $archivo) {
        $nombreSinExtension = pathinfo($archivo, PATHINFO_FILENAME);
        $rutaCompleta = $rutaBase . DIRECTORY_SEPARATOR . $archivo;
        $lineas = file($rutaCompleta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            error_log("No se pudo leer el archivo: " . $rutaCompleta);
            continue;
        }
        foreach ($lineas as $linea) {
            $matricula = explode(' ', $linea)[0];
            $autorizados[$nombreSinExtension][] = $matricula;
        }
    }
    return $autorizados;
}

function esInfractor(array $vehiculo, array $autorizados): bool
{
    if (isset($vehiculo['motor']) && ($vehiculo['motor'] === 'electrico' || $vehiculo['motor']==='eléctrico')) {
        return false;
    }
    foreach ($autorizados as $tipo => $matriculas) {
        if ($tipo !== 'servicios' && in_array($vehiculo['matricula'], $matriculas)) {
            return false;
        }
    }
    if (isset($autorizados['servicios']) && in_array($vehiculo['matricula'], $autorizados['servicios'])) {
        if ($vehiculo['hora'] >= '06:00' && $vehiculo['hora'] <= '11:00') {
            return false;
        }
    }
    return true;
}

function obtenerInfractores(string $fechaInicial, string $fechaFinal, array $autorizados): array
{
    $infractores = [];
    $archivoVehiculos = "vehiculos.txt";
    $handle = @fopen($archivoVehiculos, "r");
    if ($handle === false) {
        error_log("Error al abrir el archivo de vehiculos: " . $archivoVehiculos);
        return [];
    }
    $tsInicial = strtotime($fechaInicial);
    $tsFinal = strtotime($fechaFinal . ' 23:59:59');
    while (($linea = fgets($handle)) !== false) {
        $datos = explode(' ', trim($linea));
        $tsVehiculo = strtotime($datos[3]);
        if ($tsVehiculo >= $tsInicial && $tsVehiculo <= $tsFinal) {
            $vehiculo = [
                'matricula' => $datos[0],
                'hora' => $datos[4],
                'motor' => $datos[5]
            ];
            if (esInfractor($vehiculo, $autorizados)) {
                $infractores[] = $datos;
            }
        }
    }
    fclose($handle);
    return $infractores;
}

function imprimirFormulario(array $errores, string $fechaInicial, string $fechaFinal): void
{
    ?>
    <div id="formulario">
        <form action="listadoInfractores.php" method="post">
            <div id="grid-fechas">
                <label> Fecha Inicial:
                    <input type="date" name="fechainicial" class="fecha" value="<?=$fechaInicial?>">
                </label>
                <label>Fecha Final:
                    <input type="date" name="fechafinal" class="fecha" value="<?=$fechaFinal?>">
                </label>
                <span class="mensaje-error"><?= $errores['fechainicial'] ?? '' ?></span>
                <span class="mensaje-error"><?= $errores['fechafinal'] ?? '' ?></span>
            </div>
            <div class="contenedor-submit">
                <input type="submit" name="Buscar" value="Buscar">
                <button>
                    <a href="inicio.php" class="boton-volver">Volver al inicio</a>
                </button>

            </div>
        </form>
    </div>
    <?php
}

function imprimirInfractores(array $infractores): void
{
    $cabeceras = ['Matricula', 'Propietario', 'Direccion', 'Fecha', 'Hora', 'Motor'];
    echo '<table class="tabla-resultados">';
    echo '<thead><tr>';
    foreach ($cabeceras as $titulo) {
        echo '<th>' . $titulo . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ($infractores as $infractor) {
        echo '<tr>';
        foreach ($infractor as $dato) {
            echo '<td>' . $dato . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

$errores = [];
$infractores = [];
$fechaInicial = $_POST['fechainicial'] ?? '';
$fechaFinal = $_POST['fechafinal'] ?? '';
$formularioEnviado = isset($_POST['Buscar']);

if ($formularioEnviado) {
    $errores = comprobarErrores($_POST);
    if (empty($errores)) {
        $autorizados = cargarAutorizados();
        $infractores = obtenerInfractores($fechaInicial, $fechaFinal, $autorizados);
    }
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Revisar Multas</title>
    <link rel="stylesheet" href="css/estilosListado.css">
</head>
<body>

    <?php imprimirFormulario($errores, $fechaInicial, $fechaFinal); ?>

    <?php if ($formularioEnviado && empty($errores)): ?>
        <div class="resultados">
            <h2>Listado de Infractores</h2>
            <?php if (!empty($infractores)): ?>
                <?php imprimirInfractores($infractores); ?>
            <?php else: ?>
                <p>No se encontraron infractores en el periodo seleccionado.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</body>
</html>
