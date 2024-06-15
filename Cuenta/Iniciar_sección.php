<?php
// Incluir el archivo de conexión
include('../Conexión/Conexión.php');

// Inicializar la variable $mensaje
$mensaje = "";

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger datos del formulario
    $email = $_POST['email'];
    $contraseña = $_POST['contraseña'];

    // Verificar que los campos obligatorios no estén vacíos
    if (empty($email) || empty($contraseña)) {
        $mensaje = "Por favor, completa todos los campos.";
    } else {
        // Buscar el usuario en la base de datos
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            // Verificar la contraseña
            if (password_verify($contraseña, $row['contraseña'])) {
                // Contraseña correcta, iniciar sesión
                session_start();
                $_SESSION['id_usuario'] = $row['id_usuario'];
                $_SESSION['nombre'] = $row['nombre'];
                $_SESSION['email'] = $row['email'];
                // Redireccionar a la página de perfil
                header("Location: ../Perfil.php");
                exit();
            } else {
                // Contraseña incorrecta
                $mensaje = "La contraseña es incorrecta. Por favor, inténtalo de nuevo.";
            }
        } else {
            // Usuario no encontrado
            $mensaje = "El usuario no existe. Por favor, regístrate primero.";
        }

        // Cerrar declaración
        $stmt->close();
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
    <title>Iniciar Sesión</title>
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
            max-width: 400px; /* Ajustado para ser más ancho */
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

        input[type="email"],
        input[type="password"] {
            margin-top: 5px;
            padding: 10px;
            border: 1px solid #555;
            border-radius: 5px;
            width: calc(100% - 20px); /* Ajustado para dejar espacio a los bordes */
            box-sizing: border-box; /* Añadido para incluir el relleno y el borde en el ancho total */
        }

        input[type="submit"] {
            margin-top: 15px;
            padding: 10px;
            background-color: #008080;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div id="form-container">
        <h2>Iniciar Sesión</h2>
        <?php
        // Mostrar el mensaje
        if ($mensaje) {
            echo "<p>$mensaje</p>";
        }
        ?>
        <form action="Iniciar_sección.php" method="post">
            <label for="email">Correo Electrónico:</label>
            <input type="email" name="email" required>

            <label for="contraseña">Contraseña:</label>
            <input type="password" name="contraseña" required>

            <input type="submit" value="Iniciar Sesión">
        </form>
        <form action="../Perfil.php" method="post">
            <input type="submit" value="Regresar">
        </form>
    </div>
</body>
</html>
