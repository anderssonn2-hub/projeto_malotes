<?php
/* lembretes.php — v1.0.0
 * Painel de lembretes: "posto nao fechado".
 * Marca postos manualmente, configura canal (Telegram/Email) e intervalo de disparo.
 * O disparo efetivo é feito por lembretes_disparo.php (chamado manualmente ou agendado).
 */
header('Cache-Control: no-cache, no-store, must-revalidate');
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$pdo = null;
try {
    $pdo = new PDO("mysql:host=" . (getenv('DB_HOST') ?: '10.15.61.169') . ";dbname=" . (getenv('DB_NAME') ?: 'controle') . ";charset=utf8",
                   (getenv('DB_USER') ?: 'controle_mat'), (getenv('DB_PASS') ?: '375256'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Schema
    $pdo->exec("CREATE TABLE IF NOT EXISTS ciLembretes (
        id            INT NOT NULL AUTO_INCREMENT,
        posto         VARCHAR(10) NOT NULL,
        nome          VARCHAR(120) DEFAULT NULL,
        responsavel   VARCHAR(120) DEFAULT NULL,
        intervalo_min INT NOT NULL DEFAULT 60,
        canal         VARCHAR(20)  NOT NULL DEFAULT 'ambos',
        ativo         TINYINT(1)   NOT NULL DEFAULT 1,
        ultimo_envio  DATETIME     DEFAULT NULL,
        criado        DATETIME     NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_posto (posto)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ciLembretesConfig (
        chave VARCHAR(80) NOT NULL,
        valor TEXT DEFAULT NULL,
        PRIMARY KEY (chave)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

    $pdo->exec("INSERT IGNORE INTO ciLembretesConfig (chave, valor) VALUES
        ('telegram_token', ''),
        ('telegram_chat_id', ''),
        ('email_destino', ''),
        ('email_remetente', ''),
        ('email_smtp_host', ''),
        ('email_smtp_porta', '587'),
        ('email_smtp_user', ''),
        ('email_smtp_pass', '')");

    // AJAX
    if (isset($_POST['ajax']) && $_POST['ajax']) {
        header('Content-Type: application/json; charset=utf-8');

        if ($_POST['ajax'] === 'salvar_config') {
            $campos = array('telegram_token','telegram_chat_id','email_destino','email_remetente',
                            'email_smtp_host','email_smtp_porta','email_smtp_user','email_smtp_pass');
            $st = $pdo->prepare("UPDATE ciLembretesConfig SET valor=? WHERE chave=?");
            foreach ($campos as $c) {
                $val = isset($_POST[$c]) ? trim((string)$_POST[$c]) : '';
                $st->execute(array($val, $c));
            }
            die(json_encode(array('ok' => true)));
        }

        if ($_POST['ajax'] === 'carregar_config') {
            $rows = $pdo->query("SELECT chave, valor FROM ciLembretesConfig")->fetchAll();
            $cfg  = array();
            foreach ($rows as $r) $cfg[$r['chave']] = $r['valor'];
            die(json_encode(array('ok' => true, 'cfg' => $cfg)));
        }

        if ($_POST['ajax'] === 'listar') {
            $rows = $pdo->query("SELECT * FROM ciLembretes WHERE ativo=1 ORDER BY posto ASC")->fetchAll();
            die(json_encode(array('ok' => true, 'lista' => $rows)));
        }

        if ($_POST['ajax'] === 'salvar_lembrete') {
            $posto    = str_pad(preg_replace('/\D/','',(string)(isset($_POST['posto'])    ? $_POST['posto']    : '')),3,'0',STR_PAD_LEFT);
            $nome     = trim((string)(isset($_POST['nome'])        ? $_POST['nome']        : ''));
            $resp     = trim((string)(isset($_POST['responsavel']) ? $_POST['responsavel'] : ''));
            $interv   = max(5,(int)(isset($_POST['intervalo_min']) ? $_POST['intervalo_min'] : 60));
            $canal    = trim((string)(isset($_POST['canal'])       ? $_POST['canal']       : 'ambos'));
            if ($posto === '000') die(json_encode(array('ok' => false, 'erro' => 'Posto invalido')));
            $canais = array('telegram','email','ambos');
            if (!in_array($canal, $canais)) $canal = 'ambos';
            $stC = $pdo->prepare("SELECT id FROM ciLembretes WHERE posto=?");
            $stC->execute(array($posto));
            if ($stC->fetch()) {
                $pdo->prepare("UPDATE ciLembretes SET nome=?,responsavel=?,intervalo_min=?,canal=?,ativo=1 WHERE posto=?")
                    ->execute(array($nome,$resp,$interv,$canal,$posto));
            } else {
                $pdo->prepare("INSERT INTO ciLembretes (posto,nome,responsavel,intervalo_min,canal,ativo,criado) VALUES (?,?,?,?,?,1,NOW())")
                    ->execute(array($posto,$nome,$resp,$interv,$canal));
            }
            die(json_encode(array('ok' => true)));
        }

        if ($_POST['ajax'] === 'remover_lembrete') {
            $posto = str_pad(preg_replace('/\D/','',(string)(isset($_POST['posto']) ? $_POST['posto'] : '')),3,'0',STR_PAD_LEFT);
            $pdo->prepare("UPDATE ciLembretes SET ativo=0 WHERE posto=?")->execute(array($posto));
            die(json_encode(array('ok' => true)));
        }

        if ($_POST['ajax'] === 'testar_agora') {
            // Dispara via HTTP para lembretes_disparo.php
            $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $url    = $scheme . '://' . $host . '/lembretes_disparo.php?token=disparo_interno';
            $ctx    = stream_context_create(array('http' => array('timeout' => 15, 'method' => 'GET')));
            $resp2  = @file_get_contents($url, false, $ctx);
            if ($resp2 === false) {
                // Fallback: include direto
                ob_start();
                include __DIR__ . '/lembretes_disparo.php';
                $resp2 = ob_get_clean();
            }
            $dec = @json_decode($resp2, true);
            die(json_encode(array('ok' => true, 'resultado' => $dec)));
        }

        if ($_POST['ajax'] === 'status_agendador') {
            $pid_file  = sys_get_temp_dir() . '/lembretes_agendador.pid';
            $hb_file   = sys_get_temp_dir() . '/lembretes_agendador.heartbeat';
            $stop_file = sys_get_temp_dir() . '/lembretes_agendador.stop';
            $rodando   = false;
            $pid       = 0;
            $heartbeat = 0;
            if (file_exists($pid_file)) {
                $pid = (int)file_get_contents($pid_file);
                if ($pid > 0 && file_exists('/proc/' . $pid)) {
                    $rodando = true;
                }
            }
            if (file_exists($hb_file)) {
                $heartbeat = (int)file_get_contents($hb_file);
            }
            $atrasado = false;
            if ($rodando && $heartbeat > 0) {
                $atrasado = (time() - $heartbeat) > 180; // sem heartbeat ha mais de 3 min
            }
            die(json_encode(array(
                'ok'        => true,
                'rodando'   => $rodando,
                'pid'       => $pid,
                'heartbeat' => $heartbeat,
                'atrasado'  => $atrasado,
                'parada_pendente' => file_exists($stop_file)
            )));
        }

        if ($_POST['ajax'] === 'iniciar_agendador') {
            $pid_file = sys_get_temp_dir() . '/lembretes_agendador.pid';
            // Verificar se ja esta rodando
            if (file_exists($pid_file)) {
                $pid = (int)file_get_contents($pid_file);
                if ($pid > 0 && file_exists('/proc/' . $pid)) {
                    die(json_encode(array('ok' => false, 'erro' => 'Agendador ja esta rodando (PID ' . $pid . ')')));
                }
            }
            $script = __DIR__ . '/lembretes_agendador.php';
            $log    = sys_get_temp_dir() . '/lembretes_agendador.log';
            $cmd    = 'php ' . escapeshellarg($script) . ' >> ' . escapeshellarg($log) . ' 2>&1 &';
            @exec($cmd);
            sleep(2);
            $ok = file_exists($pid_file) && file_exists('/proc/' . (int)file_get_contents($pid_file));
            if ($ok) {
                die(json_encode(array('ok' => true, 'pid' => (int)file_get_contents($pid_file))));
            }
            die(json_encode(array('ok' => false, 'erro' => 'Nao foi possivel iniciar. Verifique se exec() esta habilitado no servidor.')));
        }

        if ($_POST['ajax'] === 'parar_agendador') {
            $stop_file = sys_get_temp_dir() . '/lembretes_agendador.stop';
            $pid_file  = sys_get_temp_dir() . '/lembretes_agendador.pid';
            file_put_contents($stop_file, '1');
            // Aguardar ate 6s para o processo encerrar
            for ($i = 0; $i < 6; $i++) {
                sleep(1);
                if (!file_exists($pid_file)) break;
                $pid = (int)@file_get_contents($pid_file);
                if (!$pid || !file_exists('/proc/' . $pid)) break;
            }
            $ainda = false;
            if (file_exists($pid_file)) {
                $pid  = (int)@file_get_contents($pid_file);
                $ainda = $pid > 0 && file_exists('/proc/' . $pid);
            }
            die(json_encode(array('ok' => true, 'ainda_rodando' => $ainda)));
        }

        if ($_POST['ajax'] === 'log_agendador') {
            $log = sys_get_temp_dir() . '/lembretes_agendador.log';
            $conteudo = '';
            if (file_exists($log)) {
                // Ultimas 80 linhas
                $linhas = file($log);
                if ($linhas === false) $linhas = array();
                $conteudo = implode('', array_slice($linhas, -80));
            }
            die(json_encode(array('ok' => true, 'log' => $conteudo)));
        }

        die(json_encode(array('ok' => false, 'erro' => 'Acao desconhecida')));
    }

    // Carregar config para a página
    $cfg = array();
    $rowsCfg = $pdo->query("SELECT chave, valor FROM ciLembretesConfig")->fetchAll();
    foreach ($rowsCfg as $r) $cfg[$r['chave']] = $r['valor'];

} catch (PDOException $ex) {
    if (isset($_POST['ajax']) && $_POST['ajax']) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(array('ok' => false, 'erro' => 'Erro de conexao')));
    }
    $cfg = array();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lembretes de Postos</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:"Trebuchet MS","Segoe UI",Arial,sans-serif;background:#f0f4f8;padding:20px;padding-top:76px;}
.topo-fixo{position:fixed;top:0;left:0;right:0;background:linear-gradient(135deg,#1565c0 0%,#1e88e5 100%);color:#fff;padding:12px 20px;z-index:1000;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 10px rgba(0,0,0,.3);}
.topo-fixo h1{font-size:18px;font-weight:700;}
.versao{background:rgba(255,255,255,.25);color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;}
.btn-voltar{color:#fff;text-decoration:none;font-size:14px;font-weight:700;padding:6px 14px;border-radius:6px;background:rgba(255,255,255,.18);margin-right:10px;display:inline-flex;align-items:center;gap:4px;}
.btn-voltar:hover{background:rgba(255,255,255,.35);}
.area{max-width:960px;margin:0 auto;}
.painel{background:#fff;border-radius:10px;padding:20px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.1);}
.painel h3{color:#1565c0;margin-bottom:14px;font-size:16px;border-bottom:2px solid #bbdefb;padding-bottom:8px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}
.campo label{display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:4px;}
.campo input,.campo select{width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;font-size:13px;background:#fff;color:#222;}
.btn{border:none;border-radius:6px;padding:9px 18px;cursor:pointer;font-size:13px;font-weight:600;}
.btn-azul{background:#1565c0;color:#fff;}
.btn-azul:hover{background:#0d47a1;}
.btn-verde{background:#2e7d32;color:#fff;}
.btn-verde:hover{background:#1b5e20;}
.btn-rem{background:#b71c1c;color:#fff;}
.btn-rem:hover{background:#7f0000;}
.btn-cinza{background:#546e7a;color:#fff;}
.btn-cinza:hover{background:#263238;}
.acoes{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{background:#e3f2fd;color:#1565c0;padding:9px 10px;text-align:left;font-weight:700;}
td{padding:8px 10px;border-bottom:1px solid #f0f0f0;vertical-align:middle;}
tr:hover td{background:#fafafa;}
.badge-canal{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;color:#fff;}
.canal-telegram{background:#0088cc;}
.canal-email{background:#e65100;}
.canal-ambos{background:#6a1b9a;}
.msg-vazia{color:#888;font-size:13px;padding:16px 0;text-align:center;}
.toast{position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;border-radius:8px;color:#fff;font-size:13px;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.25);transition:opacity .4s;}
.toast.ok{background:#2e7d32;}
.toast.err{background:#b71c1c;}
.toast.info{background:#1565c0;}
.toast.hide{opacity:0;}
.info-box{background:#e3f2fd;border:1px solid #90caf9;border-radius:8px;padding:12px 16px;font-size:12px;color:#0d47a1;margin-bottom:14px;line-height:1.6;}
.info-box a{color:#0d47a1;font-weight:700;}
.tab-nav{display:flex;gap:0;border-bottom:2px solid #e3f2fd;margin-bottom:16px;}
.tab-btn{padding:8px 18px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:600;color:#888;border-bottom:3px solid transparent;margin-bottom:-2px;}
.tab-btn.ativo{color:#1565c0;border-bottom-color:#1565c0;}
.tab-painel{display:none;}
.tab-painel.ativo{display:block;}
@media(max-width:640px){.grid-2,.grid-3{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="topo-fixo">
    <div style="display:flex;align-items:center;">
        <a href="inicio.php" class="btn-voltar">&#8592; Inicio</a>
        <h1>&#128276; Lembretes de Postos</h1>
    </div>
    <span class="versao">v2.0.3</span>
</div>

<div class="area">

<div class="painel">
    <div class="tab-nav">
        <button class="tab-btn ativo" onclick="mudarAba('lembretes')">Lembretes</button>
        <button class="tab-btn" onclick="mudarAba('config')">Configuracoes de Envio</button>
        <button class="tab-btn" onclick="mudarAba('agendador')">Agendador</button>
    </div>

    <!-- ABA: LEMBRETES -->
    <div id="tab-lembretes" class="tab-painel ativo">
        <div class="info-box">
            Marque aqui os postos que voce ainda precisa fechar. O sistema enviara um lembrete
            pelo canal configurado no intervalo definido, enquanto o posto estiver marcado como ativo.
            <br>Clique em <strong>Disparar Agora</strong> para testar ou enviar imediatamente.
        </div>

        <h3>Adicionar Lembrete</h3>
        <div class="grid-3">
            <div class="campo">
                <label>Posto</label>
                <input type="text" id="l-posto" placeholder="Ex.: 001" maxlength="10">
            </div>
            <div class="campo">
                <label>Nome do posto (opcional)</label>
                <input type="text" id="l-nome" placeholder="Ex.: CURITIBA CENTRO" maxlength="120">
            </div>
            <div class="campo">
                <label>Responsavel</label>
                <input type="text" id="l-resp" placeholder="Seu nome" maxlength="120">
            </div>
            <div class="campo">
                <label>Intervalo de lembrete (minutos)</label>
                <input type="number" id="l-intervalo" value="60" min="5" max="1440">
            </div>
            <div class="campo">
                <label>Canal</label>
                <select id="l-canal">
                    <option value="ambos">Telegram + Email</option>
                    <option value="telegram">Somente Telegram</option>
                    <option value="email">Somente Email</option>
                </select>
            </div>
        </div>
        <div class="acoes">
            <button class="btn btn-azul" onclick="salvarLembrete()">Salvar Lembrete</button>
            <button class="btn btn-verde" onclick="dispararAgora()">&#9993; Disparar Agora</button>
        </div>

        <h3 style="margin-top:20px;">Lembretes Ativos</h3>
        <table>
            <thead><tr>
                <th>Posto</th><th>Nome</th><th>Responsavel</th><th>Intervalo</th><th>Canal</th><th>Ultimo Envio</th><th>Acao</th>
            </tr></thead>
            <tbody id="tbody-lembretes">
                <tr><td colspan="7" class="msg-vazia">Carregando...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- ABA: CONFIGURACOES -->
    <div id="tab-config" class="tab-painel">
        <div class="info-box">
            <strong>Telegram:</strong> Crie um bot em <a href="https://t.me/BotFather" target="_blank">@BotFather</a>,
            copie o token e cole abaixo. Para o Chat ID: envie uma mensagem para o bot e acesse
            <code>https://api.telegram.org/bot&lt;TOKEN&gt;/getUpdates</code> para ver o chat_id.<br><br>
            <strong>Email:</strong> Preencha os dados SMTP do servidor de email da Celepar (ou outro).
            Se o servidor nao exigir autenticacao, deixe usuario/senha em branco.
        </div>

        <h3>Telegram</h3>
        <div class="grid-2">
            <div class="campo">
                <label>Token do Bot</label>
                <input type="text" id="cfg-telegram_token" placeholder="1234567890:AAFxxxxxx" maxlength="200" value="<?php echo e(isset($cfg['telegram_token']) ? $cfg['telegram_token'] : ''); ?>">
            </div>
            <div class="campo">
                <label>Chat ID (grupo ou usuario)</label>
                <input type="text" id="cfg-telegram_chat_id" placeholder="-1001234567890" maxlength="80" value="<?php echo e(isset($cfg['telegram_chat_id']) ? $cfg['telegram_chat_id'] : ''); ?>">
            </div>
        </div>

        <h3 style="margin-top:18px;">Email (SMTP)</h3>
        <div class="grid-2">
            <div class="campo">
                <label>Email destino</label>
                <input type="email" id="cfg-email_destino" placeholder="voce@celepar.pr.gov.br" maxlength="120" value="<?php echo e(isset($cfg['email_destino']) ? $cfg['email_destino'] : ''); ?>">
            </div>
            <div class="campo">
                <label>Email remetente</label>
                <input type="email" id="cfg-email_remetente" placeholder="sistema@celepar.pr.gov.br" maxlength="120" value="<?php echo e(isset($cfg['email_remetente']) ? $cfg['email_remetente'] : ''); ?>">
            </div>
            <div class="campo">
                <label>Servidor SMTP</label>
                <input type="text" id="cfg-email_smtp_host" placeholder="smtp.celepar.pr.gov.br" maxlength="120" value="<?php echo e(isset($cfg['email_smtp_host']) ? $cfg['email_smtp_host'] : ''); ?>">
            </div>
            <div class="campo">
                <label>Porta SMTP</label>
                <input type="number" id="cfg-email_smtp_porta" placeholder="587" maxlength="5" value="<?php echo e(isset($cfg['email_smtp_porta']) ? $cfg['email_smtp_porta'] : '587'); ?>">
            </div>
            <div class="campo">
                <label>Usuario SMTP</label>
                <input type="text" id="cfg-email_smtp_user" placeholder="Deixe em branco se nao usar" maxlength="120" value="<?php echo e(isset($cfg['email_smtp_user']) ? $cfg['email_smtp_user'] : ''); ?>">
            </div>
            <div class="campo">
                <label>Senha SMTP</label>
                <input type="password" id="cfg-email_smtp_pass" placeholder="Deixe em branco se nao usar" maxlength="120" value="<?php echo e(isset($cfg['email_smtp_pass']) ? $cfg['email_smtp_pass'] : ''); ?>">
            </div>
        </div>
        <div class="acoes">
            <button class="btn btn-azul" onclick="salvarConfig()">Salvar Configuracoes</button>
        </div>
    </div>

    <!-- ABA: AGENDADOR -->
    <div id="tab-agendador" class="tab-painel">
        <div class="info-box">
            O agendador roda em segundo plano neste servidor e verifica automaticamente quais postos
            precisam receber lembrete, respeitando o intervalo configurado em cada um.
            Ele precisa ser <strong>iniciado uma vez</strong> e continua rodando ate ser parado ou o servidor reiniciar.
            <br>Apos reiniciar o servidor, basta clicar em <strong>Iniciar</strong> novamente.
        </div>

        <div id="agend-status-card" style="border-radius:8px;padding:16px 20px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="font-size:15px;font-weight:700;" id="agend-status-texto">Verificando...</div>
                <div style="font-size:12px;color:#555;margin-top:4px;" id="agend-status-detalhe"></div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn btn-verde" id="btn-iniciar-agend" onclick="iniciarAgendador()" style="display:none;">&#9654; Iniciar Agendador</button>
                <button class="btn btn-rem"   id="btn-parar-agend"   onclick="pararAgendador()"   style="display:none;">&#9632; Parar Agendador</button>
                <button class="btn btn-cinza"                         onclick="atualizarStatusAgendador()">&#8635; Atualizar</button>
            </div>
        </div>

        <h3>Log de atividade</h3>
        <div style="display:flex;gap:10px;margin-bottom:8px;">
            <button class="btn btn-cinza" style="padding:6px 14px;font-size:12px;" onclick="carregarLogAgendador()">&#8635; Atualizar log</button>
        </div>
        <pre id="agend-log" style="background:#1e272e;color:#dfe6e9;border-radius:8px;padding:14px;font-size:11px;line-height:1.6;max-height:340px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;">Clique em "Atualizar log" para carregar.</pre>
    </div>
</div>

</div>
<div id="toast" class="toast hide"></div>

<script type="text/javascript">
var respSalvo = '';
try { respSalvo = localStorage.getItem('lembretes_responsavel') || ''; } catch(e) {}
if (respSalvo) document.getElementById('l-resp').value = respSalvo;

function mudarAba(aba) {
    var abas = ['lembretes','config','agendador'];
    for (var i = 0; i < abas.length; i++) {
        var tab = document.getElementById('tab-' + abas[i]);
        var btn = document.querySelectorAll('.tab-btn')[i];
        if (abas[i] === aba) {
            if (tab) tab.className = 'tab-painel ativo';
            if (btn) btn.className = 'tab-btn ativo';
        } else {
            if (tab) tab.className = 'tab-painel';
            if (btn) btn.className = 'tab-btn';
        }
    }
    if (aba === 'agendador') {
        atualizarStatusAgendador();
        carregarLogAgendador();
    }
}

function api(dados, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'lembretes.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        try { callback(JSON.parse(xhr.responseText)); }
        catch(e) { callback({ok: false, erro: 'Resposta invalida'}); }
    };
    var pares = [];
    for (var k in dados) {
        if (Object.prototype.hasOwnProperty.call(dados, k)) {
            pares.push(encodeURIComponent(k) + '=' + encodeURIComponent(dados[k]));
        }
    }
    xhr.send(pares.join('&'));
}

function toast(msg, tipo) {
    var el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'toast ' + (tipo || 'ok');
    clearTimeout(el._t);
    el._t = setTimeout(function() { el.className = 'toast hide'; }, 4000);
}

function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatarData(s) {
    if (!s) return '-';
    var d = new Date(s);
    if (isNaN(d.getTime())) return s;
    return d.toLocaleString('pt-BR');
}

function carregarLembretes() {
    api({ajax: 'listar'}, function(r) {
        var tbody = document.getElementById('tbody-lembretes');
        if (!r.ok || !r.lista || r.lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="msg-vazia">Nenhum lembrete ativo.</td></tr>';
            return;
        }
        var html = '';
        for (var i = 0; i < r.lista.length; i++) {
            var x = r.lista[i];
            var corCanal = x.canal === 'telegram' ? 'canal-telegram' : (x.canal === 'email' ? 'canal-email' : 'canal-ambos');
            html += '<tr>' +
                '<td><strong>' + esc(x.posto) + '</strong></td>' +
                '<td>' + esc(x.nome || '-') + '</td>' +
                '<td>' + esc(x.responsavel || '-') + '</td>' +
                '<td>' + esc(x.intervalo_min) + ' min</td>' +
                '<td><span class="badge-canal ' + corCanal + '">' + esc(x.canal) + '</span></td>' +
                '<td>' + esc(formatarData(x.ultimo_envio)) + '</td>' +
                '<td><button class="btn btn-rem" style="padding:4px 10px;font-size:11px;" onclick="removerLembrete(\'' + esc(x.posto) + '\')">Remover</button></td>' +
                '</tr>';
        }
        tbody.innerHTML = html;
    });
}

function salvarLembrete() {
    var posto   = document.getElementById('l-posto').value.trim();
    var nome    = document.getElementById('l-nome').value.trim();
    var resp    = document.getElementById('l-resp').value.trim();
    var interv  = document.getElementById('l-intervalo').value;
    var canal   = document.getElementById('l-canal').value;
    if (!posto) { toast('Informe o posto', 'err'); return; }
    if (!resp)  { toast('Informe o responsavel', 'err'); return; }
    try { localStorage.setItem('lembretes_responsavel', resp); } catch(e) {}
    api({ajax: 'salvar_lembrete', posto: posto, nome: nome, responsavel: resp, intervalo_min: interv, canal: canal}, function(r) {
        if (r.ok) {
            document.getElementById('l-posto').value = '';
            document.getElementById('l-nome').value  = '';
            carregarLembretes();
            toast('Lembrete salvo', 'ok');
        } else {
            toast(r.erro || 'Erro ao salvar', 'err');
        }
    });
}

function removerLembrete(posto) {
    if (!confirm('Remover lembrete do posto ' + posto + '?')) return;
    api({ajax: 'remover_lembrete', posto: posto}, function(r) {
        if (r.ok) { carregarLembretes(); toast('Lembrete removido', 'ok'); }
        else toast(r.erro || 'Erro', 'err');
    });
}

function dispararAgora() {
    toast('Disparando lembretes...', 'info');
    api({ajax: 'testar_agora'}, function(r) {
        if (r.ok) {
            var res = r.resultado || {};
            var msg = 'Disparado! Enviados: ' + (res.enviados || 0) + ', Erros: ' + (res.erros || 0);
            toast(msg, 'ok');
            carregarLembretes();
        } else {
            toast(r.erro || 'Erro ao disparar', 'err');
        }
    });
}

function salvarConfig() {
    var campos = ['telegram_token','telegram_chat_id','email_destino','email_remetente',
                  'email_smtp_host','email_smtp_porta','email_smtp_user','email_smtp_pass'];
    var dados = {ajax: 'salvar_config'};
    for (var i = 0; i < campos.length; i++) {
        var el = document.getElementById('cfg-' + campos[i]);
        if (el) dados[campos[i]] = el.value;
    }
    api(dados, function(r) {
        if (r.ok) toast('Configuracoes salvas', 'ok');
        else toast(r.erro || 'Erro', 'err');
    });
}

// ---- Agendador ----

function atualizarStatusAgendador() {
    var card   = document.getElementById('agend-status-card');
    var texto  = document.getElementById('agend-status-texto');
    var detalhe= document.getElementById('agend-status-detalhe');
    var btnIni = document.getElementById('btn-iniciar-agend');
    var btnPar = document.getElementById('btn-parar-agend');
    if (texto) texto.textContent = 'Verificando...';
    api({ajax: 'status_agendador'}, function(r) {
        if (!r.ok) {
            if (texto) texto.textContent = 'Erro ao consultar status';
            return;
        }
        if (r.rodando && !r.atrasado) {
            card.style.background = '#e8f5e9';
            card.style.border     = '2px solid #4caf50';
            texto.innerHTML       = '&#9679; <span style="color:#2e7d32">Agendador rodando</span> (PID ' + r.pid + ')';
            var ultimoHB = r.heartbeat ? new Date(r.heartbeat * 1000).toLocaleString('pt-BR') : '-';
            detalhe.textContent   = 'Ultimo heartbeat: ' + ultimoHB;
            if (btnIni) btnIni.style.display = 'none';
            if (btnPar) btnPar.style.display = '';
        } else if (r.rodando && r.atrasado) {
            card.style.background = '#fff8e1';
            card.style.border     = '2px solid #ffc107';
            texto.innerHTML       = '&#9888; <span style="color:#e65100">Agendador possivelmente travado</span> (PID ' + r.pid + ')';
            detalhe.textContent   = 'Heartbeat atrasado. Considere parar e reiniciar.';
            if (btnIni) btnIni.style.display = 'none';
            if (btnPar) btnPar.style.display = '';
        } else if (r.parada_pendente) {
            card.style.background = '#fff3e0';
            card.style.border     = '2px solid #ff9800';
            texto.innerHTML       = '&#9203; Encerrando...';
            detalhe.textContent   = 'Sinal de parada enviado. Aguarde.';
            if (btnIni) btnIni.style.display = 'none';
            if (btnPar) btnPar.style.display = 'none';
        } else {
            card.style.background = '#fce4ec';
            card.style.border     = '2px solid #ef9a9a';
            texto.innerHTML       = '&#9679; <span style="color:#b71c1c">Agendador parado</span>';
            detalhe.textContent   = 'Clique em Iniciar para comecar o envio automatico.';
            if (btnIni) btnIni.style.display = '';
            if (btnPar) btnPar.style.display = 'none';
        }
    });
}

function iniciarAgendador() {
    var btnIni = document.getElementById('btn-iniciar-agend');
    if (btnIni) { btnIni.disabled = true; btnIni.textContent = 'Iniciando...'; }
    toast('Iniciando agendador...', 'info');
    api({ajax: 'iniciar_agendador'}, function(r) {
        if (btnIni) { btnIni.disabled = false; btnIni.innerHTML = '&#9654; Iniciar Agendador'; }
        if (r.ok) {
            toast('Agendador iniciado (PID ' + r.pid + ')', 'ok');
            setTimeout(atualizarStatusAgendador, 1500);
            setTimeout(carregarLogAgendador, 2000);
        } else {
            toast(r.erro || 'Nao foi possivel iniciar', 'err');
        }
    });
}

function pararAgendador() {
    if (!confirm('Parar o agendador? Os lembretes automaticos serao suspensos.')) return;
    var btnPar = document.getElementById('btn-parar-agend');
    if (btnPar) { btnPar.disabled = true; btnPar.textContent = 'Parando...'; }
    toast('Enviando sinal de parada...', 'info');
    api({ajax: 'parar_agendador'}, function(r) {
        if (btnPar) { btnPar.disabled = false; btnPar.innerHTML = '&#9632; Parar Agendador'; }
        if (r.ok) {
            toast(r.ainda_rodando ? 'Sinal enviado. Aguarde o encerramento.' : 'Agendador parado.', 'ok');
            setTimeout(atualizarStatusAgendador, 1500);
        } else {
            toast('Erro ao parar', 'err');
        }
    });
}

function carregarLogAgendador() {
    var pre = document.getElementById('agend-log');
    if (pre) pre.textContent = 'Carregando...';
    api({ajax: 'log_agendador'}, function(r) {
        if (!r.ok || !r.log) {
            if (pre) pre.textContent = 'Nenhuma atividade registrada ainda.';
            return;
        }
        if (pre) {
            pre.textContent = r.log || 'Log vazio.';
            pre.scrollTop   = pre.scrollHeight;
        }
    });
}

carregarLembretes();
</script>
<?php include __DIR__ . '/includes/util_botoes_fixos.php'; ?>
</body>
</html>
