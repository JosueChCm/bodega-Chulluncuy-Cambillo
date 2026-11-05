<?php
// src/ServicioVenta.php · Lógica de negocio de la bodega «Doña Rosa».
// Sin HTML, sin SQL, sin acceso a base de datos: solo funciones puras (testeables con PHPUnit).

declare(strict_types=1);

final class ServicioVenta
{
    /** Umbral a partir del cual (estrictamente mayor) se aplica descuento — R2. */
    public const UMBRAL_DESCUENTO = 100.00;

    /** Porcentaje de descuento — R2. */
    public const TASA_DESCUENTO = 0.05;

    /**
     * R3 · Convierte el valor recibido del formulario en un entero, o null si no es
     * un entero escrito como tal ("2.5", "abc", "", "1e3" → null).
     */
    public static function parsearCantidad(mixed $valor): ?int
    {
        if (is_int($valor)) {
            return $valor;
        }
        if (is_string($valor) && preg_match('/^\s*[+-]?\d+\s*$/', $valor) === 1) {
            $entero = filter_var(trim($valor), FILTER_VALIDATE_INT);
            return $entero === false ? null : $entero;
        }
        return null; // decimales, floats, arreglos, null, texto
    }

    /** R3 · La cantidad debe ser un entero mayor que cero. */
    public static function validarCantidad(int $cantidad): bool
    {
        return $cantidad > 0;
    }

    /**
     * R3 · Combina parsearCantidad() y validarCantidad(): devuelve la cantidad si es
     * un entero > 0, o null si debe rechazarse (0, negativos, decimales, texto).
     */
    public static function cantidadValida(mixed $valor): ?int
    {
        $cantidad = self::parsearCantidad($valor);
        return ($cantidad !== null && self::validarCantidad($cantidad)) ? $cantidad : null;
    }

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
        $agrupado = [];
        foreach ($lineas as $linea) {
            $p = $linea['producto'];
            $agrupado[$p['id']] ??= [
                'producto_id' => $p['id'],
                'nombre'      => $p['nombre'],
                'precio'      => $p['precio'],
                'stock'       => $p['stock'],
                'cantidad'    => 0,
            ];
            $agrupado[$p['id']]['cantidad'] += $linea['cantidad'];
        }
        return array_values($agrupado);
    }

    /**
     * R1 · Devuelve los ítems cuya cantidad excede el stock (lista vacía = todo en regla).
     * @param array<int, array{cantidad:int, stock:int}> $items
     */
    public static function itemsSinStock(array $items): array
    {
        return array_values(array_filter(
            $items,
            static fn(array $it): bool => !self::hayStock($it['cantidad'], $it['stock'])
        ));
    }

    /** Criterio 2.7 · Una venta debe tener al menos una línea válida. */
    public static function ventaTieneLineas(array $items): bool
    {
        return $items !== [];
    }

    /** Importe de una línea = precio × cantidad, con 2 decimales (base del subtotal). */
    public static function calcularImporte(float|int|string $precio, int $cantidad): float
    {
        return self::calcularSubtotal([['precio' => $precio, 'cantidad' => $cantidad]]);
    }

    /**
     * Subtotal = Σ(precio × cantidad), en soles con 2 decimales.
     * @param array<int, array{precio: float|int|string, cantidad: int}> $items
     */
    public static function calcularSubtotal(array $items): float
    {
        $centimos = 0;
        foreach ($items as $item) {
            // Se trabaja en céntimos (enteros) para evitar errores de coma flotante.
            $precioCentimos = (int) round(((float) $item['precio']) * 100);
            $centimos += $precioCentimos * (int) $item['cantidad'];
        }
        return $centimos / 100;
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

    /** R2 · 5 % si el subtotal es MAYOR a S/ 100,00; si es exactamente 100,00 o menos, 0. */
    public static function calcularDescuento(float $subtotal): float
    {
        $centimos = (int) round($subtotal * 100);
        if ($centimos <= (int) round(self::UMBRAL_DESCUENTO * 100)) {
            return 0.0;
        }
        return round($centimos * self::TASA_DESCUENTO) / 100;
    }

    /** R2 · Total = subtotal − descuento, con 2 decimales. */
    public static function calcularTotal(float $subtotal): float
    {
        $centimosSubtotal  = (int) round($subtotal * 100);
        $centimosDescuento = (int) round(self::calcularDescuento($subtotal) * 100);
        return ($centimosSubtotal - $centimosDescuento) / 100;
    }
}
