<?php
// index.php · Lista los productos del catálogo con su stock.

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
    $repo = new RepositorioMySQL(conectar());
    $productos = $repo->listarProductos();
} catch (PDOException $ex) {
    $error = 'No se pudo conectar con la base de datos. Verifica que MySQL esté encendido en XAMPP '
           . 'y que hayas importado sql/bodega.sql.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bodega Doña Rosa · Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Bodega «Doña Rosa»</a>
        <a class="btn btn-light btn-sm" href="venta.php">+ Nueva venta</a>
    </div>
</nav>

<main class="container">
    <h1 class="h3 mb-3">Productos y stock</h1>

    <?php if ($error !== null): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php elseif ($productos === []): ?>
        <div class="alert alert-info">Aún no hay productos registrados.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">Stock</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($productos as $p): ?>
                    <tr>
                        <td><?= e($p['nombre']) ?></td>
                        <td class="text-end">S/ <?= number_format($p['precio'], 2) ?></td>
                        <td class="text-end">
                            <?php if ($p['stock'] === 0): ?>
                                <span class="badge text-bg-danger">Agotado (0)</span>
                            <?php else: ?>
                                <span class="badge text-bg-success"><?= $p['stock'] ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
