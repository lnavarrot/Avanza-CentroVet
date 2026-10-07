<?php

require 'includes/config.php';
require 'includes/funciones.php';


// ==========================================
// VALIDAR SESIÓN
// ==========================================

if (!estaLogueado()) {
    header('Location: login.php');
    exit;
}


// ==========================================
// SI ES VETERINARIO
// ==========================================

if (esVeterinario()) {
    header('Location: veterinario/citas.php');
    exit;
}


$usuarioId = (int)$_SESSION['usuario_id'];


// ==========================================
// REGISTRAR CITA
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mascotaId = (int)($_POST['mascota_id'] ?? 0);
    $servicioId = (int)($_POST['servicio_id'] ?? 0);
    $veterinarioId = (int)($_POST['veterinario_id'] ?? 0);

    $fecha = $_POST['fecha_cita'] ?? '';
    $hora = $_POST['hora_cita'] ?? '';

    $motivo = trim($_POST['motivo'] ?? '');


    // ======================================
    // VALIDAR FECHA
    // ======================================

    if ($fecha < date('Y-m-d')) {

        $_SESSION['flash'] =
            'La fecha no puede ser anterior a hoy.';

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO citas
                (
                    usuario_id,
                    mascota_id,
                    veterinario_id,
                    servicio_id,
                    fecha_cita,
                    hora_cita,
                    motivo
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $usuarioId,
                $mascotaId,
                $veterinarioId,
                $servicioId,
                $fecha,
                $hora,
                $motivo
            ]);


            $_SESSION['flash'] =
                'Cita registrada correctamente.';


        } catch (PDOException $e) {

            $_SESSION['flash'] =
                'Ese horario ya no está disponible. Selecciona otro.';
        }
    }


    header('Location: citas.php');
    exit;
}


// ==========================================
// OBTENER MASCOTAS
// ==========================================

$stmt = $pdo->prepare("
    SELECT *
    FROM mascotas
    WHERE usuario_id = ?
    ORDER BY nombre ASC
");

$stmt->execute([
    $usuarioId
]);

$mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// OBTENER SERVICIOS
// ==========================================

$servicios = $pdo->query("
    SELECT *
    FROM servicios
    WHERE estado = 1
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// OBTENER VETERINARIOS
// ==========================================

$veterinarios = $pdo->query("
    SELECT
        v.id,
        u.nombre,
        v.especialidad
    FROM veterinarios v
    INNER JOIN usuarios u
        ON u.id = v.usuario_id
    WHERE v.estado = 1
    AND u.estado = 1
    ORDER BY u.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// OBTENER CITAS DEL CLIENTE
// ==========================================

$stmt = $pdo->prepare("
    SELECT
        c.*,
        m.nombre AS mascota,
        s.nombre AS servicio,
        u.nombre AS veterinario

    FROM citas c

    INNER JOIN mascotas m
        ON m.id = c.mascota_id

    INNER JOIN servicios s
        ON s.id = c.servicio_id

    INNER JOIN veterinarios v
        ON v.id = c.veterinario_id

    INNER JOIN usuarios u
        ON u.id = v.usuario_id

    WHERE c.usuario_id = ?

    ORDER BY
        c.fecha_cita DESC,
        c.hora_cita DESC
");

$stmt->execute([
    $usuarioId
]);

$citas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// SERVICIO PRESELECCIONADO
// ==========================================

$servicioSeleccionado =
    (int)($_GET['servicio'] ?? 0);


$titulo = 'Citas';

require 'includes/header.php';

?>


<h1>Agendar cita</h1>

<p class="muted">
    Selecciona tu mascota, el servicio,
    el veterinario y el horario de atención.
</p>


<!-- ==========================================
     SI NO HAY MASCOTAS
========================================== -->

<?php if (empty($mascotas)): ?>

    <div class="card">

        <h3>
            Primero registra una mascota
        </h3>

        <p class="muted">
            Para agendar una cita necesitas tener
            al menos una mascota registrada.
        </p>

        <a
            class="btn"
            href="mascotas.php"
        >
            Registrar mascota
        </a>

    </div>


<?php else: ?>


    <!-- ======================================
         FORMULARIO DE CITA
    ======================================= -->

    <form
        class="card"
        method="post"
    >

        <div class="grid">


            <!-- MASCOTA -->

            <div>

                <label for="mascota_id">
                    Mascota
                </label>

                <select
                    id="mascota_id"
                    name="mascota_id"
                    required
                >

                    <?php foreach ($mascotas as $mascota): ?>

                        <option
                            value="<?= (int)$mascota['id'] ?>"
                        >
                            <?= e($mascota['nombre']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- SERVICIO -->

            <div>

                <label for="servicio_id">
                    Servicio
                </label>

                <select
                    id="servicio_id"
                    name="servicio_id"
                    required
                >

                    <?php foreach ($servicios as $servicio): ?>

                        <option
                            value="<?= (int)$servicio['id'] ?>"
                            <?= $servicioSeleccionado === (int)$servicio['id']
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= e($servicio['nombre']) ?>

                            —
                            Q<?= number_format(
                                (float)$servicio['precio'],
                                2
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- VETERINARIO -->

            <div>

                <label for="veterinario_id">
                    Veterinario
                </label>

                <select
                    id="veterinario_id"
                    name="veterinario_id"
                    required
                >

                    <?php foreach ($veterinarios as $vet): ?>

                        <option
                            value="<?= (int)$vet['id'] ?>"
                        >

                            <?= e($vet['nombre']) ?>

                            <?php if (!empty($vet['especialidad'])): ?>

                                —
                                <?= e($vet['especialidad']) ?>

                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- FECHA -->

            <div>

                <label for="fecha_cita">
                    Fecha
                </label>

                <input
                    type="date"
                    id="fecha_cita"
                    name="fecha_cita"
                    min="<?= date('Y-m-d') ?>"
                    required
                >

            </div>


            <!-- HORA -->

            <div>

                <label for="hora_cita">
                    Hora
                </label>

                <input
                    type="time"
                    id="hora_cita"
                    name="hora_cita"
                    min="08:00"
                    max="17:00"
                    step="1800"
                    required
                >

                <small class="muted">
                    Horario de atención:
                    08:00 a 17:00
                </small>

            </div>

        </div>


        <!-- MOTIVO -->

        <div class="form-group">

            <label for="motivo">
                Motivo de la consulta
            </label>

            <textarea
                id="motivo"
                name="motivo"
                placeholder="Describe brevemente el motivo de la cita."
                required
            ></textarea>

        </div>


        <button
            type="submit"
            class="btn"
        >
            Confirmar cita
        </button>

    </form>

<?php endif; ?>


<!-- ==========================================
     MIS CITAS
========================================== -->

<h2>Mis citas</h2>


<div
    class="card"
    style="overflow-x:auto;"
>

    <?php if (!empty($citas)): ?>

        <table>

            <thead>

                <tr>
                    <th>Fecha</th>
                    <th>Mascota</th>
                    <th>Servicio</th>
                    <th>Veterinario</th>
                    <th>Estado</th>
                </tr>

            </thead>


            <tbody>

                <?php foreach ($citas as $cita): ?>

                    <tr>

                        <td>

                            <?= e($cita['fecha_cita']) ?>

                            <br>

                            <small class="muted">
                                <?= e(
                                    substr(
                                        $cita['hora_cita'],
                                        0,
                                        5
                                    )
                                ) ?>
                            </small>

                        </td>


                        <td>

                            <?= e($cita['mascota']) ?>

                        </td>


                        <td>

                            <?= e($cita['servicio']) ?>

                        </td>


                        <td>

                            <?= e($cita['veterinario']) ?>

                        </td>


                        <td>

                            <?= e(
                                ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $cita['estado']
                                    )
                                )
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


    <?php else: ?>

        <p class="muted">
            Todavía no tienes citas registradas.
        </p>

    <?php endif; ?>

</div>


<?php

require 'includes/footer.php';

?>