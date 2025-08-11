<?php
/**
 * Sistema de Recomendaciones para 2 usuarios
 * - Recomendaciones bidireccionales
 * - Similitud de coseno
 */

function similitudCoseno($vec1, $vec2) {
    $productoEscalar = $magnitud1 = $magnitud2 = 0;
    for ($i = 0; $i < count($vec1); $i++) {
        $productoEscalar += $vec1[$i] * $vec2[$i];
        $magnitud1 += pow($vec1[$i], 2);
        $magnitud2 += pow($vec2[$i], 2);
    }
    $magnitud1 = sqrt($magnitud1);
    $magnitud2 = sqrt($magnitud2);
    return ($magnitud1 == 0 || $magnitud2 == 0) ? 0 : $productoEscalar / ($magnitud1 * $magnitud2);
}

$productos = ["Camiseta", "Pantalón", "Zapatos", "Gorra", "Reloj"];

// Inicializar
$usuario1 = [0, 0, 0, 0, 0];
$usuario2 = [0, 0, 0, 0, 0];
$resultado = "";
$rec_usuario1 = [];
$rec_usuario2 = [];
$error = "";
$mostrarResultados = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $raw1 = $_POST['usuario1'] ?? [];
    $raw2 = $_POST['usuario2'] ?? [];

    if (count($raw1) !== 5 || count($raw2) !== 5) {
        $error = "Debe completar las 5 puntuaciones.";
    } else {
        $usuario1 = array_map('intval', $raw1);
        $usuario2 = array_map('intval', $raw2);

        $valido = true;
        foreach (array_merge($usuario1, $usuario2) as $p) {
            if ($p < 0 || $p > 5) {
                $valido = false;
                break;
            }
        }

        if (!$valido) {
            $error = "Las puntuaciones deben estar entre 0 y 5.";
        } else {
            // Calcular similitud
            $similitud = similitudCoseno($usuario1, $usuario2);
            $porcentaje = round($similitud * 100, 2);
            $resultado = "Similitud entre usuarios: <strong>{$porcentaje}%</strong>";

            // === Recomendaciones para Usuario 1 ===
            // Lo que Usuario 2 ama (≥4) y Usuario 1 no (≤2)
            $rec_usuario1 = [];
            foreach ($productos as $i => $producto) {
                if ($usuario1[$i] <= 2 && $usuario2[$i] >= 4) {
                    $rec_usuario1[] = [
                        'producto' => $producto,
                        'puntuacion' => $usuario2[$i],
                        'motivo' => "Usuario 2 lo valora en {$usuario2[$i]}/5"
                    ];
                }
            }

            // === Recomendaciones para Usuario 2 ===
            // Lo que Usuario 1 ama (≥4) y Usuario 2 no (≤2)
            $rec_usuario2 = [];
            foreach ($productos as $i => $producto) {
                if ($usuario2[$i] <= 2 && $usuario1[$i] >= 4) {
                    $rec_usuario2[] = [
                        'producto' => $producto,
                        'puntuacion' => $usuario1[$i],
                        'motivo' => "Usuario 1 lo valora en {$usuario1[$i]}/5"
                    ];
                }
            }

            $mostrarResultados = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>🛒 Recomendaciones Bidireccionales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-primary { background: #28a745; }
        .btn-primary:hover { background: #218838; }
        input.form-control { text-align: center; }
        .recomendacion-item {
            border-left: 4px solid #28a745;
            margin-bottom: 12px;
            padding-left: 12px;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header text-center bg-success text-white">
                    <h2>🛒 Sistema de Recomendaciones (Bidireccional)</h2>
                    <p class="mb-0">Recomendaciones para ambos usuarios</p>
                </div>

                <div class="card-body">
                    <p class="text-center text-muted">Ingresa las puntuaciones (0 a 5) para cada producto:</p>

                    <form method="post">
                        <table class="table table-bordered text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto</th>
                                    <th>Usuario 1</th>
                                    <th>Usuario 2</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $i => $producto): ?>
                                <tr>
                                    <td class="text-start"><?= htmlspecialchars($producto) ?></td>
                                    <td>
                                        <input type="number" name="usuario1[]" class="form-control" min="0" max="5" required value="<?= $usuario1[$i] ?>">
                                    </td>
                                    <td>
                                        <input type="number" name="usuario2[]" class="form-control" min="0" max="5" required value="<?= $usuario2[$i] ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                🔍 Calcular y Recomendar (Ambos)
                            </button>
                        </div>
                    </form>

                    <?php if ($error): ?>
                        <div class="alert alert-danger mt-4 text-center">
                            ⚠️ <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($mostrarResultados): ?>
                        <!-- Similitud -->
                        <div class="alert alert-info mt-4 text-center">
                            <?= $resultado ?>
                        </div>

                        <!-- Recomendaciones para Usuario 1 -->
                        <div class="mt-4">
                            <h5 class="text-primary">👤 Recomendaciones para <strong>Usuario 1</strong></h5>
                            <?php if (empty($rec_usuario1)): ?>
                                <div class="alert alert-light">No se encontraron productos recomendables para Usuario 1.</div>
                            <?php else: ?>
                                <div class="border rounded p-3 bg-light">
                                    <?php foreach ($rec_usuario1 as $r): ?>
                                        <div class="recomendacion-item">
                                            <strong><?= htmlspecialchars($r['producto']) ?></strong> 
                                            <span class="badge bg-success float-end"><?= $r['puntuacion'] ?>/5</span>
                                            <br>
                                            <small class="text-muted"><?= $r['motivo'] ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Recomendaciones para Usuario 2 -->
                        <div class="mt-4">
                            <h5 class="text-secondary">👤 Recomendaciones para <strong>Usuario 2</strong></h5>
                            <?php if (empty($rec_usuario2)): ?>
                                <div class="alert alert-light">No se encontraron productos recomendables para Usuario 2.</div>
                            <?php else: ?>
                                <div class="border rounded p-3 bg-light">
                                    <?php foreach ($rec_usuario2 as $r): ?>
                                        <div class="recomendacion-item">
                                            <strong><?= htmlspecialchars($r['producto']) ?></strong> 
                                            <span class="badge bg-secondary float-end"><?= $r['puntuacion'] ?>/5</span>
                                            <br>
                                            <small class="text-muted"><?= $r['motivo'] ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <footer class="text-center mt-4 text-secondary">
                &copy; <?= date('Y') ?> - Sistema de recomendaciones bidireccionales con similitud de coseno
            </footer>
        </div>
    </div>
</div>
</body>
</html>
