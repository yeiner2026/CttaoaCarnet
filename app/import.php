<?php
// Importación de Excel: análisis (sin escribir) y aplicación (transacción única).
const CAMPOS_IMPORT = [
    'tipo_persona' => ['tipo de titular', 'tipo titular', 'tipo persona', 'tipo de persona', 'tipo'],
    'tipo_documento' => ['tipo documento', 'tipo de documento', 'tipo doc'],
    'numero_documento' => ['numero de documento', 'número de documento', 'numero documento', 'documento', 'identificacion', 'identificación', 'cedula', 'cédula'],
    'nombres' => ['nombres', 'nombre'],
    'apellidos' => ['apellidos', 'apellido'],
    'fecha_expedicion' => ['fecha de expedicion', 'fecha de expedición', 'fecha expedicion', 'fecha expedición', 'expedicion', 'expedición'],
    'ficha' => ['numero de ficha', 'número de ficha', 'numero ficha', 'ficha', 'grupo'],
    'programa' => ['programa de formacion', 'programa de formación', 'programa'],
    'dependencia' => ['area o dependencia', 'área o dependencia', 'area, dependencia o cargo', 'área, dependencia o cargo', 'dependencia', 'cargo', 'area', 'área'],
    'rh' => ['tipo de sangre', 'tipo de sangre rh', 'rh', 'grupo sanguineo', 'grupo sanguíneo'],
    'email' => ['correo electronico', 'correo electrónico', 'correo', 'email', 'e-mail'],
    'ficha_inicio' => ['fecha de inicio de la ficha', 'fecha inicio ficha', 'inicio de la ficha', 'fecha de inicio'],
    'ficha_fin' => ['fecha de fin de la ficha', 'fecha fin ficha', 'fin de la ficha', 'fecha de fin', 'fecha de terminacion', 'fecha de terminación'],
    'estado_formacion' => ['estado de formacion', 'estado de formación', 'estado del aprendiz', 'estado en la formacion', 'estado en la formación', 'situacion del aprendiz', 'situación del aprendiz'],
];
const OBLIGATORIOS_IMPORT = ['tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'fecha_expedicion', 'rh'];

/** Convierte el texto del Excel («Etapa práctica», «Retiro voluntario»…) en el código del estado, o null si no se reconoce. */
function imp_estado_formacion($t) {
    $t = mb_strtolower(trim((string)$t)); $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']); $t = trim(preg_replace('/\s+/', ' ', $t));
    $alias = ['en_formacion' => ['en formacion', 'formacion', 'activo', 'activa', 'en formacion (activo)'],
              'etapa_practica' => ['etapa practica', 'en etapa practica', 'etapa productiva', 'en etapa productiva', 'practica'],
              'retiro_voluntario' => ['retiro voluntario', 'retiro', 'retirado'],
              'cancelado' => ['cancelado', 'cancelacion', 'cancelada'],
              'certificado' => ['certificado', 'certificado (egresado)', 'egresado', 'egresada']];
    foreach ($alias as $cod => $lista) if (in_array($t, $lista, true)) return $cod;
    return null;
}
function imp_norm($s) { $s = preg_replace('/\(.*?\)/u', '', (string)$s); $s = str_replace('*', '', $s); return trim(preg_replace('/\s+/u', ' ', mb_strtolower($s))); }
function imp_mapeo_auto($cols) {
    $m = [];
    foreach (CAMPOS_IMPORT as $campo => $alias) foreach ($cols as $i => $c) if (in_array(imp_norm($c), $alias, true)) { $m[$campo] = $i; break; }
    return $m;
}
function imp_leer($ruta, $hoja) {
    $filas = xlsx_leer($ruta, $hoja);
    $cols = isset($filas[1]) ? array_map('trim', $filas[1]) : [];
    unset($filas[1]);
    return [$cols, $filas];
}
function imp_analizar($ruta, $hoja, $mapeo) {
    list($cols, $filas) = imp_leer($ruta, $hoja);
    $map = [];
    foreach ($mapeo as $k => $v) if ($v !== '' && $v !== null && isset(CAMPOS_IMPORT[$k])) $map[$k] = (int)$v;
    $faltan = array_values(array_diff(OBLIGATORIOS_IMPORT, array_keys($map)));
    if ($faltan) return [[], ['fallo' => 'Faltan columnas obligatorias o sus encabezados no coinciden con la plantilla: ' . implode(', ', $faltan)]];
    $tipos = [];
    foreach (TIPOS_PERSONA as $k => $v) { $tipos[mb_strtolower($v)] = $k; $tipos[$k] = $k; }
    $out = []; $vistos = []; $programas = []; $fechasFicha = []; $correos = [];
    foreach ($filas as $n => $fila) {
        $g = function ($c) use ($map, $fila) { return isset($map[$c]) ? trim((string)($fila[$map[$c]] ?? '')) : ''; };
        if (!array_filter($fila, function ($x) { return trim((string)$x) !== ''; })) continue;
        if (strpos(mb_strtoupper($g('nombres')), 'EJEMPLO') === 0) continue;
        $d = [];
        foreach (array_keys(CAMPOS_IMPORT) as $c) $d[$c] = $g($c);
        $d['nombres'] = titulo_nombre($d['nombres']); $d['apellidos'] = titulo_nombre($d['apellidos']);
        $d['numero_documento'] = preg_replace('/[\s.\-]/', '', $d['numero_documento']);
        $d['tipo_documento'] = strtoupper(str_replace(['.', ' '], '', $d['tipo_documento']));
        $raw_tp = $d['tipo_persona'];
        $d['tipo_persona'] = $raw_tp !== '' ? ($tipos[mb_strtolower($raw_tp)] ?? '') : ($d['ficha'] !== '' ? 'aprendiz' : 'funcionario');
        $d['rh'] = strtoupper(str_replace(' ', '', $d['rh']));
        $ef_raw = $d['estado_formacion']; $d['estado_formacion'] = '';
        list($fecha, $ef) = parsear_fecha($d['fecha_expedicion']); $d['fecha_expedicion'] = $fecha ?: '';
        $err = []; $avisos = [];
        if ($ef) $err[] = $ef;
        if ($raw_tp !== '' && $d['tipo_persona'] === '') $err[] = "tipo de titular no permitido ('" . $raw_tp . "'): usa aprendiz, instructor, funcionario o contratista";
        if ($d['numero_documento'] === '') $err[] = 'sin número de documento';
        elseif (!ctype_digit($d['numero_documento'])) $err[] = 'documento no numérico';
        if (strlen($d['numero_documento']) > 20) $err[] = 'documento demasiado largo';
        if ($d['nombres'] === '' || $d['apellidos'] === '') $err[] = 'nombres o apellidos vacíos';
        if (mb_strlen($d['nombres']) > 100 || mb_strlen($d['apellidos']) > 100) $err[] = 'nombre demasiado largo';
        if ($d['tipo_documento'] === '') $err[] = 'sin tipo de documento';
        elseif (!isset(TIPOS_DOC[$d['tipo_documento']])) $err[] = "tipo de documento inválido ('" . $d['tipo_documento'] . "')";
        if ($d['rh'] === '') $err[] = 'sin tipo de sangre (RH)';
        elseif (!in_array($d['rh'], GRUPOS_RH, true)) $err[] = 'RH inválido';
        if ($d['fecha_expedicion'] === '' && !$ef) $err[] = 'sin fecha de expedición';
        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'correo electrónico inválido';
        elseif ($d['email'] !== '') { $ke = mb_strtolower($d['email']); if (isset($correos[$ke])) $err[] = 'correo repetido en el archivo (fila ' . $correos[$ke] . ')'; else $correos[$ke] = $n; }
        if ($ef_raw !== '') {
            $cod = imp_estado_formacion($ef_raw);
            if ($cod === null) $err[] = "estado de formación no válido ('" . $ef_raw . "'): usa En formación, Etapa práctica, Retiro voluntario, Cancelado o Certificado";
            elseif ($d['tipo_persona'] === 'aprendiz') $d['estado_formacion'] = $cod;
            else $avisos[] = 'el estado de formación solo aplica a aprendices; se ignora';
        }
        list($f_ini, $e_ini) = parsear_fecha($d['ficha_inicio'], true, 'fecha de inicio de la ficha'); list($f_fin, $e_fin) = parsear_fecha($d['ficha_fin'], true, 'fecha de fin de la ficha');
        $d['ficha_inicio'] = $f_ini ?: ''; $d['ficha_fin'] = $f_fin ?: '';
        if ($e_ini || $e_fin) $err[] = 'fecha de la ficha no válida (usa dd/mm/aaaa)';
        elseif ($d['ficha_inicio'] !== '' && $d['ficha_fin'] !== '' && $d['ficha_fin'] < $d['ficha_inicio']) $err[] = 'la fecha de fin de la ficha es anterior a la de inicio';
        if ($d['tipo_persona'] === 'aprendiz') {
            if ($d['ficha'] === '') $err[] = 'aprendiz sin número de ficha';
            else {
                if (strlen($d['ficha']) > 20) $err[] = 'número de ficha demasiado largo';
                $fx = row('SELECT f.numero, p.nombre AS programa FROM fichas f JOIN programas p ON p.id=f.programa_id WHERE f.numero=?', [$d['ficha']]);
                if (!$fx && $d['programa'] === '') $err[] = 'ficha nueva sin programa de formación';
                if ($fx && $d['programa'] !== '' && mb_strtolower($d['programa']) !== mb_strtolower($fx['programa']))
                    $avisos[] = 'la ficha ya existe con el programa «' . $fx['programa'] . '»; se conserva ese programa';
                if ($d['ficha_inicio'] !== '' || $d['ficha_fin'] !== '') {
                    $clave = $d['ficha_inicio'] . '|' . $d['ficha_fin'];
                    if (isset($fechasFicha[$d['ficha']]) && $fechasFicha[$d['ficha']] !== $clave) $err[] = 'la ficha ' . $d['ficha'] . ' aparece en el archivo con fechas distintas';
                    $fechasFicha[$d['ficha']] = $fechasFicha[$d['ficha']] ?? $clave;
                    $fxf = row('SELECT fecha_inicio, fecha_fin FROM fichas WHERE numero=?', [$d['ficha']]);
                    if ($fxf && ($fxf['fecha_inicio'] || $fxf['fecha_fin'])) $avisos[] = 'la ficha ya tiene fechas (' . ($fxf['fecha_inicio'] ? fecha_legible($fxf['fecha_inicio']) : '—') . ' a ' . ($fxf['fecha_fin'] ? fecha_legible($fxf['fecha_fin']) : '—') . '); se conservan: cámbialas en la pantalla de la ficha';
                }
                if ($d['programa'] !== '') {
                    if (isset($programas[$d['ficha']]) && mb_strtolower($programas[$d['ficha']]) !== mb_strtolower($d['programa'])) $err[] = 'la ficha ' . $d['ficha'] . ' aparece en el archivo con programas distintos';
                    $programas[$d['ficha']] = $programas[$d['ficha']] ?? $d['programa'];
                }
            }
        }
        if (in_array($d['tipo_persona'], ['instructor', 'funcionario', 'contratista'], true) && $d['dependencia'] === '') $err[] = 'sin área o dependencia';
        if ($d['numero_documento'] !== '' && isset($vistos[$d['numero_documento']])) $err[] = 'documento duplicado en el archivo (fila ' . $vistos[$d['numero_documento']] . ')';
        elseif ($d['numero_documento'] !== '') $vistos[$d['numero_documento']] = $n;
        $estado = 'nuevo'; $cambios = []; $existente = null;
        if ($err) $estado = 'error';
        else {
            $ex = row('SELECT p.*, f.numero AS ficha_numero, pr.nombre AS programa_nombre FROM personas p LEFT JOIN fichas f ON f.id=p.ficha_id LEFT JOIN programas pr ON pr.id=f.programa_id WHERE p.numero_documento=?', [$d['numero_documento']]);
            if ($d['email'] !== '' && correo_en_uso($d['email'], $ex ? $ex['id'] : null)) { $err[] = 'el correo ya está registrado con otra persona'; $estado = 'error'; }
            elseif ($ex) {
                $comp = [['tipo_documento', $ex['tipo_documento']], ['tipo_persona', $ex['tipo_persona']], ['nombres', $ex['nombres']], ['apellidos', $ex['apellidos']],
                         ['ficha', (string)$ex['ficha_numero']], ['dependencia', $ex['dependencia']], ['fecha_expedicion', (string)$ex['fecha_expedicion']], ['email', $ex['email']], ['rh', (string)$ex['rh']], ['estado_formacion', formacion_codigo($ex)]];
                foreach ($comp as $c) if ($d[$c[0]] !== '' && $d[$c[0]] !== $c[1]) $cambios[] = $c[0] . ": '" . $c[1] . "' → '" . $d[$c[0]] . "'";
                $estado = $cambios ? 'con_cambios' : 'sin_cambios';
                $existente = ['nombre' => persona_nombre($ex), 'tipo' => TIPOS_PERSONA[$ex['tipo_persona']], 'ficha' => (string)$ex['ficha_numero'],
                              'programa' => $ex['ficha_numero'] ? $ex['programa_nombre'] : $ex['dependencia'],
                              'mueve_ficha' => $d['ficha'] !== '' && $d['ficha'] !== (string)$ex['ficha_numero']];
            }
        }
        $out[] = ['fila' => $n, 'datos' => $d, 'estado' => $estado, 'errores' => $err, 'avisos' => $avisos, 'cambios' => $cambios, 'existente' => $existente];
    }
    $res = ['nuevo' => 0, 'con_cambios' => 0, 'sin_cambios' => 0, 'error' => 0, 'total' => count($out), 'avisos' => 0];
    $fichas = [];
    foreach ($out as $f) { $res[$f['estado']]++; if ($f['avisos']) $res['avisos']++; if ($f['datos']['ficha'] !== '') $fichas[$f['datos']['ficha']] = 1; }
    $res['fichas'] = array_keys($fichas); sort($res['fichas']);
    return [$out, $res];
}
function imp_ficha_para($d) {
    if ($d['ficha'] === '') return null;
    $fi = ficha_obtener_o_crear($d['ficha'], $d['programa']);
    if (($d['ficha_inicio'] ?? '') !== '' || ($d['ficha_fin'] ?? '') !== '') ficha_fechas_si_vacias($fi['id'], $d['ficha_inicio'], $d['ficha_fin']);
    return $fi;
}
function imp_aplicar($filas, $actualizar_todos, $filas_actualizar) {
    $creados = $actualizados = 0; $pdo = db(); $pdo->beginTransaction();
    try {
        foreach ($filas as $f) {
            $d = $f['datos'];
            if ($f['estado'] === 'nuevo') {
                $fi = $d['tipo_persona'] === 'aprendiz' ? imp_ficha_para($d) : null;
                $id = insert('personas', ['tipo_persona' => $d['tipo_persona'], 'tipo_documento' => $d['tipo_documento'], 'numero_documento' => $d['numero_documento'],
                    'nombres' => $d['nombres'], 'apellidos' => $d['apellidos'], 'fecha_expedicion' => $d['fecha_expedicion'] ?: null, 'email' => $d['email'], 'rh' => $d['rh'],
                    'rh_verificado' => 1, 'ficha_id' => $fi ? $fi['id'] : null, 'dependencia' => $d['dependencia'], 'estado_registro' => 'pendiente', 'creada' => now(), 'actualizada' => now(),
                    'estado_formacion' => $d['tipo_persona'] === 'aprendiz' ? ($d['estado_formacion'] !== '' ? $d['estado_formacion'] : 'en_formacion') : '', 'estado_formacion_fecha' => $d['tipo_persona'] === 'aprendiz' ? now() : null]);
                recalcular_registro($id);
                if ($d['tipo_persona'] === 'aprendiz') aplicar_efecto_formacion($id); audit('importar_crear', 'Persona', $id, 'Creada desde Excel'); $creados++;
            } elseif ($f['estado'] === 'con_cambios' && ($actualizar_todos || in_array($f['fila'], $filas_actualizar, true))) {
                $p = row('SELECT * FROM personas WHERE numero_documento=?', [$d['numero_documento']]);
                $c = [];
                foreach (['tipo_documento', 'tipo_persona', 'nombres', 'apellidos', 'dependencia', 'email', 'rh'] as $k) if ($d[$k] !== '') $c[$k] = $d[$k];
                if ($d['rh'] !== '') $c['rh_verificado'] = 1;
                if ($d['fecha_expedicion'] !== '') $c['fecha_expedicion'] = $d['fecha_expedicion'];
                $tipo = $c['tipo_persona'] ?? $p['tipo_persona'];
                if ($d['ficha'] !== '' && $tipo === 'aprendiz') { $fi = imp_ficha_para($d); $c['ficha_id'] = $fi['id']; }
                $c['actualizada'] = now(); update('personas', $c, $p['id']); recalcular_registro($p['id']);
                if ($d['estado_formacion'] !== '' && ($c['tipo_persona'] ?? $p['tipo_persona']) === 'aprendiz') cambiar_estado_formacion($p['id'], $d['estado_formacion']);
                audit('importar_actualizar', 'Persona', $p['id'], implode('; ', $f['cambios'])); $actualizados++;
            }
        }
        $pdo->commit();
    } catch (Exception $ex) { $pdo->rollBack(); throw $ex; }
    $omitidos = 0; foreach ($filas as $f) if (in_array($f['estado'], ['con_cambios', 'sin_cambios'], true)) $omitidos++;
    return ['creados' => $creados, 'actualizados' => $actualizados, 'omitidos' => $omitidos - $actualizados];
}
