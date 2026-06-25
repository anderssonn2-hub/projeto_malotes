<?php
// VERSAO SISTEMA: 2.0.0 - 2026-05-26
/*
================================================================
 Controle de Devolucao de Lotes - v1.0
 ----------------------------------------------------------------
 Sistema para registrar lotes devolvidos (colocados em malote
 errado). Permite rastrear: quando saiu, quando voltou, quando
 foi reenviado, posto destino correto. Ajuda a antecipar
 questionamentos de postos que nao receberam seus lotes.

 Compativel PHP 5.3.3 / IE8+.
================================================================
*/

date_default_timezone_set('America/Sao_Paulo');
session_start();

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function criarPdoLegado($host, $dbname, $user, $pass) {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $user, $pass);
    } catch (Exception $e) {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8";
        $pdo = new PDO($dsn, $user, $pass);
        try { $pdo->exec("SET NAMES utf8"); } catch (Exception $e2) {}
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

$dbOk = true;
$mensagem = '';
$tipoMsg = '';
$pdo = null;
$novoIdDestaque = 0; // id da devolucao recem-registrada (fica destacada no topo)
try {
    $pdo = criarPdoLegado(
        (getenv('DB_HOST') ?: '10.15.61.169'),
        (getenv('DB_NAME') ?: 'controle'),
        (getenv('DB_USER') ?: 'controle_mat'),
        (getenv('DB_PASS') ?: '375256')
    );
    // Cria tabela na primeira execucao
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ciDevolucoesLotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nlote VARCHAR(20) NOT NULL,
            posto_destino_errado VARCHAR(10) DEFAULT NULL,
            nome_posto_destino_errado VARCHAR(120) DEFAULT NULL,
            posto_correto VARCHAR(10) DEFAULT NULL,
            nome_posto_correto VARCHAR(120) DEFAULT NULL,
            id_despacho_original INT DEFAULT NULL,
            data_envio_original DATE DEFAULT NULL,
            data_devolucao DATE NOT NULL,
            data_reenvio DATE DEFAULT NULL,
            id_despacho_reenvio INT DEFAULT NULL,
            motivo VARCHAR(255) DEFAULT NULL,
            observacao TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'devolvido',
            usuario_registro VARCHAR(50) DEFAULT NULL,
            criado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_at DATETIME DEFAULT NULL,
            INDEX idx_nlote (nlote),
            INDEX idx_status (status),
            INDEX idx_data_devolucao (data_devolucao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
} catch (Exception $ex) {
    $dbOk = false;
    $mensagem = 'Erro de conexao com banco: ' . $ex->getMessage();
    $tipoMsg = 'erro';
}

// Lookup ajax: busca info de um lote (ultimo despacho)
if ($dbOk && isset($_GET['lookup_lote'])) {
    header('Content-Type: application/json; charset=utf-8');
    $loteBusca = preg_replace('/\D+/', '', (string)$_GET['lookup_lote']);
    $out = array('ok' => false);
    if ($loteBusca !== '') {
        try {
            $st = $pdo->prepare("
                SELECT cdl.nlote, cdl.posto, cdl.id_despacho, cdl.data_carga,
                       cd.usuario, cd.datas_str,
                       r.nome AS nome_posto
                FROM ciDespachoLotes cdl
                LEFT JOIN ciDespachos cd ON cd.id = cdl.id_despacho
                LEFT JOIN ciRegionais r ON LPAD(r.codigo,3,'0') = LPAD(cdl.posto,3,'0')
                WHERE LPAD(cdl.nlote,8,'0') = LPAD(?,8,'0')
                ORDER BY cdl.id DESC
                LIMIT 1
            ");
            $st->execute(array($loteBusca));
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $out['ok'] = true;
                $out['data'] = array(
                    'nlote' => $row['nlote'],
                    'posto' => $row['posto'],
                    'nome_posto' => $row['nome_posto'] ? $row['nome_posto'] : '',
                    'id_despacho' => $row['id_despacho'],
                    'data_envio' => $row['data_carga'],
                    'usuario' => $row['usuario'] ? $row['usuario'] : '',
                    'datas_str' => $row['datas_str'] ? $row['datas_str'] : ''
                );
            } else {
                $out['erro'] = 'Lote nao encontrado em nenhum despacho';
            }
        } catch (Exception $ex) {
            $out['erro'] = $ex->getMessage();
        }
    } else {
        $out['erro'] = 'Numero do lote invalido';
    }
    echo json_encode($out);
    exit;
}

// Handler: registrar nova devolucao
if ($dbOk && isset($_POST['acao']) && $_POST['acao'] === 'registrar') {
    try {
        $nlote = preg_replace('/\D+/', '', (string)(isset($_POST['nlote']) ? $_POST['nlote'] : ''));
        $dataDev = isset($_POST['data_devolucao']) ? trim($_POST['data_devolucao']) : '';
        $postoErr = trim((string)(isset($_POST['posto_destino_errado']) ? $_POST['posto_destino_errado'] : ''));
        $nomePostoErr = trim((string)(isset($_POST['nome_posto_destino_errado']) ? $_POST['nome_posto_destino_errado'] : ''));
        $idDespOrig = (int)(isset($_POST['id_despacho_original']) ? $_POST['id_despacho_original'] : 0);
        $dataEnvOrig = isset($_POST['data_envio_original']) ? trim($_POST['data_envio_original']) : '';
        $motivo = trim((string)(isset($_POST['motivo']) ? $_POST['motivo'] : ''));
        $obs = trim((string)(isset($_POST['observacao']) ? $_POST['observacao'] : ''));
        $usuario = trim((string)(isset($_POST['usuario']) ? $_POST['usuario'] : ''));

        if ($nlote === '' || $dataDev === '') {
            throw new Exception('Informe o numero do lote e a data de devolucao.');
        }
        $st = $pdo->prepare("
            INSERT INTO ciDevolucoesLotes
              (nlote, posto_destino_errado, nome_posto_destino_errado,
               id_despacho_original, data_envio_original, data_devolucao,
               motivo, observacao, status, usuario_registro)
            VALUES (?,?,?,?,?,?,?,?, 'devolvido', ?)
        ");
        $st->execute(array(
            $nlote,
            $postoErr !== '' ? $postoErr : null,
            $nomePostoErr !== '' ? $nomePostoErr : null,
            $idDespOrig > 0 ? $idDespOrig : null,
            $dataEnvOrig !== '' ? $dataEnvOrig : null,
            $dataDev,
            $motivo !== '' ? $motivo : null,
            $obs !== '' ? $obs : null,
            $usuario !== '' ? $usuario : null
        ));
        $novoIdDestaque = (int)$pdo->lastInsertId();
        $mensagem = 'Devolucao do lote ' . $nlote . ' registrada com sucesso (#' . $novoIdDestaque . ').';
        $tipoMsg = 'sucesso';
    } catch (Exception $ex) {
        $mensagem = 'Erro ao registrar: ' . $ex->getMessage();
        $tipoMsg = 'erro';
    }
}

// Handler: marcar como reenviado
if ($dbOk && isset($_POST['acao']) && $_POST['acao'] === 'reenviar') {
    try {
        $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        $dataReenvio = isset($_POST['data_reenvio']) ? trim($_POST['data_reenvio']) : '';
        $idDespReenvio = (int)(isset($_POST['id_despacho_reenvio']) ? $_POST['id_despacho_reenvio'] : 0);
        $postoCorreto = trim((string)(isset($_POST['posto_correto']) ? $_POST['posto_correto'] : ''));
        $nomePostoCorreto = trim((string)(isset($_POST['nome_posto_correto']) ? $_POST['nome_posto_correto'] : ''));
        $obsReenvio = trim((string)(isset($_POST['observacao_reenvio']) ? $_POST['observacao_reenvio'] : ''));

        if ($id <= 0 || $dataReenvio === '') {
            throw new Exception('Informe a data do reenvio.');
        }
        $sqlObs = '';
        $params = array($dataReenvio,
            $idDespReenvio > 0 ? $idDespReenvio : null,
            $postoCorreto !== '' ? $postoCorreto : null,
            $nomePostoCorreto !== '' ? $nomePostoCorreto : null);
        if ($obsReenvio !== '') {
            $sqlObs = ", observacao = CONCAT(COALESCE(observacao,''), CASE WHEN observacao IS NULL OR observacao='' THEN '' ELSE '\n--- Reenvio ---\n' END, ?)";
            $params[] = $obsReenvio;
        }
        $params[] = $id;
        $st = $pdo->prepare("
            UPDATE ciDevolucoesLotes
            SET data_reenvio = ?,
                id_despacho_reenvio = ?,
                posto_correto = ?,
                nome_posto_correto = ?,
                status = 'reenviado',
                atualizado_at = NOW()
                $sqlObs
            WHERE id = ?
        ");
        $st->execute($params);
        $mensagem = 'Lote marcado como reenviado.';
        $tipoMsg = 'sucesso';
    } catch (Exception $ex) {
        $mensagem = 'Erro ao reenviar: ' . $ex->getMessage();
        $tipoMsg = 'erro';
    }
}

// Handler: cancelar registro
if ($dbOk && isset($_POST['acao']) && $_POST['acao'] === 'cancelar') {
    try {
        $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        if ($id > 0) {
            $st = $pdo->prepare("UPDATE ciDevolucoesLotes SET status = 'cancelado', atualizado_at = NOW() WHERE id = ?");
            $st->execute(array($id));
            $mensagem = 'Registro #' . $id . ' cancelado.';
            $tipoMsg = 'sucesso';
        }
    } catch (Exception $ex) {
        $mensagem = 'Erro ao cancelar: ' . $ex->getMessage();
        $tipoMsg = 'erro';
    }
}

// Filtros para listagem
$filtroStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$filtroLote   = preg_replace('/\D+/', '', (string)(isset($_GET['q_lote']) ? $_GET['q_lote'] : ''));
$filtroDe     = isset($_GET['de']) ? trim($_GET['de']) : '';
$filtroAte    = isset($_GET['ate']) ? trim($_GET['ate']) : '';

$listaDevolucoes = array();
$totais = array('devolvido' => 0, 'reenviado' => 0, 'cancelado' => 0);
if ($dbOk) {
    try {
        // Totais por status
        $stT = $pdo->query("SELECT status, COUNT(*) c FROM ciDevolucoesLotes GROUP BY status");
        while ($rT = $stT->fetch(PDO::FETCH_ASSOC)) {
            $totais[$rT['status']] = (int)$rT['c'];
        }
        // Listagem
        $where = array(); $params = array();
        if ($filtroStatus !== '') { $where[] = 'status = ?';        $params[] = $filtroStatus; }
        if ($filtroLote   !== '') { $where[] = "LPAD(nlote,8,'0') LIKE ?"; $params[] = '%' . str_pad($filtroLote,8,'0',STR_PAD_LEFT) . '%'; }
        if ($filtroDe     !== '') { $where[] = 'data_devolucao >= ?'; $params[] = $filtroDe; }
        if ($filtroAte    !== '') { $where[] = 'data_devolucao <= ?'; $params[] = $filtroAte; }
        $sqlW = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
        $stL = $pdo->prepare("SELECT * FROM ciDevolucoesLotes $sqlW ORDER BY criado_at DESC LIMIT 500");
        $stL->execute($params);
        $listaDevolucoes = $stL->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $mensagem = 'Erro ao listar: ' . $ex->getMessage();
        $tipoMsg = 'erro';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Controle de Devolucao de Lotes</title>
<style>
*{box-sizing:border-box;}
body{font-family:Arial,Helvetica,sans-serif;margin:0;background:#f4f6f8;color:#222;}
.topbar{background:#1a3a5c;color:#fff;padding:12px 18px;display:flex;align-items:center;gap:16px;}
.topbar h1{margin:0;font-size:18px;font-weight:600;}
.topbar .home{color:#fff;text-decoration:none;background:rgba(255,255,255,.15);padding:6px 12px;border-radius:5px;font-size:13px;}
.topbar .home:hover{background:rgba(255,255,255,.25);}
.abas{display:flex;flex-wrap:wrap;gap:4px;padding:8px 18px;background:#fff;border-bottom:1px solid #ddd;}
.aba{padding:8px 14px;text-decoration:none;color:#444;border-radius:5px 5px 0 0;font-size:13px;font-weight:600;background:#eef2f6;}
.aba:hover{background:#dde6ee;}
.aba.ativa{background:#1a3a5c;color:#fff;}
.main{padding:18px;max-width:1400px;margin:0 auto;}
.card{background:#fff;border:1px solid #d9e0e6;border-radius:8px;padding:16px;margin-bottom:16px;box-shadow:0 1px 2px rgba(0,0,0,.04);}
.card h2{margin:0 0 12px;font-size:15px;color:#1a3a5c;border-bottom:2px solid #e3eaf0;padding-bottom:6px;}
.msg{padding:10px 14px;border-radius:6px;margin-bottom:14px;font-weight:600;font-size:13px;}
.msg.sucesso{background:#e6f6ec;color:#1b6c34;border:1px solid #9dd8b4;}
.msg.erro{background:#fce8e8;color:#9b2222;border:1px solid #f0a0a0;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;}
label{display:block;font-size:12px;font-weight:600;color:#444;margin-bottom:3px;}
input[type=text],input[type=date],input[type=number],select,textarea{width:100%;padding:6px 8px;border:1px solid #c8d2dc;border-radius:4px;font-size:13px;font-family:inherit;}
textarea{resize:vertical;min-height:48px;}
button{cursor:pointer;border:none;border-radius:5px;padding:8px 14px;font-size:13px;font-weight:600;}
.btn-primary{background:#1a4f7a;color:#fff;}
.btn-primary:hover{background:#143d61;}
.btn-success{background:#1b6c34;color:#fff;}
.btn-warn{background:#b86a00;color:#fff;}
.btn-danger{background:#9b2222;color:#fff;}
.btn-secondary{background:#6c757d;color:#fff;}
table{width:100%;border-collapse:collapse;font-size:12px;}
th,td{padding:7px 8px;border-bottom:1px solid #e7ecf1;text-align:left;vertical-align:top;}
th{background:#eef2f6;font-size:11px;color:#445;text-transform:uppercase;}
tr.st-devolvido{background:#fff8e1;}
tr.st-reenviado{background:#e8f5e9;}
tr.st-cancelado{background:#f2f2f2;color:#888;}
tr.row-novo td{background:#d6f5e0 !important;box-shadow:inset 3px 0 0 #1b6c34;}
.badge-novo{display:inline-block;margin-left:4px;padding:1px 6px;border-radius:8px;background:#1b6c34;color:#fff;font-size:9px;font-weight:700;vertical-align:middle;}
.tag{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;text-transform:uppercase;}
.tag.devolvido{background:#f9a825;color:#fff;}
.tag.reenviado{background:#2e7d32;color:#fff;}
.tag.cancelado{background:#9e9e9e;color:#fff;}
.totais{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;}
.totais .box{padding:10px 14px;border-radius:6px;background:#fff;border:1px solid #d9e0e6;}
.totais .box b{display:block;font-size:20px;}
.totais .box span{font-size:11px;color:#666;}
.acoes-inline{display:flex;gap:4px;flex-wrap:wrap;}
.detalhe{display:none;padding:10px;background:#f9fbfd;border-top:2px solid #1a4f7a;}
.detalhe.aberto{display:table-row;}
.detalhe td{padding:14px;}
.row-flex{display:flex;gap:10px;flex-wrap:wrap;align-items:end;}
.row-flex > div{flex:1;min-width:140px;}
.info-lookup{background:#e3f2fd;border:1px solid #90caf9;border-radius:4px;padding:8px;margin-top:6px;font-size:12px;color:#0d47a1;display:none;}
.info-lookup.show{display:block;}
.info-lookup.erro{background:#fce8e8;border-color:#f0a0a0;color:#9b2222;}
</style>
</head>
<body>

<div class="topbar">
  <a class="home" href="inicio.php">&#8592; Inicio</a>
  <h1>&#128230; Controle de Devolucao de Lotes</h1>
  <span style="font-size:11px;opacity:.7;">v2.0.3</span>
</div>

<div class="abas">
  <a class="aba ativa" href="devolucao_lotes.php">&#128230; Devolucoes</a>
  <a class="aba" href="devolucao_etiquetas.php">&#128231; Etiquetas Correios</a>
  <a class="aba" href="rastreabilidade.php">&#128279; Rastreabilidade</a>
  <a class="aba" href="lacres_novo.php">&#128272; Lacres</a>
</div>

<div class="main">

<?php if ($mensagem !== ''): ?>
  <div class="msg <?php echo e($tipoMsg); ?>"><?php echo e($mensagem); ?></div>
<?php endif; ?>

<?php if (!$dbOk): ?>
  <div class="card"><p style="color:#9b2222;">&#9888; Sem conexao com banco. Recarregue a pagina.</p></div>
<?php else: ?>

<!-- Totais -->
<div class="totais">
  <div class="box" style="border-left:4px solid #f9a825;"><b><?php echo (int)$totais['devolvido']; ?></b><span>Devolvidos (pendentes)</span></div>
  <div class="box" style="border-left:4px solid #2e7d32;"><b><?php echo (int)$totais['reenviado']; ?></b><span>Reenviados</span></div>
  <div class="box" style="border-left:4px solid #9e9e9e;"><b><?php echo (int)$totais['cancelado']; ?></b><span>Cancelados</span></div>
</div>

<!-- Form: registrar nova devolucao -->
<div class="card">
  <h2>&#10133; Registrar nova devolucao</h2>
  <form method="post" id="formRegistrar">
    <input type="hidden" name="acao" value="registrar">

    <div class="row-flex">
      <div style="flex:0 0 180px;">
        <label>Numero do lote *</label>
        <input type="text" name="nlote" id="nlote" required onblur="buscarInfoLote();" placeholder="ex: 00767762">
      </div>
      <div style="flex:0 0 160px;">
        <label>Data devolucao *</label>
        <input type="date" name="data_devolucao" required value="<?php echo e(date('Y-m-d')); ?>">
      </div>
      <div style="flex:0 0 200px;">
        <label>Seu nome (responsavel)</label>
        <input type="text" name="usuario" placeholder="opcional">
      </div>
    </div>

    <div class="info-lookup" id="infoLookup"></div>

    <div class="row-flex" style="margin-top:10px;">
      <div>
        <label>Posto destino errado (codigo)</label>
        <input type="text" name="posto_destino_errado" id="postoErrCodigo" placeholder="ex: 110">
      </div>
      <div style="flex:2;">
        <label>Nome do posto errado</label>
        <input type="text" name="nome_posto_destino_errado" id="postoErrNome" placeholder="ex: Posto 110 - PARANAGUA">
      </div>
      <div>
        <label>ID despacho original</label>
        <input type="number" name="id_despacho_original" id="idDespOrig" min="0">
      </div>
      <div>
        <label>Data envio original</label>
        <input type="date" name="data_envio_original" id="dataEnvOrig">
      </div>
    </div>

    <div class="row-flex" style="margin-top:10px;">
      <div style="flex:1;">
        <label>Motivo</label>
        <input type="text" name="motivo" placeholder="ex: malote errado, endereco incorreto, posto fechado">
      </div>
    </div>

    <div style="margin-top:10px;">
      <label>Observacao</label>
      <textarea name="observacao" rows="2" placeholder="detalhes adicionais"></textarea>
    </div>

    <div style="margin-top:12px;">
      <button type="submit" class="btn-primary">&#128190; Registrar devolucao</button>
      <button type="reset" class="btn-secondary" onclick="document.getElementById('infoLookup').className='info-lookup';">Limpar</button>
    </div>
  </form>
</div>

<!-- Filtros -->
<div class="card">
  <h2>&#128269; Filtrar devolucoes</h2>
  <form method="get" class="row-flex">
    <div>
      <label>Status</label>
      <select name="status">
        <option value="">Todos</option>
        <option value="devolvido"  <?php echo $filtroStatus==='devolvido'?'selected':''; ?>>Devolvido (pendente)</option>
        <option value="reenviado"  <?php echo $filtroStatus==='reenviado'?'selected':''; ?>>Reenviado</option>
        <option value="cancelado"  <?php echo $filtroStatus==='cancelado'?'selected':''; ?>>Cancelado</option>
      </select>
    </div>
    <div><label>Numero do lote</label><input type="text" name="q_lote" value="<?php echo e($filtroLote); ?>"></div>
    <div><label>De</label><input type="date" name="de" value="<?php echo e($filtroDe); ?>"></div>
    <div><label>Ate</label><input type="date" name="ate" value="<?php echo e($filtroAte); ?>"></div>
    <div style="flex:0 0 auto;display:flex;gap:5px;">
      <button type="submit" class="btn-primary">Filtrar</button>
      <a href="devolucao_lotes.php" class="btn-secondary" style="text-decoration:none;display:inline-block;padding:8px 14px;border-radius:5px;color:#fff;">Limpar</a>
    </div>
  </form>
</div>

<!-- Listagem -->
<div class="card">
  <h2>&#128203; Lista de devolucoes (<?php echo count($listaDevolucoes); ?>)</h2>
  <?php if (empty($listaDevolucoes)): ?>
    <p style="color:#888;">Nenhum registro encontrado.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>#</th><th>Status</th><th>Lote</th>
        <th>Destino errado</th><th>Devolvido em</th>
        <th>Reenviado em</th><th>Destino correto</th>
        <th>Motivo</th><th>Registrado por</th>
        <th>Acoes</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($listaDevolucoes as $d):
        $st = $d['status'];
        $loteFmt = str_pad($d['nlote'], 8, '0', STR_PAD_LEFT);
      ?>
      <tr class="st-<?php echo e($st); ?><?php echo ($novoIdDestaque > 0 && (int)$d['id'] === $novoIdDestaque) ? ' row-novo' : ''; ?>">
        <td>#<?php echo (int)$d['id']; ?><?php echo ($novoIdDestaque > 0 && (int)$d['id'] === $novoIdDestaque) ? ' <span class="badge-novo">ULTIMO</span>' : ''; ?></td>
        <td><span class="tag <?php echo e($st); ?>"><?php echo e($st); ?></span></td>
        <td><b><?php echo e($loteFmt); ?></b></td>
        <td>
          <?php if ($d['nome_posto_destino_errado']): ?>
            <?php echo e($d['nome_posto_destino_errado']); ?>
          <?php elseif ($d['posto_destino_errado']): ?>
            Posto <?php echo e($d['posto_destino_errado']); ?>
          <?php else: ?> &mdash; <?php endif; ?>
          <?php if ($d['id_despacho_original']): ?>
            <br><small style="color:#666;">Of. #<?php echo (int)$d['id_despacho_original']; ?></small>
          <?php endif; ?>
        </td>
        <td><?php echo $d['data_devolucao'] ? e(date('d/m/Y', strtotime($d['data_devolucao']))) : '&mdash;'; ?></td>
        <td><?php echo $d['data_reenvio'] ? e(date('d/m/Y', strtotime($d['data_reenvio']))) : '&mdash;'; ?></td>
        <td>
          <?php if ($d['nome_posto_correto']): ?><?php echo e($d['nome_posto_correto']); ?>
          <?php elseif ($d['posto_correto']): ?>Posto <?php echo e($d['posto_correto']);
          else: ?>&mdash;<?php endif; ?>
          <?php if ($d['id_despacho_reenvio']): ?>
            <br><small style="color:#666;">Of. #<?php echo (int)$d['id_despacho_reenvio']; ?></small>
          <?php endif; ?>
        </td>
        <td><?php echo e($d['motivo']); ?></td>
        <td><?php echo e($d['usuario_registro']); ?></td>
        <td class="acoes-inline">
          <?php if ($st === 'devolvido'): ?>
            <button type="button" class="btn-success" onclick="toggleReenvio(<?php echo (int)$d['id']; ?>)">Reenviar</button>
            <form method="post" style="display:inline;" onsubmit="return confirm('Cancelar este registro?');">
              <input type="hidden" name="acao" value="cancelar">
              <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
              <button type="submit" class="btn-danger">Cancelar</button>
            </form>
          <?php endif; ?>
          <?php if ($d['observacao']): ?>
            <button type="button" class="btn-secondary" onclick="toggleObs(<?php echo (int)$d['id']; ?>)" title="Ver observacao">Obs</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php if ($st === 'devolvido'): ?>
      <tr class="detalhe" id="reenvio-<?php echo (int)$d['id']; ?>">
        <td colspan="10">
          <form method="post" class="row-flex">
            <input type="hidden" name="acao" value="reenviar">
            <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
            <div><label>Data reenvio *</label><input type="date" name="data_reenvio" required value="<?php echo e(date('Y-m-d')); ?>"></div>
            <div><label>Posto correto (codigo)</label><input type="text" name="posto_correto"></div>
            <div style="flex:2;"><label>Nome posto correto</label><input type="text" name="nome_posto_correto"></div>
            <div><label>ID despacho reenvio</label><input type="number" name="id_despacho_reenvio" min="0"></div>
            <div style="flex:2;"><label>Observacao reenvio</label><input type="text" name="observacao_reenvio"></div>
            <div style="flex:0 0 auto;align-self:end;"><button type="submit" class="btn-success">Confirmar reenvio</button></div>
          </form>
        </td>
      </tr>
      <?php endif; ?>
      <?php if ($d['observacao']): ?>
      <tr class="detalhe" id="obs-<?php echo (int)$d['id']; ?>">
        <td colspan="10"><b>Observacao:</b><br><pre style="white-space:pre-wrap;font-family:inherit;margin:6px 0 0;"><?php echo e($d['observacao']); ?></pre></td>
      </tr>
      <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php endif; /* dbOk */ ?>

</div>

<script type="text/javascript">
function toggleReenvio(id) {
    var el = document.getElementById('reenvio-' + id);
    if (!el) return;
    if (el.className.indexOf('aberto') >= 0) {
        el.className = el.className.replace(/\s*aberto/g, '');
    } else {
        el.className = el.className + ' aberto';
    }
}
function toggleObs(id) {
    var el = document.getElementById('obs-' + id);
    if (!el) return;
    if (el.className.indexOf('aberto') >= 0) {
        el.className = el.className.replace(/\s*aberto/g, '');
    } else {
        el.className = el.className + ' aberto';
    }
}
function buscarInfoLote() {
    var inp = document.getElementById('nlote');
    if (!inp) return;
    var val = (inp.value || '').replace(/\D+/g, '');
    var box = document.getElementById('infoLookup');
    if (!box) return;
    box.className = 'info-lookup';
    box.innerHTML = '';
    if (val.length < 3) return;
    box.className = 'info-lookup show';
    box.innerHTML = 'Buscando informacoes do lote...';
    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    xhr.open('GET', 'devolucao_lotes.php?lookup_lote=' + encodeURIComponent(val), true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        try {
            var r = eval('(' + xhr.responseText + ')');
            if (r.ok) {
                var d = r.data;
                var loteFmt = ('00000000' + d.nlote).slice(-8);
                box.className = 'info-lookup show';
                box.innerHTML =
                    '<b>Lote ' + loteFmt + ' encontrado:</b><br>' +
                    'Posto enviado: <b>' + (d.nome_posto || d.posto || '?') + '</b> (codigo ' + d.posto + ')<br>' +
                    'Oficio: <b>#' + d.id_despacho + '</b> &middot; Data envio: ' + (d.data_envio || '?') + '<br>' +
                    'Responsavel original: ' + (d.usuario || '?');
                var pE = document.getElementById('postoErrCodigo');
                var pN = document.getElementById('postoErrNome');
                var iD = document.getElementById('idDespOrig');
                var dE = document.getElementById('dataEnvOrig');
                if (pE && !pE.value) pE.value = d.posto || '';
                if (pN && !pN.value) pN.value = d.nome_posto || ('Posto ' + d.posto);
                if (iD && !iD.value) iD.value = d.id_despacho || '';
                if (dE && !dE.value) dE.value = d.data_envio || '';
            } else {
                box.className = 'info-lookup show erro';
                box.innerHTML = '&#9888; ' + (r.erro || 'Lote nao encontrado.');
            }
        } catch (e) {
            box.className = 'info-lookup show erro';
            box.innerHTML = '&#9888; Erro ao consultar o lote.';
        }
    };
    xhr.send();
}
</script>
</body>
</html>
