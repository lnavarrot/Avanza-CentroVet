<?php

// ==========================================
// CONFIGURACIÓN DE LA PÁGINA
// ==========================================

$titulo = 'Servicios';

require 'includes/header.php';


// ==========================================
// OBTENER SERVICIOS ACTIVOS
// ==========================================

$servicios = $pdo->query("
    SELECT *
    FROM servicios
    WHERE estado = 1
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>


<h1>Servicios veterinarios</h1>

<p class="muted">
    Conoce los servicios disponibles en Avanza.CentroVet
    y agenda una cita para tu mascota.
</p>


<!-- ==========================================
     LISTADO DE SERVICIOS
========================================== -->

<div class="servicios-grid">

    <?php foreach ($servicios as $servicio): ?>

        <article class="card servicio-card">


            <!-- ICONO DEL SERVICIO -->

            <div class="servicio-icono">
                ☎
            </div>


            <!-- NOMBRE -->

            <h3 class="servicio-nombre">

                <?= e($servicio['nombre']) ?>

            </h3>


            <!-- DESCRIPCIÓN -->

            <p class="servicio-descripcion">

                <?= e($servicio['descripcion'] ?? '') ?>

            </p>


            <!-- PRECIO -->

            <div class="servicio-precio">

                Q<?= number_format(
                    (float)$servicio['precio'],
                    2
                ) ?>

            </div>


            <!-- DURACIÓN -->

            <p class="servicio-duracion">

                Duracion aproximada:

                <strong>
                    <?= (int)$servicio['duracion_min'] ?> min
                </strong>

            </p>


            <!-- BOTÓN AGENDAR -->

            <a
                class="btn servicio-btn"
                href="citas.php?servicio=<?= (int)$servicio['id'] ?>"
            >
                Agendar cita
            </a>


        </article>

    <?php endforeach; ?>

</div>


<!-- ==========================================
     SI NO HAY SERVICIOS
========================================== -->

<?php if (empty($servicios)): ?>

    <div
        class="card"
        style="margin-top:20px;"
    >

        <h3>
            No hay servicios disponibles
        </h3>

        <p class="muted">
            Actualmente no existen servicios veterinarios
            disponibles para agendar.
        </p>

    </div>

<?php endif; ?>


<?php

require 'includes/footer.php';

?>