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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $citaId = (int)($_POST['cita_id'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    $observaciones = trim($_POST['observaciones'] ?? '');

    $estadosPermitidos = [
        'pendiente',
        'confirmada',
        'reprogramada',
        'en_atencion',
        'finalizada',
        'cancelada',
        'no_asistio'
    ];

    if ($citaId > 0 && in_array($estado, $estadosPermitidos, true)) {
        $stmt = $pdo->prepare("\n            UPDATE citas\n            SET estado = ?, observaciones = ?\n            WHERE id = ? AND veterinario_id = ?\n        ");
        $stmt->execute([$estado, $observaciones, $citaId, $veterinarioId]);
        $_SESSION['flash'] = 'Cita actualizada correctamente.';
    }

    header('Location: citas.php');
    exit;
}

$stmt = $pdo->prepare("\n    SELECT\n        c.*,\n        m.nombre AS mascota,\n        m.especie,\n        m.raza,\n        u.nombre AS cliente,\n        u.telefono AS telefono_cliente,\n        s.nombre AS servicio\n    FROM citas c\n    INNER JOIN mascotas m ON m.id = c.mascota_id\n    INNER JOIN usuarios u ON u.id = c.usuario_id\n    INNER JOIN servicios s ON s.id = c.servicio_id\n    WHERE c.veterinario_id = ?\n    ORDER BY\n        CASE WHEN c.fecha_cita >= CURDATE() THEN 0 ELSE 1 END,\n        c.fecha_cita ASC,\n        c.hora_cita ASC\n");
$stmt->execute([$veterinarioId]);
$citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo = 'Mis citas';
require '../includes/header.php';
?>

<h1>Mis citas programadas</h1>
<p class="muted">Consulta las citas asignadas y actualiza su estado u observaciones.</p>

<div class="card" style="overflow-x:auto;">
    <?php if (!$citas): ?>
        <p class="muted">No tienes citas asignadas.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Paciente</th>
                    <th>Propietario</th>
                    <th>Servicio</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Actualizar</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($citas as $cita): ?>
                <tr>
                    <td>
                        <?= e($cita['fecha_cita']) ?><br>
                        <strong><?= e(substr($cita['hora_cita'], 0, 5)) ?></strong>
                    </td>
                    <td>
                        <strong><?= e($cita['mascota']) ?></strong><br>
                        <small class="muted">
                            <?= e($cita['especie']) ?>
                            <?= !empty($cita['raza']) ? ' · ' . e($cita['raza']) : '' ?>
                        </small>
                    </td>
                    <td>
                        <?= e($cita['cliente']) ?>
                        <?php if (!empty($cita['telefono_cliente'])): ?>
                            <br><small class="muted"><?= e($cita['telefono_cliente']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= e($cita['servicio']) ?></td>
                    <td><?= e($cita['motivo'] ?? '') ?></td>
                    <td>
                        <span class="status-badge status-<?= e($cita['estado']) ?>">
                            <?= e(ucfirst(str_replace('_', ' ', $cita['estado']))) ?>
                        </span>
                    </td>
                    <td>
                        <form method="post" class="vet-cita-form">
                            <input type="hidden" name="cita_id" value="<?= (int)$cita['id'] ?>">

                            <select name="estado" required>
                                <?php
                                $opciones = [
                                    'pendiente' => 'Pendiente',
                                    'confirmada' => 'Confirmada',
                                    'reprogramada' => 'Reprogramada',
                                    'en_atencion' => 'En atención',
                                    'finalizada' => 'Finalizada',
                                    'cancelada' => 'Cancelada',
                                    'no_asistio' => 'No asistió'
                                ];
                                foreach ($opciones as $valor => $texto):
                                ?>
                                    <option value="<?= e($valor) ?>" <?= $cita['estado'] === $valor ? 'selected' : '' ?>>
                                        <?= e($texto) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <textarea name="observaciones" rows="2" placeholder="Observaciones de la atención"><?= e($cita['observaciones'] ?? '') ?></textarea>

                            <button type="submit" class="btn">Guardar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>
