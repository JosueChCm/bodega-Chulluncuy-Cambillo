# Registro de prompts y respuestas — Proyecto Bodega

## Índice

1. [Requisitos (requirements.md)](#1-requisitos-requirementsmd)
2. [Diseño técnico (design.md)](#2-diseño-técnico-designmd)
3. [Base de datos (sql/bodega.sql)](#3-base-de-datos-sqlbodegasql)
4. [Aplicación PHP](#4-aplicación-php)
5. [Lista de cotejo de pruebas manuales](#5-lista-de-cotejo-de-pruebas-manuales)
6. [Refactorización de ServicioVenta](#6-refactorización-de-servicioventa)

---

## 1. Requisitos (requirements.md)

### Prompt

> Actúa como analista de sistemas. A partir del archivo D0.md adjunto genera `requirements.md` con:
>
> - Cinco historias de usuario como máximo, formato «Como [rol] quiero [acción] para [beneficio]».
> - Criterios de aceptación en formato EARS (WHEN ... THE SYSTEM SHALL ...) para cada historia.
> - Un criterio de aceptación por cada regla de negocio R1 a R4, citando el código de la regla.
>
> **Restricciones:** no agregues funcionalidades fuera del alcance declarado en D0. No incluyas autenticación ni reportes. Responde solo con el contenido del archivo.

### Respuesta

El formato está validado. Ahora estoy refinando cada requisito para hacerlo más preciso y comprobable...

Created `requirements.md`.

El documento de requisitos está listo. Contiene **4 historias de usuario** (dentro del máximo de 5) que cubren todo el alcance declarado en D0:

1. **Registrar productos** — validación de nombre, precio y stock inicial, rechazo de duplicados.
2. **Registrar ventas** — selección de productos y cantidades con validación de stock y cantidad.
3. **Aplicar descuento** — 5% sobre subtotal mayor a S/ 100,00, con precisión de 2 decimales.
4. **Descontar stock** — reducción en la misma transacción con rollback ante errores.

Las 4 reglas de negocio (R1 a R4) están citadas explícitamente en los criterios de aceptación correspondientes. No se incluyó autenticación ni reportes, según las restricciones.

---

## 2. Diseño técnico (design.md)

### Prompt

> A partir de `requirements.md` genera `design.md` con:
>
> - Arquitectura en tres capas para PHP sin framework: presentación (archivos `.php` con HTML), lógica de negocio (una clase `ServicioVenta` en `src/` sin HTML ni SQL) y acceso a datos (clase `RepositorioMySQL` en `src/` usando PDO con sentencias preparadas).
> - Modelo de datos con las tres tablas `producto`, `venta` y `detalle_venta`, con tipos y claves.
> - Diagrama de secuencia en texto (Mermaid) del caso «registrar venta», indicando en qué capa se valida cada regla R1–R4.
>
> **Restricción:** la clase `ServicioVenta` debe poder probarse con PHPUnit sin base de datos.

### Respuesta

Created `design.md`.

El documento de diseño técnico está listo. Incluye todo lo que pediste:

**Arquitectura en tres capas para PHP sin framework:**

- **Presentación** (`public/*.php` con HTML) — formularios, captura de input, mostrar resultados.
- **Lógica de negocio** (`ServicioVenta.php`) — valida R1–R4, calcula subtotal/descuento/total, orquesta la transacción. Sin HTML, sin SQL directo.
- **Acceso a datos** (`RepositorioMySQL.php` + `RepositorioInterface`) — PDO con sentencias preparadas para todas las operaciones.

**Testabilidad sin base de datos:** `ServicioVenta` depende de `RepositorioInterface` por inyección de dependencias. En tests se inyecta un `RepositorioFake` en memoria, por lo que PHPUnit no necesita MySQL.

**Modelo de datos** con tres tablas (`producto`, `venta`, `detalle_venta`), con tipos MySQL, claves primarias, foráneas, restricciones `UNIQUE` y `CHECK`.

**Diagrama de secuencia en Mermaid** del caso «registrar venta» mostrando el flujo Usuario → Presentación → ServicioVenta → RepositorioMySQL → MySQL, con las validaciones R1, R2, R3 y R4 marcadas en el punto exacto donde ocurren dentro de `ServicioVenta`.

Además incluye 9 propiedades de corrección para property-based testing, estrategia de pruebas y manejo de errores.

---

## 3. Base de datos (sql/bodega.sql)

### Prompt

> Lee `design.md`. Genera el archivo `sql/bodega.sql` para MySQL 8 (XAMPP) con:
>
> - `CREATE DATABASE bodega; USE bodega;`
> - Las tres tablas con claves primarias, foráneas y tipos exactos del diseño.
> - Datos de prueba: 6 productos con nombres de una bodega peruana (arroz, aceite, leche, azúcar, atún, gaseosa) con precios entre S/ 2,50 y S/ 12,00 y stock entre 0 y 50. Uno de los productos debe tener stock 0.
>
> No generes procedimientos almacenados ni triggers. Explica en dos líneas qué hiciste.

### Respuesta

Listo. Creé `bodega.sql` con las tres tablas (`producto`, `venta`, `detalle_venta`) copiando exactamente los tipos, claves y restricciones del DDL del diseño, más 6 productos de bodega peruana (arroz S/ 2,50 hasta gaseosa S/ 12,00) con stock entre 0 y 50 — el azúcar queda en stock 0 para cubrir el caso de producto agotado.

---

## 4. Aplicación PHP

### Prompt

> Lee `design.md` y `sql/bodega.sql`. Genera la aplicación PHP 8 sin framework con esta estructura exacta:
>
> | Archivo | Contenido |
> |---|---|
> | `config/conexion.php` | Función `conectar(): PDO`, leyendo host, usuario, clave y base desde `config/env.php`. |
> | `config/env.php` | Arreglo con los datos de conexión (localhost, root, sin clave, bodega). |
> | `src/ServicioVenta.php` | Clase con métodos estáticos: `validarCantidad(int $cantidad): bool`, `hayStock(int $cantidad, int $stock): bool`, `calcularSubtotal(array $items): float`, `calcularDescuento(float $subtotal): float`, `calcularTotal(float $subtotal): float`. Sin HTML, sin SQL, sin acceso a base de datos. |
> | `src/RepositorioMySQL.php` | Métodos `listarProductos()`, `obtenerProducto($id)`, `registrarVenta($cliente, $items)` con transacción y sentencias preparadas. |
> | `index.php` | Lista productos con stock. |
> | `venta.php` | Formulario: cliente, producto (select), cantidad; permite agregar varias líneas. |
> | `guardar_venta.php` | Recibe el POST, usa `ServicioVenta` para validar y calcular, usa `RepositorioMySQL` para guardar; muestra mensaje de éxito o error legible. |
>
> Aplica las reglas R1 a R4 de `requirements.md` exactamente. Usa Bootstrap desde CDN para la apariencia. Después de generar, explica en una lista qué archivo implementa cada regla.

### Respuesta

**Archivos creados:** `config/env.php`, `config/conexion.php`, `src/ServicioVenta.php`, `src/RepositorioMySQL.php`, `index.php`, `venta.php`, `guardar_venta.php` y `sql/migracion_cliente.sql`. Además agregué la columna `cliente` a `sql/bodega.sql`.

#### Pruebas realizadas

- Los 7 archivos PHP pasan `php -l` sin errores de sintaxis.
- Las reglas dan lo esperado: subtotal de 100,00 → sin descuento; 100,01 → 5,00 de descuento; 100,10 → 5,01; vender una cantidad igual al stock deja el stock en 0.
- En la base de prueba: una venta de 30 aceites dejó el stock en 0 con descuento 18,00 y total 342,00. Luego una venta que fallaba en su segunda línea se revirtió completa: el arroz siguió en 50 y no se guardó la venta. Al final borré la base de prueba.

#### Me aparté de lo que pediste en tres puntos

1. `registrarVenta($cliente, $items, $montos)` tiene un tercer parámetro con subtotal, descuento y total. Así el repositorio solo guarda lo que calcula `ServicioVenta` y no tiene que calcular nada.
2. `ServicioVenta::parsearCantidad()` es un método que no pediste. Lo agregué porque `validarCantidad(int)` recibe un entero: sin este paso, un `"2.5"` del formulario se convertiría en 2 y no se rechazaría el decimal.
3. **Ventas con errores:** si una línea tiene error, no se guarda ninguna parte de la venta y se muestran todos los errores. Preferí eso a guardar solo las líneas válidas sin avisar.

#### Qué archivo implementa cada regla

**R1 · Stock**

- `ServicioVenta::hayStock()` en `src/ServicioVenta.php` permite que la cantidad sea igual al stock.
- `guardar_venta.php` usa esa regla y suma las líneas del mismo producto antes de comparar con el stock.
- En `src/RepositorioMySQL.php`, el `UPDATE … WHERE stock >= :minimo` vuelve a revisar el stock dentro de la transacción, para que dos ventas al mismo tiempo no lo dejen negativo.

**R2 · Descuento**

- `calcularSubtotal()`, `calcularDescuento()` y `calcularTotal()` en `src/ServicioVenta.php`. El descuento se aplica solo si el subtotal es mayor a 100,00, y los cálculos se hacen en céntimos para no perder exactitud.
- `guardar_venta.php` los llama y muestra subtotal, descuento y total.

**R3 · Cantidad**

- `parsearCantidad()` y `validarCantidad()` en `src/ServicioVenta.php` rechazan 0, negativos, decimales y texto.
- `guardar_venta.php` muestra el error de cada línea.
- El `min="1" step="1"` de `venta.php` solo ayuda en el navegador; la validación que cuenta es la del servidor.

**R4 · Actualización**

- `RepositorioMySQL::registrarVenta()` en `src/RepositorioMySQL.php` guarda la venta, su detalle y descuenta el stock en una sola transacción. Si algo falla, revierte todo y lanza `VentaNoCompletadaException`.
- `guardar_venta.php` muestra ese error de forma legible.

Todas las consultas usan sentencias preparadas y toda la salida pasa por `htmlspecialchars`. El precio se toma siempre de la base, no del formulario.

---

## 5. Lista de cotejo de pruebas manuales

### Prompt

> Genera una lista de cotejo de 8 pruebas manuales para esta aplicación, una por fila, con columnas: N.°, acción exacta en pantalla, dato de entrada, resultado esperado según la regla R1–R4 que aplica. Incluye obligatoriamente: vender exactamente el stock disponible, vender uno más que el stock, subtotal de exactamente S/ 100,00, subtotal de S/ 100,01, cantidad 0 y cantidad -1. No ejecutes nada; solo la tabla.

### Respuesta

**Para:** quien vaya a hacer las pruebas a mano (el texto supone que no conoce el código).

#### Antes de empezar

1. Importa de nuevo `sql/bodega.sql` para partir del stock inicial.
2. Agrega en phpMyAdmin este producto, que necesita la prueba 4:

   ```sql
   INSERT INTO producto (nombre, precio, stock) VALUES ('Producto prueba', 100.01, 5);
   ```

   Los precios de los datos de prueba son todos múltiplos de S/ 0,50, así que con ellos no se puede llegar a S/ 100,01.
3. Haz las pruebas en orden, porque algunas cambian el stock.
4. En todas, escribe «Cliente prueba» en el campo **Cliente**.

#### Tabla de pruebas

| N.° | Acción exacta en pantalla | Dato de entrada | Resultado esperado (regla) |
|:---:|---|---|---|
| 1 | Abrir `venta.php` → elegir el producto → escribir la cantidad → **Confirmar venta** → luego abrir `index.php` | Gaseosa Inca Kola (stock 20), cantidad 20 | **R1 / R4:** la venta se acepta con subtotal S/ 220,00, descuento S/ 11,00 y total S/ 209,00. En `index.php` la Gaseosa sigue en la lista con «Agotado (0)» y en `venta.php` ya no se puede elegir. |
| 2 | Abrir `venta.php` → elegir el producto → escribir la cantidad → **Confirmar venta** | Leche Gloria (stock 25), cantidad 26 | **R1:** la venta se rechaza con el mensaje «Leche Gloria»: pediste 26 pero solo hay 25 en stock. En `index.php` la Leche sigue con 25. |
| 3 | Abrir `venta.php` → elegir el producto → escribir la cantidad → **Confirmar venta** | Arroz Costeño (S/ 2,50), cantidad 40 | **R2:** subtotal S/ 100,00, descuento S/ 0,00 y total S/ 100,00. **R4:** el stock del Arroz baja de 50 a 10. |
| 4 | Abrir `venta.php` → elegir el producto → escribir la cantidad → **Confirmar venta** | Producto prueba (S/ 100,01), cantidad 1 | **R2:** subtotal S/ 100,01, descuento S/ 5,00 y total S/ 95,01. **R4:** el stock baja de 5 a 4. |
| 5 | Abrir `venta.php` → elegir el producto → escribir la cantidad → **Confirmar venta** | Atún Florida, cantidad 0 | **R3:** el navegador no deja enviar el formulario y avisa que el valor debe ser 1 o más. Si se logra enviar igual, el servidor responde «la cantidad es inválida» y no se registra nada. El stock del Atún sigue en 40. |
| 6 | Abrir `venta.php` → elegir el producto → escribir la cantidad → **Confirmar venta** | Atún Florida, cantidad -1 | **R3:** pasa lo mismo que en la prueba 5. El stock del Atún sigue en 40. |
| 7 | Abrir `venta.php` → elegir el producto → escribir la cantidad → **Confirmar venta** | Aceite Vegetal, cantidad 1.5 | **R3:** el navegador rechaza el decimal. Si se envía igual, el servidor responde «la cantidad es inválida (sin decimales)». El stock del Aceite sigue en 30. |
| 8 | Abrir `venta.php` → llenar la línea 1 → pulsar **+ Agregar línea** → llenar la línea 2 → **Confirmar venta** → luego abrir `index.php` | Línea 1: Atún Florida × 3<br>Línea 2: Aceite Vegetal × 2 | **R2:** subtotal S/ 43,50, descuento S/ 0,00 y total S/ 43,50. **R4:** en una sola venta el Atún baja de 40 a 37 y el Aceite de 30 a 28. |

> **Nota:** en las pruebas 5, 6 y 7, el formulario del navegador frena el dato antes de enviarlo. Para probar que el servidor también aplica R3, quita los atributos `min` y `step` del campo de cantidad con las herramientas de desarrollador (F12) y vuelve a enviar.

---

## 6. Refactorización de ServicioVenta

### Prompt

> Revisa `src/ServicioVenta.php`. Si algún cálculo o validación de las reglas R1–R4 está fuera de esa clase (en `guardar_venta.php` o en `RepositorioMySQL.php`), muévelo a `ServicioVenta` sin cambiar el comportamiento. `ServicioVenta` no debe tener `echo`, HTML, PDO ni variables de sesión. Al terminar, lista cada método con su firma y la regla que implementa, y confirma que la aplicación sigue funcionando igual.

### Respuesta

Pasé a `ServicioVenta` las reglas que estaban en `guardar_venta.php`. La aplicación sigue funcionando igual: corrí 14 escenarios en una base de prueba aparte, antes y después del cambio, y la página de resultado, el stock, la tabla `venta` y la tabla `detalle_venta` salieron idénticos.

#### Qué moví desde `guardar_venta.php`

- La suma de cantidades cuando un producto aparece en varias líneas (R1).
- La comparación de cada producto con su stock (R1).
- La revisión de que haya al menos una línea (criterio 2.7).
- La combinación de parsear y validar la cantidad (R3).
- El cálculo de subtotal, descuento y total (R2).
- El importe de cada línea.

Ahora ese archivo solo lee el formulario, llama a `ServicioVenta`, arma los mensajes y llama al repositorio.

#### Qué dejé en `RepositorioMySQL.php`

Dejé el `UPDATE … SET stock = stock - :cantidad WHERE stock >= :minimo`. No repite la regla R1, que ya se decide antes con `hayStock()`. Es un resguardo de la base de datos, como los `CHECK` del esquema: si otra venta al mismo tiempo se lleva el stock, revierte la transacción. Sacarlo a PHP cambiaría el comportamiento en ese caso. Solo le actualicé el comentario.

#### Métodos de `ServicioVenta`

Todos son `public static`:

| Firma | Regla |
|---|---|
| `parsearCantidad(mixed $valor): ?int` | R3: rechaza decimales y texto |
| `validarCantidad(int $cantidad): bool` | R3: entero > 0 |
| `cantidadValida(mixed $valor): ?int` *(nuevo)* | R3: une los dos anteriores |
| `hayStock(int $cantidad, int $stock): bool` | R1: cantidad ≤ stock |
| `agruparPorProducto(array $lineas): array` *(nuevo)* | R1: suma las líneas del mismo producto |
| `itemsSinStock(array $items): array` *(nuevo)* | R1: devuelve los ítems que exceden su stock |
| `ventaTieneLineas(array $items): bool` *(nuevo)* | Criterio 2.7 |
| `calcularImporte(float\|int\|string $precio, int $cantidad): float` *(nuevo)* | Importe de cada línea (criterio 2.6) |
| `calcularSubtotal(array $items): float` | Criterio 2.6: Σ precio × cantidad |
| `calcularDescuento(float $subtotal): float` | R2: 5 % solo si el subtotal es mayor a 100,00 |
| `calcularTotal(float $subtotal): float` | R2: subtotal − descuento |
| `calcularMontos(array $items): array` *(nuevo)* | R2: subtotal, descuento y total juntos |

R4 (descontar el stock en la misma transacción) sigue en `RepositorioMySQL::registrarVenta()`, porque la transacción es parte del acceso a datos.

#### Revisión de lo prohibido

`ServicioVenta` no tiene `echo`, HTML, PDO, `$_SESSION` ni `$_POST`. La búsqueda solo encontró textos `array<int, …>` dentro de comentarios.

#### Escenarios comparados

- Las 8 pruebas de la lista de cotejo.
- El mismo producto en dos líneas cuya suma excede el stock.
- El mismo producto en dos líneas cuya suma es igual al stock.
- Una venta sin líneas.
- Varios errores a la vez.
- Un producto agotado.
- Un producto que no existe.

> **Nota:** fueron pruebas por consola. No abrí las páginas en el navegador, pero `index.php` y `venta.php` no cambiaron.


Revisa únicamente la sintaxis de tests/ServicioVentaTest.php. No implementes nada
en src/. No modifiques la aserción ni el valor esperado. Dime si el archivo
compila y si el nombre del método describe el comportamiento probado

El archivo compila: `php -l tests/ServicioVentaTest.php` dice «No syntax errors detected». No toqué nada. Solo revisé la sintaxis, no corrí PHPUnit.

**Nombres de los métodos:** los 7 describen lo que prueban.

| Método | Qué prueba | ¿El nombre lo describe? |
|---|---|---|
| `testDescuentoCeroCuandoSubtotalEsExactamente100` | `calcularDescuento(100.00)` da 0 | Sí (R2, frontera) |
| `testAplicaDescuentoCuandoSubtotalSupera100` | `calcularDescuento(100.01)` da más de 0 | Sí (R2) |
| `testAceptaVentaIgualAlStock` | `hayStock(10, 10)` da true | Sí (R1, frontera) |
| `testRechazaVentaMayorAlStock` | `hayStock(11, 10)` da false | Sí (R1) |
| `testRechazaCantidadCero` | `validarCantidad(0)` da false | Sí (R3) |
| `testAceptaCantidadUno` | `validarCantidad(1)` da true | Sí (R3, frontera) |
| `testRechazaCantidadNegativa` | `validarCantidad(-1)` da false | Sí (R3) |

En los dos tests de R1 el nombre dice «Venta», pero lo que se compara es una cantidad contra el stock. Sería más preciso `testAceptaCantidadIgualAlStock` y `testRechazaCantidadMayorAlStock`. Es opcional.

**Algo para tener en cuenta en `testAplicaDescuentoCuandoSubtotalSupera100`** (no lo cambié):
- El valor esperado `5.0005` tiene 4 decimales, pero R2 pide el descuento con 2 decimales. El método devuelve `5.00`.
- Por la tolerancia de 0.01 el test va a pasar igual. Pero también pasaría con cualquier valor entre 4.9905 y 5.0105, así que no comprueba el redondeo a 2 decimales.

**Detalles de formato que no afectan la compilación:**
- Las líneas 45 y 46 tienen una sangría distinta al resto.
- Falta una línea en blanco antes de `testRechazaCantidadNegativa` (línea 43).
- La línea 47 tiene espacios al final.

-------------------------------------------------
Lee tests/ServicioVentaTest.php. Implementa en src/ServicioVenta.php el código
MÍNIMO necesario para que ese test pase. No agregues métodos, validaciones ni
casos que el test no exija. No modifiques el archivo de pruebas. Explica en una
línea qué cambiaste.

No cambié nada: los 7 tests ya pasan con el `ServicioVenta.php` actual, porque `calcularDescuento`, `hayStock` y `validarCantidad` ya existían de los pasos anteriores.

```
PHPUnit 11.5.57 · PHP 8.2.12
.......   7 / 7 (100%)
OK (7 tests, 7 assertions)
```

Si querías el ejercicio de TDD en «rojo → verde», no se puede hacer recortando esta clase, porque la usa [guardar_venta.php](guardar_venta.php). Para practicarlo hay que partir de una copia vacía de `ServicioVenta` en otra carpeta.
----------------------------------------------------------------

Sin cambiar el comportamiento ni los tests, mejora nombres y
elimina duplicación en ServicioVenta.php.

Ordené `ServicioVenta.php` sin cambiar su comportamiento: los 7 tests siguen pasando y los 14 escenarios de venta dan exactamente la misma salida que antes de todos los cambios. Revisé también a mano casos límite de cantidad y de montos (por ejemplo `"1e3"`, un número que no cabe en un entero, subtotal 0, `0.1 + 0.2`), y los resultados son los esperados.

**Duplicación eliminada:**
- La conversión `(int) round($x * 100)` se repetía en varios métodos. Ahora está en dos auxiliares privados: `aCentimos()` y `aSoles()`.
- El descuento en céntimos se calculaba por separado en `calcularDescuento` y en `calcularTotal`, y cada uno volvía a convertir los montos. Ahora los dos usan `descuentoEnCentimos()`.
- `calcularImporte` armaba un arreglo falso para llamar a `calcularSubtotal`. Ahora los dos usan `importeEnCentimos()`, y el subtotal es la suma de los importes.

**Nombres cambiados:**

| Antes | Ahora | Motivo |
|---|---|---|
| `cantidadValida()` | `obtenerCantidadValida()` | Devuelve un entero o null, no un bool; el nombre anterior parecía una pregunta |
| `itemsSinStock()` | `itemsQueExcedenStock()` | «Sin stock» parecía decir stock 0 |
| `ventaTieneLineas()` | `tieneLineas()` | Recibe ítems, no una venta |
| `parsearCantidad()` (público) | `convertirAEntero()` (privado) | Solo se usaba dentro de la clase |
| `$p`, `$it`, `$agrupado` | `$producto`, `$item`, `$itemsPorProducto` | Más legibles |

Los tres primeros cambios también los apliqué en [guardar_venta.php](guardar_venta.php), que es el único archivo que los usa. No quedan referencias a los nombres viejos.

Los métodos que usan los tests y los de tu especificación original mantienen el nombre: `validarCantidad`, `hayStock`, `calcularSubtotal`, `calcularDescuento` y `calcularTotal`. Además separé la clase en bloques comentados: R3, R1, montos y auxiliares privados.