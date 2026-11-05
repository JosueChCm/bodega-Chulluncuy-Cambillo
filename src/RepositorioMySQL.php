<?php
// src/RepositorioMySQL.php · Capa de acceso a datos. Todo el SQL vive aquí.
// Solo sentencias preparadas con marcadores nombrados (nunca se concatena input del usuario).

declare(strict_types=1);

/** Se lanza cuando la venta no pudo guardarse y la transacción fue revertida (criterio 4.2). */
final class VentaNoCompletadaException extends RuntimeException
{
}

final class RepositorioMySQL
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array{id:int, nombre:string, precio:float, stock:int}> */
    public function listarProductos(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, precio, stock FROM producto ORDER BY nombre');
        $stmt->execute();
        return array_map([$this, 'mapearProducto'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array{id:int, nombre:string, precio:float, stock:int}|null */
    public function obtenerProducto(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, precio, stock FROM producto WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $this->mapearProducto($fila) : null;
    }

    /**
     * R4 · Guarda la venta, su detalle y descuenta el stock en UNA sola transacción.
     * Ante cualquier error hace ROLLBACK: ni la venta ni el stock cambian (criterio 4.2).
     *
     * @param array<int, array{producto_id:int, precio:float, cantidad:int}> $items
     * @param array{subtotal:float, descuento:float, total:float} $montos calculados por ServicioVenta
     * @return int id de la venta registrada
     * @throws VentaNoCompletadaException
     */
    public function registrarVenta(string $cliente, array $items, array $montos): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmtVenta = $this->pdo->prepare(
                'INSERT INTO venta (cliente, subtotal, descuento, total)
                 VALUES (:cliente, :subtotal, :descuento, :total)'
            );
            $stmtVenta->execute([
                ':cliente'   => $cliente,
                ':subtotal'  => number_format($montos['subtotal'], 2, '.', ''),
                ':descuento' => number_format($montos['descuento'], 2, '.', ''),
                ':total'     => number_format($montos['total'], 2, '.', ''),
            ]);
            $ventaId = (int) $this->pdo->lastInsertId();

            $stmtDetalle = $this->pdo->prepare(
                'INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario)
                 VALUES (:venta_id, :producto_id, :cantidad, :precio)'
            );
            // La regla R1 se decide en ServicioVenta::hayStock() antes de llegar aquí. Este
            // "AND stock >= :minimo" es solo un resguardo de integridad (como los CHECK del esquema):
            // si otra venta simultánea consumió el stock entretanto, el UPDATE no afecta filas y
            // la transacción se revierte. No es una segunda copia de la regla de negocio.
            $stmtStock = $this->pdo->prepare(
                'UPDATE producto SET stock = stock - :cantidad
                 WHERE id = :id AND stock >= :minimo'
            );

            foreach ($items as $item) {
                $stmtDetalle->execute([
                    ':venta_id'    => $ventaId,
                    ':producto_id' => $item['producto_id'],
                    ':cantidad'    => $item['cantidad'],
                    ':precio'      => number_format((float) $item['precio'], 2, '.', ''),
                ]);

                $stmtStock->execute([
                    ':cantidad' => $item['cantidad'],
                    ':id'       => $item['producto_id'],
                    ':minimo'   => $item['cantidad'],
                ]);
                if ($stmtStock->rowCount() !== 1) {
                    throw new RuntimeException('El stock cambió mientras se registraba la venta.');
                }
            }

            $this->pdo->commit();
            return $ventaId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new VentaNoCompletadaException(
                'La venta no pudo completarse. El stock no fue modificado.',
                0,
                $e
            );
        }
    }

    /** @param array<string, mixed> $fila */
    private function mapearProducto(array $fila): array
    {
        return [
            'id'     => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'precio' => (float) $fila['precio'],
            'stock'  => (int) $fila['stock'],
        ];
    }
}
