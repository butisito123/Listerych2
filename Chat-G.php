<?php
session_start();
include 'Conexión/Conexión.php';

function mime_content_type_from_extension($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain',
    ];
    return $mime_types[$extension] ?? 'application/octet-stream';
}

if (!isset($_SESSION['id_usuario'])) {
    header("Location: Cuenta/Iniciar_sección.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_mensaje'])) {
    $grupo_id = $_GET['grupo_id'];
    $contenido_texto = $_POST['mensaje'];

    // Insertar el mensaje de texto
    $sql_enviar_mensaje = "INSERT INTO mensajes (contenido, remitente_id, grupo_id, contenido_texto) VALUES (?, ?, ?, ?)";
    $stmt_enviar_mensaje = $conn->prepare($sql_enviar_mensaje);
    $stmt_enviar_mensaje->bind_param("siss", $contenido_texto, $id_usuario, $grupo_id, $contenido_texto);
    $stmt_enviar_mensaje->execute();
    $mensaje_id = $stmt_enviar_mensaje->insert_id;

    // Manejar archivos adjuntos si se han adjuntado
    if (!empty(array_filter($_FILES['archivos_adjuntos']['name']))) {
        $total_archivos = count($_FILES['archivos_adjuntos']['name']);
        for ($i = 0; $i < $total_archivos; $i++) {
            $nombre_archivo = $_FILES['archivos_adjuntos']['name'][$i];
            $archivo_temporal = $_FILES['archivos_adjuntos']['tmp_name'][$i];
            $contenido_archivo = file_get_contents($archivo_temporal);

            // Insertar el archivo adjunto en la base de datos
            $sql_insertar_archivo = "INSERT INTO archivo_mensajes (nombre_archivo, archivo, mensaje_id) VALUES (?, ?, ?)";
            $stmt_insertar_archivo = $conn->prepare($sql_insertar_archivo);
            $stmt_insertar_archivo->bind_param("ssi", $nombre_archivo, $contenido_archivo, $mensaje_id);
            $stmt_insertar_archivo->execute();
        }
    }

    // Redirigir para evitar el reenvío del formulario al recargar la página
    header("Location: {$_SERVER['REQUEST_URI']}");
    exit();
}

$grupo_id = $_GET['grupo_id'];

// Recuperar mensajes y archivos adjuntos
$sql_mensajes = "
    SELECT m.*, u.nombre
    FROM mensajes m 
    INNER JOIN usuarios u ON m.remitente_id = u.id_usuario 
    WHERE m.grupo_id = ?
    ORDER BY m.fecha_envio ASC";
$stmt_mensajes = $conn->prepare($sql_mensajes);
$stmt_mensajes->bind_param("i", $grupo_id);
$stmt_mensajes->execute();
$result_mensajes = $stmt_mensajes->get_result();

$sql_archivos = "
    SELECT am.*, am.nombre_archivo 
    FROM archivo_mensajes am 
    INNER JOIN mensajes m ON am.mensaje_id = m.id 
    WHERE m.grupo_id = ?";
$stmt_archivos = $conn->prepare($sql_archivos);
$stmt_archivos->bind_param("i", $grupo_id);
$stmt_archivos->execute();
$result_archivos = $stmt_archivos->get_result();

// Mapeamos archivos a sus mensajes correspondientes
$archivos_por_mensaje = [];
while ($archivo = $result_archivos->fetch_assoc()) {
    $archivos_por_mensaje[$archivo['mensaje_id']][] = $archivo;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Grupal</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
    .chat-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
    }
    .chat-messages {
        width: 80%;
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ccc;
        margin-bottom: 20px;
        padding: 10px;
    }
    .message {
        margin-bottom: 10px;
    }
    .sender {
        font-weight: bold;
    }
    .archivos {
        margin-top: 10px;
        margin-left: 20px;
    }
    .archivo {
        display: inline-block;
        margin-right: 10px;
        margin-bottom: 10px;
    }
    .archivo img, .archivo video, .archivo audio {
        max-width: 500px;
        max-height: 500px;
        display: block;
    }
    .ventana-flotante {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80%;
        max-width: 400px;
        background: white;
        border: 1px solid #ccc;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        display: none;
        z-index: 1000;
    }
    .ventana-flotante h3 {
        margin-top: 0;
    }
    .ventana-flotante ul {
        list-style: none;
        padding: 0;
    }
    .ventana-flotante ul li {
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ventana-flotante ul li button {
        background: red;
        color: white;
        border: none;
        padding: 5px;
        cursor: pointer;
    }
</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

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
    
    <div class="chat-container">
        <div class="chat-messages" id="chat-messages">
            <?php while ($row = $result_mensajes->fetch_assoc()): ?>
                <div class="message">
                    <span class="sender"><?php echo htmlspecialchars($row['nombre']); ?>:</span>
                    <span class="content"><?php echo htmlspecialchars($row['contenido']); ?></span>

                    <?php if (isset($archivos_por_mensaje[$row['id']])): ?>
                        <div class="archivos">
                            <?php foreach ($archivos_por_mensaje[$row['id']] as $archivo): ?>
                                <?php 
                                    $mime = mime_content_type_from_extension($archivo['nombre_archivo']);
                                    if (strpos($mime, 'image') === 0): ?>
                                    <div class="archivo">
                                        <img src="data:<?php echo $mime; ?>;base64,<?php echo base64_encode($archivo['archivo']); ?>" alt="Imagen">
                                    </div>
                                <?php elseif (strpos($mime, 'video') === 0): ?>
                                    <div class="archivo">
                                        <video controls>
                                            <source src="data:<?php echo $mime; ?>;base64,<?php echo base64_encode($archivo['archivo']); ?>" type="<?php echo $mime; ?>">
                                        </video>
                                    </div>
                                <?php elseif (strpos($mime, 'audio') === 0): ?>
                                    <div class="archivo">
                                        <audio controls>
                                            <source src="data:<?php echo $mime; ?>;base64,<?php echo base64_encode($archivo['archivo']); ?>"
                                            type="<?php echo $mime; ?>">
                                        </audio>
                                    </div>
                                <?php else: ?>
                                    <div class="archivo">
                                        <a href="data:<?php echo $mime; ?>;base64_encode(<?php echo base64_encode($archivo['archivo']); ?>" download="<?php echo htmlspecialchars($archivo['nombre_archivo']); ?>">
                                            <?php echo htmlspecialchars($archivo['nombre_archivo']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <form id="formulario-mensaje" method="POST" action="" enctype="multipart/form-data" onsubmit="return verificarArchivos()">
            <textarea name="mensaje" placeholder="Escribe tu mensaje aquí"></textarea><br>
            <input type="file" name="archivos_adjuntos[]" multiple onchange="actualizarListaArchivos()"><br>
            <button type="submit" name="enviar_mensaje">Enviar</button>
        </form>
    </div>

    <div class="ventana-flotante" id="ventana-archivos">
        <h3>Archivos adjuntos</h3>
        <ul id="lista-archivos">
            <!-- Lista de archivos se llena dinámicamente -->
        </ul>
        <button onclick="document.getElementById('ventana-archivos').style.display='none'">Cerrar</button>
    </div>

    <script>
        // Función para actualizar la lista de archivos adjuntos en la ventana flotante
        function actualizarListaArchivos() {
            const inputArchivos = document.querySelector('input[name="archivos_adjuntos[]"]');
            const listaArchivos = document.getElementById('lista-archivos');
            listaArchivos.innerHTML = '';

            for (let i = 0; i < inputArchivos.files.length; i++) {
                const archivo = inputArchivos.files[i];
                const li = document.createElement('li');
                li.textContent = archivo.name;
                const botonQuitar = document.createElement('button');
                botonQuitar.textContent = 'Quitar';
                botonQuitar.onclick = () => quitarArchivo(i);
                li.appendChild(botonQuitar);
                listaArchivos.appendChild(li);
            }
            document.getElementById('ventana-archivos').style.display = 'block';
        }

        // Función para quitar un archivo de la lista de archivos adjuntos
        function quitarArchivo(indice) {
            const inputArchivos = document.querySelector('input[name="archivos_adjuntos[]"]');
            const dt = new DataTransfer();

            for (let i = 0; i < inputArchivos.files.length; i++) {
                if (i !== indice) {
                    dt.items.add(inputArchivos.files[i]);
                }
            }

            inputArchivos.files = dt.files;
            actualizarListaArchivos();
        }

        // Función para verificar si hay archivos adjuntos antes de enviar el formulario
        function verificarArchivos() {
            const inputArchivos = document.querySelector('input[name="archivos_adjuntos[]"]');
            // Si no hay archivos adjuntos, simplemente enviar el mensaje
            if (inputArchivos.files.length === 0 && document.querySelector('textarea[name="mensaje"]').value.trim() === '') {
                alert('Debe ingresar al menos un mensaje o adjuntar un archivo.');
                return false;
            }
            return true;
        }

        // Función para cargar mensajes nuevos cada 3 segundos
        function cargarMensajes() {
            const chatMessages = document.getElementById('chat-messages');
            const grupoId = '<?php echo $grupo_id; ?>';
            const scrollTop = chatMessages.scrollTop;

            setInterval(() => {
                $.ajax({
                    url: 'obtener_mensajes.php',
                    type: 'POST',
                    data: { grupo_id: grupoId },
                    success: function(data) {
                        chatMessages.innerHTML = data;
                        chatMessages.scrollTop = scrollTop; // Mantener el scroll en la posición actual
                    }
                });
            }, 3000); // Actualiza cada 3 segundos
        }

        // Llamar a la función para cargar mensajes automáticamente al cargar la página
        window.onload = function() {
            cargarMensajes();
        };
    </script>
</body>
</html>

