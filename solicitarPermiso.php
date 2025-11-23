<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitar Permiso</title>
    <link rel="stylesheet" href="css/estilos.css">

</head>
<body>
</body>
</html>
<?php
$archivoPermisos = fopen('permisos.txt','r');
$permisosDisponibles = array();
while (!feof($archivoPermisos)){
    $permisosDisponibles [] = fgets($archivoPermisos);
}
function comprobarErrores($datos): array
{

    $errores = array();
    foreach ($datos as $campo => $valor){
        if ($campo!== "Enviar" && empty($valor)){
            $errores [$campo] = "El campo $campo no puede estar vacio";
        }
        if ($campo === "fechainicial" && !empty($valor) && strtotime($valor) > strtotime($datos['fechafinal'])){
            $errores [$campo] = "La fecha inicial no puede ser posterior a la fecha final";
        }
    }
    return $errores;

}

function imprimirFormulario($tipoPermiso,$erroress): void
{
    echo '<form action="solicitarPermiso.php" method="post">
            Matricula <input type="text" name="Matricula" value="'.($_POST['Matricula']??'').'">
            <span>' . ($erroress['Matricula']??' '). '
            </span>
            <br>
            Descripción <input type="text" name="descripcion" value="'.($_POST['descripcion']??'').'">
            <span>' . ($erroress['descripcion']??' '). '</span>';

    echo '</span>';
    echo '<input type=""hidden name="permiso" value="' . $tipoPermiso . '">';

    if ($tipoPermiso === "Hotel" || $tipoPermiso === "Residente"){
        echo '<br>';
        echo 'Fecha inicial <input type="date" name="fechainicial" value="'.($_POST['fechainicial']??'').'">
            <span>' . ($erroress['fechainicial']??' '). '</span>';
        echo '<br>';
        echo 'Fecha final <input type="date" name="fechafinal" value="'.($_POST['fechafinal']??'').'">
            <span>' . ($erroress['fechafinal']??' '). '</span>';
        }
    echo '<br>
            Justificante <input type="file" name="Justificante" value="'.($_POST['Justificante']??'').'">
            <span>' . ($erroress['Justificante']??' ') . '
            <br>
            <input type="submit" name="senddata" >
            </form>';

    if (empty($erroress) && isset($_POST['senddata'])){
        echo '<h1>Formulario enviado correctamente</h1>';
        escribirArchivo();


    }
}

function imprimirFormularioPermsio($permisosDisponibles)
{
    echo '<form action="solicitarPermiso.php" method="post">';
    echo 'Tipo de permiso <select  name="permiso">';
    foreach ($permisosDisponibles as $permiso) {
        echo "<option value='$permiso'>$permiso</option>";
    }

    echo '</select>';
    echo '<input type="submit" name="Enviar" >';
    echo'</form>';
}
function escribirArchivo()
{
    $tipoPermiso = $_POST['permiso'];
    $matricula = trim($_POST['Matricula']);
    $descripcion = trim($_POST['descripcion']);

    if ($tipoPermiso === "Hotel" || $tipoPermiso === "Residente") {
        $nombreArchivo = "residentesYHoteles.txt";
        $fechaInicial =$_POST['fechainicial'];
        $fechaFinal = $_POST['fechafinal'];

        $datos = "$matricula $descripcion $fechaInicial $fechaFinal";
    } else {
        $nombreArchivo = "$tipoPermiso.txt";
        $nombreArchivo = lcfirst($nombreArchivo);//pongo la primera letra en minuscula para que el archivo se me guarde bien


        $datos = "$matricula $descripcion";
    }

    $rutaBase = getcwd() . DIRECTORY_SEPARATOR . "archivosTexto";
    $rutaCompleta = $rutaBase . DIRECTORY_SEPARATOR . $nombreArchivo;

    if (!is_dir($rutaBase)) {
        @mkdir($rutaBase, 0777, true);
    }

    if (file_put_contents($rutaCompleta, $datos . "\n", FILE_APPEND) === false) {
        echo "<p style='color:red;'>Error al guardar los datos en el archivo.</p>";
    }
}
//FLUJO DE LA PAGINA
if (!isset($_POST['permiso'])){
    imprimirFormularioPermsio($permisosDisponibles);
} else{
    $listaErrores = comprobarErrores($_POST);
    imprimirFormulario(trim($_POST['permiso']),$listaErrores);
}



?>
<a href="inicio.php">← Volver al Menú Principal</a>







