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
    function sifen_build_payload($conection, $noventa)
    {
        $noventa = (int)$noventa;
        $config = sifen_get_config($conection);

        $queryVenta = mysqli_query($conection, "SELECT f.noventa, f.descuento, f.totalventa, f.tipo_pago_detalle,
                                                        cl.nit, cl.nombre, cl.telefono, cl.direccion
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
        if (sifen_es_ruc($nit)) {
            $cliente['contribuyente'] = true;
            $cliente['ruc'] = $nit;
        }
        if (!empty($venta['direccion'])) {
            $cliente['direccion'] = $venta['direccion'];
        }
        if (!empty($venta['telefono'])) {
            $cliente['telefono'] = (string)$venta['telefono'];
        }

        $total = (float)$venta['totalventa'];
        if ($total <= 0) {
            $total = max(0, $subtotal - $descuento);
        }
        $tipoPago = (int)$venta['tipo_pago_detalle'];
        if ($tipoPago === 3) {
            $condicion = [
                'tipo' => 2,
                'credito' => [
                    'tipo' => 1,
                    'plazo' => '30 dias',
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
        $numero = isset($datos['numero']) && $datos['numero'] !== '' && $datos['numero'] !== null
            ? (string)(int)$datos['numero']
            : null;
        $idempotency = $datos['idempotency_key'] ?? ('venta-' . $noventa);
        $error = $datos['error_message'] ?? null;
        $request = $datos['request_payload'] ?? null;
        $response = $datos['response_raw'] ?? null;

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

if (!function_exists('sifen_kude_url')) {
    function sifen_kude_url($apiUrl, $fe)
    {
        $url = trim((string)($fe['kude_url'] ?? ''));
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        $path = isset($fe['kude_path']) ? trim((string)$fe['kude_path']) : '';
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return '';
    }
}

if (!function_exists('sifen_emitir')) {
    function sifen_emitir($conection, $noventa)
    {
        $noventa = (int)$noventa;
        $armado = sifen_build_payload($conection, $noventa);
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
            if ($previo['status'] === 'approved' && !empty($previo['document_id'])) {
                return [
                    'status' => $previo['status'],
                    'cdc' => $previo['cdc'],
                    'kude_url' => sifen_kude_url($apiUrl, $previo),
                    'error' => $previo['error_message'],
                    'document_id' => $previo['document_id'],
                ];
            }
            if (in_array($previo['status'], ['queued', 'processing', 'contingency'], true) && !empty($previo['document_id'])) {
                $documentId = $previo['document_id'];
                $doc = $previo;
                $status = $previo['status'];
                $ultimo = ['raw' => $previo['response_raw'] ?? '', 'json' => null, 'http' => 200, 'error' => ''];
                $intentos = 0;
                while ($intentos < 6 && in_array($status, ['queued', 'processing', 'contingency'], true)) {
                    usleep(700000);
                    $intentos++;
                    $consulta = sifen_request('GET', $apiUrl . '/api/v1/documents/' . rawurlencode($documentId), $token, null, 8);
                    $ultimo = $consulta;
                    if (!empty($consulta['json'])) {
                        $doc = sifen_documento_desde_respuesta($consulta['json']);
                        if (!empty($doc['status'])) {
                            $status = $doc['status'];
                        }
                    }
                }
                $error = $doc['error_message'] ?? null;
                $kudeUrl = sifen_kude_url($apiUrl, $doc);
                $guardado = [
                    'noventa' => $noventa,
                    'document_id' => $doc['id'] ?? $documentId,
                    'status' => $status,
                    'cdc' => $doc['cdc'] ?? ($previo['cdc'] ?? null),
                    'qr_url' => $doc['qr_url'] ?? ($previo['qr_url'] ?? null),
                    'kude_path' => $kudeUrl !== '' ? $kudeUrl : ($previo['kude_path'] ?? null),
                    'numero' => $doc['number'] ?? ($previo['numero'] ?? null),
                    'idempotency_key' => $payload['idempotency_key'],
                    'error_message' => $error,
                    'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'response_raw' => $ultimo['raw'] ?? '',
                ];
                sifen_guardar($conection, $guardado);
                return [
                    'status' => $status,
                    'cdc' => $guardado['cdc'],
                    'kude_url' => sifen_kude_url($apiUrl, $guardado),
                    'error' => $error,
                    'document_id' => $guardado['document_id'],
                ];
            }
        }

        $emision = sifen_request('POST', $apiUrl . '/api/v1/documents', $token, $payload, 12);
        $doc = sifen_documento_desde_respuesta($emision['json']);
        $documentId = $doc['id'] ?? null;
        $status = $doc['status'] ?? 'error';

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

        $ultimo = $emision;
        $intentos = 0;
        while ($intentos < 6 && in_array($status, ['queued', 'processing', 'contingency'], true)) {
            usleep(700000);
            $intentos++;
            $consulta = sifen_request('GET', $apiUrl . '/api/v1/documents/' . rawurlencode($documentId), $token, null, 8);
            $ultimo = $consulta;
            if (!empty($consulta['json'])) {
                $doc = sifen_documento_desde_respuesta($consulta['json']);
                if (!empty($doc['status'])) {
                    $status = $doc['status'];
                }
            }
            if (in_array($status, ['approved', 'rejected', 'cancelled', 'error'], true)) {
                break;
            }
        }

        $error = null;
        if (!empty($doc['error_message'])) {
            $error = $doc['error_message'];
        } elseif (in_array($status, ['rejected', 'error'], true)) {
            $error = sifen_mensaje_error($ultimo);
        }

        $kudeUrl = sifen_kude_url($apiUrl, $doc);
        $guardado = [
            'noventa' => $noventa,
            'document_id' => $doc['id'] ?? $documentId,
            'status' => $status,
            'cdc' => $doc['cdc'] ?? null,
            'qr_url' => $doc['qr_url'] ?? null,
            'kude_path' => $kudeUrl !== '' ? $kudeUrl : null,
            'numero' => $doc['number'] ?? null,
            'idempotency_key' => $payload['idempotency_key'],
            'error_message' => $error,
            'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'response_raw' => $ultimo['raw'] ?? '',
        ];
        sifen_guardar($conection, $guardado);

        return [
            'status' => $status,
            'cdc' => $guardado['cdc'],
            'kude_url' => sifen_kude_url($apiUrl, $guardado),
            'error' => $error,
            'document_id' => $guardado['document_id'],
        ];
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
