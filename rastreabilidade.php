<?php
/**
 * rastreabilidade.php  -  Tela unica de rastreabilidade
 *
 * Aceita qualquer entrada (lote, etiqueta correios, codigo de barras do display,
 * numero do oficio/despacho) e mostra TODA a cadeia em uma timeline:
 *
 *     Despacho/Oficio  ->  Postos  ->  Lotes  ->  Conferencia  ->  Devolucao
 *
 * Recursos:
 *  - Busca com auto-deteccao do tipo de entrada
 *  - QR code por lote e por etiqueta (link direto para esta mesma pagina)
 *  - Export CSV completo da rastreabilidade encontrada
 *  - Compativel com PHP 5.3.3 (array(), sem closures, sem traits)
 */

session_start();
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: text/html; charset=utf-8');

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function pdoControle() {
    $host = getenv('DB_HOST') ?: '10.15.61.169';
    $name = getenv('DB_NAME') ?: 'controle';
    $user = getenv('DB_USER') ?: 'controle_mat';
    $pass = getenv('DB_PASS') ?: '375256';
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

/* -----------------------------------------------------------------------
 *  Auto-deteccao do tipo de entrada
 * ----------------------------------------------------------------------- */
function detectarTipo($entrada, $modo = 'auto') {
    $entrada = trim((string)$entrada);
    if ($entrada === '') return array('tipo' => 'vazio', 'valor' => '');

    $soDig = preg_replace('/\D+/', '', $entrada);
    $len = strlen($soDig);

    // Modo FORCADO pelo usuario: sem ambiguidade entre lote e numero do oficio.
    if ($modo === 'lote') {
        if ($soDig === '') return array('tipo' => 'desconhecido', 'valor' => $entrada);
        return array('tipo' => 'lote', 'valor' => str_pad($soDig, 8, '0', STR_PAD_LEFT));
    }
    if ($modo === 'oficio') {
        $n = (int)$soDig;
        if ($n <= 0) return array('tipo' => 'desconhecido', 'valor' => $entrada);
        return array('tipo' => 'despacho_id', 'valor' => $n);
    }

    // ----- modo automatico -----
    // Numero do oficio: prefixo OF, #, oficio
    if (preg_match('/^(?:of|#|oficio)\s*([0-9]+)$/i', $entrada, $m)) {
        return array('tipo' => 'despacho_id', 'valor' => (int)$m[1]);
    }

    // Etiqueta Correios: 35 digitos
    if ($len >= 30 && $len <= 35) {
        return array('tipo' => 'etiqueta', 'valor' => str_pad($soDig, 35, '0', STR_PAD_LEFT));
    }
    // Codigo de barras do display: 19 ou 17 digitos
    if ($len === 19 || $len === 17) {
        return array('tipo' => 'display', 'valor' => $soDig);
    }
    // Lote OU numero do oficio: 1 a 8 digitos (ambiguo, busca os dois)
    if ($len >= 1 && $len <= 8) {
        return array(
            'tipo' => 'lote_oficio',
            'valor' => str_pad($soDig, 8, '0', STR_PAD_LEFT),
            'oficio' => (int)$soDig,
        );
    }
    // Despacho ID puro (9 ou mais digitos seria estranho - cai aqui se nada bater)
    return array('tipo' => 'desconhecido', 'valor' => $entrada);
}

/* -----------------------------------------------------------------------
 *  Helpers de negocio
 * ----------------------------------------------------------------------- */
/* Postos POUPA TEMPO da Capital + Regiao Metropolitana (codigos 005 a 080)
 * NAO usam etiqueta dos Correios. Somente postos do interior (>80) usam. */
function postoUsaEtiquetaCorreios($grupo, $codigo) {
    $g = strtolower((string)$grupo);
    $ehPT = (strpos($g, 'poupa') !== false || strpos($g, 'tempo') !== false);
    if (!$ehPT) return true;
    if (!preg_match('/^[0-9]+$/', (string)$codigo)) return true;
    $n = (int)$codigo;
    return !($n >= 5 && $n <= 80);
}

/* Descobre colunas reais de uma tabela (lowercase => nome real); vazio se ausente. */
function colunasTabela($pdo, $tabela) {
    $cols = array();
    try {
        $tbl = preg_replace('/[^A-Za-z0-9_]/', '', $tabela);
        $st  = $pdo->query("SHOW COLUMNS FROM `" . $tbl . "`");
        if ($st) {
            while ($r = $st->fetch()) { $cols[strtolower($r['Field'])] = $r['Field']; }
        }
    } catch (Exception $e) { /* tabela ausente */ }
    return $cols;
}

function dataBrRast($iso) {
    $s = trim((string)$iso);
    if ($s === '' || $s === '0000-00-00' || strpos($s, '0000-00-00') === 0) return '';
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/', $s, $m)) {
        return $m[3] . '/' . $m[2] . '/' . $m[1] . ' ' . $m[4] . ':' . $m[5];
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }
    return $s;
}

/* -----------------------------------------------------------------------
 *  Extracao de lote/posto a partir de codigo de barras (display)
 * ----------------------------------------------------------------------- */
function extrairDoDisplay($codbar) {
    $len = strlen($codbar);
    if ($len === 19) {
        return array(
            'lote' => str_pad(substr($codbar, 0, 8), 8, '0', STR_PAD_LEFT),
            'posto' => str_pad(substr($codbar, 11, 3), 3, '0', STR_PAD_LEFT),
            'qtd' => (int)substr($codbar, 14, 5),
        );
    }
    if ($len === 17) {
        return array(
            'lote' => str_pad(substr($codbar, 0, 8), 8, '0', STR_PAD_LEFT),
            'posto' => str_pad(substr($codbar, 9, 3), 3, '0', STR_PAD_LEFT),
            'qtd' => (int)substr($codbar, 12, 5),
        );
    }
    return null;
}

/* -----------------------------------------------------------------------
 *  Buscas
 * ----------------------------------------------------------------------- */
function buscarDespachoIds($pdo, $det) {
    /* Retorna array de despacho_id encontrados a partir do tipo de entrada. */
    $tipo = $det['tipo'];
    $val = $det['valor'];

    if ($tipo === 'despacho_id') {
        return array((int)$val);
    }
    if ($tipo === 'etiqueta') {
        $sql = "(SELECT DISTINCT id_despacho FROM ciDespachoLotes WHERE etiqueta_correios = ?)"
             . " UNION "
             . "(SELECT DISTINCT id_despacho FROM ciDespachoItens WHERE etiqueta_correios = ?)";
        $st = $pdo->prepare($sql);
        $st->execute(array($val, $val));
        $out = array();
        while ($r = $st->fetch()) $out[] = (int)$r['id_despacho'];
        return array_values(array_unique($out));
    }
    if ($tipo === 'lote' || $tipo === 'lote_oficio') {
        $out = array();
        // a) procura como lote dentro dos despachos
        $st = $pdo->prepare("SELECT DISTINCT id_despacho FROM ciDespachoLotes WHERE lote = ? OR LPAD(CAST(lote AS CHAR),8,'0') = ?");
        $st->execute(array($val, $val));
        while ($r = $st->fetch()) $out[] = (int)$r['id_despacho'];
        // b) procura tambem como NUMERO do oficio (id do despacho)
        if ($tipo === 'lote_oficio') {
            $oficio = isset($det['oficio']) ? (int)$det['oficio'] : 0;
            if ($oficio > 0) {
                $st2 = $pdo->prepare("SELECT id FROM ciDespachos WHERE id = ? LIMIT 1");
                $st2->execute(array($oficio));
                while ($r2 = $st2->fetch()) $out[] = (int)$r2['id'];
            }
        }
        return array_values(array_unique($out));
    }
    if ($tipo === 'display') {
        $ext = extrairDoDisplay($val);
        if (!$ext) return array();
        $st = $pdo->prepare("SELECT DISTINCT id_despacho FROM ciDespachoLotes WHERE (lote = ? OR LPAD(CAST(lote AS CHAR),8,'0') = ?) AND (posto = ? OR LPAD(CAST(posto AS CHAR),3,'0') = ?)");
        $st->execute(array($ext['lote'], $ext['lote'], $ext['posto'], $ext['posto']));
        $out = array();
        while ($r = $st->fetch()) $out[] = (int)$r['id_despacho'];
        return array_values(array_unique($out));
    }
    return array();
}

function carregarDespacho($pdo, $id) {
    $st = $pdo->prepare("SELECT id, grupo, usuario, criado_em, datas_str, hash_chave FROM ciDespachos WHERE id = ? LIMIT 1");
    $st->execute(array($id));
    return $st->fetch();
}

function carregarItens($pdo, $id) {
    // Compatibilidade com duas variantes de schema (id_despacho/posto e idDespacho/codigoPosto)
    try {
        $st = $pdo->prepare("SELECT
            LPAD(CAST(posto AS CHAR),3,'0') AS posto,
            COALESCE(nome_posto, '') AS nome_posto,
            COALESCE(endereco, '') AS endereco,
            COALESCE(lacre_iipr, '') AS lacre_iipr,
            COALESCE(lacre_correios, '') AS lacre_correios,
            COALESCE(etiqueta_correios, '') AS etiqueta_correios,
            COALESCE(quantidade, 0) AS quantidade,
            COALESCE(lote, '') AS lote
            FROM ciDespachoItens
            WHERE id_despacho = ?
            ORDER BY CAST(posto AS UNSIGNED)");
        $st->execute(array($id));
        return $st->fetchAll();
    } catch (PDOException $e) {
        return array();
    }
}

function carregarLotes($pdo, $id) {
    try {
        $st = $pdo->prepare("SELECT
            id,
            LPAD(CAST(lote AS CHAR),8,'0') AS lote,
            LPAD(CAST(posto AS CHAR),3,'0') AS posto,
            COALESCE(etiqueta_correios,'') AS etiqueta_correios,
            COALESCE(data_carga,'') AS data_carga,
            COALESCE(quantidade,0) AS quantidade,
            COALESCE(data_despacho_correios,'') AS data_despacho_correios,
            COALESCE(despachado_por,'') AS despachado_por,
            COALESCE(responsaveis,'') AS responsaveis
            FROM ciDespachoLotes
            WHERE id_despacho = ?
            ORDER BY CAST(posto AS UNSIGNED), CAST(lote AS UNSIGNED)");
        $st->execute(array($id));
        $rows = $st->fetchAll();
        // Lotes manuais podem ter posto NAO numerico (ex.: "M17"). Tenta resolver
        // o posto real pela etiqueta dos Correios (cadastroMalotes).
        $n = count($rows);
        for ($i = 0; $i < $n; $i++) {
            $p = trim((string)$rows[$i]['posto']);
            $rows[$i]['posto_origem'] = '';
            if ($p !== '' && !ctype_digit($p) && $rows[$i]['etiqueta_correios'] !== '') {
                $resolv = resolverPostoEtiqueta($pdo, $rows[$i]['etiqueta_correios']);
                if ($resolv !== null && $resolv !== '') {
                    $rows[$i]['posto_origem'] = $p;
                    $rows[$i]['posto'] = str_pad($resolv, 3, '0', STR_PAD_LEFT);
                }
            }
        }
        return $rows;
    } catch (PDOException $e) {
        return array();
    }
}

function carregarConferencia($pdo, $lotes) {
    /* Retorna mapa: chave "posto|lote" => array de leituras na conferencia_pacotes */
    if (count($lotes) === 0) return array();
    $chaves = array();
    $params = array();
    $i = 0;
    foreach ($lotes as $l) {
        $chaves[] = "(LPAD(CAST(nposto AS UNSIGNED),3,'0') = ? AND LPAD(CAST(nlote AS UNSIGNED),8,'0') = ?)";
        $params[] = $l['posto'];
        $params[] = $l['lote'];
        $i++;
        if ($i > 200) break; // limite de seguranca
    }
    $sql = "SELECT
        LPAD(CAST(nposto AS UNSIGNED),3,'0') AS posto,
        LPAD(CAST(nlote AS UNSIGNED),8,'0') AS lote,
        COUNT(*) AS total_lido,
        SUM(CASE WHEN conf='s' THEN 1 ELSE 0 END) AS total_conf,
        MAX(lido_em) AS ultima_leitura,
        MAX(usuario) AS conferido_por,
        MAX(conferido_em) AS conferido_em,
        MAX(dataexp) AS data_exp
        FROM conferencia_pacotes
        WHERE " . implode(' OR ', $chaves) . "
        GROUP BY LPAD(CAST(nposto AS UNSIGNED),3,'0'), LPAD(CAST(nlote AS UNSIGNED),8,'0')";
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $out = array();
        while ($r = $st->fetch()) {
            $out[$r['posto'] . '|' . $r['lote']] = $r;
        }
        return $out;
    } catch (PDOException $e) {
        return array();
    }
}

function carregarDevolucao($pdo, $etiquetas) {
    /* Retorna mapa etiqueta => array(2 => array('quando','quem')) com a DEVOLUCAO
       (retorno) do display. A devolucao e o movimento tipo=2 em ciMalotes, onde
       ciMalotes.leitura = etiqueta_correios (mesmo valor de 35 digitos).
       Considera-se devolvido somente o tipo=2 POSTERIOR ao ultimo envio (tipo=1). */
    if (count($etiquetas) === 0) return array();
    $etiquetas = array_values(array_unique(array_filter($etiquetas, 'strlen')));
    if (count($etiquetas) === 0) return array();

    $colsM = colunasTabela($pdo, 'ciMalotes');
    if (empty($colsM) || !isset($colsM['leitura']) || !isset($colsM['tipo'])
        || !isset($colsM['data']) || !isset($colsM['id'])) {
        return array();
    }
    $colQuem = isset($colsM['login']) ? 'login' : (isset($colsM['usuario']) ? 'usuario' : null);
    $ph = implode(',', array_fill(0, count($etiquetas), '?'));
    $out = array();

    // 1) ultimo ENVIO (tipo=1) por etiqueta — para garantir que o retorno e posterior
    $envio = array();
    try {
        $st = $pdo->prepare("SELECT leitura, MAX(id) AS envio_id
                             FROM ciMalotes WHERE tipo=1 AND leitura IN ($ph) GROUP BY leitura");
        $st->execute($etiquetas);
        while ($r = $st->fetch()) { $envio[$r['leitura']] = (int)$r['envio_id']; }
    } catch (Exception $e) {}

    // 2) ultimo RETORNO (tipo=2) por etiqueta
    try {
        $selQuem = $colQuem ? ", m." . $colQuem . " AS quem" : "";
        $st = $pdo->prepare("SELECT m.leitura, m.id AS rid, m.data AS quando" . $selQuem . "
                             FROM ciMalotes m
                             INNER JOIN (SELECT leitura, MAX(id) AS mid
                                         FROM ciMalotes WHERE tipo=2 AND leitura IN ($ph)
                                         GROUP BY leitura) x ON m.id = x.mid");
        $st->execute($etiquetas);
        while ($r = $st->fetch()) {
            $k = $r['leitura'];
            // retorno deve ser posterior ao envio (quando houver envio conhecido)
            if (isset($envio[$k]) && (int)$r['rid'] <= $envio[$k]) continue;
            $out[$k] = array(2 => array(
                'quando' => $r['quando'],
                'quem'   => isset($r['quem']) ? $r['quem'] : '',
            ));
        }
    } catch (Exception $e) {}

    return $out;
}

/* Resolve o codigo de posto a partir da etiqueta dos Correios (CEP+sequencial)
   consultando cadastroMalotes. Usado para lotes manuais cujo posto gravado nao
   e numerico (ex.: "M17"). Retorna string numerica do posto ou null. */
function resolverPostoEtiqueta($pdo, $etiqueta) {
    $et = preg_replace('/\D+/', '', (string)$etiqueta);
    if (strlen($et) < 13) return null;
    $cep = substr($et, 0, 8);
    $seq = substr($et, -5);
    try {
        $s = $pdo->prepare('SELECT posto FROM cadastroMalotes WHERE cep=? AND sequencial=? ORDER BY id DESC LIMIT 1');
        $s->execute(array($cep, $seq));
        $p = $s->fetchColumn();
        if ($p !== false && preg_replace('/\D+/', '', (string)$p) !== '') return preg_replace('/\D+/', '', (string)$p);
        $s2 = $pdo->prepare('SELECT posto FROM cadastroMalotes WHERE cep=? ORDER BY id DESC LIMIT 1');
        $s2->execute(array($cep));
        $p2 = $s2->fetchColumn();
        if ($p2 !== false && preg_replace('/\D+/', '', (string)$p2) !== '') return preg_replace('/\D+/', '', (string)$p2);
    } catch (Exception $e) {}
    return null;
}

/* -----------------------------------------------------------------------
 *  Status do lote QUANDO ainda NAO existe oficio
 *  Etapas (mais avancada -> menos): em_malote > conferido > estante >
 *  expedido(carrinho) > produzido > desconhecido. "retido" e um aviso extra.
 * ----------------------------------------------------------------------- */
function statusLoteSemOficio($pdo, $lote, $posto) {
    $loteN = str_pad(preg_replace('/\D+/', '', (string)$lote), 8, '0', STR_PAD_LEFT);
    $loteInt = (int)$loteN;
    $info = array(
        'lote' => $loteN, 'posto' => $posto,
        'etapa' => 'desconhecido', 'rotulo' => 'Sem registro no sistema',
        'producao' => null, 'estante' => null, 'conferido' => null,
        'oficio' => null, 'retido' => array(),
    );

    /* 1) Producao (ciPostosCsv) */
    $colsPC = colunasTabela($pdo, 'ciPostosCsv');
    if (!empty($colsPC) && isset($colsPC['lote'])) {
        $cData = '';
        foreach (array('dataCarga','data_carga','data','criado_em') as $cc) {
            if (isset($colsPC[strtolower($cc)])) { $cData = $colsPC[strtolower($cc)]; break; }
        }
        $sel = "lote AS _lote";
        if (isset($colsPC['posto']))      $sel .= ", posto AS posto";
        if (isset($colsPC['regional']))   $sel .= ", regional AS regional";
        if (isset($colsPC['quantidade'])) $sel .= ", quantidade AS quantidade";
        if (isset($colsPC['usuario']))    $sel .= ", usuario AS usuario";
        if ($cData !== '')                $sel .= ", `$cData` AS dt";
        try {
            $st = $pdo->prepare("SELECT $sel FROM ciPostosCsv WHERE lote=? OR CAST(lote AS UNSIGNED)=? ORDER BY " . ($cData !== '' ? "`$cData` DESC" : "lote DESC") . " LIMIT 1");
            $st->execute(array($loteN, $loteInt));
            $r = $st->fetch();
            if ($r) $info['producao'] = $r;
        } catch (Exception $e) {}
    }

    /* 2) Estante (lotes_na_estante - triado) */
    $colsLE = colunasTabela($pdo, 'lotes_na_estante');
    if (!empty($colsLE) && isset($colsLE['lote'])) {
        $sel = "lote AS _lote";
        if (isset($colsLE['posto']))      $sel .= ", posto AS posto";
        if (isset($colsLE['triado_em']))  $sel .= ", triado_em AS triado_em";
        if (isset($colsLE['triado_por'])) $sel .= ", triado_por AS triado_por";
        try {
            $st = $pdo->prepare("SELECT $sel FROM lotes_na_estante WHERE lote=? OR CAST(lote AS UNSIGNED)=? LIMIT 1");
            $st->execute(array($loteN, $loteInt));
            $r = $st->fetch();
            if ($r) $info['estante'] = $r;
        } catch (Exception $e) {}
    }

    /* 3) Conferido (conferencia_pacotes conf='s') */
    $colsCP = colunasTabela($pdo, 'conferencia_pacotes');
    if (!empty($colsCP) && isset($colsCP['nlote'])) {
        $cDt = '';
        foreach (array('conferido_em','lido_em','data','criado_em') as $cc) {
            if (isset($colsCP[strtolower($cc)])) { $cDt = $colsCP[strtolower($cc)]; break; }
        }
        $sel = "nlote AS _lote, conf AS conf";
        if (isset($colsCP['nposto']))  $sel .= ", nposto AS posto";
        if (isset($colsCP['usuario'])) $sel .= ", usuario AS usuario";
        if ($cDt !== '')               $sel .= ", `$cDt` AS dt";
        try {
            $st = $pdo->prepare("SELECT $sel FROM conferencia_pacotes WHERE (nlote=? OR CAST(nlote AS UNSIGNED)=?) ORDER BY (CASE WHEN LOWER(conf)='s' THEN 0 ELSE 1 END) " . ($cDt !== '' ? ", `$cDt` DESC" : "") . " LIMIT 1");
            $st->execute(array($loteN, $loteInt));
            $r = $st->fetch();
            if ($r) {
                $c = strtolower(trim((string)$r['conf']));
                if ($c === 's' || $c === 'sim' || $c === '1' || $c === 'y') $info['conferido'] = $r;
                elseif (!$info['estante']) $info['estante'] = $r;
            }
        } catch (Exception $e) {}
    }

    /* 4) Oficio gerado (ciDespachoLotes) - normalmente ja teria caido na busca */
    try {
        $st = $pdo->prepare("SELECT id_despacho, posto FROM ciDespachoLotes WHERE lote=? OR CAST(lote AS UNSIGNED)=? LIMIT 1");
        $st->execute(array($loteN, $loteInt));
        $r = $st->fetch();
        if ($r) $info['oficio'] = $r;
    } catch (Exception $e) {}

    /* 5) Retencao por posto (bloqueio/restricao ativos) */
    $postoN = ($posto !== null && $posto !== '') ? str_pad(preg_replace('/\D+/', '', (string)$posto), 3, '0', STR_PAD_LEFT) : '';
    if ($postoN === '' && $info['producao'] && isset($info['producao']['posto'])) {
        $postoN = str_pad(preg_replace('/\D+/', '', (string)$info['producao']['posto']), 3, '0', STR_PAD_LEFT);
    }
    if ($postoN !== '') {
        $postoInt = (int)$postoN;
        $colsBl = colunasTabela($pdo, 'ciPostosBloqueados');
        if (!empty($colsBl) && isset($colsBl['posto'])) {
            try {
                $st = $pdo->prepare("SELECT * FROM ciPostosBloqueados WHERE (posto=? OR CAST(posto AS UNSIGNED)=?)" . (isset($colsBl['ativo']) ? " AND ativo=1" : "") . " LIMIT 1");
                $st->execute(array($postoN, $postoInt));
                $r = $st->fetch();
                if ($r) $info['retido'][] = array('tipo' => 'Bloqueio', 'motivo' => isset($r['motivo']) ? $r['motivo'] : '');
            } catch (Exception $e) {}
        }
        $colsRe = colunasTabela($pdo, 'ciPostosRestricoes');
        if (!empty($colsRe) && isset($colsRe['posto'])) {
            try {
                $st = $pdo->prepare("SELECT * FROM ciPostosRestricoes WHERE (posto=? OR CAST(posto AS UNSIGNED)=?)" . (isset($colsRe['ativo']) ? " AND ativo=1" : "") . " LIMIT 1");
                $st->execute(array($postoN, $postoInt));
                $r = $st->fetch();
                if ($r) $info['retido'][] = array('tipo' => isset($r['tipo']) ? $r['tipo'] : 'Restricao', 'motivo' => isset($r['motivo']) ? $r['motivo'] : '');
            } catch (Exception $e) {}
        }
    }

    /* Decide etapa final */
    if ($info['oficio'])         { $info['etapa'] = 'em_malote'; $info['rotulo'] = 'Ofício gerado — dentro de malote dos Correios'; }
    elseif ($info['conferido'])  { $info['etapa'] = 'conferido'; $info['rotulo'] = 'Conferido — aguardando fechamento/despacho'; }
    elseif ($info['estante'])    { $info['etapa'] = 'estante';   $info['rotulo'] = 'Na estante — aguardando conferência'; }
    elseif ($info['producao'])   { $info['etapa'] = 'expedido';  $info['rotulo'] = 'Expedido — na mão de quem expediu ou no carrinho (antes da triagem)'; }
    else                         { $info['etapa'] = 'desconhecido'; $info['rotulo'] = 'Sem registro no sistema'; }
    return $info;
}

/* Inferencia de despacho pelo recebimento de displays (ciMalotes tipo=2).
 *
 * Regra de negocio (confirmada pelo usuario): no dia em que os Correios passam,
 * eles LEVAM os malotes/oficios e DEIXAM displays de varios postos que sairam em
 * datas passadas; esses retornos sao cadastrados como tipo=2 no MESMO dia. Logo,
 * QUALQUER recebimento de displays a partir da criacao do oficio ja indica que
 * houve despacho naquele dia -- NAO e preciso que voltem exatamente os displays
 * deste oficio (por isso nao se filtra por posto).
 *
 * Ancora = data de criacao do oficio (criado_em). Como o oficio so e gerado depois
 * da conferencia (ciclo: conferido -> oficio gerado -> despachado), ancorar em
 * criado_em ja garante que o despacho nunca aparece antes da conferencia, sem
 * precisar empurrar a data pela ultima conferencia (o que atrasava demais quando
 * havia conferencia tardia de algum lote).
 *
 * Retorna array('quando'=>data do 1o recebimento, 'total'=>n recebidos nesse dia),
 * ou null. */
/* Resolve a data-ancora do despacho. O ideal e ciDespachos.criado_em, mas em
 * oficios CORREIOS antigos esse campo costuma vir vazio -- nesse caso usamos a
 * menor data_carga dos lotes (DATE limpo) e, em ultimo caso, a 1a data de
 * datas_str (aceita YYYY-MM-DD, DD-MM-YYYY ou DD/MM/YYYY). Retorna
 * 'YYYY-MM-DD 00:00:00' ou '' quando nada e identificavel. */
function resolverAncoraDespacho($criadoEm, $lotes, $datasStr) {
    $base = trim((string)$criadoEm);
    if ($base !== '' && strpos($base, '0000-00-00') !== 0
        && preg_match('/^\d{4}-\d{2}-\d{2}/', $base)) {
        return substr($base, 0, 10) . ' 00:00:00';
    }
    $min = '';
    if (is_array($lotes)) {
        foreach ($lotes as $l) {
            $dc = isset($l['data_carga']) ? trim((string)$l['data_carga']) : '';
            if ($dc !== '' && strpos($dc, '0000-00-00') !== 0
                && preg_match('/^\d{4}-\d{2}-\d{2}/', $dc)) {
                $dia = substr($dc, 0, 10);
                if ($min === '' || $dia < $min) $min = $dia;
            }
        }
    }
    if ($min !== '') return $min . ' 00:00:00';
    $s = trim((string)$datasStr);
    if ($s !== '') {
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3] . ' 00:00:00';
        }
        if (preg_match('#(\d{2})[/-](\d{2})[/-](\d{4})#', $s, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1] . ' 00:00:00';
        }
    }
    return '';
}

function inferirDespachoPorRecebimento($pdo, $criadoEm) {
    $colsM = colunasTabela($pdo, 'ciMalotes');
    if (empty($colsM) || !isset($colsM['tipo']) || !isset($colsM['data'])) return null;

    $base = trim((string)$criadoEm);
    if ($base === '' || strpos($base, '0000-00-00') === 0) return null;

    // Piso no INICIO do dia da criacao do oficio. Usar limites de dia (em vez de
    // DATE(data)) mantem a comparacao no nivel do dia -- ciMalotes.data pode ser
    // DATE (sem hora) e criado_em vem como DATETIME -- E deixa o filtro "sargable"
    // (usa indice em (tipo,data) em vez de varrer a tabela com DATE(data)).
    $diaCriacao = substr($base, 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $diaCriacao)) return null;
    $pisoDia = $diaCriacao . ' 00:00:00';

    try {
        // 1o recebimento de displays a partir do dia de criacao do oficio.
        $st = $pdo->prepare("SELECT MIN(data) AS quando FROM ciMalotes WHERE tipo=2 AND data >= ?");
        $st->execute(array($pisoDia));
        $r = $st->fetch();
        if (!$r || empty($r['quando'])) return null;
        $quando = $r['quando'];

        // Quantos displays foram recebidos nesse mesmo dia (sinal do despacho).
        $diaQ = substr((string)$quando, 0, 10);
        $iniDia = $diaQ . ' 00:00:00';
        $fimDia = date('Y-m-d', strtotime($diaQ . ' +1 day')) . ' 00:00:00';
        $st2 = $pdo->prepare("SELECT COUNT(*) AS total FROM ciMalotes WHERE tipo=2 AND data >= ? AND data < ?");
        $st2->execute(array($iniDia, $fimDia));
        $r2 = $st2->fetch();
        return array('quando' => $quando, 'total' => $r2 ? (int)$r2['total'] : 0);
    } catch (Exception $e) {}
    return null;
}

/* -----------------------------------------------------------------------
 *  Endpoint CSV
 * ----------------------------------------------------------------------- */
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';
$q = isset($_GET['q']) ? $_GET['q'] : (isset($_POST['q']) ? $_POST['q'] : '');
$modo = isset($_GET['modo']) ? $_GET['modo'] : (isset($_POST['modo']) ? $_POST['modo'] : 'auto');
if ($modo !== 'lote' && $modo !== 'oficio') $modo = 'auto';
$msgDespacho = '';

/* Garante colunas de despacho em ciDespachoLotes (tolerante; mesmo padrao do painel_lotes) */
function garantirColunasDespacho($pdo) {
    try {
        $c1 = $pdo->query("SHOW COLUMNS FROM ciDespachoLotes LIKE 'data_despacho_correios'")->fetchAll();
        if (empty($c1)) $pdo->exec("ALTER TABLE ciDespachoLotes ADD COLUMN data_despacho_correios DATE NULL DEFAULT NULL AFTER etiqueta_correios");
        $c2 = $pdo->query("SHOW COLUMNS FROM ciDespachoLotes LIKE 'despachado_por'")->fetchAll();
        if (empty($c2)) $pdo->exec("ALTER TABLE ciDespachoLotes ADD COLUMN despachado_por VARCHAR(100) NULL DEFAULT NULL AFTER data_despacho_correios");
    } catch (Exception $e) { /* sem permissao de DDL - segue */ }
}

/* Marcar / desfazer despacho de um oficio inteiro (POST) */
$acaoPost = isset($_POST['acao_despacho']) ? trim((string)$_POST['acao_despacho']) : '';
if ($acaoPost !== '') {
    try {
        $pdo = pdoControle();
        garantirColunasDespacho($pdo);
        $idDesp = (int)(isset($_POST['id_despacho']) ? $_POST['id_despacho'] : 0);
        if ($acaoPost === 'marcar' && $idDesp > 0) {
            $resp = trim((string)(isset($_POST['responsavel']) ? $_POST['responsavel'] : ''));
            if ($resp === '') {
                $msgDespacho = 'Informe o responsavel para marcar o oficio como despachado.';
            } else {
                $st = $pdo->prepare("UPDATE ciDespachoLotes SET data_despacho_correios=CURDATE(), despachado_por=? WHERE id_despacho=?");
                $st->execute(array($resp, $idDesp));
                $msgDespacho = 'Oficio #' . $idDesp . ' marcado como despachado (' . $st->rowCount() . ' lotes).';
            }
        } elseif ($acaoPost === 'desfazer' && $idDesp > 0) {
            $st = $pdo->prepare("UPDATE ciDespachoLotes SET data_despacho_correios=NULL, despachado_por=NULL WHERE id_despacho=?");
            $st->execute(array($idDesp));
            $msgDespacho = 'Despacho do oficio #' . $idDesp . ' desfeito (' . $st->rowCount() . ' lotes).';
        }
    } catch (Exception $ex) {
        $msgDespacho = 'Erro ao atualizar despacho: ' . $ex->getMessage();
    }
}

if ($acao === 'csv' && $q !== '') {
    try {
        $pdo = pdoControle();
        garantirColunasDespacho($pdo);
        $det = detectarTipo($q, $modo);
        $ids = buscarDespachoIds($pdo, $det);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rastreabilidade_' . preg_replace('/\W+/', '_', $q) . '.csv"');
        $fp = fopen('php://output', 'w');
        // BOM para Excel
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, array('Oficio_ID','Grupo','Usuario_Oficio','Criado_em','Datas','Posto','Nome_Posto','Lote','Etiqueta_Correios','Lacre_IIPR','Lacre_Correios','Qtd_Despachada','Qtd_Conferida','Ultima_Leitura','Despachado_em','Despachado_por','Recebido_em','Recebido_por'), ';');
        foreach ($ids as $id) {
            $desp = carregarDespacho($pdo, $id);
            if (!$desp) continue;
            $lotes = carregarLotes($pdo, $id);
            $itens = carregarItens($pdo, $id);
            $mapNome = array();
            foreach ($itens as $it) {
                $mapNome[$it['posto']] = $it;
            }
            $etiq = array();
            foreach ($lotes as $l) if ($l['etiqueta_correios'] !== '') $etiq[] = $l['etiqueta_correios'];
            $conf = carregarConferencia($pdo, $lotes);
            $dev = carregarDevolucao($pdo, $etiq);
            foreach ($lotes as $l) {
                $k = $l['posto'] . '|' . $l['lote'];
                $cf = isset($conf[$k]) ? $conf[$k] : null;
                $nome = isset($mapNome[$l['posto']]) ? $mapNome[$l['posto']]['nome_posto'] : '';
                $lacIp = isset($mapNome[$l['posto']]) ? $mapNome[$l['posto']]['lacre_iipr'] : '';
                $lacCr = isset($mapNome[$l['posto']]) ? $mapNome[$l['posto']]['lacre_correios'] : '';
                $qtdDesp = isset($mapNome[$l['posto']]) ? $mapNome[$l['posto']]['quantidade'] : '';
                $usaEtqCsv = postoUsaEtiquetaCorreios($desp['grupo'], $l['posto']);
                $etqCsv = $usaEtqCsv ? $l['etiqueta_correios'] : '';
                $devRec = ($etqCsv !== '' && isset($dev[$etqCsv][2])) ? $dev[$etqCsv][2] : null;
                fputcsv($fp, array(
                    $desp['id'], $desp['grupo'], $desp['usuario'], $desp['criado_em'], $desp['datas_str'],
                    $l['posto'], $nome, $l['lote'], $etqCsv,
                    $lacIp, $lacCr,
                    $qtdDesp,
                    $cf ? $cf['total_conf'] : '',
                    $cf ? $cf['ultima_leitura'] : '',
                    $l['data_despacho_correios'], $l['despachado_por'],
                    $devRec ? $devRec['quando'] : '', $devRec ? $devRec['quem'] : ''
                ), ';');
            }
        }
        fclose($fp);
        exit;
    } catch (Exception $ex) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Erro ao gerar CSV: " . $ex->getMessage();
        exit;
    }
}

/* -----------------------------------------------------------------------
 *  Renderizacao HTML
 * ----------------------------------------------------------------------- */
$resultados = array();
$detEntrada = array('tipo' => 'vazio', 'valor' => '');
$erro = '';
$statusSemOficio = null;
if ($q !== '') {
    try {
        $pdo = pdoControle();
        garantirColunasDespacho($pdo);
        $detEntrada = detectarTipo($q, $modo);
        $ids = buscarDespachoIds($pdo, $detEntrada);
        if (count($ids) === 0) {
            $erro = 'Nenhum oficio encontrado para essa busca.';
            // Tenta mostrar em que etapa do ciclo de vida o lote esta
            $loteSt = '';
            $postoSt = null;
            if ($detEntrada['tipo'] === 'display') {
                $ext = extrairDoDisplay($detEntrada['valor']);
                if ($ext) { $loteSt = $ext['lote']; $postoSt = $ext['posto']; }
            } elseif ($detEntrada['tipo'] === 'lote' || $detEntrada['tipo'] === 'lote_oficio') {
                $loteSt = $detEntrada['valor'];
            }
            if ($loteSt !== '') {
                $statusSemOficio = statusLoteSemOficio($pdo, $loteSt, $postoSt);
            }
        } else {
            foreach ($ids as $id) {
                $desp = carregarDespacho($pdo, $id);
                if (!$desp) continue;
                $itens = carregarItens($pdo, $id);
                $lotes = carregarLotes($pdo, $id);
                $etiq = array();
                foreach ($lotes as $l) if ($l['etiqueta_correios'] !== '') $etiq[] = $l['etiqueta_correios'];
                $conf = carregarConferencia($pdo, $lotes);
                $dev = carregarDevolucao($pdo, $etiq);
                $resultados[] = array(
                    'despacho' => $desp,
                    'itens' => $itens,
                    'lotes' => $lotes,
                    'conferencia' => $conf,
                    'devolucao' => $dev,
                );
            }
        }
    } catch (Exception $ex) {
        $erro = 'Erro: ' . $ex->getMessage();
    }
}

function qrUrl($texto, $size) {
    if ($size <= 0) $size = 120;
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($texto);
}

function urlBusca($valor) {
    $base = $_SERVER['PHP_SELF'];
    return $base . '?q=' . urlencode($valor);
}

function urlAbsoluta($valor) {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    return $proto . '://' . $host . urlBusca($valor);
}

function rotuloTipo($tipo) {
    $map = array(
        'lote' => 'Lote',
        'lote_oficio' => 'Lote ou numero do oficio',
        'etiqueta' => 'Etiqueta Correios',
        'display' => 'Codigo de barras do display',
        'despacho_id' => 'Numero do oficio',
        'vazio' => '',
        'desconhecido' => 'Entrada nao reconhecida',
    );
    return isset($map[$tipo]) ? $map[$tipo] : $tipo;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Rastreabilidade - Lacres</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { box-sizing:border-box; }
body { font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif; background:#f3f4f6; color:#111; margin:0; padding:0; }
.header { background:#0f172a; color:#fff; padding:16px 24px; display:flex; align-items:center; gap:16px; }
.header h1 { margin:0; font-size:20px; font-weight:600; }
.header .nav { margin-left:auto; display:flex; gap:8px; }
.header .nav a { color:#cbd5e1; text-decoration:none; font-size:13px; padding:6px 12px; border-radius:6px; }
.header .nav a:hover { background:#1e293b; color:#fff; }
.container { max-width:1280px; margin:0 auto; padding:24px; }
.search-box { background:#fff; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.1); margin-bottom:24px; }
.search-box label { display:block; font-size:12px; font-weight:600; color:#475569; text-transform:uppercase; margin-bottom:8px; }
.search-row { display:flex; gap:8px; flex-wrap:wrap; }
.search-row select { padding:12px 14px; font-size:14px; border:2px solid #cbd5e1; border-radius:8px; outline:none; background:#fff; cursor:pointer; }
.search-row select:focus { border-color:#3b82f6; }
.search-row input[type=text] { flex:1; min-width:220px; padding:12px 16px; font-size:16px; border:2px solid #cbd5e1; border-radius:8px; outline:none; }
.search-row input[type=text]:focus { border-color:#3b82f6; }
.search-row button { padding:12px 24px; font-size:14px; font-weight:600; border:none; border-radius:8px; cursor:pointer; background:#3b82f6; color:#fff; }
.search-row button:hover { background:#2563eb; }
.search-row .btn-csv { background:#10b981; }
.search-row .btn-csv:hover { background:#059669; }
.search-row .btn-print { background:#6b7280; }
.exemplos { margin-top:12px; font-size:12px; color:#64748b; }
.exemplos a { color:#3b82f6; text-decoration:none; margin-right:12px; }
.deteccao { margin-top:8px; font-size:13px; color:#0f172a; }
.deteccao .badge { display:inline-block; background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:4px; font-weight:600; }
.alerta { background:#fef3c7; border:1px solid #fcd34d; color:#92400e; padding:12px 16px; border-radius:8px; margin-bottom:16px; }
.erro { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px; }
.timeline { position:relative; padding-left:32px; }
.timeline::before { content:''; position:absolute; left:12px; top:0; bottom:0; width:2px; background:#cbd5e1; }
.tl-item { position:relative; margin-bottom:20px; }
.tl-item::before { content:''; position:absolute; left:-26px; top:8px; width:14px; height:14px; border-radius:50%; background:#3b82f6; border:3px solid #fff; box-shadow:0 0 0 2px #3b82f6; }
.tl-item.tl-conferencia::before { background:#10b981; box-shadow:0 0 0 2px #10b981; }
.tl-item.tl-devolucao::before { background:#f59e0b; box-shadow:0 0 0 2px #f59e0b; }
.card { background:#fff; border-radius:10px; padding:16px; box-shadow:0 1px 2px rgba(0,0,0,.08); }
.card h2 { margin:0 0 12px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap:10px; }
.card h2 .pill { background:#0f172a; color:#fff; font-size:11px; padding:2px 8px; border-radius:10px; }
.card h2 .pill.pt { background:#3b82f6; }
.card h2 .pill.cr { background:#f59e0b; }
.card .meta { font-size:13px; color:#475569; margin-bottom:8px; }
.card .meta strong { color:#0f172a; }
.card .meta span { margin-right:16px; }
table.dados { width:100%; border-collapse:collapse; font-size:12px; }
table.dados th { background:#f1f5f9; padding:6px 8px; text-align:left; font-weight:600; color:#334155; border-bottom:2px solid #e2e8f0; }
table.dados td { padding:6px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
table.dados tr:hover { background:#f8fafc; }
.qr-cell { width:64px; }
.qr-cell img { width:60px; height:60px; display:block; }
.qr-cell a { display:block; font-size:10px; text-align:center; color:#3b82f6; text-decoration:none; }
.status-ok { color:#059669; font-weight:600; }
.status-warn { color:#d97706; font-weight:600; }
.status-pend { color:#dc2626; font-weight:600; }
.etapas { display:flex; gap:8px; flex-wrap:wrap; }
.etapa { flex:1; min-width:120px; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; display:flex; align-items:center; gap:8px; background:#f8fafc; }
.etapa .et-num { width:24px; height:24px; border-radius:50%; background:#cbd5e1; color:#fff; font-weight:700; font-size:12px; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
.etapa .et-rot { font-size:13px; color:#475569; font-weight:600; }
.etapa.et-ok { background:#ecfdf5; border-color:#a7f3d0; }
.etapa.et-ok .et-num { background:#10b981; }
.etapa.et-ok .et-rot { color:#065f46; }
.etapa.et-atual { background:#fffbeb; border-color:#fcd34d; box-shadow:0 0 0 2px #fcd34d inset; }
.etapa.et-atual .et-num { background:#f59e0b; }
.etapa.et-atual .et-rot { color:#92400e; }
.etapa.et-off { opacity:.6; }
.btn-despacho { padding:8px 14px; font-size:13px; font-weight:600; border:none; border-radius:6px; cursor:pointer; }
.btn-despacho.marcar { background:#10b981; color:#fff; }
.btn-despacho.desfazer { background:#e2e8f0; color:#334155; }
.box-despacho { margin-top:12px; padding:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.box-despacho input[type=text] { padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; }
.infer-despacho { color:#0369a1; font-weight:600; }
.qr-grande { display:inline-block; margin:8px; text-align:center; }
.qr-grande img { width:140px; height:140px; }
.qr-grande .qr-label { font-size:11px; color:#475569; margin-top:4px; word-break:break-all; max-width:140px; }
@media print {
    body { background:#fff; }
    .header, .search-box, .nav, .no-print { display:none !important; }
    .container { max-width:none; padding:0; }
    .card { box-shadow:none; border:1px solid #cbd5e1; page-break-inside:avoid; }
    .timeline::before { background:#94a3b8; }
}
</style>
</head>
<body>

<div class="header">
    <h1>Rastreabilidade</h1>
    <div class="nav">
        <a href="inicio.php">&larr; Inicio</a>
        <a href="lacres_novo.php">Lacres</a>
        <a href="despachos_poupatempo.php">Despachos</a>
        <a href="consulta_producao.php">Producao</a>
        <a href="devolucao_etiquetas.php">Devolucao</a>
        <a href="conferencia_inventario.php">Inventario</a>
    </div>
</div>

<div class="container">

<div class="search-box no-print">
    <form method="get" action="">
        <label>Buscar por etiqueta dos Correios, codigo do display, lote ou numero do oficio</label>
        <div class="search-row">
            <select name="modo" title="O que voce esta buscando?">
                <option value="auto"<?php echo ($modo === 'auto' ? ' selected' : ''); ?>>Detectar automaticamente</option>
                <option value="lote"<?php echo ($modo === 'lote' ? ' selected' : ''); ?>>Numero do lote</option>
                <option value="oficio"<?php echo ($modo === 'oficio' ? ' selected' : ''); ?>>Numero do oficio</option>
            </select>
            <input type="text" name="q" value="<?php echo e($q); ?>" autofocus
                   placeholder="Ex: 00768036  |  PA123456789BR...  |  0076803600100500193  |  OF1234">
            <button type="submit">Buscar</button>
            <?php if ($q !== ''): ?>
                <button type="button" class="btn-csv" onclick="window.location='?acao=csv&modo=<?php echo urlencode($modo); ?>&q=<?php echo urlencode($q); ?>'">Export CSV</button>
                <button type="button" class="btn-print" onclick="window.print()">Imprimir</button>
            <?php endif; ?>
        </div>
        <?php if ($q === ''): ?>
            <div class="exemplos">
                Escolha no seletor se o numero digitado e <strong>lote</strong> ou <strong>oficio</strong> (ou deixe em "Detectar automaticamente").
                Exemplos:
                <a href="?modo=lote&q=00768036">Lote 00768036</a>
                <a href="?modo=oficio&q=1">Oficio 1</a>
            </div>
        <?php else: ?>
            <div class="deteccao">
                <?php if ($modo === 'lote'): ?>
                    Buscando por <span class="badge">Numero do lote</span>
                <?php elseif ($modo === 'oficio'): ?>
                    Buscando por <span class="badge">Numero do oficio</span>
                <?php else: ?>
                    Entrada detectada como
                    <span class="badge"><?php echo e(rotuloTipo($detEntrada['tipo'])); ?></span>
                    <?php if ($detEntrada['tipo'] === 'lote_oficio'): ?>
                        <span style="color:#64748b;">— ambiguo: use o seletor ao lado para escolher Lote ou Oficio.</span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($detEntrada['tipo'] === 'display'):
                    $ext = extrairDoDisplay($detEntrada['valor']);
                    if ($ext): ?>
                        - lote <strong><?php echo e($ext['lote']); ?></strong>,
                          posto <strong><?php echo e($ext['posto']); ?></strong>,
                          qtd <strong><?php echo (int)$ext['qtd']; ?></strong>
                    <?php endif;
                endif; ?>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($msgDespacho !== ''): ?>
    <div class="alerta"><?php echo e($msgDespacho); ?></div>
<?php endif; ?>

<?php if ($erro !== '' && !$statusSemOficio): ?>
    <div class="erro"><?php echo e($erro); ?></div>
<?php endif; ?>

<?php if ($statusSemOficio):
    $st = $statusSemOficio;
    $etapas = array(
        'expedido'  => array('n' => 1, 'rot' => 'Expedido / carrinho'),
        'estante'   => array('n' => 2, 'rot' => 'Na estante'),
        'conferido' => array('n' => 3, 'rot' => 'Conferido'),
        'em_malote' => array('n' => 4, 'rot' => 'Oficio gerado'),
    );
    $atual = isset($etapas[$st['etapa']]) ? $etapas[$st['etapa']]['n'] : 0;
?>
    <div class="card" style="margin-bottom:24px; border-left:5px solid #f59e0b;">
        <h2>Lote <?php echo e($st['lote']); ?> — ainda sem ofício gerado</h2>
        <p style="font-size:14px; color:#0f172a; margin:0 0 14px;">
            Situação atual: <strong style="color:#b45309;"><?php echo e($st['rotulo']); ?></strong>
        </p>

        <div class="etapas">
            <?php foreach ($etapas as $kEt => $et):
                $cls = ($atual >= $et['n'] && $atual > 0) ? 'et-ok' : 'et-off';
                if ($atual === $et['n']) $cls = 'et-atual';
            ?>
                <div class="etapa <?php echo $cls; ?>">
                    <span class="et-num"><?php echo $et['n']; ?></span>
                    <span class="et-rot"><?php echo e($et['rot']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <table class="dados" style="margin-top:16px;">
            <tbody>
                <?php if ($st['producao']):
                    $p = $st['producao']; ?>
                    <tr><th style="width:160px;">Produção (expedição)</th>
                        <td>
                            posto <strong><?php echo e(isset($p['posto']) ? $p['posto'] : '-'); ?></strong>
                            <?php if (isset($p['regional']) && $p['regional'] !== ''): ?> · regional <?php echo e($p['regional']); ?><?php endif; ?>
                            <?php if (isset($p['quantidade'])): ?> · <?php echo (int)$p['quantidade']; ?> cédulas<?php endif; ?>
                            <?php if (isset($p['usuario']) && $p['usuario'] !== ''): ?> · por <?php echo e($p['usuario']); ?><?php endif; ?>
                            <?php if (isset($p['dt']) && $p['dt'] !== ''): ?> · <?php echo e(dataBrRast($p['dt'])); ?><?php endif; ?>
                        </td></tr>
                <?php endif; ?>
                <?php if ($st['estante']):
                    $e2 = $st['estante']; ?>
                    <tr><th>Estante (triagem)</th>
                        <td>
                            <?php if (isset($e2['triado_em']) && $e2['triado_em'] !== ''): ?>triado em <?php echo e(dataBrRast($e2['triado_em'])); ?><?php else: ?>registrado na estante<?php endif; ?>
                            <?php if (isset($e2['triado_por']) && $e2['triado_por'] !== ''): ?> · por <?php echo e($e2['triado_por']); ?><?php endif; ?>
                        </td></tr>
                <?php endif; ?>
                <?php if ($st['conferido']):
                    $c2 = $st['conferido']; ?>
                    <tr><th>Conferência</th>
                        <td>
                            <span class="status-ok">Conferido</span>
                            <?php if (isset($c2['dt']) && $c2['dt'] !== ''): ?> em <?php echo e(dataBrRast($c2['dt'])); ?><?php endif; ?>
                            <?php if (isset($c2['usuario']) && $c2['usuario'] !== ''): ?> · por <?php echo e($c2['usuario']); ?><?php endif; ?>
                        </td></tr>
                <?php endif; ?>
                <?php if (count($st['retido']) > 0): ?>
                    <tr><th>Retenção</th>
                        <td>
                            <?php foreach ($st['retido'] as $rt): ?>
                                <span class="status-pend"><?php echo e($rt['tipo']); ?></span><?php if ($rt['motivo'] !== ''): ?>: <?php echo e($rt['motivo']); ?><?php endif; ?><br>
                            <?php endforeach; ?>
                        </td></tr>
                <?php endif; ?>
                <?php if (!$st['producao'] && !$st['estante'] && !$st['conferido']): ?>
                    <tr><td style="color:#64748b;">Este lote não foi localizado em produção, estante ou conferência. Verifique o número.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if (count($resultados) > 1): ?>
    <div class="alerta">
        Foram encontrados <strong><?php echo count($resultados); ?> oficios</strong> para essa entrada
        (a mesma etiqueta/lote pode aparecer em mais de um despacho historicamente).
    </div>
<?php endif; ?>

<?php foreach ($resultados as $res):
    $d = $res['despacho'];
    $itens = $res['itens'];
    $lotes = $res['lotes'];
    $conf = $res['conferencia'];
    $dev = $res['devolucao'];
    $grupoLow = strtolower($d['grupo']);
    $isPT = (strpos($grupoLow,'poupa') !== false || strpos($grupoLow,'tempo') !== false);
    $pillCls = $isPT ? 'pt' : (strpos($grupoLow,'correi') !== false ? 'cr' : '');
    $lblLacre = $isPT ? 'Lacre Poupa Tempo' : 'Lacre IIPR';
    $lblLacreCr = $isPT ? 'Lacre Poupa Tempo Correios' : 'Lacre Correios';

    // Oficio ja marcado como despachado? (qualquer lote com data)
    $jaDespachado = false;
    foreach ($lotes as $l) { if (!empty($l['data_despacho_correios'])) { $jaDespachado = true; break; } }

    // Inferencia de despacho pelo recebimento de displays (so se ainda nao marcado).
    // Ancora na criacao do oficio; qualquer recebimento a partir dai indica despacho.
    $despInfer = null;
    if (!$jaDespachado) {
        $ancoraDesp = resolverAncoraDespacho($d['criado_em'], $lotes, isset($d['datas_str']) ? $d['datas_str'] : '');
        try { $despInfer = inferirDespachoPorRecebimento($pdo, $ancoraDesp); } catch (Exception $e) { $despInfer = null; }
    }

    $totalConferidas = 0;
    foreach ($lotes as $l) {
        $k = $l['posto'] . '|' . $l['lote'];
        if (isset($conf[$k])) $totalConferidas += (int)$conf[$k]['total_conf'];
    }

    // Postos e cedulas previstas: Poupa Tempo usa ciDespachoItens; CORREIOS nao
    // popula ciDespachoItens (fica vazio) -- nesse caso contamos postos distintos
    // e somamos a quantidade direto dos lotes (ciDespachoLotes).
    $totalEsperadas = 0;
    if (count($itens) > 0) {
        $totalPostos = count($itens);
        foreach ($itens as $it) $totalEsperadas += (int)$it['quantidade'];
    } else {
        $postosUniq = array();
        foreach ($lotes as $l) {
            $postosUniq[$l['posto']] = true;
            $totalEsperadas += (int)$l['quantidade'];
        }
        $totalPostos = count($postosUniq);
    }

    $totalRecebidas = 0;
    foreach ($lotes as $l) {
        if ($l['etiqueta_correios'] !== '' && isset($dev[$l['etiqueta_correios']][2])) {
            $totalRecebidas++;
        }
    }
?>

<div class="timeline">

    <!-- Despacho / Oficio -->
    <div class="tl-item">
        <div class="card">
            <h2>
                Oficio #<?php echo (int)$d['id']; ?>
                <?php if ($pillCls): ?><span class="pill <?php echo $pillCls; ?>"><?php echo e($d['grupo']); ?></span><?php endif; ?>
                <span style="margin-left:auto; font-size:12px; color:#64748b; font-weight:normal;">
                    <?php echo (int)$totalPostos; ?> postos · <?php echo count($lotes); ?> lotes
                    <?php if ($totalEsperadas > 0): ?> · <?php echo (int)$totalEsperadas; ?> cedulas previstas<?php endif; ?>
                    · <?php echo (int)$totalConferidas; ?> conferidas
                    · <?php echo (int)$totalRecebidas; ?> recebidas
                </span>
            </h2>
            <div class="meta">
                <span><strong>Responsavel:</strong> <?php echo e($d['usuario']); ?></span>
                <span><strong>Criado em:</strong> <?php echo e($d['criado_em']); ?></span>
                <?php if (!empty($d['datas_str'])): ?>
                    <span><strong>Datas:</strong> <?php echo e($d['datas_str']); ?></span>
                <?php endif; ?>
                <span><strong>Hash:</strong> <code style="font-size:11px;"><?php echo e(substr($d['hash_chave'], 0, 12)); ?>...</code></span>
                <?php
                    // Link para o PDF do oficio (mesmo padrao de consulta_producao.php):
                    // <base>/cioficios/{id}_{tipo}.pdf  (tipo = grupo sem espacos, minusculo)
                    $tipoPdf = strtolower(str_replace(' ', '', trim($d['grupo'])));
                    $baseDirPdf = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                    if ($baseDirPdf === '.' ) $baseDirPdf = '';
                    $pdfLink = $baseDirPdf . '/cioficios/' . rawurlencode((int)$d['id'] . '_' . $tipoPdf . '.pdf');
                ?>
                <a class="no-print" href="<?php echo e($pdfLink); ?>" target="_blank" rel="noopener noreferrer"
                   style="margin-left:auto; background:#2563eb; color:#fff; padding:6px 12px; border-radius:6px; text-decoration:none; font-size:12px; font-weight:600;">
                    Baixar PDF do ofício
                </a>
            </div>

            <div class="box-despacho">
                <?php if ($jaDespachado): ?>
                    <span class="status-ok">Ofício despachado aos Correios.</span>
                    <form method="post" action="" style="margin:0;" onsubmit="return confirm('Desfazer o despacho deste ofício?');">
                        <input type="hidden" name="q" value="<?php echo e($q); ?>">
                        <input type="hidden" name="modo" value="<?php echo e($modo); ?>">
                        <input type="hidden" name="id_despacho" value="<?php echo (int)$d['id']; ?>">
                        <input type="hidden" name="acao_despacho" value="desfazer">
                        <button type="submit" class="btn-despacho desfazer no-print">Desfazer despacho</button>
                    </form>
                <?php else: ?>
                    <?php if ($despInfer): ?>
                        <span class="infer-despacho">Provavelmente despachado — houve recebimento de displays em <?php echo e(dataBrRast($despInfer['quando'])); ?> (<?php echo (int)$despInfer['total']; ?> leitura(s)).</span>
                    <?php endif; ?>
                    <form method="post" action="" style="margin:0; display:flex; gap:8px; align-items:center; flex-wrap:wrap;" class="no-print">
                        <input type="hidden" name="q" value="<?php echo e($q); ?>">
                        <input type="hidden" name="modo" value="<?php echo e($modo); ?>">
                        <input type="hidden" name="id_despacho" value="<?php echo (int)$d['id']; ?>">
                        <input type="hidden" name="acao_despacho" value="marcar">
                        <input type="text" name="responsavel" placeholder="Responsável pelo despacho" required>
                        <button type="submit" class="btn-despacho marcar">Marcar ofício como despachado</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Postos -->
    <?php if (count($itens) > 0): ?>
    <div class="tl-item">
        <div class="card">
            <h2>Postos do oficio</h2>
            <table class="dados">
                <thead><tr>
                    <th>Posto</th><th>Nome</th><th>Endereco</th>
                    <th><?php echo e($lblLacre); ?></th><th><?php echo e($lblLacreCr); ?></th>
                    <th>Qtd</th>
                </tr></thead>
                <tbody>
                <?php foreach ($itens as $it): ?>
                    <tr>
                        <td><strong><?php echo e($it['posto']); ?></strong></td>
                        <td><?php echo e($it['nome_posto']); ?></td>
                        <td style="font-size:11px;"><?php echo e($it['endereco']); ?></td>
                        <td><?php echo e($it['lacre_iipr']); ?></td>
                        <td><?php echo e($it['lacre_correios']); ?></td>
                        <td><?php echo (int)$it['quantidade']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lotes (+QR codes + conferencia + devolucao integradas por linha) -->
    <?php if (count($lotes) > 0): ?>
    <div class="tl-item tl-conferencia">
        <div class="card">
            <h2>Lotes / Etiquetas / Status</h2>
            <table class="dados">
                <thead><tr>
                    <th>Posto</th>
                    <th>Lote</th>
                    <th>Etiqueta Correios</th>
                    <th>Despachado</th>
                    <th>Conferencia</th>
                    <th>Devolucao</th>
                    <th class="qr-cell">QR Lote</th>
                    <th class="qr-cell">QR Etiqueta</th>
                </tr></thead>
                <tbody>
                <?php foreach ($lotes as $l):
                    $k = $l['posto'] . '|' . $l['lote'];
                    $cf = isset($conf[$k]) ? $conf[$k] : null;
                    $usaEtq = postoUsaEtiquetaCorreios($d['grupo'], $l['posto']);
                    $temEtq = ($usaEtq && $l['etiqueta_correios'] !== '');
                    $devRec = ($temEtq && isset($dev[$l['etiqueta_correios']][2])) ? $dev[$l['etiqueta_correios']][2] : null;
                    $urlLote = urlAbsoluta($l['lote']);
                    $urlEtq = $temEtq ? urlAbsoluta($l['etiqueta_correios']) : '';
                ?>
                    <tr>
                        <td><strong><?php echo e($l['posto']); ?></strong><?php if (!empty($l['posto_origem'])): ?><br><span style="color:#94a3b8; font-size:10px;" title="Posto resolvido pela etiqueta; valor original gravado: <?php echo e($l['posto_origem']); ?>">(orig.: <?php echo e($l['posto_origem']); ?>)</span><?php endif; ?></td>
                        <td>
                            <a href="<?php echo e(urlBusca($l['lote'])); ?>" style="color:#3b82f6; text-decoration:none; font-weight:600;">
                                <?php echo e($l['lote']); ?>
                            </a>
                        </td>
                        <td style="font-size:11px;">
                            <?php if ($temEtq): ?>
                                <a href="<?php echo e(urlBusca($l['etiqueta_correios'])); ?>" style="color:#3b82f6; text-decoration:none;">
                                    <?php echo e($l['etiqueta_correios']); ?>
                                </a>
                            <?php elseif (!$usaEtq): ?>
                                <span style="color:#94a3b8;" title="Poupa Tempo Capital/RM nao usa etiqueta dos Correios">Capital — sem etiqueta</span>
                            <?php else: ?>
                                <span style="color:#94a3b8;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:11px;">
                            <?php if ($l['data_despacho_correios']): ?>
                                <span class="status-ok"><?php echo e(dataBrRast($l['data_despacho_correios'])); ?></span>
                                <?php if ($l['despachado_por']): ?>
                                    <br><span style="color:#64748b;">por <?php echo e($l['despachado_por']); ?></span>
                                <?php endif; ?>
                            <?php elseif ($despInfer): ?>
                                <span class="infer-despacho">provável</span>
                                <br><span style="color:#64748b;"><?php echo e(dataBrRast($despInfer['quando'])); ?></span>
                            <?php else: ?>
                                <span style="color:#94a3b8;">nao despachado</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:11px;">
                            <?php if ($cf && (int)$cf['total_conf'] > 0): ?>
                                <span class="status-ok">Conferido</span>
                                <?php $dtConf = !empty($cf['conferido_em']) ? $cf['conferido_em'] : $cf['ultima_leitura']; ?>
                                <?php if ($dtConf): ?><br><span style="color:#64748b;"><?php echo e(dataBrRast($dtConf)); ?></span><?php endif; ?>
                                <?php if (!empty($cf['conferido_por'])): ?><br><span style="color:#64748b;">por <?php echo e($cf['conferido_por']); ?></span><?php endif; ?>
                            <?php elseif ($cf && (int)$cf['total_lido'] > 0): ?>
                                <span class="status-warn">Lido (não conferido)</span>
                                <?php if ($cf['ultima_leitura']): ?><br><span style="color:#64748b;"><?php echo e(dataBrRast($cf['ultima_leitura'])); ?></span><?php endif; ?>
                            <?php else: ?>
                                <span class="status-pend">pendente</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:11px;">
                            <?php if ($devRec): ?>
                                <span class="status-warn">devolvido</span>
                                <br><span style="color:#64748b;"><?php echo e(dataBrRast($devRec['quando'])); ?></span>
                                <?php if ($devRec['quem']): ?><br><span style="color:#64748b;">por <?php echo e($devRec['quem']); ?></span><?php endif; ?>
                            <?php else: ?>
                                <span style="color:#94a3b8;">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="qr-cell">
                            <a href="<?php echo e(urlBusca($l['lote'])); ?>" title="Abrir busca pelo lote">
                                <img src="<?php echo e(qrUrl($urlLote, 60)); ?>" alt="QR lote <?php echo e($l['lote']); ?>">
                            </a>
                        </td>
                        <td class="qr-cell">
                            <?php if ($urlEtq): ?>
                                <a href="<?php echo e(urlBusca($l['etiqueta_correios'])); ?>" title="Abrir busca pela etiqueta">
                                    <img src="<?php echo e(qrUrl($urlEtq, 60)); ?>" alt="QR etiqueta">
                                </a>
                            <?php else: ?>
                                <span style="color:#cbd5e1;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php endforeach; ?>

<?php if ($q === ''): ?>
<div class="card" style="margin-top:24px;">
    <h2>Como usar</h2>
    <p style="font-size:14px; color:#475569; line-height:1.6;">
        Cole ou bipe qualquer um destes itens na busca acima:
    </p>
    <ul style="font-size:14px; color:#475569; line-height:1.8;">
        <li><strong>Lote</strong> (ate 8 digitos): mostra todos os oficios que contem aquele lote.</li>
        <li><strong>Etiqueta dos Correios</strong> (35 digitos): mostra o oficio dono daquela etiqueta.</li>
        <li><strong>Codigo de barras do display</strong> (17 ou 19 digitos): extrai lote+posto e mostra o oficio.</li>
        <li><strong>Numero do oficio</strong> (use prefixo OF, ex: <code>OF1234</code>): mostra diretamente o oficio.</li>
    </ul>
    <p style="font-size:14px; color:#475569; line-height:1.6;">
        Cada lote tem um <strong>QR code</strong> que pode ser impresso e colado no display - quando bipado pelo celular,
        abre direto esta tela com a rastreabilidade completa.
    </p>
</div>
<?php endif; ?>

</div>

</body>
</html>
