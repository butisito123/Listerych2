<?php
// Incluir el archivo de conexión
include('../Conexión/Conexión.php');

// Inicializar la variable $mensaje
$mensaje = "";

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger datos del formulario
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $contraseña = $_POST['contraseña'];

    // Verificar que los campos obligatorios no estén vacíos
    if (empty($nombre) || empty($email) || empty($contraseña)) {
        $mensaje = "Por favor, completa todos los campos obligatorios.";
    } else {
        // Encriptar la contraseña usando bcrypt
        $hashContraseña = password_hash($contraseña, PASSWORD_DEFAULT);

        if ($_FILES['imagen_perfil']['error'] === UPLOAD_ERR_OK) {
            // Obtener información de la imagen de perfil
            $imagen_perfil = file_get_contents($_FILES['imagen_perfil']['tmp_name']);
        } else {
            // Error al cargar la imagen de perfil
            $mensaje = "Error al cargar la imagen de perfil.";
            $imagen_perfil = null; // Asegura que se maneja la ausencia de una imagen de perfil
        }
        
        if ($_FILES['imagen_portada']['error'] === UPLOAD_ERR_OK) {
            // Obtener información de la imagen de portada
            $imagen_portada = file_get_contents($_FILES['imagen_portada']['tmp_name']);
        } else {
            // Error al cargar la imagen de portada
            $mensaje = "Error al cargar la imagen de portada.";
            $imagen_portada = null; // Asegura que se maneja la ausencia de una imagen de portada
        }
        
        // Insertar datos en la base de datos si no hubo errores con las imágenes
        if (!$mensaje) {
            // Llamar al procedimiento almacenado para generar el código de usuario
            $sql_codigo = "CALL GenerarCodigoUsuario()";
            $conn->query($sql_codigo);

            // Obtener el último código generado
            $sql_last_code = "SELECT codigo_usuario FROM usuarios ORDER BY id_usuario DESC LIMIT 1";
            $result_last_code = $conn->query($sql_last_code);
            $row_last_code = $result_last_code->fetch_assoc();
            $codigo_usuario = $row_last_code['codigo_usuario'];

            // Preparar la consulta SQL para insertar datos, incluidas las imágenes
            $sql = "INSERT INTO usuarios (nombre, email, contraseña, imagen_perfil, imagen_portada, codigo_usuario) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // Los parámetros serán todos tratados como strings ('ssssss')
            $stmt->bind_param("ssssss", $nombre, $email, $hashContraseña, $imagen_perfil, $imagen_portada, $codigo_usuario);

            // Ejecutar la consulta
            if ($stmt->execute()) {
                // Éxito, redireccionar o mostrar mensaje
                header("Location: ../Perfil.php");
                exit();
            } else {
                // Error en la consulta
                $mensaje = "Error al crear la cuenta. Por favor, inténtalo de nuevo.";
            }

            // Cerrar declaración
            $stmt->close();
        }
    }
}

// Cerrar conexión
$conn->close();
?>




<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta</title>
    <!-- Estilos CSS -->
    <style>
        body {
            background-color: #1c1c1c;
            color: #fff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        html, body {
            height: 100%;
            overflow: hidden; /* Evita que el formulario rebase la parte superior de la página */
        }

        #form-container {
            background-color: #333;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            max-width: 600px; /* Ajustado para ser más ancho */
            width: 100%;
            overflow: auto; /* Añadido para permitir desplazamiento vertical cuando sea necesario */
            max-height: calc(100% - 40px); /* Ajustado para limitar la altura del contenedor */
            position: relative; /* Cambiado de absolute a relative */
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-top: 10px;
            display: block;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="file"],
        input[type="date"],
        textarea {
            margin-top: 5px;
            padding: 10px;
            border: 1px solid #555;
            border-radius: 5px;
            width: calc(100% - 20px); /* Ajustado para dejar espacio a los bordes */
            box-sizing: border-box; /* Añadido para incluir el relleno y el borde en el ancho total */
        }

        input[type="submit"],
        input[type="button"] {
            margin-top: 15px;
            padding: 10px;
            background-color: #008080;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #profile-container,
        #cover-container {
            position: relative;
            overflow: hidden;
            margin-bottom: 15px;
        }

        #profile-preview,
        #cover-preview {
            max-width: 100%;
            max-height: 300px;
            transition: opacity 0.5s ease-in-out;
        }

        #remove-profile,
        #remove-cover {
            display: none;
            position: absolute;
            top: 5px;
            right: 5px;
            cursor: pointer;
            color: #ff6347;
        }
    </style>
</head>
<body>
    <div id="form-container">
        <h2>Crear Cuenta</h2>
        <?php
        // Mostrar el mensaje
        if ($mensaje) {
            echo "<p>$mensaje</p>";
        }
        ?>
        <form action="Crear_cuenta.php" method="post" enctype="multipart/form-data">
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" required>

            <label for="email">Correo Electrónico:</label>
            <input type="email" name="email" required>

            <label for="contraseña">Contraseña:</label>
            <input type="password" name="contraseña" required>

            <label for="imagen_perfil">Imagen de Perfil:</label>
            <input type="file" name="imagen_perfil" accept=".jpg, .jpeg, .png, .webp">

            <label for="imagen_portada">Imagen de Portada:</label>
            <input type="file" name="imagen_portada" accept=".jpg, .jpeg, .png, .webp">

            <input type="submit" value="Crear Cuenta">
        </form>
        <form action="../Perfil.php" method="post">
            <input type="submit" value="Regresar">
        </form>
    </div>
    <script>
        function previewImage(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const removeBtn = document.getElementById(`remove-${inputId}`);

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width:100%; max-height:100%;">`;
                    removeBtn.style.display = 'inline';
                };

                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeImage(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const removeBtn = document.getElementById(`remove-${inputId}`);

            input.value = '';
            preview.innerHTML = '';
            removeBtn.style.display = 'none';
        }
    </script>
</body>
</html>
