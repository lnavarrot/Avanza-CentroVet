<?php

require '../includes/config.php';
require '../includes/funciones.php';

if (!estaLogueado() || !esVeterinario()) {
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM veterinarios WHERE usuario_id = ? AND estado = 1 LIMIT 1");
$stmt->execute([$_SESSION['usuario_id']]);
$veterinario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$veterinario) {
    $_SESSION['flash'] = 'No se encontró un perfil veterinario activo.';
    header('Location: ../index.php');
    exit;
}

$veterinarioId = (int)$veterinario['id'];

$stmt = $pdo->prepare("\n    SELECT\n        m.id,\n        m.nombre,\n        m.especie,\n        m.raza,\n        m.sexo,\n        m.fecha_nacimiento,\n        m.peso,\n        m.alergias,\n        m.observaciones,\n        u.nombre AS propietario,\n        u.telefono AS telefono_propietario,\n        MAX(c.fecha_cita) AS ultima_cita,\n        COUNT(c.id) AS total_citas\n    FROM mascotas m\n    INNER JOIN citas c ON c.mascota_id = m.id\n    INNER JOIN usuarios u ON u.id = m.usuario_id\n    WHERE c.veterinario_id = ?\n    GROUP BY\n        m.id, m.nombre, m.especie, m.raza, m.sexo, m.fecha_nacimiento,\n        m.peso, m.alergias, m.observaciones, u.nombre, u.telefono\n    ORDER BY m.nombre ASC\n");
$stmt->execute([$veterinarioId]);
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Pacientes';
require '../includes/header.php';
?>

<h1>Mis pacientes</h1>
<p class="muted">Mascotas que tienen o han tenido citas asignadas contigo.</p>

<div class="grid pacientes-grid">
<?php foreach ($pacientes as $paciente): ?>
    <article class="card mascota-card paciente-card">
        <div class="mascota-icono">🐾</div>

        <h3><?= e($paciente['nombre']) ?></h3>
        <p><strong>Especie:</strong> <?= e($paciente['especie']) ?></p>
        <p><strong>Raza:</strong> <?= e($paciente['raza'] ?: 'No especificada') ?></p>
        <p><strong>Sexo:</strong> <?= e($paciente['sexo']) ?></p>
        <p>
            <strong>Peso:</strong>
            <?= $paciente['peso'] !== null ? e($paciente['peso']) . ' kg' : 'No registrado' ?>
        </p>

        <?php if (!empty($paciente['fecha_nacimiento'])): ?>
            <p><strong>Fecha de nacimiento:</strong> <?= e($paciente['fecha_nacimiento']) ?></p>
        <?php endif; ?>

        <hr class="soft-rule">

        <p><strong>Propietario:</strong> <?= e($paciente['propietario']) ?></p>
        <?php if (!empty($paciente['telefono_propietario'])): ?>
            <p class="muted">Teléfono: <?= e($paciente['telefono_propietario']) ?></p>
        <?php endif; ?>

        <?php if (!empty($paciente['alergias'])): ?>
            <p><strong>Alergias:</strong> <?= e($paciente['alergias']) ?></p>
        <?php endif; ?>

        <?php if (!empty($paciente['observaciones'])): ?>
            <p><strong>Observaciones:</strong> <?= e($paciente['observaciones']) ?></p>
        <?php endif; ?>

        <p class="muted">
            Total de citas: <?= (int)$paciente['total_citas'] ?><br>
            Última cita: <?= e($paciente['ultima_cita'] ?? 'Sin registro') ?>
        </p>
    </article>
<?php endforeach; ?>
</div>

<?php if (!$pacientes): ?>
    <div class="card" style="margin-top:20px;">
        <p class="muted">Todavía no tienes pacientes asignados.</p>
    </div>
<?php endif; ?>

<?php require '../includes/footer.php'; ?>
