<?php
/**
 * Control financiero y seguimiento - capa de datos (SOLO LECTURA).
 *
 * Lo usan graphs_rentabilidad.php y graphs_cartera.php (submenu GRAFICAS).
 * No crea tablas ni escribe nada: unicamente SELECT sobre cliente, cotizacion,
 * abonos, gastos, gastosauditoria, seguimiento, categorias, usuario y personal.
 *
 * Reglas de negocio (ver ANALISIS_DATOS_VIDEX.md):
 *  - Ingresos       = tabla abonos (NO cotizacion.valorRecibido, que no cuadra).
 *  - Directos       = gastos con idCotizacion > 0.
 *  - Indirectos     = gastos con idCotizacion = 0 (gastos globales).
 *  - Auditoria      = gastosauditoria, imputada al CLIENTE (no existe vinculo
 *                     con una cotizacion concreta).
 *  - Personal       = cliente.interno = 1 (casa, carro, impuestos, sucesion...).
 *                     Se separa del negocio; ver las listas de excepciones abajo.
 */

/** Estados de cotizacion que cuentan como cartera (dinero por cobrar). 65 Aprobada y con pagos, 66 Pendiente de pago. */
const GF_ESTADOS_CARTERA = [65, 66];
const GF_ESTADO_PAGADA = 67;

/** Clientes con interno=1 que en realidad son negocio (KNOX, Bono Anglo). Editable. */
const GF_CLIENTES_FORZAR_NEGOCIO = [74, 350];
/** Clientes con interno=0 que en realidad son personales. Editable. */
const GF_CLIENTES_FORZAR_PERSONAL = [];
/** Cotizaciones personales cuyo cliente ya no existe (la casa de 2023-2024). Editable. */
const GF_COTIZACIONES_PERSONAL = [370, 375, 405];
/**
 * Fragmentos del nombre del TIPO de gasto global que se consideran gastos del
 * dueño (no del negocio). Provisional: confirmar con el responsable. Editable.
 */
const GF_TIPOS_GASTO_DUENO = [
    'Diezmos', 'EPS portes', 'Tarjetas Cr', 'Adelanto de n', 'representaci',
    'Transportes / Clio', 'Obando', 'Impuestos',
];

class GraphsFinanzasService
{
    private $link;
    private $lastError = '';
    private $restriccion = null;   // ids de cliente permitidos (usuarios con clientes_filtro) o null
    private $cacheCot = [];
    private $cacheCli = null;
    private $cacheSeg = null;

    public function __construct()
    {
        $db = new DBbase_Sql;
        $db->connect();
        $this->link = $db->Link_ID;
        $f = isset($_SESSION['clientes_filtro']) ? trim((string) $_SESSION['clientes_filtro']) : '';
        if ($f !== '' && preg_match('/^[0-9,\s]+$/', $f)) {
            $this->restriccion = array_map('intval', array_filter(array_map('trim', explode(',', $f)), 'strlen'));
        }
    }

    public function getLastError() { return $this->lastError; }
    public function esRestringido() { return $this->restriccion !== null; }

    protected function esc($v) { return mysqli_real_escape_string($this->link, (string) $v); }

    protected function filas($sql)
    {
        $res = mysqli_query($this->link, $sql);
        if ($res === false) {
            $this->lastError = mysqli_error($this->link);
            error_log('GraphsFinanzasService SQL error: ' . $this->lastError);
            return [];
        }
        $out = [];
        while ($f = mysqli_fetch_assoc($res)) {
            $out[] = $f;
        }
        mysqli_free_result($res);
        return $out;
    }

    private function clientePermitido($idCliente)
    {
        return $this->restriccion === null || in_array((int) $idCliente, $this->restriccion, true);
    }

    // ------------------------------------------------------------------
    // Periodo
    // ------------------------------------------------------------------

    public static function fechaValida($v)
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $v, $m)) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    /** Devuelve [desde, hasta, anio]. Prioridad: fechas explicitas > anio > anio actual. */
    public static function rango(array $src)
    {
        $d = isset($src['fechaInicial']) ? trim((string) $src['fechaInicial']) : '';
        $h = isset($src['fechaFinal']) ? trim((string) $src['fechaFinal']) : '';
        if (self::fechaValida($d) && self::fechaValida($h)) {
            if ($d > $h) { $t = $d; $d = $h; $h = $t; }
            return [$d, $h, ''];
        }
        $anio = isset($src['anio']) ? trim((string) $src['anio']) : '';
        if ($anio === 'todos') {
            return ['2000-01-01', date('Y-12-31'), 'todos'];
        }
        if (preg_match('/^\d{4}$/', $anio) && (int) $anio >= 2000 && (int) $anio <= 2100) {
            return [$anio . '-01-01', $anio . '-12-31', $anio];
        }
        return [date('Y-01-01'), date('Y-12-31'), date('Y')];
    }

    // ------------------------------------------------------------------
    // Clasificacion negocio / personal
    // ------------------------------------------------------------------

    public static function clienteEsPersonal($interno, $idCliente)
    {
        $id = (int) $idCliente;
        if (in_array($id, GF_CLIENTES_FORZAR_PERSONAL, true)) {
            return true;
        }
        return ((int) $interno === 1) && !in_array($id, GF_CLIENTES_FORZAR_NEGOCIO, true);
    }

    public static function tipoEsDueno($descripcion)
    {
        $d = (string) $descripcion;
        foreach (GF_TIPOS_GASTO_DUENO as $frag) {
            if ($frag !== '' && stripos($d, $frag) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function enAmbito($esPersonal, $ambito)
    {
        if ($ambito === 'personal') { return (bool) $esPersonal; }
        if ($ambito === 'todo') { return true; }
        return !$esPersonal;
    }

    // ------------------------------------------------------------------
    // Cotizaciones (proyectos) con su dinero
    // ------------------------------------------------------------------

    /**
     * Todas las cotizaciones con cobrado/gastado historico y del periodo.
     * Las agregaciones (por cliente, resumen) se hacen en PHP sobre esta lista.
     */
    public function cotizaciones($desde, $hasta)
    {
        $key = $desde . '|' . $hasta;
        if (isset($this->cacheCot[$key])) {
            return $this->cacheCot[$key];
        }
        $d = $this->esc($desde);
        $h = $this->esc(date('Y-m-d', strtotime($hasta . ' +1 day')));
        $sql = "SELECT c.id, c.idCliente, cl.id AS cliente_existe, cl.nombre AS cliente, cl.interno,
                       c.estado, ce.descripcion AS estado_nombre, c.descripcion, c.fechaCotizacion,
                       c.valorCotizado, c.valorCobrado, c.contactoNombre,
                       IFNULL(a.total,0) AS cobrado_total, IFNULL(ap.total,0) AS cobrado_periodo,
                       IFNULL(g.total,0) AS gasto_total, IFNULL(gp.total,0) AS gasto_periodo
                FROM cotizacion c
                LEFT JOIN cliente cl ON cl.id = c.idCliente
                LEFT JOIN categorias ce ON ce.id = c.estado
                LEFT JOIN (SELECT idCotizacion, SUM(valor) AS total FROM abonos GROUP BY idCotizacion) a ON a.idCotizacion = c.id
                LEFT JOIN (SELECT idCotizacion, SUM(valor) AS total FROM abonos
                           WHERE fechaCreacion >= '$d' AND fechaCreacion < '$h' GROUP BY idCotizacion) ap ON ap.idCotizacion = c.id
                LEFT JOIN (SELECT idCotizacion, SUM(valor) AS total FROM gastos
                           WHERE idCotizacion > 0 GROUP BY idCotizacion) g ON g.idCotizacion = c.id
                LEFT JOIN (SELECT idCotizacion, SUM(valor) AS total FROM gastos
                           WHERE idCotizacion > 0 AND fechaCreacion >= '$d' AND fechaCreacion < '$h' GROUP BY idCotizacion) gp ON gp.idCotizacion = c.id";
        $out = [];
        foreach ($this->filas($sql) as $r) {
            $idCli = (int) $r['idCliente'];
            if (!$this->clientePermitido($idCli)) {
                continue;
            }
            $existe = !empty($r['cliente_existe']);
            $personal = in_array((int) $r['id'], GF_COTIZACIONES_PERSONAL, true)
                || ($existe && self::clienteEsPersonal($r['interno'], $idCli));
            $cobradoP = (float) $r['cobrado_periodo'];
            $gastoP = (float) $r['gasto_periodo'];
            $cobradoT = (float) $r['cobrado_total'];
            $gastoT = (float) $r['gasto_total'];
            $aCobrar = (float) $r['valorCobrado'];
            $estado = (int) $r['estado'];
            $pendiente = in_array($estado, GF_ESTADOS_CARTERA, true) ? max($aCobrar - $cobradoT, 0) : 0.0;
            $revisar = '';
            if ($estado === GF_ESTADO_PAGADA && ($aCobrar - $cobradoT) > 1000) {
                $revisar = 'Pagada totalmente pero faltan $' . number_format($aCobrar - $cobradoT, 0, ',', '.');
            } elseif (!$existe) {
                $revisar = 'Cliente eliminado';
            } elseif ($cobradoT == 0 && $gastoT > 0 && !$personal) {
                $revisar = 'Con gasto y sin ingreso';
            }
            $fecha = (string) $r['fechaCotizacion'];
            if ($fecha === '' || strpos($fecha, '0000') === 0) {
                $fecha = '';
            }
            $out[] = [
                'id' => (int) $r['id'],
                'idCliente' => $idCli,
                'cliente' => $existe ? (string) $r['cliente'] : ('Cliente eliminado (id ' . $idCli . ')'),
                'cliente_existe' => $existe,
                'personal' => $personal,
                'estado' => $estado,
                'estado_nombre' => $r['estado_nombre'] !== null && $r['estado_nombre'] !== '' ? (string) $r['estado_nombre'] : ('Estado ' . $estado),
                'descripcion' => (string) $r['descripcion'],
                'fecha' => $fecha,
                'contacto' => (string) $r['contactoNombre'],
                'cotizado' => (float) $r['valorCotizado'],
                'a_cobrar' => $aCobrar,
                'cobrado_p' => $cobradoP,
                'gasto_p' => $gastoP,
                'ganancia_p' => $cobradoP - $gastoP,
                'margen_p' => $cobradoP > 0 ? (($cobradoP - $gastoP) / $cobradoP) * 100 : null,
                'cobrado_t' => $cobradoT,
                'gasto_t' => $gastoT,
                'ganancia_t' => $cobradoT - $gastoT,
                'margen_t' => $cobradoT > 0 ? (($cobradoT - $gastoT) / $cobradoT) * 100 : null,
                'pendiente' => $pendiente,
                'revisar' => $revisar,
            ];
        }
        $this->cacheCot[$key] = $out;
        return $out;
    }

    /** Cotizaciones a mostrar en el periodo: con movimiento, creadas en el periodo o con cartera pendiente. */
    public function proyectosDelPeriodo($desde, $hasta, $ambito)
    {
        $res = [];
        foreach ($this->cotizaciones($desde, $hasta) as $c) {
            if (!self::enAmbito($c['personal'], $ambito)) {
                continue;
            }
            $enPeriodo = $c['fecha'] !== '' && $c['fecha'] >= $desde && $c['fecha'] <= $hasta;
            if ($c['cobrado_p'] != 0 || $c['gasto_p'] != 0 || $enPeriodo || $c['pendiente'] > 0) {
                $res[] = $c;
            }
        }
        return $res;
    }

    // ------------------------------------------------------------------
    // Clientes
    // ------------------------------------------------------------------

    public function clientesMap()
    {
        if ($this->cacheCli !== null) {
            return $this->cacheCli;
        }
        $sql = "SELECT cl.id, cl.nombre, cl.nit, cl.email, cl.telefono, cl.celular, cl.representante_legal,
                       cl.interno, cl.tipoCliente, tc.descripcion AS tipo_nombre, cl.estado, cl.idComercial,
                       uc.nombre AS comercial
                FROM cliente cl
                LEFT JOIN categorias tc ON tc.id = cl.tipoCliente
                LEFT JOIN usuario uc ON uc.id = cl.idComercial";
        $map = [];
        foreach ($this->filas($sql) as $r) {
            $id = (int) $r['id'];
            if (!$this->clientePermitido($id)) {
                continue;
            }
            $r['id'] = $id;
            $r['personal'] = self::clienteEsPersonal($r['interno'], $id);
            $r['existe'] = true;
            $map[$id] = $r;
        }
        $this->cacheCli = $map;
        return $map;
    }

    // ------------------------------------------------------------------
    // Auditoria (costo de personas, imputado al cliente)
    // ------------------------------------------------------------------

    /** [idCliente => ['total'=>, 'periodo'=>]] */
    public function auditoriaPorCliente($desde, $hasta)
    {
        $d = $this->esc($desde);
        $h = $this->esc(date('Y-m-d', strtotime($hasta . ' +1 day')));
        $rows = $this->filas("SELECT idCliente, SUM(valor) AS total,
                SUM(CASE WHEN fechaCreacion >= '$d' AND fechaCreacion < '$h' THEN valor ELSE 0 END) AS periodo
                FROM gastosauditoria GROUP BY idCliente");
        $out = [];
        foreach ($rows as $r) {
            $id = (int) $r['idCliente'];
            if ($this->clientePermitido($id)) {
                $out[$id] = ['total' => (float) $r['total'], 'periodo' => (float) $r['periodo']];
            }
        }
        return $out;
    }

    public function auditoriaDetalleCliente($idCliente)
    {
        return $this->filas("SELECT p.nombres AS persona, cat.descripcion AS tipo, COUNT(*) AS n, SUM(ga.valor) AS total,
                MAX(ga.fechaCreacion) AS ultimo
                FROM gastosauditoria ga
                LEFT JOIN personal p ON p.cc = ga.idPersona
                LEFT JOIN categorias cat ON cat.id = ga.tipo
                WHERE ga.idCliente = " . (int) $idCliente . "
                GROUP BY p.nombres, cat.descripcion ORDER BY total DESC");
    }

    // ------------------------------------------------------------------
    // Gastos globales (indirectos)
    // ------------------------------------------------------------------

    /**
     * Gastos globales del periodo. Devuelve:
     *  porTipo: [{tipo, n, total, dueno}]  y  porMes: [mes => ['negocio'=>, 'dueno'=>]]
     */
    public function globales($desde, $hasta)
    {
        if ($this->restriccion !== null) {
            return ['porTipo' => [], 'porMes' => [], 'restringido' => true];
        }
        $d = $this->esc($desde);
        $h = $this->esc(date('Y-m-d', strtotime($hasta . ' +1 day')));
        $rows = $this->filas("SELECT DATE_FORMAT(g.fechaCreacion, '%Y-%m') AS mes, g.tipo, cat.descripcion AS tipo_nombre,
                COUNT(*) AS n, SUM(g.valor) AS total
                FROM gastos g LEFT JOIN categorias cat ON cat.id = g.tipo
                WHERE g.idCotizacion = 0 AND g.fechaCreacion >= '$d' AND g.fechaCreacion < '$h'
                GROUP BY mes, g.tipo, cat.descripcion");
        $porTipo = [];
        $porMes = [];
        foreach ($rows as $r) {
            $nombre = ($r['tipo_nombre'] !== null && $r['tipo_nombre'] !== '') ? (string) $r['tipo_nombre'] : '(sin tipo)';
            $dueno = self::tipoEsDueno($nombre);
            if (!isset($porTipo[$nombre])) {
                $porTipo[$nombre] = ['tipo' => $nombre, 'n' => 0, 'total' => 0.0, 'dueno' => $dueno];
            }
            $porTipo[$nombre]['n'] += (int) $r['n'];
            $porTipo[$nombre]['total'] += (float) $r['total'];
            $mes = (string) $r['mes'];
            if (!isset($porMes[$mes])) {
                $porMes[$mes] = ['negocio' => 0.0, 'dueno' => 0.0];
            }
            $porMes[$mes][$dueno ? 'dueno' : 'negocio'] += (float) $r['total'];
        }
        usort($porTipo, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        return ['porTipo' => array_values($porTipo), 'porMes' => $porMes, 'restringido' => false];
    }

    // ------------------------------------------------------------------
    // Resumen y series
    // ------------------------------------------------------------------

    /** Estado de resultados del periodo para un ambito (negocio|personal|todo). */
    public function resumen($desde, $hasta, $ambito)
    {
        $ingresos = $directos = $pendiente = 0.0;
        $nProy = 0;
        foreach ($this->proyectosDelPeriodo($desde, $hasta, $ambito) as $c) {
            $ingresos += $c['cobrado_p'];
            $directos += $c['gasto_p'];
            $pendiente += $c['pendiente'];
            $nProy++;
        }
        $clientes = $this->clientesMap();
        $cotPorCliente = [];
        foreach ($this->cotizaciones($desde, $hasta) as $c) {
            if (!isset($cotPorCliente[$c['idCliente']])) {
                $cotPorCliente[$c['idCliente']] = ['personal' => true];
            }
            if (!$c['personal']) {
                $cotPorCliente[$c['idCliente']]['personal'] = false;
            }
        }
        $auditoria = 0.0;
        foreach ($this->auditoriaPorCliente($desde, $hasta) as $idCli => $a) {
            if (isset($clientes[$idCli])) {
                $pers = $clientes[$idCli]['personal'];
            } else {
                $pers = isset($cotPorCliente[$idCli]) ? $cotPorCliente[$idCli]['personal'] : false;
            }
            if (self::enAmbito($pers, $ambito)) {
                $auditoria += $a['periodo'];
            }
        }
        $g = $this->globales($desde, $hasta);
        $glNegocio = $glDueno = 0.0;
        foreach ($g['porTipo'] as $t) {
            if ($t['dueno']) { $glDueno += $t['total']; } else { $glNegocio += $t['total']; }
        }
        if ($ambito === 'negocio') { $globales = $glNegocio; }
        elseif ($ambito === 'personal') { $globales = $glDueno; }
        else { $globales = $glNegocio + $glDueno; }

        $contribucion = $ingresos - $directos - $auditoria;
        return [
            'ingresos' => $ingresos,
            'directos' => $directos,
            'auditoria' => $auditoria,
            'contribucion' => $contribucion,
            'margen_contribucion' => $ingresos > 0 ? ($contribucion / $ingresos) * 100 : null,
            'globales' => $globales,
            'globales_negocio' => $glNegocio,
            'globales_dueno' => $glDueno,
            'resultado' => $contribucion - $globales,
            'margen_resultado' => $ingresos > 0 ? (($contribucion - $globales) / $ingresos) * 100 : null,
            'pendiente' => $pendiente,
            'n_proyectos' => $nProy,
            'globales_restringido' => !empty($g['restringido']),
        ];
    }

    /** Serie mensual del periodo: [mes => ingresos, directos, auditoria, globales, resultado]. */
    public function serieMensual($desde, $hasta, $ambito)
    {
        $d = $this->esc($desde);
        $h = $this->esc(date('Y-m-d', strtotime($hasta . ' +1 day')));
        $cotPers = [];
        foreach ($this->cotizaciones($desde, $hasta) as $c) {
            $cotPers[$c['id']] = $c['personal'];
        }
        $clientes = $this->clientesMap();
        $meses = [];
        $sumar = function ($mes, $campo, $valor) use (&$meses) {
            if (!isset($meses[$mes])) {
                $meses[$mes] = ['ingresos' => 0.0, 'directos' => 0.0, 'auditoria' => 0.0, 'globales' => 0.0];
            }
            $meses[$mes][$campo] += $valor;
        };
        $ab = $this->filas("SELECT DATE_FORMAT(fechaCreacion, '%Y-%m') AS mes, idCotizacion, SUM(valor) AS total
                FROM abonos WHERE fechaCreacion >= '$d' AND fechaCreacion < '$h' GROUP BY mes, idCotizacion");
        foreach ($ab as $r) {
            $id = (int) $r['idCotizacion'];
            if (isset($cotPers[$id]) && self::enAmbito($cotPers[$id], $ambito)) {
                $sumar($r['mes'], 'ingresos', (float) $r['total']);
            }
        }
        $ga = $this->filas("SELECT DATE_FORMAT(fechaCreacion, '%Y-%m') AS mes, idCotizacion, SUM(valor) AS total
                FROM gastos WHERE idCotizacion > 0 AND fechaCreacion >= '$d' AND fechaCreacion < '$h' GROUP BY mes, idCotizacion");
        foreach ($ga as $r) {
            $id = (int) $r['idCotizacion'];
            if (isset($cotPers[$id]) && self::enAmbito($cotPers[$id], $ambito)) {
                $sumar($r['mes'], 'directos', (float) $r['total']);
            }
        }
        $au = $this->filas("SELECT DATE_FORMAT(fechaCreacion, '%Y-%m') AS mes, idCliente, SUM(valor) AS total
                FROM gastosauditoria WHERE fechaCreacion >= '$d' AND fechaCreacion < '$h' GROUP BY mes, idCliente");
        foreach ($au as $r) {
            $id = (int) $r['idCliente'];
            if (!$this->clientePermitido($id)) {
                continue;
            }
            $pers = isset($clientes[$id]) ? $clientes[$id]['personal'] : false;
            if (self::enAmbito($pers, $ambito)) {
                $sumar($r['mes'], 'auditoria', (float) $r['total']);
            }
        }
        $g = $this->globales($desde, $hasta);
        foreach ($g['porMes'] as $mes => $v) {
            if ($ambito === 'negocio') { $val = $v['negocio']; }
            elseif ($ambito === 'personal') { $val = $v['dueno']; }
            else { $val = $v['negocio'] + $v['dueno']; }
            $sumar($mes, 'globales', $val);
        }
        ksort($meses);
        foreach ($meses as $mes => &$m) {
            $m['resultado'] = $m['ingresos'] - $m['directos'] - $m['auditoria'] - $m['globales'];
        }
        unset($m);
        return $meses;
    }

    // ------------------------------------------------------------------
    // Seguimientos (comunicacion con el cliente)
    // ------------------------------------------------------------------

    /** Todos los seguimientos, del mas reciente al mas antiguo, con cliente resuelto. */
    public function seguimientos()
    {
        if ($this->cacheSeg !== null) {
            return $this->cacheSeg;
        }
        $rows = $this->filas("SELECT s.id, s.idCotizacion, s.idCliente AS s_cliente, c.idCliente AS c_cliente,
                s.tipo, t.descripcion AS medio, s.idUsuario, u.nombre AS usuario, s.observacion, s.fechaCreacion,
                c.contactoNombre AS contacto
                FROM seguimiento s
                LEFT JOIN cotizacion c ON c.id = s.idCotizacion
                LEFT JOIN categorias t ON t.id = s.tipo
                LEFT JOIN usuario u ON u.id = s.idUsuario
                ORDER BY s.fechaCreacion DESC, s.id DESC");
        $out = [];
        foreach ($rows as $r) {
            $cid = (int) $r['s_cliente'] > 0 ? (int) $r['s_cliente'] : (int) $r['c_cliente'];
            if (!$this->clientePermitido($cid)) {
                continue;
            }
            $out[] = [
                'id' => (int) $r['id'],
                'idCliente' => $cid,
                'idCotizacion' => (int) $r['idCotizacion'],
                'medio' => $r['medio'] !== null && $r['medio'] !== '' ? (string) $r['medio'] : 'Sin medio',
                'usuario' => $r['usuario'] !== null && $r['usuario'] !== '' ? (string) $r['usuario'] : 'Desconocido',
                'observacion' => (string) $r['observacion'],
                'fecha' => (string) $r['fechaCreacion'],
                'contacto' => (string) $r['contacto'],
            ];
        }
        $this->cacheSeg = $out;
        return $out;
    }

    // ------------------------------------------------------------------
    // Cartera: una fila por cliente (dinero + ultima comunicacion)
    // ------------------------------------------------------------------

    public function cartera($desde, $hasta, $ambito)
    {
        $clientes = $this->clientesMap();
        $filas = [];
        $cotPersonalesCliente = [];

        foreach ($this->cotizaciones($desde, $hasta) as $c) {
            $id = $c['idCliente'];
            if (!isset($filas[$id])) {
                $base = isset($clientes[$id]) ? $clientes[$id] : [
                    'id' => $id, 'nombre' => $c['cliente'], 'nit' => '', 'email' => '', 'telefono' => '', 'celular' => '',
                    'representante_legal' => '', 'interno' => 0, 'tipo_nombre' => '', 'comercial' => '', 'estado' => 1,
                    'personal' => false, 'existe' => false,
                ];
                $filas[$id] = $this->filaCartera($base);
                $cotPersonalesCliente[$id] = true;
            }
            $f = &$filas[$id];
            $f['n_cot']++;
            if ($c['estado'] === 65 || $c['estado'] === 66 || $c['estado'] === 67) {
                $f['n_aprobadas']++;
            }
            $f['cotizado'] += $c['cotizado'];
            $f['cobrado_p'] += $c['cobrado_p'];
            $f['gasto_p'] += $c['gasto_p'];
            $f['cobrado_t'] += $c['cobrado_t'];
            $f['gasto_t'] += $c['gasto_t'];
            $f['pendiente'] += $c['pendiente'];
            if (!$c['personal']) {
                $cotPersonalesCliente[$id] = false;
            }
            unset($f);
        }
        foreach ($filas as $id => &$f) {
            if (!isset($clientes[$id])) {
                $f['personal'] = !empty($cotPersonalesCliente[$id]);
            }
        }
        unset($f);

        $aud = $this->auditoriaPorCliente($desde, $hasta);
        foreach ($aud as $id => $a) {
            if (!isset($filas[$id]) && isset($clientes[$id])) {
                $filas[$id] = $this->filaCartera($clientes[$id]);
            }
            if (isset($filas[$id])) {
                $filas[$id]['aud_p'] = $a['periodo'];
                $filas[$id]['aud_t'] = $a['total'];
            }
        }

        // Comunicacion
        $porCliente = [];
        foreach ($this->seguimientos() as $s) {
            $porCliente[$s['idCliente']][] = $s;
        }
        foreach ($porCliente as $id => $lista) {
            if (!isset($filas[$id]) && isset($clientes[$id])) {
                $filas[$id] = $this->filaCartera($clientes[$id]);
            }
            if (!isset($filas[$id])) {
                continue;
            }
            $f = &$filas[$id];
            $f['ultimo'] = $lista[0];
            $f['n_contactos_t'] = count($lista);
            foreach ($lista as $s) {
                $dia = substr($s['fecha'], 0, 10);
                if ($dia >= $desde && $dia <= $hasta) {
                    $f['n_contactos_p']++;
                    if (!isset($f['medios_p'][$s['medio']])) { $f['medios_p'][$s['medio']] = 0; }
                    $f['medios_p'][$s['medio']]++;
                }
            }
            unset($f);
        }

        $hoy = strtotime(date('Y-m-d'));
        $res = [];
        foreach ($filas as $f) {
            if (!self::enAmbito($f['personal'], $ambito)) {
                continue;
            }
            $f['ganancia_p'] = $f['cobrado_p'] - $f['gasto_p'] - $f['aud_p'];
            $f['margen_p'] = $f['cobrado_p'] > 0 ? ($f['ganancia_p'] / $f['cobrado_p']) * 100 : null;
            $f['ganancia_t'] = $f['cobrado_t'] - $f['gasto_t'] - $f['aud_t'];
            if ($f['ultimo'] !== null) {
                $f['dias'] = (int) floor(($hoy - strtotime(substr($f['ultimo']['fecha'], 0, 10))) / 86400);
            } else {
                $f['dias'] = null;
            }
            $f['semaforo'] = self::semaforo($f['dias']);
            // Solo clientes con actividad: cotizaciones, auditoria o comunicacion
            if ($f['n_cot'] > 0 || $f['aud_t'] > 0 || $f['ultimo'] !== null) {
                $res[] = $f;
            }
        }
        return $res;
    }

    private function filaCartera(array $c)
    {
        return [
            'id' => (int) $c['id'],
            'nombre' => (string) $c['nombre'],
            'nit' => isset($c['nit']) ? (string) $c['nit'] : '',
            'email' => isset($c['email']) ? (string) $c['email'] : '',
            'telefono' => isset($c['telefono']) ? (string) $c['telefono'] : '',
            'celular' => isset($c['celular']) ? (string) $c['celular'] : '',
            'representante' => isset($c['representante_legal']) ? (string) $c['representante_legal'] : '',
            'tipo' => isset($c['tipo_nombre']) ? (string) $c['tipo_nombre'] : '',
            'comercial' => isset($c['comercial']) ? (string) $c['comercial'] : '',
            'activo' => !isset($c['estado']) || (int) $c['estado'] !== 0,
            'personal' => !empty($c['personal']),
            'existe' => !empty($c['existe']),
            'n_cot' => 0, 'n_aprobadas' => 0, 'cotizado' => 0.0,
            'cobrado_p' => 0.0, 'gasto_p' => 0.0, 'aud_p' => 0.0,
            'cobrado_t' => 0.0, 'gasto_t' => 0.0, 'aud_t' => 0.0,
            'pendiente' => 0.0,
            'ultimo' => null, 'n_contactos_t' => 0, 'n_contactos_p' => 0, 'medios_p' => [],
        ];
    }

    /** verde <=30 dias, amarillo <=90, rojo >90 o sin registro. */
    public static function semaforo($dias)
    {
        if ($dias === null) { return 'rojo'; }
        if ($dias <= 30) { return 'verde'; }
        if ($dias <= 90) { return 'amarillo'; }
        return 'rojo';
    }

    // ------------------------------------------------------------------
    // Ficha de un cliente
    // ------------------------------------------------------------------

    public function ficha($idCliente, $desde, $hasta)
    {
        $idCliente = (int) $idCliente;
        if ($idCliente <= 0 || !$this->clientePermitido($idCliente)) {
            return null;
        }
        $clientes = $this->clientesMap();
        $cots = [];
        foreach ($this->cotizaciones($desde, $hasta) as $c) {
            if ($c['idCliente'] === $idCliente) {
                $cots[] = $c;
            }
        }
        if (!isset($clientes[$idCliente]) && !$cots) {
            return null;
        }
        $cli = isset($clientes[$idCliente]) ? $clientes[$idCliente] : [
            'id' => $idCliente, 'nombre' => 'Cliente eliminado (id ' . $idCliente . ')', 'nit' => '', 'email' => '',
            'telefono' => '', 'celular' => '', 'representante_legal' => '', 'interno' => 0, 'tipo_nombre' => '',
            'comercial' => '', 'estado' => 0, 'personal' => false, 'existe' => false,
        ];
        $seg = [];
        foreach ($this->seguimientos() as $s) {
            if ($s['idCliente'] === $idCliente) {
                $seg[] = $s;
            }
        }
        $ids = [0];
        foreach ($cots as $c) { $ids[] = (int) $c['id']; }
        $in = implode(',', $ids);
        $abonos = $this->filas("SELECT a.id, a.idCotizacion, a.fechaCreacion AS fecha, a.valor, a.observacion, t.descripcion AS tipo
                FROM abonos a LEFT JOIN categorias t ON t.id = a.tipo
                WHERE a.idCotizacion IN ($in) ORDER BY a.fechaCreacion DESC, a.id DESC LIMIT 100");
        $gastos = $this->filas("SELECT g.id, g.idCotizacion, g.fechaCreacion AS fecha, g.valor, g.observacion, t.descripcion AS tipo
                FROM gastos g LEFT JOIN categorias t ON t.id = g.tipo
                WHERE g.idCotizacion IN ($in) AND g.idCotizacion > 0 ORDER BY g.fechaCreacion DESC, g.id DESC LIMIT 100");
        $auditoria = $this->auditoriaDetalleCliente($idCliente);
        $audTot = $this->auditoriaPorCliente($desde, $hasta);

        // Contacto principal: el de la cotizacion mas reciente que tenga nombre
        $contacto = null;
        $mejor = '';
        $q = $this->filas("SELECT contactoNombre, contactoTelefono, contactoCelular, contactoEmail, fechaCotizacion
                FROM cotizacion WHERE idCliente = $idCliente AND contactoNombre <> ''
                ORDER BY fechaCotizacion DESC, id DESC LIMIT 1");
        if ($q) {
            $contacto = [
                'nombre' => (string) $q[0]['contactoNombre'], 'telefono' => (string) $q[0]['contactoTelefono'],
                'celular' => (string) $q[0]['contactoCelular'], 'email' => (string) $q[0]['contactoEmail'],
            ];
        }
        return [
            'cliente' => $cli, 'cotizaciones' => $cots, 'seguimientos' => $seg,
            'abonos' => $abonos, 'gastos' => $gastos, 'auditoria' => $auditoria,
            'aud_periodo' => isset($audTot[$idCliente]) ? $audTot[$idCliente]['periodo'] : 0.0,
            'aud_total' => isset($audTot[$idCliente]) ? $audTot[$idCliente]['total'] : 0.0,
            'contacto' => $contacto,
        ];
    }

    /** Lista de tipos de cliente presentes (para filtros). */
    public static function valoresUnicos(array $filas, $campo)
    {
        $v = [];
        foreach ($filas as $f) {
            if (isset($f[$campo]) && $f[$campo] !== '') { $v[$f[$campo]] = true; }
        }
        $k = array_keys($v);
        sort($k);
        return $k;
    }
}
