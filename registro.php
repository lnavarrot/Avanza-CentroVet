<?php

require 'includes/config.php';
require 'includes/funciones.php';


// ==========================================
// PROCESAR REGISTRO
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');


    // ======================================
    // VALIDACIONES
    // ======================================

    if (
        $nombre !== '' &&
        filter_var($email, FILTER_VALIDATE_EMAIL) &&
        strlen($password) >= 6
    ) {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO usuarios
                (
                    nombre,
                    email,
                    password,
                    rol,
                    telefono,
                    direccion,
                    estado
                )
                VALUES (?, ?, ?, 'cliente', ?, ?, 1)
            ");

            $stmt->execute([
                $nombre,
                $email,
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
                $telefono,
                $direccion
            ]);


            $_SESSION['flash'] =
                'Cuenta creada correctamente. Ya puedes iniciar sesión.';

            header('Location: login.php');
            exit;


        } catch (PDOException $e) {

            $_SESSION['flash'] =
                'El correo electrónico ya está registrado.';
        }

    } else {

        $_SESSION['flash'] =
            'Completa los campos correctamente. La contraseña debe tener al menos 6 caracteres.';
    }
}


$titulo = 'Registro';

require 'includes/header.php';

?>


<form
    class="card"
    method="post"
    autocomplete="on"
>

    <h1>Crear cuenta</h1>

    <p class="muted">
        Regístrate para administrar tus mascotas,
        agendar citas y realizar compras.
    </p>


    <!-- NOMBRE -->

    <div class="form-group">

        <label for="nombre">
            Nombre completo
        </label>

        <input
            type="text"
            id="nombre"
            name="nombre"
            autocomplete="name"
            required
        >

    </div>


    <!-- CORREO -->

    <div class="form-group">

        <label for="email">
            Correo electrónico
        </label>

        <input
            type="email"
            id="email"
            name="email"
            autocomplete="email"
            required
        >

    </div>


    <!-- TELÉFONO -->

    <div class="form-group">

        <label for="telefono">
            Teléfono
        </label>

        <input
            type="text"
            id="telefono"
            name="telefono"
            autocomplete="tel"
        >

    </div>


    <!-- DIRECCIÓN -->

    <div class="form-group">

        <label for="direccion">
            Dirección
        </label>

        <textarea
            id="direccion"
            name="direccion"
            autocomplete="street-address"
        ></textarea>

    </div>


    <!-- CONTRASEÑA -->

    <div class="form-group">

        <label for="password">
            Contraseña
        </label>

        <input
            type="password"
            id="password"
            name="password"
            minlength="6"
            autocomplete="new-password"
            required
        >

        <small class="muted">
            Debe contener al menos 6 caracteres.
        </small>

    </div>


    <button
        type="submit"
        class="btn"
    >
        Crear cuenta
    </button>


    <p
        class="muted"
        style="margin-top:18px;"
    >
        ¿Ya tienes una cuenta?

        <a href="login.php">
            Iniciar sesión
        </a>
    </p>

</form>


<?php

require 'includes/footer.php';

?>