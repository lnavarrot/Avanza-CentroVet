<?php

$titulo = 'Inicio';

require 'includes/header.php';

$dest = $pdo->query("
    SELECT *
    FROM productos
    WHERE activo = 1
    ORDER BY id DESC
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

?>

<section class="hero">

    <div>

        <span class="muted">
            Cuidado, bienestar y tienda veterinaria
        </span>

        <h1>
            Todo para el bienestar de tu mascota en un solo lugar.
        </h1>

        <p>
            Consulta productos, registra a tus mascotas y agenda una cita con Avanza.CentroVet.
        </p>

        <div class="actions">

            <a class="btn" href="citas.php">
                Agendar cita
            </a>

            <a class="btn secondary" href="productos.php">
                Ver productos
            </a>

        </div>

    </div>

    <div class="pet">
        🐶🐱
    </div>

</section>


<h2>Productos destacados</h2>

<div class="productos-destacados">

<?php foreach ($dest as $p): ?>

    <article class="card producto-card">

        <div class="product-img">

            <?php if (!empty($p['imagen'])): ?>

                <img
                    src="<?= APP_URL ?>/uploads/productos/<?= e($p['imagen']) ?>"
                    alt="<?= e($p['nombre']) ?>"
                    class="product-image"
                    loading="lazy"
                >

            <?php else: ?>

                <div class="product-placeholder">
                    🐾
                </div>

            <?php endif; ?>

        </div>


        <h3>
            <?= e($p['nombre']) ?>
        </h3>


        <p>
            <?= e($p['descripcion']) ?>
        </p>


        <div class="price">

            Q<?= number_format(
                (float)$p['precio'],
                2
            ) ?>

        </div>


        <p class="stock">

            Disponibles:
            <?= (int)$p['stock'] ?>

        </p>

    </article>

<?php endforeach; ?>

</div>


<section
    class="card"
    style="margin-top:28px"
>

    <h2>
        Servicios veterinarios
    </h2>

    <p>
        Consulta general, vacunacion, desparasitacion, grooming y apoyo de laboratorio.
    </p>

    <a
        class="btn"
        href="servicios.php"
    >
        Consultar servicios
    </a>

</section>


<?php

require 'includes/footer.php';

?>