<?php

require '../includes/config.php';
require '../includes/funciones.php';

if (!esAdmin()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? '';

    // =========================================
    // CREAR PRODUCTO
    // =========================================
    if ($accion === 'crear') {

        $categoria_id = (int)($_POST['categoria_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = (float)($_POST['precio'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $stock_minimo = (int)($_POST['stock_minimo'] ?? 5);

        $imagen = null;

        // =========================================
        // SUBIR IMAGEN
        // =========================================
        if (
            isset($_FILES['imagen']) &&
            $_FILES['imagen']['error'] === UPLOAD_ERR_OK
        ) {

            $extensionesPermitidas = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            $extension = strtolower(
                pathinfo(
                    $_FILES['imagen']['name'],
                    PATHINFO_EXTENSION
                )
            );

            if (!in_array($extension, $extensionesPermitidas)) {
                $_SESSION['flash'] = 'Formato de imagen no permitido.';
                header('Location: productos.php');
                exit;
            }

            $nombreImagen =
                'producto_' .
                time() .
                '_' .
                uniqid() .
                '.' .
                $extension;

            $carpetaDestino =
                '../uploads/productos/';

            // Crear carpeta si no existe
            if (!is_dir($carpetaDestino)) {
                mkdir(
                    $carpetaDestino,
                    0777,
                    true
                );
            }

            $rutaDestino =
                $carpetaDestino .
                $nombreImagen;

            if (
                move_uploaded_file(
                    $_FILES['imagen']['tmp_name'],
                    $rutaDestino
                )
            ) {
                $imagen = $nombreImagen;
            }
        }

        // =========================================
        // INSERT PRODUCTO
        // =========================================

        $st = $pdo->prepare("
            INSERT INTO productos
            (
                categoria_id,
                nombre,
                descripcion,
                imagen,
                precio,
                stock,
                stock_minimo
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $st->execute([
            $categoria_id,
            $nombre,
            $descripcion,
            $imagen,
            $precio,
            $stock,
            $stock_minimo
        ]);

        $_SESSION['flash'] =
            'Producto creado correctamente.';
    }

    // =========================================
    // EDITAR PRODUCTO
    // =========================================
    if ($accion === 'editar') {

        $id = (int)($_POST['id'] ?? 0);
        $categoria_id =
            (int)($_POST['categoria_id'] ?? 0);

        $nombre =
            trim($_POST['nombre'] ?? '');

        $descripcion =
            trim($_POST['descripcion'] ?? '');

        $precio =
            (float)($_POST['precio'] ?? 0);

        $stock =
            (int)($_POST['stock'] ?? 0);

        $stock_minimo =
            (int)($_POST['stock_minimo'] ?? 5);

        // Obtener imagen existente
        $st = $pdo->prepare("
            SELECT imagen
            FROM productos
            WHERE id = ?
        ");

        $st->execute([$id]);

        $productoActual =
            $st->fetch(PDO::FETCH_ASSOC);

        $imagen =
            $productoActual['imagen'] ?? null;

        // =========================================
        // NUEVA IMAGEN
        // =========================================
        if (
            isset($_FILES['imagen']) &&
            $_FILES['imagen']['error'] === UPLOAD_ERR_OK
        ) {

            $extensionesPermitidas = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            $extension = strtolower(
                pathinfo(
                    $_FILES['imagen']['name'],
                    PATHINFO_EXTENSION
                )
            );

            if (
                in_array(
                    $extension,
                    $extensionesPermitidas
                )
            ) {

                $nombreImagen =
                    'producto_' .
                    time() .
                    '_' .
                    uniqid() .
                    '.' .
                    $extension;

                $carpetaDestino =
                    '../uploads/productos/';

                if (!is_dir($carpetaDestino)) {
                    mkdir(
                        $carpetaDestino,
                        0777,
                        true
                    );
                }

                $rutaDestino =
                    $carpetaDestino .
                    $nombreImagen;

                if (
                    move_uploaded_file(
                        $_FILES['imagen']['tmp_name'],
                        $rutaDestino
                    )
                ) {

                    // Eliminar imagen anterior
                    if (
                        !empty($imagen) &&
                        file_exists(
                            $carpetaDestino .
                            $imagen
                        )
                    ) {

                        unlink(
                            $carpetaDestino .
                            $imagen
                        );
                    }

                    $imagen =
                        $nombreImagen;
                }
            }
        }

        // =========================================
        // UPDATE PRODUCTO
        // =========================================

        $st = $pdo->prepare("
            UPDATE productos

            SET categoria_id = ?,
                nombre = ?,
                descripcion = ?,
                imagen = ?,
                precio = ?,
                stock = ?,
                stock_minimo = ?

            WHERE id = ?
        ");

        $st->execute([
            $categoria_id,
            $nombre,
            $descripcion,
            $imagen,
            $precio,
            $stock,
            $stock_minimo,
            $id
        ]);

        $_SESSION['flash'] =
            'Producto actualizado correctamente.';
    }

    // =========================================
    // DESACTIVAR PRODUCTO
    // =========================================
    if ($accion === 'eliminar') {

        $st = $pdo->prepare("
            UPDATE productos
            SET activo = 0
            WHERE id = ?
        ");

        $st->execute([
            (int)$_POST['id']
        ]);

        $_SESSION['flash'] =
            'Producto desactivado.';
    }

    // =========================================
    // ACTIVAR PRODUCTO
    // =========================================
    if ($accion === 'activar') {

        $st = $pdo->prepare("
            UPDATE productos
            SET activo = 1
            WHERE id = ?
        ");

        $st->execute([
            (int)$_POST['id']
        ]);

        $_SESSION['flash'] =
            'Producto activado.';
    }

    header('Location: productos.php');
    exit;
}

// =========================================
// OBTENER PRODUCTOS
// =========================================

$productos = $pdo->query("
    SELECT
        p.*,
        c.nombre AS categoria

    FROM productos p

    LEFT JOIN categorias c
        ON c.id = p.categoria_id

    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// =========================================
// OBTENER CATEGORÍAS
// =========================================

$categorias = $pdo->query("
    SELECT *
    FROM categorias
    ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Admin productos';

require '../includes/header.php';

?>

<h1>Gestión de productos</h1>


<!-- =========================================
     CREAR PRODUCTO
========================================= -->

<form
    class="card"
    method="post"
    enctype="multipart/form-data"
>

    <input
        type="hidden"
        name="accion"
        value="crear"
    >

    <h2>Nuevo producto</h2>

    <div class="grid">

        <div>

            <label>Nombre</label>

            <input
                type="text"
                name="nombre"
                required
            >

        </div>


        <div>

            <label>Categoría</label>

            <select
                name="categoria_id"
                required
            >

                <?php foreach (
                    $categorias as $categoria
                ): ?>

                    <option
                        value="<?= $categoria['id'] ?>"
                    >
                        <?= e($categoria['nombre']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div>

            <label>Precio</label>

            <input
                type="number"
                step="0.01"
                min="0"
                name="precio"
                required
            >

        </div>


        <div>

            <label>Stock</label>

            <input
                type="number"
                min="0"
                name="stock"
                required
            >

        </div>


        <div>

            <label>Stock mínimo</label>

            <input
                type="number"
                min="0"
                name="stock_minimo"
                value="5"
            >

        </div>

    </div>


    <div class="form-group">

        <label>Descripción</label>

        <textarea
            name="descripcion"
            rows="4"
        ></textarea>

    </div>


    <!-- IMAGEN -->

    <div class="form-group">

        <label>
            Imagen del producto
        </label>

        <input
            type="file"
            name="imagen"
            accept=".jpg,.jpeg,.png,.webp"
        >

        <small>
            Formatos permitidos:
            JPG, JPEG, PNG y WEBP
        </small>

    </div>


    <button class="btn">
        Crear producto
    </button>

</form>


<!-- =========================================
     LISTADO DE PRODUCTOS
========================================= -->

<div class="card">

    <h2>Productos registrados</h2>

    <div style="overflow-x:auto;">

        <table>

            <thead>

                <tr>

                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Stock mínimo</th>
                    <th>Estado</th>
                    <th>Acciones</th>

                </tr>

            </thead>


            <tbody>

            <?php foreach (
                $productos as $producto
            ): ?>

                <tr>

                    <!-- IMAGEN -->

                    <td>

                    <?php if (
                        !empty($producto['imagen'])
                    ): ?>

                        <img
                            src="../uploads/productos/<?= e($producto['imagen']) ?>"
                            alt="<?= e($producto['nombre']) ?>"
                            style="
                                width:80px;
                                height:80px;
                                object-fit:contain;
                                border-radius:8px;
                                background:#eef5f4;
                                padding:5px;
                            "
                        >

                    <?php else: ?>

                        <div
                            style="
                                width:80px;
                                height:80px;
                                background:#eef5f4;
                                display:flex;
                                justify-content:center;
                                align-items:center;
                                border-radius:8px;
                                font-size:28px;
                            "
                        >
                            🐾
                        </div>

                    <?php endif; ?>

                    </td>


                    <td>
                        <?= e($producto['nombre']) ?>
                    </td>


                    <td>
                        <?= e(
                            $producto['categoria']
                            ?? 'Sin categoría'
                        ) ?>
                    </td>


                    <td>

                        Q<?= number_format(
                            $producto['precio'],
                            2
                        ) ?>

                    </td>


                    <td>

                        <?= $producto['stock'] ?>

                    </td>


                    <td>

                        <?= $producto['stock_minimo'] ?>

                    </td>


                    <td>

                    <?php if (
                        $producto['activo']
                    ): ?>

                        <span
                            style="
                                color:green;
                                font-weight:bold;
                            "
                        >
                            Activo
                        </span>

                    <?php else: ?>

                        <span
                            style="
                                color:red;
                                font-weight:bold;
                            "
                        >
                            Inactivo
                        </span>

                    <?php endif; ?>

                    </td>


                    <td>

                        <!-- EDITAR -->

                        <button
                            type="button"
                            class="btn"
                            onclick='editarProducto(
                                <?= json_encode(
                                    $producto,
                                    JSON_HEX_APOS |
                                    JSON_HEX_QUOT
                                ) ?>
                            )'
                        >
                            Editar
                        </button>


                        <!-- DESACTIVAR -->

                        <?php if (
                            $producto['activo']
                        ): ?>

                            <form
                                method="post"
                                style="display:inline;"
                            >

                                <input
                                    type="hidden"
                                    name="accion"
                                    value="eliminar"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $producto['id'] ?>"
                                >

                                <button
                                    class="btn danger"
                                >
                                    Desactivar
                                </button>

                            </form>

                        <?php else: ?>

                            <!-- ACTIVAR -->

                            <form
                                method="post"
                                style="display:inline;"
                            >

                                <input
                                    type="hidden"
                                    name="accion"
                                    value="activar"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $producto['id'] ?>"
                                >

                                <button class="btn">
                                    Activar
                                </button>

                            </form>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>


<!-- =========================================
     MODAL EDITAR PRODUCTO
========================================= -->

<div
    id="modalEditarProducto"
    style="
        display:none;
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background:rgba(0,0,0,.5);
        z-index:1000;
        align-items:center;
        justify-content:center;
    "
>

    <div
        class="card"
        style="
            width:90%;
            max-width:700px;
            max-height:90vh;
            overflow-y:auto;
        "
    >

        <h2>Editar producto</h2>


        <form
            method="post"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="accion"
                value="editar"
            >

            <input
                type="hidden"
                name="id"
                id="edit_id"
            >


            <div class="grid">

                <div>

                    <label>Nombre</label>

                    <input
                        type="text"
                        name="nombre"
                        id="edit_nombre"
                        required
                    >

                </div>


                <div>

                    <label>Categoría</label>

                    <select
                        name="categoria_id"
                        id="edit_categoria"
                    >

                        <?php foreach (
                            $categorias as $categoria
                        ): ?>

                            <option
                                value="<?= $categoria['id'] ?>"
                            >
                                <?= e($categoria['nombre']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div>

                    <label>Precio</label>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="precio"
                        id="edit_precio"
                        required
                    >

                </div>


                <div>

                    <label>Stock</label>

                    <input
                        type="number"
                        min="0"
                        name="stock"
                        id="edit_stock"
                        required
                    >

                </div>


                <div>

                    <label>Stock mínimo</label>

                    <input
                        type="number"
                        min="0"
                        name="stock_minimo"
                        id="edit_stock_minimo"
                    >

                </div>

            </div>


            <div class="form-group">

                <label>Descripción</label>

                <textarea
                    name="descripcion"
                    id="edit_descripcion"
                    rows="4"
                ></textarea>

            </div>


            <div class="form-group">

                <label>
                    Imagen actual
                </label>

                <div id="imagenActual"></div>

            </div>


            <div class="form-group">

                <label>
                    Cambiar imagen
                </label>

                <input
                    type="file"
                    name="imagen"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <small>
                    Si no seleccionas una nueva,
                    se conservará la anterior.
                </small>

            </div>


            <button class="btn">
                Guardar cambios
            </button>


            <button
                type="button"
                class="btn danger"
                onclick="cerrarEditarProducto()"
            >
                Cancelar
            </button>

        </form>

    </div>

</div>


<script>

function editarProducto(producto) {

    document.getElementById(
        'edit_id'
    ).value = producto.id;

    document.getElementById(
        'edit_nombre'
    ).value = producto.nombre;

    document.getElementById(
        'edit_categoria'
    ).value = producto.categoria_id;

    document.getElementById(
        'edit_precio'
    ).value = producto.precio;

    document.getElementById(
        'edit_stock'
    ).value = producto.stock;

    document.getElementById(
        'edit_stock_minimo'
    ).value =
        producto.stock_minimo;

    document.getElementById(
        'edit_descripcion'
    ).value =
        producto.descripcion || '';

    let contenedor =
        document.getElementById(
            'imagenActual'
        );

    if (producto.imagen) {

        contenedor.innerHTML =
            '<img src="../uploads/productos/' +
            producto.imagen +
            '" style="' +
            'width:150px;' +
            'height:150px;' +
            'object-fit:contain;' +
            'background:#eef5f4;' +
            'padding:10px;' +
            'border-radius:10px;' +
            '">';

    } else {

        contenedor.innerHTML =
            '<p>Sin imagen</p>';
    }

    document.getElementById(
        'modalEditarProducto'
    ).style.display = 'flex';
}


function cerrarEditarProducto() {

    document.getElementById(
        'modalEditarProducto'
    ).style.display = 'none';
}

</script>


<?php

require '../includes/footer.php';

?>