<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/funciones.php';

?>

<!doctype html>

<html lang="es">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?= e($titulo ?? APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= APP_URL ?>/assets/css/style.css?v=21"
    >

</head>

<body>

<header class="top">

    <div class="wrap nav">

        <!-- ==================================================
             LOGO
        =================================================== -->

        <a
            class="brand"
            href="<?= APP_URL ?>/index.php"
        >

            <img
                src="<?= APP_URL ?>/assets/img/logo-avanza.jpeg"
                alt="Logo Avanza.CentroVet"
                class="brand-logo"
            >

            <span class="brand-text">
                Avanza.CentroVet
            </span>

        </a>


        <!-- ==================================================
             MENÚ PRINCIPAL
        =================================================== -->

        <nav>


            <?php if (estaLogueado() && esAdmin()): ?>


                <!-- ==========================================
                     MENÚ ADMINISTRADOR
                =========================================== -->

                <a href="<?= APP_URL ?>/productos.php">
                    Productos
                </a>

                <a href="<?= APP_URL ?>/servicios.php">
                    Servicios
                </a>

                <a href="<?= APP_URL ?>/mascotas.php">
                    Mascotas
                </a>

                <a href="<?= APP_URL ?>/admin/reportes.php">
                    Reportes
                </a>

                <a href="<?= APP_URL ?>/perfil.php">
                    Mi perfil
                </a>

                <a href="<?= APP_URL ?>/admin/index.php">
                    Admin
                </a>

                <a href="<?= APP_URL ?>/logout.php">
                    Salir
                </a>


            <?php elseif (estaLogueado() && esVeterinario()): ?>


                <!-- ==========================================
                     MENÚ VETERINARIO
                =========================================== -->

                <a href="<?= APP_URL ?>/veterinario/index.php">
                    Panel
                </a>

                <a href="<?= APP_URL ?>/veterinario/citas.php">
                    Mis citas
                </a>

                <a href="<?= APP_URL ?>/veterinario/pacientes.php">
                    Pacientes
                </a>

                <a href="<?= APP_URL ?>/perfil.php">
                    Mi perfil
                </a>

                <a href="<?= APP_URL ?>/logout.php">
                    Salir
                </a>


            <?php elseif (estaLogueado()): ?>


                <!-- ==========================================
                     MENÚ CLIENTE
                =========================================== -->

                <a href="<?= APP_URL ?>/productos.php">
                    Productos
                </a>

                <a href="<?= APP_URL ?>/servicios.php">
                    Servicios
                </a>

                <a href="<?= APP_URL ?>/citas.php">
                    Agendar cita
                </a>

                <a href="<?= APP_URL ?>/mascotas.php">
                    Mis mascotas
                </a>

                <a href="<?= APP_URL ?>/perfil.php">
                    Mi perfil
                </a>

                <a href="<?= APP_URL ?>/carrito.php">
                    Carrito (<?= carritoCount() ?>)
                </a>

                <a href="<?= APP_URL ?>/logout.php">
                    Salir
                </a>


            <?php else: ?>


                <!-- ==========================================
                     MENÚ USUARIO NO AUTENTICADO
                =========================================== -->

                <a href="<?= APP_URL ?>/productos.php">
                    Productos
                </a>

                <a href="<?= APP_URL ?>/servicios.php">
                    Servicios
                </a>

                <a href="<?= APP_URL ?>/citas.php">
                    Agendar cita
                </a>

                <a href="<?= APP_URL ?>/login.php">
                    Ingresar
                </a>

                <a href="<?= APP_URL ?>/registro.php">
                    Registrarse
                </a>

                <a href="<?= APP_URL ?>/carrito.php">
                    Carrito (<?= carritoCount() ?>)
                </a>


            <?php endif; ?>


        </nav>

    </div>

</header>


<!-- ==================================================
     CONTENIDO PRINCIPAL
=================================================== -->

<main class="wrap">


    <!-- ==============================================
         MENSAJES DEL SISTEMA
    =============================================== -->

    <?php if (!empty($_SESSION['flash'])): ?>

        <div class="flash">

            <?= e($_SESSION['flash']) ?>

        </div>

        <?php unset($_SESSION['flash']); ?>

    <?php endif; ?>