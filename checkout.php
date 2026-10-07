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
// VALIDAR CARRITO
// ==========================================

if (empty($_SESSION['carrito'])) {
    header('Location: carrito.php');
    exit;
}


// ==========================================
// OBTENER PRODUCTOS DEL CARRITO
// ==========================================

$ids = array_keys($_SESSION['carrito']);

$placeholders = implode(
    ',',
    array_fill(0, count($ids), '?')
);

$stmt = $pdo->prepare("
    SELECT *
    FROM productos
    WHERE id IN ($placeholders)
");

$stmt->execute($ids);

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// CALCULAR TOTAL
// ==========================================

$total = 0;

foreach ($productos as $producto) {

    $cantidad =
        (int)$_SESSION['carrito'][$producto['id']];

    $total +=
        (float)$producto['precio'] *
        $cantidad;
}


// ==========================================
// PROCESAR PEDIDO
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $direccion =
        trim($_POST['direccion'] ?? '');

    $metodoPago =
        $_POST['metodo_pago'] ?? '';


    // Métodos permitidos
    $metodosPermitidos = [
        'efectivo',
        'transferencia',
        'pago_en_clinica'
    ];


    if (
        $direccion === '' ||
        !in_array(
            $metodoPago,
            $metodosPermitidos,
            true
        )
    ) {

        $_SESSION['flash'] =
            'Completa correctamente los datos del pedido.';

        header('Location: checkout.php');
        exit;
    }


    try {

        // ======================================
        // INICIAR TRANSACCIÓN
        // ======================================

        $pdo->beginTransaction();


        /*
        ==========================================
        VOLVER A CONSULTAR PRODUCTOS
        CON BLOQUEO PARA VALIDAR STOCK REAL
        ==========================================
        */

        $stmt = $pdo->prepare("
            SELECT *
            FROM productos
            WHERE id IN ($placeholders)
            FOR UPDATE
        ");

        $stmt->execute($ids);

        $productosActuales =
            $stmt->fetchAll(PDO::FETCH_ASSOC);


        // ======================================
        // VALIDAR STOCK
        // ======================================

        foreach ($productosActuales as $producto) {

            $cantidad =
                (int)$_SESSION['carrito'][$producto['id']];

            if (
                $cantidad >
                (int)$producto['stock']
            ) {

                throw new Exception(
                    'Stock insuficiente'
                );
            }
        }


        // ======================================
        // RECALCULAR TOTAL
        // ======================================

        $totalFinal = 0;

        foreach ($productosActuales as $producto) {

            $cantidad =
                (int)$_SESSION['carrito'][$producto['id']];

            $totalFinal +=
                (float)$producto['precio'] *
                $cantidad;
        }


        // ======================================
        // CREAR PEDIDO
        // ======================================

        $stmt = $pdo->prepare("
            INSERT INTO pedidos
            (
                usuario_id,
                subtotal,
                envio,
                total,
                metodo_pago,
                direccion_envio
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $envio = 0;

        $stmt->execute([
            $_SESSION['usuario_id'],
            $totalFinal,
            $envio,
            $totalFinal,
            $metodoPago,
            $direccion
        ]);


        $pedidoId =
            $pdo->lastInsertId();


        // ======================================
        // PREPARAR DETALLE DEL PEDIDO
        // ======================================

        $detalleStmt = $pdo->prepare("
            INSERT INTO detalle_pedido
            (
                pedido_id,
                producto_id,
                cantidad,
                precio_unitario,
                subtotal
            )
            VALUES (?, ?, ?, ?, ?)
        ");


        // ======================================
        // PREPARAR ACTUALIZACIÓN DE STOCK
        // ======================================

        $stockStmt = $pdo->prepare("
            UPDATE productos
            SET stock = stock - ?
            WHERE id = ?
        ");


        // ======================================
        // GUARDAR DETALLE Y ACTUALIZAR STOCK
        // ======================================

        foreach ($productosActuales as $producto) {

            $cantidad =
                (int)$_SESSION['carrito'][$producto['id']];

            $precio =
                (float)$producto['precio'];

            $subtotal =
                $cantidad * $precio;


            $detalleStmt->execute([
                $pedidoId,
                $producto['id'],
                $cantidad,
                $precio,
                $subtotal
            ]);


            $stockStmt->execute([
                $cantidad,
                $producto['id']
            ]);
        }


        // ======================================
        // CONFIRMAR TRANSACCIÓN
        // ======================================

        $pdo->commit();


        // ======================================
        // VACIAR CARRITO
        // ======================================

        $_SESSION['carrito'] = [];


        $_SESSION['flash'] =
            'Pedido #' .
            $pedidoId .
            ' confirmado correctamente.';


        header('Location: perfil.php');
        exit;


    } catch (Exception $e) {

        // ======================================
        // REVERTIR CAMBIOS
        // ======================================

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }


        $_SESSION['flash'] =
            'No fue posible procesar el pedido. Verifica el stock disponible.';


        header('Location: carrito.php');
        exit;
    }
}


// ==========================================
// PÁGINA
// ==========================================

$titulo = 'Finalizar compra';

require 'includes/header.php';

?>


<form
    class="card"
    method="post"
>

    <h1>
        Finalizar compra
    </h1>


    <p class="muted">
        Confirma la dirección de entrega
        y el método de pago.
    </p>


    <!-- ======================================
         RESUMEN
    ======================================= -->

    <div
        style="
            margin-bottom:20px;
            padding:15px;
            border-radius:10px;
            background:var(--lila-claro);
        "
    >

        <span class="muted">
            Total del pedido
        </span>

        <br>

        <strong class="price">
            Q<?= number_format(
                (float)$total,
                2
            ) ?>
        </strong>

    </div>


    <!-- ======================================
         DIRECCIÓN
    ======================================= -->

    <div class="form-group">

        <label for="direccion">
            Dirección de entrega
        </label>

        <textarea
            id="direccion"
            name="direccion"
            placeholder="Ingresa la dirección donde deseas recibir tu pedido."
            required
        ></textarea>

    </div>


    <!-- ======================================
         MÉTODO DE PAGO
    ======================================= -->

    <div class="form-group">

        <label for="metodo_pago">
            Método de pago
        </label>

        <select
            id="metodo_pago"
            name="metodo_pago"
            required
        >

            <option value="">
                Selecciona una opción
            </option>

            <option value="efectivo">
                Efectivo contra entrega
            </option>

            <option value="transferencia">
                Transferencia bancaria
            </option>

            <option value="pago_en_clinica">
                Pago en clínica
            </option>

        </select>

    </div>


    <!-- ======================================
         BOTÓN
    ======================================= -->

    <button
        type="submit"
        class="btn"
    >
        Confirmar pedido
    </button>


    <a
        href="carrito.php"
        class="btn secondary"
    >
        Volver al carrito
    </a>

</form>


<?php

require 'includes/footer.php';

?>