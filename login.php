<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/funciones.php';


// Si el usuario ya inició sesión
if (estaLogueado()) {
    header('Location: index.php');
    exit;
}


// Procesar inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT *
        FROM usuarios
        WHERE email = ?
        AND estado = 1
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


    if (
        $usuario &&
        password_verify($password, $usuario['password'])
    ) {

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['rol'];

        $_SESSION['flash'] =
            'Bienvenido(a), ' . $usuario['nombre'] . '.';

        // Redirección según rol
        if ($usuario['rol'] === 'admin') {

            header('Location: admin/index.php');

        } elseif ($usuario['rol'] === 'veterinario') {

            header('Location: veterinario/index.php');

        } else {

            header('Location: index.php');
        }

        exit;
    }


    $_SESSION['flash'] =
        'Correo electrónico o contraseña incorrectos.';

    header('Location: login.php');
    exit;
}


$titulo = 'Iniciar sesión';

require_once __DIR__ . '/includes/header.php';

?>


<form
    class="card"
    method="post"
    autocomplete="on"
>

    <h1>
        Iniciar sesión
    </h1>


    <p class="muted">
        Ingresa a tu cuenta de Avanza.CentroVet.
    </p>


    <div class="form-group">

        <label for="email">
            Correo electrónico
        </label>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="correo@ejemplo.com"
            autocomplete="email"
            required
        >

    </div>


    <div class="form-group">

        <label for="password">
            Contraseña
        </label>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Ingresa tu contraseña"
            autocomplete="current-password"
            required
        >

    </div>


    <button
        type="submit"
        class="btn"
    >
        Ingresar
    </button>


    <p
        class="muted"
        style="margin-top:18px;"
    >
        ¿No tienes una cuenta?

        <a href="<?= APP_URL ?>/registro.php">
            Registrarse
        </a>
    </p>

</form>


<?php

require_once __DIR__ . '/includes/footer.php';

?>
