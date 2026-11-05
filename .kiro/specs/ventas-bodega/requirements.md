# Requirements Document

## Introduction

El sistema «ventas-bodega» es una aplicación de registro de ventas para la bodega «Doña Rosa», ubicada en San Vicente de Cañete. La aplicación permite a Doña Rosa, la dueña, registrar productos, registrar ventas, descontar el stock automáticamente y aplicar descuentos, reemplazando el cuaderno donde actualmente anota las ventas.

El sistema funciona sobre PHP 8 + MySQL en XAMPP, sin framework y sin librerías externas salvo PHPUnit. No incluye autenticación de usuarios ni reportes. Esta versión cubre exclusivamente: registrar productos, registrar ventas, descontar el stock y aplicar descuento.

## Glossary

- **Sistema**: La aplicación de registro de ventas de la bodega «Doña Rosa», construida en PHP 8 + MySQL.
- **Doña_Rosa**: La dueña de la bodega y única usuaria del Sistema.
- **Producto**: Un artículo del catálogo de la bodega, identificado por nombre, precio unitario y Stock.
- **Stock**: La cantidad disponible de un Producto, expresada como un entero no negativo.
- **Venta**: Una transacción de venta que contiene una o más Lineas_Venta.
- **Linea_Venta**: Un par (Producto, cantidad) dentro de una Venta.
- **Subtotal**: La suma de (precio unitario × cantidad) para todas las Lineas_Venta de una Venta, antes de aplicar cualquier Descuento.
- **Descuento**: Una reducción del 5 % aplicada sobre el Subtotal cuando el Subtotal es mayor a S/ 100,00.
- **Total**: El monto final de la Venta después de aplicar el Descuento, si corresponde; en caso contrario, igual al Subtotal.

## Reglas de negocio

- **R1 · Stock**: No se puede vender una cantidad mayor al Stock disponible del Producto. Si la cantidad iguala al Stock, la Venta es válida y el Stock queda en 0.
- **R2 · Descuento**: Si el Subtotal de la Venta es mayor a S/ 100,00 se aplica 5 % de Descuento sobre el Subtotal. Si es exactamente S/ 100,00 no hay Descuento.
- **R3 · Cantidad**: La cantidad debe ser un entero mayor que cero. Cero, negativos y decimales se rechazan.
- **R4 · Actualización**: Al confirmar la Venta, el Stock de cada Producto se reduce en la cantidad vendida, en la misma transacción.

## Requirements

### Requisito 1: Registrar productos

**Historia de usuario:** Como Doña_Rosa, quiero registrar productos con su precio y Stock inicial, para tener un catálogo actualizado de mi bodega.

#### Criterios de aceptación

1. CUANDO Doña_Rosa proporciona un nombre de Producto no vacío, un precio unitario mayor que cero y un Stock inicial que sea un entero no negativo, EL Sistema DEBERÁ registrar el Producto en el catálogo.
2. SI el nombre del Producto está vacío, el precio unitario es menor o igual a cero, o el Stock inicial no es un entero no negativo, EL Sistema DEBERÁ rechazar el registro del Producto y mostrar un mensaje de error indicando el campo inválido.
3. CUANDO un Producto se registra correctamente, EL Sistema DEBERÁ almacenar el nombre, el precio unitario y el Stock en la base de datos.
4. SI ya existe un Producto con el mismo nombre en el catálogo, EL Sistema DEBERÁ rechazar el registro y mostrar un mensaje de error indicando que ya existe un Producto con ese nombre.

### Requisito 2: Registrar ventas

**Historia de usuario:** Como Doña_Rosa, quiero registrar una Venta seleccionando Productos y cantidades, para dejar de anotar las ventas en el cuaderno.

#### Criterios de aceptación

1. CUANDO Doña_Rosa selecciona un Producto e ingresa una cantidad para una Linea_Venta, EL Sistema DEBERÁ validar que la cantidad sea un entero mayor que cero [R3].
2. SI la cantidad no es un entero mayor que cero (cero, negativa, decimal o no numérica), EL Sistema DEBERÁ rechazar la Linea_Venta y mostrar un mensaje de error indicando que la cantidad es inválida [R3].
3. SI la cantidad excede el Stock disponible del Producto, EL Sistema DEBERÁ rechazar la Linea_Venta y mostrar un mensaje de error indicando que la cantidad excede el Stock disponible [R1].
4. CUANDO la cantidad iguala el Stock disponible del Producto, EL Sistema DEBERÁ aceptar la Linea_Venta y el Stock del Producto DEBERÁ quedar en cero tras la confirmación [R1].
5. CUANDO Doña_Rosa confirma una Venta con al menos una Linea_Venta válida, EL Sistema DEBERÁ registrar la Venta con sus Lineas_Venta.
6. CUANDO Doña_Rosa confirma una Venta con al menos una Linea_Venta válida, EL Sistema DEBERÁ calcular el Subtotal como la suma de (precio unitario × cantidad) para cada Linea_Venta y mostrar el Subtotal a Doña_Rosa.
7. SI Doña_Rosa confirma una Venta sin ninguna Linea_Venta válida, EL Sistema DEBERÁ rechazar la confirmación, mostrar un mensaje de error indicando que la Venta debe contener al menos una Linea_Venta válida y no registrar la Venta.

### Requisito 3: Aplicar descuento

**Historia de usuario:** Como Doña_Rosa, quiero que el Sistema aplique automáticamente el Descuento cuando corresponda, para no equivocarme en el cálculo.

#### Criterios de aceptación

1. CUANDO se calcula el Subtotal de una Venta y este resulta mayor a S/ 100,00, EL Sistema DEBERÁ aplicar un Descuento del 5 % sobre el Subtotal y calcular el Total como Subtotal menos Descuento, con el Descuento y el Total expresados con 2 decimales [R2].
2. CUANDO se calcula el Subtotal de una Venta y este resulta exactamente S/ 100,00, EL Sistema DEBERÁ establecer el Descuento en cero y el Total igual al Subtotal [R2].
3. CUANDO se calcula el Subtotal de una Venta y este resulta menor a S/ 100,00, EL Sistema DEBERÁ establecer el Descuento en cero y el Total igual al Subtotal.

### Requisito 4: Descontar stock

**Historia de usuario:** Como Doña_Rosa, quiero que el Stock se descuente automáticamente al confirmar la Venta, para llevar un control preciso del inventario.

#### Criterios de aceptación

1. CUANDO una Venta se confirma, EL Sistema DEBERÁ reducir el Stock de cada Producto en la cantidad vendida, en la misma transacción de base de datos, de modo que el Stock resultante de cada Producto sea un entero no negativo [R4].
2. SI ocurre un error durante la actualización del Stock de cualquier Producto, EL Sistema DEBERÁ revertir la transacción completa, descartar la Venta, mantener el Stock de todos los Productos sin cambios y mostrar un mensaje de error indicando que la Venta no pudo completarse [R4].
3. CUANDO el Stock de un Producto llega a cero tras una Venta, EL Sistema DEBERÁ mantener el Producto en el catálogo con Stock igual a cero.
