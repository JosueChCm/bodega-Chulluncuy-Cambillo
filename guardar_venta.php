<?php
// guardar_venta.php · Recibe el POST de venta.php, valida y calcula con ServicioVenta
// y guarda con RepositorioMySQL. Muestra éxito o un error legible.

declare(strict_types=1);

require __DIR__ . '/config/conexion.php';
require __DIR__ . '/src/ServicioVenta.php';
require __DIR__ . '/src/RepositorioMySQL.php';

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function soles(float $monto): string
{
    return 'S/ ' . number_format($monto, 2);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: venta.php');
    exit;
}

$errores   = [];
$resultado = null;   // se llena si la venta se guarda

$cliente     = trim((string) ($_POST['cliente'] ?? ''));
$productoIds = is_array($_POST['producto_id'] ?? null) ? $_POST['producto_id'] : [];
$cantidades  = is_array($_POST['cantidad'] ?? null) ? $_POST['cantidad'] : [];

if ($cliente === '') {
    $errores[] = 'Escribe el nombre del cliente.';
} elseif (mb_strlen($cliente) > 100) {
    $errores[] = 'El nombre del cliente no puede tener más de 100 caracteres.';
}

try {
    $repo = new RepositorioMySQL(conectar());

    // 1) Leer cada línea del formulario y validar la cantidad (R3) con ServicioVenta.
    $lineas = [];   // ['producto' => [...], 'cantidad' => int]
    foreach ($productoIds as $i => $idCrudo) {
        $numLinea      = (int) $i + 1;
        $cantidadCruda = $cantidades[$i] ?? '';

        // Línea totalmente vacía (el usuario agregó una fila y no la usó): se ignora.
        if (trim((string) $idCrudo) === '' && trim((string) $cantidadCruda) === '') {
            continue;
        }

        $productoId = filter_var($idCrudo, FILTER_VALIDATE_INT);
        $producto   = $productoId === false ? null : $repo->obtenerProducto($productoId);
        if ($producto === null) {
            $errores[] = "Línea {$numLinea}: elige un producto válido.";
            continue;
        }

        // R3 · La cantidad debe ser un entero mayor que cero.
        $cantidad = ServicioVenta::cantidadValida($cantidadCruda);
        if ($cantidad === null) {
            $errores[] = "Línea {$numLinea} ({$producto['nombre']}): la cantidad es inválida. "
                       . 'Debe ser un número entero mayor que cero (sin decimales).';
            continue;
        }

        $lineas[] = ['producto' => $producto, 'cantidad' => $cantidad];
    }

    // R1 · Se suman las líneas del mismo producto y se compara el total contra el stock.
    // El precio de cada ítem viene de la BD, no del formulario.
    $items = ServicioVenta::agruparPorProducto($lineas);
    foreach (ServicioVenta::itemsSinStock($items) as $it) {
        $errores[] = "«{$it['nombre']}»: pediste {$it['cantidad']} pero solo hay {$it['stock']} en stock.";
    }

    // Criterio 2.7 · Debe haber al menos una línea válida.
    if ($errores === [] && !ServicioVenta::ventaTieneLineas($items)) {
        $errores[] = 'La venta debe contener al menos una línea válida (producto y cantidad).';
    }

    // 2) Calcular y guardar solo si todo es válido.
    if ($errores === []) {
        // R2 · Subtotal, descuento (5 % si subtotal > 100) y total.
        $montos = ServicioVenta::calcularMontos($items);

        // R4 · Venta + detalle + descuento de stock en una sola transacción.
        $ventaId = $repo->registrarVenta($cliente, $items, $montos);

        $resultado = ['ventaId' => $ventaId, 'items' => $items] + $montos;
    }
} catch (VentaNoCompletadaException $ex) {
    $errores[] = $ex->getMessage();
} catch (PDOException $ex) {
    $errores[] = 'No se pudo conectar con la base de datos. Verifica que MySQL esté encendido en XAMPP.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bodega Doña Rosa · Resultado de la venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Bodega «Doña Rosa»</a>
    </div>
</nav>

<main class="container" style="max-width: 760px;">
<?php if ($resultado !== null): ?>
    <div class="alert alert-success">
        <strong>¡Venta registrada!</strong> N.º <?= $resultado['ventaId'] ?> · Cliente: <?= e($cliente) ?>
    </div>
    <div class="card shadow-sm mb-3">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">P. unit.</th><th class="text-end">Importe</th></tr>
            </thead>
            <tbody>
            <?php foreach ($resultado['items'] as $it): ?>
                <tr>
                    <td><?= e($it['nombre']) ?></td>
                    <td class="text-end"><?= $it['cantidad'] ?></td>
                    <td class="text-end"><?= soles($it['precio']) ?></td>
                    <td class="text-end"><?= soles(ServicioVenta::calcularImporte($it['precio'], $it['cantidad'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="3" class="text-end">Subtotal</th><td class="text-end"><?= soles($resultado['subtotal']) ?></td></tr>
                <tr><th colspan="3" class="text-end">Descuento (5 %)</th><td class="text-end text-danger">− <?= soles($resultado['descuento']) ?></td></tr>
                <tr class="table-success"><th colspan="3" class="text-end">Total</th><th class="text-end"><?= soles($resultado['total']) ?></th></tr>
            </tfoot>
        </table>
    </div>
    <a href="venta.php" class="btn btn-success">Nueva venta</a>
    <a href="index.php" class="btn btn-outline-secondary">Ver stock</a>
<?php else: ?>
    <div class="alert alert-danger">
        <strong>No se registró la venta.</strong> Corrige lo siguiente:
        <ul class="mb-0 mt-2">
            <?php foreach ($errores as $msg): ?>
                <li><?= e($msg) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <a href="javascript:history.back()" class="btn btn-secondary">← Volver al formulario</a>
<?php endif; ?>
</main>
</body>
</html>
