<?php
session_start(); // Iniciar o reanudar la sesión

// Incluir archivo de conexión
include 'Conexión/Conexión.php';

// Inicializar variables
$todosUsuarios = [];
$amistadesAceptadas = [];
$solicitudesPendientes = [];
$usuarioBuscado = null;

// Verificar si hay una sesión iniciada
if (isset($_SESSION['id_usuario'])) {
    $id_usuario = $_SESSION['id_usuario'];
    
    // Obtener todos los usuarios
    $sql = "SELECT id_usuario, nombre, email, imagen_perfil, codigo_usuario FROM usuarios";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $todosUsuarios[] = $row;
    }
    $stmt->close();
    
    // Obtener amistades aceptadas
    $sql = "
        SELECT u.id_usuario, u.nombre, u.email, u.imagen_perfil, u.codigo_usuario
        FROM usuarios u
        INNER JOIN amistades a ON (
            (a.id_usuario1 = ? AND a.id_usuario2 = u.id_usuario AND a.estado_amistad = 'aceptada') OR 
            (a.id_usuario2 = ? AND a.id_usuario1 = u.id_usuario AND a.estado_amistad = 'aceptada')
        )
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $amistadesAceptadas[] = $row;
    }
    $stmt->close();
    
    // Obtener solicitudes pendientes
    $sql = "
        SELECT u.id_usuario, u.nombre, u.email, u.imagen_perfil, u.codigo_usuario
        FROM usuarios u
        INNER JOIN amistades a ON (
            (a.id_usuario1 = ? AND a.id_usuario2 = u.id_usuario AND a.estado_amistad = 'pendiente') OR 
            (a.id_usuario2 = ? AND a.id_usuario1 = u.id_usuario AND a.estado_amistad = 'pendiente')
        )
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $solicitudesPendientes[] = $row;
    }
    $stmt->close();

    // Buscar usuario por codigo_usuario
    if (isset($_POST['buscar_codigo'])) {
        $codigo_usuario = $_POST['buscar_codigo'];
        $sql = "SELECT id_usuario, nombre, email, imagen_perfil, codigo_usuario FROM usuarios WHERE codigo_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $codigo_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $usuarioBuscado = $row;
        }
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amigos</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
.container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

h2 {
    margin-top: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

table, th, td {
    border: 1px solid black;
}

th, td {
    padding: 10px;
    text-align: left;
}

button {
    padding: 10px 20px;
    margin: 5px;
    cursor: pointer;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
}

button:hover {
    background-color: #0056b3;
}

#ventana-flotante {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: white;
    padding: 20px;
    border: 1px solid #ccc;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#contenido-ventana {
    margin-bottom: 20px;
}

#ventana-flotante button {
    margin-right: 10px;
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
                        <a href="Cuenta/Serrar_sección.php" class="menu__link menu__link--inside">Cerrar sesión</a>
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

<div class="container">
    <h2>Todos los Usuarios</h2>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Imagen de Perfil</th>
                <th>Código de Usuario</th>
                <th>Opciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($todosUsuarios as $usuario) : ?>
                <tr>
                    <td><?php echo $usuario['nombre']; ?></td>
                    <td><?php echo $usuario['email']; ?></td>
                    <td><img src="data:image/jpeg;base64,<?php echo base64_encode($usuario['imagen_perfil']); ?>" alt="Imagen de perfil" width="50" height="50"></td>
                    <td><?php echo $usuario['codigo_usuario']; ?></td>
                    <td>
                        <button onclick="mostrarOpcionesRelacion('<?php echo $usuario['nombre']; ?>', '<?php echo $usuario['email']; ?>', '<?php echo $usuario['codigo_usuario']; ?>')">Opciones</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Amistades Aceptadas</h2>
    <?php if (count($amistadesAceptadas) > 0) : ?>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Imagen de Perfil</th>
                    <th>Código de Usuario</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($amistadesAceptadas as $amigo) : ?>
                    <tr>
                        <td><?php echo $amigo['nombre']; ?></td>
                        <td><?php echo $amigo['email']; ?></td>
                        <td><img src="data:image/jpeg;base64,<?php echo base64_encode($amigo['imagen_perfil']); ?>" alt="Imagen de perfil" width="50" height="50"></td>
                        <td><?php echo $amigo['codigo_usuario']; ?></td>
                        <td>
                            <button onclick="mostrarOpcionesRelacion('<?php echo $amigo['nombre']; ?>', '<?php echo $amigo['email']; ?>', '<?php echo $amigo['codigo_usuario']; ?>')">Opciones</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>No tienes amistades aceptadas.</p>
    <?php endif; ?>

    <h2>Solicitudes de Amistad Pendientes</h2>
    <?php if (count($solicitudesPendientes) > 0) : ?>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Imagen de Perfil</th>
                    <th>Código de Usuario</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitudesPendientes as $pendiente) : ?>
                    <tr>
                        <td><?php echo $pendiente['nombre']; ?></td>
                        <td><?php echo $pendiente['email']; ?></td>
                        <td><img src="data:image/jpeg;base64,<?php echo base64_encode($pendiente['imagen_perfil']); ?>" alt="Imagen de perfil" width="50" height="50"></td>
                        <td><?php echo $pendiente['codigo_usuario']; ?></td>
                        <td>
                            <button onclick="mostrarOpcionesRelacion('<?php echo $pendiente['nombre']; ?>', '<?php echo $pendiente['email']; ?>', '<?php echo $pendiente['codigo_usuario']; ?>')">Opciones</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>No tienes solicitudes de amistad pendientes.</p>
    <?php endif; ?>

    <h2>Buscar Usuario</h2>
    <form method="POST">
        <input type="text" name="buscar_codigo" placeholder="Código de Usuario">
        <button type="submit">Buscar</button>
    </form>
    <?php if ($usuarioBuscado) : ?>
        <h3>Usuario Encontrado:</h3>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Imagen de Perfil</th>
                    <th>Código de Usuario</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $usuarioBuscado['nombre']; ?></td>
                    <td><?php echo $usuarioBuscado['email']; ?></td>
                    <td><img src="data:image/jpeg;base64,<?php echo base64_encode($usuarioBuscado['imagen_perfil']); ?>" alt="Imagen de perfil" width="50" height="50"></td>
                    <td><?php echo $usuarioBuscado['codigo_usuario']; ?></td>
                    <td>
                        <button onclick="mostrarOpcionesRelacion('<?php echo $usuarioBuscado['nombre']; ?>', '<?php echo $usuarioBuscado['email']; ?>', '<?php echo $usuarioBuscado['codigo_usuario']; ?>')">Opciones</button>
                    </td>
                </tr>
            </tbody>
        </table>
    <?php elseif (isset($_POST['buscar_codigo'])) : ?>
        <p>No se encontró ningún usuario con ese código.</p>
    <?php endif; ?>
</div>

<script>
    // Función para mostrar la ventana flotante con las opciones de relación
    function mostrarOpcionesRelacion(nombre, email, codigoUsuario) {
        const ventanaFlotante = document.getElementById('ventana-flotante');
        const contenidoVentana = document.getElementById('contenido-ventana');
        contenidoVentana.innerHTML = `
            <h3>Opciones de Relación para ${nombre}</h3>
            <p>Email: ${email}</p>
            <p>Código de Usuario: ${codigoUsuario}</p>
            <button onclick="gestionarRelacion('${codigoUsuario}', 'pendiente')">Solicitar Amistad</button>
            <button onclick="gestionarRelacion('${codigoUsuario}', 'aceptada')">Aceptar Amistad</button>
            <button onclick="gestionarRelacion('${codigoUsuario}', 'rechazada')">Rechazar Amistad</button>
            <button onclick="gestionarRelacion('${codigoUsuario}', 'bloqueada')">Bloquear Usuario</button>
        `;
        ventanaFlotante.style.display = 'block';
    }

    // Función para gestionar la relación (pendiente de implementación)
    function gestionarRelacion(codigoUsuario, estado) {
        // Aquí iría el código para gestionar la relación en la base de datos
        alert(`Relación para el usuario ${codigoUsuario} cambiada a: ${estado}`);
        cerrarVentana();
    }

    // Función para cerrar la ventana flotante
    function cerrarVentana() {
        const ventanaFlotante = document.getElementById('ventana-flotante');
        ventanaFlotante.style.display = 'none';
    }
</script>

<!-- Ventana flotante para las opciones de relación -->
<div id="ventana-flotante" style="display: none;">
    <div id="contenido-ventana"></div>
    <button onclick="cerrarVentana()">Cerrar</button>
</div>
</body>
</html>
