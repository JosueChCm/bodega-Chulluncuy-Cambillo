<?php
// tests/ServicioVentaTest.php
// Pruebas de valores límite para las reglas R1, R2 y R3 de ServicioVenta.
// Solo lógica pura: no se usa RepositorioMySQL ni ninguna conexión a base de datos.

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/ServicioVenta.php';

final class ServicioVentaTest extends TestCase
{
    private const DELTA = 0.001;

    // ═══ R3 · Cantidad: entero > 0 · borde = 0 ════════════════════

    public function testRechazaCantidadNegativa(): void          // por debajo del borde
    {
        $this->assertFalse(ServicioVenta::validarCantidad(-1));
    }

    public function testRechazaCantidadCero(): void              // en el borde
    {
        $this->assertFalse(ServicioVenta::validarCantidad(0));
    }

    public function testAceptaCantidadUno(): void                // por encima del borde
    {
        $this->assertTrue(ServicioVenta::validarCantidad(1));
    }

    public function testFormularioRechazaCantidadCeroEscrita(): void
    {
        $this->assertNull(ServicioVenta::obtenerCantidadValida('0'));
    }

    public function testFormularioAceptaCantidadUnoEscritaYLaDevuelveComoEntero(): void
    {
        $this->assertSame(1, ServicioVenta::obtenerCantidadValida('1'));
    }

    public function testFormularioRechazaCantidadDecimal(): void
    {
        $this->assertNull(ServicioVenta::obtenerCantidadValida('1.5'));
    }

    // ═══ R1 · Stock: cantidad ≤ stock · borde = stock (10) ════════

    public function testAceptaVentaMenorAlStock(): void          // por debajo del borde
    {
        $this->assertTrue(ServicioVenta::hayStock(9, 10));
    }

    public function testAceptaVentaIgualAlStock(): void          // en el borde
    {
        $this->assertTrue(ServicioVenta::hayStock(10, 10));
    }

    public function testRechazaVentaMayorAlStock(): void         // por encima del borde
    {
        $this->assertFalse(ServicioVenta::hayStock(11, 10));
    }

    public function testRechazaVentaDeUnaUnidadConStockCero(): void
    {
        $this->assertFalse(ServicioVenta::hayStock(1, 0));
    }

    public function testAceptaLineasDelMismoProductoQueSumanExactamenteElStock(): void
    {
        $items = ServicioVenta::agruparPorProducto([
            ['producto' => $this->producto(stock: 10), 'cantidad' => 6],
            ['producto' => $this->producto(stock: 10), 'cantidad' => 4],
        ]);

        $this->assertSame(10, $items[0]['cantidad']);
        $this->assertSame([], ServicioVenta::itemsQueExcedenStock($items));
    }

    public function testRechazaLineasDelMismoProductoQueSumanUnoMasQueElStock(): void
    {
        $items = ServicioVenta::agruparPorProducto([
            ['producto' => $this->producto(stock: 10), 'cantidad' => 6],
            ['producto' => $this->producto(stock: 10), 'cantidad' => 5],
        ]);

        $this->assertSame(1, count(ServicioVenta::itemsQueExcedenStock($items)));
    }

    // ═══ R2 · Descuento: 5 % si subtotal > 100,00 · borde = 100,00 ═

    public function testSinDescuentoCuandoSubtotalEs99Con99(): void   // por debajo del borde
    {
        $this->assertEqualsWithDelta(0.00, ServicioVenta::calcularDescuento(99.99), self::DELTA);
        $this->assertEqualsWithDelta(99.99, ServicioVenta::calcularTotal(99.99), self::DELTA);
    }

    public function testSinDescuentoCuandoSubtotalEsExactamente100(): void   // en el borde
    {
        $this->assertEqualsWithDelta(0.00, ServicioVenta::calcularDescuento(100.00), self::DELTA);
        $this->assertEqualsWithDelta(100.00, ServicioVenta::calcularTotal(100.00), self::DELTA);
    }

    public function testAplicaDescuentoCuandoSubtotalEs100Con01(): void      // por encima del borde
    {
        // 100,01 × 5 % = 5,0005 → 5,00 con 2 decimales
        $this->assertEqualsWithDelta(5.00, ServicioVenta::calcularDescuento(100.01), self::DELTA);
        $this->assertEqualsWithDelta(95.01, ServicioVenta::calcularTotal(100.01), self::DELTA);
    }

    public function testRedondeaDescuentoA2DecimalesHaciaArribaEnLaMitad(): void
    {
        // 100,10 × 5 % = 5,005 → 5,01
        $this->assertEqualsWithDelta(5.01, ServicioVenta::calcularDescuento(100.10), self::DELTA);
        $this->assertEqualsWithDelta(95.09, ServicioVenta::calcularTotal(100.10), self::DELTA);
    }

    public function testSubtotalDeLineasQueSumanExactamente100NoTieneDescuento(): void
    {
        $montos = ServicioVenta::calcularMontos([
            ['precio' => 2.50, 'cantidad' => 40],   // 100,00
        ]);

        $this->assertEqualsWithDelta(100.00, $montos['subtotal'], self::DELTA);
        $this->assertEqualsWithDelta(0.00, $montos['descuento'], self::DELTA);
        $this->assertEqualsWithDelta(100.00, $montos['total'], self::DELTA);
    }

    public function testSubtotalDeLineasQueSumanUnCentimoMasDe100TieneDescuento(): void
    {
        $montos = ServicioVenta::calcularMontos([
            ['precio' => 2.50, 'cantidad' => 40],   // 100,00
            ['precio' => 0.01, 'cantidad' => 1],    //   0,01
        ]);

        $this->assertEqualsWithDelta(100.01, $montos['subtotal'], self::DELTA);
        $this->assertEqualsWithDelta(5.00, $montos['descuento'], self::DELTA);
        $this->assertEqualsWithDelta(95.01, $montos['total'], self::DELTA);
    }

    /** @return array{id:int, nombre:string, precio:float, stock:int} */
    private function producto(int $stock): array
    {
        return ['id' => 1, 'nombre' => 'Arroz Costeño', 'precio' => 2.50, 'stock' => $stock];
    }

}
