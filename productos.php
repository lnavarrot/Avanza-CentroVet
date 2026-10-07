<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/funciones.php';

$titulo = 'Productos';

require_once __DIR__ . '/includes/header.php';


// ==========================================
// FILTROS
// ==========================================

$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);


// ==========================================
// CONSULTA DE PRODUCTOS
// ==========================================

$sql = "
    SELECT
        p.*,
        c.nombre AS categoria
    FROM productos p
    LEFT JOIN categorias c
        ON c.id = p.categoria_id
    WHERE p.activo = 1
";

$args = [];


// ==========================================
// BÚSQUEDA
// ==========================================

if ($q !== '') {

    $sql .= "
        AND (
            p.nombre LIKE ?
            OR p.descripcion LIKE ?
        )
    ";

    $args[] = "%{$q}%";
    $args[] = "%{$q}%";
}


// ==========================================
// FILTRO POR CATEGORÍA
// ==========================================

if ($cat > 0) {

    $sql .= "
        AND p.categoria_id = ?
    ";

    $args[] = $cat;
}


$sql .= " ORDER BY p.nombre ASC";


$stmt = $pdo->prepare($sql);

$stmt->execute($args);

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// CATEGORÍAS
// ==========================================

$categorias = $pdo->query("
    SELECT *
    FROM categorias
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>


<div class="catalogo-contenedor">

    <h1>
        Catálogo de productos
    </h1>


    <p class="muted">
        Encuentra alimentos, accesorios, productos de higiene
        y artículos para el bienestar de tu mascota.
    </p>


    <!-- ==========================================
         FILTROS
    ========================================== -->

    <form
        class="card catalogo-filtros"
        method="get"
    >

        <div class="grid">

            <div>

                <label>
                    Buscar
                </label>

                <input
                    type="text"
                    name="q"
                    value="<?= e($q) ?>"
                    placeholder="Alimento, collar, shampoo..."
                >

            </div>


            <div>

                <label>
                    Categoría
                </label>

                <select name="cat">

                    <option value="0">
                        Todas
                    </option>

                    <?php foreach ($categorias as $categoria): ?>

                        <option
                            value="<?= (int)$categoria['id'] ?>"
                            <?= $cat === (int)$categoria['id']
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= e($categoria['nombre']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>


        <button
            type="submit"
            class="btn"
        >
            Filtrar
        </button>

    </form>



    <!-- ==========================================
         PRODUCTOS
    ========================================== -->

    <div class="catalogo-grid">


        <?php foreach ($productos as $p): ?>


            <?php

            // ==========================================
            // VALIDAR IMAGEN
            // ==========================================

            $imagenDisponible = false;
            $urlImagen = '';

            if (!empty($p['imagen'])) {

                $nombreImagen = basename($p['imagen']);

                $rutaFisica =
                    __DIR__ .
                    '/uploads/productos/' .
                    $nombreImagen;

                if (file_exists($rutaFisica)) {

                    $imagenDisponible = true;

                    $urlImagen =
                        APP_URL .
                        '/uploads/productos/' .
                        rawurlencode($nombreImagen);
                }
            }

            ?>


            <article class="catalogo-card">


                <!-- ======================================
                     IMAGEN
                ======================================= -->

                <div class="catalogo-img">


                    <?php if ($imagenDisponible): ?>

                        <img
                            src="<?= e($urlImagen) ?>"
                            alt="<?= e($p['nombre']) ?>"
                            loading="lazy"
                            onerror="
                                this.style.display='none';
                                this.nextElementSibling.style.display='flex';
                            "
                        >

                        <div
                            class="catalogo-placeholder"
                            style="display:none;"
                        >
                            🐾
                        </div>


                    <?php else: ?>

                        <div class="catalogo-placeholder">
                            🐾
                        </div>

                    <?php endif; ?>


                </div>



                <!-- ======================================
                     CATEGORÍA
                ======================================= -->

                <small class="catalogo-categoria">

                    <?= e(
                        $p['categoria']
                        ?? 'Sin categoría'
                    ) ?>

                </small>



                <!-- ======================================
                     NOMBRE
                ======================================= -->

                <h3 class="catalogo-nombre">

                    <?= e($p['nombre']) ?>

                </h3>



                <!-- ======================================
                     DESCRIPCIÓN
                ======================================= -->

                <p class="catalogo-descripcion">

                    <?= e(
                        $p['descripcion']
                        ?? ''
                    ) ?>

                </p>



                <!-- ======================================
                     PRECIO
                ======================================= -->

                <div class="catalogo-precio">

                    Q<?= number_format(
                        (float)$p['precio'],
                        2
                    ) ?>

                </div>



                <!-- ======================================
                     STOCK
                ======================================= -->

                <?php if ((int)$p['stock'] > 0): ?>

                    <p class="catalogo-stock">

                        Stock:
                        <?= (int)$p['stock'] ?>

                    </p>

                <?php else: ?>

                    <p class="
                        catalogo-stock
                        catalogo-agotado
                    ">
                        Producto agotado
                    </p>

                <?php endif; ?>



                <!-- ======================================
                     CARRITO
                ======================================= -->

                <form
                    method="post"
                    action="carrito.php"
                    class="catalogo-carrito"
                >

                    <input
                        type="hidden"
                        name="accion"
                        value="agregar"
                    >

                    <input
                        type="hidden"
                        name="producto_id"
                        value="<?= (int)$p['id'] ?>"
                    >


                    <label>
                        Cantidad
                    </label>


                    <input
                        type="number"
                        name="cantidad"
                        min="1"
                        max="<?= max(
                            1,
                            (int)$p['stock']
                        ) ?>"
                        value="1"
                        <?= (int)$p['stock'] <= 0
                            ? 'disabled'
                            : ''
                        ?>
                    >


                    <button
                        type="submit"
                        class="btn"
                        <?= (int)$p['stock'] <= 0
                            ? 'disabled'
                            : ''
                        ?>
                    >

                        <?= (int)$p['stock'] <= 0
                            ? 'Agotado'
                            : 'Agregar al carrito'
                        ?>

                    </button>

                </form>


            </article>


        <?php endforeach; ?>


    </div>



    <!-- ==========================================
         SIN RESULTADOS
    ========================================== -->

    <?php if (empty($productos)): ?>

        <div
            class="card"
            style="margin-top:20px;"
        >

            <h3>
                No se encontraron productos
            </h3>

            <p class="muted">
                Intenta con otra búsqueda
                o selecciona una categoría diferente.
            </p>

        </div>

    <?php endif; ?>


</div>


<?php

require_once __DIR__ . '/includes/footer.php';

?>