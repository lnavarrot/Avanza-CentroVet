<?php

require '../includes/config.php';
require '../includes/funciones.php';


// ======================================================
// VALIDAR ADMINISTRADOR
// ======================================================

if (!esAdmin()) {
    header('Location: ../login.php');
    exit;
}


// ======================================================
// ACTUALIZAR ESTADOS
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? '';


    // ==================================================
    // ACTUALIZAR ESTADO DE PEDIDO
    // ==================================================

    if ($accion === 'actualizar_pedido') {

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $nuevoEstado = $_POST['estado'] ?? '';

        $estadosPedidoPermitidos = [
            'pendiente',
            'procesando',
            'enviado',
            'completado',
            'cancelado'
        ];

        if (
            $pedidoId > 0 &&
            in_array(
                $nuevoEstado,
                $estadosPedidoPermitidos,
                true
            )
        ) {

            $stmt = $pdo->prepare("
                UPDATE pedidos
                SET estado = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $nuevoEstado,
                $pedidoId
            ]);

            $_SESSION['flash'] =
                'Estado del pedido actualizado correctamente.';

        } else {

            $_SESSION['flash'] =
                'No fue posible actualizar el pedido.';
        }
    }


    // ==================================================
    // ACTUALIZAR ESTADO DE CITA
    // ==================================================

    if ($accion === 'actualizar_cita') {

        $citaId = (int)($_POST['cita_id'] ?? 0);
        $nuevoEstado = $_POST['estado'] ?? '';

        $estadosCitaPermitidos = [
            'pendiente',
            'confirmada',
            'reprogramada',
            'en_atencion',
            'finalizada',
            'cancelada',
            'no_asistio'
        ];

        if (
            $citaId > 0 &&
            in_array(
                $nuevoEstado,
                $estadosCitaPermitidos,
                true
            )
        ) {

            $stmt = $pdo->prepare("
                UPDATE citas
                SET estado = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $nuevoEstado,
                $citaId
            ]);

            $_SESSION['flash'] =
                'Estado de la cita actualizado correctamente.';

        } else {

            $_SESSION['flash'] =
                'No fue posible actualizar la cita.';
        }
    }


    header('Location: index.php');
    exit;
}


// ======================================================
// ESTADÍSTICAS GENERALES
// ======================================================

$stats = [];

$consultas = [

    'usuarios' => "
        SELECT COUNT(*)
        FROM usuarios
    ",

    'mascotas' => "
        SELECT COUNT(*)
        FROM mascotas
    ",

    'citas' => "
        SELECT COUNT(*)
        FROM citas
    ",

    'productos' => "
        SELECT COUNT(*)
        FROM productos
    ",

    'pedidos' => "
        SELECT COUNT(*)
        FROM pedidos
    "

];


foreach ($consultas as $clave => $consulta) {

    $stats[$clave] = $pdo
        ->query($consulta)
        ->fetchColumn();
}


// ======================================================
// INGRESOS
// ======================================================

$ingresos = $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM pedidos
    WHERE estado <> 'cancelado'
")->fetchColumn();


// ======================================================
// PRODUCTOS CON BAJO STOCK
// ======================================================

$productosBajoStock = $pdo->query("
    SELECT
        id,
        nombre,
        stock,
        stock_minimo,
        imagen,
        activo

    FROM productos

    WHERE stock <= stock_minimo
    AND activo = 1

    ORDER BY stock ASC

    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);


// ======================================================
// ÚLTIMAS CITAS
// ======================================================

$ultimasCitas = [];

try {

    $stmt = $pdo->query("
        SELECT
            c.id,
            c.fecha_cita,
            c.hora_cita,
            c.estado,

            m.nombre AS mascota,

            u.nombre AS cliente

        FROM citas c

        LEFT JOIN mascotas m
            ON m.id = c.mascota_id

        LEFT JOIN usuarios u
            ON u.id = c.usuario_id

        ORDER BY
            c.fecha_cita DESC,
            c.hora_cita DESC

        LIMIT 10
    ");

    $ultimasCitas =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $ultimasCitas = [];
}


// ======================================================
// ÚLTIMOS PEDIDOS
// ======================================================

$ultimosPedidos = [];

try {

    $stmt = $pdo->query("
        SELECT
            p.id,
            p.total,
            p.estado,
            p.fecha_pedido,

            u.nombre AS cliente

        FROM pedidos p

        LEFT JOIN usuarios u
            ON u.id = p.usuario_id

        ORDER BY p.id DESC

        LIMIT 10
    ");

    $ultimosPedidos =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $ultimosPedidos = [];
}


// ======================================================
// TÍTULO
// ======================================================

$titulo = 'Administración';

require '../includes/header.php';

?>


<h1>Panel de administración</h1>

<p class="muted">
    Resumen general de Avanza.CentroVet
</p>


<!-- ==================================================
     ESTADÍSTICAS
================================================== -->

<div class="stats">

    <div class="stat">

        <span class="muted">
            Usuarios
        </span>

        <br>

        <strong>
            <?= (int)$stats['usuarios'] ?>
        </strong>

    </div>


    <div class="stat">

        <span class="muted">
            Mascotas
        </span>

        <br>

        <strong>
            <?= (int)$stats['mascotas'] ?>
        </strong>

    </div>


    <div class="stat">

        <span class="muted">
            Citas
        </span>

        <br>

        <strong>
            <?= (int)$stats['citas'] ?>
        </strong>

    </div>


    <div class="stat">

        <span class="muted">
            Productos
        </span>

        <br>

        <strong>
            <?= (int)$stats['productos'] ?>
        </strong>

    </div>


    <div class="stat">

        <span class="muted">
            Pedidos
        </span>

        <br>

        <strong>
            <?= (int)$stats['pedidos'] ?>
        </strong>

    </div>


    <div class="stat">

        <span class="muted">
            Ingresos
        </span>

        <br>

        <strong>
            Q<?= number_format(
                (float)$ingresos,
                2
            ) ?>
        </strong>

    </div>

</div>


<!-- ==================================================
     ACCIONES RÁPIDAS
================================================== -->

<div
    class="actions"
    style="margin:22px 0;"
>

    <a
        class="btn"
        href="productos.php"
    >
        Productos
    </a>


    <a
        class="btn"
        href="citas.php"
    >
        Citas
    </a>


    <a
        class="btn"
        href="usuarios.php"
    >
        Usuarios
    </a>


    <a
        class="btn"
        href="../productos.php"
    >
        Ver catálogo
    </a>

</div>


<!-- ==================================================
     PRODUCTOS CON BAJO STOCK
================================================== -->

<div
    class="card"
    style="margin-bottom:22px;"
>

    <h2>
        Productos con bajo stock
    </h2>


    <?php if (empty($productosBajoStock)): ?>

        <p class="muted">
            Actualmente no existen productos con bajo stock.
        </p>

    <?php else: ?>

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>Imagen</th>

                        <th>Producto</th>

                        <th>Stock actual</th>

                        <th>Stock mínimo</th>

                        <th>Estado</th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($productosBajoStock as $producto): ?>

                    <tr>


                        <!-- IMAGEN -->

                        <td>

                            <?php if (!empty($producto['imagen'])): ?>

                                <img
                                    src="../uploads/productos/<?= e($producto['imagen']) ?>"
                                    alt="<?= e($producto['nombre']) ?>"
                                    style="
                                        width:60px;
                                        height:60px;
                                        object-fit:contain;
                                        background:#edf5f4;
                                        border-radius:8px;
                                        padding:5px;
                                    "
                                >

                            <?php else: ?>

                                <div
                                    style="
                                        width:60px;
                                        height:60px;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        background:#edf5f4;
                                        border-radius:8px;
                                        font-size:24px;
                                    "
                                >
                                    🐾
                                </div>

                            <?php endif; ?>

                        </td>


                        <!-- PRODUCTO -->

                        <td>

                            <?= e($producto['nombre']) ?>

                        </td>


                        <!-- STOCK ACTUAL -->

                        <td>

                            <strong
                                style="color:#c64040;"
                            >
                                <?= (int)$producto['stock'] ?>
                            </strong>

                        </td>


                        <!-- STOCK MÍNIMO -->

                        <td>

                            <?= (int)$producto['stock_minimo'] ?>

                        </td>


                        <!-- ESTADO -->

                        <td>

                            <?php if (
                                (int)$producto['stock'] <= 0
                            ): ?>

                                <span
                                    style="
                                        color:#c64040;
                                        font-weight:bold;
                                    "
                                >
                                    Agotado
                                </span>

                            <?php else: ?>

                                <span
                                    style="
                                        color:#d58a00;
                                        font-weight:bold;
                                    "
                                >
                                    Bajo stock
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>


<!-- ==================================================
     ÚLTIMAS CITAS
================================================== -->

<div
    class="card"
    style="margin-bottom:22px;"
>

    <h2>
        Últimas citas
    </h2>

    <p class="muted">
        Puedes cambiar el estado de las citas registradas.
    </p>


    <?php if (empty($ultimasCitas)): ?>

        <p class="muted">
            No hay citas registradas.
        </p>

    <?php else: ?>

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>Mascota</th>

                        <th>Cliente</th>

                        <th>Fecha</th>

                        <th>Hora</th>

                        <th>Estado actual</th>

                        <th>Cambiar estado</th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($ultimasCitas as $cita): ?>

                    <tr>


                        <!-- MASCOTA -->

                        <td>

                            <?= e(
                                $cita['mascota']
                                ?? 'Sin mascota'
                            ) ?>

                        </td>


                        <!-- CLIENTE -->

                        <td>

                            <?= e(
                                $cita['cliente']
                                ?? 'Sin cliente'
                            ) ?>

                        </td>


                        <!-- FECHA -->

                        <td>

                            <?= e(
                                $cita['fecha_cita']
                                ?? ''
                            ) ?>

                        </td>


                        <!-- HORA -->

                        <td>

                            <?= e(
                                isset($cita['hora_cita'])
                                    ? substr(
                                        $cita['hora_cita'],
                                        0,
                                        5
                                    )
                                    : ''
                            ) ?>

                        </td>


                        <!-- ESTADO ACTUAL -->

                        <td>

                            <strong>

                                <?= e(
                                    ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $cita['estado'] ?? ''
                                        )
                                    )
                                ) ?>

                            </strong>

                        </td>


                        <!-- CAMBIAR ESTADO -->

                        <td>

                            <form
                                method="post"
                                style="
                                    margin:0;
                                    min-width:190px;
                                "
                            >

                                <input
                                    type="hidden"
                                    name="accion"
                                    value="actualizar_cita"
                                >

                                <input
                                    type="hidden"
                                    name="cita_id"
                                    value="<?= (int)$cita['id'] ?>"
                                >


                                <select
                                    name="estado"
                                    required
                                    style="margin-bottom:8px;"
                                >


                                    <!-- PENDIENTE -->

                                    <option
                                        value="pendiente"
                                        <?= $cita['estado'] === 'pendiente'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Pendiente
                                    </option>


                                    <!-- CONFIRMADA -->

                                    <option
                                        value="confirmada"
                                        <?= $cita['estado'] === 'confirmada'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Confirmada
                                    </option>


                                    <!-- REPROGRAMADA -->

                                    <option
                                        value="reprogramada"
                                        <?= $cita['estado'] === 'reprogramada'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Reprogramada
                                    </option>


                                    <!-- EN ATENCIÓN -->

                                    <option
                                        value="en_atencion"
                                        <?= $cita['estado'] === 'en_atencion'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        En atención
                                    </option>


                                    <!-- FINALIZADA -->

                                    <option
                                        value="finalizada"
                                        <?= $cita['estado'] === 'finalizada'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Finalizada
                                    </option>


                                    <!-- CANCELADA -->

                                    <option
                                        value="cancelada"
                                        <?= $cita['estado'] === 'cancelada'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Cancelada
                                    </option>


                                    <!-- NO ASISTIÓ -->

                                    <option
                                        value="no_asistio"
                                        <?= $cita['estado'] === 'no_asistio'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        No asistió
                                    </option>

                                </select>


                                <button
                                    type="submit"
                                    class="btn"
                                >
                                    Actualizar
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>


<!-- ==================================================
     ÚLTIMOS PEDIDOS
================================================== -->

<div class="card">

    <h2>
        Últimos pedidos
    </h2>

    <p class="muted">
        Puedes cambiar el estado de los pedidos realizados por los clientes.
    </p>


    <?php if (empty($ultimosPedidos)): ?>

        <p class="muted">
            No hay pedidos registrados.
        </p>

    <?php else: ?>

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>Pedido</th>

                        <th>Cliente</th>

                        <th>Total</th>

                        <th>Estado actual</th>

                        <th>Fecha</th>

                        <th>Cambiar estado</th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($ultimosPedidos as $pedido): ?>

                    <tr>


                        <!-- PEDIDO -->

                        <td>

                            #<?= (int)$pedido['id'] ?>

                        </td>


                        <!-- CLIENTE -->

                        <td>

                            <?= e(
                                $pedido['cliente']
                                ?? 'Sin cliente'
                            ) ?>

                        </td>


                        <!-- TOTAL -->

                        <td>

                            <strong>

                                Q<?= number_format(
                                    (float)$pedido['total'],
                                    2
                                ) ?>

                            </strong>

                        </td>


                        <!-- ESTADO ACTUAL -->

                        <td>

                            <strong>

                                <?= e(
                                    ucfirst(
                                        $pedido['estado']
                                        ?? ''
                                    )
                                ) ?>

                            </strong>

                        </td>


                        <!-- FECHA -->

                        <td>

                            <?= e(
                                $pedido['fecha_pedido']
                                ?? ''
                            ) ?>

                        </td>


                        <!-- CAMBIAR ESTADO -->

                        <td>

                            <form
                                method="post"
                                style="
                                    margin:0;
                                    min-width:180px;
                                "
                            >

                                <input
                                    type="hidden"
                                    name="accion"
                                    value="actualizar_pedido"
                                >

                                <input
                                    type="hidden"
                                    name="pedido_id"
                                    value="<?= (int)$pedido['id'] ?>"
                                >


                                <select
                                    name="estado"
                                    required
                                    style="margin-bottom:8px;"
                                >


                                    <!-- PENDIENTE -->

                                    <option
                                        value="pendiente"
                                        <?= $pedido['estado'] === 'pendiente'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Pendiente
                                    </option>


                                    <!-- PROCESANDO -->

                                    <option
                                        value="procesando"
                                        <?= $pedido['estado'] === 'procesando'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Procesando
                                    </option>


                                    <!-- ENVIADO -->

                                    <option
                                        value="enviado"
                                        <?= $pedido['estado'] === 'enviado'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Enviado
                                    </option>


                                    <!-- COMPLETADO -->

                                    <option
                                        value="completado"
                                        <?= $pedido['estado'] === 'completado'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Completado
                                    </option>


                                    <!-- CANCELADO -->

                                    <option
                                        value="cancelado"
                                        <?= $pedido['estado'] === 'cancelado'
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        Cancelado
                                    </option>

                                </select>


                                <button
                                    type="submit"
                                    class="btn"
                                >
                                    Actualizar
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>


<?php

require '../includes/footer.php';

?>