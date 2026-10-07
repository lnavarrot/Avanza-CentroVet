<?php

require_once '../includes/config.php';
require_once '../includes/funciones.php';

// Validar acceso de administrador
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$mensaje = '';
$error = '';

// ================================
// CREAR USUARIO
// ================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['accion'] ?? '') === 'crear'
) {

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? '';
    $estado = (int)($_POST['estado'] ?? 1);

    $rolesPermitidos = [
        'cliente',
        'veterinario',
        'admin'
    ];

    if (
        empty($nombre) ||
        empty($email) ||
        empty($password)
    ) {
        $error = 'Nombre, correo y contraseña son obligatorios.';
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    }

    elseif (!in_array($rol, $rolesPermitidos)) {
        $error = 'El rol seleccionado no es válido.';
    }

    else {

        // Verificar correo duplicado
        $stmt = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'El correo electrónico ya está registrado.';
        }

        else {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO usuarios
                (
                    nombre,
                    email,
                    password,
                    rol,
                    telefono,
                    estado
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $nombre,
                $email,
                $passwordHash,
                $rol,
                $telefono,
                $estado
            ]);

            $nuevoUsuarioId = $pdo->lastInsertId();

            // Si es veterinario, crear registro profesional
            if ($rol === 'veterinario') {

                $numeroColegiado =
                    trim($_POST['numero_colegiado'] ?? '');

                $especialidad =
                    trim($_POST['especialidad'] ?? '');

                $stmtVet = $pdo->prepare("
                    INSERT INTO veterinarios
                    (
                        usuario_id,
                        numero_colegiado,
                        especialidad,
                        estado
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $stmtVet->execute([
                    $nuevoUsuarioId,
                    $numeroColegiado,
                    $especialidad,
                    $estado
                ]);
            }

            $mensaje = 'Usuario creado correctamente.';
        }
    }
}

// ================================
// MODIFICAR USUARIO
// ================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['accion'] ?? '') === 'editar'
) {

    $usuarioId = (int)($_POST['usuario_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $estado = (int)($_POST['estado'] ?? 1);

    $rolesPermitidos = [
        'cliente',
        'veterinario',
        'admin'
    ];

    if (
        $usuarioId <= 0 ||
        empty($nombre) ||
        empty($email)
    ) {
        $error = 'Datos incompletos.';
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico no válido.';
    }

    elseif (!in_array($rol, $rolesPermitidos)) {
        $error = 'Rol no válido.';
    }

    else {

        // Verificar correo repetido
        $stmt = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
            AND id != ?
        ");

        $stmt->execute([
            $email,
            $usuarioId
        ]);

        if ($stmt->fetch()) {
            $error =
                'El correo ya pertenece a otro usuario.';
        }

        else {

            $stmt = $pdo->prepare("
                UPDATE usuarios

                SET nombre = ?,
                    email = ?,
                    telefono = ?,
                    rol = ?,
                    estado = ?

                WHERE id = ?
            ");

            $stmt->execute([
                $nombre,
                $email,
                $telefono,
                $rol,
                $estado,
                $usuarioId
            ]);

            // Manejo de información veterinaria
            if ($rol === 'veterinario') {

                $numeroColegiado =
                    trim($_POST['numero_colegiado'] ?? '');

                $especialidad =
                    trim($_POST['especialidad'] ?? '');

                // Revisar si ya existe
                $stmt = $pdo->prepare("
                    SELECT id
                    FROM veterinarios
                    WHERE usuario_id = ?
                ");

                $stmt->execute([$usuarioId]);

                $vet = $stmt->fetch();

                if ($vet) {

                    $stmtVet = $pdo->prepare("
                        UPDATE veterinarios

                        SET numero_colegiado = ?,
                            especialidad = ?,
                            estado = ?

                        WHERE usuario_id = ?
                    ");

                    $stmtVet->execute([
                        $numeroColegiado,
                        $especialidad,
                        $estado,
                        $usuarioId
                    ]);

                }

                else {

                    $stmtVet = $pdo->prepare("
                        INSERT INTO veterinarios
                        (
                            usuario_id,
                            numero_colegiado,
                            especialidad,
                            estado
                        )
                        VALUES (?, ?, ?, ?)
                    ");

                    $stmtVet->execute([
                        $usuarioId,
                        $numeroColegiado,
                        $especialidad,
                        $estado
                    ]);
                }
            }

            $mensaje =
                'Usuario actualizado correctamente.';
        }
    }
}

// ================================
// RESTABLECER CONTRASEÑA
// ================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['accion'] ?? '') === 'reset_password'
) {

    $usuarioId =
        (int)($_POST['usuario_id'] ?? 0);

    $nuevaPassword =
        $_POST['nueva_password'] ?? '';

    if (
        $usuarioId > 0 &&
        strlen($nuevaPassword) >= 6
    ) {

        $hash = password_hash(
            $nuevaPassword,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET password = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $hash,
            $usuarioId
        ]);

        $mensaje =
            'Contraseña actualizada correctamente.';

    }

    else {

        $error =
            'La contraseña debe tener al menos 6 caracteres.';
    }
}

// ================================
// OBTENER USUARIOS
// ================================

$sql = "
    SELECT
        u.*,
        v.numero_colegiado,
        v.especialidad

    FROM usuarios u

    LEFT JOIN veterinarios v
        ON u.id = v.usuario_id

    ORDER BY u.id DESC
";

$stmt = $pdo->query($sql);

$usuarios =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Gestión de Usuarios';

require_once '../includes/header.php';

?>

<div class="admin-container">

    <h1>Gestión de Usuarios</h1>

    <?php if ($mensaje): ?>

        <div class="alert alert-success">
            <?php
            echo htmlspecialchars($mensaje);
            ?>
        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert alert-danger">
            <?php
            echo htmlspecialchars($error);
            ?>
        </div>

    <?php endif; ?>


    <!-- =========================
         CREAR USUARIO
    ========================== -->

    <div class="card">

        <h2>Nuevo Usuario</h2>

        <form method="POST">

            <input
                type="hidden"
                name="accion"
                value="crear"
            >

            <div class="form-row">

                <div class="form-group">

                    <label>Nombre</label>

                    <input
                        type="text"
                        name="nombre"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Correo</label>

                    <input
                        type="email"
                        name="email"
                        required
                    >

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>Teléfono</label>

                    <input
                        type="text"
                        name="telefono"
                    >

                </div>


                <div class="form-group">

                    <label>Contraseña</label>

                    <input
                        type="password"
                        name="password"
                        required
                    >

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>Rol</label>

                    <select
                        name="rol"
                        id="rolCrear"
                        onchange="mostrarCamposVeterinarioCrear()"
                        required
                    >

                        <option value="">
                            Seleccione
                        </option>

                        <option value="cliente">
                            Cliente
                        </option>

                        <option value="veterinario">
                            Veterinario
                        </option>

                        <option value="admin">
                            Administrador
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Estado</label>

                    <select name="estado">

                        <option value="1">
                            Activo
                        </option>

                        <option value="0">
                            Inactivo
                        </option>

                    </select>

                </div>

            </div>


            <div
                id="datosVeterinarioCrear"
                style="display:none;"
            >

                <div class="form-row">

                    <div class="form-group">

                        <label>
                            Número de colegiado
                        </label>

                        <input
                            type="text"
                            name="numero_colegiado"
                        >

                    </div>

                    <div class="form-group">

                        <label>
                            Especialidad
                        </label>

                        <input
                            type="text"
                            name="especialidad"
                        >

                    </div>

                </div>

            </div>


            <button
                type="submit"
                class="btn"
            >
                Crear Usuario
            </button>

        </form>

    </div>


    <!-- =========================
         LISTADO USUARIOS
    ========================== -->

    <div class="card">

        <h2>
            Usuarios Registrados
        </h2>

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($usuarios as $usuario): ?>

                    <tr>

                        <td>
                            <?php
                            echo $usuario['id'];
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $usuario['nombre']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $usuario['email']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $usuario['telefono']
                                ?? ''
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $usuario['rol']
                            );
                            ?>
                        </td>

                        <td>

                            <?php if ($usuario['estado'] == 1): ?>

                                <span class="badge badge-success">
                                    Activo
                                </span>

                            <?php else: ?>

                                <span class="badge badge-danger">
                                    Inactivo
                                </span>

                            <?php endif; ?>

                        </td>


                        <td>

                            <button
                                type="button"
                                class="btn btn-secondary"
                                onclick='editarUsuario(
                                    <?php
                                    echo json_encode($usuario);
                                    ?>
                                )'
                            >

                                Editar

                            </button>


                            <button
                                type="button"
                                class="btn"
                                onclick="abrirPassword(
                                    <?php echo $usuario['id']; ?>,
                                    '<?php
                                    echo htmlspecialchars(
                                        $usuario['nombre'],
                                        ENT_QUOTES
                                    );
                                    ?>'
                                )"
                            >

                                Contraseña

                            </button>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<!-- =========================
     MODAL EDITAR
========================== -->

<div
    id="modalEditar"
    style="
        display:none;
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background:rgba(0,0,0,.5);
        align-items:center;
        justify-content:center;
        z-index:1000;
    "
>

    <div
        class="card"
        style="
            width:90%;
            max-width:700px;
        "
    >

        <h2>
            Editar Usuario
        </h2>


        <form method="POST">

            <input
                type="hidden"
                name="accion"
                value="editar"
            >

            <input
                type="hidden"
                name="usuario_id"
                id="edit_usuario_id"
            >


            <div class="form-group">

                <label>Nombre</label>

                <input
                    type="text"
                    name="nombre"
                    id="edit_nombre"
                    required
                >

            </div>


            <div class="form-group">

                <label>Correo</label>

                <input
                    type="email"
                    name="email"
                    id="edit_email"
                    required
                >

            </div>


            <div class="form-group">

                <label>Teléfono</label>

                <input
                    type="text"
                    name="telefono"
                    id="edit_telefono"
                >

            </div>


            <div class="form-group">

                <label>Rol</label>

                <select
                    name="rol"
                    id="edit_rol"
                    onchange="mostrarCamposVeterinarioEditar()"
                >

                    <option value="cliente">
                        Cliente
                    </option>

                    <option value="veterinario">
                        Veterinario
                    </option>

                    <option value="admin">
                        Administrador
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>Estado</label>

                <select
                    name="estado"
                    id="edit_estado"
                >

                    <option value="1">
                        Activo
                    </option>

                    <option value="0">
                        Inactivo
                    </option>

                </select>

            </div>


            <div
                id="datosVeterinarioEditar"
                style="display:none;"
            >

                <div class="form-group">

                    <label>
                        Número de colegiado
                    </label>

                    <input
                        type="text"
                        name="numero_colegiado"
                        id="edit_numero_colegiado"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Especialidad
                    </label>

                    <input
                        type="text"
                        name="especialidad"
                        id="edit_especialidad"
                    >

                </div>

            </div>


            <button
                type="submit"
                class="btn"
            >
                Guardar Cambios
            </button>


            <button
                type="button"
                class="btn btn-secondary"
                onclick="cerrarEditar()"
            >
                Cancelar
            </button>

        </form>

    </div>

</div>


<!-- =========================
     MODAL PASSWORD
========================== -->

<div
    id="modalPassword"
    style="
        display:none;
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background:rgba(0,0,0,.5);
        align-items:center;
        justify-content:center;
        z-index:1000;
    "
>

    <div
        class="card"
        style="
            width:90%;
            max-width:500px;
        "
    >

        <h2>
            Restablecer Contraseña
        </h2>

        <p id="nombrePassword"></p>


        <form method="POST">

            <input
                type="hidden"
                name="accion"
                value="reset_password"
            >

            <input
                type="hidden"
                name="usuario_id"
                id="password_usuario_id"
            >


            <div class="form-group">

                <label>
                    Nueva contraseña
                </label>

                <input
                    type="password"
                    name="nueva_password"
                    minlength="6"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn"
            >
                Cambiar Contraseña
            </button>


            <button
                type="button"
                class="btn btn-secondary"
                onclick="cerrarPassword()"
            >
                Cancelar
            </button>

        </form>

    </div>

</div>


<script>

function mostrarCamposVeterinarioCrear() {

    const rol =
        document.getElementById('rolCrear').value;

    const datos =
        document.getElementById(
            'datosVeterinarioCrear'
        );

    datos.style.display =
        rol === 'veterinario'
        ? 'block'
        : 'none';
}


function mostrarCamposVeterinarioEditar() {

    const rol =
        document.getElementById(
            'edit_rol'
        ).value;

    const datos =
        document.getElementById(
            'datosVeterinarioEditar'
        );

    datos.style.display =
        rol === 'veterinario'
        ? 'block'
        : 'none';
}


function editarUsuario(usuario) {

    document.getElementById(
        'edit_usuario_id'
    ).value = usuario.id;

    document.getElementById(
        'edit_nombre'
    ).value = usuario.nombre;

    document.getElementById(
        'edit_email'
    ).value = usuario.email;

    document.getElementById(
        'edit_telefono'
    ).value = usuario.telefono || '';

    document.getElementById(
        'edit_rol'
    ).value = usuario.rol;

    document.getElementById(
        'edit_estado'
    ).value = usuario.estado;

    document.getElementById(
        'edit_numero_colegiado'
    ).value =
        usuario.numero_colegiado || '';

    document.getElementById(
        'edit_especialidad'
    ).value =
        usuario.especialidad || '';

    mostrarCamposVeterinarioEditar();

    document.getElementById(
        'modalEditar'
    ).style.display = 'flex';
}


function cerrarEditar() {

    document.getElementById(
        'modalEditar'
    ).style.display = 'none';
}


function abrirPassword(id, nombre) {

    document.getElementById(
        'password_usuario_id'
    ).value = id;

    document.getElementById(
        'nombrePassword'
    ).textContent =
        'Usuario: ' + nombre;

    document.getElementById(
        'modalPassword'
    ).style.display = 'flex';
}


function cerrarPassword() {

    document.getElementById(
        'modalPassword'
    ).style.display = 'none';
}

</script>

<?php
require_once '../includes/footer.php';
?>