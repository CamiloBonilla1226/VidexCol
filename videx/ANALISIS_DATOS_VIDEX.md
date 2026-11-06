# Análisis de los datos de VIDEX (base `twsselvr_videx_app`)

> Complemento de [ANALISIS_PROYECTO_VIDEX.md](ANALISIS_PROYECTO_VIDEX.md) (que explica el código).
> Este documento explica **qué dicen los datos reales**, según las consultas que corriste en la base.
> Fecha: 2026-11-06. No se modificó nada en el proyecto ni en la base de datos.
>
> **Advertencia sobre las cifras:** las sumas por grupo (negocio / personal) las hice a mano desde listados de 25–80 filas.
> Son **aproximadas** y sirven para ver proporciones, no para cerrar contabilidad. Varias consultas salieron cortadas en 25 filas
> (límite de phpMyAdmin); lo pendiente está en la sección 8.

---

## 1. Conclusión principal

> **Los totales que hoy muestra el sistema mezclan el negocio con las finanzas personales/familiares.**
> Si se separan, el negocio es bastante más rentable de lo que parece, y la mayor parte de los "gastos directos" históricos no pertenece al negocio.

| | Abonos (ingresos) | Gastos directos | Auditoría (costo) |
|---|---|---|---|
| **Negocio** (`cliente.interno = 0`) | ≈ 490 M | ≈ 54 M | ≈ 60 M |
| **Personal / familiar** (`cliente.interno = 1`) | ≈ 320 M | ≈ 337 M | ≈ 3 M |

Cerca del **85 %** de lo que el sistema llama "gastos directos" es personal.

---

## 2. Cómo está organizado el dinero en la base

```
cliente  ──<  cotizacion  ──<  abonos   (lo que entra)
                           └─<  gastos   (idCotizacion > 0  → "directo" a esa cotización)

gastos con idCotizacion = 0  → "gastos globales"  (sin proyecto)   ← lo que sería "indirecto"
gastosauditoria  (costo de personas por cliente/proyecto de auditoría; no hay abonosauditoria)
```

- **Directo** = gasto ligado a una cotización (un proyecto concreto): `gastos.idCotizacion > 0`.
- **Indirecto** = gasto de la empresa que sirve a todo y no es de un proyecto: `gastos.idCotizacion = 0`.
- **El tipo de gasto NO define si es directo o indirecto.** "Servidores", "Ingeniero de Sistemas", "Dominio/SSL" y "Digitador" aparecen tanto en globales como en directos. Lo único que decide es si el gasto tiene cotización.

---

## 3. Hallazgo 1 — Lo personal está registrado como si fuera "proyecto"

La familia/dueño usa el mismo sistema: crea un "cliente" y una "cotización" para llevar casa, carro, impuestos y sucesión. Los "abonos" ahí son **aportes de la familia**, no ingresos de clientes.

La marca que lo separa es **`cliente.interno = 1`** junto con **`tipoCliente = 175`**.

Principales registros personales (valores en pesos, aproximados):

| Cliente / cotización | Abonos | Gastos |
|---|---|---|
| BMW X1 compra carro de familia | 52,9 M | 71,6 M |
| Boulevard Niza 2025 Casa | 54,1 M | 50,0 M |
| Boulevard Niza 2026 Casa | 36,1 M | 42,1 M |
| Boulevard Niza 2021 casa | 30,6 M | 29,9 M |
| Boulevard Niza 2020 casa | 14,5 M | 28,2 M |
| Boulevard Niza 2022 casa | 29,3 M | 28,2 M |
| Sucesión Obando Posada | 30,0 M | 30,0 M |
| Aportes x María Teresa Obando (impuestos + sucesión) | 53,1 M | – |
| Tolú 2020 / 2021 pago de impuestos | – | 34,3 M / 9,7 M |
| Tolú mantenimientos y arreglos | – | 3,1 M |

En los gastos, el tipo **"Casa Obando Posada + Sucesión"** reúne ~758 registros y ~222 M; **"Impuestos"** ~44 M.

**Efecto:** en 2022, de 184 M de abonos, gran parte son aportes familiares; en 2025, de 130 M de abonos ~54 M son de la casa y de 51 M de gastos directos ~50 M son de la casa. El negocio real tuvo directos de apenas ~1 M ese año.

### Casos que no encajan limpio con `interno`
| Caso | Qué pasa | Duda |
|---|---|---|
| **Sistema KNOX** | `interno=1` pero es empresa (tipo 105): 3,85 M abonos / 6,1 M gastos | Parece negocio mal marcado |
| **CCO Bono ANUAL ANGLO Colombiano** | `interno=1`, tipo 175: 15,5 M de abonos, sin gastos | Parece ingreso del negocio |
| **CCO Proceso Ley de apoyos** | `interno=1`: 3,4 M de gasto, sin ingreso | ¿Costo del negocio o aporte? |
| **IA + Prompt + Ing./Diseñadores + VidEx** | `interno=1`, tipo 117: 3 M en auditoría | Desarrollo interno de VidEx (inversión propia) |
| **Posibles Clientes / Plataforma Educativa** | 28 cotizaciones, 2,1 M gasto, 0 abonos | Costo de prospección comercial |

---

## 4. Hallazgo 2 — Clientes reales (negocio)

Son clientes con `interno = 0`: mayoritariamente **colegios** (tipo 60), **iglesias y fundaciones** (59, 104) y **empresas** (105).

| Cliente | Abonos | Gasto directo | Costo auditoría |
|---|---|---|---|
| Liceo Anglo Colombiano | 172,6 M | 20,1 M | 9,3 M |
| Colegio Estrada María Auxiliadora | 102,6 M | 8,6 M | 11,3 M |
| Campestre Principado de Mónaco | 52,1 M | 1,4 M | 19,4 M |
| Centro Educativo Children's World | 38,3 M | 1,3 M | 2,3 M |
| Fundación Satura Colombia | 26,5 M | 4,9 M | 2,6 M |
| Confraternidad Carcelaria de Colombia | 26,0 M | 0,5 M | 6,8 M |
| IGLEICO | 8,0 M | 1,0 M | 4,7 M |
| Inversiones Jervas & CIA | 5,7 M | 1,3 M | 3,2 M |

Lectura: **seis o siete clientes concentran la mayor parte del ingreso**; Anglo y Estrada solos pasan los 270 M. En auditoría el costo es alto en varios colegios (Principado de Mónaco: 19 M contra 52 M de ingreso).

---

## 5. Hallazgo 3 — Cotizaciones cuyo cliente ya no existe

Hay cotizaciones con `idCliente` que apunta a un cliente **borrado** de la tabla `cliente` (ej. ids 4, 49, 78, 88, 99, 101, 102 y prospectos 2101–2104).

- Son **ingresos reales del negocio** (desarrollo web, dashboards, portales, renovaciones de dominio/hosting). En las 25 filas visibles suman **≈ 30 M en abonos**, de los cuales ≈ 24 M son del "Portal de Iglesias Cristianas".
- Cualquier reporte que use `INNER JOIN cliente` **pierde ese dinero**. Esto afecta también a [graphs_costos_directos.php](graphs_costos_directos.php).
- Varias tienen `estado = 170`, que **no existe** en `categorias` (sale vacío). Cuatro de ellas suman ≈ 26,5 M.
- También hay cotizaciones de la casa sin cliente (370, 375, 405), que son personales.

**Regla de diseño:** los reportes deben partir de `cotizacion` con `LEFT JOIN cliente` y mostrar "Cliente eliminado (id X)".

---

## 6. Hallazgo 4 — Totales por año (todo mezclado)

| Año | Abonos | Gasto directo | Gasto global |
|---|---|---|---|
| 2017 | 23,0 M | 7,6 M | – |
| 2018 | 38,4 M | 15,0 M | 13,7 M |
| 2019 | 42,5 M | 10,1 M | 10,3 M |
| 2020 | 90,7 M | 72,6 M | 19,0 M |
| 2021 | 111,5 M | 52,4 M | 30,4 M |
| 2022 | 184,4 M | 130,6 M | 40,4 M |
| 2023 | 105,2 M | 46,6 M | 25,1 M |
| 2024 | 92,5 M | 18,9 M | 42,8 M |
| 2025 | 130,1 M | 51,0 M | **falta** |
| 2026 | **falta** | **falta** | **falta** |

- 2017 no tiene gastos globales: probablemente ese registro aún no se usaba.
- Los **globales crecen de 13,7 M (2018) a 42,8 M (2024)**. En 2024 son más del doble de los directos: la carga indirecta es lo que más pesa.
- Los picos de directos de 2020–2022 coinciden con la **casa Boulevard Niza** y los **impuestos de Tolú**, no con el negocio.

---

## 7. Hallazgo 5 — Qué hay en los gastos globales (indirectos)

Suman ≈ 250 M en el listado visto (2018–2025, incompleto). Mi **clasificación provisional**, que debes confirmar:

**Probablemente del negocio (≈ 148 M)**
| Tipo | Total global |
|---|---|
| Ingeniero de Sistemas | 43,9 M |
| Servidores (Banahosting, Namecheap, ChatGPT) | 40,4 M |
| Celular / internet | 17,8 M |
| Digitador / temporales | 13,7 M |
| Asistente VidEx | 7,6 M |
| Renovación CCB | 7,3 M |
| Fotocopias / impresos / hardware | 6,5 M |
| Dominio / SSL | 5,2 M |
| Otros de operación, Webex/Zoom, IA, diseñador, servidores SMART | ≈ 6 M |

**Probablemente personales / del dueño (≈ 104 M)**
| Tipo | Total global |
|---|---|
| Gastos de representación / viajes / restaurante | 23,5 M |
| Transportes / Clio / gasolina | 21,5 M |
| Diezmos y ofrendas | 20,8 M |
| EPS / pensión / Colsanitas / odontología | 17,9 M |
| Tarjetas de crédito | 13,5 M |
| Adelanto de nómina / auto préstamos | 6,5 M |
| Impuestos, otros "Obando Ramírez" | ≈ 0,7 M |

Si esos ≈ 104 M se tratan como costo del negocio, se distorsiona la rentabilidad; si se excluyen, hay que decir explícitamente que son gastos del dueño.

---

## 8. Calidad de datos (problemas detectados)

| Problema | Cantidad | Valor |
|---|---|---|
| Gastos con cotización inexistente | 4 | 205 mil |
| Abonos con cotización inexistente | 4 | 2,76 M |
| Gastos directos sin `idCliente` | 40 | 2,6 M |
| Gastos globales con `idCliente` | 10 | 1,9 M |
| Gastos con valor ≤ 0 | 3 | 0 |
| Estado 170 sin nombre en `categorias` | varias cotizaciones | ≈ 26,5 M en abonos |
| Cotizaciones con fecha `0000-00-00` | 46 | incluye estado 234 "DESARROLLO de MJOP" |
| `valorRecibido` ≠ suma de abonos | muchas | no usar como fuente de verdad |

Además, `gastosauditoria` tiene 228 registros por ≈ 68 M, **con el tipo vacío en la mayoría** y **sin ningún abono de auditoría** (`abonosauditoria` = 0). Es solo costo (pagos a personas por cliente).

**Regla:** el dinero que pagan sale de la tabla `abonos`, **no** de `cotizacion.valorRecibido`.

---

## 9. Conclusión para el modelo de reportes

1. **Fuente de verdad:** `abonos` (ingresos), `gastos` (directos e indirectos) y `gastosauditoria` (costo de personas por cliente).
2. **Separar negocio de personal** con `cliente.interno` (0 = negocio, 1 = personal), con excepciones manuales (KNOX, Bono Anglo, IA/VidEx).
3. **Directo** = `gastos.idCotizacion > 0` + `gastosauditoria` del mismo cliente (a confirmar).
4. **Indirecto** = `gastos.idCotizacion = 0`, excluyendo los tipos personales; el resto se reparte entre proyectos.
5. **Reparto de indirectos** (a decidir): proporcional a ingresos, partes iguales, o manual con una tabla `gasto_asignacion`.
6. **Reportes**: por año y proyecto → ingresos, directos, auditoría, indirectos asignados, utilidad y margen; sección separada para lo personal; exportar a Excel.
7. **Reportes basados en `cotizacion` con `LEFT JOIN cliente`** para no perder las de clientes borrados.

---

## 10. Pendientes y decisiones

**Datos que faltan (resultados cortados en 25 filas):**
- Totales por año completos: falta `gasto_global` de 2025 y todo 2026.
- Cotizaciones de clientes borrados: lista completa (solo vimos 25).
- Cotizaciones por año/estado desde 2021 (se vio hasta 2020).

**Decisiones tuyas:**
1. ¿Casa, carro, impuestos y sucesión quedan **fuera** de la rentabilidad del negocio y se muestran aparte?
2. Los gastos del dueño (diezmos, EPS, tarjetas, adelantos, representación): ¿se **excluyen** de los indirectos o se muestran como "gastos del dueño"?
3. ¿El costo de auditoría se descuenta de los ingresos del mismo cliente?
4. ¿Cómo se reparten los indirectos entre proyectos?
5. ¿KNOX, el Bono Anglo y "IA + VidEx" cuentan como negocio?
6. ¿La unidad "proyecto" es la **cotización** o el **cliente** (año)?
