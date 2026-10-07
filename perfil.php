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
// OBTENER INFORMACIÓN DEL USUARIO
// ==========================================

$st = $pdo->prepare("
    SELECT *
    FROM usuarios
    WHERE id = ?
");

$st->execute([
    $_SESSION['usuario_id']
]);

$u = $st->fetch();


// ==========================================
// OBTENER HISTORIAL DE PEDIDOS
// ==========================================

$st = $pdo->prepare("
    SELECT *
    FROM pedidos
    WHERE usuario_id = ?
    ORDER BY fecha_pedido DESC
");

$st->execute([
    $_SESSION['usuario_id']
]);

$pedidos = $st->fetchAll();


// ==========================================
// TÍTULO
// ==========================================

$titulo = 'Mi perfil';

require 'includes/header.php';

?>


<h1>Mi perfil</h1>

<p class="muted">
    Consulta la información de tu cuenta y tus accesos principales.
</p>


<!-- ==========================================
     INFORMACIÓN DEL PERFIL
========================================== -->

<div class="grid">

    <div class="card">

        <h2>
            <?= e($u['nombre']) ?>
        </h2>


        <p>
            <strong>Correo:</strong><br>
            <?= e($u['email']) ?>
        </p>


        <p>
            <strong>Teléfono:</strong><br>

            <?= !empty($u['telefono'])
                ? e($u['telefono'])
                : 'No registrado'
            ?>
        </p>


        <p>
            <strong>Dirección:</strong><br>

            <?= !empty($u['direccion'])
                ? e($u['direccion'])
                : 'No registrada'
            ?>
        </p>


        <p>
            <strong>Tipo de usuario:</strong><br>

            <?= e(ucfirst($u['rol'])) ?>
        </p>

    </div>


    <!-- ======================================
         ACCESOS RÁPIDOS
    ======================================= -->

    <div class="card">

        <h2>Accesos rápidos</h2>


        <?php if ($u['rol'] === 'cliente'): ?>

            <div class="actions">

                <a
                    class="btn"
                    href="mascotas.php"
                >
                    Mis mascotas
                </a>


                <a
                    class="btn"
                    href="citas.php"
                >
                    Mis citas
                </a>


                <a
                    class="btn secondary"
                    href="productos.php"
                >
                    Productos
                </a>

            </div>


        <?php elseif ($u['rol'] === 'veterinario'): ?>

            <div class="actions">

                <a
                    class="btn"
                    href="veterinario/index.php"
                >
                    Panel veterinario
                </a>


                <a
                    class="btn"
                    href="veterinario/citas.php"
                >
                    Mis citas
                </a>


                <a
                    class="btn secondary"
                    href="veterinario/pacientes.php"
                >
                    Mis pacientes
                </a>

            </div>


        <?php elseif ($u['rol'] === 'admin'): ?>

            <div class="actions">

                <a
                    class="btn"
                    href="admin/index.php"
                >
                    Panel administrativo
                </a>


                <a
                    class="btn secondary"
                    href="admin/usuarios.php"
                >
                    Usuarios
                </a>


                <a
                    class="btn secondary"
                    href="admin/citas.php"
                >
                    Citas
                </a>

            </div>

        <?php endif; ?>

    </div>

</div>


<!-- ==========================================
     HISTORIAL DE PEDIDOS
========================================== -->

<?php if ($u['rol'] === 'cliente'): ?>

    <h2>Historial de pedidos</h2>


    <div class="card">

        <?php if (!empty($pedidos)): ?>

            <div style="overflow-x:auto;">

                <table>

                    <thead>

                        <tr>
                            <th>Pedido</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($pedidos as $p): ?>

                            <tr>

                                <td>
                                    #<?= (int)$p['id'] ?>
                                </td>


                                <td>
                                    <?= e($p['fecha_pedido']) ?>
                                </td>


                                <td>
                                    Q<?= number_format(
                                        (float)$p['total'],
                                        2
                                    ) ?>
                                </td>


                                <td>
                                    <?= e(
                                        ucfirst($p['estado'])
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


        <?php else: ?>

            <p class="muted">
                Todavía no tienes pedidos registrados.
            </p>

        <?php endif; ?>

    </div>

<?php endif; ?>


<?php

require 'includes/footer.php';

?>