<?php

require 'includes/config.php';
require 'includes/funciones.php';


// ==========================================
// INICIALIZAR CARRITO
// ==========================================

$_SESSION['carrito'] =
    $_SESSION['carrito'] ?? [];


// ==========================================
// PROCESAR ACCIONES
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion =
        $_POST['accion'] ?? '';

    $productoId =
        (int)($_POST['producto_id'] ?? 0);


    // ======================================
    // AGREGAR PRODUCTO
    // ======================================

    if (
        $accion === 'agregar' &&
        $productoId > 0
    ) {

        $stmt = $pdo->prepare("
            SELECT
                id,
                nombre,
                stock,
                activo
            FROM productos
            WHERE id = ?
            AND activo = 1
        ");

        $stmt->execute([
            $productoId
        ]);

        $producto =
            $stmt->fetch(PDO::FETCH_ASSOC);


        $cantidad =
            max(
                1,
                (int)($_POST['cantidad'] ?? 1)
            );


        $cantidadActual =
            (int)(
                $_SESSION['carrito'][$productoId]
                ?? 0
            );


        if (!$producto) {

            $_SESSION['flash'] =
                'El producto no está disponible.';

        } elseif (
            $cantidadActual + $cantidad
            <= (int)$producto['stock']
        ) {

            $_SESSION['carrito'][$productoId] =
                $cantidadActual + $cantidad;


            $_SESSION['flash'] =
                'Producto agregado al carrito.';

        } else {

            $_SESSION['flash'] =
                'Stock insuficiente.';
        }
    }


    // ======================================
    // ELIMINAR PRODUCTO
    // ======================================

    if (
        $accion === 'eliminar' &&
        $productoId > 0
    ) {

        unset(
            $_SESSION['carrito'][$productoId]
        );


        $_SESSION['flash'] =
            'Producto eliminado del carrito.';
    }


    // ======================================
    // VACIAR CARRITO
    // ======================================

    if ($accion === 'vaciar') {

        $_SESSION['carrito'] = [];


        $_SESSION['flash'] =
            'El carrito fue vaciado.';
    }


    header('Location: carrito.php');
    exit;
}


// ==========================================
// OBTENER PRODUCTOS DEL CARRITO
// ==========================================

$items = [];

$total = 0;


if (!empty($_SESSION['carrito'])) {

    $ids =
        array_keys(
            $_SESSION['carrito']
        );


    $placeholders =
        implode(
            ',',
            array_fill(
                0,
                count($ids),
                '?'
            )
        );


    $stmt = $pdo->prepare("
        SELECT *
        FROM productos
        WHERE id IN ($placeholders)
        AND activo = 1
    ");

    $stmt->execute($ids);


    foreach ($stmt as $producto) {

        $productoId =
            (int)$producto['id'];


        $cantidad =
            (int)(
                $_SESSION['carrito'][$productoId]
                ?? 0
            );


        if ($cantidad <= 0) {
            continue;
        }


        $producto['cantidad'] =
            $cantidad;


        $producto['subtotal'] =
            $cantidad *
            (float)$producto['precio'];


        $total +=
            $producto['subtotal'];


        $items[] =
            $producto;
    }
}


// ==========================================
// TÍTULO
// ==========================================

$titulo = 'Carrito';

require 'includes/header.php';

?>


<h1>Carrito de compras</h1>

<p class="muted">
    Revisa los productos seleccionados antes de finalizar tu compra.
</p>


<div class="card">


    <!-- ======================================
         CARRITO VACÍO
    ======================================= -->

    <?php if (empty($items)): ?>

        <h3>
            Tu carrito está vacío
        </h3>

        <p class="muted">
            Agrega productos desde nuestro catálogo.
        </p>

        <a
            class="btn"
            href="productos.php"
        >
            Ver productos
        </a>


    <?php else: ?>


        <!-- ==================================
             PRODUCTOS
        =================================== -->

        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($items as $item): ?>

                        <tr>


                            <!-- PRODUCTO -->

                            <td>

                                <div
                                    style="
                                        display:flex;
                                        align-items:center;
                                        gap:12px;
                                    "
                                >

                                    <?php if (
                                        !empty($item['imagen'])
                                    ): ?>

                                        <img
                                            src="<?= APP_URL ?>/uploads/productos/<?= e($item['imagen']) ?>"
                                            alt="<?= e($item['nombre']) ?>"
                                            style="
                                                width:60px;
                                                height:60px;
                                                object-fit:contain;
                                                background:var(--lila-claro);
                                                border-radius:8px;
                                                padding:4px;
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
                                                background:var(--lila-claro);
                                                border-radius:8px;
                                                font-size:24px;
                                            "
                                        >
                                            🐾
                                        </div>

                                    <?php endif; ?>


                                    <strong>
                                        <?= e($item['nombre']) ?>
                                    </strong>

                                </div>

                            </td>


                            <!-- CANTIDAD -->

                            <td>
                                <?= (int)$item['cantidad'] ?>
                            </td>


                            <!-- PRECIO -->

                            <td>

                                Q<?= number_format(
                                    (float)$item['precio'],
                                    2
                                ) ?>

                            </td>


                            <!-- SUBTOTAL -->

                            <td>

                                <strong>

                                    Q<?= number_format(
                                        (float)$item['subtotal'],
                                        2
                                    ) ?>

                                </strong>

                            </td>


                            <!-- ELIMINAR -->

                            <td>

                                <form
                                    method="post"
                                    style="
                                        margin:0;
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="accion"
                                        value="eliminar"
                                    >

                                    <input
                                        type="hidden"
                                        name="producto_id"
                                        value="<?= (int)$item['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn danger"
                                    >
                                        Eliminar
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <!-- ==================================
             TOTAL
        =================================== -->

        <div
            style="
                margin-top:24px;
                padding-top:18px;
                border-top:1px solid var(--border);
            "
        >

            <h2>

                Total:

                <span class="price">

                    Q<?= number_format(
                        (float)$total,
                        2
                    ) ?>

                </span>

            </h2>

        </div>


        <!-- ==================================
             ACCIONES
        =================================== -->

        <div class="actions">


            <?php if (estaLogueado()): ?>

                <a
                    class="btn"
                    href="checkout.php"
                >
                    Finalizar compra
                </a>

            <?php else: ?>

                <a
                    class="btn"
                    href="login.php"
                >
                    Inicia sesión para comprar
                </a>

            <?php endif; ?>


            <a
                class="btn secondary"
                href="productos.php"
            >
                Seguir comprando
            </a>


            <form
                method="post"
                style="margin:0;"
            >

                <input
                    type="hidden"
                    name="accion"
                    value="vaciar"
                >

                <button
                    type="submit"
                    class="btn danger"
                >
                    Vaciar carrito
                </button>

            </form>

        </div>


    <?php endif; ?>


</div>


<?php

require 'includes/footer.php';

?>