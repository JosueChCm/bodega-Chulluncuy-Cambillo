<?php
// venta.php · Formulario de venta: cliente + varias líneas (producto, cantidad).

declare(strict_types=1);

require __DIR__ . '/config/conexion.php';
require __DIR__ . '/src/RepositorioMySQL.php';

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

$productos = [];
$error = null;
try {
    $productos = (new RepositorioMySQL(conectar()))->listarProductos();
} catch (PDOException $ex) {
    $error = 'No se pudo conectar con la base de datos. Verifica que MySQL esté encendido en XAMPP.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bodega Doña Rosa · Nueva venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Bodega «Doña Rosa»</a>
        <a class="btn btn-outline-light btn-sm" href="index.php">Ver productos</a>
    </div>
</nav>

<main class="container" style="max-width: 760px;">
    <h1 class="h3 mb-3">Nueva venta</h1>

    <?php if ($error !== null): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php else: ?>
    <form action="guardar_venta.php" method="post" class="card shadow-sm">
        <div class="card-body">
            <div class="mb-4">
                <label for="cliente" class="form-label">Cliente</label>
                <input type="text" class="form-control" id="cliente" name="cliente"
                       maxlength="100" required placeholder="Nombre del cliente">
            </div>

            <h2 class="h6">Productos</h2>
            <div id="lineas">
                <div class="row g-2 mb-2 linea">
                    <div class="col-7">
                        <select name="producto_id[]" class="form-select" required>
                            <option value="">— Elige un producto —</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $p['stock'] === 0 ? 'disabled' : '' ?>>
                                    <?= e($p['nombre']) ?> · S/ <?= number_format($p['precio'], 2) ?>
                                    (stock: <?= $p['stock'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-3">
                        <input type="number" name="cantidad[]" class="form-control"
                               min="1" step="1" required placeholder="Cantidad">
                    </div>
                    <div class="col-2 d-grid">
                        <button type="button" class="btn btn-outline-danger quitar" title="Quitar línea">✕</button>
                    </div>
                </div>
            </div>

            <button type="button" id="agregar" class="btn btn-outline-secondary btn-sm">+ Agregar línea</button>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted">Compras mayores a S/ 100,00 tienen 5 % de descuento.</small>
            <button type="submit" class="btn btn-success">Confirmar venta</button>
        </div>
    </form>
    <?php endif; ?>
</main>

<script>
// Agrega o quita líneas de venta en el formulario (solo interfaz; la validación real es en el servidor).
const contenedor = document.getElementById('lineas');
const plantilla = contenedor ? contenedor.querySelector('.linea').cloneNode(true) : null;

document.getElementById('agregar')?.addEventListener('click', () => {
    const nueva = plantilla.cloneNode(true);
    nueva.querySelectorAll('select, input').forEach(c => c.value = '');
    contenedor.appendChild(nueva);
});

contenedor?.addEventListener('click', (ev) => {
    if (!ev.target.classList.contains('quitar')) return;
    if (contenedor.querySelectorAll('.linea').length > 1) {
        ev.target.closest('.linea').remove();
    } else {
        ev.target.closest('.linea').querySelectorAll('select, input').forEach(c => c.value = '');
    }
});
</script>
</body>
</html>
