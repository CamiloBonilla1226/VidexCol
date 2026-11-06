<?php
/**
 * USUARIO_BUSCAR_EXCEL.PHP
 *
 * Exportación a CSV
 * - Formato estándar y confiable
 * - Compatible con todos los spreadsheets
 * - Sin dependencias adicionales
 */

class UsuarioBuscarExcel {

    /**
     * Exportar a CSV
     */
    public function exportToCSV($usuarios, $ctrl, $params = []) {
        $filename = 'usuarios_' . date('Ymd_His') . '.csv';

        // Headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM para Excel
        echo "\xEF\xBB\xBF";

        // Abrir output como CSV
        $output = fopen('php://output', 'w');

        // Headers de columnas
        $headers = $this->getHeaders($ctrl);
        fputcsv($output, $headers, ';');

        // Datos
        foreach ($usuarios as $usuario) {
            $row = $this->flattenRow($usuario, $ctrl);
            fputcsv($output, $row, ';');
        }

        fclose($output);
    }

    /**
     * Obtener headers según tipo de usuario
     */
    private function getHeaders($ctrl) {
        $headers = [
            'ID',
            'Tipo Usuario',
            'Acceso Sistema',
            'Nombre',
            'Identificación',
            'Tipo Identificación',
            'Dirección',
            'Teléfono 1',
            'Teléfono 2',
            'Celular',
            'Celular 2',
            'Email',
            'Página Web',
            'Observaciones',
        ];

        // Agregar columnas específicas por tipo
        if ($ctrl == "" || $ctrl == 0 || $ctrl == 4) {
            $headers[] = 'Autorizado Cliente';
        }

        if ($ctrl == "" || $ctrl == 0 || $ctrl == 1 || $ctrl == 4) {
            $headers[] = 'Empresa - Tipo';
            $headers[] = 'Empresa - Nombre';
            $headers[] = 'Empresa - NIT';
            $headers[] = 'Empresa - Representante';
            $headers[] = 'Empresa - Contacto';
            $headers[] = 'Empresa - Dirección';
            $headers[] = 'Empresa - URL';
            $headers[] = 'Empresa - Teléfono 1';
            $headers[] = 'Empresa - Teléfono 2';
            $headers[] = 'Empresa - Celular 1';
            $headers[] = 'Empresa - Celular 2';
            $headers[] = 'Empresa - Email 1';
            $headers[] = 'Empresa - Email 2';
            $headers[] = 'Empresa - Cargo';
        }

        if ($ctrl == "" || $ctrl == 0 || $ctrl == 2) {
            $headers[] = 'Cliente - Tipo Persona';
            $headers[] = 'Cliente - Tipo Servicio';
            $headers[] = 'Cliente - Tipo Contrato';
            $headers[] = 'Cliente - Observaciones';
            $headers[] = 'Cliente - Valor Contrato';
            $headers[] = 'Cliente - Día de Pago';
            $headers[] = 'Cliente - Fecha Aprobación';
            $headers[] = 'Cliente - Fecha Aprobación Contrato';
            $headers[] = 'Cliente - Fecha Inicial Contrato';
            $headers[] = 'Cliente - Fecha Final Contrato';
        }

        if ($ctrl == "" || $ctrl == 0 || $ctrl == 3) {
            $headers[] = 'Proveedor - Tipo Persona';
            $headers[] = 'Proveedor - Tipo Servicio 1';
            $headers[] = 'Proveedor - Tipo Servicio 2';
            $headers[] = 'Proveedor - Tipo Contrato 1';
            $headers[] = 'Proveedor - Tipo Contrato 2';
            $headers[] = 'Proveedor - Ampliación Servicios';
            $headers[] = 'Proveedor - Fecha Inicio Vigencia';
            $headers[] = 'Proveedor - Fecha Final Vigencia';
            $headers[] = 'Proveedor - Porcentaje Descuento';
        }

        return $headers;
    }

    /**
     * Convertir registro anidado a fila plana
     */
    private function flattenRow($usuario, $ctrl) {
        $row = [
            $usuario['id'],
            $usuario['tipo'],
            ($usuario['acceso'] == 1) ? 'Si' : 'No',
            $usuario['nombre'],
            $usuario['identificacion'],
            $usuario['tipoID_desc'] ?? '',
            $usuario['direccion'],
            $usuario['telefono1'],
            $usuario['telefono2'] ?? '',
            $usuario['celular'],
            $usuario['celular2'] ?? '',
            $usuario['email'],
            $usuario['url'] ?? '',
            $usuario['observaciones'] ?? '',
        ];

        // Agregar datos específicos
        if ($ctrl == "" || $ctrl == 0 || $ctrl == 4) {
            $row[] = $usuario['nomcliente'] ?? '';
        }

        if ($ctrl == "" || $ctrl == 0 || $ctrl == 1 || $ctrl == 4) {
            $empresa = $usuario['empresa'] ?? [];
            $row[] = $empresa['tipo'] ?? '';
            $row[] = $empresa['nombre'] ?? '';
            $row[] = $empresa['nit'] ?? '';
            $row[] = $empresa['representante'] ?? '';
            $row[] = $empresa['contacto'] ?? '';
            $row[] = $empresa['direccion'] ?? '';
            $row[] = $empresa['url'] ?? '';
            $row[] = $empresa['telefono1'] ?? '';
            $row[] = $empresa['telefono2'] ?? '';
            $row[] = $empresa['celular1'] ?? '';
            $row[] = $empresa['celular2'] ?? '';
            $row[] = $empresa['email1'] ?? '';
            $row[] = $empresa['email2'] ?? '';
            $row[] = $empresa['cargo'] ?? '';
        }

        if ($ctrl == "" || $ctrl == 0 || $ctrl == 2) {
            $cliente = $usuario['cliente'] ?? [];
            $row[] = $cliente['tipoPersona'] ?? '';
            $row[] = $cliente['tipo1'] ?? '';
            $row[] = $cliente['servicio1'] ?? '';
            $row[] = $cliente['observaciones'] ?? '';
            $row[] = $cliente['valor1'] ?? '';
            $row[] = $cliente['diaPago'] ?? '';
            $row[] = $cliente['fechaAprob'] ?? '';
            $row[] = $cliente['fechaAprobCont'] ?? '';
            $row[] = $cliente['fechaInicial'] ?? '';
            $row[] = $cliente['fechaFinal'] ?? '';
        }

        if ($ctrl == "" || $ctrl == 0 || $ctrl == 3) {
            $servicios = $usuario['servicios'] ?? [];
            $row[] = $servicios['tipoPersona'] ?? '';
            $row[] = $servicios['tipo1'] ?? '';
            $row[] = $servicios['tipo2'] ?? '';
            $row[] = $servicios['contrato1'] ?? '';
            $row[] = $servicios['contrato2'] ?? '';
            $row[] = $servicios['observaciones'] ?? '';
            $row[] = $servicios['fechaInicio'] ?? '';
            $row[] = $servicios['fechaFin'] ?? '';
            $row[] = $servicios['porcentaje'] ?? '';
        }

        return $row;
    }
}
?>
