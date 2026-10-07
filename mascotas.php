<?php

require 'includes/config.php';
require 'includes/funciones.php';


// ======================================================
// VALIDAR SESIÓN
// ======================================================

if (!estaLogueado()) {
    header('Location: login.php');
    exit;
}

$uid = (int)$_SESSION['usuario_id'];
$admin = esAdmin();


// ======================================================
// PROCESAR FORMULARIOS
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? 'registrar';


    // ==================================================
    // REGISTRAR MASCOTA
    // ==================================================

    if ($accion === 'registrar') {

        // Si es administrador puede seleccionar propietario.
        // Si es cliente, la mascota siempre será suya.

        if ($admin) {
            $usuarioMascota = (int)($_POST['usuario_id'] ?? 0);
        } else {
            $usuarioMascota = $uid;
        }


        $nombre = trim($_POST['nombre'] ?? '');
        $especie = trim($_POST['especie'] ?? '');
        $raza = trim($_POST['raza'] ?? '');
        $sexo = $_POST['sexo'] ?? 'No especificado';

        $fechaNacimiento =
            !empty($_POST['fecha_nacimiento'])
                ? $_POST['fecha_nacimiento']
                : null;

        $peso =
            $_POST['peso'] !== ''
                ? $_POST['peso']
                : null;

        $alergias = trim($_POST['alergias'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');


        if (
            $usuarioMascota > 0 &&
            $nombre !== '' &&
            $especie !== ''
        ) {

            $stmt = $pdo->prepare("
                INSERT INTO mascotas (
                    usuario_id,
                    nombre,
                    especie,
                    raza,
                    sexo,
                    fecha_nacimiento,
                    peso,
                    alergias,
                    observaciones
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $usuarioMascota,
                $nombre,
                $especie,
                $raza,
                $sexo,
                $fechaNacimiento,
                $peso,
                $alergias,
                $observaciones
            ]);

            $_SESSION['flash'] =
                'Mascota registrada correctamente.';

        } else {

            $_SESSION['flash'] =
                'Completa los datos obligatorios.';
        }


        header('Location: mascotas.php');
        exit;
    }


    // ==================================================
    // EDITAR MASCOTA
    // ==================================================

    if ($accion === 'editar') {

        $mascotaId = (int)($_POST['mascota_id'] ?? 0);

        $nombre = trim($_POST['nombre'] ?? '');
        $especie = trim($_POST['especie'] ?? '');
        $raza = trim($_POST['raza'] ?? '');
        $sexo = $_POST['sexo'] ?? 'No especificado';

        $fechaNacimiento =
            !empty($_POST['fecha_nacimiento'])
                ? $_POST['fecha_nacimiento']
                : null;

        $peso =
            $_POST['peso'] !== ''
                ? $_POST['peso']
                : null;

        $alergias = trim($_POST['alergias'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');


        // ----------------------------------------------
        // ADMINISTRADOR
        // Puede modificar cualquier mascota
        // ----------------------------------------------

        if ($admin) {

            $usuarioMascota =
                (int)($_POST['usuario_id'] ?? 0);

            $stmt = $pdo->prepare("
                UPDATE mascotas

                SET
                    usuario_id = ?,
                    nombre = ?,
                    especie = ?,
                    raza = ?,
                    sexo = ?,
                    fecha_nacimiento = ?,
                    peso = ?,
                    alergias = ?,
                    observaciones = ?

                WHERE id = ?
            ");

            $stmt->execute([
                $usuarioMascota,
                $nombre,
                $especie,
                $raza,
                $sexo,
                $fechaNacimiento,
                $peso,
                $alergias,
                $observaciones,
                $mascotaId
            ]);

        }

        // ----------------------------------------------
        // CLIENTE
        // Solo puede modificar sus propias mascotas
        // ----------------------------------------------

        else {

            $stmt = $pdo->prepare("
                UPDATE mascotas

                SET
                    nombre = ?,
                    especie = ?,
                    raza = ?,
                    sexo = ?,
                    fecha_nacimiento = ?,
                    peso = ?,
                    alergias = ?,
                    observaciones = ?

                WHERE id = ?
                AND usuario_id = ?
            ");

            $stmt->execute([
                $nombre,
                $especie,
                $raza,
                $sexo,
                $fechaNacimiento,
                $peso,
                $alergias,
                $observaciones,
                $mascotaId,
                $uid
            ]);
        }


        $_SESSION['flash'] =
            'Mascota actualizada correctamente.';


        header('Location: mascotas.php');
        exit;
    }


    // ==================================================
    // ELIMINAR MASCOTA
    // SOLO ADMINISTRADOR
    // ==================================================

    if (
        $accion === 'eliminar' &&
        $admin
    ) {

        $mascotaId =
            (int)($_POST['mascota_id'] ?? 0);

        try {

            $stmt = $pdo->prepare("
                DELETE FROM mascotas
                WHERE id = ?
            ");

            $stmt->execute([
                $mascotaId
            ]);

            $_SESSION['flash'] =
                'Mascota eliminada correctamente.';

        } catch (PDOException $e) {

            $_SESSION['flash'] =
                'No se puede eliminar la mascota porque tiene información relacionada, por ejemplo citas registradas.';
        }


        header('Location: mascotas.php');
        exit;
    }
}


// ======================================================
// OBTENER CLIENTES
// PARA EL ADMINISTRADOR
// ======================================================

$clientes = [];

if ($admin) {

    $stmt = $pdo->query("
        SELECT
            id,
            nombre,
            email

        FROM usuarios

        WHERE rol = 'cliente'
        AND estado = 1

        ORDER BY nombre ASC
    ");

    $clientes =
        $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// ======================================================
// OBTENER MASCOTAS
// ======================================================

if ($admin) {

    // Administrador ve TODAS las mascotas

    $stmt = $pdo->query("
        SELECT
            m.*,
            u.nombre AS propietario,
            u.email AS propietario_email

        FROM mascotas m

        INNER JOIN usuarios u
            ON u.id = m.usuario_id

        ORDER BY m.id DESC
    ");

    $mascotas =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {

    // Cliente solo ve sus mascotas

    $stmt = $pdo->prepare("
        SELECT *
        FROM mascotas
        WHERE usuario_id = ?
        ORDER BY id DESC
    ");

    $stmt->execute([
        $uid
    ]);

    $mascotas =
        $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// ======================================================
// TÍTULO
// ======================================================

$titulo =
    $admin
        ? 'Administrar mascotas'
        : 'Mis mascotas';


require 'includes/header.php';

?>


<!-- ==================================================
     ENCABEZADO
================================================== -->

<?php if ($admin): ?>

    <h1>
        Administración de mascotas
    </h1>

    <p class="muted">
        Consulta y administra las mascotas registradas
        por los clientes.
    </p>

<?php else: ?>

    <h1>
        Mis mascotas
    </h1>

    <p class="muted">
        Administra la información de tus mascotas.
    </p>

<?php endif; ?>


<!-- ==================================================
     LISTADO DE MASCOTAS
================================================== -->

<?php if (empty($mascotas)): ?>

    <div class="card">

        <p class="muted">
            No hay mascotas registradas.
        </p>

    </div>

<?php else: ?>


<div class="grid">

<?php foreach ($mascotas as $mascota): ?>

    <article class="card">


        <!-- ICONO -->

        <div
            class="product-img"
            style="
                height:120px;
                font-size:55px;
            "
        >
            🐾
        </div>


        <!-- NOMBRE -->

        <h2>
            <?= e($mascota['nombre']) ?>
        </h2>


        <!-- PROPIETARIO -->

        <?php if ($admin): ?>

            <p>

                <strong>
                    Propietario:
                </strong>

                <?= e(
                    $mascota['propietario']
                ) ?>

            </p>


            <p class="muted">

                <?= e(
                    $mascota['propietario_email']
                ) ?>

            </p>

        <?php endif; ?>


        <!-- INFORMACIÓN -->

        <p>
            <strong>Especie:</strong>
            <?= e($mascota['especie']) ?>
        </p>


        <p>
            <strong>Raza:</strong>

            <?= e(
                $mascota['raza']
                ?: 'No especificada'
            ) ?>
        </p>


        <p>
            <strong>Sexo:</strong>

            <?= e(
                $mascota['sexo']
                ?: 'No especificado'
            ) ?>
        </p>


        <p>
            <strong>Peso:</strong>

            <?php if (
                $mascota['peso'] !== null
            ): ?>

                <?= e($mascota['peso']) ?> kg

            <?php else: ?>

                No registrado

            <?php endif; ?>

        </p>


        <p>
            <strong>Fecha de nacimiento:</strong>

            <?= e(
                $mascota['fecha_nacimiento']
                ?: 'No registrada'
            ) ?>
        </p>


        <p>
            <strong>Alergias:</strong>

            <?= e(
                $mascota['alergias']
                ?: 'Ninguna registrada'
            ) ?>
        </p>


        <?php if (
            !empty($mascota['observaciones'])
        ): ?>

            <p>

                <strong>
                    Observaciones:
                </strong>

                <?= e(
                    $mascota['observaciones']
                ) ?>

            </p>

        <?php endif; ?>


        <!-- ==========================================
             EDITAR
        =========================================== -->

        <details style="margin-top:18px;">

            <summary
                class="btn"
                style="
                    cursor:pointer;
                    text-align:center;
                "
            >
                Editar mascota
            </summary>


            <form
                method="post"
                style="margin-top:20px;"
            >

                <input
                    type="hidden"
                    name="accion"
                    value="editar"
                >

                <input
                    type="hidden"
                    name="mascota_id"
                    value="<?= (int)$mascota['id'] ?>"
                >


                <!-- PROPIETARIO ADMIN -->

                <?php if ($admin): ?>

                    <div class="form-group">

                        <label>
                            Propietario
                        </label>

                        <select
                            name="usuario_id"
                            required
                        >

                            <?php foreach (
                                $clientes as $cliente
                            ): ?>

                                <option
                                    value="<?= (int)$cliente['id'] ?>"
                                    <?= (int)$cliente['id']
                                        === (int)$mascota['usuario_id']
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= e($cliente['nombre']) ?>
                                    -
                                    <?= e($cliente['email']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                <?php endif; ?>


                <div class="form-group">

                    <label>
                        Nombre
                    </label>

                    <input
                        name="nombre"
                        value="<?= e($mascota['nombre']) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Especie
                    </label>

                    <input
                        name="especie"
                        value="<?= e($mascota['especie']) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Raza
                    </label>

                    <input
                        name="raza"
                        value="<?= e($mascota['raza']) ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Sexo
                    </label>

                    <select name="sexo">

                        <option
                            value="Macho"
                            <?= $mascota['sexo'] === 'Macho'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Macho
                        </option>

                        <option
                            value="Hembra"
                            <?= $mascota['sexo'] === 'Hembra'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Hembra
                        </option>

                        <option
                            value="No especificado"
                            <?= $mascota['sexo'] === 'No especificado'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            No especificado
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Fecha de nacimiento
                    </label>

                    <input
                        type="date"
                        name="fecha_nacimiento"
                        value="<?= e(
                            $mascota['fecha_nacimiento']
                            ?? ''
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Peso (kg)
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="peso"
                        value="<?= e(
                            $mascota['peso']
                            ?? ''
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Alergias
                    </label>

                    <input
                        name="alergias"
                        value="<?= e(
                            $mascota['alergias']
                            ?? ''
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Observaciones
                    </label>

                    <textarea
                        name="observaciones"
                    ><?= e(
                        $mascota['observaciones']
                        ?? ''
                    ) ?></textarea>

                </div>


                <button
                    type="submit"
                    class="btn"
                >
                    Guardar cambios
                </button>

            </form>

        </details>


        <!-- ==========================================
             ELIMINAR - SOLO ADMIN
        =========================================== -->

        <?php if ($admin): ?>

            <form
                method="post"
                style="margin-top:12px;"
                onsubmit="
                    return confirm(
                        '¿Deseas eliminar esta mascota?'
                    );
                "
            >

                <input
                    type="hidden"
                    name="accion"
                    value="eliminar"
                >

                <input
                    type="hidden"
                    name="mascota_id"
                    value="<?= (int)$mascota['id'] ?>"
                >

                <button
                    type="submit"
                    class="btn danger"
                >
                    Eliminar mascota
                </button>

            </form>

        <?php endif; ?>


    </article>

<?php endforeach; ?>

</div>

<?php endif; ?>


<!-- ==================================================
     REGISTRAR MASCOTA
================================================== -->

<form
    class="card"
    method="post"
    style="margin-top:30px;"
>

    <input
        type="hidden"
        name="accion"
        value="registrar"
    >


    <?php if ($admin): ?>

        <h2>
            Registrar mascota para un cliente
        </h2>

    <?php else: ?>

        <h2>
            Registrar mascota
        </h2>

    <?php endif; ?>


    <!-- PROPIETARIO -->

    <?php if ($admin): ?>

        <div class="form-group">

            <label>
                Cliente / propietario
            </label>

            <select
                name="usuario_id"
                required
            >

                <option value="">
                    Selecciona un cliente
                </option>

                <?php foreach (
                    $clientes as $cliente
                ): ?>

                    <option
                        value="<?= (int)$cliente['id'] ?>"
                    >

                        <?= e($cliente['nombre']) ?>
                        -
                        <?= e($cliente['email']) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

    <?php endif; ?>


    <div class="grid">


        <div>

            <label>
                Nombre
            </label>

            <input
                name="nombre"
                required
            >

        </div>


        <div>

            <label>
                Especie
            </label>

            <input
                name="especie"
                required
            >

        </div>


        <div>

            <label>
                Raza
            </label>

            <input
                name="raza"
            >

        </div>


        <div>

            <label>
                Sexo
            </label>

            <select name="sexo">

                <option value="Macho">
                    Macho
                </option>

                <option value="Hembra">
                    Hembra
                </option>

                <option value="No especificado">
                    No especificado
                </option>

            </select>

        </div>


        <div>

            <label>
                Fecha de nacimiento
            </label>

            <input
                type="date"
                name="fecha_nacimiento"
            >

        </div>


        <div>

            <label>
                Peso (kg)
            </label>

            <input
                type="number"
                step="0.01"
                min="0"
                name="peso"
            >

        </div>

    </div>


    <div class="form-group">

        <label>
            Alergias
        </label>

        <input name="alergias">

    </div>


    <div class="form-group">

        <label>
            Observaciones
        </label>

        <textarea
            name="observaciones"
        ></textarea>

    </div>


    <button
        type="submit"
        class="btn"
    >

        <?php if ($admin): ?>

            Registrar mascota

        <?php else: ?>

            Guardar mascota

        <?php endif; ?>

    </button>

</form>


<?php

require 'includes/footer.php';

?>