<?php
/* lembretes_agendador.php — v1.0.0
 * Agendador interno de lembretes. Roda em loop infinito (background).
 * Cada tick: verifica postos com lembrete vencido e dispara.
 * Controlado por lembretes.php (start/stop via PID + arquivo de parada).
 *
 * Uso (background):  php lembretes_agendador.php >> /tmp/lembretes.log 2>&1 &
 */

define('AGEND_PID_FILE',       sys_get_temp_dir() . '/lembretes_agendador.pid');
define('AGEND_HEARTBEAT_FILE', sys_get_temp_dir() . '/lembretes_agendador.heartbeat');
define('AGEND_STOP_FILE',      sys_get_temp_dir() . '/lembretes_agendador.stop');
define('AGEND_TICK_SECONDS',   60);   // verificar a cada 60 segundos

// Garantir instancia unica
if (file_exists(AGEND_PID_FILE)) {
    $pid_ant = (int)file_get_contents(AGEND_PID_FILE);
    if ($pid_ant > 0 && file_exists('/proc/' . $pid_ant)) {
        echo date('d/m/Y H:i:s') . " [AVISO] Agendador ja esta rodando (PID $pid_ant). Saindo.\n";
        exit(0);
    }
}

// Remover flag de parada residual
if (file_exists(AGEND_STOP_FILE)) {
    @unlink(AGEND_STOP_FILE);
}

// Salvar PID
$meu_pid = function_exists('getmypid') ? getmypid() : 0;
file_put_contents(AGEND_PID_FILE, (string)$meu_pid);
echo date('d/m/Y H:i:s') . " [START] Agendador iniciado (PID $meu_pid, tick=" . AGEND_TICK_SECONDS . "s)\n";

// ----------------------------------------------------------------
// Funcoes de envio (identicas ao lembretes_disparo.php, autonomas)
// ----------------------------------------------------------------

function agend_telegram($token, $chat_id, $msg) {
    if (!$token || !$chat_id) return array(false, 'Token/ChatID nao configurado');
    $url  = 'https://api.telegram.org/bot' . urlencode($token) . '/sendMessage';
    $body = http_build_query(array('chat_id' => $chat_id, 'text' => $msg, 'parse_mode' => 'HTML'));
    $ctx  = stream_context_create(array('http' => array(
        'method'        => 'POST',
        'header'        => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($body),
        'content'       => $body,
        'timeout'       => 10,
        'ignore_errors' => true
    )));
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return array(false, 'Timeout/falha Telegram');
    $dec = @json_decode($resp, true);
    if ($dec && isset($dec['ok']) && $dec['ok']) return array(true, 'ok');
    return array(false, isset($dec['description']) ? $dec['description'] : 'Erro Telegram');
}

function agend_smtp_readline($sock) {
    $resp = '';
    while (!feof($sock)) {
        $linha = fgets($sock, 512);
        $resp .= $linha;
        if (isset($linha[3]) && $linha[3] === ' ') break;
    }
    return $resp;
}

function agend_smtp_cmd($sock, $cmd) {
    fputs($sock, $cmd . "\r\n");
    return agend_smtp_readline($sock);
}

function agend_email($cfg, $para, $assunto, $corpo) {
    $remetente = (isset($cfg['email_remetente']) && $cfg['email_remetente'])
                 ? $cfg['email_remetente'] : 'sistema@localhost';
    $smtp_host = isset($cfg['email_smtp_host'])  ? trim($cfg['email_smtp_host'])  : '';
    $smtp_port = isset($cfg['email_smtp_porta']) ? (int)$cfg['email_smtp_porta']  : 587;
    $smtp_user = isset($cfg['email_smtp_user'])  ? trim($cfg['email_smtp_user'])  : '';
    $smtp_pass = isset($cfg['email_smtp_pass'])  ? trim($cfg['email_smtp_pass'])  : '';

    if ($smtp_host) {
        $ssl    = ($smtp_port == 465);
        $prefix = $ssl ? 'ssl://' : '';
        $sock   = @fsockopen($prefix . $smtp_host, $smtp_port, $errno, $errstr, 10);
        if (!$sock) return array(false, 'Falha ao conectar SMTP: ' . $errstr);
        agend_smtp_readline($sock);
        $ehlo = agend_smtp_cmd($sock, 'EHLO ' . gethostname());
        if (!$ssl && $smtp_port == 587 && strpos($ehlo, 'STARTTLS') !== false) {
            agend_smtp_cmd($sock, 'STARTTLS');
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            agend_smtp_cmd($sock, 'EHLO ' . gethostname());
        }
        if ($smtp_user) {
            agend_smtp_cmd($sock, 'AUTH LOGIN');
            agend_smtp_cmd($sock, base64_encode($smtp_user));
            $auth = agend_smtp_cmd($sock, base64_encode($smtp_pass));
            if (substr(trim($auth), 0, 3) !== '235') {
                fclose($sock);
                return array(false, 'Autenticacao SMTP falhou');
            }
        }
        agend_smtp_cmd($sock, 'MAIL FROM:<' . $remetente . '>');
        agend_smtp_cmd($sock, 'RCPT TO:<' . $para . '>');
        agend_smtp_cmd($sock, 'DATA');
        $cabecalhos  = "From: Sistema Lacres <" . $remetente . ">\r\n";
        $cabecalhos .= "To: " . $para . "\r\n";
        $cabecalhos .= "Subject: " . $assunto . "\r\n";
        $cabecalhos .= "MIME-Version: 1.0\r\n";
        $cabecalhos .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $cabecalhos .= "Content-Transfer-Encoding: 8bit\r\n";
        $resp = agend_smtp_cmd($sock, $cabecalhos . "\r\n" . $corpo . "\r\n.");
        agend_smtp_cmd($sock, 'QUIT');
        fclose($sock);
        if (substr(trim($resp), 0, 3) === '250') return array(true, 'ok');
        return array(false, 'SMTP rejeitou: ' . trim($resp));
    }

    $ok = @mail($para, $assunto, $corpo,
                'From: ' . $remetente . "\r\nContent-Type: text/plain; charset=UTF-8");
    return $ok ? array(true, 'ok (mail nativo)') : array(false, 'mail() falhou');
}

// ----------------------------------------------------------------
// Loop principal
// ----------------------------------------------------------------

function agend_tick() {
    $log_prefixo = date('d/m/Y H:i:s');

    try {
        $pdo = new PDO("mysql:host=" . (getenv('DB_HOST') ?: '10.15.61.169') . ";dbname=" . (getenv('DB_NAME') ?: 'controle') . ";charset=utf8",
                       (getenv('DB_USER') ?: 'controle_mat'), (getenv('DB_PASS') ?: '375256'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Carregar config
        $cfg = array();
        foreach ($pdo->query("SELECT chave, valor FROM ciLembretesConfig")->fetchAll() as $r) {
            $cfg[$r['chave']] = $r['valor'];
        }

        $tg_token   = isset($cfg['telegram_token'])   ? trim($cfg['telegram_token'])   : '';
        $tg_chat    = isset($cfg['telegram_chat_id']) ? trim($cfg['telegram_chat_id']) : '';
        $email_dest = isset($cfg['email_destino'])    ? trim($cfg['email_destino'])    : '';
        $agora_ts   = time();

        $lembretes = $pdo->query("SELECT * FROM ciLembretes WHERE ativo = 1")->fetchAll();
        $enviados  = 0;
        $erros     = 0;
        $ignorados = 0;

        foreach ($lembretes as $lem) {
            $posto  = $lem['posto'];
            $nome   = $lem['nome'] ? $lem['nome'] : ('Posto ' . $posto);
            $interv = max(5, (int)$lem['intervalo_min']);
            $canal  = $lem['canal'];
            $resp   = $lem['responsavel'];

            // Verificar intervalo
            if ($lem['ultimo_envio']) {
                $diff_min = ($agora_ts - strtotime($lem['ultimo_envio'])) / 60;
                if ($diff_min < $interv) {
                    $ignorados++;
                    continue;
                }
            }

            $msg_base = "LEMBRETE SISTEMA LACRES\n"
                      . "-------------------------------\n"
                      . "Posto: " . $posto . " - " . $nome . "\n"
                      . "Responsavel: " . ($resp ? $resp : '-') . "\n"
                      . "Voce ainda NAO fechou este posto!\n"
                      . "Acesse o sistema e gere o oficio.\n"
                      . "-------------------------------\n"
                      . "Enviado em: " . date('d/m/Y H:i') . "\n";

            $msg_tg = "<b>&#128276; LEMBRETE - Posto nao fechado</b>\n"
                    . "Posto: <b>" . htmlspecialchars($posto) . "</b> - " . htmlspecialchars($nome) . "\n"
                    . "Responsavel: " . htmlspecialchars($resp ? $resp : '-') . "\n"
                    . "<i>Voce ainda NAO fechou este posto. Gere o oficio.</i>\n"
                    . "Horario: " . date('d/m/Y H:i');

            $enviou  = false;
            $erros_l = array();

            if (($canal === 'telegram' || $canal === 'ambos') && $tg_token && $tg_chat) {
                list($ok, $info) = agend_telegram($tg_token, $tg_chat, $msg_tg);
                if ($ok) $enviou = true;
                else     $erros_l[] = 'Telegram: ' . $info;
            }

            if (($canal === 'email' || $canal === 'ambos') && $email_dest) {
                $assunto = 'Lembrete: posto ' . $posto . ' nao foi fechado';
                list($ok, $info) = agend_email($cfg, $email_dest, $assunto, $msg_base);
                if ($ok) $enviou = true;
                else     $erros_l[] = 'Email: ' . $info;
            }

            if ($enviou) {
                $pdo->prepare("UPDATE ciLembretes SET ultimo_envio=NOW() WHERE posto=?")
                    ->execute(array($posto));
                $enviados++;
                echo $log_prefixo . " [OK] Posto $posto: lembrete enviado\n";
            } elseif (!empty($erros_l)) {
                $erros++;
                echo $log_prefixo . " [ERRO] Posto $posto: " . implode('; ', $erros_l) . "\n";
            }
        }

        if ($enviados || $erros) {
            echo $log_prefixo . " [TICK] enviados=$enviados erros=$erros ignorados=$ignorados\n";
        }

    } catch (Exception $ex) {
        echo date('d/m/Y H:i:s') . " [ERRO BD] " . $ex->getMessage() . "\n";
    }
}

// Loop infinito com tick de AGEND_TICK_SECONDS
while (true) {
    // Verificar sinal de parada
    if (file_exists(AGEND_STOP_FILE)) {
        echo date('d/m/Y H:i:s') . " [STOP] Arquivo de parada detectado. Encerrando.\n";
        @unlink(AGEND_STOP_FILE);
        @unlink(AGEND_PID_FILE);
        exit(0);
    }

    // Atualizar heartbeat
    file_put_contents(AGEND_HEARTBEAT_FILE, time());

    // Executar tick
    agend_tick();

    // Aguardar proximo tick (verificando parada a cada 5s)
    $espera = AGEND_TICK_SECONDS;
    while ($espera > 0) {
        sleep(5);
        $espera -= 5;
        if (file_exists(AGEND_STOP_FILE)) break;
    }
}
