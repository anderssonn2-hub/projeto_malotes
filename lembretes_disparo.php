<?php
/* lembretes_disparo.php — v1.0.0
 * Motor de disparo de lembretes via Telegram e/ou Email.
 * Pode ser chamado:
 *   - Via browser: lembretes_disparo.php?token=disparo_interno
 *   - Via cron/agendador interno
 *   - Via include de lembretes.php (testar_agora)
 * Retorna JSON: {"enviados": N, "erros": N, "log": [...]}
 */

// Quando incluído por outro PHP, não emite headers novamente
$_DISPARO_INCLUIDO = defined('_DISPARO_INCLUIDO');
define('_DISPARO_INCLUIDO', 1);

if (!$_DISPARO_INCLUIDO) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache');
}

function disparo_enviar_telegram($token, $chat_id, $mensagem) {
    if (!$token || !$chat_id) return array(false, 'Token ou Chat ID nao configurado');
    $url  = 'https://api.telegram.org/bot' . urlencode($token) . '/sendMessage';
    $body = http_build_query(array('chat_id' => $chat_id, 'text' => $mensagem, 'parse_mode' => 'HTML'));
    $ctx  = stream_context_create(array('http' => array(
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($body),
        'content' => $body,
        'timeout' => 10,
        'ignore_errors' => true
    )));
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return array(false, 'Timeout ou falha de conexao com Telegram');
    $dec = @json_decode($resp, true);
    if ($dec && isset($dec['ok']) && $dec['ok']) return array(true, 'ok');
    $err = (isset($dec['description']) ? $dec['description'] : 'Erro desconhecido Telegram');
    return array(false, $err);
}

function disparo_enviar_email($cfg, $para, $assunto, $corpo) {
    $remetente = isset($cfg['email_remetente']) && $cfg['email_remetente'] ? $cfg['email_remetente'] : 'sistema@localhost';
    $smtp_host = isset($cfg['email_smtp_host']) ? trim($cfg['email_smtp_host']) : '';
    $smtp_port = isset($cfg['email_smtp_porta']) ? (int)$cfg['email_smtp_porta'] : 587;
    $smtp_user = isset($cfg['email_smtp_user']) ? trim($cfg['email_smtp_user']) : '';
    $smtp_pass = isset($cfg['email_smtp_pass']) ? trim($cfg['email_smtp_pass']) : '';

    // Tenta envio via SMTP simples (socket direto, sem lib externa)
    // Suporta: conexao sem SSL (porta 25/587) e com SSL (porta 465)
    if ($smtp_host) {
        $ssl    = ($smtp_port == 465);
        $prefix = $ssl ? 'ssl://' : '';
        $sock   = @fsockopen($prefix . $smtp_host, $smtp_port, $errno, $errstr, 10);
        if (!$sock) return array(false, 'Nao foi possivel conectar ao SMTP: ' . $errstr);
        $smtp_read = function($sock) {
            $resp = '';
            while (!feof($sock)) {
                $linha = fgets($sock, 512);
                $resp .= $linha;
                if (isset($linha[3]) && $linha[3] === ' ') break;
            }
            return $resp;
        };
        $smtp_cmd = function($sock, $cmd) use ($smtp_read) {
            fputs($sock, $cmd . "\r\n");
            return $smtp_read($sock);
        };
        $smtp_read($sock);
        $ehlo = $smtp_cmd($sock, 'EHLO ' . gethostname());
        if (!$ssl && $smtp_port == 587 && strpos($ehlo, 'STARTTLS') !== false) {
            $smtp_cmd($sock, 'STARTTLS');
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $smtp_cmd($sock, 'EHLO ' . gethostname());
        }
        if ($smtp_user) {
            $smtp_cmd($sock, 'AUTH LOGIN');
            $smtp_cmd($sock, base64_encode($smtp_user));
            $auth = $smtp_cmd($sock, base64_encode($smtp_pass));
            if (substr(trim($auth), 0, 3) !== '235') {
                fclose($sock);
                return array(false, 'Falha na autenticacao SMTP');
            }
        }
        $smtp_cmd($sock, 'MAIL FROM:<' . $remetente . '>');
        $smtp_cmd($sock, 'RCPT TO:<' . $para . '>');
        $smtp_cmd($sock, 'DATA');
        $headers  = "From: Sistema Lacres <" . $remetente . ">\r\n";
        $headers .= "To: " . $para . "\r\n";
        $headers .= "Subject: " . $assunto . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $resp = $smtp_cmd($sock, $headers . "\r\n" . $corpo . "\r\n.");
        $smtp_cmd($sock, 'QUIT');
        fclose($sock);
        if (substr(trim($resp), 0, 3) === '250') return array(true, 'ok');
        return array(false, 'SMTP rejeitou mensagem: ' . trim($resp));
    }

    // Fallback: mail() nativo do PHP
    $ok = @mail($para, $assunto, $corpo, 'From: ' . $remetente . "\r\nContent-Type: text/plain; charset=UTF-8");
    if ($ok) return array(true, 'ok (mail nativo)');
    return array(false, 'mail() falhou — configure SMTP');
}

$resultado = array('enviados' => 0, 'erros' => 0, 'ignorados' => 0, 'log' => array());

try {
    $pdo = new PDO("mysql:host=" . (getenv('DB_HOST') ?: '10.15.61.169') . ";dbname=" . (getenv('DB_NAME') ?: 'controle') . ";charset=utf8",
                   (getenv('DB_USER') ?: 'controle_mat'), (getenv('DB_PASS') ?: '375256'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Carregar config
    $cfg    = array();
    $stCfg  = $pdo->query("SELECT chave, valor FROM ciLembretesConfig");
    foreach ($stCfg->fetchAll() as $r) $cfg[$r['chave']] = $r['valor'];

    $tg_token   = isset($cfg['telegram_token'])   ? trim($cfg['telegram_token'])   : '';
    $tg_chat    = isset($cfg['telegram_chat_id']) ? trim($cfg['telegram_chat_id']) : '';
    $email_dest = isset($cfg['email_destino'])    ? trim($cfg['email_destino'])    : '';
    $agora      = date('Y-m-d H:i:s');

    // Buscar lembretes que precisam ser disparados
    // Condição: ativo=1 E (ultimo_envio IS NULL OU ultimo_envio <= NOW() - intervalo_min minutos)
    $stmtL = $pdo->query("SELECT * FROM ciLembretes WHERE ativo = 1");
    $lembretes = $stmtL->fetchAll();

    foreach ($lembretes as $lem) {
        $posto    = $lem['posto'];
        $nome     = $lem['nome'] ? $lem['nome'] : ('Posto ' . $posto);
        $interv   = max(5, (int)$lem['intervalo_min']);
        $canal    = $lem['canal'];
        $resp     = $lem['responsavel'];

        // Verificar intervalo
        if ($lem['ultimo_envio']) {
            $diff = (strtotime($agora) - strtotime($lem['ultimo_envio'])) / 60;
            if ($diff < $interv) {
                $resultado['ignorados']++;
                $resultado['log'][] = 'Posto ' . $posto . ': ignorado (proximo em ' . round($interv - $diff) . ' min)';
                continue;
            }
        }

        $msg_base = "LEMBRETE SISTEMA LACRES\n"
                  . "-------------------------------\n"
                  . "Posto: " . $posto . " - " . $nome . "\n"
                  . "Responsavel: " . ($resp ?: '-') . "\n"
                  . "Mensagem: Voce ainda NAO fechou este posto!\n"
                  . "Acesse o sistema e gere o oficio.\n"
                  . "-------------------------------\n"
                  . "Enviado em: " . date('d/m/Y H:i') . "\n";

        $msg_tg   = "<b>&#128276; LEMBRETE — Posto nao fechado</b>\n"
                  . "Posto: <b>" . htmlspecialchars($posto) . "</b> — " . htmlspecialchars($nome) . "\n"
                  . "Responsavel: " . htmlspecialchars($resp ?: '-') . "\n"
                  . "<i>Voce ainda NAO fechou este posto. Gere o oficio.</i>\n"
                  . "Horario: " . date('d/m/Y H:i');

        $enviou   = false;
        $erros_l  = array();

        if (($canal === 'telegram' || $canal === 'ambos') && $tg_token && $tg_chat) {
            list($ok, $info) = disparo_enviar_telegram($tg_token, $tg_chat, $msg_tg);
            if ($ok) $enviou = true;
            else     $erros_l[] = 'Telegram: ' . $info;
        }

        if (($canal === 'email' || $canal === 'ambos') && $email_dest) {
            $assunto = 'Lembrete: posto ' . $posto . ' ainda nao foi fechado';
            list($ok, $info) = disparo_enviar_email($cfg, $email_dest, $assunto, $msg_base);
            if ($ok) $enviou = true;
            else     $erros_l[] = 'Email: ' . $info;
        }

        if ($enviou) {
            $pdo->prepare("UPDATE ciLembretes SET ultimo_envio=NOW() WHERE posto=?")
                ->execute(array($posto));
            $resultado['enviados']++;
            $resultado['log'][] = 'Posto ' . $posto . ': lembrete enviado';
        } else {
            $resultado['erros']++;
            $resultado['log'][] = 'Posto ' . $posto . ': FALHA — ' . implode('; ', $erros_l);
        }
    }

} catch (PDOException $ex) {
    $resultado['log'][] = 'Erro BD: ' . $ex->getMessage();
    $resultado['erros']++;
}

if (!$_DISPARO_INCLUIDO) {
    echo json_encode($resultado);
}
return $resultado;
