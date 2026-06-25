<?php
// VERSAO SISTEMA: 2.0.0 - 2026-05-26
header('Cache-Control: no-cache, no-store, must-revalidate');
session_start();

define('DB_HOST', (getenv('DB_HOST') ?: '10.15.61.169'));
define('DB_NAME', (getenv('DB_NAME') ?: 'controle'));
define('DB_USER', (getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat')));
define('DB_PASS', (getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256')));

function normalizarUtf8($s) {
    $s = (string)$s;
    if ($s === '' || preg_match('//u', $s)) return $s;
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8','UTF-8//IGNORE',$s);
        if ($t !== false && $t !== '') return $t;
    }
    return $s;
}
function e($s) { return htmlspecialchars(normalizarUtf8($s), ENT_QUOTES, 'UTF-8'); }
function dataBr($d) {
    $d = trim((string)$d);
    if ($d === '') return '';
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return ($dt===false) ? $d : $dt->format('d/m/Y');
}
function diasAtras($d) {
    $dt = DateTime::createFromFormat('Y-m-d', trim((string)$d));
    if ($dt===false) return '?';
    $hoje = new DateTime('today');
    $diff = $hoje->diff($dt);
    return (int)$diff->days;
}

/* ── CONEXÃO ── */
$dbOk = false; $mensagem = ''; $mensagem_tipo = '';
$ultimo_movimento = array();
$responsavel_salvo = isset($_SESSION['ultimo_responsavel_devolucao'])
    ? trim((string)$_SESSION['ultimo_responsavel_devolucao']) : '';

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $dbOk = true;
} catch (Exception $ex) {
    $mensagem = 'Falha ao conectar no banco.'; $mensagem_tipo = 'erro';
}

/* ── FUNÇÕES DB ── */
// Verifica (com cache) se uma tabela existe — usado para o filtro de inventario.
function tabelaExisteDev($pdo, $tabela) {
    static $cache = array();
    if (isset($cache[$tabela])) return $cache[$tabela];
    $existe = false;
    try {
        $r = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabela));
        $existe = ($r && $r->fetch()) ? true : false;
    } catch (Exception $e) { $existe = false; }
    $cache[$tabela] = $existe;
    return $existe;
}

// Aceita int (compat. antigo = dias) ou array de opcoes de filtro.
function normalizarOpcoesTransito($opts) {
    if (is_array($opts)) return $opts;
    return array('dias' => (int)$opts);
}

// Monta as clausulas WHERE extras do filtro "Em Transito" e preenche $params.
// Opcoes: dias, data_ini (YYYY-MM-DD), data_fim (YYYY-MM-DD),
//         com_retorno (1 = so quem ja recebeu tipo=2 alguma vez),
//         excluir_inv (1 = exclui quem esta no inventario salvo).
function filtrosTransitoSql($pdo, $opts, &$params) {
    $o = normalizarOpcoesTransito($opts);
    $sql = '';
    $di = isset($o['data_ini']) ? trim((string)$o['data_ini']) : '';
    $df = isset($o['data_fim']) ? trim((string)$o['data_fim']) : '';
    $dias = isset($o['dias']) ? (int)$o['dias'] : 0;

    // Data inicial explicita tem prioridade sobre o atalho de dias.
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $di)) {
        $sql .= " AND m1.data >= ?"; $params[] = $di . ' 00:00:00';
    } elseif ($dias > 0) {
        $sql .= " AND m1.data >= DATE_SUB(CURDATE(), INTERVAL " . $dias . " DAY)";
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $df)) {
        $sql .= " AND m1.data <= ?"; $params[] = $df . ' 23:59:59';
    }
    // So traz quem ja teve retorno (tipo=2) registrado alguma vez.
    if (!empty($o['com_retorno'])) {
        $sql .= " AND EXISTS(SELECT 1 FROM ciMalotes mr WHERE mr.leitura = m1.leitura AND mr.tipo = 2)";
    }
    // Exclui displays que estao no inventario fisico salvo (= ja voltaram).
    if (!empty($o['excluir_inv']) && tabelaExisteDev($pdo, 'ciInventarioDisplays')) {
        $sql .= " AND m1.leitura NOT IN (SELECT leitura FROM ciInventarioDisplays)";
    }
    return $sql;
}
function contarTransito($pdo, $opts = 0) {
    $params = array();
    $extra = filtrosTransitoSql($pdo, $opts, $params);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ciMalotes m1
                      WHERE m1.tipo=1$extra
                      AND m1.id = (SELECT MAX(m3.id) FROM ciMalotes m3 WHERE m3.leitura=m1.leitura AND m3.tipo=1)
                      AND NOT EXISTS(
                        SELECT 1 FROM ciMalotes m2 WHERE m2.leitura=m1.leitura AND m2.tipo=2 AND m2.data>=m1.data)");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
function buscarTransito($pdo, $limite, $opts = 0) {
    $params = array();
    $extra = filtrosTransitoSql($pdo, $opts, $params);
    $stmt = $pdo->prepare("SELECT m1.leitura, m1.posto, m1.login, DATE(m1.data) AS data_mov,
                           (SELECT cdl.lote FROM ciDespachoLotes cdl WHERE cdl.etiqueta_correios=m1.leitura ORDER BY cdl.id DESC LIMIT 1) AS lote,
                           (SELECT COUNT(*) FROM ciMalotes mr WHERE mr.leitura=m1.leitura AND mr.tipo=2) AS qt_retornos
                           FROM ciMalotes m1
                           WHERE m1.tipo=1$extra
                           AND m1.id = (SELECT MAX(m3.id) FROM ciMalotes m3 WHERE m3.leitura=m1.leitura AND m3.tipo=1)
                           AND NOT EXISTS(
                             SELECT 1 FROM ciMalotes m2 WHERE m2.leitura=m1.leitura AND m2.tipo=2 AND m2.data>=m1.data)
                           ORDER BY m1.data DESC, m1.id DESC LIMIT " . (int)$limite);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
function buscarUltimosEnvios($pdo, $limite) {
    $stmt = $pdo->prepare("SELECT leitura,posto,login,DATE(data) AS data_mov FROM ciMalotes
                           WHERE tipo=1 ORDER BY id DESC LIMIT " . (int)$limite);
    $stmt->execute(); return $stmt->fetchAll();
}
function buscarUltimosRecebimentos($pdo, $limite) {
    $stmt = $pdo->prepare("SELECT leitura,posto,login,DATE(data) AS data_mov FROM ciMalotes
                           WHERE tipo=2 ORDER BY id DESC LIMIT " . (int)$limite);
    $stmt->execute(); return $stmt->fetchAll();
}
function buscarPorPosto($pdo, $posto) {
    $stmt = $pdo->prepare("SELECT tipo,leitura,login,DATE(data) AS data_mov FROM ciMalotes
                           WHERE posto=? ORDER BY id DESC LIMIT 100");
    $stmt->execute(array($posto)); return $stmt->fetchAll();
}
function buscarStatusLote($pdo, $lote) {
    // 1) Combos (despacho, posto) unicos onde este lote aparece
    //    GROUP BY garante 1 linha por combo (sem multiplicar quando etiqueta_correios varia)
    $st = $pdo->prepare(
        "SELECT id_despacho, posto, "
        ."  COALESCE(MAX(NULLIF(TRIM(etiqueta_correios),'')),'') AS etiqueta_lote "
        ."FROM ciDespachoLotes WHERE lote = ? "
        ."GROUP BY id_despacho, posto "
        ."ORDER BY id_despacho DESC, posto ASC"
    );
    $st->execute(array($lote));
    $combos = $st->fetchAll();

    // 2) Para cada combo, busca TODAS etiquetas distintas em ciDespachoItens
    //    (1 etiqueta = atribuicao unica; 2+ = candidatas / ambiguas)
    $stItens = $pdo->prepare(
        "SELECT DISTINCT etiqueta_correios FROM ciDespachoItens "
        ."WHERE id_despacho = ? "
        ."  AND LPAD(CAST(posto AS UNSIGNED),3,'0') = LPAD(CAST(? AS UNSIGNED),3,'0') "
        ."  AND etiqueta_correios IS NOT NULL AND TRIM(etiqueta_correios) <> ''"
    );
    $stEnvio = $pdo->prepare("SELECT id, posto, login, DATE(data) AS data_mov FROM ciMalotes WHERE leitura=? AND tipo=1 ORDER BY id DESC LIMIT 1");
    $stRet   = $pdo->prepare("SELECT DATE(data) AS data_mov FROM ciMalotes WHERE leitura=? AND tipo=2 AND id > ? ORDER BY id DESC LIMIT 1");

    $result = array();
    foreach ($combos as $combo) {
        $stItens->execute(array($combo['id_despacho'], $combo['posto']));
        $etiquetas_oficio = array();
        while ($rE = $stItens->fetch(PDO::FETCH_ASSOC)) {
            $val = trim((string)$rE['etiqueta_correios']);
            if ($val !== '') $etiquetas_oficio[] = $val;
        }
        // Fallback: usa a etiqueta de ciDespachoLotes se nada em ciDespachoItens
        if (empty($etiquetas_oficio) && trim((string)$combo['etiqueta_lote']) !== '') {
            $etiquetas_oficio[] = trim((string)$combo['etiqueta_lote']);
        }
        $eh_ambiguo = (count($etiquetas_oficio) > 1);

        if (empty($etiquetas_oficio)) {
            $result[] = array(
                'leitura'=>null, 'posto'=>null, 'enviado_por'=>null,
                'data_envio'=>null, 'status'=>'Sem etiqueta', 'data_retorno'=>null,
                'id_despacho'=>$combo['id_despacho'], 'posto_oficio'=>$combo['posto'],
                'ambiguo'=>false, 'qtde_candidatas'=>0
            );
            continue;
        }

        foreach ($etiquetas_oficio as $leitura) {
            $stEnvio->execute(array($leitura));
            $envio = $stEnvio->fetch(PDO::FETCH_ASSOC);
            $status = 'Nao enviado'; $data_envio = null; $posto = null; $enviado_por = null; $data_retorno = null;
            if ($envio) {
                $posto = $envio['posto']; $enviado_por = $envio['login']; $data_envio = $envio['data_mov'];
                $stRet->execute(array($leitura, $envio['id']));
                $retorno = $stRet->fetch(PDO::FETCH_ASSOC);
                $status = $retorno ? 'Retornou' : 'Em transito';
                $data_retorno = $retorno ? $retorno['data_mov'] : null;
            }
            $result[] = array(
                'leitura'=>$leitura, 'posto'=>$posto, 'enviado_por'=>$enviado_por,
                'data_envio'=>$data_envio, 'status'=>$status, 'data_retorno'=>$data_retorno,
                'id_despacho'=>$combo['id_despacho'], 'posto_oficio'=>$combo['posto'],
                'ambiguo'=>$eh_ambiguo, 'qtde_candidatas'=>count($etiquetas_oficio)
            );
        }
    }
    return $result;
}
function buscarPorLote($pdo, $lote) {
    // Inclui etiquetas vindas de ciDespachoLotes.etiqueta_correios E de ciDespachoItens
    // (mesmo despacho + mesmo posto). Cobre o caso de oficio com 2+ linhas por posto.
    $stmt = $pdo->prepare(
        "SELECT DISTINCT cm.tipo, cm.leitura, cm.posto, cm.login, DATE(cm.data) AS data_mov "
        ."FROM ciMalotes cm "
        ."WHERE cm.leitura IN ("
        ."  SELECT etiqueta_correios FROM ciDespachoLotes "
        ."  WHERE lote = ? AND etiqueta_correios IS NOT NULL AND TRIM(etiqueta_correios) <> '' "
        ."  UNION "
        ."  SELECT cdi.etiqueta_correios FROM ciDespachoItens cdi "
        ."  INNER JOIN ciDespachoLotes cdl "
        ."    ON cdl.id_despacho = cdi.id_despacho "
        ."   AND LPAD(CAST(cdl.posto AS UNSIGNED),3,'0') = LPAD(CAST(cdi.posto AS UNSIGNED),3,'0') "
        ."  WHERE cdl.lote = ? AND cdi.etiqueta_correios IS NOT NULL AND TRIM(cdi.etiqueta_correios) <> '' "
        .") "
        ."ORDER BY cm.id DESC"
    );
    $stmt->execute(array($lote, $lote));
    return $stmt->fetchAll();
}
function resolverPostoCompleto($pdo, $leitura) {
    $posto = resolverPosto($pdo, $leitura);
    $nome = '';
    $eh_central = false;
    if ($posto !== null && $posto !== '') {
        try {
            $s = $pdo->prepare(
                "SELECT nome, entrega FROM ciRegionais "
                ."WHERE LPAD(CAST(posto AS UNSIGNED),3,'0') = LPAD(CAST(? AS UNSIGNED),3,'0') "
                ."ORDER BY id DESC LIMIT 1"
            );
            $s->execute(array($posto));
            $r = $s->fetch(PDO::FETCH_ASSOC);
            if ($r) {
                $nome = trim((string)$r['nome']);
                $entrega = strtoupper(trim((string)$r['entrega']));
                if (stripos($nome, 'CENTRAL') !== false || stripos($entrega, 'CENTRAL') !== false) {
                    $eh_central = true;
                }
            }
        } catch (Exception $e) {}
    }
    return array('posto'=>$posto, 'nome'=>$nome, 'eh_central'=>$eh_central);
}

function resolverPosto($pdo, $leitura) {
    try {
        // Tentar por leitura exata primeiro
        $s = $pdo->prepare('SELECT posto FROM cadastroMalotes WHERE leitura=? ORDER BY id DESC LIMIT 1');
        $s->execute(array($leitura));
        $p = $s->fetchColumn();
        if ($p !== false && trim((string)$p) !== '') {
            return trim((string)$p);
        }
        // Fallback: buscar por CEP + sequencial (display pode ter leitura diferente no cadastro)
        if (strlen($leitura) >= 13) {
            $cep = substr($leitura, 0, 8);
            $seq = substr($leitura, -5);
            $s2 = $pdo->prepare('SELECT posto FROM cadastroMalotes WHERE cep=? AND sequencial=? ORDER BY id DESC LIMIT 1');
            $s2->execute(array($cep, $seq));
            $p2 = $s2->fetchColumn();
            if ($p2 !== false && trim((string)$p2) !== '') {
                return trim((string)$p2);
            }
            // Último fallback: só pelo CEP
            $s3 = $pdo->prepare('SELECT posto FROM cadastroMalotes WHERE cep=? ORDER BY id DESC LIMIT 1');
            $s3->execute(array($cep));
            $p3 = $s3->fetchColumn();
            if ($p3 !== false && trim((string)$p3) !== '') {
                return trim((string)$p3);
            }
        }
        // nenhuma das buscas retornou resultado
    } catch (Exception $ex) {}
    return null;
}
function registrarMovimento($pdo, $leitura_raw, $responsavel, $tipo, &$mensagem, &$mensagem_tipo, &$resp_salvo, &$ult_mov) {
    $leitura = preg_replace('/\D+/','',(string)$leitura_raw);
    if ($responsavel === '') { $mensagem='Informe o responsavel.'; $mensagem_tipo='erro'; return; }
    if (strlen($leitura) !== 35) { $mensagem='Etiqueta invalida — 35 digitos.'; $mensagem_tipo='erro'; return; }
    $cep = substr($leitura,0,8); $seq = substr($leitura,-5);
    $posto = resolverPosto($pdo,$leitura);
    $aviso = '';
    if ($tipo===1) {
        // Verificar se display já foi enviado hoje
        $cDup = $pdo->prepare('SELECT COUNT(*) FROM ciMalotes WHERE leitura=? AND tipo=1 AND DATE(data)=CURDATE()');
        $cDup->execute(array($leitura));
        if ((int)$cDup->fetchColumn() > 0) {
            $mensagem = 'Display ja registrado como enviado hoje.';
            $mensagem_tipo = 'warn';
            // Retornar info do registro existente para mostrar posto
            $rowExist = $pdo->prepare('SELECT posto, login FROM ciMalotes WHERE leitura=? AND tipo=1 AND DATE(data)=CURDATE() ORDER BY id DESC LIMIT 1');
            $rowExist->execute(array($leitura));
            $rowE = $rowExist->fetch(PDO::FETCH_ASSOC);
            $ult_mov = array('tipo'=>1,'leitura'=>$leitura,'posto'=>($rowE?$rowE['posto']:$posto),'responsavel'=>($rowE?$rowE['login']:$responsavel),'data'=>date('d/m/Y'));
            return;
        }
    }
    if ($tipo===2) {
        $c1 = $pdo->prepare('SELECT COUNT(*) FROM ciMalotes WHERE leitura=? AND tipo=1');
        $c1->execute(array($leitura));
        if ((int)$c1->fetchColumn()===0) $aviso='Aviso: sem registro de envio, recebimento gravado mesmo assim. ';
        $c2 = $pdo->prepare('SELECT COUNT(*) FROM ciMalotes WHERE leitura=? AND tipo=2 AND DATE(data)=CURDATE()');
        $c2->execute(array($leitura));
        if ((int)$c2->fetchColumn()>0) { $mensagem='Etiqueta ja recebida hoje.'; $mensagem_tipo='warn'; return; }
    }
    $pdo->prepare('INSERT INTO ciMalotes (leitura,data,observacao,login,tipo,cep,sequencial,posto) VALUES (?,?,?,?,?,?,?,?)')
        ->execute(array($leitura,date('Y-m-d'),null,$responsavel,$tipo,$cep,$seq,$posto));
    $_SESSION['ultimo_responsavel_devolucao'] = $responsavel;
    $resp_salvo = $responsavel;
    $mensagem = $aviso . (($tipo===1) ? 'Envio registrado — Posto: '.($posto?$posto:'?') : 'Recebimento registrado com sucesso.');
    $mensagem_tipo = ($aviso !== '') ? 'warn' : 'ok';
    $ult_mov = array('tipo'=>$tipo,'leitura'=>$leitura,'posto'=>$posto,'responsavel'=>$responsavel,'data'=>date('d/m/Y'));
}

/* ── AÇÕES POST ── */
if ($dbOk && $_SERVER['REQUEST_METHOD']==='POST') {
    $acao        = isset($_POST['acao'])        ? trim((string)$_POST['acao'])        : '';
    $responsavel = isset($_POST['responsavel']) ? trim((string)$_POST['responsavel']) : '';

    if ($acao==='registrar_envio') {
        registrarMovimento($pdo,isset($_POST['leitura_envio'])?$_POST['leitura_envio']:'',$responsavel,1,$mensagem,$mensagem_tipo,$responsavel_salvo,$ultimo_movimento);
    } elseif ($acao==='registrar_recebimento') {
        registrarMovimento($pdo,isset($_POST['leitura_recebimento'])?$_POST['leitura_recebimento']:'',$responsavel,2,$mensagem,$mensagem_tipo,$responsavel_salvo,$ultimo_movimento);
    } elseif ($acao==='marcar_recebido') {
        $leit = preg_replace('/\D+/','',(string)(isset($_POST['leitura'])?$_POST['leitura']:''));
        if (strlen($leit)===35 && $responsavel!=='') {
            $c = $pdo->prepare('SELECT COUNT(*) FROM ciMalotes WHERE leitura=? AND tipo=2 AND DATE(data)=CURDATE()');
            $c->execute(array($leit));
            if ((int)$c->fetchColumn()===0) {
                $cep=substr($leit,0,8); $seq=substr($leit,-5);
                $posto=resolverPosto($pdo,$leit);
                $pdo->prepare('INSERT INTO ciMalotes (leitura,data,observacao,login,tipo,cep,sequencial,posto) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute(array($leit,date('Y-m-d'),'Fechado manualmente via painel',$responsavel,2,$cep,$seq,$posto));
                $mensagem='Etiqueta marcada como recebida.'; $mensagem_tipo='ok';
            } else {
                $mensagem='Ja recebida hoje.'; $mensagem_tipo='warn';
            }
        } else {
            $mensagem='Dados invalidos.'; $mensagem_tipo='erro';
        }
        if (isset($_POST['ajax'])&&$_POST['ajax']==='1') {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array('ok'=>($mensagem_tipo==='ok'||$mensagem_tipo==='warn'),'mensagem'=>$mensagem,'transito'=>contarTransito($pdo)));
            exit;
        }
    } elseif ($acao==='buscar_posto') {
        header('Content-Type: application/json; charset=UTF-8');
        $posto = preg_replace('/\D+/','',isset($_POST['posto'])?trim($_POST['posto']):'');
        $posto = str_pad($posto,3,'0',STR_PAD_LEFT);
        $rows  = ($posto!=='000') ? buscarPorPosto($pdo,$posto) : array();
        echo json_encode(array('ok'=>true,'rows'=>$rows,'posto'=>$posto));
        exit;
    } elseif ($acao==='buscar_lote') {
        header('Content-Type: application/json; charset=UTF-8');
        $lote = preg_replace('/\D+/','',isset($_POST['lote'])?trim($_POST['lote']):'');
        $rows   = ($lote!=='') ? buscarPorLote($pdo,$lote)   : array();
        $status = ($lote!=='') ? buscarStatusLote($pdo,$lote) : array();
        echo json_encode(array('ok'=>true,'rows'=>$rows,'lote'=>$lote,'status'=>$status));
        exit;
    } elseif ($acao==='buscar_pares') {
      header('Content-Type: application/json; charset=UTF-8');
      $leit = preg_replace('/\D+/','',(string)(isset($_POST['leitura'])?$_POST['leitura']:''));
      $limit = isset($_POST['limit']) && (int)$_POST['limit']>0 ? (int)$_POST['limit'] : 3;
      $pares = array();
      if (strlen($leit)===35) {
        $sql = "SELECT id,posto,login,DATE(data) AS data_mov FROM ciMalotes WHERE leitura=? AND tipo=1 ORDER BY id DESC LIMIT " . (int)$limit;
        $s = $pdo->prepare($sql);
        $s->execute(array($leit));
        $sends = $s->fetchAll(PDO::FETCH_ASSOC);
        $getReturn = $pdo->prepare("SELECT id,posto,login,DATE(data) AS data_mov FROM ciMalotes WHERE leitura=? AND tipo=2 AND id > ? ORDER BY id ASC LIMIT 1");
        $getLote  = $pdo->prepare("SELECT cdl.lote, cdl.id AS cdl_id, cdl.id_despacho, cd.grupo, COALESCE(cd.usuario,'') AS oficio_usuario, COALESCE(cd.criado_em,'') AS oficio_criado_em FROM ciDespachoLotes cdl LEFT JOIN ciDespachos cd ON cd.id = cdl.id_despacho WHERE cdl.etiqueta_correios=? ORDER BY cdl.id DESC LIMIT 1");
        foreach ($sends as $sd) {
          $ret = null;
          $getReturn->execute(array($leit, $sd['id']));
          $rrow = $getReturn->fetch(PDO::FETCH_ASSOC);
          if ($rrow) {
            $ret = array('id'=>$rrow['id'],'posto'=>$rrow['posto'],'usuario'=>$rrow['login'],'data'=>$rrow['data_mov']);
          }
          $getLote->execute(array($leit));
          $lrow = $getLote->fetch(PDO::FETCH_ASSOC);
          $pares[] = array('envio'=>array('id'=>$sd['id'],'posto'=>$sd['posto'],'usuario'=>$sd['login'],'data'=>$sd['data_mov']), 'retorno'=>$ret, 'lote'=>$lrow);
        }
      }
      echo json_encode(array('ok'=>true,'pares'=>$pares));
      exit;
    } elseif ($acao==='resolver_etiqueta') {
      header('Content-Type: application/json; charset=UTF-8');
      $leit = preg_replace('/\D+/','',(string)(isset($_POST['leitura'])?$_POST['leitura']:''));
      if (strlen($leit) !== 35) {
        echo json_encode(array('ok'=>false,'erro'=>'Etiqueta deve ter 35 digitos.'));
        exit;
      }
      $info = resolverPostoCompleto($pdo, $leit);
      $cR = $pdo->prepare('SELECT COUNT(*) FROM ciMalotes WHERE leitura=? AND tipo=2 AND DATE(data)=CURDATE()');
      $cR->execute(array($leit));
      $ja_recebida_hoje = ((int)$cR->fetchColumn() > 0);
      $cE = $pdo->prepare('SELECT id, posto, login, DATE(data) AS dt FROM ciMalotes WHERE leitura=? AND tipo=1 ORDER BY id DESC LIMIT 1');
      $cE->execute(array($leit));
      $ultEnvio = $cE->fetch(PDO::FETCH_ASSOC);
      echo json_encode(array(
        'ok'=>true, 'leitura'=>$leit,
        'posto'=>$info['posto'], 'nome'=>$info['nome'], 'eh_central'=>$info['eh_central'],
        'ja_recebida_hoje'=>$ja_recebida_hoje,
        'tem_envio'=>($ultEnvio?true:false),
        'ultimo_envio'=>$ultEnvio ? array('posto'=>$ultEnvio['posto'],'login'=>$ultEnvio['login'],'data'=>$ultEnvio['dt']) : null
      ));
      exit;
    } elseif ($acao==='gravar_lote_recebimentos') {
      header('Content-Type: application/json; charset=UTF-8');
      $resp = trim((string)(isset($_POST['responsavel'])?$_POST['responsavel']:''));
      if ($resp === '') { echo json_encode(array('ok'=>false,'erro'=>'Informe o responsavel.')); exit; }
      $jsonIn = isset($_POST['etiquetas']) ? (string)$_POST['etiquetas'] : '[]';
      $arr = json_decode($jsonIn, true);
      if (!is_array($arr)) { echo json_encode(array('ok'=>false,'erro'=>'Lista invalida.')); exit; }
      // aceita formato antigo (lista de strings) ou novo (lista de objetos {leitura, posto})
      $itens = array();
      foreach ($arr as $entry) {
        if (is_array($entry)) {
          $itens[] = array(
            'leitura' => isset($entry['leitura']) ? (string)$entry['leitura'] : '',
            'posto'   => isset($entry['posto'])   ? (string)$entry['posto']   : null
          );
        } else {
          $itens[] = array('leitura' => (string)$entry, 'posto' => null);
        }
      }
      $resultados = array(); $salvas = 0; $puladas = 0; $erros = 0;
      $stmtIns = $pdo->prepare('INSERT INTO ciMalotes (leitura,data,observacao,login,tipo,cep,sequencial,posto) VALUES (?,?,?,?,?,?,?,?)');
      $hoje = date('Y-m-d');
      // v2.0.0: dup-check em UMA unica query (em vez de 1 SELECT por etiqueta)
      // reduz drasticamente o tempo de resposta com lotes grandes.
      $etiquetasValidas = array();
      foreach ($itens as $it) {
        $e = preg_replace('/\D+/','',(string)$it['leitura']);
        if (strlen($e) === 35) { $etiquetasValidas[$e] = true; }
      }
      $duplicadasHoje = array();
      if (count($etiquetasValidas) > 0) {
        $listaEti = array_keys($etiquetasValidas);
        $placeholders = rtrim(str_repeat('?,', count($listaEti)), ',');
        $sqlDup = 'SELECT leitura FROM ciMalotes WHERE tipo=2 AND DATE(data)=CURDATE() AND leitura IN ('.$placeholders.')';
        $stmtDupAll = $pdo->prepare($sqlDup);
        $stmtDupAll->execute($listaEti);
        while ($rowDup = $stmtDupAll->fetch(PDO::FETCH_ASSOC)) {
          $duplicadasHoje[(string)$rowDup['leitura']] = true;
        }
      }
      try { $pdo->beginTransaction(); } catch (Exception $eTx) {}
      foreach ($itens as $it) {
        $eti = preg_replace('/\D+/','',(string)$it['leitura']);
        if (strlen($eti) !== 35) {
          $resultados[] = array('leitura'=>(string)$it['leitura'],'status'=>'erro','msg'=>'Invalida (precisa 35 digitos).');
          $erros++; continue;
        }
        if (isset($duplicadasHoje[$eti])) {
          $resultados[] = array('leitura'=>$eti,'status'=>'duplicada','msg'=>'Ja recebida hoje (nao gravada de novo).');
          $puladas++; continue;
        }
        // marca como duplicada para nao reinserir caso venha duplicada no proprio lote
        $duplicadasHoje[$eti] = true;
        // usa posto enviado pelo JS (ja resolvido). So consulta o BD se nao veio nada.
        $posto = trim((string)$it['posto']);
        if ($posto === '') {
          $info = resolverPostoCompleto($pdo, $eti);
          $posto = $info['posto'];
        }
        $cep = substr($eti,0,8); $seq = substr($eti,-5);
        try {
          $stmtIns->execute(array($eti, $hoje, null, $resp, 2, $cep, $seq, $posto));
          $resultados[] = array('leitura'=>$eti,'status'=>'ok','msg'=>'Salva','posto'=>$posto);
          $salvas++;
        } catch (Exception $ex) {
          $resultados[] = array('leitura'=>$eti,'status'=>'erro','msg'=>'Falha BD: '.$ex->getMessage());
          $erros++;
        }
      }
      $commitErro = '';
      try { if ($pdo->inTransaction()) $pdo->commit(); } catch (Exception $eC) {
        $commitErro = $eC->getMessage();
        try { $pdo->rollBack(); } catch (Exception $eR) {}
        // Se o commit falhou, nada foi persistido -> reportar com clareza
        echo json_encode(array(
          'ok'=>false,
          'erro'=>'Falha ao confirmar a gravacao (commit): '.$commitErro.' - nenhum registro foi salvo.',
          'salvas'=>0,'puladas'=>0,'erros'=>count($itens),
          'total'=>count($itens),'resultados'=>$resultados
        ));
        exit;
      }
      $_SESSION['ultimo_responsavel_devolucao'] = $resp;
      echo json_encode(array(
        'ok'=>true,'salvas'=>$salvas,'puladas'=>$puladas,'erros'=>$erros,
        'total'=>count($itens),'resultados'=>$resultados,'transito'=>contarTransito($pdo)
      ));
      exit;
    } elseif ($acao==='historico') {
      header('Content-Type: application/json; charset=UTF-8');
      $leit = preg_replace('/\D+/','',(string)(isset($_POST['leitura'])?$_POST['leitura']:''));
      $hist = array();
      $total = 0;
      $porPagina = 20;
      $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
      if ($pagina < 1) $pagina = 1;
      if (strlen($leit)===35) {
        $sc = $pdo->prepare("SELECT COUNT(*) FROM ciMalotes WHERE leitura=?");
        $sc->execute(array($leit));
        $total = (int)$sc->fetchColumn();
        $totalPaginas = $total > 0 ? (int)ceil($total / $porPagina) : 1;
        if ($pagina > $totalPaginas) $pagina = $totalPaginas;
        $offset = ($pagina - 1) * $porPagina;
        // Mais recentes primeiro
        $s=$pdo->prepare("SELECT tipo,login,DATE(data) AS data_mov,observacao FROM ciMalotes WHERE leitura=? ORDER BY data DESC, id DESC LIMIT " . (int)$porPagina . " OFFSET " . (int)$offset);
        $s->execute(array($leit)); $hist=$s->fetchAll();
      }
      echo json_encode(array('ok'=>true,'historico'=>$hist,'total'=>$total,'pagina'=>$pagina,'por_pagina'=>$porPagina));
      exit;
    }
    if (isset($_POST['ajax'])&&$_POST['ajax']==='1') {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array(
            'ok' => ($mensagem_tipo==='ok'||$mensagem_tipo==='warn'),
            'mensagem' => $mensagem, 'mensagem_tipo' => $mensagem_tipo,
            'ultimo_movimento' => $ultimo_movimento,
            'transito' => contarTransito($pdo)
        ));
        exit;
    }
}

/* ── DADOS ── */
$aba   = isset($_GET['aba']) ? trim($_GET['aba']) : 'operacao';
// Padrao: 60 dias (evita trazer registros muito antigos). Use ?dias=0 para "Todos".
if (isset($_GET['dias'])) {
    $filtro_dias = max(0, (int)$_GET['dias']);
} else {
    $filtro_dias = 60;
}
// Filtros adicionais do "Em Transito" (para reduzir falsos positivos).
$f_data_ini    = (isset($_GET['data_ini']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_ini'])) ? $_GET['data_ini'] : '';
$f_data_fim    = (isset($_GET['data_fim']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_fim'])) ? $_GET['data_fim'] : '';
$f_com_retorno = (isset($_GET['com_retorno']) && $_GET['com_retorno'] == '1') ? 1 : 0;
$f_excluir_inv = (isset($_GET['excluir_inv']) && $_GET['excluir_inv'] == '1') ? 1 : 0;
$opts_transito = array(
    'dias'        => $filtro_dias,
    'data_ini'    => $f_data_ini,
    'data_fim'    => $f_data_fim,
    'com_retorno' => $f_com_retorno,
    'excluir_inv' => $f_excluir_inv
);

$transito_count = 0; $lista_transito = array();
$ultimos_envios = array(); $ultimos_receb = array();
$inv_existe = false; $inv_total = 0;

if ($dbOk) {
    $transito_count = contarTransito($pdo, $opts_transito);
    if ($aba==='transito')      $lista_transito = buscarTransito($pdo,1000,$opts_transito);
    if ($aba==='envios')        $ultimos_envios  = buscarUltimosEnvios($pdo,100);
    if ($aba==='recebimentos')  $ultimos_receb   = buscarUltimosRecebimentos($pdo,100);
    if ($aba==='operacao') {
        $ultimos_envios = buscarUltimosEnvios($pdo,8);
        $ultimos_receb  = buscarUltimosRecebimentos($pdo,8);
    }
    // Inventario salvo (habilita o filtro "Excluir presentes no inventario").
    $inv_existe = tabelaExisteDev($pdo, 'ciInventarioDisplays');
    if ($inv_existe) {
        try { $inv_total = (int)$pdo->query("SELECT COUNT(*) FROM ciInventarioDisplays")->fetchColumn(); }
        catch (Exception $e) { $inv_total = 0; }
    }
}
$dias_label = array(0=>'Todos',30=>'30 dias',60=>'60 dias',90=>'90 dias');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Controle de Etiquetas Correios v1.0.12</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:"Trebuchet MS","Segoe UI",Arial,sans-serif;background:#eef2f7;color:#1a2b3c;min-height:100vh;}
a{color:#0b3d91;text-decoration:none;}
.topbar{background:#0b1a2e;color:#fff;padding:10px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.topbar h1{font-size:16px;font-weight:700;flex:1;}
.topbar a.home{color:#90caf9;font-size:12px;}
.abas{background:#fff;border-bottom:2px solid #d0dae4;display:flex;padding:0 16px;gap:0;overflow-x:auto;}
.aba{padding:10px 18px;font-size:12px;font-weight:700;color:#607080;border-bottom:3px solid transparent;cursor:pointer;white-space:nowrap;text-decoration:none;display:inline-block;}
.aba.ativa{color:#0b3d91;border-bottom-color:#0b3d91;}
.aba .badge{background:#e53935;color:#fff;border-radius:999px;padding:1px 6px;font-size:10px;margin-left:4px;}
.main{max-width:980px;margin:18px auto;padding:0 14px;}
.card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:14px;}
.card h2{font-size:14px;color:#0b1a2e;margin-bottom:14px;font-weight:700;}
.kpis{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;}
.kpi{background:#fff;border-radius:10px;padding:12px 18px;box-shadow:0 2px 8px rgba(0,0,0,.07);flex:1;min-width:120px;text-align:center;}
.kpi .k-label{font-size:11px;color:#607080;margin-bottom:4px;}
.kpi .k-val{font-size:26px;font-weight:700;color:#0b1a2e;}
.kpi.alerta .k-val{color:#c62828;}
.resp-bloco{margin-bottom:16px;}
.resp-bloco label{display:block;font-size:12px;font-weight:700;color:#3a5068;margin-bottom:6px;}
.input-resp{width:100%;padding:11px 14px;border:2px solid #b0c4d8;border-radius:8px;font-size:14px;transition:border-color .2s;}
.input-resp:focus{border-color:#0b3d91;outline:none;}
.input-resp.erro{border-color:#e53935;background:#fff8f8;}
.op-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
@media(max-width:640px){.op-grid{grid-template-columns:1fr;}}
.op-bloco{border-radius:10px;padding:16px;display:flex;flex-direction:column;gap:10px;}
.op-bloco.receb{background:#e8f5e9;border:2px solid #a5d6a7;}
.op-bloco.envio{background:#e3f2fd;border:2px solid #90caf9;}
.op-bloco h3{font-size:13px;font-weight:700;margin-bottom:2px;}
.op-bloco.receb h3{color:#1b5e20;}
.op-bloco.envio h3{color:#0d47a1;}
.op-bloco .sublabel{font-size:11px;color:#607080;margin-bottom:4px;}
.input-etiq{width:100%;padding:12px 14px;border:2px solid #b0c4d8;border-radius:8px;font-size:13px;font-family:"Courier New",Courier,monospace;letter-spacing:1px;transition:border-color .2s;}
.op-bloco.receb .input-etiq:focus{outline:none;border-color:#2e7d32;box-shadow:0 0 0 3px rgba(46,125,50,.12);}
.op-bloco.envio .input-etiq:focus{outline:none;border-color:#0d47a1;box-shadow:0 0 0 3px rgba(13,71,161,.12);}
.char-count{font-size:10px;color:#90a4ae;text-align:right;font-family:monospace;}
.status-live{padding:12px 16px;border-radius:10px;background:#e8f5e9;border:1px solid #a5d6a7;color:#1b5e20;font-size:14px;font-weight:600;min-height:46px;transition:all .2s;}
.status-live.erro{background:#ffebee;border-color:#ef9a9a;color:#b71c1c;}
.status-live.warn{background:#fff8e1;border-color:#ffe082;color:#7d4e00;}
.mov-box{background:#f0f8ff;border:1px solid #b3d4f5;border-radius:10px;padding:14px;display:none;}
.mov-box .mov-title{font-weight:700;font-size:13px;color:#0b3d91;margin-bottom:8px;}
.mov-box .mov-linha{font-size:12px;color:#3a5068;margin-bottom:3px;}
.tabela{width:100%;border-collapse:collapse;font-size:12px;}
.tabela th{background:#1a2b3c;color:#fff;padding:8px 10px;text-align:left;font-size:11px;}
.tabela td{padding:7px 10px;border-bottom:1px solid #eef2f7;vertical-align:middle;}
.tabela tr:hover td{background:#f5f8fc;}
.tabela .mono{font-family:"Courier New",Courier,monospace;font-size:11px;word-break:break-all;}
.badge-tip{display:inline-block;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;}
.badge-tip.t1{background:#e3f2fd;color:#0d47a1;}
.badge-tip.t2{background:#e8f5e9;color:#1b5e20;}
.badge-tip.transito{background:#fff8e1;color:#7d4e00;}
.hist-paginacao{display:flex;align-items:center;justify-content:center;gap:12px;margin-top:12px;}
.hist-pag-info{font-size:12px;font-weight:700;color:#3a5068;}
.btn-pag{padding:6px 14px;background:#0b3d91;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:12px;}
.btn-pag:disabled{background:#b0c4d8;cursor:default;}
.badge-tip.antigo{background:#ffebee;color:#b71c1c;}
.dias-old{font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;white-space:nowrap;}
.dias-old.ok{background:#e8f5e9;color:#1b5e20;}
.dias-old.medio{background:#fff8e1;color:#7d4e00;}
.dias-old.antigo{background:#ffebee;color:#b71c1c;}
.btn-fechar{background:#e53935;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:11px;font-weight:700;cursor:pointer;}
.btn-fechar:hover{background:#b71c1c;}
.filtros-dias{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:14px;}
.filtro-btn{padding:6px 14px;border-radius:20px;border:1px solid #b0c4d8;font-size:12px;font-weight:700;color:#3a5068;background:#fff;cursor:pointer;text-decoration:none;}
.filtro-btn.ativo{background:#0b3d91;color:#fff;border-color:#0b3d91;}
.filtro-radio{padding:5px 12px;border-radius:20px;border:1px solid #b0c4d8;font-size:12px;font-weight:700;color:#3a5068;background:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:5px;}
.filtro-radio.ativo{background:#0b3d91;color:#fff;border-color:#0b3d91;}
.filtro-radio input{margin:0;}
.filtro-check{font-size:12px;font-weight:700;color:#3a5068;cursor:pointer;display:inline-flex;align-items:center;gap:5px;background:#f0f5fb;border:1px solid #d3e0ee;border-radius:8px;padding:6px 12px;}
.filtros-transito input[type=date]{padding:6px 8px;border:1px solid #b0c4d8;border-radius:6px;font-size:12px;}
body.dark .filtro-radio{background:#16191e;color:#90caf9;border-color:#2a3040;}
body.dark .filtro-radio.ativo{background:#0b3d91;color:#fff;}
body.dark .filtro-check{background:#16191e;color:#90caf9;border-color:#2a3040;}
body.dark .filtros-transito input[type=date]{background:#1c2028;color:#dde3ec;border-color:#2a3040;}
.search-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;}
.search-row input{flex:1;min-width:120px;padding:9px 12px;border:2px solid #b0c4d8;border-radius:8px;font-size:13px;}
.search-row input:focus{outline:none;border-color:#0b3d91;}
.btn-buscar{padding:9px 20px;background:#0b3d91;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:12px;}
.btn-buscar:hover{background:#083170;}
.resultado-busca{margin-top:10px;}
.resp-aviso{color:#e53935;font-size:12px;font-weight:700;margin-top:4px;display:none;}
/* DARK */
body.dark{background:#111114;color:#dde3ec;}
body.dark .topbar{background:#0b1020;}
body.dark .abas{background:#16191e;border-bottom-color:#2a3040;}
body.dark .aba{color:#607080;}
body.dark .aba.ativa{color:#90caf9;border-bottom-color:#4fc3f7;}
body.dark .card,.dark .kpi,.dark .mov-box{background:#16191e!important;border-color:#252d3a!important;}
body.dark .input-resp,.dark .input-etiq,.dark .search-row input{background:#1c2028;color:#dde3ec;border-color:#2a3040;}
body.dark .op-bloco.receb{background:#0a2010;border-color:#1b5e20;}
body.dark .op-bloco.envio{background:#0a1a2e;border-color:#0d47a1;}
body.dark .status-live{background:#0a2010;border-color:#1b5e20;color:#81c784;}
body.dark .status-live.erro{background:#2a0d0d;border-color:#b71c1c;color:#ef9a9a;}
body.dark .tabela th{background:#0b1a2e;}
body.dark .tabela td{border-bottom-color:#1e2430;color:#dde3ec;}
body.dark .tabela tr:hover td{background:#1a2030;}
body.dark .filtro-btn{background:#16191e;color:#90caf9;border-color:#2a3040;}
body.dark .filtro-btn.ativo{background:#0b3d91;color:#fff;}
</style>
</head>
<body>
<div class="topbar">
  <a class="home" href="inicio.php">&#8592; Início</a>
  <h1>&#128230; Controle de Etiquetas Correios</h1><span style="font-size:11px;opacity:.7;margin-left:8px;">v2.1.0</span>
</div>

<div class="abas">
  <a class="aba <?php echo ($aba==='operacao'?'ativa':''); ?>" href="?aba=operacao">&#9997; Operação</a>
  <a class="aba <?php echo ($aba==='transito'?'ativa':''); ?>" href="?aba=transito<?php echo ($filtro_dias>0?'&dias='.$filtro_dias:''); ?>">&#9992; Em Trânsito <span class="badge" id="badge-transito"><?php echo (int)$transito_count; ?></span></a>
  <a class="aba <?php echo ($aba==='envios'?'ativa':''); ?>"        href="?aba=envios">&#8593; Envios</a>
  <a class="aba <?php echo ($aba==='recebimentos'?'ativa':''); ?>"  href="?aba=recebimentos">&#8595; Recebimentos</a>
  <a class="aba <?php echo ($aba==='pesquisar'?'ativa':''); ?>"     href="?aba=pesquisar">&#128269; Pesquisar</a>
  <a class="aba" href="conferencia_inventario.php" style="background:#fff3e0;color:#6a3805;">&#128203; Conferir Invent&aacute;rio</a>
  <a class="aba" href="rastreabilidade.php" style="background:#e0f2fe;color:#075985;">&#128279; Rastreabilidade</a>
  <a class="aba" href="devolucao_lotes.php" style="background:#fff3cd;color:#7a5a00;">&#128230; Devolucao Lotes</a>
</div>

<div class="main">

<?php if (!$dbOk): ?>
  <div class="card"><p style="color:#c62828;">&#9888; <?php echo e($mensagem); ?></p></div>
<?php endif; ?>

<!-- ═══════════════ ABA OPERAÇÃO ═══════════════ -->
<?php if ($aba==='operacao'): ?>

  <div class="kpis">
    <div class="kpi <?php echo ($transito_count>0?'alerta':''); ?>">
      <div class="k-label">Em trânsito</div>
      <div class="k-val" id="kpi-transito"><?php echo (int)$transito_count; ?></div>
    </div>
    <div class="kpi">
      <div class="k-label">Ver trânsito completo</div>
      <div style="margin-top:6px;"><a href="?aba=transito" style="font-size:12px;font-weight:700;">Ver lista &#8594;</a></div>
    </div>
  </div>

  <div class="card">
    <div class="resp-bloco">
      <label for="responsavel">Responsável (obrigatório)</label>
      <input type="text" id="responsavel" class="input-resp" autocomplete="off"
             value="<?php echo e($responsavel_salvo); ?>" placeholder="Digite seu nome...">
      <div class="resp-aviso" id="resp-aviso">&#9888; Informe o responsavel antes de registrar.</div>
    </div>
    <div class="status-live" id="status-live">Pronto para leitura.</div>
    <div class="mov-box" id="mov-box">
      <div class="mov-title" id="mov-title">—</div>
      <div class="mov-linha" id="mov-linha1"></div>
      <div class="mov-linha" id="mov-linha2"></div>
    </div>
  </div>

  <!-- ── RECEBIMENTO EM LOTE ── -->
  <div class="op-bloco receb" style="margin-bottom:14px;">
    <h3>&#8595; Recebimento em Lote</h3>
    <div class="sublabel">Escaneie as etiquetas (35 d&iacute;gitos). Cada leitura entra na lista abaixo. Clique em <strong>Gravar todos</strong> para salvar (tipo&nbsp;2).</div>
    <input type="text" id="leitura_lote" class="input-etiq" autocomplete="off" maxlength="35"
           placeholder="Escaneie ou digite 35 d&iacute;gitos e pressione ENTER">
    <div class="char-count"><span id="cnt-lote">0</span>/35 &nbsp;&middot;&nbsp; Na lista: <strong id="cnt-lista">0</strong></div>
    <button type="button" id="btnCameraDev" onclick="abrirCameraDev();" class="btn-buscar" style="background:#1a237e;margin-top:8px;">&#128247; Ler com a c&acirc;mera (celular)</button>

    <div id="lista-lote" style="max-height:340px;overflow-y:auto;border:1px solid #cfd8e3;border-radius:8px;background:#fff;margin-top:8px;display:none;">
      <table class="tabela" style="margin:0;">
        <thead>
          <tr>
            <th style="width:36px;">#</th>
            <th>Etiqueta</th>
            <th style="width:240px;">Posto</th>
            <th style="width:120px;">Status</th>
            <th style="width:54px;"></th>
          </tr>
        </thead>
        <tbody id="lista-lote-tbody"></tbody>
      </table>
    </div>
    <div id="lista-vazia" style="font-size:12px;color:#607080;margin-top:8px;font-style:italic;">Nenhuma etiqueta lida ainda.</div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
      <button type="button" id="btnGravarLote" class="btn-buscar" style="background:#1b5e20;" disabled>&#9989; Gravar todos (<span id="btn-cnt">0</span>)</button>
      <button type="button" id="btnLimparLote" class="btn-fechar" style="background:#607080;">Limpar lista</button>
    </div>

    <div id="resumo-lote" style="margin-top:10px;display:none;font-size:12px;"></div>
  </div>

  <!-- ── ENVIO (continua leitura unica) ── -->
  <div class="op-bloco envio">
    <h3>&#8593; Registrar Envio</h3>
    <div class="sublabel">Etiqueta sendo enviada (tipo 1) &mdash; leitura &uacute;nica</div>
    <form id="formEnvio">
      <input type="hidden" name="acao" value="registrar_envio">
      <input type="hidden" name="responsavel" value="" class="resp-hidden">
      <input type="text" id="leitura_envio" name="leitura_envio"
             class="input-etiq" autocomplete="off" maxlength="35"
             placeholder="Escaneie ou digite 35 d&iacute;gitos">
      <div class="char-count"><span id="cnt-envio">0</span>/35</div>
    </form>
  </div>

  <!-- Mini-histórico -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
    <div class="card">
      <h2>Últimos Envios</h2>
      <table class="tabela">
        <thead><tr><th>Etiqueta</th><th>Posto</th><th>Resp.</th><th>Data</th></tr></thead>
        <tbody><?php if (!empty($ultimos_envios)): foreach ($ultimos_envios as $r): ?>
          <tr><td class="mono"><?php echo e(substr($r['leitura'],0,12)).'...'; ?></td>
              <td><?php echo e($r['posto']?:'-'); ?></td>
              <td><?php echo e($r['login']?:'-'); ?></td>
              <td><?php echo e(dataBr($r['data_mov'])); ?></td></tr>
        <?php endforeach; else: ?>
          <tr><td colspan="4">Nenhum envio.</td></tr>
        <?php endif; ?></tbody>
      </table>
    </div>
    <div class="card">
      <h2>Últimos Recebimentos</h2>
      <table class="tabela">
        <thead><tr><th>Etiqueta</th><th>Posto</th><th>Resp.</th><th>Data</th></tr></thead>
        <tbody><?php if (!empty($ultimos_receb)): foreach ($ultimos_receb as $r): ?>
          <tr><td class="mono"><?php echo e(substr($r['leitura'],0,12)).'...'; ?></td>
              <td><?php echo e($r['posto']?:'-'); ?></td>
              <td><?php echo e($r['login']?:'-'); ?></td>
              <td><?php echo e(dataBr($r['data_mov'])); ?></td></tr>
        <?php endforeach; else: ?>
          <tr><td colspan="4">Nenhum recebimento.</td></tr>
        <?php endif; ?></tbody>
      </table>
    </div>
  </div>

<!-- ═══════════════ ABA TRÂNSITO ═══════════════ -->
<?php elseif ($aba==='transito'): ?>
  <div class="card">
    <h2>&#9992; Em trânsito
      <?php if ($filtro_dias>0): ?><small style="font-weight:400;color:#607080;">(últimos <?php echo $filtro_dias; ?> dias)</small><?php endif; ?>
      — <?php echo count($lista_transito); ?> etiqueta(s)
    </h2>

    <form method="get" class="filtros-transito" style="margin-bottom:12px;">
      <input type="hidden" name="aba" value="transito">
      <div class="filtros-dias">
        <strong style="font-size:12px;color:#3a5068;">Período (data de envio):</strong>
        <?php foreach ($dias_label as $v=>$label): ?>
          <label class="filtro-radio <?php echo (($f_data_ini==='' && $filtro_dias===$v)?'ativo':''); ?>">
            <input type="radio" name="dias" value="<?php echo $v; ?>" <?php echo ($filtro_dias===$v?'checked':''); ?>> <?php echo $label; ?>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="filtros-dias">
        <strong style="font-size:12px;color:#3a5068;">Ou por intervalo:</strong>
        <label style="font-size:12px;color:#3a5068;">De <input type="date" name="data_ini" value="<?php echo e($f_data_ini); ?>"></label>
        <label style="font-size:12px;color:#3a5068;">Até <input type="date" name="data_fim" value="<?php echo e($f_data_fim); ?>"></label>
        <span style="font-size:11px;color:#90a4ae;">(o intervalo tem prioridade sobre o período)</span>
      </div>
      <div class="filtros-dias">
        <strong style="font-size:12px;color:#3a5068;">Somente em trânsito real:</strong>
        <label class="filtro-check" title="Atenção: pode ocultar displays de primeira viagem que ainda não voltaram nenhuma vez"><input type="checkbox" name="com_retorno" value="1" <?php echo ($f_com_retorno?'checked':''); ?>> Só displays que já voltaram alguma vez (tipo 2)</label>
        <?php if ($inv_existe): ?>
        <label class="filtro-check"><input type="checkbox" name="excluir_inv" value="1" <?php echo ($f_excluir_inv?'checked':''); ?>> Excluir presentes no inventário (<?php echo (int)$inv_total; ?> salvos)</label>
        <?php endif; ?>
        <button type="submit" class="btn-buscar">Filtrar</button>
        <a href="?aba=transito&amp;dias=0" class="filtro-btn">Limpar</a>
      </div>
    </form>

    <p style="font-size:12px;color:#607080;margin-bottom:12px;">
      &#9888; Entradas antigas (aparecendo como &ldquo;em trânsito&rdquo; por muito tempo) são etiquetas enviadas mas cujo <strong>recebimento nunca foi registrado</strong> no sistema.
      Marque <strong>&ldquo;Só displays que já voltaram alguma vez&rdquo;</strong> ou <strong>&ldquo;Excluir presentes no inventário&rdquo;</strong> para esconder esses falsos positivos.
      <?php if (!$inv_existe): ?><br><span style="color:#a01818;">Dica: salve o inventário em <a href="conferencia_inventario.php">Conferir Inventário</a> para habilitar a exclusão por inventário.</span><?php endif; ?>
    </p>

    <?php if (empty($lista_transito)): ?>
      <p style="color:#2e7d32;font-weight:700;">&#10003; Nenhuma etiqueta em trânsito<?php echo $filtro_dias>0?' neste período.':'.'; ?></p>
    <?php else: ?>
    <table class="tabela">
      <thead><tr><th>#</th><th>Etiqueta</th><th>Lote</th><th>Posto</th><th>Enviado por</th><th>Data envio</th><th>Dias</th><th>Retornos</th><th>Ação</th></tr></thead>
      <tbody>
      <?php $i=1; foreach ($lista_transito as $r):
        $dias = diasAtras($r['data_mov']);
        $diasClass = $dias<=14?'ok':($dias<=30?'medio':'antigo');
        $qtRet = isset($r['qt_retornos']) ? (int)$r['qt_retornos'] : 0;
      ?>
        <tr>
          <td><?php echo $i++; ?></td>
          <td class="mono"><?php echo e($r['leitura']); ?></td>
          <td><?php echo e($r['lote']?$r['lote']:'—'); ?></td>
          <td><?php echo e($r['posto']?$r['posto']:'-'); ?></td>
          <td><?php echo e($r['login']&&$r['login']!==''?$r['login']:'Nao informado'); ?></td>
          <td><?php echo e(dataBr($r['data_mov'])); ?></td>
          <td><span class="dias-old <?php echo $diasClass; ?>"><?php echo $dias; ?> dias</span></td>
          <td><?php if ($qtRet>0): ?><span class="dias-old ok" title="Já voltou <?php echo $qtRet; ?>x antes"><?php echo $qtRet; ?>x</span><?php else: ?><span class="dias-old antigo" title="Nunca teve retorno registrado — possível falso positivo">nunca</span><?php endif; ?></td>
          <td><button class="btn-fechar" onclick="marcarRecebido('<?php echo e($r['leitura']); ?>', this)">Marcar recebido</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

<!-- ═══════════════ ABA ENVIOS ═══════════════ -->
<?php elseif ($aba==='envios'): ?>
  <div class="card">
    <h2>&#8593; Últimos 100 Envios</h2>
    <table class="tabela">
      <thead><tr><th>#</th><th>Etiqueta completa</th><th>CEP</th><th>Seq.</th><th>Posto</th><th>Responsável</th><th>Data</th></tr></thead>
      <tbody><?php if (!empty($ultimos_envios)): $i=1; foreach ($ultimos_envios as $r): ?>
        <tr><td><?php echo $i++; ?></td>
            <td class="mono"><?php echo e($r['leitura']); ?></td>
            <td class="mono"><?php echo e(substr($r['leitura'],0,8)); ?></td>
            <td class="mono"><?php echo e(substr($r['leitura'],-5)); ?></td>
            <td><?php echo e($r['posto']?:'-'); ?></td>
            <td><?php echo e($r['login']?:'-'); ?></td>
            <td><?php echo e(dataBr($r['data_mov'])); ?></td></tr>
      <?php endforeach; else: ?>
        <tr><td colspan="7">Nenhum envio encontrado.</td></tr>
      <?php endif; ?></tbody>
    </table>
  </div>

<!-- ═══════════════ ABA RECEBIMENTOS ═══════════════ -->
<?php elseif ($aba==='recebimentos'): ?>
  <div class="card">
    <h2>&#8595; Últimos 100 Recebimentos</h2>
    <table class="tabela">
      <thead><tr><th>#</th><th>Etiqueta completa</th><th>CEP</th><th>Seq.</th><th>Posto</th><th>Responsável</th><th>Data</th></tr></thead>
      <tbody><?php if (!empty($ultimos_receb)): $i=1; foreach ($ultimos_receb as $r): ?>
        <tr><td><?php echo $i++; ?></td>
            <td class="mono"><?php echo e($r['leitura']); ?></td>
            <td class="mono"><?php echo e(substr($r['leitura'],0,8)); ?></td>
            <td class="mono"><?php echo e(substr($r['leitura'],-5)); ?></td>
            <td><?php echo e($r['posto']?:'-'); ?></td>
            <td><?php echo e($r['login']?:'-'); ?></td>
            <td><?php echo e(dataBr($r['data_mov'])); ?></td></tr>
      <?php endforeach; else: ?>
        <tr><td colspan="7">Nenhum recebimento encontrado.</td></tr>
      <?php endif; ?></tbody>
    </table>
  </div>

<!-- ═══════════════ ABA PESQUISAR ═══════════════ -->
<?php elseif ($aba==='pesquisar'): ?>

  <!-- Pesquisa por POSTO -->
  <div class="card">
    <h2>&#128205; Pesquisar por Posto</h2>
    <div class="search-row">
      <input type="text" id="inputPosto" placeholder="Número do posto (ex: 014)" maxlength="5" oninput="this.value=this.value.replace(/\D+/g,'')">
      <button class="btn-buscar" onclick="buscarPorPosto()">Buscar</button>
    </div>
    <div id="resultado-posto" class="resultado-busca"></div>
  </div>

  <!-- Pesquisa por LOTE -->
  <div class="card">
    <h2>&#128230; Pesquisar por Lote</h2>
    <div class="search-row">
      <input type="text" id="inputLote" placeholder="Número do lote" maxlength="10" oninput="this.value=this.value.replace(/\D+/g,'')">
      <button class="btn-buscar" onclick="buscarPorLote()">Buscar</button>
    </div>
    <div id="resultado-lote" class="resultado-busca"></div>
  </div>

  <!-- Histórico por etiqueta -->
  <div class="card">
    <h2>&#128269; Pesquisar Display (envio / retorno)</h2>
    <div class="search-row">
      <input type="text" id="inputPares" maxlength="35" class="input-etiq" placeholder="Cole ou escaneie a etiqueta (35 dígitos)" style="font-size:12px;letter-spacing:.5px;">
      <input type="number" id="inputLimit" min="1" max="10" value="3" style="width:80px;padding:9px;border:2px solid #b0c4d8;border-radius:8px;font-size:13px;">
      <button class="btn-buscar" onclick="consultarPares()">Pesquisar</button>
    </div>
    <div id="pares-resultado" class="resultado-busca"></div>
  </div>

  <!-- Histórico por etiqueta (completo) -->
  <div class="card">
    <h2>&#128269; Histórico de uma etiqueta</h2>
    <div class="search-row">
      <input type="text" id="inputHistorico" maxlength="35" class="input-etiq"
             placeholder="Cole ou escaneie a etiqueta (35 dígitos)" style="font-size:12px;letter-spacing:.5px;">
      <button class="btn-buscar" onclick="consultarHistorico()">Consultar</button>
    </div>
    <div id="hist-resultado" class="resultado-busca"></div>
  </div>

<?php endif; ?>

</div>

<!-- Campo responsável oculto para marcar recebido -->
<input type="hidden" id="resp-global" value="<?php echo e($responsavel_salvo); ?>">

<script>
(function(){
  var respInput   = document.getElementById('responsavel');
  var statusLive  = document.getElementById('status-live');
  var kpiTransito = document.getElementById('kpi-transito');
  var movBox      = document.getElementById('mov-box');
  var respGlobal  = document.getElementById('resp-global');

  /* Restaurar responsável */
  if (respInput) {
    var salvo = localStorage.getItem('responsavel_devolucao');
    if (salvo && respInput.value.replace(/\s/g,'') === '') {
      respInput.value = salvo;
      if (respGlobal) respGlobal.value = salvo;
    }
    respInput.addEventListener('input', function(){
      localStorage.setItem('responsavel_devolucao', this.value);
      if (respGlobal) respGlobal.value = this.value;
      sincRespHidden();
      var av = document.getElementById('resp-aviso');
      if (av) av.style.display = this.value.replace(/\s/g,'')?'none':'block';
    });
  }

  function sincRespHidden() {
    var val = respInput ? respInput.value : '';
    var hs  = document.querySelectorAll('.resp-hidden');
    for (var i=0; i<hs.length; i++) hs[i].value = val;
  }
  sincRespHidden();

  function setStatus(txt, tipo) {
    if (!statusLive) return;
    statusLive.className = 'status-live' + (tipo==='erro'?' erro':(tipo==='warn'?' warn':''));
    statusLive.textContent = txt;
  }

  function showMov(d) {
    if (!movBox||!d) return;
    movBox.style.display = 'block';
    var t = parseInt(d.tipo,10);
    document.getElementById('mov-title').innerHTML = (t===1?'&#8593; Envio':'&#8595; Recebimento')+' registrado';
    document.getElementById('mov-linha1').innerHTML = 'Etiqueta: <span style="font-family:Courier New,monospace;word-break:break-all;">'+ esc(d.leitura||'-') +'</span>';
    document.getElementById('mov-linha2').textContent = 'Posto: '+(d.posto||'-')+' | Resp: '+(d.responsavel||'-')+' | '+( d.data||'');
  }

  function esc(s){ return String(s||'').replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];}); }

  function enviarAjax(input, acao) {
    if (!respInput || respInput.value.replace(/\s/g,'')==='') {
      setStatus('Informe o responsavel antes de registrar.','erro');
      var av=document.getElementById('resp-aviso'); if(av) av.style.display='block';
      if(respInput) respInput.focus();
      input.value='';
      return;
    }
    var digits = input.value.replace(/\D+/g,'');
    if (digits.length !== 35) return;
    sincRespHidden();
    var fd = new FormData(input.form);
    fd.set('ajax','1'); fd.set(input.name, digits);
    input.value = '';
    var cntId = (acao==='registrar_recebimento')?'cnt-receb':'cnt-envio';
    var cnt=document.getElementById(cntId); if(cnt) cnt.textContent='0';
    setStatus('Salvando...','ok');
    input.focus();
    fetch(window.location.pathname+window.location.search,{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        if(d){ setStatus(d.mensagem||(d.ok?'OK':'Erro'), d.mensagem_tipo||'ok');
               if(kpiTransito&&typeof d.transito!=='undefined') { kpiTransito.textContent=String(d.transito); var b=document.getElementById('badge-transito'); if(b) b.textContent=String(d.transito); }
               if(d.ultimo_movimento) showMov(d.ultimo_movimento); }
      }).catch(function(){ setStatus('Falha de comunicacao.','erro'); })
      .then(function(){ input.focus(); });
  }

  function prepInput(id, cntId, acao) {
    var inp = document.getElementById(id); if(!inp) return;
    inp.addEventListener('input',function(){
      var d=this.value.replace(/\D+/g,'');
      var c=document.getElementById(cntId); if(c) c.textContent=d.length;
      if(d.length===35) enviarAjax(this,acao);
    });
  }
  prepInput('leitura_envio','cnt-envio','registrar_envio');

  /* ══════════ RECEBIMENTO EM LOTE ══════════ */
  var loteList = [];           // { leitura, posto, nome, eh_central, ja_recebida_hoje, tem_envio, status }
  var inpLote   = document.getElementById('leitura_lote');
  var cntLote   = document.getElementById('cnt-lote');
  var cntLista  = document.getElementById('cnt-lista');
  var btnCnt    = document.getElementById('btn-cnt');
  var tbodyLote = document.getElementById('lista-lote-tbody');
  var caixaLote = document.getElementById('lista-lote');
  var vaziaLote = document.getElementById('lista-vazia');
  var btnGravar = document.getElementById('btnGravarLote');
  var btnLimpar = document.getElementById('btnLimparLote');
  var resumoLote = document.getElementById('resumo-lote');

  // --- Contagem por POSTO: mostra (1),(2)... quando ha mais de uma etiqueta do
  // MESMO posto na sessao. Ajuda o operador a ver que aquele posto JA foi lido e
  // nao ficar tentando reler um display ja detectado. (a duplicata da MESMA
  // etiqueta exata ja e barrada antes, em adicionarAoLote.)
  function chavePosto(it) {
    return it.eh_central ? ('C|' + (it.posto || '')) : ('P|' + (it.posto || '?'));
  }
  function calcContagemPosto() {
    var ord = [], total = {};
    for (var a = 0; a < loteList.length; a++) {
      var k = chavePosto(loteList[a]);
      total[k] = (total[k] || 0) + 1;
      ord[a] = total[k];
    }
    return { ord: ord, total: total };
  }

  function rerenderLote() {
    if (!tbodyLote) return;
    cntLista.textContent = String(loteList.length);
    btnCnt.textContent = String(loteList.length);
    btnGravar.disabled = (loteList.length === 0);
    if (loteList.length === 0) {
      tbodyLote.innerHTML = '';
      caixaLote.style.display = 'none';
      vaziaLote.style.display = 'block';
      renderPainelCamera();
      return;
    }
    caixaLote.style.display = 'block';
    vaziaLote.style.display = 'none';
    var h = '';
    var cont = calcContagemPosto();
    // Renderiza do mais novo para o mais antigo: o ultimo display lido fica
    // sempre no TOPO da lista (mais facil de conferir ao bipar varios).
    for (var i = loteList.length - 1; i >= 0; i--) {
      var it = loteList[i];
      var totPosto = cont.total[chavePosto(it)] || 1;
      var marcador = (totPosto > 1)
        ? ' <span style="background:#fff3e0;color:#e65100;font-size:11px;font-weight:800;padding:1px 6px;border-radius:8px;" title="'+cont.ord[i]+'a etiqueta lida deste posto">('+cont.ord[i]+')</span>'
        : '';
      var postoTxt = '';
      if (it.eh_central) {
        postoTxt = '<strong style="color:#0d47a1;">CENTRAL</strong> &middot; '+esc(it.posto||'?')+marcador;
      } else if (it.posto) {
        postoTxt = '<strong>'+esc(it.posto)+'</strong>'+marcador;
      } else {
        postoTxt = '<span style="color:#607080;">—</span>';
      }
      if (it.nome) postoTxt += '<br><small style="color:#607080;">'+esc(it.nome)+'</small>';
      var statusTxt, statusCls;
      if (it.status === 'pendente') {
        statusTxt = 'Pronto';   statusCls = 't1';
      } else if (it.status === 'duplicada') {
        statusTxt = 'Ja recebida hoje'; statusCls = 'transito';
      } else if (it.status === 'sem_envio') {
        statusTxt = 'Sem envio (!)'; statusCls = 'antigo';
      } else if (it.status === 'salva') {
        statusTxt = 'Salva'; statusCls = 't2';
      } else if (it.status === 'erro') {
        statusTxt = 'Erro';  statusCls = 'antigo';
      } else {
        statusTxt = it.status || '-'; statusCls = '';
      }
      h += '<tr data-idx="'+i+'">'
         + '<td>'+(i+1)+'</td>'
         + '<td class="mono">'+esc(it.leitura)+'</td>'
         + '<td>'+postoTxt+'</td>'
         + '<td><span class="badge-tip '+statusCls+'">'+statusTxt+'</span>'
         +     (it.msg?('<br><small style="color:#607080;">'+esc(it.msg)+'</small>'):'')+'</td>'
         + '<td><button type="button" class="btn-fechar" data-rm="'+i+'" title="Remover da lista">&times;</button></td>'
         + '</tr>';
    }
    tbodyLote.innerHTML = h;
    renderPainelCamera();
  }

  // Espelha a lista de displays lidos no painel ABAIXO da camera (com painel),
  // mostrando o numero do posto que esta sendo salvo, o ultimo lido no TOPO.
  function renderPainelCamera() {
    if (!(window.CamScanner && CamScanner.isPainel && CamScanner.isPainel() && CamScanner.setPainelHTML)) return;
    if (loteList.length === 0) {
      CamScanner.setPainelHTML('<div style="padding:22px 14px;text-align:center;color:#94a3b8;font-size:15px;line-height:1.6;">&#128247; Aponte a c&acirc;mera para o c&oacute;digo de barras do display.<br><span style="font-size:13px;color:#64748b;">A cada leitura v&aacute;lida voc&ecirc; ouve <b>um</b> bipe e o n&uacute;mero do posto aparece <b>grande</b> aqui.</span><br><span style="font-size:12px;color:#64748b;">Toque na imagem para focar.</span></div>');
      return;
    }
    var cont = calcContagemPosto();
    var ultimo = loteList[loteList.length - 1];
    var ultTotal = cont.total[chavePosto(ultimo)] || 1;
    var ultOrd = cont.ord[loteList.length - 1];
    // BANNER GRANDE: numero do posto lido por ultimo (pedido: mostrar MAIOR).
    var postoBig = ultimo.eh_central ? 'CENTRAL' : (ultimo.posto ? esc(ultimo.posto) : '—');
    var corBanner, txtEstado;
    if (ultimo.status === 'salva') { corBanner = '#14532d'; txtEstado = 'Salva'; }
    else if (ultimo.status === 'duplicada') { corBanner = '#7c2d12'; txtEstado = 'J\u00e1 recebida hoje'; }
    else if (ultimo.status === 'sem_envio') { corBanner = '#7c2d12'; txtEstado = 'Sem registro de envio'; }
    else if (ultimo.status === 'erro') { corBanner = '#7f1d1d'; txtEstado = 'Erro ao gravar'; }
    else { corBanner = '#0b2545'; txtEstado = 'Pronto para gravar'; }
    var badgeMulti = (ultTotal > 1)
      ? ' <span style="font-size:30px;font-weight:900;color:#fdba74;vertical-align:middle;">(' + ultOrd + ')</span>'
      : '';
    var h = '<div style="padding:14px 12px;background:' + corBanner + ';border-bottom:2px solid rgba(255,255,255,.12);text-align:center;">'
      +   '<div style="font-size:12px;color:#93c5fd;font-weight:800;letter-spacing:1px;">&#10003; POSTO LIDO</div>'
      +   '<div style="font-size:58px;line-height:1.05;font-weight:900;color:#fff;letter-spacing:1px;">' + postoBig + badgeMulti + '</div>'
      +   (ultimo.nome ? '<div style="font-size:14px;color:#cbd5e1;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + esc(ultimo.nome) + '</div>' : '')
      +   '<div style="font-size:12px;color:#e2e8f0;margin-top:5px;">' + txtEstado
      +     (ultTotal > 1 ? ' &middot; <b style="color:#fdba74;">' + ultOrd + '\u00aa etiqueta deste posto</b>' : '')
      +   '</div>'
      + '</div>';
    h += '<div style="padding:8px 12px;font-size:12px;color:#94a3b8;border-bottom:1px solid #1e293b;">Lidos nesta sess&atilde;o: <b style="color:#e2e8f0;">' + loteList.length + '</b></div>';
    for (var i = loteList.length - 1; i >= 0; i--) {
      var it = loteList[i];
      var totPosto = cont.total[chavePosto(it)] || 1;
      var marca = (totPosto > 1) ? ' <span style="color:#fdba74;font-weight:800;">(' + cont.ord[i] + ')</span>' : '';
      var postoTxt;
      if (it.eh_central) { postoTxt = 'CENTRAL &middot; ' + esc(it.posto || '?'); }
      else if (it.posto) { postoTxt = esc(it.posto); }
      else if (it.status === 'pendente') { postoTxt = 'Consultando...'; }
      else { postoTxt = '—'; }
      var btxt, bcor;
      if (it.status === 'salva') { btxt = 'Salva'; bcor = '#16a34a'; }
      else if (it.status === 'duplicada') { btxt = 'J\u00e1 recebida'; bcor = '#b45309'; }
      else if (it.status === 'sem_envio') { btxt = 'Sem envio'; bcor = '#b45309'; }
      else if (it.status === 'erro') { btxt = 'Erro'; bcor = '#dc2626'; }
      else if (it.posto) { btxt = 'Pronto'; bcor = '#0d9488'; }
      else { btxt = '...'; bcor = '#475569'; }
      var destaque = (i === loteList.length - 1) ? 'background:#13314f;' : '';
      h += '<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 12px;border-bottom:1px solid #1e293b;' + destaque + '">'
         +   '<div style="min-width:0;flex:1 1 auto;">'
         +     '<div style="font-size:20px;font-weight:800;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + postoTxt + marca + '</div>'
         +     (it.nome ? '<div style="font-size:12px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + esc(it.nome) + '</div>' : '')
         +   '</div>'
         +   '<span style="flex:0 0 auto;font-size:12px;font-weight:700;color:#fff;background:' + bcor + ';padding:4px 9px;border-radius:999px;">' + btxt + '</span>'
         + '</div>';
    }
    CamScanner.setPainelHTML(h);
  }

  if (tbodyLote) {
    tbodyLote.addEventListener('click', function(ev){
      var t = ev.target;
      if (t && t.getAttribute && t.getAttribute('data-rm') !== null) {
        var idx = parseInt(t.getAttribute('data-rm'),10);
        if (!isNaN(idx)) { loteList.splice(idx,1); rerenderLote(); }
      }
    });
  }

  function adicionarAoLote(leitura) {
    // duplicada na propria lista?
    for (var i = 0; i < loteList.length; i++) {
      if (loteList[i].leitura === leitura) {
        setStatus('Etiqueta ja esta na lista (#' + (i+1) + ').', 'warn');
        return;
      }
    }
    // v2.7.0 (C): RESOLVER PRIMEIRO. So entra na lista se for um display EXISTENTE =
    // tem_envio (ha registro tipo=1 em ciMalotes p/ ESTA leitura exata). Leitura invalida/
    // inexistente NAO vira linha (nada de "desconhecido") e nao duplica. Mantem a rapidez:
    // 1 consulta por leitura; a camera ja deduplica leituras repetidas (~1200ms).
    var fd = new FormData();
    fd.append('acao','resolver_etiqueta');
    fd.append('leitura', leitura);
    fetch(window.location.pathname, {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (!d || !d.ok) {
          setStatus('Leitura invalida — ignorada.', 'warn');
          return;
        }
        // posto autoritativo = do envio (tipo=1); d.posto (cadastro) complementa.
        var posto = (d.ultimo_envio && d.ultimo_envio.posto) ? d.ultimo_envio.posto : d.posto;
        // v2.8.0 (camera): "display existente/valido" = aquele que RESOLVE para um posto
        // conhecido (cadastroMalotes). Antes exigiamos registro de ENVIO (tipo=1), o que
        // descartava em SILENCIO displays reais sem envio lancado -> parecia que "a camera
        // nao le" (so postos com envio entravam). Agora todo display que resolve posto entra;
        // o que NAO resolve posto (desconhecido) continua barrado (sem listar "desconhecido").
        if (!posto) {
          setStatus('Display nao reconhecido no cadastro — ignorado.', 'warn');
          return;
        }
        if (d.ja_recebida_hoje) {
          setStatus('Etiqueta ja recebida hoje — ignorada.', 'warn');
          return;
        }
        // dedup de novo (resposta assincrona; outra leitura igual pode ter chegado antes)
        for (var k = 0; k < loteList.length; k++) {
          if (loteList[k].leitura === leitura) { return; }
        }
        // Sem envio (tipo=1) NAO bloqueia: entra com aviso e o servidor grava do mesmo
        // jeito (igual ao lancamento manual, que ja avisa "sem registro de envio").
        loteList.push({
          leitura: leitura,
          posto: posto || '',
          nome: d.nome || '',
          eh_central: !!d.eh_central,
          ja_recebida_hoje: false,
          tem_envio: !!d.tem_envio,
          status: 'pendente',
          msg: d.tem_envio ? '' : 'Sem registro de envio (sera gravada).'
        });
        // BIPE so AGORA: o posto foi REALMENTE identificado (pedido do usuario). A
        // camera (lib_cam_scanner) foi aberta com beepOnDetect:false, entao o UNICO
        // bipe vem daqui -> 1 bipe por display valido, nunca para leitura invalida
        // nem repetido p/ o mesmo codigo (a MESMA etiqueta exata e barrada antes).
        if (window.CamScanner && CamScanner.beep) { try { CamScanner.beep(); } catch (eB) {} }
        var cont = calcContagemPosto();
        var idxNovo = loteList.length - 1;
        var totPostoNovo = cont.total[chavePosto(loteList[idxNovo])] || 1;
        var ordPostoNovo = cont.ord[idxNovo];
        rerenderLote();
        var postoMsg = loteList[idxNovo].eh_central ? 'CENTRAL' : posto;
        var sufPosto = (totPostoNovo > 1) ? (' (' + ordPostoNovo + 'a etiqueta deste posto)') : '';
        setStatus('Posto ' + postoMsg + ' lido' + sufPosto + '.'
                  + (d.tem_envio ? '' : ' (sem registro de envio)') + ' Continue lendo.', 'ok');
      })
      .catch(function(){
        setStatus('Falha de comunicacao.', 'erro');
      });
  }

  function processarLeituraLote() {
    var d = inpLote.value.replace(/\D+/g,'');
    if (cntLote) cntLote.textContent = String(d.length);
    if (d.length !== 35) return;
    if (!respInput || respInput.value.replace(/\s/g,'')==='') {
      setStatus('Informe o responsavel antes de ler etiquetas.','erro');
      var av=document.getElementById('resp-aviso'); if(av) av.style.display='block';
      respInput && respInput.focus();
      inpLote.value=''; if (cntLote) cntLote.textContent='0';
      return;
    }
    inpLote.value = '';
    if (cntLote) cntLote.textContent = '0';
    setStatus('Verificando etiqueta...', '');
    adicionarAoLote(d);
    inpLote.focus();
  }

  if (inpLote) {
    inpLote.addEventListener('input', processarLeituraLote);
    inpLote.addEventListener('keydown', function(ev){
      if (ev.keyCode === 13) {
        ev.preventDefault();
        var d = this.value.replace(/\D+/g,'');
        if (d.length === 35) processarLeituraLote();
      }
    });
    inpLote.focus();
  }

  if (btnLimpar) {
    btnLimpar.addEventListener('click', function(){
      if (loteList.length === 0) return;
      if (!confirm('Limpar toda a lista ('+loteList.length+' etiquetas)?')) return;
      loteList = [];
      if (resumoLote) { resumoLote.style.display='none'; resumoLote.innerHTML=''; }
      rerenderLote();
      if (inpLote) inpLote.focus();
    });
  }

  if (btnGravar) {
    btnGravar.addEventListener('click', function(){
      if (loteList.length === 0) return;
      if (!respInput || respInput.value.replace(/\s/g,'')==='') {
        setStatus('Informe o responsavel antes de gravar.','erro');
        respInput && respInput.focus();
        return;
      }
      // monta lista de etiquetas a enviar (ignora as ja salvas), com posto ja resolvido no front
      var paraEnviar = [];
      for (var i=0; i<loteList.length; i++) {
        if (loteList[i].status !== 'salva') {
          paraEnviar.push({ leitura: loteList[i].leitura, posto: loteList[i].posto || '' });
        }
      }
      if (paraEnviar.length === 0) { setStatus('Nada novo para gravar.','warn'); return; }
      if (!confirm('Gravar '+paraEnviar.length+' etiqueta(s) como RECEBIDAS (tipo 2)?')) return;
      btnGravar.disabled = true;
      btnGravar.textContent = 'Gravando ' + paraEnviar.length + '...';
      setStatus('Gravando ' + paraEnviar.length + ' etiqueta(s)...', 'ok');
      btnLimpar && (btnLimpar.disabled = true);
      var fd = new FormData();
      fd.append('acao','gravar_lote_recebimentos');
      fd.append('responsavel', respInput.value);
      fd.append('etiquetas', JSON.stringify(paraEnviar));
      fetch(window.location.pathname, {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(d){
          if (!d || !d.ok) {
            setStatus((d && d.erro) ? d.erro : 'Falha ao gravar.','erro');
            btnGravar.disabled = false;
            btnGravar.innerHTML = '&#9989; Gravar todos (<span id="btn-cnt">'+loteList.length+'</span>)';
            btnCnt = document.getElementById('btn-cnt');
            return;
          }
          // marca cada item com seu resultado
          if (d.resultados && d.resultados.length) {
            for (var k=0; k<d.resultados.length; k++) {
              var res = d.resultados[k];
              for (var j=0; j<loteList.length; j++) {
                if (loteList[j].leitura === res.leitura) {
                  loteList[j].status = (res.status==='ok')?'salva':res.status;
                  loteList[j].msg = res.msg || '';
                  if (res.posto && !loteList[j].posto) loteList[j].posto = res.posto;
                  break;
                }
              }
            }
          }
          // v2.0.0: feedback IMEDIATO assim que o server responde — nao espera o rerender DOM.
          setStatus('Concluido: ' + (d.salvas||0) + ' salvas, ' + (d.puladas||0) + ' puladas, ' + (d.erros||0) + ' erros.', ((d.erros||0)>0?'warn':'ok'));
          btnGravar.disabled = false;
          btnLimpar && (btnLimpar.disabled = false);
          btnGravar.innerHTML = '&#9989; Gravar todos (<span id="btn-cnt">'+loteList.length+'</span>)';
          btnCnt = document.getElementById('btn-cnt');
          var corBg = ((d.erros||0) > 0) ? '#fff8e1' : '#e8f5e9';
          var corBorda = ((d.erros||0) > 0) ? '#ffe082' : '#a5d6a7';
          var corTxt = ((d.erros||0) > 0) ? '#7d4e00' : '#1b5e20';
          var resumoHtml = '<div style="padding:10px 12px;border-radius:8px;background:'+corBg+';border:1px solid '+corBorda+';color:'+corTxt+';font-weight:700;">'
            + '&#10003; Gravacao concluida &mdash; Salvas: '+(d.salvas||0)
            + ' &nbsp;&middot;&nbsp; Puladas (duplicadas hoje): '+(d.puladas||0)
            + ' &nbsp;&middot;&nbsp; Erros: '+(d.erros||0)
            + ' &nbsp;&middot;&nbsp; Total: '+(d.total||0)
            + '</div>';
          if (resumoLote) { resumoLote.innerHTML = resumoHtml; resumoLote.style.display = 'block'; }
          if (typeof d.transito !== 'undefined') {
            if (kpiTransito) kpiTransito.textContent = String(d.transito);
            var b=document.getElementById('badge-transito'); if(b) b.textContent=String(d.transito);
          }
          // rerender da tabela rola DEPOIS da mensagem de sucesso para nao bloquear o feedback visual
          setTimeout(function(){ rerenderLote(); if (inpLote) inpLote.focus(); }, 0);
        })
        .catch(function(){
          setStatus('Falha de comunicacao ao gravar.','erro');
          btnGravar.disabled = false;
          btnLimpar && (btnLimpar.disabled = false);
          btnGravar.innerHTML = '&#9989; Gravar todos (<span id="btn-cnt">'+loteList.length+'</span>)';
          btnCnt = document.getElementById('btn-cnt');
        });
    });
  }

  /* ── MARCAR RECEBIDO (Aba Trânsito) ── */
  window.marcarRecebido = function(leitura, btn) {
    var resp = (document.getElementById('resp-global')||{}).value || localStorage.getItem('responsavel_devolucao') || '';
    if (!resp || resp.replace(/\s/g,'')==='') {
      var nome = prompt('Informe seu nome (responsavel pelo fechamento):');
      if (!nome || nome.replace(/\s/g,'')==='') { alert('Nome obrigatorio.'); return; }
      resp = nome;
      localStorage.setItem('responsavel_devolucao', resp);
    }
    if (!confirm('Marcar etiqueta como recebida?\n'+leitura)) return;
    var fd=new FormData();
    fd.append('acao','marcar_recebido');
    fd.append('leitura',leitura);
    fd.append('responsavel',resp);
    fd.append('ajax','1');
    btn.disabled=true; btn.textContent='...';
    fetch(window.location.pathname,{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        if(d&&d.ok){ btn.closest('tr').style.opacity='.3'; btn.textContent='OK'; var b=document.getElementById('badge-transito'); if(b) b.textContent=String(d.transito||0); }
        else { alert((d&&d.mensagem)||'Erro.'); btn.disabled=false; btn.textContent='Marcar recebido'; }
      });
  };

  /* ── PESQUISA POR POSTO ── */
  window.buscarPorPosto = function() {
    var inp=document.getElementById('inputPosto'); if(!inp) return;
    var posto=inp.value.replace(/\D+/g,'').padStart?inp.value.replace(/\D+/g,''):inp.value.replace(/\D+/g,'');
    while(posto.length<3) posto='0'+posto;
    var el=document.getElementById('resultado-posto'); if(!el) return;
    el.innerHTML='<em>Buscando...</em>';
    var fd=new FormData(); fd.append('acao','buscar_posto'); fd.append('posto',posto);
    fetch(window.location.pathname,{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ renderTabelaBusca(el, d.rows, 'Posto '+esc(d.posto||'-')); });
  };

  /* ── PESQUISA POR LOTE ── */
  window.buscarPorLote = function() {
    var inp=document.getElementById('inputLote'); if(!inp) return;
    var lote=inp.value.replace(/\D+/g,'');
    if(!lote){ alert('Informe o numero do lote.'); return; }
    var el=document.getElementById('resultado-lote'); if(!el) return;
    el.innerHTML='<em>Buscando...</em>';
    var fd=new FormData(); fd.append('acao','buscar_lote'); fd.append('lote',lote);
    fetch(window.location.pathname,{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        if(!d.status||d.status.length===0){
          el.innerHTML='<p style="color:#888;margin-top:8px;">Nenhum display encontrado neste lote.</p>';
          return;
        }
        var totalTransito=0, totalRetornou=0, totalNaoEnviado=0;
        for(var i=0;i<d.status.length;i++){
          if(d.status[i].status==='Em transito') totalTransito++;
          else if(d.status[i].status==='Retornou') totalRetornou++;
          else totalNaoEnviado++;
        }
        var html='<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;">'
          +'<div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:700;color:#7d4e00;">&#9992; Em trânsito: '+totalTransito+'</div>'
          +'<div style="background:#e8f5e9;border:1px solid #a5d6a7;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:700;color:#1b5e20;">&#10003; Retornou: '+totalRetornou+'</div>'
          +(totalNaoEnviado>0?'<div style="background:#eceff1;border:1px solid #b0bec5;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:700;color:#455a64;">&#8212; Não enviado: '+totalNaoEnviado+'</div>':'')
          +'</div>';
        html+='<p style="margin-bottom:8px;"><a href="painel_lotes.php?lote='+esc(d.lote||'')+'" target="_blank" style="font-size:12px;font-weight:700;color:#0b3d91;">&#128230; Ver lote no Painel de Controle &rarr;</a></p>';
        // Detecta se ha posto(s) com etiquetas ambiguas (oficio com 2+ linhas)
        var temAmbiguo=false;
        for(var k=0;k<d.status.length;k++){ if(d.status[k].ambiguo){temAmbiguo=true;break;} }
        if(temAmbiguo){
          html+='<div style="background:#fff3e0;border:1px solid #ffb74d;border-radius:8px;padding:10px 14px;margin-bottom:10px;color:#6a3805;font-size:12px;font-weight:600;">'
            +'&#9888; Este lote pertence a um posto que teve <b>2 ou mais linhas no of&iacute;cio</b>. Todas as etiquetas candidatas est&atilde;o listadas abaixo &mdash; n&atilde;o &eacute; poss&iacute;vel determinar com exatid&atilde;o qual delas levou este lote espec&iacute;fico.'
            +'</div>';
        }
        html+='<table class="tabela"><thead><tr><th>#</th><th>Posto (of&iacute;cio)</th><th>Etiqueta</th><th>Posto (envio)</th><th>Enviado por</th><th>Data envio</th><th>Status</th><th>Data retorno</th></tr></thead><tbody>';
        var ultPostoChave='';
        var seq=0;
        for(var j=0;j<d.status.length;j++){
          var s=d.status[j];
          var postoChave=(s.id_despacho||'')+'|'+(s.posto_oficio||'');
          var novoBloco=(postoChave!==ultPostoChave);
          ultPostoChave=postoChave;
          if(novoBloco) seq=0;
          seq++;
          var stClass=s.status==='Em transito'?'medio':(s.status==='Retornou'?'ok':'');
          var stLabel=s.status==='Em transito'?'&#9992; Em tr&acirc;nsito':(s.status==='Retornou'?'&#10003; Retornou':(s.status==='Sem etiqueta'?'&#8212; Sem etiqueta':'&#8212; N&atilde;o enviado'));
          // Cabecalho do bloco quando posto ambiguo
          if(novoBloco && s.ambiguo){
            html+='<tr style="background:#fff8e1;">'
              +'<td colspan="8" style="font-weight:700;color:#7d4e00;font-size:12px;padding:6px 10px;">'
              +'&#128269; Posto '+esc(s.posto_oficio||'-')+' &middot; of&iacute;cio #'+esc(String(s.id_despacho||'-'))
              +' &mdash; <span style="background:#ff9800;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">'+s.qtde_candidatas+' etiquetas candidatas</span>'
              +'</td></tr>';
          }
          var ambigBadge = s.ambiguo ? ' <span style="background:#ff9800;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:700;margin-left:4px;">CANDIDATA</span>' : '';
          html+='<tr'+(s.ambiguo?' style="background:#fffbf0;"':'')+'>'
            +'<td>'+(j+1)+'</td>'
            +'<td><b>'+esc(s.posto_oficio||'-')+'</b></td>'
            +'<td class="mono">'+esc(s.leitura||'-')+ambigBadge+'</td>'
            +'<td>'+esc(s.posto||'-')+'</td>'
            +'<td>'+esc(s.enviado_por||'-')+'</td>'
            +'<td>'+esc(s.data_envio||'-')+'</td>'
            +'<td><span class="dias-old '+(stClass)+'">'+stLabel+'</span></td>'
            +'<td>'+esc(s.data_retorno||'-')+'</td>'
            +'</tr>';
        }
        html+='</tbody></table>';
        el.innerHTML=html;
      });
  };

  function renderTabelaBusca(el, rows, titulo) {
    if(!rows||rows.length===0){el.innerHTML='<p style="color:#888;margin-top:8px;">Nenhum registro encontrado.</p>';return;}
    var html='<p style="font-size:12px;color:#3a5068;margin-bottom:8px;font-weight:700;">'+titulo+' — '+rows.length+' registro(s)</p>';
    html+='<table class="tabela"><thead><tr><th>Tipo</th><th>Etiqueta</th><th>Posto</th><th>Responsável</th><th>Data</th></tr></thead><tbody>';
    for(var i=0;i<rows.length;i++){
      var r=rows[i]; var t=parseInt(r.tipo,10);
      html+='<tr><td><span class="badge-tip '+(t===1?'t1':'t2')+'">'+(t===1?'Envio':'Recebimento')+'</span></td>'
          +'<td class="mono">'+esc(r.leitura||'-')+'</td>'
          +'<td>'+esc(r.posto||'-')+'</td>'
          +'<td>'+esc(r.login||'-')+'</td>'
          +'<td>'+esc(r.data_mov||'-')+'</td></tr>';
    }
    html+='</tbody></table>';
    el.innerHTML=html;
  }

  /* ── HISTÓRICO POR ETIQUETA ── */
  var histLeitura='';
  window.consultarHistorico = function(pagina) {
    var inp=document.getElementById('inputHistorico'); if(!inp) return;
    var leit;
    if (typeof pagina==='number' && pagina>0) {
      leit = histLeitura;
    } else {
      leit = inp.value.replace(/\D+/g,'');
      pagina = 1;
    }
    if(leit.length!==35){alert('Informe 35 digitos.');return;}
    histLeitura = leit;
    var el=document.getElementById('hist-resultado'); if(!el) return;
    el.innerHTML='<em>Buscando...</em>';
    var fd=new FormData(); fd.append('acao','historico'); fd.append('leitura',leit); fd.append('pagina',String(pagina));
    fetch(window.location.pathname,{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        if(!d.historico||d.historico.length===0){el.innerHTML='<p style="color:#888;margin-top:8px;">Nenhum registro encontrado para esta etiqueta.</p>';return;}
        var total = d.total||d.historico.length;
        var porPag = d.por_pagina||20;
        var pag = d.pagina||1;
        var totalPag = Math.max(1, Math.ceil(total/porPag));
        var html='<p style="font-size:12px;margin-bottom:8px;font-weight:700;color:#3a5068;">Histórico: <span style="font-family:Courier New,monospace;">'+esc(leit)+'</span> <span style="color:#90a4ae;font-weight:400;">('+total+' registro'+(total===1?'':'s')+')</span></p>';
        html+='<table class="tabela"><thead><tr><th>Tipo</th><th>Responsável</th><th>Data</th><th>Observação</th></tr></thead><tbody>';
        for(var i=0;i<d.historico.length;i++){
          var h=d.historico[i]; var t=parseInt(h.tipo,10);
          html+='<tr><td><span class="badge-tip '+(t===1?'t1':'t2')+'">'+(t===1?'Envio':'Recebimento')+'</span></td>'
              +'<td>'+esc(h.login||'-')+'</td><td>'+esc(h.data_mov||'-')+'</td><td>'+esc(h.observacao||'-')+'</td></tr>';
        }
        html+='</tbody></table>';
        if(totalPag>1){
          html+='<div class="hist-paginacao">';
          html+='<button type="button" class="btn-pag" '+(pag<=1?'disabled':'')+' onclick="consultarHistorico('+(pag-1)+')">&#8592; Anterior</button>';
          html+='<span class="hist-pag-info">Página '+pag+' de '+totalPag+'</span>';
          html+='<button type="button" class="btn-pag" '+(pag>=totalPag?'disabled':'')+' onclick="consultarHistorico('+(pag+1)+')">Próxima &#8594;</button>';
          html+='</div>';
        }
        el.innerHTML=html;
      }).catch(function(){ el.innerHTML='<p style="color:#c62828;">Falha de comunicacao.</p>'; });
  };

  /* ── CONSULTAR PARES ENVIO/RETORNO ── */
  window.consultarPares = function() {
    var inp=document.getElementById('inputPares'); if(!inp) return;
    var leit=inp.value.replace(/\D+/g,'');
    if(leit.length!==35){alert('Informe 35 digitos.');return;}
    var lim = parseInt((document.getElementById('inputLimit')||{}).value,10) || 3;
    var el=document.getElementById('pares-resultado'); if(!el) return;
    el.innerHTML='<em>Buscando...</em>';
    var fd=new FormData(); fd.append('acao','buscar_pares'); fd.append('leitura',leit); fd.append('limit',String(lim));
    fetch(window.location.pathname,{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        if(!d.pares||d.pares.length===0){ el.innerHTML='<p style="color:#888;margin-top:8px;">Nenhum par encontrado para esta etiqueta.</p>'; return; }
        var html='<p style="font-size:12px;margin-bottom:8px;font-weight:700;color:#3a5068;">Resultados (ultimos '+d.pares.length+' envios)</p>';
        html+='<table class="tabela"><thead><tr><th>#</th><th>Envio</th><th>Retorno</th><th>Posto</th><th>Etiqueta</th><th>Lote</th><th>Ofício</th></tr></thead><tbody>';
        for(var i=0;i<d.pares.length;i++){
          var p=d.pares[i];
          var envio = p.envio || {};
          var retorno = p.retorno || null;
          var lote = (p.lote && p.lote.lote) ? p.lote.lote : '-';
          var oficio = (p.lote && p.lote.id_despacho) ? ('#'+p.lote.id_despacho+' '+(p.lote.oficio_usuario||'')) : '-';
          html+='<tr>'
            +'<td>'+(i+1)+'</td>'
            +'<td style="white-space:nowrap;">&#8593; '+(envio.data||'-')+' '+(envio.usuario?'<br><small>'+envio.usuario+'</small>':'')+'</td>'
            +'<td style="white-space:nowrap;">'+(retorno?('&#8595; '+(retorno.data||'-')+'<br><small>'+(retorno.usuario||'')+'</small>'):'<span style="color:#888">—</span>')+'</td>'
            +'<td>'+(envio.posto||(retorno?retorno.posto:'-')||'-')+'</td>'
            +'<td class="mono">'+(document.getElementById('inputPares')?esc(document.getElementById('inputPares').value):'-')+'</td>'
            +'<td>'+lote+'</td>'
            +'<td>'+esc(oficio)+'</td>'
            +'</tr>';
        }
        html+='</tbody></table>';
        el.innerHTML=html;
      }).catch(function(){ el.innerHTML='<p style="color:#c62828;">Falha de comunicacao.</p>'; });
  };

  /* Enter nos campos de pesquisa */
  var iP=document.getElementById('inputPosto'); if(iP) iP.addEventListener('keydown',function(e){if(e.keyCode===13) window.buscarPorPosto();});
  var iL=document.getElementById('inputLote');  if(iL) iL.addEventListener('keydown',function(e){if(e.keyCode===13) window.buscarPorLote();});
  var iH=document.getElementById('inputHistorico'); if(iH) iH.addEventListener('keydown',function(e){if(e.keyCode===13) window.consultarHistorico();});

  // Leitura por CAMERA ao vivo (celular) para o "Recebimento em Lote": cada
  // etiqueta lida (35 dig) entra na lista, igual ao leitor/digitacao do PC; o
  // botao "Gravar todos" salva tudo em ciMalotes (tipo 2) como sempre.
  window.abrirCameraDev = function () {
    if (typeof CamScanner === 'undefined' || !CamScanner.start) {
      alert('Leitor de camera nao disponivel nesta pagina.');
      return;
    }
    if (!respInput || respInput.value.replace(/\s/g,'') === '') {
      setStatus('Informe o responsavel antes de ler etiquetas.','erro');
      var av = document.getElementById('resp-aviso'); if (av) av.style.display = 'block';
      respInput && respInput.focus();
      return;
    }
    CamScanner.start({
      titulo: 'Ler etiquetas (recebimento)',
      painel: true,
      beepOnDetect: false,
      onRead: function (bruto) {
        var d = ('' + (bruto || '')).replace(/\D+/g,'');
        if (d.length >= 35) {
          inpLote.value = d.slice(-35);
          processarLeituraLote();
        } else if (d.length > 0) {
          // leitura parcial: o scanner ja deu beep, mas faltam digitos. Avisa para o
          // usuario nao achar que "leu" (beep) e a etiqueta nao aparecer na lista.
          setStatus('Leu ' + d.length + ' digitos — aproxime e alinhe a etiqueta inteira na mira.', 'warn');
        }
      }
    });
    renderPainelCamera();
  };

})();
</script>
<script src="assets/js/lib_zxing.min.js"></script>
<script src="assets/js/lib_cam_scanner.js"></script>
<?php include __DIR__ . '/includes/_acess.php'; ?>
</body>
</html>
