<?php
session_start(); // Iniciar o reanudar la sesión

// Incluir archivo de conexión
include 'Conexión/Conexión.php';

// Inicializar variables para el perfil del usuario
$perfil_nombre = "";
$perfil_email = "";
$perfil_imagen_perfil = "";
$perfil_imagen_portada = "";
$perfil_codigo_usuario = "";

// Verificar si hay una sesión iniciada
if (isset($_SESSION['id_usuario'])) {
    // Obtener información del usuario que inició sesión
    $id_usuario = $_SESSION['id_usuario'];
    $sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $perfil_nombre = $row['nombre'];
        $perfil_email = $row['email'];
        $perfil_imagen_perfil = $row['imagen_perfil'];
        $perfil_imagen_portada = $row['imagen_portada'];
        $perfil_codigo_usuario = $row['codigo_usuario'];
    }
    $stmt->close();
}

// Cerrar conexión
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .perfil-portada {
            width: 100%;
            height: 400px; /* Ajusta la altura de la imagen de portada según tus necesidades */
            background-size: cover;
            background-position: center;
            position: relative;
            z-index: -1;
        }
        .perfil-imagen-perfil {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            position: absolute;
            top: 45%; /* Nivelar la altura de la imagen de perfil */
            left: 50%;
            transform: translate(-50%, -50%);
            border: 5px solid white;
            z-index: 1;
        }
        .perfil-container {
            text-align: center;
            padding-top: 90px;
        }
        .perfil-nombre {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px; /* Espaciado inferior */
        }
        .perfil-email {
            font-size: 24px;
            color: #666;
            margin-bottom: 10px; /* Espaciado inferior */
        }
        .perfil-codigo {
            font-size: 20px;
            color: #999;
            margin-bottom: 20px; /* Espaciado inferior */
        }
        .perfil-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            padding: 20px;
        }
        .perfil-button {
            padding: 10px 15px;
            background-color: #555;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .perfil-button:hover {
            background-color: #777;
        }
        @media only screen and (max-width: 768px) {
            .perfil-buttons {
                flex-direction: column;
                align-items: center;
            }
            .perfil-button {
                width: 80%;
            }
        }
    </style>
</head>
<body>
<nav class="menu">
    <section class="menu__container">
            <h1 class="menu__logo">MisteryPach.</h1>

            <ul class="menu__links">
                <li class="menu__item">
                    <a href="index.html" class="menu__link">Inicio</a>
                </li>

                <li class="menu__item menu__item--show">
                    <a href="#" class="menu__link">Interacción <img src="assets/arrow.svg" class="menu__arrow"></a>
    
                    <ul class="menu__nesting">
                        <li class="menu__inside">
                            <a href="Chat_privado.php" class="menu__link menu__link--inside">Chat privado</a>
                        </li>
                        <li class="menu__inside">
                            <a href="Chat_grupales.php" class="menu__link menu__link--inside">Chat grupales</a>
                        </li>
                        <li class="menu__inside">
                            <a href="Video_llamada.php" class="menu__link menu__link--inside">Video llamada</a>
                        </li>
                        <li class="menu__inside">
                            <a href="Reunión_grupal.php" class="menu__link menu__link--inside">Reunión grupal</a>
                        </li>
                    </ul>
                </li>

                <li class="menu__item menu__item--show">
                    <a href="#" class="menu__link">Contactos <img src="assets/arrow.svg" class="menu__arrow"></a>
    
                    <ul class="menu__nesting">
                        <li class="menu__inside">
                            <a href="Amigos.php" class="menu__link menu__link--inside">Amigos</a>
                        </li>
                        <li class="menu__inside">
                            <a href="Empresa.php" class="menu__link menu__link--inside">Empresa</a>
                        </li>
                    </ul>
                </li>

                <li class="menu__item menu__item--show">
                    <a href="#" class="menu__link">Cuenta <img src="assets/arrow.svg" class="menu__arrow"></a>
                    <ul class="menu__nesting">
                        <li class="menu__inside">
                            <a href="Perfil.php" class="menu__link menu__link--inside">Ver Perfil</a>
                        </li>
                        <li class="menu__inside">
                            <a href="Configuración.php" class="menu__link menu__link--inside">Configuración</a>
                        </li>
                        <li class="menu__inside">
                            <a href="Cuenta/Iniciar_sección.php" class="menu__link menu__link--inside">Agregar cuenta</a>
                        </li>
                        <li class="menu__inside">
                            <a href="Cuenta/Serrar_sección.php" class="menu__link menu__link--inside">Serrar sección</a>
                        </li>
                    </ul>
                </li>
            </ul>
            <div class="menu__hamburguer">
                <img src="assets/menu.svg" class="menu__img">
            </div>
        </section>
    </nav>
    <script src="js/app.js"></script>

    <?php if ($perfil_nombre) : ?>
    <div class="perfil-portada" style="background-image: url('data:image/jpeg;base64,<?php echo isset($perfil_imagen_portada) ? base64_encode($perfil_imagen_portada) : ''; ?>');"></div>
    
    <div class="perfil-imagen-perfil-container">
        <img src="data:image/jpeg;base64,<?php echo isset($perfil_imagen_perfil) ? base64_encode($perfil_imagen_perfil) : ''; ?>" class="perfil-imagen-perfil" alt="Imagen de perfil">
    </div>

    <div class="perfil-container">
        <h2 class="perfil-nombre">¡Bienvenido a tu perfil, <?php echo $perfil_nombre; ?>!</h2>
        <div class="perfil-info">
            <p class="perfil-email">Correo: <?php echo $perfil_email; ?></p>
            <p class="perfil-codigo">Código de usuario: <?php echo $perfil_codigo_usuario; ?></p>
            <!-- Mostrar más información del perfil si es necesario -->
        </div>
    </div>
    <?php endif; ?>

    <div class="perfil-buttons">
        <a href="Cuenta/Crear_cuenta.php" class="perfil-button">Crear Cuenta</a>
        <a href="Editar datos.php" class="perfil-button">Editar Datos</a>
        <a href="Eliminar Cuenta.php" class="perfil-button">Eliminar Cuenta</a>
        <a href="Cuenta/Iniciar_sección.php" class="perfil-button">Iniciar Sesión</a>
    </div>
</body>
</html>
