<?php
session_start();
require '../vendor/autoload.php';

use Intervention\Image\ImageManagerStatic as Image; // Importa el namespace adecuado
require_once "../Controller/ControllerReparation.php";

$controllerReparation = new ControllerReparation(new ServiceReparation());
$controllerReparation->getService()->connect();

$outputDirectory = "../src/images/outputImg/";

if (!array_key_exists("role", $_SESSION)) {
    $_SESSION["role"] = "";
}

if (isset($_POST["roleBtn"]) ||  $_SESSION["role"] == "") {
    $_SESSION["role"] = $_POST["role"];
}

if (!is_dir($outputDirectory)) {
    // Si el directorio no existe, intenta crearlo con permisos adecuados (0777 para pruebas)
    mkdir($outputDirectory, 0777, true);
}


if (isset($_GET["getReparation"]) && isset($_GET["idReparation"])) {
    $reparationId = $_GET["idReparation"];
    $reparation = $controllerReparation->getReparation($reparationId);

    // Verificar si se obtuvo la reparación y la imagen
    if (isset($reparation)) {
        // Establecer el tipo MIME de la respuesta como una imagen JPEG
        // header('Cont ent-Type: image/jpg');
       
        if ($_SESSION["role"] == "client") {
            $occult = Image::make('..\src\images\watermark.jpg');
            $occult->pixelate(4);
            $reparation->getPhoto()->insert($occult, 'top-left', 0,  $reparation->getPhoto()->height() - 15);
        }
        $imageName = "output_image.jpg"; // Nombre del archivo de salida
        $imagePath = $outputDirectory . $imageName;
        $reparation->getPhoto()->save($imagePath, 90);
        // Mostrar la imagen almacenada en el BLOB
        echo "<div style='width:100%;text-align:center;'>";
        echo "<p><b>Name:</b> ".$reparation->getNameWorkshop()."</p>";
        echo "<p><b>Register date:</b> ".$reparation->getRegisterDate()."</p>";
        echo "<p><b>License plate:</b> ".$reparation->getLicensePlate()."</p>";
        echo "<img style='' src='" . $imagePath . "'>";
        echo "</div>";
    }else{
        echo '<div class="alert alert-danger">Reparation doesn\'t exist.</div>';
    }
}

//Insertar reparación
if (isset($_POST["insert"])) {

    $name = $_POST["insertName"];
    $date = $_POST["insertDate"];
    $licensePlate = $_POST["insertLicensePlate"];


    // Procesamiento de la imagen con Intervention Image
    if (
        strlen($name) <= 12 &&
        preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])$/', $date) &&
        preg_match('/^\d{4}-[A-Za-z]{3}$/i', $licensePlate)
    ) {
        $uploadedFile = $_FILES['insertPhoto'];
        $photoPath = $uploadedFile['tmp_name'];
        $photo = Image::make($photoPath);
        $inserted =  $controllerReparation->insertReparation(new Reparation($name, $date, $licensePlate, $photo));
        if ($inserted) {
            // Display reparation number if the insertion is successful
            echo '<div class="alert alert-success">Reparation ID: ' . $inserted . '</div>';
        } else {
            // Show an error message if insertion fails
            echo '<div class="alert alert-danger">Error inserting the repair record.</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Incorrect parameters format.</div>';
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <!--GET-->
    <div class="container mt-4">
        <form action="" method="get" class="mb-3">
            <input type="text" name="idReparation" class="form-control">
            <button type="submit" name="getReparation" class="btn btn-primary mt-2">Search</button>
        </form>
    </div>


    <!--INSERT-->
    <?php
    if ($_SESSION["role"] == "employee") {
        echo '
    <div class="container mt-4">
        <form action="" method="post" enctype="multipart/form-data" class="mb-3">
            <div class="mb-3">
                <label for="insertName" class="form-label">Name:</label>
                <input type="text" name="insertName" class="form-control">
            </div>
            <div class="mb-3">
            <label for="date" class="form-label">Date (yyyy-mm-dd):</label>
            <input type="text" name="insertDate" class="form-control" placeholder="YYYY-MM-DD" title="Please enter a date in the format yyyy-mm-dd">
        
            </div>
            <div class="mb-3">
                <label for="licensePlate" class="form-label">License Plate:</label>
                <input type="text" name="insertLicensePlate" class="form-control" placeholder="9999-XXX" >
            </div>
            <div class="mb-3">
                <label for="photo" class="form-label">Photo:</label>
                <input type="file" name="insertPhoto" class="form-control">
            </div>
            <button type="submit" name="insert" class="btn btn-primary">Insert reparation</button>
        </form>

      
    </div>';
    } ?>
    <a href="index.php" class="btn btn-secondary">Back</a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>