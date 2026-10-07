<?php

require '../includes/config.php';
require '../includes/funciones.php';

if (!estaLogueado() || !esVeterinario()) {
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->prepare("\n    SELECT v.id, v.especialidad, v.numero_colegiado, u.nombre\n    FROM veterinarios v\n    INNER JOIN usuarios u ON u.id = v.usuario_id\n    WHERE v.usuario_id = ?\n      AND v.estado = 1\n      AND u.estado = 1\n    LIMIT 1\n");
$stmt->execute([$_SESSION['usuario_id']]);
$veterinario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$veterinario) {
    $_SESSION['flash'] = 'No se encontró un perfil veterinario activo para este usuario.';
    header('Location: ../index.php');
    exit;
}

$veterinarioId = (int)$veterinario['id'];

$stmt = $pdo->prepare("\n    SELECT COUNT(*)\n    FROM citas\n    WHERE veterinario_id = ?\n      AND estado IN ('pendiente','confirmada','reprogramada','en_atencion')\n");
$stmt->execute([$veterinarioId]);
$citasProgramadas = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("\n    SELECT COUNT(*)\n    FROM citas\n    WHERE veterinario_id = ?\n      AND fecha_cita = CURDATE()\n      AND estado NOT IN ('cancelada','no_asistio')\n");
$stmt->execute([$veterinarioId]);
$citasHoy = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("\n    SELECT COUNT(DISTINCT mascota_id)\n    FROM citas\n    WHERE veterinario_id = ?\n");
$stmt->execute([$veterinarioId]);
$pacientes = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("\n    SELECT\n        c.id,\n        c.fecha_cita,\n        c.hora_cita,\n        c.estado,\n        c.motivo,\n        m.nombre AS mascota,\n        m.especie,\n        m.raza,\n        u.nombre AS cliente,\n        s.nombre AS servicio\n    FROM citas c\n    INNER JOIN mascotas m ON m.id = c.mascota_id\n    INNER JOIN usuarios u ON u.id = c.usuario_id\n    INNER JOIN servicios s ON s.id = c.servicio_id\n    WHERE c.veterinario_id = ?\n      AND c.estado NOT IN ('finalizada','cancelada','no_asistio')\n    ORDER BY c.fecha_cita ASC, c.hora_cita ASC\n    LIMIT 8\n");
$stmt->execute([$veterinarioId]);
$proximasCitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Panel veterinario';
require '../includes/header.php';
?>

<h1>Panel veterinario</h1>
<p class="muted">
    Bienvenido(a), <?= e($veterinario['nombre']) ?>.
    <?php if (!empty($veterinario['especialidad'])): ?>
        Especialidad: <?= e($veterinario['especialidad']) ?>.
    <?php endif; ?>
</p>

<div class="stats vet-stats">
    <div class="stat">
        <span class="muted">Citas programadas</span><br>
        <strong><?= $citasProgramadas ?></strong>
    </div>

    <div class="stat">
        <span class="muted">Citas de hoy</span><br>
        <strong><?= $citasHoy ?></strong>
    </div>

    <div class="stat">
        <span class="muted">Pacientes</span><br>
        <strong><?= $pacientes ?></strong>
    </div>
</div>

<div class="actions" style="margin:22px 0;">
    <a class="btn" href="citas.php">Ver mis citas</a>
    <a class="btn secondary" href="pacientes.php">Ver pacientes</a>
</div>

<div class="card">
    <div class="section-head">
        <div>
            <h2>Próximas citas</h2>
            <p class="muted">Citas pendientes o confirmadas asignadas a tu agenda.</p>
        </div>
        <a class="btn secondary" href="citas.php">Ver todas</a>
    </div>

    <?php if (!$proximasCitas): ?>
        <p class="muted">No tienes citas programadas actualmente.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Paciente</th>
                        <th>Propietario</th>
                        <th>Servicio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($proximasCitas as $cita): ?>
                    <tr>
                        <td><?= e($cita['fecha_cita']) ?></td>
                        <td><?= e(substr($cita['hora_cita'], 0, 5)) ?></td>
                        <td>
                            <strong><?= e($cita['mascota']) ?></strong><br>
                            <small class="muted">
                                <?= e($cita['especie']) ?>
                                <?= !empty($cita['raza']) ? ' · ' . e($cita['raza']) : '' ?>
                            </small>
                        </td>
                        <td><?= e($cita['cliente']) ?></td>
                        <td><?= e($cita['servicio']) ?></td>
                        <td>
                            <span class="status-badge status-<?= e($cita['estado']) ?>">
                                <?= e(ucfirst(str_replace('_', ' ', $cita['estado']))) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>
