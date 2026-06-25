<?php
/* auditoria_lote.php — v2.0.5
   Auditoria / linha do tempo de um LOTE.
   Mostra, em ordem cronologica (data/hora) e por fases, o ciclo de vida do lote:
   producao/expedicao, triagem para a estante, conferencia, fechamento em oficio
   (posto/regional/lacres/display) e movimentos de display (envio/devolucao) e
   adiantamentos.

   Compativel com PHP 5.3.3 (array(), sem closures/short-array). Tolerante a
   tabelas/colunas ausentes (SHOW TABLES / SHOW COLUMNS): cada fase so aparece se
   a tabela existir; a coluna de data/responsavel e escolhida dinamicamente.
   O banco da LAN (10.15.61.169) e inacessivel fora da rede do usuario — neste
   caso a pagina mostra um aviso e continua funcional (sem dados).
*/
header('Cache-Control: no-cache, no-store, must-revalidate');
session_start();

require_once dirname(__FILE__) . '/db_config.php';

function eAUD($s) {
    $s2 = (string)$s;
    if (!preg_match('//u', $s2) && function_exists('iconv')) {
        $t = @iconv('UTF-8', 'UTF-8//IGNORE', $s2);
        if ($t !== false) $s2 = $t;
    }
    return htmlspecialchars($s2, ENT_QUOTES, 'UTF-8');
}

/* Normaliza um valor de data/hora cru para ordenacao e exibicao. */
function audNormTs($raw) {
    $r = trim((string)$raw);
    if ($r === '' || strpos($r, '0000-00-00') === 0) {
        return array('sort' => '9999-99-99 99:99:99', 'disp' => '—');
    }
    // Tenta formatos comuns vindos do MySQL.
    $dt = false;
    $fmts = array('Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y');
    foreach ($fmts as $f) {
        $tmp = DateTime::createFromFormat($f, $r);
        if ($tmp !== false) { $dt = $tmp; break; }
    }
    if ($dt === false) {
        $ts = strtotime($r);
        if ($ts !== false) { $dt = new DateTime('@' . $ts); $dt->setTimezone(new DateTimeZone(date_default_timezone_get() ?: 'UTC')); }
    }
    if ($dt === false) {
        return array('sort' => '9999-99-99 99:99:99', 'disp' => eAUD($r));
    }
    $temHora = (strpos($r, ':') !== false);
    return array(
        'sort' => $dt->format('Y-m-d H:i:s'),
        'disp' => $temHora ? $dt->format('d/m/Y H:i:s') : $dt->format('d/m/Y')
    );
}

function audTabelaExiste($pdo, $t) {
    try {
        $st = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t));
        return ($st && $st->fetch()) ? true : false;
    } catch (Exception $e) { return false; }
}

/* Retorna o 1o nome de coluna existente na tabela dentre os candidatos. */
function audPickCol($pdo, $tab, $cands) {
    foreach ($cands as $c) {
        try {
            $st = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`', '', $tab) . "` LIKE " . $pdo->quote($c));
            if ($st && $st->fetch()) return $c;
        } catch (Exception $e) { /* tolera */ }
    }
    return null;
}

/* v2.3.3: regional canonica do posto (fonte da verdade = ciRegionais), com cache.
   Necessario porque ciPostosCsv pode guardar a regional vinda do codigo de barras
   (ex.: 501) em vez da regional real do posto (ex.: 527 para o posto 527). */
function audRegionalCanonica($pdo, $posto) {
    static $cache = array();
    $p = preg_replace('/\D+/', '', (string)$posto);
    if ($p === '') return '';
    $p3 = str_pad($p, 3, '0', STR_PAD_LEFT);
    if (array_key_exists($p3, $cache)) return $cache[$p3];
    $cache[$p3] = '';
    try {
        if (audTabelaExiste($pdo, 'ciRegionais')) {
            $cPosto = audPickCol($pdo, 'ciRegionais', array('posto', 'nposto'));
            $cReg = audPickCol($pdo, 'ciRegionais', array('regional'));
            if ($cPosto && $cReg) {
                $st = $pdo->prepare("SELECT `$cReg` AS regional FROM `ciRegionais` WHERE LPAD(`$cPosto`,3,'0') = ? LIMIT 1");
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

/* Variacoes do numero do lote (INT/VARCHAR, zeros a esquerda). */
function audLoteCands($lote) {
    $lote = trim((string)$lote);
    $d = preg_replace('/\D+/', '', $lote);
    $set = array();
    if ($lote !== '') $set[$lote] = 1;
    if ($d !== '') {
        $set[$d] = 1;
        $sz = ltrim($d, '0'); if ($sz !== '') $set[$sz] = 1;
        $set[str_pad($d, 8, '0', STR_PAD_LEFT)] = 1;
    }
    $out = array();
    foreach ($set as $k => $v) { if ((string)$k !== '') $out[] = (string)$k; }
    return $out;
}

function audIn($n) { return implode(',', array_fill(0, $n, '?')); }

/* Adiciona um evento a linha do tempo. $despacho = id do oficio (ciclo) ao qual o
   evento pertence; vazio para eventos que nao sao de oficio (producao, conferencia,
   display, adiantamento) — esses aparecem sempre, em qualquer ciclo. */
function audAdd(&$eventos, $tsRaw, $fase, $resp, $detalhe, $cor, $despacho = '') {
    $n = audNormTs($tsRaw);
    $eventos[] = array(
        'sort' => $n['sort'],
        'disp' => $n['disp'],
        'fase' => $fase,
        'resp' => trim((string)$resp),
        'detalhe' => $detalhe,
        'cor' => $cor,
        'despacho' => trim((string)$despacho)
    );
}

/* ---------- Conexao ---------- */
$dbOk = false; $erroMsg = '';
$pdo = null;
try {
    $cred = getDbCredentials();
    $pdo = new PDO("mysql:host=" . $cred['host'] . ";dbname=" . $cred['name'] . ";charset=utf8", $cred['user'], $cred['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $dbOk = true;
} catch (Exception $e) {
    $dbOk = false;
    $erroMsg = $e->getMessage();
}

/* ---------- Pesquisa ---------- */
$lotePesq = isset($_GET['lote']) ? trim((string)$_GET['lote']) : '';
$eventos = array();
$ciclos = array();   // T7: id_despacho => rotulo de data (ciclos de oficio do lote)
$cicloSel = 0;       // T7: ciclo selecionado (0 = mostrar tudo / fail-open)
$etiquetas = array();   // etiquetas (35 dig) associadas ao lote, p/ rastrear display
$resumo = array('posto' => '', 'regional' => '', 'nome' => '', 'quantidade' => '');
$pesquisou = ($lotePesq !== '');

if ($pesquisou && $dbOk) {
    $cands = audLoteCands($lotePesq);
    $nC = count($cands);
    if ($nC > 0) {
        $inLote = audIn($nC);

        /* FASE 1: PRODUCAO / EXPEDICAO — ciPostosCsv */
        if (audTabelaExiste($pdo, 'ciPostosCsv')) {
            $cL = audPickCol($pdo, 'ciPostosCsv', array('lote', 'nlote', 'nLote'));
            if ($cL) {
                $cData = audPickCol($pdo, 'ciPostosCsv', array('dataCarga', 'data', 'datahora', 'criado_em', 'created_at'));
                $cUser = audPickCol($pdo, 'ciPostosCsv', array('usuario', 'login', 'user'));
                $cPosto = audPickCol($pdo, 'ciPostosCsv', array('posto', 'nposto'));
                $cReg = audPickCol($pdo, 'ciPostosCsv', array('regional'));
                $cQtd = audPickCol($pdo, 'ciPostosCsv', array('quantidade', 'qtd', 'qtde'));
                $cUpload = audPickCol($pdo, 'ciPostosCsv', array('data', 'criado_em', 'created_at'));
                $sel = array("`$cL` AS lote");
                if ($cData) $sel[] = "`$cData` AS dt";
                if ($cUpload && $cUpload !== $cData) $sel[] = "`$cUpload` AS dt_up";
                if ($cUser) $sel[] = "`$cUser` AS usr";
                if ($cPosto) $sel[] = "`$cPosto` AS posto";
                if ($cReg) $sel[] = "`$cReg` AS regional";
                if ($cQtd) $sel[] = "`$cQtd` AS qtd";
                try {
                    $st = $pdo->prepare("SELECT " . implode(', ', $sel) . " FROM ciPostosCsv WHERE `$cL` IN ($inLote)");
                    $st->execute($cands);
                    while ($r = $st->fetch()) {
                        /* v2.3.3: usa a regional canonica de ciRegionais; ciPostosCsv pode trazer a regional do codigo de barras (ex.: 501 em vez de 527) */
                        if (isset($r['posto'])) { $rc = audRegionalCanonica($pdo, $r['posto']); if ($rc !== '') $r['regional'] = $rc; }
                        if ($resumo['posto'] === '' && isset($r['posto'])) $resumo['posto'] = $r['posto'];
                        if ($resumo['regional'] === '' && isset($r['regional'])) $resumo['regional'] = $r['regional'];
                        if ($resumo['quantidade'] === '' && isset($r['qtd'])) $resumo['quantidade'] = $r['qtd'];
                        $det = array();
                        if (isset($r['posto'])) $det[] = 'Posto ' . eAUD($r['posto']);
                        if (isset($r['regional'])) $det[] = 'Regional ' . eAUD($r['regional']);
                        if (isset($r['qtd'])) $det[] = 'Qtd ' . eAUD($r['qtd']);
                        audAdd($eventos, isset($r['dt']) ? $r['dt'] : (isset($r['dt_up']) ? $r['dt_up'] : ''),
                            'Produção / Expedição', isset($r['usr']) ? $r['usr'] : '', implode(' · ', $det), 'azul');
                    }
                } catch (Exception $e) { /* tolera */ }
            }
        }

        /* FASE 2: TRIAGEM PARA A ESTANTE — lotes_na_estante (se existir) */
        if (audTabelaExiste($pdo, 'lotes_na_estante')) {
            $cL = audPickCol($pdo, 'lotes_na_estante', array('lote', 'nlote'));
            if ($cL) {
                $cData = audPickCol($pdo, 'lotes_na_estante', array('triado_em', 'data', 'datahora', 'criado_em'));
                $cUser = audPickCol($pdo, 'lotes_na_estante', array('triado_por', 'usuario', 'login'));
                $cPosto = audPickCol($pdo, 'lotes_na_estante', array('posto', 'nposto'));
                $cReg = audPickCol($pdo, 'lotes_na_estante', array('regional'));
                $cQtd = audPickCol($pdo, 'lotes_na_estante', array('quantidade', 'qtd'));
                $sel = array("`$cL` AS lote");
                if ($cData) $sel[] = "`$cData` AS dt";
                if ($cUser) $sel[] = "`$cUser` AS usr";
                if ($cPosto) $sel[] = "`$cPosto` AS posto";
                if ($cReg) $sel[] = "`$cReg` AS regional";
                if ($cQtd) $sel[] = "`$cQtd` AS qtd";
                try {
                    $st = $pdo->prepare("SELECT " . implode(', ', $sel) . " FROM lotes_na_estante WHERE `$cL` IN ($inLote)");
                    $st->execute($cands);
                    while ($r = $st->fetch()) {
                        $det = array();
                        if (isset($r['posto'])) $det[] = 'Posto ' . eAUD($r['posto']);
                        if (isset($r['regional'])) $det[] = 'Regional ' . eAUD($r['regional']);
                        if (isset($r['qtd'])) $det[] = 'Qtd ' . eAUD($r['qtd']);
                        audAdd($eventos, isset($r['dt']) ? $r['dt'] : '',
                            'Triado para a estante', isset($r['usr']) ? $r['usr'] : '', implode(' · ', $det), 'roxo');
                    }
                } catch (Exception $e) { /* tolera */ }
            }
        }

        /* FASE 3: CONFERENCIA — conferencia_pacotes */
        if (audTabelaExiste($pdo, 'conferencia_pacotes')) {
            $cL = audPickCol($pdo, 'conferencia_pacotes', array('nlote', 'lote', 'nLote'));
            if ($cL) {
                $cData = audPickCol($pdo, 'conferencia_pacotes', array('conferido_em', 'data', 'datahora', 'data_conf', 'criado_em'));
                $cUser = audPickCol($pdo, 'conferencia_pacotes', array('usuario', 'login', 'user', 'conferido_por'));
                $cPosto = audPickCol($pdo, 'conferencia_pacotes', array('nposto', 'posto'));
                $cReg = audPickCol($pdo, 'conferencia_pacotes', array('regional'));
                $cConf = audPickCol($pdo, 'conferencia_pacotes', array('conf'));
                $sel = array("`$cL` AS lote");
                if ($cData) $sel[] = "`$cData` AS dt";
                if ($cUser) $sel[] = "`$cUser` AS usr";
                if ($cPosto) $sel[] = "`$cPosto` AS posto";
                if ($cReg) $sel[] = "`$cReg` AS regional";
                if ($cConf) $sel[] = "`$cConf` AS conf";
                try {
                    $st = $pdo->prepare("SELECT " . implode(', ', $sel) . " FROM conferencia_pacotes WHERE `$cL` IN ($inLote)");
                    $st->execute($cands);
                    while ($r = $st->fetch()) {
                        if ($resumo['posto'] === '' && isset($r['posto'])) $resumo['posto'] = $r['posto'];
                        if ($resumo['regional'] === '' && isset($r['regional'])) $resumo['regional'] = $r['regional'];
                        $conferido = (!isset($r['conf'])) || in_array(strtolower(trim((string)$r['conf'])), array('s', 'sim', '1', 'y', 'yes', 't', 'true'), true);
                        $det = array();
                        if (isset($r['posto'])) $det[] = 'Posto ' . eAUD($r['posto']);
                        if (isset($r['regional'])) $det[] = 'Regional ' . eAUD($r['regional']);
                        if (isset($r['conf'])) $det[] = $conferido ? 'Conferido' : ('Status: ' . eAUD($r['conf']));
                        audAdd($eventos, isset($r['dt']) ? $r['dt'] : '',
                            $conferido ? 'Conferido' : 'Conferência (pendente)',
                            isset($r['usr']) ? $r['usr'] : '', implode(' · ', $det), 'verde');
                    }
                } catch (Exception $e) { /* tolera */ }
            }
        }

        /* FASE 3b: LACRES/DISPLAY da conferencia — conferencia_pacotes_lacres */
        if (audTabelaExiste($pdo, 'conferencia_pacotes_lacres')) {
            $cL = audPickCol($pdo, 'conferencia_pacotes_lacres', array('lote', 'nlote'));
            if ($cL) {
                $cEtq = audPickCol($pdo, 'conferencia_pacotes_lacres', array('etiqueta_correios'));
                $cIipr = audPickCol($pdo, 'conferencia_pacotes_lacres', array('lacre_iipr'));
                $cCorr = audPickCol($pdo, 'conferencia_pacotes_lacres', array('lacre_correios'));
                $sel = array("`$cL` AS lote");
                if ($cEtq) $sel[] = "`$cEtq` AS etq";
                if ($cIipr) $sel[] = "`$cIipr` AS iipr";
                if ($cCorr) $sel[] = "`$cCorr` AS corr";
                try {
                    $st = $pdo->prepare("SELECT " . implode(', ', $sel) . " FROM conferencia_pacotes_lacres WHERE `$cL` IN ($inLote)");
                    $st->execute($cands);
                    while ($r = $st->fetch()) {
                        if (isset($r['etq']) && trim((string)$r['etq']) !== '') {
                            $etiquetas[preg_replace('/\s+/', '', (string)$r['etq'])] = 1;
                        }
                    }
                } catch (Exception $e) { /* tolera */ }
            }
        }

        /* FASE 4: FECHAMENTO / OFICIO — ciDespachoLotes (+ ciDespachos) */
        $idsDespacho = array();
        if (audTabelaExiste($pdo, 'ciDespachoLotes')) {
            $cL = audPickCol($pdo, 'ciDespachoLotes', array('lote', 'nlote'));
            if ($cL) {
                $cId = audPickCol($pdo, 'ciDespachoLotes', array('id_despacho'));
                $cData = audPickCol($pdo, 'ciDespachoLotes', array('data_despacho_correios', 'data_carga', 'data', 'criado_em'));
                $cUser = audPickCol($pdo, 'ciDespachoLotes', array('despachado_por', 'usuario', 'login'));
                $cPosto = audPickCol($pdo, 'ciDespachoLotes', array('posto', 'nposto'));
                $cReg = audPickCol($pdo, 'ciDespachoLotes', array('regional'));
                $cEtq = audPickCol($pdo, 'ciDespachoLotes', array('etiqueta_correios'));
                $cIipr = audPickCol($pdo, 'ciDespachoLotes', array('etiquetaiipr', 'lacre_iipr'));
                $cCorr = audPickCol($pdo, 'ciDespachoLotes', array('etiquetacorreios', 'lacre_correios'));
                $sel = array("`$cL` AS lote");
                if ($cId) $sel[] = "`$cId` AS idd";
                if ($cData) $sel[] = "`$cData` AS dt";
                if ($cUser) $sel[] = "`$cUser` AS usr";
                if ($cPosto) $sel[] = "`$cPosto` AS posto";
                if ($cReg) $sel[] = "`$cReg` AS regional";
                if ($cEtq) $sel[] = "`$cEtq` AS etq";
                if ($cIipr) $sel[] = "`$cIipr` AS iipr";
                if ($cCorr) $sel[] = "`$cCorr` AS corr";
                try {
                    $st = $pdo->prepare("SELECT " . implode(', ', $sel) . " FROM ciDespachoLotes WHERE `$cL` IN ($inLote)");
                    $st->execute($cands);
                    while ($r = $st->fetch()) {
                        if (isset($r['idd']) && (string)$r['idd'] !== '') $idsDespacho[(string)$r['idd']] = 1;
                        if (isset($r['etq']) && trim((string)$r['etq']) !== '') {
                            $etiquetas[preg_replace('/\s+/', '', (string)$r['etq'])] = 1;
                        }
                        $det = array();
                        if (isset($r['idd'])) $det[] = 'Ofício #' . eAUD($r['idd']);
                        if (isset($r['posto'])) $det[] = 'Posto ' . eAUD($r['posto']);
                        if (isset($r['regional'])) $det[] = 'Regional ' . eAUD($r['regional']);
                        if (isset($r['iipr']) && trim((string)$r['iipr']) !== '') $det[] = 'Lacre IIPR ' . eAUD($r['iipr']);
                        if (isset($r['corr']) && trim((string)$r['corr']) !== '') $det[] = 'Lacre Correios ' . eAUD($r['corr']);
                        if (isset($r['etq']) && trim((string)$r['etq']) !== '') $det[] = 'Display ' . eAUD($r['etq']);
                        audAdd($eventos, isset($r['dt']) ? $r['dt'] : '',
                            'Fechado em ofício', isset($r['usr']) ? $r['usr'] : '', implode(' · ', $det), 'laranja',
                            isset($r['idd']) ? $r['idd'] : '');
                    }
                } catch (Exception $e) { /* tolera */ }
            }
        }

        /* FASE 4b: CAPA DO OFICIO — ciDespachos (data/usuario do oficio) */
        if (!empty($idsDespacho) && audTabelaExiste($pdo, 'ciDespachos')) {
            $cId = audPickCol($pdo, 'ciDespachos', array('id'));
            if ($cId) {
                $cData = audPickCol($pdo, 'ciDespachos', array('criado_em', 'data', 'datahora', 'created_at'));
                $cUser = audPickCol($pdo, 'ciDespachos', array('usuario', 'login', 'user'));
                $cGrupo = audPickCol($pdo, 'ciDespachos', array('grupo', 'tipo'));
                $ids = array_keys($idsDespacho);
                $sel = array("`$cId` AS id");
                if ($cData) $sel[] = "`$cData` AS dt";
                if ($cUser) $sel[] = "`$cUser` AS usr";
                if ($cGrupo) $sel[] = "`$cGrupo` AS grupo";
                try {
                    $st = $pdo->prepare("SELECT " . implode(', ', $sel) . " FROM ciDespachos WHERE `$cId` IN (" . audIn(count($ids)) . ")");
                    $st->execute($ids);
                    while ($r = $st->fetch()) {
                        $det = array('Ofício #' . eAUD($r['id']));
                        if (isset($r['grupo'])) $det[] = 'Grupo ' . eAUD($r['grupo']);
                        audAdd($eventos, isset($r['dt']) ? $r['dt'] : '',
                            'Ofício gerado', isset($r['usr']) ? $r['usr'] : '', implode(' · ', $det), 'laranja',
                            isset($r['id']) ? $r['id'] : '');
                    }
                } catch (Exception $e) { /* tolera */ }
            }
        }

        /* FASE 5: DISPLAY — ciMalotes (envio tipo=1 / devolucao tipo=2) */
        if (!empty($etiquetas) && audTabelaExiste($pdo, 'ciMalotes')) {
            $cLeit = audPickCol($pdo, 'ciMalotes', array('leitura'));
            if ($cLeit) {
                $cData = audPickCol($pdo, 'ciMalotes', array('data', 'datahora', 'criado_em'));
                $cUser = audPickCol($pdo, 'ciMalotes', array('login', 'usuario', 'user'));
                $cTipo = audPickCol($pdo, 'ciMalotes', array('tipo'));
                $cPosto = audPickCol($pdo, 'ciMalotes', array('posto', 'nposto'));
                $etqs = array_keys($etiquetas);
                $sel = array("`$cLeit` AS leit");
                if ($cData) $sel[] = "`$cData` AS dt";
                if ($cUser) $sel[] = "`$cUser` AS usr";
                if ($cTipo) $sel[] = "`$cTipo` AS tipo";
                if ($cPosto) $sel[] = "`$cPosto` AS posto";
                $ord = audPickCol($pdo, 'ciMalotes', array('id'));
                $sqlM = "SELECT " . implode(', ', $sel) . " FROM ciMalotes WHERE `$cLeit` IN (" . audIn(count($etqs)) . ")";
                if ($ord) $sqlM .= " ORDER BY `$ord`";
                try {
                    $st = $pdo->prepare($sqlM);
                    $st->execute($etqs);
                    while ($r = $st->fetch()) {
                        $tp = isset($r['tipo']) ? (int)$r['tipo'] : 0;
                        $fase = ($tp === 2) ? 'Display devolvido (retorno)' : 'Display enviado (saída)';
                        $cor = ($tp === 2) ? 'verde' : 'cinza';
                        $det = array();
                        if (isset($r['posto'])) $det[] = 'Posto ' . eAUD($r['posto']);
                        if (isset($r['leit'])) $det[] = 'Display ' . eAUD($r['leit']);
                        audAdd($eventos, isset($r['dt']) ? $r['dt'] : '', $fase,
                            isset($r['usr']) ? $r['usr'] : '', implode(' · ', $det), $cor);
                    }
                } catch (Exception $e) { /* tolera */ }
            }
        }

        /* FASE 6: ADIANTAMENTO — ciDespachoAdiantado */
        if (audTabelaExiste($pdo, 'ciDespachoAdiantado')) {
            $cL = audPickCol($pdo, 'ciDespachoAdiantado', array('lote', 'nlote'));
            if ($cL) {
                $cData = audPickCol($pdo, 'ciDespachoAdiantado', array('data_despacho', 'data_producao', 'data', 'criado_em'));
                $cPosto = audPickCol($pdo, 'ciDespachoAdiantado', array('posto', 'nposto'));
                $cObs = audPickCol($pdo, 'ciDespachoAdiantado', array('observacao', 'obs'));
                $cNum = audPickCol($pdo, 'ciDespachoAdiantado', array('numero_oficio', 'oficio'));
                $sel = array("`$cL` AS lote");
                if ($cData) $sel[] = "`$cData` AS dt";
                if ($cPosto) $sel[] = "`$cPosto` AS posto";
                if ($cObs) $sel[] = "`$cObs` AS obs";
                if ($cNum) $sel[] = "`$cNum` AS num";
                try {
                    $st = $pdo->prepare("SELECT " . implode(', ', $sel) . " FROM ciDespachoAdiantado WHERE `$cL` IN ($inLote)");
                    $st->execute($cands);
                    while ($r = $st->fetch()) {
                        $det = array();
                        if (isset($r['posto'])) $det[] = 'Posto ' . eAUD($r['posto']);
                        if (isset($r['num']) && trim((string)$r['num']) !== '') $det[] = 'Ofício ' . eAUD($r['num']);
                        if (isset($r['obs']) && trim((string)$r['obs']) !== '') $det[] = eAUD($r['obs']);
                        audAdd($eventos, isset($r['dt']) ? $r['dt'] : '',
                            'Adiantado', '', implode(' · ', $det), 'vermelho');
                    }
                } catch (Exception $e) { /* tolera */ }
            }
        }

        /* Ordena por FASE (rank) e, dentro de cada fase, por data/hora (asc).
           Eventos sem data vao para o fim da propria fase. */
        usort($eventos, 'audCmpEventos');

        /* T7: identifica os ciclos de oficio (cada id_despacho = um ciclo) e escolhe
           qual exibir. Padrao = mais recente (maior id). ?ciclo=ID forca outro;
           ?ciclo=0 (ou "todos") mostra todos. Fail-open: sem ciclos, mostra tudo. */
        foreach ($eventos as $ev) {
            if ($ev['despacho'] === '') continue;
            $idC = (int)$ev['despacho'];
            if ($idC <= 0) continue;
            if (!isset($ciclos[$idC])) $ciclos[$idC] = '—';
            if ($ciclos[$idC] === '—' && $ev['disp'] !== '—') $ciclos[$idC] = $ev['disp'];
        }
        if (!empty($ciclos)) {
            $idsCiclo = array_keys($ciclos);
            rsort($idsCiclo, SORT_NUMERIC);
            if (isset($_GET['ciclo'])) {
                $cg = trim((string)$_GET['ciclo']);
                if ($cg === '0' || strtolower($cg) === 'todos') {
                    $cicloSel = 0;
                } else {
                    $cgi = (int)$cg;
                    $cicloSel = isset($ciclos[$cgi]) ? $cgi : $idsCiclo[0];
                }
            } else {
                $cicloSel = $idsCiclo[0];
            }
        }
    }
}

function audFaseRank($fase) {
    $f = (string)$fase;
    if (strpos($f, 'Produção') !== false)   return 1;
    if (strpos($f, 'Triado') !== false)     return 2;
    if (strpos($f, 'Conferido') !== false || strpos($f, 'Conferência') !== false) return 3;
    if (strpos($f, 'Fechado em ofício') !== false) return 4;
    if (strpos($f, 'Ofício gerado') !== false)     return 5;
    if (strpos($f, 'Display enviado') !== false)   return 6;
    if (strpos($f, 'Display devolvido') !== false) return 7;
    if (strpos($f, 'Adiantado') !== false)  return 8;
    return 9;
}

function audCmpEventos($a, $b) {
    $ra = audFaseRank($a['fase']);
    $rb = audFaseRank($b['fase']);
    if ($ra !== $rb) return ($ra < $rb) ? -1 : 1;
    if ($a['sort'] === $b['sort']) return 0;
    return ($a['sort'] < $b['sort']) ? -1 : 1;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Auditoria de Lote v2.0.5</title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; font-family: "Trebuchet MS", "Segoe UI", Arial, sans-serif; background: #f4f6f9; color: #243240; }
  .topbar { background: #0b1a2e; color: #fff; padding: 10px 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .topbar h1 { font-size: 16px; font-weight: 700; flex: 1; margin: 0; }
  .topbar a.home { color: #90caf9; font-size: 12px; text-decoration: none; }
  .topbar .ver { font-size: 11px; color: #9fb3c8; background: #13294a; padding: 2px 8px; border-radius: 10px; }
  .wrap { max-width: 920px; margin: 0 auto; padding: 18px 16px 60px; }
  .busca { background: #fff; border: 1px solid #dde4ec; border-radius: 10px; padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
  .busca label { display: block; font-size: 12px; color: #5a6b7c; margin-bottom: 6px; }
  .busca-row { display: flex; gap: 8px; flex-wrap: wrap; }
  .busca input[type=text] { flex: 1; min-width: 200px; padding: 10px 12px; border: 1px solid #cdd6e0; border-radius: 8px; font-size: 15px; font-family: monospace; }
  .busca button { padding: 10px 18px; border: 0; background: #1565c0; color: #fff; border-radius: 8px; font-size: 14px; cursor: pointer; }
  .busca button:hover { background: #0d47a1; }
  .aviso { background: #fff3cd; border: 1px solid #ffe08a; color: #7a5a00; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
  .resumo { background: #e8f0fe; border: 1px solid #c5d6f5; border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 14px; }
  .resumo b { color: #0d47a1; }
  .vazio { background: #fff; border: 1px dashed #cdd6e0; border-radius: 10px; padding: 28px; text-align: center; color: #7a8a99; }
  .tl { list-style: none; margin: 0; padding: 0; position: relative; }
  .tl:before { content: ""; position: absolute; left: 18px; top: 6px; bottom: 6px; width: 2px; background: #dde4ec; }
  .tl li { position: relative; padding: 0 0 14px 46px; }
  .tl .dot { position: absolute; left: 11px; top: 4px; width: 16px; height: 16px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 1px #cdd6e0; background: #90a4ae; }
  .tl .card { background: #fff; border: 1px solid #e1e8f0; border-radius: 8px; padding: 10px 12px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
  .tl .quando { font-size: 12px; color: #5a6b7c; }
  .tl .fase { font-weight: 700; font-size: 14px; margin: 2px 0; }
  .tl .resp { font-size: 12px; color: #37474f; }
  .tl .det { font-size: 12px; color: #546170; margin-top: 4px; word-break: break-word; }
  .azul .dot { background: #1565c0; } .azul .fase { color: #0d47a1; }
  .roxo .dot { background: #7e57c2; } .roxo .fase { color: #5e35b1; }
  .verde .dot { background: #2e7d32; } .verde .fase { color: #1b5e20; }
  .laranja .dot { background: #ef6c00; } .laranja .fase { color: #e65100; }
  .cinza .dot { background: #607d8b; } .cinza .fase { color: #455a64; }
  .vermelho .dot { background: #c62828; } .vermelho .fase { color: #b71c1c; }
  .legenda { font-size: 11px; color: #7a8a99; margin-top: 16px; line-height: 1.6; }
  .ciclos { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 14px; }
  .ciclos-tit { font-size: 12px; color: #5a6b7c; font-weight: 700; }
  .ciclo-link { font-size: 12px; text-decoration: none; color: #1565c0; background: #eef3fb; border: 1px solid #cdd9ec; padding: 5px 10px; border-radius: 14px; }
  .ciclo-link:hover { background: #e1ebf9; }
  .ciclo-link.ativo { background: #1565c0; color: #fff; border-color: #1565c0; font-weight: 700; }
</style>
</head>
<body>
<div class="topbar">
  <a class="home" href="inicio.php">&#8592; Início</a>
  <h1>Auditoria de Lote</h1>
  <span class="ver">v2.0.5</span>
</div>

<div class="wrap">

  <form class="busca" method="get" action="auditoria_lote.php">
    <label for="lote">Número / código de barras do lote</label>
    <div class="busca-row">
      <input type="text" id="lote" name="lote" value="<?php echo eAUD($lotePesq); ?>" placeholder="Digite ou bipe o lote" autofocus>
      <button type="submit">Pesquisar</button>
    </div>
  </form>

  <?php if (!$dbOk): ?>
    <div class="aviso">
      Não foi possível conectar ao banco de dados agora. A auditoria precisa do banco
      <b>controle</b> (servidor da rede interna). Tente novamente dentro da rede do Poupatempo.
    </div>
  <?php endif; ?>

  <?php if ($pesquisou && $dbOk): ?>
    <?php if ($resumo['posto'] !== '' || $resumo['regional'] !== '' || $resumo['quantidade'] !== ''): ?>
      <div class="resumo">
        Lote <b><?php echo eAUD($lotePesq); ?></b>
        <?php if ($resumo['posto'] !== ''): ?> · Posto <b><?php echo eAUD($resumo['posto']); ?></b><?php endif; ?>
        <?php if ($resumo['regional'] !== ''): ?> · Regional <b><?php echo eAUD($resumo['regional']); ?></b><?php endif; ?>
        <?php if ($resumo['quantidade'] !== ''): ?> · Qtd <b><?php echo eAUD($resumo['quantidade']); ?></b><?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (empty($eventos)): ?>
      <div class="vazio">Nenhum acontecimento encontrado para o lote <b><?php echo eAUD($lotePesq); ?></b>.</div>
    <?php else: ?>
      <?php if (count($ciclos) > 1): ?>
        <div class="ciclos">
          <span class="ciclos-tit">Ofícios deste lote:</span>
          <?php $idsNav = array_keys($ciclos); rsort($idsNav, SORT_NUMERIC); ?>
          <?php foreach ($idsNav as $idN): ?>
            <a class="ciclo-link<?php echo ($idN === $cicloSel) ? ' ativo' : ''; ?>" href="auditoria_lote.php?lote=<?php echo urlencode($lotePesq); ?>&amp;ciclo=<?php echo (int)$idN; ?>">Ofício #<?php echo (int)$idN; ?><?php if ($ciclos[$idN] !== '—'): ?> · <?php echo eAUD($ciclos[$idN]); ?><?php endif; ?></a>
          <?php endforeach; ?>
          <a class="ciclo-link<?php echo ($cicloSel === 0) ? ' ativo' : ''; ?>" href="auditoria_lote.php?lote=<?php echo urlencode($lotePesq); ?>&amp;ciclo=0">Todos</a>
        </div>
      <?php endif; ?>
      <ul class="tl">
        <?php foreach ($eventos as $ev): ?>
          <?php if ($cicloSel > 0 && $ev['despacho'] !== '' && (int)$ev['despacho'] !== $cicloSel) continue; ?>
          <li class="<?php echo eAUD($ev['cor']); ?>">
            <span class="dot"></span>
            <div class="card">
              <div class="quando"><?php echo eAUD($ev['disp']); ?></div>
              <div class="fase"><?php echo eAUD($ev['fase']); ?></div>
              <?php if ($ev['resp'] !== ''): ?>
                <div class="resp">Responsável: <?php echo eAUD($ev['resp']); ?></div>
              <?php endif; ?>
              <?php if ($ev['detalhe'] !== ''): ?>
                <div class="det"><?php echo $ev['detalhe']; ?></div>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="legenda">
        As fases aparecem agrupadas por etapa (Produção → Triagem → Conferência → Ofício → Display → Adiantamento)
        e, dentro de cada etapa, por data/hora. Eventos sem data registrada aparecem ao final da etapa.
        Quando o lote tem mais de um ofício, mostramos por padrão o ciclo mais recente — use os botões acima
        para ver os anteriores ou "Todos". A linha do tempo só mostra as etapas que existem no banco para este lote.
      </div>
    <?php endif; ?>
  <?php elseif (!$pesquisou): ?>
    <div class="vazio">Digite ou bipe o número do lote acima para ver a linha do tempo completa.</div>
  <?php endif; ?>

</div>
</body>
</html>
