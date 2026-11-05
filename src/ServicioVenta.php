<?php
// src/ServicioVenta.php · Lógica de negocio de la bodega «Doña Rosa».
// Sin HTML, sin SQL, sin acceso a base de datos: solo funciones puras (testeables con PHPUnit).
// Los montos se calculan internamente en céntimos (enteros) para evitar errores de coma flotante.

declare(strict_types=1);

final class ServicioVenta
{
    /** R2 · El descuento se aplica solo si el subtotal es ESTRICTAMENTE mayor a este monto. */
    public const UMBRAL_DESCUENTO = 100.00;

    /** R2 · Porcentaje de descuento. */
    public const TASA_DESCUENTO = 0.05;

    // ─── R3 · Cantidad ──────────────────────────────────────────

    /** R3 · La cantidad debe ser un entero mayor que cero. */
    public static function validarCantidad(int $cantidad): bool
    {
        return $cantidad > 0;
    }

    /**
     * R3 · Devuelve la cantidad si el valor del formulario es un entero > 0,
     * o null si debe rechazarse (0, negativos, decimales, texto, vacío).
     */
    public static function obtenerCantidadValida(mixed $valor): ?int
    {
        $cantidad = self::convertirAEntero($valor);
        return ($cantidad !== null && self::validarCantidad($cantidad)) ? $cantidad : null;
    }

    // ─── R1 · Stock ─────────────────────────────────────────────

    /** R1 · No se puede vender más que el stock; igualarlo es válido (el stock queda en 0). */
    public static function hayStock(int $cantidad, int $stock): bool
    {
        return $cantidad <= $stock;
    }

    /**
     * R1 · Une las líneas del mismo producto sumando sus cantidades, para que el stock
     * se compare contra el total pedido. Conserva el orden en que aparece cada producto.
     *
     * @param array<int, array{producto: array{id:int, nombre:string, precio:float, stock:int}, cantidad:int}> $lineas
     * @return array<int, array{producto_id:int, nombre:string, precio:float, stock:int, cantidad:int}>
     */
    public static function agruparPorProducto(array $lineas): array
    {
        $itemsPorProducto = [];
        foreach ($lineas as $linea) {
            $producto = $linea['producto'];
            $itemsPorProducto[$producto['id']] ??= [
                'producto_id' => $producto['id'],
                'nombre'      => $producto['nombre'],
                'precio'      => $producto['precio'],
                'stock'       => $producto['stock'],
                'cantidad'    => 0,
            ];
            $itemsPorProducto[$producto['id']]['cantidad'] += $linea['cantidad'];
        }
        return array_values($itemsPorProducto);
    }

    /**
     * R1 · Devuelve los ítems cuya cantidad excede el stock (lista vacía = todo en regla).
     * @param array<int, array{cantidad:int, stock:int}> $items
     */
    public static function itemsQueExcedenStock(array $items): array
    {
        return array_values(array_filter(
            $items,
            static fn(array $item): bool => !self::hayStock($item['cantidad'], $item['stock'])
        ));
    }

    /** Criterio 2.7 · Una venta debe tener al menos una línea válida. */
    public static function tieneLineas(array $items): bool
    {
        return $items !== [];
    }

    // ─── Montos: importe, subtotal y R2 · descuento/total ───────

    /** Importe de una línea = precio × cantidad, con 2 decimales. */
    public static function calcularImporte(float|int|string $precio, int $cantidad): float
    {
        return self::aSoles(self::importeEnCentimos($precio, $cantidad));
    }

    /**
     * Subtotal = Σ(precio × cantidad), con 2 decimales.
     * @param array<int, array{precio: float|int|string, cantidad: int}> $items
     */
    public static function calcularSubtotal(array $items): float
    {
        $subtotalCentimos = 0;
        foreach ($items as $item) {
            $subtotalCentimos += self::importeEnCentimos($item['precio'], (int) $item['cantidad']);
        }
        return self::aSoles($subtotalCentimos);
    }

    /** R2 · 5 % si el subtotal es MAYOR a S/ 100,00; si es exactamente 100,00 o menos, 0. */
    public static function calcularDescuento(float $subtotal): float
    {
        return self::aSoles(self::descuentoEnCentimos(self::aCentimos($subtotal)));
    }

    /** R2 · Total = subtotal − descuento, con 2 decimales. */
    public static function calcularTotal(float $subtotal): float
    {
        $subtotalCentimos = self::aCentimos($subtotal);
        return self::aSoles($subtotalCentimos - self::descuentoEnCentimos($subtotalCentimos));
    }

    /**
     * R2 · Subtotal, descuento y total de la venta en un solo paso.
     * @return array{subtotal: float, descuento: float, total: float}
     */
    public static function calcularMontos(array $items): array
    {
        $subtotal = self::calcularSubtotal($items);
        return [
            'subtotal'  => $subtotal,
            'descuento' => self::calcularDescuento($subtotal),
            'total'     => self::calcularTotal($subtotal),
        ];
    }

    // ─── Auxiliares privados ────────────────────────────────────

    /**
     * Convierte un valor del formulario en entero, o null si no está escrito como
     * entero ("2.5", "abc", "", "1e3" → null). Acepta espacios alrededor.
     */
    private static function convertirAEntero(mixed $valor): ?int
    {
        if (is_int($valor)) {
            return $valor;
        }
        if (is_string($valor) && preg_match('/^\s*[+-]?\d+\s*$/', $valor) === 1) {
            $entero = filter_var(trim($valor), FILTER_VALIDATE_INT); // false si desborda int
            return $entero === false ? null : $entero;
        }
        return null;
    }

    private static function importeEnCentimos(float|int|string $precio, int $cantidad): int
    {
        return self::aCentimos((float) $precio) * $cantidad;
    }

    private static function descuentoEnCentimos(int $subtotalCentimos): int
    {
        if ($subtotalCentimos <= self::aCentimos(self::UMBRAL_DESCUENTO)) {
            return 0;
        }
        return (int) round($subtotalCentimos * self::TASA_DESCUENTO);
    }

    private static function aCentimos(float $soles): int
    {
        return (int) round($soles * 100);
    }

    private static function aSoles(int $centimos): float
    {
        return $centimos / 100;
    }
}
