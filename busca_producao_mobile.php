<?php
// =========================================================================
// busca_producao_mobile.php — Versao 2.0.0
// Pagina mobile-first dedicada a busca de producao de cedulas.
// Aceita: lote, display (lacre IIPR / lacre Correios), numero do posto,
// data e etiqueta Correios (35 digitos). Pode ser aberta via QR Code
// passando ?q=<valor> na URL, e abre direto na tela de resultados.
// Retorno: oficio(s), postos, lacres, displays, lotes e movimentos envolvidos.
// =========================================================================
require_once __DIR__ . '/db_config.php';

@date_default_timezone_set('America/Sao_Paulo');

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function so_digitos($s) {
    return preg_replace('/\D+/', '', (string)$s);
}
function normaliza_posto($s) {
    $d = so_digitos($s);
    if ($d === '') return '';
    $d = ltrim($d, '0');
    if ($d === '') $d = '0';
    return str_pad($d, 3, '0', STR_PAD_LEFT);
}
// Postos POUPA TEMPO da Capital + Regiao Metropolitana (codigos 005 a 080)
// NAO usam etiqueta dos Correios nem lacre Correios. Somente o interior (>80)
// usa. Mesma regra da rastreabilidade.php (postoUsaEtiquetaCorreios).
function postoUsaEtiquetaCorreios($grupo, $codigo) {
    $g = strtolower((string)$grupo);
    $ehPT = (strpos($g, 'poupa') !== false || strpos($g, 'tempo') !== false);
    if (!$ehPT) return true;
    if (!preg_match('/^[0-9]+$/', (string)$codigo)) return true;
    $n = (int)$codigo;
    return !($n >= 5 && $n <= 80);
}
function fmt_data_br($iso) {
    if (!$iso) return '';
    $s = substr((string)$iso, 0, 10);
    $p = explode('-', $s);
    if (count($p) === 3) return $p[2] . '/' . $p[1] . '/' . $p[0];
    return $iso;
}
function fmt_dt_br($iso) {
    if (!$iso) return '';
    $s = (string)$iso;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/', $s, $m)) {
        return $m[3] . '/' . $m[2] . '/' . $m[1] . ' ' . $m[4] . ':' . $m[5];
    }
    return fmt_data_br($s);
}
// Descobre colunas existentes em uma tabela (lowercase => nome real).
// Retorna array vazio se a tabela nao existir.
function colunas_tabela($pdo, $tabela) {
    $cols = array();
    try {
        $tbl = preg_replace('/[^A-Za-z0-9_]/', '', $tabela);
        $st  = $pdo->query("SHOW COLUMNS FROM `" . $tbl . "`");
        if ($st) {
            while ($r = $st->fetch()) {
                $cols[strtolower($r['Field'])] = $r['Field'];
            }
        }
    } catch (Exception $e) { /* tabela ausente — segue vazio */ }
    return $cols;
}
// v2.3.3: regional canonica do posto (fonte da verdade = ciRegionais), com cache.
// ciPostosCsv pode guardar a regional vinda do codigo de barras (ex.: 501) em
// vez da regional real do posto (ex.: 527 para o posto 527).
function regionalCanonica($pdo, $posto) {
    static $cache = array();
    $p = preg_replace('/\D+/', '', (string)$posto);
    if ($p === '') return '';
    $p3 = str_pad($p, 3, '0', STR_PAD_LEFT);
    if (array_key_exists($p3, $cache)) return $cache[$p3];
    $cache[$p3] = '';
    try {
        $colsR = colunas_tabela($pdo, 'ciRegionais');
        if (!empty($colsR) && isset($colsR['regional'])) {
            $cPosto = isset($colsR['posto']) ? 'posto' : (isset($colsR['nposto']) ? 'nposto' : null);
            if ($cPosto) {
                $st = $pdo->prepare("SELECT `" . $colsR['regional'] . "` AS regional FROM ciRegionais WHERE LPAD(`$cPosto`,3,'0') = ? LIMIT 1");
                $st->execute(array($p3));
                $row = $st->fetch();
                if ($row && isset($row['regional'])) {
                    $rv = preg_replace('/\D+/', '', (string)$row['regional']);
                    if ($rv !== '') $cache[$p3] = $rv;
                }
            }
        }
    } catch (Exception $e) { /* tolera */ }
    return $cache[$p3];
}
// v2.3.3: normaliza data/hora p/ ordenacao (sort) + exibicao (disp) na timeline.
function tlNormTs($raw) {
    $r = trim((string)$raw);
    if ($r === '' || strpos($r, '0000-00-00') === 0) {
        return array('sort' => '9999-99-99 99:99:99', 'disp' => '—');
    }
    $dt = false;
    $fmts = array('Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y');
    foreach ($fmts as $f) {
        $tmp = DateTime::createFromFormat($f, $r);
        if ($tmp !== false) { $dt = $tmp; break; }
    }
    if ($dt === false) {
        $ts = strtotime($r);
        if ($ts !== false) { $dt = new DateTime('@' . $ts); }
    }
    if ($dt === false) return array('sort' => '9999-99-99 99:99:99', 'disp' => $r);
    $temHora = (strpos($r, ':') !== false);
    return array(
        'sort' => $dt->format('Y-m-d H:i:s'),
        'disp' => $temHora ? $dt->format('d/m/Y H:i:s') : $dt->format('d/m/Y')
    );
}
function tlCmpEventos($a, $b) {
    if ($a['sort'] === $b['sort']) return 0;
    return ($a['sort'] < $b['sort']) ? -1 : 1;
}
// v2.3.3: monta a linha do tempo unificada de UM lote (producao -> triagem ->
// conferencia -> oficio/fechamento -> despacho -> movimentos de display).
function montar_timeline($info) {
    $ev = array();
    if (!empty($info['producao'])) {
        foreach ($info['producao'] as $pr) {
            $det = array();
            if (isset($pr['posto']))      $det[] = 'Posto ' . normaliza_posto($pr['posto']);
            if (!empty($pr['regional']))  $det[] = 'Regional ' . $pr['regional'];
            if (isset($pr['qtd']))        $det[] = 'Qtd ' . (int)$pr['qtd'];
            $n = tlNormTs(isset($pr['dt']) ? $pr['dt'] : '');
            $ev[] = array('sort'=>$n['sort'],'disp'=>$n['disp'],'fase'=>'Producao / Expedicao',
                'resp'=>isset($pr['usuario'])?$pr['usuario']:'','det'=>implode(' · ', $det),'cor'=>'azul');
        }
    }
    if (!empty($info['triado'])) {
        foreach ($info['triado'] as $tr) {
            $det = array();
            if (isset($tr['posto'])) $det[] = 'Posto ' . normaliza_posto($tr['posto']);
            $n = tlNormTs(isset($tr['dt']) ? $tr['dt'] : '');
            $ev[] = array('sort'=>$n['sort'],'disp'=>$n['disp'],'fase'=>'Triado para a estante',
                'resp'=>isset($tr['usuario'])?$tr['usuario']:'','det'=>implode(' · ', $det),'cor'=>'roxo');
        }
    }
    if (!empty($info['estante'])) {
        foreach ($info['estante'] as $es) {
            $c = isset($es['conf']) ? strtolower(trim((string)$es['conf'])) : '';
            $conferido = ($c === 's' || $c === 'sim' || $c === '1' || $c === 'y');
            $det = array();
            if (isset($es['posto'])) $det[] = 'Posto ' . normaliza_posto($es['posto']);
            $det[] = $conferido ? 'Conferido' : 'Pendente';
            $n = tlNormTs(isset($es['dt']) ? $es['dt'] : '');
            $ev[] = array('sort'=>$n['sort'],'disp'=>$n['disp'],'fase'=>$conferido?'Conferido':'Conferencia (pendente)',
                'resp'=>isset($es['usuario'])?$es['usuario']:'','det'=>implode(' · ', $det),'cor'=>'verde');
        }
    }
    if (!empty($info['oficio'])) {
        foreach ($info['oficio'] as $o) {
            $det = array();
            if (!empty($o['id_despacho']))       $det[] = 'Oficio #' . (int)$o['id_despacho'];
            if (!empty($o['posto']))             $det[] = 'Posto ' . normaliza_posto($o['posto']);
            if (!empty($o['etiquetaiipr']))      $det[] = 'Lacre IIPR ' . $o['etiquetaiipr'];
            if (!empty($o['etiquetacorreios']))  $det[] = 'Lacre Correios ' . $o['etiquetacorreios'];
            if (!empty($o['etiqueta_correios'])) $det[] = 'Display ' . $o['etiqueta_correios'];
            $n = tlNormTs(isset($o['data_carga']) ? $o['data_carga'] : '');
            $ev[] = array('sort'=>$n['sort'],'disp'=>$n['disp'],'fase'=>'Fechado em oficio',
                'resp'=>'','det'=>implode(' · ', $det),'cor'=>'laranja');
            $dd = isset($o['data_despacho_correios']) ? trim((string)$o['data_despacho_correios']) : '';
            if ($dd !== '' && strpos($dd, '0000-00-00') !== 0) {
                $nd = tlNormTs($dd);
                $ev[] = array('sort'=>$nd['sort'],'disp'=>$nd['disp'],'fase'=>'Despachado',
                    'resp'=>isset($o['despachado_por'])?$o['despachado_por']:'',
                    'det'=>(!empty($o['id_despacho'])?'Oficio #'.(int)$o['id_despacho']:''),'cor'=>'laranja');
            }
        }
    }
    if (!empty($info['despacho_inferido'])) {
        $n = tlNormTs($info['despacho_inferido']);
        $ev[] = array('sort'=>$n['sort'],'disp'=>$n['disp'],'fase'=>'Despachado (provavel)',
            'resp'=>'','det'=>'inferido pelo retorno de displays','cor'=>'laranja');
    }
    if (!empty($info['adiantado'])) {
        foreach ($info['adiantado'] as $ad) {
            $det = array();
            if (!empty($ad['posto'])) $det[] = 'Posto ' . normaliza_posto($ad['posto']);
            if (isset($ad['num']) && trim((string)$ad['num']) !== '') $det[] = 'Oficio ' . $ad['num'];
            if (isset($ad['obs']) && trim((string)$ad['obs']) !== '')  $det[] = $ad['obs'];
            $n = tlNormTs(isset($ad['dt']) ? $ad['dt'] : '');
            $ev[] = array('sort'=>$n['sort'],'disp'=>$n['disp'],'fase'=>'Adiantado',
                'resp'=>'','det'=>implode(' · ', $det),'cor'=>'vermelho');
        }
    }
    if (!empty($info['movimentos'])) {
        foreach ($info['movimentos'] as $mv) {
            $tipo = (int)$mv['tipo'];
            $fase = ($tipo===1?'Display enviado':($tipo===2?'Display recebido':'Movimento de display'));
            $det = array();
            if (!empty($mv['leitura'])) $det[] = 'Etiq. ' . $mv['leitura'];
            if (!empty($mv['posto']))   $det[] = 'Posto ' . normaliza_posto($mv['posto']);
            $n = tlNormTs(isset($mv['data']) ? $mv['data'] : '');
            $ev[] = array('sort'=>$n['sort'],'disp'=>$n['disp'],'fase'=>$fase,
                'resp'=>isset($mv['login'])?$mv['login']:'','det'=>implode(' · ', $det),
                'cor'=>($tipo===2?'verde':'cinza'));
        }
    }
    usort($ev, 'tlCmpEventos');
    return $ev;
}
// Para um conjunto de lotes (strings), retorna:
//   array(loteNormalizado => array('producao'=>[], 'estante'=>[]))
// "producao" vem de ciPostosCsv (quem produziu, quanto, quando).
// "estante" vem de conferencia_pacotes (entrada no estoque interno).
// Para CADA lote, varremos as variacoes (zero-pad 8 + sem zeros + numerico)
// para nao perder casos onde o tipo da coluna difere entre tabelas.
function buscar_info_lotes($pdo, $lotes) {
    $out = array();
    if (empty($lotes)) return $out;
    // Normaliza chaves: usa LPAD-8 como chave canonica.
    $variacoes = array(); // lpad8 => array('lpad8','sem_zeros','int')
    foreach ($lotes as $l) {
        $d = preg_replace('/\D+/', '', (string)$l);
        if ($d === '') continue;
        $p = str_pad($d, 8, '0', STR_PAD_LEFT);
        $variacoes[$p] = array(
            'lpad'   => $p,
            'limpo'  => ltrim($d, '0') !== '' ? ltrim($d, '0') : '0',
            'int'    => (int)$d,
            'bruto'  => $d,
        );
        $out[$p] = array('producao' => array(), 'estante' => array());
    }
    if (empty($variacoes)) return $out;

    // Monta lista plana de todas variacoes para usar em IN(...)
    $todas = array();
    foreach ($variacoes as $v) {
        $todas[$v['lpad']]  = true;
        $todas[$v['limpo']] = true;
        $todas[(string)$v['int']] = true;
    }
    $todasArr = array_keys($todas);
    $ph = rtrim(str_repeat('?,', count($todasArr)), ',');

    // --- 1) Producao em ciPostosCsv ----------------------------------------
    $colsPC = colunas_tabela($pdo, 'ciPostosCsv');
    if (!empty($colsPC) && isset($colsPC['lote'])) {
        // Detecta nomes reais das colunas opcionais
        $cQtd   = isset($colsPC['quantidade']) ? 'quantidade' : null;
        $cUsr   = isset($colsPC['usuario'])    ? 'usuario'    : null;
        $cPosto = isset($colsPC['posto'])      ? 'posto'      : null;
        $cReg   = isset($colsPC['regional'])   ? 'regional'   : null;
        $cData  = null;
        foreach (array('dataCarga','data_carga','data','criado_at','criado_em') as $cc) {
            if (isset($colsPC[strtolower($cc)])) { $cData = $colsPC[strtolower($cc)]; break; }
        }
        $sel = array("lote AS _lote");
        if ($cQtd)   $sel[] = "`$cQtd` AS qtd";
        if ($cUsr)   $sel[] = "`$cUsr` AS usuario";
        if ($cPosto) $sel[] = "`$cPosto` AS posto";
        if ($cReg)   $sel[] = "`$cReg` AS regional";
        if ($cData)  $sel[] = "`$cData` AS dt";
        $selStr = implode(',', $sel);
        try {
            $st = $pdo->prepare(
                "SELECT $selStr FROM ciPostosCsv
                  WHERE lote IN ($ph) OR CAST(lote AS UNSIGNED) IN ($ph)
                  ORDER BY " . ($cData ? "`$cData` DESC" : "lote DESC") . "
                  LIMIT 50"
            );
            $st->execute(array_merge($todasArr, $todasArr));
            while ($r = $st->fetch()) {
                $key = str_pad(preg_replace('/\D+/', '', (string)$r['_lote']), 8, '0', STR_PAD_LEFT);
                if (!isset($out[$key])) $out[$key] = array('producao' => array(), 'estante' => array());
                // v2.3.3: regional canonica (ciPostosCsv pode trazer a regional do codigo de barras, ex.: 501 em vez de 527)
                if (isset($r['posto'])) { $rc = regionalCanonica($pdo, $r['posto']); if ($rc !== '') $r['regional'] = $rc; }
                $out[$key]['producao'][] = $r;
            }
        } catch (Exception $e) { /* segue */ }
    }

    // --- 2) Estante em conferencia_pacotes ---------------------------------
    $colsCP = colunas_tabela($pdo, 'conferencia_pacotes');
    if (!empty($colsCP) && isset($colsCP['nlote'])) {
        $cConf  = isset($colsCP['conf'])    ? 'conf'    : null;
        $cUsr   = isset($colsCP['usuario']) ? 'usuario' : null;
        $cPosto = isset($colsCP['nposto'])  ? 'nposto'  : (isset($colsCP['posto'])?'posto':null);
        $cDt    = null;
        foreach (array('conferido_em','lido_em','data_conf','data','criado_em','criado_at') as $cc) {
            if (isset($colsCP[strtolower($cc)])) { $cDt = $colsCP[strtolower($cc)]; break; }
        }
        $sel = array("nlote AS _lote");
        if ($cConf)  $sel[] = "`$cConf` AS conf";
        if ($cUsr)   $sel[] = "`$cUsr` AS usuario";
        if ($cPosto) $sel[] = "`$cPosto` AS posto";
        if ($cDt)    $sel[] = "`$cDt` AS dt";
        $selStr = implode(',', $sel);
        try {
            $st = $pdo->prepare(
                "SELECT $selStr FROM conferencia_pacotes
                  WHERE nlote IN ($ph) OR CAST(nlote AS UNSIGNED) IN ($ph)
                  ORDER BY " . ($cDt ? "`$cDt` DESC" : "nlote DESC") . "
                  LIMIT 50"
            );
            $st->execute(array_merge($todasArr, $todasArr));
            while ($r = $st->fetch()) {
                $key = str_pad(preg_replace('/\D+/', '', (string)$r['_lote']), 8, '0', STR_PAD_LEFT);
                if (!isset($out[$key])) $out[$key] = array('producao' => array(), 'estante' => array());
                $out[$key]['estante'][] = $r;
            }
        } catch (Exception $e) { /* segue */ }
    }

    // --- 3) Triado para a estante em lotes_na_estante ----------------------
    // Espelha a rastreabilidade.php: um lote pode estar "na estante" (triado)
    // em lotes_na_estante mesmo sem ainda ter conf='s' em conferencia_pacotes.
    $colsLE = colunas_tabela($pdo, 'lotes_na_estante');
    if (!empty($colsLE) && isset($colsLE['lote'])) {
        $cPosto = isset($colsLE['posto'])      ? 'posto'      : null;
        $cDt    = isset($colsLE['triado_em'])  ? 'triado_em'  : null;
        $cUsr   = isset($colsLE['triado_por']) ? 'triado_por' : null;
        $sel = array('lote AS _lote');
        if ($cPosto) $sel[] = "`$cPosto` AS posto";
        if ($cDt)    $sel[] = "`$cDt` AS dt";
        if ($cUsr)   $sel[] = "`$cUsr` AS usuario";
        $selStr = implode(',', $sel);
        try {
            $st = $pdo->prepare(
                "SELECT $selStr FROM lotes_na_estante
                  WHERE lote IN ($ph) OR CAST(lote AS UNSIGNED) IN ($ph)
                  ORDER BY " . ($cDt ? "`$cDt` DESC" : "lote DESC") . "
                  LIMIT 50"
            );
            $st->execute(array_merge($todasArr, $todasArr));
            while ($r = $st->fetch()) {
                $key = str_pad(preg_replace('/\D+/', '', (string)$r['_lote']), 8, '0', STR_PAD_LEFT);
                if (!isset($out[$key])) $out[$key] = array('producao' => array(), 'estante' => array(), 'triado' => array());
                if (!isset($out[$key]['triado'])) $out[$key]['triado'] = array();
                $out[$key]['triado'][] = $r;
            }
        } catch (Exception $e) { /* segue */ }
    }

    // --- 4) Oficio / despacho em ciDespachoLotes ---------------------------
    // Um lote que ja entrou num oficio passou das fases de estante/conferencia.
    // Guarda id_despacho + data_despacho_correios + despachado_por p/ o ciclo
    // de vida (fase "no malote" e "despachado").
    $colsDL = colunas_tabela($pdo, 'ciDespachoLotes');
    $despachoIds = array(); // id_despacho (sem despacho gravado) => true
    if (!empty($colsDL) && isset($colsDL['lote']) && isset($colsDL['id_despacho'])) {
        $cDespDt  = isset($colsDL['data_despacho_correios']) ? 'data_despacho_correios' : null;
        $cDespPor = isset($colsDL['despachado_por'])         ? 'despachado_por'         : null;
        $cDataC   = isset($colsDL['data_carga'])             ? 'data_carga'             : null;
        $sel = array('lote AS _lote', 'id_despacho AS id_despacho');
        if (isset($colsDL['posto'])) $sel[] = "LPAD(CAST(posto AS CHAR),3,'0') AS posto";
        if ($cDespDt)  $sel[] = "`$cDespDt` AS data_despacho_correios";
        if ($cDespPor) $sel[] = "`$cDespPor` AS despachado_por";
        if ($cDataC)   $sel[] = "`$cDataC` AS data_carga";
        if (isset($colsDL['etiqueta_correios'])) $sel[] = "etiqueta_correios AS etiqueta_correios";
        if (isset($colsDL['etiquetaiipr']))      $sel[] = "etiquetaiipr AS etiquetaiipr";
        if (isset($colsDL['etiquetacorreios']))  $sel[] = "etiquetacorreios AS etiquetacorreios";
        $selStr = implode(',', $sel);
        try {
            $st = $pdo->prepare(
                "SELECT $selStr FROM ciDespachoLotes
                  WHERE lote IN ($ph) OR CAST(lote AS UNSIGNED) IN ($ph)
                  LIMIT 200"
            );
            $st->execute(array_merge($todasArr, $todasArr));
            while ($r = $st->fetch()) {
                $key = str_pad(preg_replace('/\D+/', '', (string)$r['_lote']), 8, '0', STR_PAD_LEFT);
                if (!isset($out[$key])) $out[$key] = array('producao'=>array(),'estante'=>array(),'triado'=>array());
                if (!isset($out[$key]['oficio'])) $out[$key]['oficio'] = array();
                $out[$key]['oficio'][] = $r;
                if (isset($r['etiqueta_correios']) && trim((string)$r['etiqueta_correios']) !== '') {
                    if (!isset($out[$key]['etiquetas'])) $out[$key]['etiquetas'] = array();
                    $out[$key]['etiquetas'][preg_replace('/\s+/', '', (string)$r['etiqueta_correios'])] = true;
                }
                $idd = (int)$r['id_despacho'];
                $temDesp = isset($r['data_despacho_correios']) && trim((string)$r['data_despacho_correios']) !== ''
                           && strpos((string)$r['data_despacho_correios'], '0000-00-00') !== 0;
                if ($idd > 0 && !$temDesp) $despachoIds[$idd] = true;
            }
        } catch (Exception $e) { /* segue */ }
    }

    // --- 5) Despacho inferido (recebimento de displays) -------------------
    // Para oficios SEM data_despacho_correios gravada, espelha a regra da
    // rastreabilidade.php: QUALQUER recebimento de display (ciMalotes tipo=2)
    // a partir da ancora do oficio sinaliza despacho provavel. A ancora segue
    // a prioridade criado_em -> menor data_carga dos lotes -> 1a data de datas_str.
    if (!empty($despachoIds)) {
        $idsArr = array_keys($despachoIds);
        $phD = rtrim(str_repeat('?,', count($idsArr)), ',');
        $cabDesp = array(); // id => array(criado_em, datas_str)
        try {
            $stc = $pdo->prepare("SELECT id, COALESCE(criado_em,'') AS criado_em, COALESCE(datas_str,'') AS datas_str FROM ciDespachos WHERE id IN ($phD)");
            $stc->execute($idsArr);
            while ($r = $stc->fetch()) $cabDesp[(int)$r['id']] = $r;
        } catch (Exception $e) { /* segue sem cabecalho */ }
        // menor data_carga por oficio, a partir dos lotes ja carregados em $out
        $minCargaPorDesp = array();
        foreach ($out as $k => $info) {
            if (empty($info['oficio'])) continue;
            foreach ($info['oficio'] as $o) {
                $idd = (int)$o['id_despacho'];
                if (!isset($despachoIds[$idd])) continue;
                $dc = isset($o['data_carga']) ? trim((string)$o['data_carga']) : '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dc) && strpos($dc, '0000-00-00') !== 0) {
                    $dia = substr($dc, 0, 10);
                    if (!isset($minCargaPorDesp[$idd]) || $dia < $minCargaPorDesp[$idd]) $minCargaPorDesp[$idd] = $dia;
                }
            }
        }
        // 1o recebimento tipo=2 >= ancora, por oficio
        $colsM = colunas_tabela($pdo, 'ciMalotes');
        $temMalotes = (!empty($colsM) && isset($colsM['tipo']) && isset($colsM['data']));
        $despachoData = array(); // id_despacho => data do recebimento
        if ($temMalotes) {
            foreach ($idsArr as $idd) {
                $criado = isset($cabDesp[$idd]) ? $cabDesp[$idd]['criado_em'] : '';
                $datas  = isset($cabDesp[$idd]) ? $cabDesp[$idd]['datas_str'] : '';
                $minc   = isset($minCargaPorDesp[$idd]) ? $minCargaPorDesp[$idd] : '';
                $anc = ancora_despacho_mobile($criado, $minc, $datas);
                if ($anc === '') continue;
                try {
                    $stm = $pdo->prepare("SELECT MIN(data) AS quando FROM ciMalotes WHERE tipo=2 AND data >= ?");
                    $stm->execute(array($anc . ' 00:00:00'));
                    $rm = $stm->fetch();
                    if ($rm && !empty($rm['quando'])) $despachoData[$idd] = $rm['quando'];
                } catch (Exception $e) { /* segue */ }
            }
        }
        if (!empty($despachoData)) {
            foreach ($out as $k => $info) {
                if (empty($info['oficio'])) continue;
                foreach ($info['oficio'] as $o) {
                    $idd = (int)$o['id_despacho'];
                    if (isset($despachoData[$idd])) { $out[$k]['despacho_inferido'] = $despachoData[$idd]; break; }
                }
            }
        }
    }

    // --- 6) v2.3.3: etiquetas extras (conferencia_pacotes_lacres) + movimentos
    //        de display (ciMalotes) por lote, p/ a linha do tempo unificada. ---
    $colsCPL = colunas_tabela($pdo, 'conferencia_pacotes_lacres');
    if (!empty($colsCPL) && isset($colsCPL['etiqueta_correios'])) {
        $cLcpl = isset($colsCPL['lote']) ? 'lote' : (isset($colsCPL['nlote']) ? 'nlote' : null);
        if ($cLcpl) {
            try {
                $st = $pdo->prepare(
                    "SELECT `$cLcpl` AS _lote, etiqueta_correios AS etq FROM conferencia_pacotes_lacres
                      WHERE `$cLcpl` IN ($ph) OR CAST(`$cLcpl` AS UNSIGNED) IN ($ph)"
                );
                $st->execute(array_merge($todasArr, $todasArr));
                while ($r = $st->fetch()) {
                    if (trim((string)$r['etq']) === '') continue;
                    $key = str_pad(preg_replace('/\D+/', '', (string)$r['_lote']), 8, '0', STR_PAD_LEFT);
                    if (!isset($out[$key])) continue;
                    if (!isset($out[$key]['etiquetas'])) $out[$key]['etiquetas'] = array();
                    $out[$key]['etiquetas'][preg_replace('/\s+/', '', (string)$r['etq'])] = true;
                }
            } catch (Exception $e) { /* segue */ }
        }
    }
    $todasEtq = array();
    foreach ($out as $k => $info) {
        if (empty($info['etiquetas'])) continue;
        foreach ($info['etiquetas'] as $etq => $_) $todasEtq[$etq] = true;
    }
    if (!empty($todasEtq)) {
        $colsM = colunas_tabela($pdo, 'ciMalotes');
        if (!empty($colsM) && isset($colsM['leitura'])) {
            $etis = array_keys($todasEtq);
            $phE = rtrim(str_repeat('?,', count($etis)), ',');
            $movPorEtq = array();
            try {
                $stm = $pdo->prepare(
                    "SELECT leitura, data, login, tipo, posto FROM ciMalotes
                      WHERE leitura IN ($phE) ORDER BY data ASC, id ASC"
                );
                $stm->execute($etis);
                while ($r = $stm->fetch()) {
                    $movPorEtq[(string)$r['leitura']][] = $r;
                }
            } catch (Exception $e) { /* segue */ }
            if (!empty($movPorEtq)) {
                foreach ($out as $k => $info) {
                    if (empty($info['etiquetas'])) continue;
                    foreach ($info['etiquetas'] as $etq => $_) {
                        if (!isset($movPorEtq[$etq])) continue;
                        if (!isset($out[$k]['movimentos'])) $out[$k]['movimentos'] = array();
                        foreach ($movPorEtq[$etq] as $mv) $out[$k]['movimentos'][] = $mv;
                    }
                }
            }
        }
    }

    // --- 7) v2.3.3: adiantamento (ciDespachoAdiantado) p/ paridade com a
    //        auditoria_lote (FASE 6). Lote despachado/produzido adiantado. ---
    $colsAD = colunas_tabela($pdo, 'ciDespachoAdiantado');
    if (!empty($colsAD)) {
        $cLad = isset($colsAD['lote']) ? 'lote' : (isset($colsAD['nlote']) ? 'nlote' : null);
        if ($cLad) {
            $cDtAd = null;
            foreach (array('data_despacho','data_producao','data','criado_em') as $cc) {
                if (isset($colsAD[$cc])) { $cDtAd = $cc; break; }
            }
            $cPsAd  = isset($colsAD['posto']) ? 'posto' : (isset($colsAD['nposto']) ? 'nposto' : null);
            $cObsAd = isset($colsAD['observacao']) ? 'observacao' : (isset($colsAD['obs']) ? 'obs' : null);
            $cNumAd = isset($colsAD['numero_oficio']) ? 'numero_oficio' : (isset($colsAD['oficio']) ? 'oficio' : null);
            $selAd = array("`$cLad` AS _lote");
            if ($cDtAd)  $selAd[] = "`$cDtAd` AS dt";
            if ($cPsAd)  $selAd[] = "`$cPsAd` AS posto";
            if ($cObsAd) $selAd[] = "`$cObsAd` AS obs";
            if ($cNumAd) $selAd[] = "`$cNumAd` AS num";
            try {
                $st = $pdo->prepare(
                    "SELECT " . implode(', ', $selAd) . " FROM ciDespachoAdiantado
                      WHERE `$cLad` IN ($ph) OR CAST(`$cLad` AS UNSIGNED) IN ($ph)"
                );
                $st->execute(array_merge($todasArr, $todasArr));
                while ($r = $st->fetch()) {
                    $key = str_pad(preg_replace('/\D+/', '', (string)$r['_lote']), 8, '0', STR_PAD_LEFT);
                    if (!isset($out[$key])) continue;
                    if (!isset($out[$key]['adiantado'])) $out[$key]['adiantado'] = array();
                    $out[$key]['adiantado'][] = $r;
                }
            } catch (Exception $e) { /* segue */ }
        }
    }

    return $out;
}
// Resolve a ancora (dia YYYY-MM-DD) para inferir o despacho de um oficio.
// Prioridade: criado_em valido -> menor data_carga dos lotes -> 1a data em datas_str.
// Aceita datas YYYY-MM-DD e DD-MM-YYYY / DD/MM/YYYY no datas_str.
function ancora_despacho_mobile($criadoEm, $minDataCarga, $datasStr) {
    $base = trim((string)$criadoEm);
    if ($base !== '' && strpos($base, '0000-00-00') !== 0 && preg_match('/^\d{4}-\d{2}-\d{2}/', $base)) {
        return substr($base, 0, 10);
    }
    $mc = trim((string)$minDataCarga);
    if ($mc !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $mc)) return substr($mc, 0, 10);
    $s = trim((string)$datasStr);
    if ($s !== '') {
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $s, $m)) return $m[1] . '-' . $m[2] . '-' . $m[3];
        if (preg_match('#(\d{2})[/-](\d{2})[/-](\d{4})#', $s, $m)) return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    return '';
}
// Decide o status do lote escolhendo a fase MAIS AVANCADA do ciclo de vida.
//   "despachado"  -> oficio com data_despacho_correios OU despacho inferido
//   "malote"      -> lote ja entrou num oficio (ciDespachoLotes), aguardando despacho
//   "conferido"   -> conferencia_pacotes com conf='s' (aguardando geracao do oficio)
//   "estante"     -> triado p/ a estante (lotes_na_estante)
//   "expedido"    -> ha registro em conferencia_pacotes mas sem conf='s'
//   "produzido"   -> existe em ciPostosCsv (no carrinho / com quem expediu)
//   "desconhecido"-> nada encontrado
function status_lote($info) {
    $oficio  = !empty($info['oficio'])   ? $info['oficio']   : array();
    $estante = !empty($info['estante'])  ? $info['estante']  : array();
    $triado  = !empty($info['triado'])   ? $info['triado']   : array();
    $prod    = !empty($info['producao']) ? $info['producao'] : array();
    // 5) despachado
    foreach ($oficio as $o) {
        $d = isset($o['data_despacho_correios']) ? trim((string)$o['data_despacho_correios']) : '';
        if ($d !== '' && strpos($d, '0000-00-00') !== 0) return 'despachado';
    }
    if (!empty($info['despacho_inferido'])) return 'despachado';
    // 4) fechado no malote (em oficio, sem despacho)
    if (!empty($oficio)) return 'malote';
    // 3) conferido (conf='s')
    foreach ($estante as $e) {
        $c = isset($e['conf']) ? strtolower(trim((string)$e['conf'])) : '';
        if ($c === 's' || $c === 'sim' || $c === '1' || $c === 'y') return 'conferido';
    }
    // 2) triado p/ a estante
    if (!empty($triado))  return 'estante';
    // intermediario: lido mas nao conferido
    if (!empty($estante)) return 'expedido';
    // 1) no carrinho / com quem expediu
    if (!empty($prod))    return 'produzido';
    return 'desconhecido';
}

// ---- Parametros ---------------------------------------------------------
$termo     = isset($_GET['q']) ? trim((string)$_GET['q']) : (isset($_POST['q']) ? trim((string)$_POST['q']) : '');
$tipo      = isset($_GET['tipo']) ? trim((string)$_GET['tipo']) : (isset($_POST['tipo']) ? trim((string)$_POST['tipo']) : 'auto');
$dataIni   = isset($_GET['di']) ? trim((string)$_GET['di']) : (isset($_POST['di']) ? trim((string)$_POST['di']) : '');
$dataFim   = isset($_GET['df']) ? trim((string)$_GET['df']) : (isset($_POST['df']) ? trim((string)$_POST['df']) : '');

// Normaliza datas (aceita dd/mm/yyyy ou yyyy-mm-dd)
function normaliza_data($d) {
    if (!$d) return '';
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $d, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
    return '';
}
$dataIniN = normaliza_data($dataIni);
$dataFimN = normaliza_data($dataFim);

// ---- Auto-deteccao ------------------------------------------------------
// Regras (quando tipo=auto):
//  - 35 digitos             -> etiqueta_correios
//  - 17 a 25 digitos        -> lote (ciDespachoLotes.lote tipicamente 8-20 dig)
//  - 1 a 4 digitos          -> posto
//  - dd/mm/yyyy             -> data
//  - alfanumerico curto     -> lacre (IIPR ou Correios)
function detectar_tipo($termo) {
    $t = trim((string)$termo);
    if ($t === '') return '';
    if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $t) || preg_match('#^\d{4}-\d{2}-\d{2}$#', $t)) return 'data';
    $dig = so_digitos($t);
    if ($dig === $t || $dig === ltrim($t, '0')) {
        // termo eh so digitos
        $len = strlen($dig);
        if ($len === 35) return 'etiqueta';
        if ($len >= 6 && $len <= 30) return 'lote';
        if ($len >= 1 && $len <= 5)  return 'posto';
    }
    return 'lacre';
}
$tipoEfetivo = ($tipo === 'auto' || $tipo === '') ? detectar_tipo($termo) : $tipo;

// ---- Busca --------------------------------------------------------------
$erroBusca = '';
$resultados = array(); // lista de "matches" -> cada um vira um card de oficio
$totaisGerais = array('oficios' => 0, 'lotes' => 0, 'carteiras' => 0, 'postos' => 0);

if ($termo !== '' || $dataIniN !== '' || $dataFimN !== '') {
    try {
        $pdo = getDbPdo();

        // 1) Descobrir os id_despacho que casam com o filtro.
        $idDespachos = array();
        $postosFiltro = array();   // restrita a este(s) posto(s) para resultados
        $lotesFiltro = array();    // restrita a este(s) lote(s)

        if ($tipoEfetivo === 'etiqueta') {
            $eti = so_digitos($termo);
            // Procura em 3 lugares: ciDespachoLotes.etiqueta_correios,
            // ciDespachoItens.etiqueta_correios e ciMalotes.leitura.
            $sql = "SELECT DISTINCT id_despacho FROM ciDespachoLotes WHERE etiqueta_correios = ?
                    UNION
                    SELECT DISTINCT id_despacho FROM ciDespachoItens WHERE etiqueta_correios = ?";
            $st = $pdo->prepare($sql);
            $st->execute(array($eti, $eti));
            while ($r = $st->fetch()) { $idDespachos[(int)$r['id_despacho']] = true; }

            // Se nada nas tabelas de despacho, tenta achar o posto via ciMalotes
            // e depois localizar despachos desse posto (apenas se houver data filtrada).
            $stM = $pdo->prepare("SELECT posto FROM ciMalotes WHERE leitura = ? ORDER BY id DESC LIMIT 1");
            $stM->execute(array($eti));
            $linhaM = $stM->fetch();
            if ($linhaM && !empty($linhaM['posto'])) {
                $postosFiltro[normaliza_posto($linhaM['posto'])] = true;
            }
        } elseif ($tipoEfetivo === 'lote') {
            // ciDespachoLotes.lote = VARCHAR(8) com zeros a esquerda (str_pad).
            // Aceita digitacao com ou sem zeros: padroniza para 8 digitos e
            // tambem usa comparacao numerica como fallback.
            $loteDig = so_digitos($termo);
            $lotePad = str_pad($loteDig, 8, '0', STR_PAD_LEFT);
            // Marca os dois formatos no filtro para nao perder lotes que casam.
            $lotesFiltro[$loteDig] = true;
            $lotesFiltro[$lotePad] = true;
            $sql = "SELECT DISTINCT id_despacho FROM ciDespachoLotes
                     WHERE lote = ? OR lote = ? OR CAST(lote AS UNSIGNED) = ?
                    UNION
                    SELECT DISTINCT id_despacho FROM ciDespachoItens
                     WHERE lote = ? OR lote = ? OR CAST(lote AS UNSIGNED) = ?";
            $st = $pdo->prepare($sql);
            $st->execute(array($loteDig, $lotePad, (int)$loteDig, $loteDig, $lotePad, (int)$loteDig));
            while ($r = $st->fetch()) { $idDespachos[(int)$r['id_despacho']] = true; }
        } elseif ($tipoEfetivo === 'posto') {
            $posto = normaliza_posto($termo);
            $postosFiltro[$posto] = true;
            $sql = "SELECT DISTINCT id_despacho FROM ciDespachoLotes WHERE LPAD(posto,3,'0') = ?
                    UNION
                    SELECT DISTINCT id_despacho FROM ciDespachoItens WHERE LPAD(posto,3,'0') = ?";
            $st = $pdo->prepare($sql);
            $st->execute(array($posto, $posto));
            while ($r = $st->fetch()) { $idDespachos[(int)$r['id_despacho']] = true; }
        } elseif ($tipoEfetivo === 'lacre') {
            // Procura em lacre_iipr e lacre_correios de ciDespachoItens.
            $sql = "SELECT DISTINCT id_despacho, posto FROM ciDespachoItens
                     WHERE lacre_iipr = ? OR lacre_correios = ?";
            $st = $pdo->prepare($sql);
            $st->execute(array($termo, $termo));
            while ($r = $st->fetch()) {
                $idDespachos[(int)$r['id_despacho']] = true;
                $postosFiltro[normaliza_posto($r['posto'])] = true;
            }
        } elseif ($tipoEfetivo === 'oficio') {
            // Busca direta pelo numero do oficio (ciDespachos.id).
            $idOf = (int)so_digitos($termo);
            if ($idOf > 0) { $idDespachos[$idOf] = true; }
        } elseif ($tipoEfetivo === 'data') {
            // Filtro so por data: usa data_carga em ciDespachoLotes.
            $dt = normaliza_data($termo);
            if ($dt === '') $dt = $dataIniN;
            if ($dt !== '') {
                $sql = "SELECT DISTINCT id_despacho FROM ciDespachoLotes WHERE DATE(data_carga) = ?";
                $st = $pdo->prepare($sql);
                $st->execute(array($dt));
                while ($r = $st->fetch()) { $idDespachos[(int)$r['id_despacho']] = true; }
                if ($dataIniN === '') $dataIniN = $dt;
                if ($dataFimN === '') $dataFimN = $dt;
            }
        }

        // Aplica filtro por intervalo de data (se informado) — afina os despachos encontrados.
        if (($dataIniN !== '' || $dataFimN !== '') && !empty($idDespachos)) {
            $ids = array_keys($idDespachos);
            $ph = rtrim(str_repeat('?,', count($ids)), ',');
            $params = $ids;
            $whereData = '';
            if ($dataIniN !== '' && $dataFimN !== '') {
                $whereData = "AND DATE(l.data_carga) BETWEEN ? AND ?";
                $params[] = $dataIniN; $params[] = $dataFimN;
            } elseif ($dataIniN !== '') {
                $whereData = "AND DATE(l.data_carga) >= ?";
                $params[] = $dataIniN;
            } elseif ($dataFimN !== '') {
                $whereData = "AND DATE(l.data_carga) <= ?";
                $params[] = $dataFimN;
            }
            $sql = "SELECT DISTINCT l.id_despacho FROM ciDespachoLotes l
                     WHERE l.id_despacho IN ($ph) $whereData";
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $novos = array();
            while ($r = $st->fetch()) { $novos[(int)$r['id_despacho']] = true; }
            $idDespachos = $novos;
        } elseif (empty($idDespachos) && ($dataIniN !== '' || $dataFimN !== '') && $termo === '') {
            // Sem termo, apenas data -> lista despachos do periodo.
            $params = array();
            $whereData = '';
            if ($dataIniN !== '' && $dataFimN !== '') {
                $whereData = "DATE(data_carga) BETWEEN ? AND ?";
                $params[] = $dataIniN; $params[] = $dataFimN;
            } elseif ($dataIniN !== '') {
                $whereData = "DATE(data_carga) >= ?";
                $params[] = $dataIniN;
            } elseif ($dataFimN !== '') {
                $whereData = "DATE(data_carga) <= ?";
                $params[] = $dataFimN;
            }
            $sql = "SELECT DISTINCT id_despacho FROM ciDespachoLotes WHERE $whereData LIMIT 200";
            $st = $pdo->prepare($sql);
            $st->execute($params);
            while ($r = $st->fetch()) { $idDespachos[(int)$r['id_despacho']] = true; }
        }

        if (!empty($idDespachos)) {
            $ids = array_keys($idDespachos);
            $ph  = rtrim(str_repeat('?,', count($ids)), ',');

            // 2) Cabecalho dos oficios
            $sqlCab = "SELECT id, grupo, datas_str, usuario, criado_em, criado_at
                         FROM ciDespachos
                        WHERE id IN ($ph)
                        ORDER BY id DESC";
            $stCab = $pdo->prepare($sqlCab);
            $stCab->execute($ids);
            $cabPorId = array();
            while ($r = $stCab->fetch()) {
                $cabPorId[(int)$r['id']] = $r;
            }

            // 3) Itens (lacres + displays) por posto
            $sqlIt = "SELECT i.id_despacho, LPAD(CAST(i.posto AS CHAR),3,'0') AS posto,
                             i.lacre_iipr, i.lacre_correios, i.etiqueta_correios,
                             i.nome_posto, i.endereco, i.quantidade, i.usuario
                        FROM ciDespachoItens i
                       WHERE i.id_despacho IN ($ph)
                       ORDER BY i.id_despacho DESC, LPAD(i.posto,3,'0') ASC";
            $stIt = $pdo->prepare($sqlIt);
            $stIt->execute($ids);
            $itensPorDespacho = array();
            while ($r = $stIt->fetch()) {
                $id = (int)$r['id_despacho'];
                if (!isset($itensPorDespacho[$id])) $itensPorDespacho[$id] = array();
                $postoOk = empty($postosFiltro) || isset($postosFiltro[$r['posto']]);
                if (!$postoOk) continue;
                $itensPorDespacho[$id][$r['posto']] = $r;
            }

            // 4) Lotes — inclui colunas opcionais de Correios se existirem.
            //    Para grupo CORREIOS os lacres ficam aqui (etiquetaiipr=lacre IIPR,
            //    etiquetacorreios=lacre Correios), nao em ciDespachoItens.
            $colsLotes = array();
            try {
                $stColsL = $pdo->query("SHOW COLUMNS FROM ciDespachoLotes");
                while ($cl = $stColsL->fetch()) {
                    $colsLotes[strtolower($cl['Field'])] = true;
                }
            } catch (Exception $eCols) { /* ignora */ }
            $selEtIIPR = isset($colsLotes['etiquetaiipr'])      ? "l.etiquetaiipr      AS etiquetaiipr"      : "'' AS etiquetaiipr";
            $selEtCor  = isset($colsLotes['etiquetacorreios'])  ? "l.etiquetacorreios  AS etiquetacorreios"  : "'' AS etiquetacorreios";
            $selGIIPR  = isset($colsLotes['grupo_iipr'])        ? "l.grupo_iipr        AS grupo_iipr"        : "'' AS grupo_iipr";
            $selGCor   = isset($colsLotes['grupo_correios'])    ? "l.grupo_correios    AS grupo_correios"    : "'' AS grupo_correios";

            $sqlLt = "SELECT l.id_despacho, LPAD(CAST(l.posto AS CHAR),3,'0') AS posto, l.lote,
                             l.quantidade, l.data_carga, l.responsaveis,
                             l.etiqueta_correios,
                             $selEtIIPR, $selEtCor, $selGIIPR, $selGCor
                        FROM ciDespachoLotes l
                       WHERE l.id_despacho IN ($ph)
                       ORDER BY l.id_despacho DESC, LPAD(l.posto,3,'0') ASC, l.lote ASC";
            $stLt = $pdo->prepare($sqlLt);
            $stLt->execute($ids);
            $lotesPorDespachoPosto = array();
            while ($r = $stLt->fetch()) {
                $id = (int)$r['id_despacho'];
                $postoOk = empty($postosFiltro) || isset($postosFiltro[$r['posto']]);
                $loteOk  = empty($lotesFiltro)  || isset($lotesFiltro[(string)$r['lote']]);
                if (!$postoOk || !$loteOk) continue;
                if (!isset($lotesPorDespachoPosto[$id])) $lotesPorDespachoPosto[$id] = array();
                if (!isset($lotesPorDespachoPosto[$id][$r['posto']])) $lotesPorDespachoPosto[$id][$r['posto']] = array();
                $lotesPorDespachoPosto[$id][$r['posto']][] = $r;
            }

            // 5) Movimentos em ciMalotes para as etiquetas Correios encontradas
            $etiquetasParaBuscar = array();
            foreach ($itensPorDespacho as $idD => $postos) {
                foreach ($postos as $p => $it) {
                    if (!empty($it['etiqueta_correios'])) {
                        $etiquetasParaBuscar[(string)$it['etiqueta_correios']] = true;
                    }
                }
            }
            foreach ($lotesPorDespachoPosto as $idD => $postos) {
                foreach ($postos as $p => $lotes) {
                    foreach ($lotes as $lt) {
                        if (!empty($lt['etiqueta_correios'])) {
                            $etiquetasParaBuscar[(string)$lt['etiqueta_correios']] = true;
                        }
                    }
                }
            }
            // Se a busca foi por etiqueta, sempre inclui o termo na lista
            if ($tipoEfetivo === 'etiqueta') {
                $etiquetasParaBuscar[so_digitos($termo)] = true;
            }
            $movimentosPorEtiqueta = array();
            if (!empty($etiquetasParaBuscar)) {
                $etis = array_keys($etiquetasParaBuscar);
                $phE = rtrim(str_repeat('?,', count($etis)), ',');
                $sqlMov = "SELECT leitura, data, login, tipo, posto
                             FROM ciMalotes
                            WHERE leitura IN ($phE)
                            ORDER BY data DESC, id DESC";
                $stMov = $pdo->prepare($sqlMov);
                $stMov->execute($etis);
                while ($r = $stMov->fetch()) {
                    $movimentosPorEtiqueta[(string)$r['leitura']][] = $r;
                }
            }

            // 6) Monta resultados
            foreach ($cabPorId as $idD => $cab) {
                $postosDoOficio = isset($itensPorDespacho[$idD]) ? $itensPorDespacho[$idD] : array();
                $lotesDoOficio  = isset($lotesPorDespachoPosto[$idD]) ? $lotesPorDespachoPosto[$idD] : array();

                // Une as chaves de postos (pode ter posto so em lotes sem item)
                $todosPostos = array();
                foreach ($postosDoOficio as $k => $v) { $todosPostos[$k] = true; }
                foreach ($lotesDoOficio as $k => $v)  { $todosPostos[$k] = true; }
                ksort($todosPostos);

                $postosFinal = array();
                $somaCart = 0; $somaLotes = 0;
                foreach ($todosPostos as $codPosto => $_) {
                    $it = isset($postosDoOficio[$codPosto]) ? $postosDoOficio[$codPosto] : null;
                    $ls = isset($lotesDoOficio[$codPosto]) ? $lotesDoOficio[$codPosto] : array();
                    $somaLotes += count($ls);
                    $cartPosto = 0;
                    foreach ($ls as $lr) { $cartPosto += (int)$lr['quantidade']; }
                    if ($cartPosto === 0 && $it) { $cartPosto = (int)$it['quantidade']; }
                    $somaCart += $cartPosto;
                    $postosFinal[] = array(
                        'codigo'  => $codPosto,
                        'item'    => $it,
                        'lotes'   => $ls,
                        'cartPosto' => $cartPosto,
                    );
                }
                if (empty($postosFinal)) continue;

                $resultados[] = array(
                    'cab'          => $cab,
                    'postos'       => $postosFinal,
                    'totais'       => array(
                        'postos'    => count($postosFinal),
                        'lotes'     => $somaLotes,
                        'carteiras' => $somaCart,
                    ),
                );
                $totaisGerais['oficios']++;
                $totaisGerais['lotes']     += $somaLotes;
                $totaisGerais['carteiras'] += $somaCart;
                $totaisGerais['postos']    += count($postosFinal);
            }
        }
    } catch (Exception $ex) {
        $erroBusca = $ex->getMessage();
    }
}

// ---- Info extra de lotes (estante + producao) ---------------------------
// Coleta TODOS os lotes que aparecem nos resultados (para mostrar status
// "Na estante" / "Apenas expedido" em cada lote do oficio) + o termo se a
// busca foi por lote (para o caso "lote sem oficio").
$infoLotesExtra = array();  // chave LPAD-8 => array('producao'=>[], 'estante'=>[])
$loteBuscadoFallback = '';  // lote canonico (LPAD-8) buscado sem oficio
$lotePadInit = ''; // chave LPAD-8 do termo se busca foi por lote
if ($tipoEfetivo === 'lote' && $termo !== '') {
    $lotePadInit = str_pad(so_digitos($termo), 8, '0', STR_PAD_LEFT);
}
if (!empty($resultados) || $lotePadInit !== '') {
    try {
        if (!isset($pdo)) $pdo = getDbPdo();
        $coleta = array();
        if ($lotePadInit !== '') $coleta[$lotePadInit] = true;
        foreach ($resultados as $res) {
            foreach ($res['postos'] as $p) {
                foreach ($p['lotes'] as $lt) {
                    $k = str_pad(preg_replace('/\D+/', '', (string)$lt['lote']), 8, '0', STR_PAD_LEFT);
                    if ($k !== '00000000') $coleta[$k] = true;
                }
            }
        }
        if (!empty($coleta)) {
            $infoLotesExtra = buscar_info_lotes($pdo, array_keys($coleta));
        }
        // Se busca foi por lote e nada veio em oficios, marca para mostrar
        // card de fallback (mesmo que nao haja info de producao/estante).
        if ($lotePadInit !== '' && empty($resultados)) {
            $loteBuscadoFallback = $lotePadInit;
        }
    } catch (Exception $e) { /* mantem busca principal */ }
}

$temBusca = ($termo !== '' || $dataIniN !== '' || $dataFimN !== '');
$descTipo = array(
    'etiqueta' => 'Etiqueta Correios (35 dig.)',
    'lote'     => 'Lote',
    'posto'    => 'Posto',
    'oficio'   => 'Numero do Oficio',
    'lacre'    => 'Lacre / Display',
    'data'     => 'Data',
);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#1a4f7a">
<title>Busca de Producao</title>
<style>
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
html, body { margin: 0; padding: 0; }
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: #f0f2f5;
    color: #1f2937;
    font-size: 15px;
    line-height: 1.4;
}
.topbar {
    background: linear-gradient(135deg, #1a4f7a 0%, #2c6ea0 100%);
    color: #fff;
    padding: 14px 16px;
    position: sticky; top: 0; z-index: 50;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.topbar h1 {
    font-size: 17px; margin: 0; font-weight: 700; letter-spacing: 0.2px;
}
.topbar .sub { font-size: 12px; opacity: 0.85; margin-top: 2px; }
.container { padding: 12px; max-width: 720px; margin: 0 auto; }

form.busca {
    background: #fff; border-radius: 12px; padding: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 14px;
}
.campo { margin-bottom: 12px; }
.campo label {
    display: block; font-size: 12px; font-weight: 600;
    color: #4b5563; margin-bottom: 6px; text-transform: uppercase;
    letter-spacing: 0.5px;
}
.campo input[type=text], .campo input[type=search], .campo input[type=date], .campo select {
    width: 100%; padding: 13px 12px; border: 2px solid #d1d5db;
    border-radius: 10px; font-size: 16px; background: #fff;
    -webkit-appearance: none; appearance: none;
    color: #111827;
}
.campo input:focus, .campo select:focus { outline: none; border-color: #2c6ea0; box-shadow: 0 0 0 3px rgba(44,110,160,0.15); }
.linha-dupla { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.botoes { display: grid; grid-template-columns: 2fr 1fr; gap: 8px; margin-top: 4px; }
button {
    border: none; padding: 14px 16px; border-radius: 10px;
    font-size: 16px; font-weight: 700; cursor: pointer;
    -webkit-appearance: none;
}
button.primary { background: #1a4f7a; color: #fff; }
button.primary:active { background: #143d5e; }
button.secondary { background: #e5e7eb; color: #374151; }
button.secondary:active { background: #d1d5db; }

.hint {
    font-size: 12px; color: #6b7280; margin-top: 8px;
    background: #f9fafb; padding: 8px 10px; border-radius: 8px;
    border-left: 3px solid #2c6ea0;
}
.hint strong { color: #1a4f7a; }

.alerta { padding: 12px 14px; border-radius: 10px; margin-bottom: 12px; font-size: 14px; }
.alerta.erro { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.alerta.info { background: #dbeafe; color: #1e3a8a; border: 1px solid #93c5fd; }
.alerta.warn { background: #fef3c7; color: #78350f; border: 1px solid #fcd34d; }

.resumo {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;
    margin-bottom: 14px;
}
.resumo .box {
    background: #fff; border-radius: 10px; padding: 10px 6px;
    text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.resumo .num { font-size: 20px; font-weight: 800; color: #1a4f7a; }
.resumo .lbl { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }

.card-oficio {
    background: #fff; border-radius: 12px; margin-bottom: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;
}
.card-oficio .cab {
    background: linear-gradient(135deg, #1a4f7a 0%, #2c6ea0 100%);
    color: #fff; padding: 12px 14px;
}
.card-oficio .cab .num-of {
    font-size: 20px; font-weight: 800;
}
.card-oficio .cab .grupo {
    display: inline-block; background: rgba(255,255,255,0.2);
    padding: 2px 8px; border-radius: 6px; font-size: 11px;
    font-weight: 700; margin-left: 8px; vertical-align: middle;
}
.card-oficio .cab .meta {
    font-size: 12px; opacity: 0.9; margin-top: 4px;
}
.card-oficio .cab .pdf-link {
    float: right; background: #fff; color: #1a4f7a;
    padding: 4px 10px; border-radius: 6px; font-size: 12px;
    font-weight: 700; text-decoration: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.card-oficio .cab .pdf-link:active { background: #f3f4f6; }
.card-oficio .tot-of {
    display: flex; gap: 14px; padding: 8px 14px;
    background: #f3f4f6; font-size: 12px; color: #4b5563;
    border-bottom: 1px solid #e5e7eb;
}
.card-oficio .tot-of b { color: #1a4f7a; font-size: 14px; }

.posto {
    border-bottom: 1px solid #f3f4f6; padding: 12px 14px;
}
.posto:last-child { border-bottom: none; }
.posto .ph {
    display: flex; align-items: center; gap: 8px; margin-bottom: 8px;
}
.posto .cod {
    background: #1a4f7a; color: #fff; padding: 4px 10px;
    border-radius: 16px; font-weight: 800; font-size: 13px;
    min-width: 50px; text-align: center;
}
.posto .nm { font-weight: 700; color: #1f2937; font-size: 14px; flex: 1; }
.posto .qt {
    background: #d1fae5; color: #065f46; padding: 3px 10px;
    border-radius: 16px; font-weight: 700; font-size: 12px;
}

.kv { display: grid; grid-template-columns: 1fr; gap: 6px; margin-top: 6px; }
.kv .row {
    display: flex; justify-content: space-between; align-items: center;
    gap: 8px; padding: 6px 0; border-bottom: 1px dashed #e5e7eb;
    font-size: 13px;
}
.kv .row:last-child { border-bottom: none; }
.kv .row .k {
    color: #6b7280; font-weight: 600; font-size: 11px;
    text-transform: uppercase; letter-spacing: 0.4px; flex-shrink: 0;
}
.kv .row .v {
    font-family: ui-monospace, "SF Mono", Consolas, monospace;
    font-size: 13px; color: #111827; font-weight: 600;
    text-align: right; word-break: break-all;
}
.kv .row .v.empty { color: #9ca3af; font-style: italic; font-weight: 400; }

.lotes-mini { margin-top: 8px; }
.lotes-mini .lt {
    background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;
    padding: 8px 10px; margin-bottom: 6px; font-size: 12px;
}
.lotes-mini .lt .l1 { display: flex; justify-content: space-between; }
.lotes-mini .lt .nlote {
    font-family: ui-monospace, "SF Mono", Consolas, monospace;
    font-weight: 700; color: #1a4f7a;
}
.lotes-mini .lt .data { color: #6b7280; font-size: 11px; }
.lotes-mini .lt .qtd { color: #065f46; font-weight: 700; }
.lotes-mini .lt .eti {
    font-family: ui-monospace, "SF Mono", Consolas, monospace;
    color: #4b5563; font-size: 11px; margin-top: 4px; word-break: break-all;
}
.badge-status {
    display:inline-block; padding:2px 8px; border-radius:10px;
    font-size:10px; font-weight:800; text-transform:uppercase;
    letter-spacing:0.4px; margin-left:6px; vertical-align:middle;
}
.badge-despachado  { background:#bbf7d0; color:#065f46; border:1px solid #34d399; }
.badge-malote      { background:#ede9fe; color:#5b21b6; border:1px solid #c4b5fd; }
.badge-conferido   { background:#ccfbf1; color:#115e59; border:1px solid #5eead4; }
.badge-estante     { background:#dcfce7; color:#166534; border:1px solid #86efac; }
.badge-expedido    { background:#dbeafe; color:#1e3a8a; border:1px solid #93c5fd; }
.badge-produzido   { background:#fef9c3; color:#854d0e; border:1px solid #fde047; }
.badge-desconhecido{ background:#f3f4f6; color:#6b7280; border:1px solid #d1d5db; }
.estante-info { margin-top:4px; padding:6px 8px; background:#f0fdf4; border-radius:6px;
                border-left:3px solid #22c55e; font-size:11px; color:#166534; }
.estante-info.despachado { background:#ecfdf5; border-left-color:#10b981; color:#065f46; font-weight:600; }
.estante-info.malote { background:#f5f3ff; border-left-color:#8b5cf6; color:#5b21b6; }
.estante-info.expedido { background:#eff6ff; border-left-color:#3b82f6; color:#1e3a8a; }
.estante-info.produzido{ background:#fefce8; border-left-color:#eab308; color:#854d0e; }

.card-lote-solo {
    background:#fff; border-radius:12px; margin-bottom:14px;
    box-shadow:0 2px 8px rgba(0,0,0,0.08); overflow:hidden;
    border:2px solid #fde68a;
}
.card-lote-solo .cab {
    background:linear-gradient(135deg, #ca8a04 0%, #eab308 100%);
    color:#fff; padding:12px 14px;
}
.card-lote-solo .cab .titulo { font-size:18px; font-weight:800; }
.card-lote-solo .cab .sub { font-size:12px; opacity:0.92; margin-top:3px; }
.card-lote-solo .corpo { padding:12px 14px; }
.card-lote-solo .secao { margin-bottom:10px; }
.card-lote-solo .secao h4 {
    margin:0 0 6px 0; font-size:12px; color:#4b5563;
    text-transform:uppercase; letter-spacing:0.4px; font-weight:700;
}
.card-lote-solo .lin {
    display:flex; justify-content:space-between; padding:6px 0;
    border-bottom:1px dashed #e5e7eb; font-size:13px;
}
.card-lote-solo .lin:last-child { border-bottom:none; }
.card-lote-solo .lin .k { color:#6b7280; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:0.4px; }
.card-lote-solo .lin .v { font-weight:600; color:#111827; text-align:right; }

.mov { margin-top: 8px; padding: 8px 10px; background: #fffbeb; border-radius: 8px; border: 1px solid #fde68a; font-size: 12px; }
.mov .mh { font-weight: 700; color: #78350f; margin-bottom: 4px; }
.mov .mv { display: flex; justify-content: space-between; padding: 3px 0; border-top: 1px dashed #fcd34d; }
.mov .mv:first-of-type { border-top: none; }
.mov .mv .ml { color: #92400e; }
.mov .mv .mr { font-weight: 700; color: #78350f; }
.mov .tipo-1 { color: #1e40af; }
.mov .tipo-2 { color: #065f46; }

/* v2.3.3: linha do tempo unificada (auditoria + rastreio + displays) */
.tl-card {
    background:#fff; border-radius:12px; padding:14px;
    box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:14px;
}
.tl-card > .cab { margin-bottom:10px; }
.tl-card .titulo { font-weight:700; font-size:15px; color:#1a4f7a; }
.tl-card .sub { font-size:12px; color:#6b7280; margin-top:2px; }
.tl { list-style:none; margin:0; padding:0; position:relative; }
.tl:before { content:""; position:absolute; left:9px; top:6px; bottom:6px; width:2px; background:#e5e7eb; }
.tl li { position:relative; padding:0 0 12px 30px; }
.tl .dot { position:absolute; left:3px; top:3px; width:14px; height:14px; border-radius:50%; border:3px solid #fff; box-shadow:0 0 0 1px #cdd6e0; background:#90a4ae; }
.tl .quando { font-size:11px; color:#6b7280; }
.tl .fase { font-weight:700; font-size:13px; margin:1px 0; }
.tl .resp { font-size:11px; color:#37474f; }
.tl .det { font-size:11px; color:#546170; margin-top:2px; word-break:break-word; }
.tl .azul .dot { background:#1565c0; } .tl .azul .fase { color:#0d47a1; }
.tl .roxo .dot { background:#7e57c2; } .tl .roxo .fase { color:#5e35b1; }
.tl .verde .dot { background:#2e7d32; } .tl .verde .fase { color:#1b5e20; }
.tl .laranja .dot { background:#ef6c00; } .tl .laranja .fase { color:#e65100; }
.tl .cinza .dot { background:#607d8b; } .tl .cinza .fase { color:#455a64; }
.tl .vermelho .dot { background:#c62828; } .tl .vermelho .fase { color:#b71c1c; }

.empty-state {
    background: #fff; border-radius: 12px; padding: 28px 16px;
    text-align: center; color: #6b7280;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.empty-state .icon { font-size: 36px; margin-bottom: 8px; }
.empty-state .t { font-size: 16px; font-weight: 700; color: #374151; margin-bottom: 4px; }

.detectado {
    display: inline-block; background: #dbeafe; color: #1e3a8a;
    padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;
    margin-left: 6px;
}

@media (min-width: 640px) {
    .container { padding: 18px; }
    body { font-size: 16px; }
}
</style>
</head>
<body>

<div class="topbar">
    <h1>&#128269; Busca de Producao</h1>
    <div class="sub">Lacres, Displays, Lotes, Oficios &mdash; mobile / QR Code</div>
</div>

<div class="container">

<form class="busca" method="get" action="busca_producao_mobile.php" autocomplete="off">
    <div class="campo">
        <label for="q">Termo de Busca</label>
        <input type="search" id="q" name="q" value="<?php echo e($termo); ?>"
               inputmode="search" autocapitalize="characters" autocorrect="off" spellcheck="false"
               placeholder="Lote, display, posto, oficio, etiqueta 35 dig...">
    </div>

    <div class="linha-dupla">
        <div class="campo">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="auto"     <?php echo $tipo==='auto'?'selected':''; ?>>Auto-detectar</option>
                <option value="etiqueta" <?php echo $tipo==='etiqueta'?'selected':''; ?>>Etiqueta Correios</option>
                <option value="lote"     <?php echo $tipo==='lote'?'selected':''; ?>>Lote</option>
                <option value="posto"    <?php echo $tipo==='posto'?'selected':''; ?>>Posto</option>
                <option value="oficio"   <?php echo $tipo==='oficio'?'selected':''; ?>>N. do Oficio</option>
                <option value="lacre"    <?php echo $tipo==='lacre'?'selected':''; ?>>Lacre / Display</option>
                <option value="data"     <?php echo $tipo==='data'?'selected':''; ?>>Data</option>
            </select>
        </div>
        <div class="campo">
            <label for="di">Data inicial</label>
            <input type="date" id="di" name="di" value="<?php echo e($dataIniN); ?>">
        </div>
    </div>

    <div class="linha-dupla">
        <div class="campo">
            <label for="df">Data final</label>
            <input type="date" id="df" name="df" value="<?php echo e($dataFimN); ?>">
        </div>
        <div class="campo">
            <label>&nbsp;</label>
            <button type="button" class="secondary" onclick="document.getElementById('q').value='';document.getElementById('di').value='';document.getElementById('df').value='';document.getElementById('tipo').value='auto';document.getElementById('q').focus();">Limpar</button>
        </div>
    </div>

    <div class="botoes">
        <button type="submit" class="primary">&#128269; Buscar</button>
        <button type="button" class="secondary" onclick="window.location.href='busca_producao_mobile.php'">Reset</button>
    </div>

    <?php if (!$temBusca): ?>
    <div class="hint">
        <strong>Dica:</strong> deixe o tipo em <em>Auto-detectar</em> e cole/digite o termo.
        Etiqueta de 35 digitos, lote (6-30 dig), posto (1-5 dig) e lacres alfanumericos sao
        identificados automaticamente. Pode tambem abrir esta pagina via QR Code com
        <code>?q=VALOR</code> na URL.
    </div>
    <?php endif; ?>
</form>

<?php if ($erroBusca !== ''): ?>
    <div class="alerta erro">
        <strong>Erro:</strong> <?php echo e($erroBusca); ?>
    </div>
<?php endif; ?>

<?php
// v2.3.3: LINHA DO TEMPO UNIFICADA — aparece em qualquer busca por LOTE, esteja
// ele em oficio ou nao. Reune producao, triagem, conferencia, oficio, despacho e
// movimentos de display (ciMalotes) numa unica cronologia (estilo auditoria_lote).
if ($lotePadInit !== '' && $erroBusca === '' && isset($infoLotesExtra[$lotePadInit])):
    $eventosTl = montar_timeline($infoLotesExtra[$lotePadInit]);
    if (!empty($eventosTl)):
?>
    <div class="tl-card">
        <div class="cab">
            <div class="titulo">&#128338; Linha do tempo &middot; Lote <?php echo e($lotePadInit); ?></div>
            <div class="sub">Producao &rarr; triagem &rarr; conferencia &rarr; oficio &rarr; display, tudo num lugar so</div>
        </div>
        <ul class="tl">
            <?php foreach ($eventosTl as $evt): ?>
            <li class="<?php echo e($evt['cor']); ?>">
                <span class="dot"></span>
                <div class="quando"><?php echo e($evt['disp']); ?></div>
                <div class="fase"><?php echo e($evt['fase']); ?></div>
                <?php if ($evt['resp'] !== ''): ?><div class="resp">por <?php echo e($evt['resp']); ?></div><?php endif; ?>
                <?php if ($evt['det'] !== ''): ?><div class="det"><?php echo e($evt['det']); ?></div><?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; endif; ?>

<?php
// Card especial para LOTE buscado que nao esta em nenhum oficio.
// Mostra producao (ciPostosCsv) e estante (conferencia_pacotes) mesmo
// para lotes antigos ou ainda nao expedidos.
if ($loteBuscadoFallback !== '' && empty($resultados) && $erroBusca === ''):
    $info = isset($infoLotesExtra[$loteBuscadoFallback]) ? $infoLotesExtra[$loteBuscadoFallback] : array('producao'=>array(),'estante'=>array());
    $stFb = status_lote($info);
    $rotuloFb = array(
        'despachado'   => 'DESPACHADO',
        'malote'       => 'NO MALOTE (aguardando despacho)',
        'conferido'    => 'CONFERIDO (aguardando geracao do oficio)',
        'estante'      => 'NA ESTANTE (triado, aguardando conferencia)',
        'expedido'     => 'LIDO (nao conferido)',
        'produzido'    => 'NO CARRINHO / EXPEDIDO (antes da estante)',
        'desconhecido' => 'SEM REGISTRO no sistema',
    );
?>
    <div class="card-lote-solo">
        <div class="cab">
            <div class="titulo">&#128230; Lote <?php echo e($loteBuscadoFallback); ?></div>
            <div class="sub">Nao consta em nenhum oficio</div>
            <div style="margin-top:6px;">
                <span class="badge-status badge-<?php echo $stFb; ?>" style="font-size:12px;">
                    <?php echo e($rotuloFb[$stFb]); ?>
                </span>
            </div>
        </div>
        <div class="corpo">
            <?php if (!empty($info['producao'])): ?>
            <div class="secao">
                <h4>&#128736; Producao (ciPostosCsv)</h4>
                <?php foreach ($info['producao'] as $pr): ?>
                <div class="lin"><span class="k">Posto</span><span class="v"><?php echo e(isset($pr['posto'])?normaliza_posto($pr['posto']):'-'); ?></span></div>
                <?php if (!empty($pr['regional'])): ?>
                <div class="lin"><span class="k">Regional</span><span class="v"><?php echo e($pr['regional']); ?></span></div>
                <?php endif; ?>
                <?php if (isset($pr['qtd'])): ?>
                <div class="lin"><span class="k">Quantidade</span><span class="v"><?php echo (int)$pr['qtd']; ?> cart.</span></div>
                <?php endif; ?>
                <?php if (!empty($pr['usuario'])): ?>
                <div class="lin"><span class="k">Produzido por</span><span class="v"><?php echo e($pr['usuario']); ?></span></div>
                <?php endif; ?>
                <?php if (!empty($pr['dt'])): ?>
                <div class="lin"><span class="k">Data carga</span><span class="v"><?php echo e(fmt_dt_br($pr['dt'])); ?></span></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($info['estante'])): ?>
            <div class="secao">
                <h4>&#127981; Estante / Conferencia</h4>
                <?php foreach ($info['estante'] as $es): ?>
                <?php $conf = isset($es['conf']) ? strtolower(trim((string)$es['conf'])) : ''; ?>
                <div class="lin">
                    <span class="k">Status</span>
                    <span class="v">
                        <?php if ($conf === 's' || $conf === 'sim' || $conf === '1' || $conf === 'y'): ?>
                            <span style="color:#166534;">&#10003; Conferido</span>
                        <?php else: ?>
                            <span style="color:#854d0e;">&#9203; Pendente</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (!empty($es['posto'])): ?>
                <div class="lin"><span class="k">Posto</span><span class="v"><?php echo e(normaliza_posto($es['posto'])); ?></span></div>
                <?php endif; ?>
                <?php if (!empty($es['usuario'])): ?>
                <div class="lin"><span class="k">Conferido por</span><span class="v"><?php echo e($es['usuario']); ?></span></div>
                <?php endif; ?>
                <?php if (!empty($es['dt'])): ?>
                <div class="lin"><span class="k">Conferido em</span><span class="v"><?php echo e(fmt_dt_br($es['dt'])); ?></span></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($info['triado'])): ?>
            <div class="secao">
                <h4>&#127981; Estante (triado)</h4>
                <?php foreach ($info['triado'] as $tr): ?>
                <div class="lin"><span class="k">Status</span><span class="v"><span style="color:#166534;">&#10003; Na estante</span></span></div>
                <?php if (!empty($tr['posto'])): ?>
                <div class="lin"><span class="k">Posto</span><span class="v"><?php echo e(normaliza_posto($tr['posto'])); ?></span></div>
                <?php endif; ?>
                <?php if (!empty($tr['usuario'])): ?>
                <div class="lin"><span class="k">Triado por</span><span class="v"><?php echo e($tr['usuario']); ?></span></div>
                <?php endif; ?>
                <?php if (!empty($tr['dt'])): ?>
                <div class="lin"><span class="k">Triado em</span><span class="v"><?php echo e(fmt_dt_br($tr['dt'])); ?></span></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($info['producao']) && empty($info['estante']) && empty($info['triado'])): ?>
            <div class="alerta warn" style="margin-bottom:0;">
                Nao encontramos registro deste lote em <strong>ciPostosCsv</strong>,
                <strong>lotes_na_estante</strong> nem <strong>conferencia_pacotes</strong>.
                Verifique se o numero do lote esta correto.
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($temBusca && empty($resultados) && $loteBuscadoFallback === '' && $erroBusca === ''): ?>
    <div class="empty-state">
        <div class="icon">&#128269;</div>
        <div class="t">Nada encontrado</div>
        <div>Nao foi encontrado nenhum oficio para os filtros informados.</div>
        <?php if ($tipoEfetivo !== ''): ?>
            <div style="margin-top:8px;font-size:12px;">
                Tipo detectado: <strong><?php echo e(isset($descTipo[$tipoEfetivo]) ? $descTipo[$tipoEfetivo] : $tipoEfetivo); ?></strong>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($resultados)): ?>
    <div class="alerta info">
        <strong>Termo:</strong> <?php echo e($termo); ?>
        <?php if ($tipoEfetivo !== ''): ?>
            <span class="detectado"><?php echo e(isset($descTipo[$tipoEfetivo]) ? $descTipo[$tipoEfetivo] : $tipoEfetivo); ?></span>
        <?php endif; ?>
    </div>

    <div class="resumo">
        <div class="box"><div class="num"><?php echo (int)$totaisGerais['oficios']; ?></div><div class="lbl">Oficios</div></div>
        <div class="box"><div class="num"><?php echo (int)$totaisGerais['postos']; ?></div><div class="lbl">Postos</div></div>
        <div class="box"><div class="num"><?php echo (int)$totaisGerais['lotes']; ?></div><div class="lbl">Lotes</div></div>
        <div class="box"><div class="num"><?php echo (int)$totaisGerais['carteiras']; ?></div><div class="lbl">Cart.</div></div>
    </div>

    <?php foreach ($resultados as $res):
        $c = $res['cab'];
        $criado = !empty($c['criado_at']) ? $c['criado_at'] : (!empty($c['criado_em']) ? $c['criado_em'] : '');

        // Rotulos dos lacres: ofícios POUPA TEMPO (inclui interior) usam
        // "Lacre Poupa Tempo" / "Lacre Poupa Tempo Correios" (mesmo padrão da
        // rastreabilidade.php); demais grupos seguem "Lacre IIPR"/"Lacre Correios".
        $grupoLow   = strtolower((string)$c['grupo']);
        $isPT       = (strpos($grupoLow, 'poupa') !== false || strpos($grupoLow, 'tempo') !== false);
        $lblLacre   = $isPT ? 'Lacre Poupa Tempo' : 'Lacre IIPR';
        $lblLacreCr = $isPT ? 'Lacre Poupa Tempo Correios' : 'Lacre Correios';

        // Monta link do PDF do oficio: <base>/cioficios/{id}_{tipo}_{dd-mm-yyyy}.pdf
        // <base> = diretorio onde a app esta (ex.: /controle/malote) para
        // funcionar tanto na raiz quanto sob prefixo Yii.
        // tipo = lower(grupo) sem espacos: "poupatempo" ou "correios"
        // v2.0.0: nome do arquivo = {id}_{tipo}.pdf (sem data).
        // tipo = "correios" ou "poupatempo" (sem espaco/underscore).
        // ID e unico por despacho, entao a data nao e necessaria.
        $pdf_link = '';
        $pdf_nome = '';
        if ((int)$c['id'] > 0 && !empty($c['grupo'])) {
            $tipo_lower = strtolower(str_replace(' ', '', trim((string)$c['grupo'])));
            $pdf_nome   = (int)$c['id'] . '_' . $tipo_lower . '.pdf';
            $base_dir   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            if ($base_dir === '' || $base_dir === '.') $base_dir = '';
            $pdf_link = $base_dir . '/cioficios/' . rawurlencode($pdf_nome);
        }
    ?>
    <div class="card-oficio">
        <div class="cab">
            <span class="num-of">Oficio n. <?php echo (int)$c['id']; ?></span>
            <span class="grupo"><?php echo e($c['grupo']); ?></span>
            <?php if ($pdf_link !== ''): ?>
                <a class="pdf-link" href="<?php echo e($pdf_link); ?>" target="_blank" rel="noopener" title="Abrir PDF: <?php echo e($pdf_nome); ?>">&#128196; PDF</a>
            <?php endif; ?>
            <div class="meta">
                <?php if (!empty($c['datas_str'])): ?>Datas: <?php echo e($c['datas_str']); ?> &nbsp;&middot;&nbsp;<?php endif; ?>
                <?php if (!empty($c['usuario'])): ?>Usuario: <?php echo e($c['usuario']); ?><?php endif; ?>
                <?php if ($criado): ?> &nbsp;&middot;&nbsp; Criado: <?php echo e(fmt_data_br($criado)); ?><?php endif; ?>
            </div>
        </div>
        <div class="tot-of">
            <div>Postos: <b><?php echo (int)$res['totais']['postos']; ?></b></div>
            <div>Lotes: <b><?php echo (int)$res['totais']['lotes']; ?></b></div>
            <div>Carteiras: <b><?php echo (int)$res['totais']['carteiras']; ?></b></div>
        </div>

        <?php foreach ($res['postos'] as $p):
            $it = $p['item'];
            $nomePosto = $it ? $it['nome_posto'] : '';
            $endereco  = $it ? $it['endereco']  : '';
            $lacreIIPR = $it ? trim((string)$it['lacre_iipr']) : '';
            $lacreCor  = $it ? trim((string)$it['lacre_correios']) : '';
            $etiCor    = $it ? trim((string)$it['etiqueta_correios']) : '';

            // Fallback CORREIOS: lacres ficam em ciDespachoLotes
            // (etiquetaiipr / etiquetacorreios / etiqueta_correios).
            // Coleta valores distintos para o caso de variarem entre lotes.
            $setLacreIIPR = array(); $setLacreCor = array(); $setEtiCor = array();
            if ($lacreIIPR !== '') $setLacreIIPR[$lacreIIPR] = true;
            if ($lacreCor  !== '') $setLacreCor[$lacreCor]   = true;
            if ($etiCor    !== '') $setEtiCor[$etiCor]       = true;
            foreach ($p['lotes'] as $lt) {
                $vI = isset($lt['etiquetaiipr'])     ? trim((string)$lt['etiquetaiipr'])     : '';
                $vC = isset($lt['etiquetacorreios']) ? trim((string)$lt['etiquetacorreios']) : '';
                $vE = isset($lt['etiqueta_correios']) ? trim((string)$lt['etiqueta_correios']) : '';
                if ($vI !== '') $setLacreIIPR[$vI] = true;
                if ($vC !== '') $setLacreCor[$vC]  = true;
                if ($vE !== '') $setEtiCor[$vE]    = true;
            }
            $lacreIIPR = implode(', ', array_keys($setLacreIIPR));
            $lacreCor  = implode(', ', array_keys($setLacreCor));
            $etiCor    = implode(', ', array_keys($setEtiCor));
        ?>
        <div class="posto">
            <div class="ph">
                <span class="cod"><?php echo e($p['codigo']); ?></span>
                <span class="nm"><?php echo e($nomePosto !== '' ? $nomePosto : '(sem nome)'); ?></span>
                <span class="qt"><?php echo (int)$p['cartPosto']; ?> cart.</span>
            </div>

            <div class="kv">
                <?php if ($endereco !== ''): ?>
                <div class="row"><span class="k">Endereco</span><span class="v" style="font-family:inherit;font-weight:500;font-size:12px;color:#4b5563;"><?php echo e($endereco); ?></span></div>
                <?php endif; ?>
                <div class="row"><span class="k"><?php echo e($lblLacre); ?></span><span class="v <?php echo $lacreIIPR===''?'empty':''; ?>"><?php echo $lacreIIPR!==''?e($lacreIIPR):'(vazio)'; ?></span></div>
                <?php
                // Postos POUPA TEMPO da Capital/RM (cod 005-080) NAO usam lacre
                // Correios nem etiqueta dos Correios -> nao mostra essas linhas.
                $usaCorreios = postoUsaEtiquetaCorreios($c['grupo'], $p['codigo']);
                if ($usaCorreios):
                ?>
                <div class="row"><span class="k"><?php echo e($lblLacreCr); ?></span><span class="v <?php echo $lacreCor===''?'empty':''; ?>"><?php echo $lacreCor!==''?e($lacreCor):'(vazio)'; ?></span></div>
                <div class="row"><span class="k">Etiq. Correios</span><span class="v <?php echo $etiCor===''?'empty':''; ?>"><?php echo $etiCor!==''?e($etiCor):'(vazio)'; ?></span></div>
                <?php else: ?>
                <div class="row"><span class="k"><?php echo e($lblLacreCr); ?></span><span class="v" style="color:#94a3b8;font-style:italic;font-weight:400;">Capital — sem etiqueta Correios</span></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($p['lotes'])): ?>
            <div class="lotes-mini">
                <?php foreach ($p['lotes'] as $lt):
                    // Status na estante (verde/azul/amarelo)
                    $kLote = str_pad(preg_replace('/\D+/', '', (string)$lt['lote']), 8, '0', STR_PAD_LEFT);
                    $infoLt = isset($infoLotesExtra[$kLote]) ? $infoLotesExtra[$kLote] : array('producao'=>array(),'estante'=>array());
                    $stLt   = status_lote($infoLt);
                    $rotulo = array(
                        'despachado'   => 'Despachado',
                        'malote'       => 'No malote (aguarda despacho)',
                        'conferido'    => 'Conferido',
                        'estante'      => 'Na estante (triado)',
                        'expedido'     => 'Lido (nao conferido)',
                        'produzido'    => 'No carrinho / expedido',
                        'desconhecido' => 'Sem registro',
                    );
                ?>
                <div class="lt">
                    <div class="l1">
                        <span class="nlote">Lote <?php echo e($lt['lote']); ?>
                            <span class="badge-status badge-<?php echo $stLt; ?>"><?php echo e($rotulo[$stLt]); ?></span>
                        </span>
                        <span class="qtd"><?php echo (int)$lt['quantidade']; ?> cart.</span>
                    </div>
                    <div class="data">
                        Data carga: <?php echo e(fmt_data_br($lt['data_carga'])); ?>
                        <?php if (!empty($lt['responsaveis'])): ?>
                            &middot; Resp: <?php echo e($lt['responsaveis']); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($stLt === 'despachado'):
                        $oRow = !empty($infoLt['oficio']) ? $infoLt['oficio'][0] : null;
                        $despManual = '';
                        if (!empty($infoLt['oficio'])) {
                            foreach ($infoLt['oficio'] as $oo) {
                                $dd = isset($oo['data_despacho_correios']) ? trim((string)$oo['data_despacho_correios']) : '';
                                if ($dd !== '' && strpos($dd, '0000-00-00') !== 0) { $oRow = $oo; $despManual = $dd; break; }
                            }
                        }
                    ?>
                    <div class="estante-info despachado">
                        &#128666; Despachado
                        <?php if ($despManual !== ''): ?>
                            em <?php echo e(fmt_data_br($despManual)); ?>
                            <?php if ($oRow && !empty($oRow['despachado_por'])): ?> por <strong><?php echo e($oRow['despachado_por']); ?></strong><?php endif; ?>
                        <?php elseif (!empty($infoLt['despacho_inferido'])): ?>
                            (provavel) &middot; retorno de displays em <?php echo e(fmt_dt_br($infoLt['despacho_inferido'])); ?>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($stLt === 'malote'): ?>
                    <div class="estante-info malote">
                        &#128230; Fechado no malote, aguardando despacho.
                    </div>
                    <?php elseif ($stLt === 'conferido'):
                        $cf1 = null;
                        foreach ($infoLt['estante'] as $e) {
                            $cc = isset($e['conf']) ? strtolower(trim((string)$e['conf'])) : '';
                            if ($cc==='s' || $cc==='sim' || $cc==='1' || $cc==='y') { $cf1 = $e; break; }
                        }
                    ?>
                    <div class="estante-info">
                        &#10003; Conferido (aguardando geracao do oficio)
                        <?php if ($cf1 && !empty($cf1['dt'])): ?>em <?php echo e(fmt_dt_br($cf1['dt'])); ?><?php endif; ?>
                        <?php if ($cf1 && !empty($cf1['usuario'])): ?> por <strong><?php echo e($cf1['usuario']); ?></strong><?php endif; ?>
                    </div>
                    <?php elseif ($stLt === 'estante' && !empty($infoLt['triado'])):
                        $e1 = $infoLt['triado'][0];
                    ?>
                    <div class="estante-info">
                        &#10003; Na estante (triado)
                        <?php if (!empty($e1['dt'])): ?>em <?php echo e(fmt_dt_br($e1['dt'])); ?><?php endif; ?>
                        <?php if (!empty($e1['usuario'])): ?> por <strong><?php echo e($e1['usuario']); ?></strong><?php endif; ?>
                    </div>
                    <?php elseif ($stLt === 'expedido'): ?>
                    <div class="estante-info expedido">
                        &#9888; Lote lido mas <strong>nao conferido</strong>.
                    </div>
                    <?php elseif ($stLt === 'produzido'): ?>
                    <div class="estante-info produzido">
                        &#128722; No carrinho / expedido (antes da triagem para a estante).
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($lt['etiqueta_correios'])): ?>
                        <div class="eti">Etiq: <?php echo e($lt['etiqueta_correios']); ?></div>
                        <?php
                            $eti = (string)$lt['etiqueta_correios'];
                            $movs = isset($movimentosPorEtiqueta[$eti]) ? $movimentosPorEtiqueta[$eti] : array();
                        ?>
                        <?php if (!empty($movs)): ?>
                            <div class="mov">
                                <div class="mh">Movimentos (<?php echo count($movs); ?>)</div>
                                <?php foreach ($movs as $mv): ?>
                                <div class="mv">
                                    <span class="ml">
                                        <span class="tipo-<?php echo (int)$mv['tipo']; ?>">
                                            <?php echo ((int)$mv['tipo']===1?'Envio':((int)$mv['tipo']===2?'Recebimento':'Tipo '.(int)$mv['tipo'])); ?>
                                        </span>
                                        &middot; <?php echo e(fmt_data_br($mv['data'])); ?>
                                    </span>
                                    <span class="mr">
                                        <?php echo e($mv['login']); ?>
                                        <?php if (!empty($mv['posto'])): ?> &middot; P<?php echo e(normaliza_posto($mv['posto'])); ?><?php endif; ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<div style="text-align:center;font-size:11px;color:#9ca3af;margin-top:20px;padding-bottom:30px;">
    Sistema Lacres v2.0.0 &middot; Pagina mobile de busca
</div>

</div>

<script>
// Submete automaticamente quando vier de QR Code com ?q= ja preenchido — nada a fazer:
// o PHP ja processou e renderizou os resultados.
// Foco no campo quando entra sem termo.
(function(){
    var q = document.getElementById('q');
    if (q && q.value === '') { try { q.focus(); } catch(e){} }
})();
</script>

</body>
</html>
