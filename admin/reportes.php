<?php

require_once '../includes/config.php';
require_once '../includes/funciones.php';

/* =========================================================
   SOLO ADMINISTRADORES
========================================================= */

if (!estaLogueado() || !esAdmin()) {
    header('Location: ../login.php');
    exit;
}


/* =========================================================
   FILTRO DEL REPORTE
========================================================= */

$tipo = $_GET['tipo'] ?? 'mes';

$hoy = date('Y-m-d');

$fechaInicio = '';
$fechaFin = '';
$tituloPeriodo = '';


/* =========================================================
   REPORTE SEMANAL
========================================================= */

if ($tipo === 'semana') {

    $fechaSeleccionada = $_GET['fecha'] ?? $hoy;

    try {

        $fecha = new DateTime($fechaSeleccionada);

    } catch (Exception $e) {

        $fecha = new DateTime();

    }

    /*
     * Calculamos lunes y domingo de la semana seleccionada
     */

    $diaSemana = (int)$fecha->format('N');

    $inicio = clone $fecha;
    $inicio->modify('-' . ($diaSemana - 1) . ' days');

    $fin = clone $inicio;
    $fin->modify('+6 days');

    $fechaInicio = $inicio->format('Y-m-d');
    $fechaFin = $fin->format('Y-m-d');

    $tituloPeriodo =
        'Semana del ' .
        $inicio->format('d/m/Y') .
        ' al ' .
        $fin->format('d/m/Y');


/* =========================================================
   REPORTE MENSUAL
========================================================= */

} else {

    $tipo = 'mes';

    $mes = isset($_GET['mes'])
        ? (int)$_GET['mes']
        : (int)date('m');

    $anio = isset($_GET['anio'])
        ? (int)$_GET['anio']
        : (int)date('Y');

    if ($mes < 1 || $mes > 12) {
        $mes = (int)date('m');
    }

    if ($anio < 2020 || $anio > 2100) {
        $anio = (int)date('Y');
    }

    $fechaInicio = sprintf(
        '%04d-%02d-01',
        $anio,
        $mes
    );

    $fechaFin = date(
        'Y-m-t',
        strtotime($fechaInicio)
    );

    $nombresMeses = [
        1  => 'Enero',
        2  => 'Febrero',
        3  => 'Marzo',
        4  => 'Abril',
        5  => 'Mayo',
        6  => 'Junio',
        7  => 'Julio',
        8  => 'Agosto',
        9  => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];

    $tituloPeriodo =
        $nombresMeses[$mes] .
        ' ' .
        $anio;
}


/* =========================================================
   TOTAL DE CITAS DEL PERÍODO
========================================================= */

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM citas
    WHERE fecha_cita BETWEEN ? AND ?
");

$stmt->execute([
    $fechaInicio,
    $fechaFin
]);

$totalCitas = (int)$stmt->fetchColumn();


/* =========================================================
   TOTAL DE PEDIDOS
========================================================= */

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM pedidos
    WHERE DATE(fecha_pedido) BETWEEN ? AND ?
");

$stmt->execute([
    $fechaInicio,
    $fechaFin
]);

$totalPedidos = (int)$stmt->fetchColumn();


/* =========================================================
   INGRESOS
========================================================= */

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total), 0)
    FROM pedidos
    WHERE DATE(fecha_pedido) BETWEEN ? AND ?
    AND estado <> 'cancelado'
");

$stmt->execute([
    $fechaInicio,
    $fechaFin
]);

$totalIngresos = (float)$stmt->fetchColumn();


/* =========================================================
   CITAS POR ESTADO
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        estado,
        COUNT(*) AS total
    FROM citas
    WHERE fecha_cita BETWEEN ? AND ?
    GROUP BY estado
    ORDER BY total DESC
");

$stmt->execute([
    $fechaInicio,
    $fechaFin
]);

$citasEstado = $stmt->fetchAll();


/* =========================================================
   PEDIDOS POR ESTADO
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        estado,
        COUNT(*) AS total
    FROM pedidos
    WHERE DATE(fecha_pedido) BETWEEN ? AND ?
    GROUP BY estado
    ORDER BY total DESC
");

$stmt->execute([
    $fechaInicio,
    $fechaFin
]);

$pedidosEstado = $stmt->fetchAll();


/* =========================================================
   PRODUCTOS VENDIDOS
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        p.nombre,
        SUM(dp.cantidad) AS cantidad_vendida,
        SUM(dp.subtotal) AS total_vendido

    FROM detalle_pedido dp

    INNER JOIN productos p
        ON p.id = dp.producto_id

    INNER JOIN pedidos pe
        ON pe.id = dp.pedido_id

    WHERE DATE(pe.fecha_pedido) BETWEEN ? AND ?
    AND pe.estado <> 'cancelado'

    GROUP BY
        p.id,
        p.nombre

    ORDER BY cantidad_vendida DESC

    LIMIT 10
");

$stmt->execute([
    $fechaInicio,
    $fechaFin
]);

$productosVendidos = $stmt->fetchAll();


/* =========================================================
   CITAS DEL PERÍODO
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        c.fecha_cita,
        c.hora_cita,
        c.estado,

        m.nombre AS mascota,
        u.nombre AS cliente,
        s.nombre AS servicio

    FROM citas c

    INNER JOIN mascotas m
        ON m.id = c.mascota_id

    INNER JOIN usuarios u
        ON u.id = c.usuario_id

    INNER JOIN servicios s
        ON s.id = c.servicio_id

    WHERE c.fecha_cita BETWEEN ? AND ?

    ORDER BY
        c.fecha_cita DESC,
        c.hora_cita DESC
");

$stmt->execute([
    $fechaInicio,
    $fechaFin
]);

$citas = $stmt->fetchAll();


/* =========================================================
   PEDIDOS DEL PERÍODO
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.total,
        p.estado,
        p.fecha_pedido,
        u.nombre AS cliente

    FROM pedidos p

    INNER JOIN usuarios u
        ON u.id = p.usuario_id

    WHERE DATE(p.fecha_pedido) BETWEEN ? AND ?

    ORDER BY p.fecha_pedido DESC
");

$stmt->execute([
    $fechaInicio,
    $fechaFin
]);

$pedidos = $stmt->fetchAll();


/* =========================================================
   HEADER
========================================================= */

$titulo = 'Reportes';

require '../includes/header.php';

?>


<h1>Reportes</h1>

<p class="muted">
    Consulta la información de Avanza.CentroVet por semana o por mes.
</p>


<!-- ======================================================
     SELECTOR SEMANA / MES
====================================================== -->

<div class="card" style="margin-top:25px;">

    <h2>Seleccionar período</h2>

    <form method="get">

        <div class="grid">

            <div>

                <label>Tipo de reporte</label>

                <select
                    name="tipo"
                    id="tipoReporte"
                    onchange="cambiarTipoReporte()"
                >

                    <option
                        value="semana"
                        <?= $tipo === 'semana' ? 'selected' : '' ?>
                    >
                        Semanal
                    </option>

                    <option
                        value="mes"
                        <?= $tipo === 'mes' ? 'selected' : '' ?>
                    >
                        Mensual
                    </option>

                </select>

            </div>


            <!-- FECHA PARA REPORTE SEMANAL -->

            <div
                id="filtroSemana"
                style="<?= $tipo === 'semana' ? '' : 'display:none;' ?>"
            >

                <label>
                    Selecciona una fecha de la semana
                </label>

                <input
                    type="date"
                    name="fecha"
                    value="<?= e($_GET['fecha'] ?? $hoy) ?>"
                >

            </div>


            <!-- MES -->

            <div
                id="filtroMes"
                style="<?= $tipo === 'mes' ? '' : 'display:none;' ?>"
            >

                <label>Mes</label>

                <select name="mes">

                    <?php foreach ($nombresMeses as $numero => $nombre): ?>

                        <option
                            value="<?= $numero ?>"
                            <?= ($tipo === 'mes' && $mes === $numero)
                                ? 'selected'
                                : ''
                            ?>
                        >
                            <?= e($nombre) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- AÑO -->

            <div
                id="filtroAnio"
                style="<?= $tipo === 'mes' ? '' : 'display:none;' ?>"
            >

                <label>Año</label>

                <select name="anio">

                    <?php
                    $anioActual = (int)date('Y');

                    for ($a = $anioActual - 5; $a <= $anioActual + 1; $a++):
                    ?>

                        <option
                            value="<?= $a ?>"
                            <?= (
                                $tipo === 'mes' &&
                                $anio === $a
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >
                            <?= $a ?>
                        </option>

                    <?php endfor; ?>

                </select>

            </div>

        </div>


        <div
            class="actions"
            style="margin-top:20px;"
        >

            <button
                type="submit"
                class="btn"
            >
                Generar reporte
            </button>

        </div>

    </form>

</div>


<!-- ======================================================
     PERÍODO CONSULTADO
====================================================== -->

<div
    class="card"
    style="margin-top:25px;"
>

    <span class="muted">
        Período consultado
    </span>

    <h2>
        <?= e($tituloPeriodo) ?>
    </h2>

    <p class="muted">

        Desde

        <strong>
            <?= date(
                'd/m/Y',
                strtotime($fechaInicio)
            ) ?>
        </strong>

        hasta

        <strong>
            <?= date(
                'd/m/Y',
                strtotime($fechaFin)
            ) ?>
        </strong>

    </p>

</div>


<!-- ======================================================
     RESUMEN
====================================================== -->

<div
    class="stats"
    style="margin-top:25px;"
>

    <div class="stat">

        <span class="muted">
            Citas
        </span>

        <br>

        <strong>
            <?= $totalCitas ?>
        </strong>

    </div>


    <div class="stat">

        <span class="muted">
            Pedidos
        </span>

        <br>

        <strong>
            <?= $totalPedidos ?>
        </strong>

    </div>


    <div class="stat">

        <span class="muted">
            Ingresos
        </span>

        <br>

        <strong>
            Q<?= number_format(
                $totalIngresos,
                2
            ) ?>
        </strong>

    </div>

</div>


<!-- ======================================================
     CITAS POR ESTADO
====================================================== -->

<div
    class="card"
    style="margin-top:25px;"
>

    <h2>
        Citas por estado
    </h2>


    <?php if ($citasEstado): ?>

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>
                            Estado
                        </th>

                        <th>
                            Cantidad
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($citasEstado as $c): ?>

                    <tr>

                        <td>

                            <?= e(
                                ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $c['estado']
                                    )
                                )
                            ) ?>

                        </td>

                        <td>
                            <?= (int)$c['total'] ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>


    <?php else: ?>

        <p class="muted">
            No existen citas en este período.
        </p>

    <?php endif; ?>

</div>


<!-- ======================================================
     PEDIDOS POR ESTADO
====================================================== -->

<div
    class="card"
    style="margin-top:25px;"
>

    <h2>
        Pedidos por estado
    </h2>


    <?php if ($pedidosEstado): ?>

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>
                            Estado
                        </th>

                        <th>
                            Cantidad
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($pedidosEstado as $p): ?>

                    <tr>

                        <td>
                            <?= e(
                                ucfirst(
                                    $p['estado']
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= (int)$p['total'] ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>


    <?php else: ?>

        <p class="muted">
            No existen pedidos en este período.
        </p>

    <?php endif; ?>

</div>


<!-- ======================================================
     PRODUCTOS MÁS VENDIDOS
====================================================== -->

<div
    class="card"
    style="margin-top:25px;"
>

    <h2>
        Productos más vendidos
    </h2>


    <?php if ($productosVendidos): ?>

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>
                            Producto
                        </th>

                        <th>
                            Unidades vendidas
                        </th>

                        <th>
                            Total vendido
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($productosVendidos as $p): ?>

                    <tr>

                        <td>
                            <?= e($p['nombre']) ?>
                        </td>

                        <td>
                            <?= (int)$p['cantidad_vendida'] ?>
                        </td>

                        <td>

                            Q<?= number_format(
                                (float)$p['total_vendido'],
                                2
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>


    <?php else: ?>

        <p class="muted">
            No existen ventas en este período.
        </p>

    <?php endif; ?>

</div>


<!-- ======================================================
     DETALLE DE CITAS
====================================================== -->

<div
    class="card"
    style="margin-top:25px;"
>

    <h2>
        Citas del período
    </h2>


    <?php if ($citas): ?>

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>Mascota</th>

                        <th>Cliente</th>

                        <th>Servicio</th>

                        <th>Fecha</th>

                        <th>Hora</th>

                        <th>Estado</th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($citas as $c): ?>

                    <tr>

                        <td>
                            <?= e($c['mascota']) ?>
                        </td>

                        <td>
                            <?= e($c['cliente']) ?>
                        </td>

                        <td>
                            <?= e($c['servicio']) ?>
                        </td>

                        <td>

                            <?= date(
                                'd/m/Y',
                                strtotime(
                                    $c['fecha_cita']
                                )
                            ) ?>

                        </td>

                        <td>

                            <?= e(
                                substr(
                                    $c['hora_cita'],
                                    0,
                                    5
                                )
                            ) ?>

                        </td>

                        <td>

                            <?= e(
                                ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $c['estado']
                                    )
                                )
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>


    <?php else: ?>

        <p class="muted">
            No existen citas en este período.
        </p>

    <?php endif; ?>

</div>


<!-- ======================================================
     DETALLE DE PEDIDOS
====================================================== -->

<div
    class="card"
    style="margin-top:25px;"
>

    <h2>
        Pedidos del período
    </h2>


    <?php if ($pedidos): ?>

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>
                            Pedido
                        </th>

                        <th>
                            Cliente
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Estado
                        </th>

                        <th>
                            Fecha
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($pedidos as $p): ?>

                    <tr>

                        <td>
                            #<?= (int)$p['id'] ?>
                        </td>

                        <td>
                            <?= e($p['cliente']) ?>
                        </td>

                        <td>

                            Q<?= number_format(
                                (float)$p['total'],
                                2
                            ) ?>

                        </td>

                        <td>
                            <?= e(
                                ucfirst(
                                    $p['estado']
                                )
                            ) ?>
                        </td>

                        <td>

                            <?= date(
                                'd/m/Y H:i',
                                strtotime(
                                    $p['fecha_pedido']
                                )
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>


    <?php else: ?>

        <p class="muted">
            No existen pedidos en este período.
        </p>

    <?php endif; ?>

</div>


<div
    class="actions"
    style="margin:30px 0;"
>

    <a
        class="btn secondary"
        href="<?= APP_URL ?>/admin/index.php"
    >
        Volver al panel
    </a>

</div>


<!-- ======================================================
     MOSTRAR / OCULTAR FILTROS
====================================================== -->

<script>

function cambiarTipoReporte() {

    const tipo =
        document.getElementById('tipoReporte').value;

    const semana =
        document.getElementById('filtroSemana');

    const mes =
        document.getElementById('filtroMes');

    const anio =
        document.getElementById('filtroAnio');


    if (tipo === 'semana') {

        semana.style.display = 'block';

        mes.style.display = 'none';

        anio.style.display = 'none';

    } else {

        semana.style.display = 'none';

        mes.style.display = 'block';

        anio.style.display = 'block';

    }

}

</script>


<?php require '../includes/footer.php'; ?>