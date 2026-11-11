<?php

if (!function_exists('sifen_get_config')) {
    function sifen_get_config($conection)
    {
        $row = null;
        $query = mysqli_query($conection, "SELECT * FROM config_facturacion_electronica ORDER BY id ASC LIMIT 1");
        if ($query && mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
        }

        if (!$row) {
            $row = [
                'id' => null,
                'api_url' => '',
                'api_token' => '',
                'ambiente' => 'test',
                'establecimiento' => '001',
                'punto' => '001',
                'moneda' => 'PYG',
                'iva_por_defecto' => 10,
                'activo' => 0,
                'timbrado' => '',
            ];
        }

        $envUrl = getenv('SIFEN_API_URL');
        $envToken = getenv('SIFEN_API_TOKEN');
        if (($row['api_url'] === null || $row['api_url'] === '') && $envUrl) {
            $row['api_url'] = $envUrl;
        }
        if (($row['api_token'] === null || $row['api_token'] === '') && $envToken) {
            $row['api_token'] = $envToken;
        }

        $row['api_url'] = rtrim((string)$row['api_url'], '/');
        return $row;
    }
}

if (!function_exists('sifen_esta_activo')) {
    function sifen_esta_activo($conection)
    {
        $config = sifen_get_config($conection);
        return (int)($config['activo'] ?? 0) === 1;
    }
}

if (!function_exists('sifen_entero_o_nulo')) {
    function sifen_entero_o_nulo($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_int($valor) || is_float($valor) || (is_string($valor) && preg_match('/^-?\d+$/', $valor))) {
            return (int)$valor;
        }
        return null;
    }
}

if (!function_exists('sifen_codigo_error')) {
    function sifen_codigo_error($respuesta)
    {
        $json = $respuesta['json'] ?? null;
        if (is_array($json) && is_array($json['error'] ?? null) && !empty($json['error']['code'])) {
            return (string)$json['error']['code'];
        }
        return '';
    }
}

if (!function_exists('sifen_mensaje_cupo_codigo')) {
    function sifen_mensaje_cupo_codigo($codigo)
    {
        if ($codigo === 'BILLING_SUSPENDED') {
            return 'La empresa está suspendida. No se puede facturar hasta regularizar el pago.';
        }
        if ($codigo === 'DOCUMENT_LIMIT') {
            return 'Se agotaron los comprobantes del mes, incluida la salvaguarda.';
        }
        return '';
    }
}

if (!function_exists('sifen_vista_cupo')) {
    function sifen_vista_cupo($data)
    {
        $status = (string)($data['status'] ?? '');
        $can = !empty($data['can_issue']);
        $usingGrace = !empty($data['using_grace']);
        $included = sifen_entero_o_nulo($data['included'] ?? null);
        $remainingIncluded = sifen_entero_o_nulo($data['remaining_included'] ?? null);
        $remaining = array_key_exists('remaining', $data) ? sifen_entero_o_nulo($data['remaining']) : null;

        $bloquea = false;
        $aviso = '';
        if (!$can && $status === 'suspended') {
            $bloquea = true;
            $aviso = sifen_mensaje_cupo_codigo('BILLING_SUSPENDED');
        } elseif (!$can && $remaining === 0) {
            $bloquea = true;
            $aviso = sifen_mensaje_cupo_codigo('DOCUMENT_LIMIT');
        } elseif ($usingGrace && $remaining !== null) {
            $aviso = 'Ya se usaron los ' . (int)$included . ' incluidos. Quedan ' . $remaining . ' de la salvaguarda.';
        } elseif ($remainingIncluded !== null && $remainingIncluded > 0 && $remainingIncluded <= 20) {
            $aviso = 'Quedan ' . $remainingIncluded . ' comprobantes incluidos este mes.';
        }

        return [
            'ok' => true,
            'activo' => true,
            'can_issue' => $can,
            'status' => $status,
            'using_grace' => $usingGrace,
            'included' => $included,
            'grace' => sifen_entero_o_nulo($data['grace'] ?? null),
            'used' => sifen_entero_o_nulo($data['used'] ?? null),
            'remaining_included' => $remainingIncluded,
            'remaining' => $remaining,
            'bloquea' => $bloquea,
            'aviso' => $aviso,
            'linea' => $remaining === null ? '' : ('Comprobantes electrónicos disponibles: ' . $remaining),
        ];
    }
}

if (!function_exists('sifen_cupo')) {
    function sifen_cupo($conection)
    {
        if (!sifen_esta_activo($conection)) {
            return ['ok' => true, 'activo' => false, 'bloquea' => false, 'aviso' => '', 'linea' => ''];
        }
        $config = sifen_get_config($conection);
        if ($config['api_url'] === '' || $config['api_token'] === '') {
            return ['ok' => false, 'activo' => true, 'bloquea' => false, 'aviso' => 'No se pudo consultar el cupo.', 'linea' => ''];
        }
        $respuesta = sifen_request('GET', $config['api_url'] . '/api/v1/billing/quota', $config['api_token'], null, 8);
        $data = is_array($respuesta['json']['data'] ?? null) ? $respuesta['json']['data'] : null;
        if ((int)$respuesta['http'] !== 200 || $data === null) {
            return ['ok' => false, 'activo' => true, 'bloquea' => false, 'aviso' => 'No se pudo consultar el cupo.', 'linea' => ''];
        }
        return sifen_vista_cupo($data);
    }
}

if (!function_exists('sifen_bloqueo_emision')) {
    function sifen_bloqueo_emision($conection)
    {
        $cupo = sifen_cupo($conection);
        if (!empty($cupo['bloquea'])) {
            return $cupo['aviso'] !== '' ? $cupo['aviso'] : 'No se puede facturar.';
        }
        return '';
    }
}

if (!function_exists('sifen_enviar')) {
    function sifen_enviar($conection, $path, $body)
    {
        $config = sifen_get_config($conection);
        if ($config['api_url'] === '' || $config['api_token'] === '') {
            return ['ok' => false, 'msg' => 'Falta la URL o el token de la facturación electrónica.'];
        }
        if ($path === '/api/v1/documents') {
            $bloqueo = sifen_bloqueo_emision($conection);
            if ($bloqueo !== '') {
                return ['ok' => false, 'msg' => $bloqueo, 'http' => 402, 'billing' => true];
            }
        }
        $respuesta = sifen_request('POST', $config['api_url'] . $path, $config['api_token'], $body, 20);
        if ((int)$respuesta['http'] === 402) {
            $mensaje = sifen_mensaje_cupo_codigo(sifen_codigo_error($respuesta));
            if ($mensaje === '') {
                $mensaje = sifen_mensaje_error($respuesta);
            }
            sifen_cupo($conection);
            return ['ok' => false, 'msg' => $mensaje, 'http' => 402, 'billing' => true];
        }
        if (in_array($respuesta['http'], [200, 202], true)) {
            $doc = sifen_documento_desde_respuesta($respuesta['json']);
            return [
                'ok' => true,
                'msg' => 'La API aceptó el pedido.',
                'http' => $respuesta['http'],
                'doc' => $doc,
                'raw' => $respuesta['raw'],
            ];
        }
        return ['ok' => false, 'msg' => sifen_mensaje_error($respuesta), 'http' => $respuesta['http']];
    }
}

if (!function_exists('sifen_request')) {
    function sifen_request($method, $url, $token, $body = null, $timeout = 12)
    {
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ];
        $payload = null;
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $payload = json_encode($body, JSON_UNESCAPED_UNICODE);
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => $timeout,
            ]);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }
            $raw = curl_exec($ch);
            $error = curl_error($ch);
            $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $headerLines = implode("\r\n", $headers);
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => $headerLines,
                    'content' => $payload ?? '',
                    'timeout' => $timeout,
                    'ignore_errors' => true,
                ],
            ]);
            $raw = @file_get_contents($url, false, $context);
            $error = $raw === false ? 'No se pudo conectar con la API SIFEN' : '';
            $http = 0;
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
                $http = (int)$match[1];
            }
        }

        $json = null;
        if (is_string($raw) && $raw !== '') {
            $json = json_decode($raw, true);
        }

        return [
            'http' => $http,
            'raw' => is_string($raw) ? $raw : '',
            'json' => $json,
            'error' => $error,
        ];
    }
}

if (!function_exists('sifen_es_ruc')) {
    function sifen_es_ruc($nit)
    {
        $nit = trim((string)$nit);
        if ($nit === '' || $nit === '0' || strcasecmp($nit, 's/n') === 0 || strcasecmp($nit, 'sin nombre') === 0) {
            return false;
        }
        return (bool)preg_match('/^\d{3,8}-\d$/', $nit);
    }
}

if (!function_exists('sifen_tipo_entrega')) {
    function sifen_tipo_entrega($tipoPago)
    {
        switch ((int)$tipoPago) {
            case 2:
                return 5;
            case 4:
                return 21;
            case 5:
                return 3;
            default:
                return 1;
        }
    }
}

if (!function_exists('sifen_build_payload')) {
    function sifen_build_payload($conection, $noventa, $plazoDias = 30)
    {
        $noventa = (int)$noventa;
        $config = sifen_get_config($conection);

        $queryVenta = mysqli_query($conection, "SELECT f.noventa, f.fecha, f.descuento, f.totalventa, f.tipo_pago_detalle,
                                                        cl.nit, cl.nombre, cl.telefono, cl.direccion, cl.email
                                                 FROM venta f
                                                 INNER JOIN cliente cl ON f.codcliente = cl.idcliente
                                                 WHERE f.noventa = $noventa
                                                 LIMIT 1");
        if (!$queryVenta || mysqli_num_rows($queryVenta) === 0) {
            return ['ok' => false, 'error' => 'Venta no encontrada para emitir.'];
        }
        $venta = mysqli_fetch_assoc($queryVenta);

        $queryItems = mysqli_query($conection, "SELECT p.codigo, p.descripcion, dt.cantidad, dt.precio_venta
                                                 FROM detalleventa dt
                                                 INNER JOIN producto p ON dt.codproducto = p.codproducto
                                                 WHERE dt.noventa = $noventa");
        if (!$queryItems || mysqli_num_rows($queryItems) === 0) {
            return ['ok' => false, 'error' => 'La venta no tiene productos para facturar.'];
        }

        $iva = (int)($config['iva_por_defecto'] ?? 10);
        if (!in_array($iva, [0, 5, 10], true)) {
            $iva = 10;
        }
        $moneda = $config['moneda'] ?: 'PYG';

        $lineas = [];
        $subtotal = 0.0;
        while ($item = mysqli_fetch_assoc($queryItems)) {
            $cantidad = (float)$item['cantidad'];
            $precio = (float)$item['precio_venta'];
            $importe = $cantidad * $precio;
            $subtotal += $importe;
            $linea = [
                'descripcion' => $item['descripcion'],
                'cantidad' => $cantidad,
                'precioUnitario' => $precio,
                'unidadMedida' => 77,
                'ivaTipo' => 1,
                'iva' => $iva,
                'ivaProporcion' => 100,
                'descuento' => 0,
            ];
            if (!empty($item['codigo'])) {
                $linea['codigo'] = $item['codigo'];
            }
            $lineas[] = $linea;
        }

        $descuento = (float)$venta['descuento'];
        if ($descuento > 0 && $subtotal > 0) {
            $restante = $descuento;
            $ultima = count($lineas) - 1;
            foreach ($lineas as $i => $linea) {
                $importe = $linea['cantidad'] * $linea['precioUnitario'];
                if ($i === $ultima) {
                    $parte = $restante;
                } else {
                    $parte = round($importe / $subtotal * $descuento, 2);
                    $restante -= $parte;
                }
                if ($parte < 0) {
                    $parte = 0;
                }
                $lineas[$i]['descuento'] = $parte;
            }
        }

        $nit = trim((string)$venta['nit']);
        $nombre = trim((string)$venta['nombre']);
        if ($nombre === '') {
            $nombre = 'Sin nombre';
        }
        $cliente = [
            'contribuyente' => false,
            'razonSocial' => $nombre,
        ];
        if (sifen_parece_ruc($nit)) {
            $vistaRuc = sifen_evaluar_ruc($conection, $nit, $nombre, true, false);
            if (!empty($vistaRuc['bloquea_emision'])) {
                $errorRuc = trim((string)($vistaRuc['msg'] ?? ''));
                return ['ok' => false, 'error' => $errorRuc !== '' ? $errorRuc : 'No se pudo consultar el RUC.'];
            }
            if (!empty($vistaRuc['dv_aceptado']) && !empty($vistaRuc['cliente']['ruc'])) {
                $cliente['contribuyente'] = true;
                $cliente['ruc'] = $vistaRuc['cliente']['ruc'];
                $cliente['razonSocial'] = $vistaRuc['cliente']['razonSocial'];
            }
        }
        if (!empty($venta['direccion'])) {
            $cliente['direccion'] = $venta['direccion'];
        }
        if (!empty($venta['telefono'])) {
            $cliente['telefono'] = (string)$venta['telefono'];
        }
        $email = trim((string)($venta['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $cliente['email'] = $email;
        }

        $total = (float)$venta['totalventa'];
        if ($total <= 0) {
            $total = max(0, $subtotal - $descuento);
        }
        $tipoPago = (int)$venta['tipo_pago_detalle'];
        if ($tipoPago === 3) {
            $dias = (int)$plazoDias;
            if ($dias < 1) {
                $dias = 30;
            }
            $condicion = [
                'tipo' => 2,
                'credito' => [
                    'tipo' => 1,
                    'plazo' => $dias . ' dias',
                ],
            ];
        } else {
            $condicion = [
                'tipo' => 1,
                'entregas' => [[
                    'tipo' => sifen_tipo_entrega($tipoPago),
                    'monto' => (string)round($total),
                    'moneda' => $moneda,
                    'cambio' => 0,
                ]],
            ];
        }

        $payload = [
            'tipoDocumento' => 1,
            'establecimiento' => $config['establecimiento'] ?: '001',
            'punto' => $config['punto'] ?: '001',
            'moneda' => $moneda,
            'idempotency_key' => 'venta-' . $noventa,
            'cliente' => $cliente,
            'factura' => ['presencia' => 1],
            'condicion' => $condicion,
            'items' => $lineas,
        ];
        if (!empty($venta['fecha'])) {
            $emitida = strtotime($venta['fecha']);
            if ($emitida) {
                $payload['fecha_emision'] = date('Y-m-d\TH:i:s', $emitida);
            }
        }

        return ['ok' => true, 'payload' => $payload, 'config' => $config];
    }
}

if (!function_exists('sifen_guardar')) {
    function sifen_guardar($conection, $datos)
    {
        $stmt = mysqli_prepare(
            $conection,
            "INSERT INTO factura_electronica
                (noventa, document_id, status, cdc, qr_url, kude_path, numero, idempotency_key, error_message, request_payload, response_raw)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                document_id = VALUES(document_id),
                status = VALUES(status),
                cdc = VALUES(cdc),
                qr_url = VALUES(qr_url),
                kude_path = VALUES(kude_path),
                numero = VALUES(numero),
                error_message = VALUES(error_message),
                request_payload = VALUES(request_payload),
                response_raw = VALUES(response_raw)"
        );
        if (!$stmt) {
            return false;
        }

        $noventa = (int)$datos['noventa'];
        $documentId = $datos['document_id'] ?? null;
        $status = $datos['status'] ?? 'error';
        $cdc = $datos['cdc'] ?? null;
        $qr = $datos['qr_url'] ?? null;
        $kude = $datos['kude_path'] ?? null;
        if (is_string($kude) && preg_match('#^https?://#i', trim($kude))) {
            $kude = null;
        }
        $numero = isset($datos['numero']) && $datos['numero'] !== '' && $datos['numero'] !== null
            ? (string)(int)$datos['numero']
            : null;
        $idempotency = $datos['idempotency_key'] ?? ('venta-' . $noventa);
        $error = $datos['error_message'] ?? null;
        $request = $datos['request_payload'] ?? null;
        $response = sifen_raw_sin_enlaces_firmados($datos['response_raw'] ?? null);

        mysqli_stmt_bind_param(
            $stmt,
            'issssssssss',
            $noventa,
            $documentId,
            $status,
            $cdc,
            $qr,
            $kude,
            $numero,
            $idempotency,
            $error,
            $request,
            $response
        );
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}

if (!function_exists('sifen_documento_desde_respuesta')) {
    function sifen_documento_desde_respuesta($json)
    {
        if (!is_array($json)) {
            return [];
        }
        if (isset($json['data']) && is_array($json['data'])) {
            return $json['data'];
        }
        return $json;
    }
}

if (!function_exists('sifen_mensaje_error')) {
    function sifen_mensaje_error($respuesta)
    {
        if (!empty($respuesta['error'])) {
            return $respuesta['error'];
        }
        $json = $respuesta['json'];
        if (is_array($json)) {
            if (!empty($json['error']['message'])) {
                return $json['error']['message'];
            }
            if (!empty($json['message'])) {
                return $json['message'];
            }
        }
        if (!empty($respuesta['http'])) {
            return 'La API SIFEN respondió HTTP ' . $respuesta['http'];
        }
        return 'No se pudo emitir la factura electrónica.';
    }
}

if (!function_exists('sifen_quitar_enlaces_firmados')) {
    function sifen_quitar_enlaces_firmados(&$nodo)
    {
        if (!is_array($nodo)) {
            return;
        }
        unset($nodo['kude_url'], $nodo['xml_url']);
        foreach ($nodo as &$hijo) {
            if (is_array($hijo)) {
                sifen_quitar_enlaces_firmados($hijo);
            }
        }
        unset($hijo);
    }
}

if (!function_exists('sifen_raw_sin_enlaces_firmados')) {
    function sifen_raw_sin_enlaces_firmados($raw)
    {
        if (!is_string($raw) || $raw === '') {
            return $raw;
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            return $raw;
        }
        sifen_quitar_enlaces_firmados($json);
        $nuevo = json_encode($json, JSON_UNESCAPED_UNICODE);
        return is_string($nuevo) ? $nuevo : $raw;
    }
}

if (!function_exists('sifen_kude_url')) {
    function sifen_kude_url($apiUrl, $fe)
    {
        unset($apiUrl);
        if (!is_array($fe)) {
            return '';
        }
        $url = trim((string)($fe['kude_url'] ?? ''));
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        return '';
    }
}

if (!function_exists('sifen_esperar_kude')) {
    function sifen_esperar_kude($apiUrl, $token, $documentId, $doc, $raw)
    {
        if (!is_array($doc)) {
            $doc = [];
        }
        $documentId = trim((string)$documentId);
        $intento = 0;
        while (sifen_kude_url($apiUrl, $doc) === '' && $intento < 8 && $documentId !== '') {
            if ($intento > 0) {
                usleep(300000);
            }
            $intento++;
            $consulta = sifen_request(
                'GET',
                rtrim((string)$apiUrl, '/') . '/api/v1/documents/' . rawurlencode($documentId),
                $token,
                null,
                8
            );
            if (is_string($consulta['raw'] ?? null) && $consulta['raw'] !== '') {
                $raw = $consulta['raw'];
            }
            if (!empty($consulta['json'])) {
                $nuevo = sifen_documento_desde_respuesta($consulta['json']);
                if (is_array($nuevo) && $nuevo !== []) {
                    $doc = $nuevo;
                }
            }
        }
        return [$doc, is_string($raw) ? $raw : ''];
    }
}

if (!function_exists('sifen_resultado_emision')) {
    function sifen_resultado_emision($conection, $noventa, $payload, $doc, $documentId, $raw, $previo = null)
    {
        if (!is_array($doc)) {
            $doc = [];
        }
        if (!is_array($previo)) {
            $previo = [];
        }
        $status = !empty($doc['status'])
            ? (string)$doc['status']
            : (!empty($previo['status']) ? (string)$previo['status'] : 'processing');
        $error = !empty($doc['error_message']) ? (string)$doc['error_message'] : null;
        $kudeUrl = sifen_kude_url('', $doc);
        $cdc = $doc['cdc'] ?? ($previo['cdc'] ?? null);
        $id = $doc['id'] ?? $documentId;
        sifen_guardar($conection, [
            'noventa' => $noventa,
            'document_id' => $id,
            'status' => $status,
            'cdc' => $cdc,
            'qr_url' => $doc['qr_url'] ?? ($previo['qr_url'] ?? null),
            'kude_path' => null,
            'numero' => $doc['number'] ?? ($previo['numero'] ?? null),
            'idempotency_key' => $payload['idempotency_key'],
            'error_message' => $error,
            'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'response_raw' => is_string($raw) ? $raw : '',
        ]);
        return [
            'status' => $status,
            'cdc' => $cdc,
            'kude_url' => $kudeUrl,
            'error' => $error,
            'document_id' => $id,
        ];
    }
}

if (!function_exists('sifen_emitir')) {
    function sifen_emitir($conection, $noventa, $plazoDias = 30)
    {
        $noventa = (int)$noventa;
        $armado = sifen_build_payload($conection, $noventa, $plazoDias);
        if (empty($armado['ok'])) {
            $error = $armado['error'] ?? 'No se pudo armar la factura.';
            sifen_guardar($conection, [
                'noventa' => $noventa,
                'status' => 'error',
                'idempotency_key' => 'venta-' . $noventa,
                'error_message' => $error,
            ]);
            return ['status' => 'error', 'cdc' => null, 'kude_url' => '', 'error' => $error];
        }

        $config = $armado['config'];
        $payload = $armado['payload'];
        $apiUrl = $config['api_url'];
        $token = $config['api_token'];
        if ($apiUrl === '' || $token === '') {
            $error = 'Falta la URL o el token de la facturación electrónica.';
            sifen_guardar($conection, [
                'noventa' => $noventa,
                'status' => 'error',
                'idempotency_key' => $payload['idempotency_key'],
                'error_message' => $error,
                'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
            return ['status' => 'error', 'cdc' => null, 'kude_url' => '', 'error' => $error];
        }

        $existente = mysqli_query(
            $conection,
            "SELECT * FROM factura_electronica WHERE idempotency_key = '" . mysqli_real_escape_string($conection, $payload['idempotency_key']) . "' LIMIT 1"
        );
        if ($existente && mysqli_num_rows($existente) > 0) {
            $previo = mysqli_fetch_assoc($existente);
            $sigueElMismo = !in_array($previo['status'], ['rejected', 'error'], true)
                && !empty($previo['document_id'])
                && in_array($previo['status'], ['approved', 'queued', 'processing', 'contingency'], true);
            if ($sigueElMismo) {
                list($doc, $raw) = sifen_esperar_kude($apiUrl, $token, $previo['document_id'], [], '');
                return sifen_resultado_emision($conection, $noventa, $payload, $doc, $previo['document_id'], $raw, $previo);
            }
        }

        $bloqueo = sifen_bloqueo_emision($conection);
        if ($bloqueo !== '') {
            sifen_guardar($conection, [
                'noventa' => $noventa,
                'status' => 'error',
                'idempotency_key' => $payload['idempotency_key'],
                'error_message' => $bloqueo,
                'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
            return ['status' => 'error', 'cdc' => null, 'kude_url' => '', 'error' => $bloqueo, 'billing' => true];
        }

        unset($payload['numero']);
        $emision = sifen_request('POST', $apiUrl . '/api/v1/documents', $token, $payload, 12);
        $doc = sifen_documento_desde_respuesta($emision['json']);
        $documentId = $doc['id'] ?? null;

        if ((int)$emision['http'] === 402) {
            $error = sifen_mensaje_cupo_codigo(sifen_codigo_error($emision));
            if ($error === '') {
                $error = sifen_mensaje_error($emision);
            }
            sifen_guardar($conection, [
                'noventa' => $noventa,
                'document_id' => $documentId,
                'status' => 'error',
                'idempotency_key' => $payload['idempotency_key'],
                'error_message' => $error,
                'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'response_raw' => $emision['raw'],
            ]);
            sifen_cupo($conection);
            return ['status' => 'error', 'cdc' => null, 'kude_url' => '', 'error' => $error, 'billing' => true];
        }

        if ($emision['http'] !== 202 || empty($documentId)) {
            $error = sifen_mensaje_error($emision);
            sifen_guardar($conection, [
                'noventa' => $noventa,
                'document_id' => $documentId,
                'status' => 'error',
                'idempotency_key' => $payload['idempotency_key'],
                'error_message' => $error,
                'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'response_raw' => $emision['raw'],
            ]);
            return ['status' => 'error', 'cdc' => null, 'kude_url' => '', 'error' => $error];
        }

        list($doc, $raw) = sifen_esperar_kude($apiUrl, $token, $documentId, $doc, $emision['raw'] ?? '');
        return sifen_resultado_emision($conection, $noventa, $payload, $doc, $documentId, $raw);
    }
}

if (!function_exists('sifen_bajar_kude_html')) {
    function sifen_bajar_kude_html($apiUrl, $token, $documentId)
    {
        $apiUrl = rtrim((string)$apiUrl, '/');
        $documentId = trim((string)$documentId);
        if ($apiUrl === '' || $token === '' || $documentId === '' || !function_exists('curl_init')) {
            return '';
        }

        $ch = curl_init($apiUrl . '/api/v1/documents/' . rawurlencode($documentId) . '/kude');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html',
                'Authorization: Bearer ' . $token,
            ],
        ]);
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($raw) || $http !== 200 || $raw === '' || strncmp($raw, '%PDF', 4) === 0 || strlen($raw) > 1500000) {
            return '';
        }
        return $raw;
    }
}

if (!function_exists('sifen_health')) {
    function sifen_health($apiUrl, $token)
    {
        $apiUrl = rtrim((string)$apiUrl, '/');
        if ($apiUrl === '') {
            return ['ok' => false, 'msg' => 'Falta la URL de la API.'];
        }
        $respuesta = sifen_request('GET', $apiUrl . '/api/health', (string)$token, null, 8);
        if ($respuesta['http'] === 200) {
            $estado = $respuesta['json']['data']['status'] ?? 'ok';
            return ['ok' => true, 'msg' => 'Conexión correcta (' . $estado . ').'];
        }
        return ['ok' => false, 'msg' => sifen_mensaje_error($respuesta)];
    }
}

if (!function_exists('sifen_refrescar_pendientes')) {
    function sifen_refrescar_pendientes($conection)
    {
        $config = sifen_get_config($conection);
        if ($config['api_url'] === '' || $config['api_token'] === '') {
            return 0;
        }

        $query = mysqli_query(
            $conection,
            "SELECT * FROM factura_electronica
             WHERE status IN ('queued', 'processing', 'contingency')
               AND document_id IS NOT NULL AND document_id <> ''"
        );
        if (!$query) {
            return 0;
        }

        $actualizadas = 0;
        while ($row = mysqli_fetch_assoc($query)) {
            $consulta = sifen_request(
                'GET',
                $config['api_url'] . '/api/v1/documents/' . rawurlencode($row['document_id']),
                $config['api_token'],
                null,
                8
            );
            if (empty($consulta['json'])) {
                continue;
            }
            $doc = sifen_documento_desde_respuesta($consulta['json']);
            $status = !empty($doc['status']) ? $doc['status'] : $row['status'];
            sifen_guardar($conection, [
                'noventa' => (int)$row['noventa'],
                'document_id' => $doc['id'] ?? $row['document_id'],
                'status' => $status,
                'cdc' => $doc['cdc'] ?? $row['cdc'],
                'qr_url' => $doc['qr_url'] ?? $row['qr_url'],
                'kude_path' => null,
                'numero' => $doc['number'] ?? $row['numero'],
                'idempotency_key' => $row['idempotency_key'],
                'error_message' => $doc['error_message'] ?? null,
                'request_payload' => $row['request_payload'],
                'response_raw' => $consulta['raw'] ?? '',
            ]);
            $actualizadas++;
        }

        return $actualizadas;
    }
}

if (!function_exists('sifen_limpiar_ruc')) {
    function sifen_limpiar_ruc($ruc)
    {
        $ruc = trim((string)$ruc);
        $ruc = preg_replace('/\s+/', '', $ruc);
        return is_string($ruc) ? $ruc : '';
    }
}

if (!function_exists('sifen_parece_ruc')) {
    function sifen_parece_ruc($ruc)
    {
        $ruc = sifen_limpiar_ruc($ruc);
        if ($ruc === '' || strlen($ruc) > 20) {
            return false;
        }
        if (!preg_match('/^[\d.\-]+$/', $ruc)) {
            return false;
        }
        return (bool)preg_match('/\d{3,}/', $ruc);
    }
}

if (!function_exists('sifen_mensaje_validacion_ruc')) {
    function sifen_mensaje_validacion_ruc($respuesta)
    {
        $json = is_array($respuesta['json'] ?? null) ? $respuesta['json'] : [];
        $partes = [];
        if (!empty($json['message']) && is_string($json['message'])) {
            $partes[] = $json['message'];
        }
        if (!empty($json['error']) && is_array($json['error']) && !empty($json['error']['message']) && is_string($json['error']['message'])) {
            $partes[] = $json['error']['message'];
        }
        if (!empty($json['errors']) && is_array($json['errors'])) {
            foreach ($json['errors'] as $msgs) {
                if (is_string($msgs)) {
                    $partes[] = $msgs;
                } elseif (is_array($msgs)) {
                    foreach ($msgs as $m) {
                        if (is_string($m) && $m !== '') {
                            $partes[] = $m;
                        }
                    }
                }
            }
        }
        $partes = array_values(array_unique($partes));
        if ($partes) {
            return implode(' ', $partes);
        }
        return 'El formato del RUC no es válido.';
    }
}

if (!function_exists('sifen_interpretar_respuesta_ruc')) {
    function sifen_interpretar_respuesta_ruc($respuesta)
    {
        $http = (int)($respuesta['http'] ?? 0);
        if ($http === 401) {
            return ['ok' => false, 'http' => 401, 'msg' => 'API key ausente o inválida.', 'data' => null];
        }
        if ($http === 422) {
            return ['ok' => false, 'http' => 422, 'msg' => sifen_mensaje_validacion_ruc($respuesta), 'data' => null];
        }
        if ($http === 429) {
            return ['ok' => false, 'http' => 429, 'msg' => 'Máximo 30 consultas por minuto.', 'data' => null];
        }
        if ($http !== 200 || !is_array($respuesta['json'] ?? null)) {
            if (!empty($respuesta['error'])) {
                $msg = (string)$respuesta['error'];
            } elseif ($http === 0) {
                $msg = 'No se pudo conectar con la pasarela SIFEN.';
            } else {
                $msg = sifen_mensaje_error($respuesta);
                if ($msg === 'No se pudo emitir la factura electrónica.') {
                    $msg = 'No se pudo consultar el RUC.';
                }
            }
            return ['ok' => false, 'http' => $http, 'msg' => $msg, 'data' => null];
        }

        $json = $respuesta['json'];
        if (!empty($json['error'])) {
            $msg = 'No se pudo consultar el RUC.';
            if (is_array($json['error']) && !empty($json['error']['message']) && is_string($json['error']['message'])) {
                $msg = $json['error']['message'];
            } elseif (is_string($json['error'])) {
                $msg = $json['error'];
            }
            return ['ok' => false, 'http' => $http, 'msg' => $msg, 'data' => null];
        }
        if (!is_array($json['data'] ?? null) || trim((string)($json['data']['ruc'] ?? '')) === '') {
            return ['ok' => false, 'http' => $http, 'msg' => 'La pasarela no devolvió el RUC.', 'data' => null];
        }

        return [
            'ok' => true,
            'http' => 200,
            'msg' => '',
            'data' => $json['data'],
            'correlation_id' => is_array($json['meta'] ?? null) ? ($json['meta']['correlation_id'] ?? null) : null,
        ];
    }
}

if (!function_exists('sifen_cache_ruc_leer')) {
    function sifen_cache_ruc_leer($ruc, $conConsulta)
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['sifen_ruc_ultima']) || !is_array($_SESSION['sifen_ruc_ultima'])) {
            return null;
        }
        $cache = $_SESSION['sifen_ruc_ultima'];
        if ((int)($cache['expira'] ?? 0) < time()) {
            return null;
        }
        if ((bool)($cache['consulta'] ?? true) !== (bool)$conConsulta) {
            return null;
        }
        $claves = is_array($cache['claves'] ?? null) ? $cache['claves'] : [];
        if (!in_array($ruc, $claves, true)) {
            return null;
        }
        return is_array($cache['respuesta'] ?? null) ? $cache['respuesta'] : null;
    }
}

if (!function_exists('sifen_cache_ruc_guardar')) {
    function sifen_cache_ruc_guardar($ruc, $conConsulta, $respuesta)
    {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($respuesta['ok']) || !is_array($respuesta['data'] ?? null)) {
            return;
        }
        $claves = [$ruc];
        $canonico = trim((string)($respuesta['data']['ruc'] ?? ''));
        if ($canonico !== '' && !in_array($canonico, $claves, true)) {
            $claves[] = $canonico;
        }
        $_SESSION['sifen_ruc_ultima'] = [
            'claves' => $claves,
            'consulta' => (bool)$conConsulta,
            'expira' => time() + 120,
            'respuesta' => $respuesta,
        ];
    }
}

if (!function_exists('sifen_url_ruc')) {
    function sifen_url_ruc($apiUrl, $ruc, $conConsulta = false)
    {
        $url = rtrim((string)$apiUrl, '/') . '/api/v1/ruc/' . rawurlencode(sifen_limpiar_ruc($ruc));
        if (!$conConsulta) {
            $url .= '?consulta=0';
        }
        return $url;
    }
}

if (!function_exists('sifen_consultar_ruc')) {
    function sifen_consultar_ruc($conection, $ruc, $conConsulta = false, $usarCache = false)
    {
        $ruc = sifen_limpiar_ruc($ruc);
        if ($usarCache) {
            $cache = sifen_cache_ruc_leer($ruc, $conConsulta);
            if ($cache !== null) {
                return $cache;
            }
        }
        if (strlen($ruc) > 20) {
            return ['ok' => false, 'http' => 422, 'msg' => 'El RUC admite como máximo 20 caracteres.', 'data' => null];
        }

        $config = sifen_get_config($conection);
        if ($config['api_url'] === '' || $config['api_token'] === '') {
            return ['ok' => false, 'http' => 401, 'msg' => 'API key ausente o inválida.', 'data' => null];
        }

        $url = sifen_url_ruc($config['api_url'], $ruc, $conConsulta);
        $respuesta = sifen_request('GET', $url, $config['api_token'], null, 12);
        $interpretada = sifen_interpretar_respuesta_ruc($respuesta);
        sifen_cache_ruc_guardar($ruc, $conConsulta, $interpretada);
        return $interpretada;
    }
}

if (!function_exists('sifen_dv_aceptado')) {
    function sifen_dv_aceptado($data)
    {
        if (!is_array($data) || ($data['dv_valido'] ?? null) === false) {
            return false;
        }
        return trim((string)($data['ruc'] ?? '')) !== '';
    }
}

if (!function_exists('sifen_es_razon_ejemplo')) {
    function sifen_es_razon_ejemplo($razon)
    {
        $texto = strtoupper(trim((string)$razon));
        $texto = str_replace('.', '', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        if (!is_string($texto) || $texto === '') {
            return false;
        }
        if (strpos($texto, 'EMPRESA MOCK') === 0) {
            return true;
        }
        return in_array($texto, ['EMPRESA DE EJEMPLO', 'EMPRESA EJEMPLO', 'NOMBRE DE EJEMPLO', 'EJEMPLO'], true);
    }
}

if (!function_exists('sifen_razon_social_util')) {
    function sifen_razon_social_util($consulta)
    {
        if (!is_array($consulta)) {
            return null;
        }
        if (array_key_exists('disponible', $consulta) && $consulta['disponible'] === false) {
            return null;
        }
        if (($consulta['encontrado'] ?? null) !== true) {
            return null;
        }
        $razon = $consulta['razon_social'] ?? null;
        if (!is_string($razon)) {
            return null;
        }
        $razon = trim($razon);
        if ($razon === '' || sifen_es_razon_ejemplo($razon)) {
            return null;
        }
        return $razon;
    }
}

if (!function_exists('sifen_vista_consulta_ruc')) {
    function sifen_vista_consulta_ruc($consulta, $nombreCajero = '')
    {
        $vista = [
            'ok' => !empty($consulta['ok']),
            'http' => (int)($consulta['http'] ?? 0),
            'msg' => (string)($consulta['msg'] ?? ''),
            'bloquea_emision' => false,
            'autocompletar' => false,
            'pedir_razon_social' => false,
            'dv_aceptado' => false,
            'advertencia' => false,
            'es_ruc' => false,
            'ruc' => null,
            'razon_social' => null,
            'estado_codigo' => null,
            'facturador_electronico' => null,
            'cliente' => null,
        ];
        if (empty($consulta['ok'])) {
            if ($vista['http'] !== 422) {
                $vista['bloquea_emision'] = true;
            }
            if ($vista['msg'] === '') {
                $vista['msg'] = $vista['http'] === 422
                    ? 'El formato del RUC no es válido.'
                    : 'No se pudo consultar el RUC.';
            }
            return $vista;
        }

        $data = $consulta['data'];
        $ruc = trim((string)($data['ruc'] ?? ''));
        $dv = trim((string)($data['dv'] ?? ''));
        $vista['es_ruc'] = true;
        $vista['ruc'] = $ruc;
        $vista['dv_aceptado'] = sifen_dv_aceptado($data);
        if (!$vista['dv_aceptado']) {
            $vista['bloquea_emision'] = true;
            $correcto = $dv !== '' ? $dv : '—';
            $vista['msg'] = 'El dígito verificador no coincide. El correcto es ' . $correcto
                . ($ruc !== '' ? ' (RUC ' . $ruc . ')' : '')
                . '. Corregilo para poder emitir.';
            return $vista;
        }

        $info = is_array($data['consulta'] ?? null) ? $data['consulta'] : null;
        $razon = sifen_razon_social_util($info);
        $estadoCodigo = '';
        $estadoTexto = '';
        if (is_array($info)) {
            $estadoCodigo = strtoupper(trim((string)($info['estado_codigo'] ?? '')));
            $estadoTexto = trim((string)($info['estado'] ?? ''));
            $vista['estado_codigo'] = $estadoCodigo !== '' ? $estadoCodigo : null;
            if (array_key_exists('facturador_electronico', $info)) {
                $vista['facturador_electronico'] = $info['facturador_electronico'];
            }
        }
        if ($razon !== null) {
            $vista['autocompletar'] = true;
            $vista['razon_social'] = $razon;
            $nota = '';
            if ($vista['facturador_electronico'] === true) {
                $nota = ' Facturador electrónico.';
            } elseif ($vista['facturador_electronico'] === false) {
                $nota = ' No es facturador electrónico.';
            }
            $cierre = substr($razon, -1) === '.' ? '' : '.';
            $vista['msg'] = 'RUC ' . $ruc . '. ' . $razon . $cierre . $nota;
            if ($estadoCodigo !== '' && $estadoCodigo !== 'ACT') {
                $detalle = $estadoTexto !== '' ? $estadoTexto : $estadoCodigo;
                if (strcasecmp($detalle, $estadoCodigo) !== 0) {
                    $detalle .= ' (' . $estadoCodigo . ')';
                }
                $vista['advertencia'] = true;
                $vista['msg'] .= ' El contribuyente no está activo (' . $detalle . ').';
            }
        } else {
            $vista['msg'] = '';
        }

        $nombre = trim((string)$nombreCajero);
        if ($nombre === '') {
            $nombre = 'Sin nombre';
        }
        $vista['cliente'] = [
            'contribuyente' => true,
            'ruc' => $ruc,
            'razonSocial' => $razon !== null ? $razon : $nombre,
        ];
        return $vista;
    }
}

if (!function_exists('sifen_evaluar_ruc')) {
    function sifen_evaluar_ruc($conection, $ruc, $nombreCajero = '', $usarCache = false, $conConsulta = false)
    {
        $ruc = sifen_limpiar_ruc($ruc);
        if (strlen($ruc) > 20) {
            $vista = sifen_vista_consulta_ruc([
                'ok' => false,
                'http' => 422,
                'msg' => 'El RUC admite como máximo 20 caracteres.',
                'data' => null,
            ]);
            $vista['bloquea_emision'] = true;
            return $vista;
        }
        if (!sifen_parece_ruc($ruc)) {
            return [
                'ok' => true,
                'http' => 0,
                'msg' => '',
                'bloquea_emision' => false,
                'autocompletar' => false,
                'pedir_razon_social' => false,
                'dv_aceptado' => false,
                'advertencia' => false,
                'es_ruc' => false,
                'ruc' => null,
                'razon_social' => null,
                'estado_codigo' => null,
                'facturador_electronico' => null,
                'cliente' => null,
            ];
        }
        $consulta = sifen_consultar_ruc($conection, $ruc, $conConsulta, $usarCache);
        $vista = sifen_vista_consulta_ruc($consulta, $nombreCajero);
        if (empty($consulta['ok']) && !empty($vista['bloquea_emision']) && !sifen_esta_activo($conection)) {
            $vista['bloquea_emision'] = false;
        }
        return $vista;
    }
}

if (!function_exists('sifen_buscar_cliente_id')) {
    function sifen_buscar_cliente_id($conection, $idcliente, $nitOriginal, $rucCanonico)
    {
        $id = (int)$idcliente;
        if ($id > 1) {
            return $id;
        }
        $candidatos = [];
        foreach ([$nitOriginal, $rucCanonico, str_replace('.', '', (string)$nitOriginal)] as $nit) {
            $nit = sifen_limpiar_ruc($nit);
            if ($nit !== '' && !in_array($nit, $candidatos, true)) {
                $candidatos[] = $nit;
            }
        }
        foreach ($candidatos as $nit) {
            $stmt = mysqli_prepare(
                $conection,
                "SELECT idcliente FROM cliente WHERE nit = ? AND status = 1 AND idcliente <> 1 ORDER BY idcliente ASC LIMIT 1"
            );
            if (!$stmt) {
                continue;
            }
            mysqli_stmt_bind_param($stmt, 's', $nit);
            mysqli_stmt_execute($stmt);
            $found = 0;
            mysqli_stmt_bind_result($stmt, $found);
            $ok = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            if ($ok && (int)$found > 1) {
                return (int)$found;
            }
        }
        return 0;
    }
}

if (!function_exists('sifen_actualizar_cliente_local')) {
    function sifen_actualizar_cliente_local($conection, $idcliente, $ruc, $razonSocial)
    {
        $id = (int)$idcliente;
        $ruc = trim((string)$ruc);
        if ($id <= 1 || $ruc === '') {
            return;
        }
        $razonSocial = trim((string)$razonSocial);
        if ($razonSocial !== '') {
            if (function_exists('mb_substr')) {
                $razonSocial = mb_substr($razonSocial, 0, 80, 'UTF-8');
            } else {
                $razonSocial = substr($razonSocial, 0, 80);
            }
            $stmt = mysqli_prepare($conection, "UPDATE cliente SET nit = ?, nombre = ? WHERE idcliente = ? AND status = 1");
            if (!$stmt) {
                return;
            }
            mysqli_stmt_bind_param($stmt, 'ssi', $ruc, $razonSocial, $id);
        } else {
            $stmt = mysqli_prepare($conection, "UPDATE cliente SET nit = ? WHERE idcliente = ? AND status = 1");
            if (!$stmt) {
                return;
            }
            mysqli_stmt_bind_param($stmt, 'si', $ruc, $id);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('sifen_aplicar_ruc_local')) {
    function sifen_aplicar_ruc_local($conection, $idcliente, $nitOriginal, $vista, $nombreSiNoHayRazon = '')
    {
        unset($nombreSiNoHayRazon);
        $ruc = isset($vista['ruc']) ? trim((string)$vista['ruc']) : '';
        if (empty($vista['dv_aceptado']) || $ruc === '') {
            return;
        }
        $id = sifen_buscar_cliente_id($conection, $idcliente, $nitOriginal, $ruc);
        if ($id <= 1) {
            return;
        }
        $nombre = '';
        if (!empty($vista['autocompletar'])) {
            $candidata = trim((string)($vista['razon_social'] ?? ''));
            if ($candidata !== '' && !sifen_es_razon_ejemplo($candidata)) {
                $nombre = $candidata;
            }
        }
        sifen_actualizar_cliente_local($conection, $id, $ruc, $nombre);
    }
}

if (!function_exists('sifen_preparar_cliente_venta')) {
    function sifen_preparar_cliente_venta($conection, $idcliente, $nombrePantalla = '')
    {
        $config = sifen_get_config($conection);
        if ($config['api_url'] === '' || $config['api_token'] === '') {
            return ['ok' => true];
        }

        $id = (int)$idcliente;
        if ($id <= 0) {
            $id = 1;
        }
        $nit = '';
        $nombre = '';
        $stmt = mysqli_prepare($conection, "SELECT nit, nombre FROM cliente WHERE idcliente = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $nit, $nombre);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }
        $nit = sifen_limpiar_ruc($nit);
        if (strlen($nit) > 20 && preg_match('/\d/', $nit)) {
            return ['ok' => false, 'msg' => 'El RUC admite como máximo 20 caracteres.'];
        }
        if (!sifen_parece_ruc($nit)) {
            return ['ok' => true];
        }

        $vista = sifen_evaluar_ruc($conection, $nit, (string)$nombre, true, false);
        if (!empty($vista['bloquea_emision'])) {
            $msg = trim((string)($vista['msg'] ?? ''));
            return ['ok' => false, 'msg' => $msg !== '' ? $msg : 'No se pudo consultar el RUC.'];
        }
        sifen_aplicar_ruc_local($conection, $id, $nit, $vista);
        return ['ok' => true];
    }
}
