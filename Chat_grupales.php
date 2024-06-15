<?php
session_start();

// Incluir archivo de conexión
include 'Conexión/Conexión.php';

// Función para obtener el tipo MIME basado en la extensión del archivo
function mime_content_type_from_extension($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];
    return $mime_types[$extension] ?? 'application/octet-stream';
}

// Verificar si hay una sesión iniciada
if (!isset($_SESSION['id_usuario'])) {
    // Redirigir a la página de inicio de sesión si no hay sesión iniciada
    header("Location: Cuenta/Iniciar_sección.php");
    exit();
}

// Obtener el ID del usuario actual
$id_usuario = $_SESSION['id_usuario'];

// Manejar la creación de un nuevo grupo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_grupo'])) {
    $nombre_grupo = trim($_POST['nombre_grupo']);
    $aptitud = $_POST['aptitud'];
    $visibilidad = $_POST['visibilidad'];

    // Validar y procesar la imagen de portada
    $imagen_portada = NULL;
    if (!empty($_FILES['imagen_portada']['tmp_name'])) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_info = getimagesize($_FILES['imagen_portada']['tmp_name']);
        if ($file_info && in_array($file_info['mime'], $allowed_types)) {
            $imagen_portada = file_get_contents($_FILES['imagen_portada']['tmp_name']);
        } else {
            echo "Formato de imagen no válido.";
            exit();
        }
    }

    // Verificar si el nombre de grupo ya existe
    $sql_verificar_nombre = "SELECT COUNT(*) AS total FROM grupos WHERE nombre = ?";
    $stmt_verificar_nombre = $conn->prepare($sql_verificar_nombre);
    $stmt_verificar_nombre->bind_param("s", $nombre_grupo);
    $stmt_verificar_nombre->execute();
    $result_verificar_nombre = $stmt_verificar_nombre->get_result();
    $row_verificar_nombre = $result_verificar_nombre->fetch_assoc();
    if ($row_verificar_nombre['total'] > 0) {
        echo "El nombre de grupo ya está en uso.";
        exit();
    }

    // Generar un código único para el grupo
    $codigo_grupos = bin2hex(random_bytes(8));

    // Insertar el nuevo grupo
    $sql_crear_grupo = "INSERT INTO grupos (nombre, imagen_portada, aptitud, visibilidad, codigo_grupos, creador_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_crear_grupo = $conn->prepare($sql_crear_grupo);
    $stmt_crear_grupo->bind_param("sbsssi", $nombre_grupo, $imagen_portada, $aptitud, $visibilidad, $codigo_grupos, $id_usuario);
    if ($stmt_crear_grupo->execute()) {
        $grupo_id = $stmt_crear_grupo->insert_id;

        // Agregar al creador al grupo como administrador
        $rol = 'administrador';
        $sql_agregar_creador = "INSERT INTO usuarios_grupos (usuario_id, grupo_id, rol) VALUES (?, ?, ?)";
        $stmt_agregar_creador = $conn->prepare($sql_agregar_creador);
        $stmt_agregar_creador->bind_param("iis", $id_usuario, $grupo_id, $rol);
        $stmt_agregar_creador->execute();

        // Redirigir después de la inserción exitosa
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();
    }
    $stmt_crear_grupo->close();
}

// Consultar los grupos creados por el usuario
$sql_grupos_creados = "SELECT * FROM grupos WHERE creador_id = ?";
$stmt_grupos_creados = $conn->prepare($sql_grupos_creados);
$stmt_grupos_creados->bind_param("i", $id_usuario);
$stmt_grupos_creados->execute();
$result_grupos_creados = $stmt_grupos_creados->get_result();

// Consultar los grupos en los que el usuario está unido
$sql_grupos_unidos = "SELECT g.*, ug.rol FROM grupos g INNER JOIN usuarios_grupos ug ON g.id = ug.grupo_id WHERE ug.usuario_id = ?";
$stmt_grupos_unidos = $conn->prepare($sql_grupos_unidos);
$stmt_grupos_unidos->bind_param("i", $id_usuario);
$stmt_grupos_unidos->execute();
$result_grupos_unidos = $stmt_grupos_unidos->get_result();

// Consultar los grupos públicos disponibles
$sql_grupos_publicos = "SELECT * FROM grupos WHERE visibilidad = 'Visible' AND estado = 'activo'";
$result_grupos_publicos = $conn->query($sql_grupos_publicos);

// Consultar las amistades del usuario
$sql_amistades = "SELECT u.id_usuario, u.nombre FROM amistades a INNER JOIN usuarios u ON a.id_usuario2 = u.id_usuario WHERE a.id_usuario1 = ? AND a.estado_amistad = 'aceptada'";
$stmt_amistades = $conn->prepare($sql_amistades);
$stmt_amistades->bind_param("i", $id_usuario);
$stmt_amistades->execute();
$result_amistades = $stmt_amistades->get_result();

// Cerrar conexión
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupos</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
    font-family: Arial, sans-serif;
    background-color: #212121; /* Fondo principal negro */
    color: #fff; /* Texto blanco */
    margin: 0;
    padding: 0;
}

h2 {
    color: #fff; /* Texto blanco para títulos */
}

input[type="text"], input[type="file"], select {
    width: 100%;
    padding: 10px;
    margin: 5px 0;
    border: 1px solid #ddd;
    background-color: #333; /* Fondo gris oscuro */
    color: #fff; /* Texto blanco */
}

input[type="text"]::placeholder {
    color: #ccc; /* Placeholder en gris claro */
}

select {
    background-color: #333; /* Fondo gris oscuro */
    color: #fff; /* Texto blanco */
}

button[type="submit"], button[type="button"], .button {
    background-color: #444; /* Fondo gris medio */
    color: #fff; /* Texto blanco */
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    margin-top: 10px;
}

button[type="submit"]:hover, button[type="button"]:hover, .button:hover {
    background-color: #555; /* Cambio de color al pasar el mouse */
}

.grupo-tabla {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.grupo-tabla th, .grupo-tabla td {
    border: 1px solid #444; /* Bordes gris oscuro */
    padding: 8px;
}

.grupo-tabla th {
    background-color: #333; /* Encabezado en gris oscuro */
    color: #fff; /* Texto blanco */
}

.grupo-tabla td {
    background-color: #555; /* Fondo gris medio para celdas */
}

#ventana-flotante {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: #333; /* Fondo gris oscuro */
    border: 1px solid #888; /* Borde gris claro */
    padding: 20px;
    z-index: 1000;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}

#ventana-flotante button {
    background-color: #444; /* Fondo gris medio */
    color: #fff; /* Texto blanco */
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    margin-top: 10px;
}

#ventana-flotante button:hover {
    background-color: #555; /* Cambio de color al pasar el mouse */
}

#ventana-flotante ul {
    list-style: none;
    padding: 0;
}

#ventana-flotante ul li {
    margin-bottom: 10px;
}

#ventana-flotante input[type="checkbox"] {
    margin-right: 10px;
}

#ventana-flotante input[type="checkbox"] + span {
    color: #fff; /* Texto blanco para nombres de usuarios */
}


    </style>
</head>
<body>
<nav class="menu">
        <section class="menu__container">
            <h1 class="menu__logo">Misterychat.</h1>

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
    <h2>Crear Nuevo Grupo</h2>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
        <input type="text" name="nombre_grupo" placeholder="Nombre del Grupo" required>
        <input type="file" name="imagen_portada" accept="image/jpeg, image/jpg, image/png, image/gif, image/webp">
        <select name="aptitud" required>
            <option value="+18">+18</option>
            <option value="Todo público">Todo público</option>
        </select>
        <select name="visibilidad" required>
            <option value="Visible">Visible</option>
            <option value="Oculto">Oculto</option>
        </select>
        <button type="submit" name="crear_grupo">Crear Grupo</button>
    </form>

    <h2>Tus Grupos Creados</h2>
    <table class="grupo-tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Código</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result_grupos_creados->fetch_assoc()): ?>
                <tr>
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['codigo_grupos']); ?></td>
                    <td>
                        <form action="Chat-G.php" method="GET">
                            <input type="hidden" name="grupo_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="button">Entrar</button>
                        </form>
                        <button onclick="mostrarVentanaFlotante(<?php echo $row['id']; ?>)">Agregar Usuarios</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Grupos a los que te has Unido</h2>
    <table class="grupo-tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Código</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result_grupos_unidos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['codigo_grupos']); ?></td>
                    <td><?php echo htmlspecialchars($row['rol']); ?></td>
                    <td>
                        <form action="Chat-G.php" method="GET">
                            <input type="hidden" name="grupo_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="button">Entrar</button>
                        </form>
                        <?php if ($row['rol'] === 'administrador'): ?>
                            <button onclick="mostrarVentanaFlotante(<?php echo $row['id']; ?>)">Agregar Usuarios</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Grupos Públicos Disponibles</h2>
    <table class="grupo-tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Código</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result_grupos_publicos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['codigo_grupos']); ?></td>
                    <td>
                        <button onclick="unirmeAGrupo(<?php echo $row['id']; ?>)">Unirme</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div id="ventana-flotante">
        <h2>Agregar Usuarios al Grupo</h2>
        <form id="form-agregar-usuario" method="POST" action="agregar_usuario.php">
            <input type="hidden" name="grupo_id" id="grupo_id">
            <ul>
                <?php while ($row = $result_amistades->fetch_assoc()): ?>
                    <li>
                        <input type="checkbox" name="usuarios[]" value="<?php echo $row['id_usuario']; ?>">
                        <span><?php echo htmlspecialchars($row['nombre']); ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
            <button type="submit">Agregar Usuarios</button>
            <button type="button" onclick="cerrarVentanaFlotante()">Cancelar</button>
        </form>
    </div>

    <script>
        function mostrarVentanaFlotante(grupo_id) {
            document.getElementById('grupo_id').value = grupo_id;
            document.getElementById('ventana-flotante').style.display = 'block';
        }

        function cerrarVentanaFlotante() {
            document.getElementById('ventana-flotante').style.display = 'none';
        }

        function unirmeAGrupo(grupo_id) {
            // Implementar la lógica para unirse al grupo
            // Se puede realizar una llamada AJAX o redirigir a una página específica
            alert('Función para unirse al grupo en desarrollo');
        }
    </script>
</body>
</html>

