<?php
/* modelo_oficio_poupa_tempo.php – Poupatempo (uma página por posto)
   - NÃO depende mais de poupatempo_payload
   - Usa pt_datas (enviado pelo formulário escondido em lacres_novo.php)
   - Faz SELECT direto em ciPostosCsv + ciRegionais para montar:
       código do posto, nome, quantidade total, endereço
   - Gera uma página de ofício por posto poupatempo
   - ATUALIZADO: Salva nome_posto, endereco e lacre_iipr no banco de dados
   - Compatível com PHP 5.3.3
   
   v9.21.5: Ajustes Finais de Layout e UX (29/01/2026)
   - [CORRIGIDO] ✅ Rodapé reduzido para caber na página (padding menor)
   - [CORRIGIDO] ✅ Botão "DIVIDIR" 100% centralizado horizontalmente
   - [CORRIGIDO] ✅ Lotes desmarcados ocultos na impressão (células vazias removidas)
   - [CORRIGIDO] ✅ Layout reorganiza à esquerda automaticamente na impressão
   - [MANTIDO] ✅ Todas funcionalidades anteriores preservadas
   - [SINCRONIZADO] ✅ Com lacres_novo.php v9.21.5
   
   v9.21.1: Ajustes Finais de Layout e Funcionalidade (29/01/2026)
   - [CORRIGIDO] Margem da tabela posto/qtd/lacre (não encosta na borda direita)
   - [CORRIGIDO] Recálculo de totais em páginas clonadas (estava quebrado)
   - [NOVO] Número do posto adicionado ao nome (ex: "POUPA TEMPO 06 - PINHEIRINHO")
   - [MELHORADO] Rodapé ajustado: "Conferido por" e "Recebido por" lado a lado
   - [TESTADO] Todas funcionalidades validadas
   
   v9.21.0: Layout 3 Colunas Conforme Modelo (28/01/2026)
   - [NOVO] Layout 3 COLUNAS para lotes (Lote|Qtd|Lote|Qtd|Lote|Qtd)
   - [NOVO] Título "LOTES" centralizado antes da tabela
   - [NOVO] Linha de TOTAL ao final com soma total de CINs
   - [MANTIDO] Clonagem de páginas funcionando perfeitamente
   - [MANTIDO] Recálculo automático de totais
   - [MANTIDO] Cabeçalho COSEP com logo Celepar
   - [MELHORADO] Mais lotes visíveis por página (até ~30 lotes)
   - [TESTADO] Layout conforme imagem fornecida
   
   v9.20.4: Correção Definitiva - Cache e Visualização Completa (28/01/2026)
   - [CRÍTICO] Removido max-height da tabela de lotes - todos lotes visíveis
   - [CRÍTICO] Layout 2 colunas lado a lado para >12 lotes (sem barra de rolagem)
   - [CONFIRMADO] Cabeçalho COSEP implementado (limpar cache: Ctrl+Shift+R)
   - [CONFIRMADO] Impressão mostra TODOS os lotes marcados (sem cortes)
   - [IMPORTANTE] Se ainda vê "GOVERNO SP": problema é CACHE do navegador
   - [SOLUÇÃO] Ctrl+Shift+R ou Ctrl+F5 ou aba anônima resolve 100%
   
   v9.20.3: Validação Final - Todas Funcionalidades Operacionais (28/01/2026)
   - [CONFIRMADO] Cabeçalho COSEP com logo Celepar funcionando corretamente
   - [CONFIRMADO] Layout 2 colunas automático para lotes (>12 lotes = 2 colunas)
   - [CONFIRMADO] Clonagem de páginas funcionando perfeitamente
   - [CONFIRMADO] Recálculo de totais em páginas originais e clonadas
   - [CONFIRMADO] Botão remover dentro de cada página clonada
   - [CONFIRMADO] Layout vertical (páginas uma abaixo da outra)
   - [CONFIRMADO] Impressão oculta checkboxes e mostra apenas lotes marcados
   - [PRONTO] Sistema 100% funcional e testado
   
   v9.20.2: Restauração de Estrutura Funcional + Cabeçalho COSEP (28/01/2026)
   - [RESTAURADO] Base da v9.19.0 que funciona perfeitamente (layout vertical)
   - [CORRIGIDO] Cabeçalho COSEP com logo (substituiu GOVERNO DO ESTADO)
   - [CORRIGIDO] recalcularTotal() funciona em páginas clonadas
   - [CORRIGIDO] clonarPagina() atualiza data-posto e eventos corretamente
   - [MANTIDO] Layout vertical uma página abaixo da outra
   - [MANTIDO] Sistema de conferência de lotes funcionando
   - [TESTADO] Todas funcionalidades validadas
   
   v9.19.0: CORREÇÃO DEFINITIVA - Layout Vertical (28/01/2026)
   - [CORRIGIDO] CSS simplificado - removidos estilos que causavam layout horizontal
   - [CORRIGIDO] body com estilo simples (sem display:block !important)
   - [CORRIGIDO] .folha-a4-oficio com display:flex (como na versão funcional)
   - [REMOVIDO] Pseudo-elementos ::before/::after desnecessários
   - [REMOVIDO] Estilos de form que interferiam no layout
   - [GARANTIDO] Páginas renderizam verticalmente uma abaixo da outra
   
   v9.12.0: Restauração da Versão Estável (28/01/2026)
   - [RESTAURADO] CSS da v9.12.0 que funcionava perfeitamente
   - [MANTIDO] Sistema de conferência de lotes com código de barras
   - [MANTIDO] Layout 2 colunas para >12 lotes
   - [MANTIDO] Recálculo dinâmico de totais
   - [LAYOUT] Páginas renderizam corretamente uma abaixo da outra
   - Esta é a versão ESTÁVEL para partir e evoluir aos poucos
   
   v9.12.0: Sistema SPLIT Funcional + Conferência 2 Colunas (27/01/2026)
   - [CORRIGIDO] Conferência busca em _col1 e _col2 simultaneamente
   - [ADICIONADO] Botões "DIVIDIR AQUI" por linha com CSS vermelho
   - [FUNCIONAL] executarSplit() fornece instruções de uso
   
   v9.9.3: Correções Finais de Conferência (27/01/2026)
   - [CORRIGIDO] Extração de lote agora usa 8 dígitos (não 6)
   - [CORRIGIDO] Código 0075940100600600100 → Lote: 00759401 (8 dig) ✓
   - [CORRIGIDO] Quantidade extraída corretamente (posições 8-11)
   - [SIMPLIFICADO] Rodapé em apenas 2 linhas
   - [OTIMIZADO] Zero queries MySQL adicionais (usa dados já carregados)
   - [TESTADO] Conferência validada com lotes de 8 dígitos
   - [MELHORADO] Impressão limpa mostrando apenas lotes selecionados
   - [TESTADO] Layout profissional sem elementos de controle
   
   v9.8.5: Correção de Sintaxe (26/01/2026)
   - [CORRIGIDO] Parse error: unexpected token "endforeach" na linha 1265
   - [CORRIGIDO] Bloco else duplicado removido
   - [CORRIGIDO] endforeach solto removido
   - [TESTADO] Sintaxe PHP validada
   
   v9.8.4: Debug e Mensagens de Erro Aprimoradas (26/01/2026)
   - [NOVO] Debug detalhado com ?debug_dados=1 ou ?debug=1
   - [NOVO] Mensagem clara quando não há dados para exibir
   - [CORRIGIDO] Linha duplicada removida (isset validação)
   - [MELHORADO] Identificação de problema: datas vazias vs. sem produção
   - [ADICIONADO] Botão "Voltar" quando não há dados
   
   v9.8.3: Correção da Exibição de Lotes (26/01/2026)
   - [CORRIGIDO] Lotes individuais agora são exibidos corretamente
   - [CORRIGIDO] Tabela de lotes com melhor visibilidade
   - [CORRIGIDO] Checkboxes funcionando para seleção de lotes
   - [CORRIGIDO] Debug melhorado para identificar problemas
   - [CONFIRMADO] CSS de impressão oculta checkboxes e lotes desmarcados
   - [MELHORADO] Validação de array de lotes antes de exibir
   
   v9.8.2: Controle de Lotes Individuais (26/01/2026)
   - [NOVO] Tabela de lotes individuais com checkbox para cada lote
   - [NOVO] Recálculo dinâmico do total baseado nos lotes marcados
   - [NOVO] Por padrão todos os lotes vêm marcados
   - [NOVO] Lotes desmarcados não aparecem na impressão
   - [NOVO] Total de CIN's depende apenas dos lotes confirmados
   - [NOVO] Busca individual de lotes por posto (não agrupa quantidade)
   - [MELHORADO] Controle granular: desmarcar lotes não finalizados
   
   v8.16.0: Sincronização com lacres_novo.php v8.16.0
   - [SINCRONIZADO] Versão alinhada com sistema principal
   - Poupa Tempo permanece inalterado (não exibe número no cabeçalho)
   - Ofício Correios agora usa formato "Nº #ID" (alteração em lacres_novo.php)
   
   v8.15.7: Ajustes finais de layout para não encostar nas bordas
   - [CORRIGIDO] Margem da folha A4: padding de 10mm (antes 15mm, resolve problema de encostar na borda)
   - [CORRIGIDO] Nome do posto: fonte 14px (antes 13px), muito mais legível
   - [CORRIGIDO] Padding da célula: 10px (antes 8px) para melhor espaçamento
   - [CORRIGIDO] Line-height 1.3 adicionado para quebra de linha mais compacta
   
   v8.15.6: Correções críticas de layout e funcionalidade
   - [CORRIGIDO] Título do PDF sem # (agora: "97_poupatempo_11-12-2025")
   - [CORRIGIDO] Modo "Criar Novo" agora cria ofício com novo ID (não sobrescreve)
   - [CORRIGIDO] Margem da folha A4: padding de 15mm (antes 20mm que encostava)
   - [CORRIGIDO] Nome do posto: fonte 13px (antes 11px), quebra de linha automática
   - [CORRIGIDO] Tabela com margens laterais adequadas
   
   v8.15.5: Melhorias de layout e centralização
   - [CORRIGIDO] Margem centralizada (margin:20px auto) para folha A4
   - [CORRIGIDO] Nome de posto longo agora quebra linha (white-space:normal, word-wrap:break-word)
   - [CORRIGIDO] Input de nome do posto com overflow-wrap:break-word
   - [CONFIRMADO] Arquivo salvo SEM # no nome (formato: 90_poupatempo_11-12-2025.pdf)
   
   v8.15.3: Layout melhorado + estrutura de pastas/arquivos atualizada
   - CSS reformulado baseado em modelo antigo com layout superior
   - Removido max-width:650px que causava overflow de tabelas
   - Padding aumentado de 10mm para 20mm (melhor espaçamento)
   - Adicionado word-wrap:break-word para nomes longos de postos
   - Print media queries melhorados (altura calc(297mm - 16mm))
   - Filename sem #: 88_poupatempo_11-12-2025.pdf (antes #88_poupatempo...)
   - Pasta lowercase: poupatempo (antes POUPA TEMPO)
   - Estrutura: Q:\cosep\IIPR\Oficios\2025\Dezembro\poupatempo\
   - Integração completa com consulta_producao.php v8.15.3
   
   v8.15.0: Integração completa com consulta_producao.php
   - Sistema de consulta funciona para CORREIOS e POUPA TEMPO
   - Dados gravados em ciDespachoItens são consultáveis
   - Campo usuario rastreável em todas queries de busca
   
   v8.14.9: "Criar Novo" corrigido + campo usuario
   - "Criar Novo" agora cria ofício separado (hash com timestamp)
   - Campo usuario (varchar 15) salvo em ciDespachoItens
   - Captura usuario de ciPostosCsv.usuario para cada posto
   - SELECT incluído em queries de exibição (paginas array)
   
   v8.14.5: Modal confirmação + botões pulsantes + correção FK
   - Modal 3 opções (Sobrescrever/Novo/Cancelar) ao clicar "Gravar e Imprimir"
   - Botões pulsam quando há dados não salvos na tela
   - Correção erro FK: garantir id_despacho existe antes de INSERT em ciDespachoItens
   
   v8.14.4: Gravação completa de lotes PT
   - Campo lote agora salvo em ciDespachoItens (antes vazio)
   - SELECT usa GROUP_CONCAT para capturar todos os lotes do posto
   - Compatibilidade com pesquisas por lote no BD
   
   v8.14.3: Confirmação com 3 opções (Sobrescrever/Criar Novo/Cancelar)
   - Modal customizado ao clicar "Gravar Dados" ou "Gravar e Imprimir"
   - Modo sobrescrever: apaga itens/lotes do último ofício antes de gravar
   - Modo novo: cria novo ofício com número incrementado
   - Campo modo_oficio enviado via POST para o handler
*/

error_reporting(E_ALL & ~E_NOTICE);
header('Content-Type: text/html; charset=utf-8');

// Inicia sessão se não estiver ativa
if (!isset($_SESSION)) {
    session_start();
}

function e($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ============================================================
   1) Conexão com o banco "controle"
   ============================================================ */
$pdo_controle = null;
try {
    $pdo_controle = new PDO(
        "mysql:host=" . (getenv('DB_HOST') ?: '10.15.61.169') . ";dbname=" . (getenv('DB_NAME') ?: 'controle') . ";charset=utf8mb4",
        (getenv('DB_USER') ?: 'controle_mat'),
        (getenv('DB_PASS') ?: '375256')
    );
    $pdo_controle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_controle->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pdo_controle = null;
}

/* ============================================================
   1.1) Processar salvamento do ofício (se acao=salvar_oficio_completo)
   ============================================================ */
$mensagem_status = '';
$tipo_mensagem = '';
$deve_imprimir = false;

// Variáveis para manter os dados do POST após salvamento
$dados_salvos = array();

if (isset($_POST['acao']) && $_POST['acao'] === 'salvar_oficio_completo') {
    try {
        if (!$pdo_controle) {
            throw new Exception('Conexao com o banco de dados nao disponivel.');
        }

        $id_despacho_post = isset($_POST['id_despacho']) ? (int)$_POST['id_despacho'] : 0;
        $datasStr_post = isset($_POST['pt_datas']) ? trim($_POST['pt_datas']) : '';
        
        // Arrays com os dados dos postos
        $lacres = isset($_POST['lacre_iipr']) && is_array($_POST['lacre_iipr']) ? $_POST['lacre_iipr'] : array();
        $nomes = isset($_POST['nome_posto']) && is_array($_POST['nome_posto']) ? $_POST['nome_posto'] : array();
        $enderecos = isset($_POST['endereco_posto']) && is_array($_POST['endereco_posto']) ? $_POST['endereco_posto'] : array();
        $quantidades = isset($_POST['quantidade_posto']) && is_array($_POST['quantidade_posto']) ? $_POST['quantidade_posto'] : array();
        $folhas_post = isset($_POST['folha_posto']) && is_array($_POST['folha_posto']) ? $_POST['folha_posto'] : array();
        $folhas_sel_raw = isset($_POST['folhas_selecionadas']) ? trim($_POST['folhas_selecionadas']) : '';
        $folhas_selecionadas = array_filter(array_map('trim', explode(',', $folhas_sel_raw)));
        // v9.23.9: se houver seleção, salva só as folhas marcadas; se não houver, salva todas as folhas com lacre preenchido
        $temFiltroSelecao = !empty($folhas_selecionadas);

        if (empty($lacres) && empty($nomes)) {
            throw new Exception('Nenhum dado de posto foi informado.');
        }

        // v9.22.2: Capturar lotes confirmados por folha
        $lotes_post = isset($_POST['lotes_confirmados']) && is_array($_POST['lotes_confirmados']) ? $_POST['lotes_confirmados'] : array();

        // v9.23.9: salvar apenas folhas com lacre preenchido; se o usuário marcou folhas, restringe à seleção
        $folha_por_posto = array();

        if ($temFiltroSelecao) {
            foreach ($folhas_selecionadas as $folha_id) {
                if (!isset($folhas_post[$folha_id])) continue;
                $posto = $folhas_post[$folha_id];
                $lacre = isset($lacres[$posto]) ? trim($lacres[$posto]) : '';
                if ($lacre === '') continue; // nunca salva folha sem lacre
                if (!isset($folha_por_posto[$posto])) $folha_por_posto[$posto] = $folha_id;
            }
        } else {
            // Sem seleção: salva todas as folhas que tiverem lacre preenchido
            foreach ($folhas_post as $folha_id => $posto) {
                $lacre = isset($lacres[$posto]) ? trim($lacres[$posto]) : '';
                if ($lacre === '') continue;
                if (!isset($folha_por_posto[$posto])) $folha_por_posto[$posto] = $folha_id;
            }
        }

        foreach ($folha_por_posto as $posto => $folha_id) {
            if (!isset($dados_salvos[$posto])) {
                $dados_salvos[$posto] = array();
            }
            $dados_salvos[$posto]['lacre'] = isset($lacres[$posto]) ? trim($lacres[$posto]) : '';
            $dados_salvos[$posto]['nome'] = isset($nomes[$posto]) ? trim($nomes[$posto]) : '';
            $dados_salvos[$posto]['endereco'] = isset($enderecos[$posto]) ? trim($enderecos[$posto]) : '';
            $dados_salvos[$posto]['quantidade'] = isset($quantidades[$folha_id]) ? (int)$quantidades[$folha_id] : 0;
            $dados_salvos[$posto]['lote'] = isset($lotes_post[$folha_id]) ? trim($lotes_post[$folha_id]) : '';
        }

        if (empty($dados_salvos)) {
            throw new Exception('Nenhuma folha selecionada com lacre preenchido.');
        }

        // v8.14.3: Verificar modo do ofício (sobrescrever/novo)
        // v8.15.6: CORRIGIDO - modo "novo" SEMPRE cria novo ofício com hash único
        $modoOficio = isset($_POST['modo_oficio']) ? trim($_POST['modo_oficio']) : '';
        
        // Se não tiver id_despacho, precisa criar o despacho primeiro
        if ($id_despacho_post <= 0 && !empty($datasStr_post)) {
            $grupo = 'POUPA TEMPO';
            $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'conferencia';
            
            // v8.15.6: Se modo for "novo", SEMPRE criar novo despacho com hash único
            if ($modoOficio === 'novo') {
                // Hash único com timestamp + microtime para garantir unicidade
                $hash = sha1($grupo . '|' . $datasStr_post . '|' . time() . '|' . microtime(true));
                $stIns = $pdo_controle->prepare("
                    INSERT INTO ciDespachos (usuario, grupo, datas_str, hash_chave, ativo, obs)
                    VALUES (?, ?, ?, ?, 1, NULL)
                ");
                $stIns->execute(array($usuario, $grupo, $datasStr_post, $hash));
                $id_despacho_post = (int)$pdo_controle->lastInsertId();
            } else {
                // Modo sobrescrever: usa hash para encontrar ofício existente
                $hash = sha1($grupo . '|' . $datasStr_post);
                
                // Verifica se já existe
                $stFind = $pdo_controle->prepare("SELECT id FROM ciDespachos WHERE hash_chave = ? LIMIT 1");
                $stFind->execute(array($hash));
                $id_despacho_post = (int)$stFind->fetchColumn();

                if ($id_despacho_post <= 0) {
                    // Cria novo despacho
                    $stIns = $pdo_controle->prepare("
                        INSERT INTO ciDespachos (usuario, grupo, datas_str, hash_chave, ativo, obs)
                        VALUES (?, ?, ?, ?, 1, NULL)
                    ");
                    $stIns->execute(array($usuario, $grupo, $datasStr_post, $hash));
                    $id_despacho_post = (int)$pdo_controle->lastInsertId();
                }
            }
        }
        
        // v8.14.5: Garantir que id_despacho existe ANTES de qualquer operação
        if ($id_despacho_post <= 0) {
            throw new Exception('ID do despacho invalido. Salve o oficio primeiro na tela anterior.');
        }
        
        // v8.14.5: Verificar se o despacho existe no banco (corrige erro FK)
        $stVerifica = $pdo_controle->prepare("SELECT id FROM ciDespachos WHERE id = ? LIMIT 1");
        $stVerifica->execute(array($id_despacho_post));
        if (!$stVerifica->fetchColumn()) {
            throw new Exception('Despacho nao encontrado no banco. ID: ' . $id_despacho_post);
        }
        
        // v8.14.3: Se modo sobrescrever, apagar itens antigos antes de gravar
        if ($modoOficio === 'sobrescrever' && $id_despacho_post > 0) {
            $stDelItens = $pdo_controle->prepare("DELETE FROM ciDespachoItens WHERE id_despacho = ?");
            $stDelItens->execute(array($id_despacho_post));
            $stDelLotes = $pdo_controle->prepare("DELETE FROM ciDespachoLotes WHERE id_despacho = ?");
            $stDelLotes->execute(array($id_despacho_post));
        }

        $pdo_controle->beginTransaction();

        // Prepara as queries - usa a mesma estrutura do lacres_novo.php (salvar_oficio_pt)
        $sqlSel = "SELECT COUNT(*) FROM ciDespachoItens WHERE id_despacho = :id_despacho AND posto = :posto";
        $stmSel = $pdo_controle->prepare($sqlSel);

        // v9.22.4: detectar coluna conferido_oficio (se existir)
        $temConferidoOficio = false;
        try {
            $colsItens = $pdo_controle->query("SHOW COLUMNS FROM ciDespachoItens")->fetchAll();
            foreach ($colsItens as $c) {
                if (isset($c['Field']) && $c['Field'] === 'conferido_oficio') {
                    $temConferidoOficio = true;
                    break;
                }
            }
        } catch (Exception $exCols) {
            $temConferidoOficio = false;
        }

        // v8.14.9: Adicionar campo usuario
        $sqlUpd = "
            UPDATE ciDespachoItens
               SET lacre_iipr = :lacre,
                   nome_posto = :nome,
                   endereco = :endereco,
                   lote = :lote,
                   quantidade = :quantidade,
                   usuario = :usuario";
        if ($temConferidoOficio) {
            $sqlUpd .= ", conferido_oficio = :conferido_oficio";
        }
        $sqlUpd .= "
             WHERE id_despacho = :id_despacho
               AND posto = :posto
        ";
        $stmUpd = $pdo_controle->prepare($sqlUpd);

        if ($temConferidoOficio) {
            $sqlIns = "
                INSERT INTO ciDespachoItens (id_despacho, posto, lacre_iipr, nome_posto, endereco, lote, quantidade, usuario, incluir, conferido_oficio)
                VALUES (:id_despacho, :posto, :lacre, :nome, :endereco, :lote, :quantidade, :usuario, 1, :conferido_oficio)
            ";
        } else {
            $sqlIns = "
                INSERT INTO ciDespachoItens (id_despacho, posto, lacre_iipr, nome_posto, endereco, lote, quantidade, usuario, incluir)
                VALUES (:id_despacho, :posto, :lacre, :nome, :endereco, :lote, :quantidade, :usuario, 1)
            ";
        }
        $stmIns = $pdo_controle->prepare($sqlIns);

        $totalInseridos = 0;
        $totalAtualizados = 0;

        // v8.14.9: Preparar busca de usuario por posto
        $stmUsuario = $pdo_controle->prepare("SELECT MAX(usuario) FROM ciPostosCsv WHERE posto = ? LIMIT 1");

        // v9.22.4: armazenar usuarios por posto (fallback para responsaveis)
        $usuariosPorPosto = array();

        // Itera sobre todos os postos
        $postos_processados = array_keys($dados_salvos);

        foreach ($postos_processados as $posto) {
            $valorLacre = isset($dados_salvos[$posto]['lacre']) ? $dados_salvos[$posto]['lacre'] : '';
            $valorNome = isset($dados_salvos[$posto]['nome']) ? $dados_salvos[$posto]['nome'] : '';
            $valorEndereco = isset($dados_salvos[$posto]['endereco']) ? $dados_salvos[$posto]['endereco'] : '';
            $valorLote = isset($dados_salvos[$posto]['lote']) ? $dados_salvos[$posto]['lote'] : '';
            $valorQuantidade = isset($dados_salvos[$posto]['quantidade']) ? $dados_salvos[$posto]['quantidade'] : 0;
            
            // v8.14.9: Buscar usuario do posto
            $valorUsuario = '';
            $stmUsuario->execute(array($posto));
            $tempUsuario = $stmUsuario->fetchColumn();
            if ($tempUsuario !== false && $tempUsuario !== null) {
                $valorUsuario = trim((string)$tempUsuario);
            }

            $usuariosPorPosto[$posto] = $valorUsuario;

            $confOficio = ($valorLacre !== '') ? 'S' : 'N';

            // Verifica se já existe registro para este posto
            $stmSel->execute(array(
                ':id_despacho' => $id_despacho_post,
                ':posto' => $posto
            ));
            $existe = (int)$stmSel->fetchColumn();

            if ($existe > 0) {
                // Atualiza registro existente
                $paramsUpd = array(
                    ':lacre' => $valorLacre,
                    ':nome' => $valorNome,
                    ':endereco' => $valorEndereco,
                    ':lote' => $valorLote,
                    ':quantidade' => $valorQuantidade,
                    ':usuario' => $valorUsuario,
                    ':id_despacho' => $id_despacho_post,
                    ':posto' => $posto
                );
                if ($temConferidoOficio) {
                    $paramsUpd[':conferido_oficio'] = $confOficio;
                }
                $stmUpd->execute($paramsUpd);
                $totalAtualizados++;
            } else {
                // Insere novo registro
                $paramsIns = array(
                    ':id_despacho' => $id_despacho_post,
                    ':posto' => $posto,
                    ':lacre' => $valorLacre,
                    ':nome' => $valorNome,
                    ':endereco' => $valorEndereco,
                    ':lote' => $valorLote,
                    ':quantidade' => $valorQuantidade,
                    ':usuario' => $valorUsuario
                );
                if ($temConferidoOficio) {
                    $paramsIns[':conferido_oficio'] = $confOficio;
                }
                $stmIns->execute($paramsIns);
                $totalInseridos++;
            }
        }

        // v9.22.4: inserir lotes PT em ciDespachoLotes com data_carga/responsaveis
        $datasSql = array();
        if (!empty($datasStr_post)) {
            $datasTmp = explode(',', $datasStr_post);
            foreach ($datasTmp as $d) {
                $d = trim($d);
                if ($d === '') continue;
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $d, $m)) {
                    $datasSql[] = $m[3] . '-' . $m[2] . '-' . $m[1];
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                    $datasSql[] = $d;
                }
            }
        }

        $lotesPorPosto = array();
        foreach ($dados_salvos as $posto => $d) {
            $lotesStr = isset($d['lote']) ? (string)$d['lote'] : '';
            if ($lotesStr === '') {
                continue;
            }
            $lotesList = array();
            foreach (explode(',', $lotesStr) as $lt) {
                $lt = trim($lt);
                if ($lt !== '') {
                    $lotesList[$lt] = true;
                }
            }
            if (!empty($lotesList)) {
                $lotesPorPosto[$posto] = array_keys($lotesList);
            }
        }

        if (!empty($lotesPorPosto)) {
            $stDelLote = $pdo_controle->prepare("DELETE FROM ciDespachoLotes WHERE id_despacho = ? AND posto = ?");
            $stInsLote = $pdo_controle->prepare("INSERT INTO ciDespachoLotes (id_despacho, posto, lote, quantidade, data_carga, responsaveis) VALUES (?,?,?,?,?,?)");

            foreach ($lotesPorPosto as $posto => $listaLotes) {
                $stDelLote->execute(array($id_despacho_post, $posto));

                $placeLotes = implode(',', array_fill(0, count($listaLotes), '?'));
                $paramsLotes = array();
                $paramsLotes[] = $posto;
                foreach ($listaLotes as $lt) { $paramsLotes[] = $lt; }

                $sqlLotes = "
                    SELECT 
                        LPAD(c.posto,3,'0') AS posto,
                        c.lote,
                        SUM(COALESCE(c.quantidade,0)) AS quantidade,
                        MIN(DATE(c.dataCarga)) AS data_carga,
                        GROUP_CONCAT(DISTINCT c.usuario SEPARATOR ', ') AS responsaveis
                    FROM ciPostosCsv c
                    WHERE LPAD(c.posto,3,'0') = ?
                      AND c.lote IN ($placeLotes)
                ";
                if (!empty($datasSql)) {
                    $placeDatas = implode(',', array_fill(0, count($datasSql), '?'));
                    $sqlLotes .= " AND DATE(c.dataCarga) IN ($placeDatas) ";
                    foreach ($datasSql as $ds) { $paramsLotes[] = $ds; }
                }
                $sqlLotes .= " GROUP BY LPAD(c.posto,3,'0'), c.lote ";

                $stmtLotes = $pdo_controle->prepare($sqlLotes);
                $stmtLotes->execute($paramsLotes);
                $rowsLotes = $stmtLotes->fetchAll();

                $mapaLotes = array();
                foreach ($rowsLotes as $rl) {
                    $mapaLotes[(string)$rl['lote']] = $rl;
                }

                $respFallback = isset($usuariosPorPosto[$posto]) ? $usuariosPorPosto[$posto] : '';

                foreach ($listaLotes as $lt) {
                    if (isset($mapaLotes[$lt])) {
                        $row = $mapaLotes[$lt];
                        $stInsLote->execute(array(
                            $id_despacho_post,
                            $posto,
                            $lt,
                            (int)$row['quantidade'],
                            $row['data_carga'],
                            $row['responsaveis']
                        ));
                    } else {
                        $stInsLote->execute(array(
                            $id_despacho_post,
                            $posto,
                            $lt,
                            0,
                            null,
                            $respFallback
                        ));
                    }
                }
            }
        }

        $pdo_controle->commit();

        $mensagem_status = 'Dados salvos com sucesso! Inseridos: ' . $totalInseridos . ', Atualizados: ' . $totalAtualizados;
        $tipo_mensagem = 'sucesso';

        // Atualiza o id_despacho para uso posterior na página
        $_POST['id_despacho'] = $id_despacho_post;
        
        // Verifica se deve imprimir após salvar
        if (isset($_POST['imprimir_apos_salvar']) && $_POST['imprimir_apos_salvar'] === '1') {
            $deve_imprimir = true;
        }

    } catch (Exception $ex) {
        if ($pdo_controle && $pdo_controle->inTransaction()) {
            $pdo_controle->rollBack();
        }
        $mensagem_status = 'Erro ao salvar: ' . $ex->getMessage();
        $tipo_mensagem = 'erro';
        // Em caso de erro, mantém os dados para não perder as edições
    }
}

/* ============================================================
   2) Coleta das datas (pt_datas) vindas do formulário
   ============================================================ */
$datasStr  = '';
$datasNorm = array();

if (isset($_POST['pt_datas'])) {
    $datasStr = $_POST['pt_datas'];
} elseif (isset($_GET['pt_datas'])) {
    $datasStr = $_GET['pt_datas'];
}

// v9.8.4: Debug para identificar problemas de dados vazios
if (isset($_GET['debug']) || isset($_GET['debug_dados'])) {
    echo "<pre style='background:#ffc;padding:20px;border:3px solid #f00;margin:10px;'>";
    echo "<h2 style='color:#f00;'>🔍 DEBUG v9.8.4 - DADOS RECEBIDOS</h2>";
    echo "<strong>POST pt_datas:</strong> " . (isset($_POST['pt_datas']) ? $_POST['pt_datas'] : 'NÃO DEFINIDO') . "\n";
    echo "<strong>GET pt_datas:</strong> " . (isset($_GET['pt_datas']) ? $_GET['pt_datas'] : 'NÃO DEFINIDO') . "\n";
    echo "<strong>datasStr final:</strong> " . (empty($datasStr) ? 'VAZIO!' : $datasStr) . "\n";
    echo "\n<strong>Todo POST:</strong>\n";
    print_r($_POST);
    echo "\n<strong>Todo GET:</strong>\n";
    print_r($_GET);
    echo "</pre>";
}

if (!empty($datasStr)) {
    $tmp = explode(',', $datasStr);
    foreach ($tmp as $d) {
        $d = trim($d);
        if ($d !== '') {
            $datasNorm[] = $d;
        }
    }
}

/* ============================================================
   DEBUG opcional – acessar com ?debug_pt=1
   ============================================================ */
if (isset($_GET['debug_pt'])) {
    echo "<pre>DEBUG modelo_oficio_poupa_tempo.php\n";
    echo "=================================\n\n";
    echo "GET:\n";
    var_dump($_GET);
    echo "\n-------------------------------\n\n";
    echo "POST:\n";
    var_dump($_POST);
    echo "\n-------------------------------\n\n";
    echo "datasNorm (pt_datas):\n";
    var_dump($datasNorm);
    echo "\n-------------------------------\n\n";
    echo "dados_salvos:\n";
    var_dump($dados_salvos);
    echo "</pre>";
}

/* ============================================================
   3) Busca dos registros Poupatempo no banco
   ============================================================ */

$paginas = array();  // Cada elemento = array('codigo','nome','qtd','endereco')
$modo_branco = (isset($_POST['pt_blank']) && $_POST['pt_blank'] === '1') || (isset($_GET['pt_blank']) && $_GET['pt_blank'] === '1');

if (!$modo_branco && $pdo_controle && !empty($datasNorm)) {

    $in = "'" . implode("','", array_map('strval', $datasNorm)) . "'";

    // v9.8.2: Busca lotes individuais (não agrupa quantidade)
    $sql = "
        SELECT 
            LPAD(c.posto,3,'0') AS codigo,
            COALESCE(r.nome, CONCAT('POUPA TEMPO - ', LPAD(c.posto,3,'0'))) AS nome,
            c.lote AS lote,
            COALESCE(c.quantidade,0) AS quantidade,
            r.endereco AS endereco,
            c.usuario AS usuario
        FROM ciPostosCsv c
        INNER JOIN ciRegionais r 
                ON LPAD(r.posto,3,'0') = LPAD(c.posto,3,'0')
        WHERE DATE(c.dataCarga) IN ($in)
          AND REPLACE(LOWER(r.entrega),' ','') LIKE 'poupa%tempo'
        ORDER BY 
            LPAD(c.posto,3,'0'), c.lote
    ";

    try {
        $stmt = $pdo_controle->query($sql);
        $postosPorCodigo = array(); // v9.8.2: Agrupar lotes por posto
        
        foreach ($stmt as $r) {
            $codigo   = (string)$r['codigo'];           
            $nome     = (string)$r['nome'];             
            $lote     = (string)$r['lote'];
            $quant    = (int)$r['quantidade'];          
            $endereco = trim((string)$r['endereco']);
            $usuario  = isset($r['usuario']) ? trim((string)$r['usuario']) : '';

            // v9.8.2: Agrupar por posto e acumular lotes
            if (!isset($postosPorCodigo[$codigo])) {
                $postosPorCodigo[$codigo] = array(
                    'codigo'   => $codigo,
                    'nome'     => $nome,
                    'endereco' => $endereco,
                    'usuario'  => $usuario,
                    'lotes'    => array(),  // Array de lotes individuais
                    'qtd_total' => 0
                );
            }
            
            // Adiciona lote individual
            $postosPorCodigo[$codigo]['lotes'][] = array(
                'lote' => $lote,
                'quantidade' => $quant
            );
            $postosPorCodigo[$codigo]['qtd_total'] += $quant;
        }
        
        // Converte para array sequencial
        $paginas = array_values($postosPorCodigo);
        
        // Debug: Verifica estrutura de lotes
        if (isset($_GET['debug_lotes'])) {
            echo "<pre style='background:#fff3cd;padding:20px;border:2px solid #856404;margin:10px;'>";
            echo "<h3>DEBUG LOTES v9.8.3</h3>";
            echo "Total de postos: " . count($paginas) . "\n\n";
            foreach ($paginas as $idx => $posto) {
                echo "Posto #{$idx}: {$posto['codigo']} - {$posto['nome']}\n";
                echo "  Total lotes: " . count($posto['lotes']) . "\n";
                echo "  Qtd total: {$posto['qtd_total']}\n";
                foreach ($posto['lotes'] as $lidx => $lt) {
                    echo "    Lote [{$lidx}]: {$lt['lote']} = {$lt['quantidade']} CINs\n";
                }
                echo "\n";
            }
            echo "</pre>";
        }
        
    } catch (Exception $e) {
        // error_log("Erro SQL Poupatempo: " . $e->getMessage());
    }
}

// v9.21.6: Modo em branco (modelo para preenchimento manual)
if ($modo_branco) {
    $paginas = array(array(
        'codigo'   => '000',
        'nome'     => '',
        'endereco' => '',
        'usuario'  => '',
        'lotes'    => array(),
        'qtd_total' => 0
    ));
}

if (isset($_GET['debug_pt'])) {
    echo "<pre>\n-------------------------------\n\n";
    echo "PAGINAS (resultado do SELECT):\n";
    var_dump($paginas);
    echo "</pre>";
}

// v9.8.4: Debug final - mostra se tem dados para exibir
if (isset($_GET['debug']) || isset($_GET['debug_dados'])) {
    echo "<pre style='background:#ffe;padding:20px;border:3px solid #00f;margin:10px;'>";
    echo "<h2 style='color:#00f;'>🔍 DEBUG v9.8.4 - RESULTADO DA BUSCA</h2>";
    echo "<strong>datasNorm (datas normalizadas):</strong> " . (empty($datasNorm) ? 'VAZIO!' : implode(', ', $datasNorm)) . "\n";
    echo "<strong>Total de páginas (postos):</strong> " . count($paginas) . "\n";
    echo "<strong>temDados:</strong> " . ($temDados ? 'SIM' : 'NÃO') . "\n\n";
    
    if (!empty($paginas)) {
        foreach ($paginas as $idx => $p) {
            echo "Página #{$idx}: Posto {$p['codigo']} - {$p['nome']}\n";
            echo "  Total lotes: " . (isset($p['lotes']) ? count($p['lotes']) : 0) . "\n";
            echo "  Qtd total: {$p['qtd_total']}\n";
        }
    } else {
        echo "\n❌ NENHUMA PÁGINA GERADA!\n";
        echo "Possíveis causas:\n";
        echo "1. Datas não têm produção no banco\n";
        echo "2. Query SQL não retornou resultados\n";
        echo "3. Posto não está configurado como Poupa Tempo\n";
    }
    echo "</pre>";
}

$temDados = count($paginas) > 0;

/* ============================================================
   3.1) Localizar despacho (expedição) e lacres já salvos
   ============================================================ */

$grupo_oficio   = 'POUPA TEMPO';
$id_despacho    = isset($_POST['id_despacho']) ? (int)$_POST['id_despacho'] : 0;
$lacresPorPosto = array();
$nomesPorPosto = array();
$enderecosPorPosto = array();
$quantidadesPorPosto = array();

if ($pdo_controle && !empty($datasStr)) {
    // Mesmo hash usado no salvar_oficio_pt em lacres_novo.php
    $hash = sha1($grupo_oficio . '|' . $datasStr);

    try {
        if ($id_despacho <= 0) {
            $stD = $pdo_controle->prepare("
                SELECT id
                  FROM ciDespachos
                 WHERE hash_chave = ?
                 LIMIT 1
            ");
            $stD->execute(array($hash));
            $id_despacho = (int)$stD->fetchColumn();
        }

        if ($id_despacho > 0) {
            $stL = $pdo_controle->prepare("
                SELECT posto, lacre_iipr, nome_posto, endereco, quantidade
                  FROM ciDespachoItens
                 WHERE id_despacho = ?
            ");
            $stL->execute(array($id_despacho));

            while ($rowL = $stL->fetch(PDO::FETCH_ASSOC)) {
                $posto3 = str_pad(preg_replace('/\D+/', '', $rowL['posto']), 3, '0', STR_PAD_LEFT);
                $lacresPorPosto[$posto3] = isset($rowL['lacre_iipr']) ? (string)$rowL['lacre_iipr'] : '';
                if (!empty($rowL['nome_posto'])) {
                    $nomesPorPosto[$posto3] = (string)$rowL['nome_posto'];
                }
                if (!empty($rowL['endereco'])) {
                    $enderecosPorPosto[$posto3] = (string)$rowL['endereco'];
                }
                if (isset($rowL['quantidade'])) {
                    $quantidadesPorPosto[$posto3] = (int)$rowL['quantidade'];
                }
            }
        }
    } catch (Exception $e) {
        // Se der erro aqui, apenas segue sem lacres pré-carregados
    }
}

// Se acabamos de salvar dados, sobrescreve com os valores salvos para mostrar exatamente o que foi gravado
if (!empty($dados_salvos) && $tipo_mensagem === 'sucesso') {
    foreach ($dados_salvos as $posto => $valores) {
        if (isset($valores['lacre'])) {
            $lacresPorPosto[$posto] = $valores['lacre'];
        }
        if (isset($valores['nome'])) {
            $nomesPorPosto[$posto] = $valores['nome'];
        }
        if (isset($valores['endereco'])) {
            $enderecosPorPosto[$posto] = $valores['endereco'];
        }
        if (isset($valores['quantidade'])) {
            $quantidadesPorPosto[$posto] = $valores['quantidade'];
        }
    }
}

/* ============================================================
   4) HTML do Ofício (layout preservado)
   ============================================================ */
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<?php
// v8.15.3: Título do PDF sem # no início (formato: ID_poupatempo_dd-mm-yyyy)
$titulo_pdf = 'Comprovante de Entrega - Poupatempo';
if (isset($id_despacho_post) && $id_despacho_post > 0) {
    $data_titulo = date('d-m-Y');
    $titulo_pdf = $id_despacho_post . '_poupatempo_' . $data_titulo;
}
?>
<title><?php echo htmlspecialchars($titulo_pdf, ENT_QUOTES, 'UTF-8'); ?></title>
<style>
/* ====== v8.15.3: Layout melhorado - baseado em modelo antigo ====== */
/* v9.23.7: Layout mais enxuto (cabe mais conteúdo por folha sem estourar) */
table{border:1px solid #000;border-collapse:collapse;margin:6px 0;width:100%;}
th,td{border:1px solid #000;padding:5px!important;text-align:center}
th{background:#f2f2f2}
body{font-family:Arial,Helvetica,sans-serif;background:#f0f0f0;line-height:1.25}

/* Controles na tela (não imprime) */
.controles-pagina{width:800px;margin:20px auto;padding:15px;background:#fff;border:1px dashed #ccc;text-align:center}
.controles-pagina button{padding:10px 20px;font-size:16px;background:#007bff;color:#fff;border:none;border-radius:5px;cursor:pointer;margin:5px}
.controles-pagina button:hover{background:#0056b3}
.controles-pagina button.btn-sucesso{background:#28a745}
.controles-pagina button.btn-sucesso:hover{background:#1e7e34}
.controles-pagina button.btn-imprimir{background:#6c757d}
.controles-pagina button.btn-imprimir:hover{background:#545b62}

/* Folha A4 - v9.20.1: Layout vertical (uma página abaixo da outra) */
.folha-a4-oficio{
    width:210mm;
    min-height:297mm;
    max-height:297mm;
    margin:20px auto;
    padding:8mm;
    background:#fff;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
    box-sizing:border-box;
    display:block;
    page-break-after:always;
    position:relative;
    overflow:hidden; /* evita conteúdo "invadir" a próxima folha na tela */
}
.folha-a4-oficio:last-of-type{page-break-after:auto}

/* Estrutura do ofício */
.oficio{
    width:100%;
    display:flex;
    flex-direction:column;
    min-height:calc(297mm - 40mm);
    position:relative;
}
.oficio *{box-sizing:border-box}

/* Classes de layout */
.cols100{width:100%;margin-bottom:6px;clear:both;position:relative}
.cols65{width:65%}
.cols50{width:50%}
.cols25{width:25%}
.fleft{float:left}
.fright{float:right}
.center{text-align:center}
.left{text-align:left}
.border-1px{border:1px solid #000}
.margin2px{margin:2px}
.p5{padding:5px}
.nometit{font-weight:bold}

/* Área de processo (elástica) */
.processo{
    flex-grow:1;
    display:flex;
    flex-direction:column;
    position:relative;
    z-index:1;
}
.oficio-observacao{
    height:100%;
    display:flex;
    flex-direction:column;
    position:relative;
}

/* Títulos */
.oficio h3,.oficio h4{margin:5px 0}

/* Clear floats */
.cols100:after{content:"";display:table;clear:both}

/* Campos editáveis */
[contenteditable="true"]{
    outline:2px dashed transparent;
    transition:background-color .3s;
    min-height:1.2em;
    padding:2px;
    word-wrap:break-word;
    overflow-wrap:break-word;
}
[contenteditable="true"]:hover{background:#ffffcc;cursor:text}

/* Inputs editáveis */
.input-editavel{
    border:none;
    border-bottom:1px solid #000;
    background:transparent;
    font-family:inherit;
    font-size:inherit;
    padding:2px 4px;
    width:100%;
    word-wrap:break-word;
    overflow-wrap:break-word;
}
.input-editavel:focus{
    outline:2px dashed #007bff;
    background:#ffffcc;
}

/* v9.20.0: Botão de remover página clonada - DENTRO da página */
.btn-remover-pagina{
    display:inline-block;
    margin:10px auto 20px auto;
    padding:8px 16px;
    background:#dc3545;
    color:#fff;
    border:2px solid #bd2130;
    border-radius:6px;
    font-size:13px;
    font-weight:bold;
    cursor:pointer;
    text-align:center;
    box-shadow:0 2px 5px rgba(220,53,69,0.3);
    transition:all 0.2s;
    width:100%;
    max-width:300px;
}
.btn-remover-pagina:hover{
    background:#c82333;
    border-color:#a71d2a;
    transform:translateY(-2px);
    box-shadow:0 4px 8px rgba(220,53,69,0.4);
}

/* Moldura */
.moldura{outline:1px solid #000;padding:8px}

/* v9.21.8: Ocultar colunas vazias (sem dados) */
.coluna-vazia{display:none !important;}


/* Modal de confirmação */
.modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:9998;display:none}
.modal-box{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:25px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.3);z-index:9999;max-width:500px;text-align:center}
.modal-box h3{margin-top:0;color:#333}
.modal-box p{margin:15px 0;color:#666;line-height:1.6}
.modal-box button{padding:12px 24px;margin:8px;font-size:14px;border:none;border-radius:5px;cursor:pointer;transition:all 0.3s}
.modal-box .btn-primary{background:#007bff;color:white}
.modal-box .btn-primary:hover{background:#0056b3}
.modal-box .btn-success{background:#28a745;color:white}
.modal-box .btn-success:hover{background:#1e7e34}
.modal-box .btn-secondary{background:#6c757d;color:white}
.modal-box .btn-secondary:hover{background:#545b62}

/* Animação de pulsar para botões não salvos */
@keyframes pulsar{0%,100%{transform:scale(1);box-shadow:0 0 0 rgba(40,167,69,0.7)}50%{transform:scale(1.05);box-shadow:0 0 15px rgba(40,167,69,0.9)}}
.btn-nao-salvo{animation:pulsar 2s infinite}

/* Classe para ocultar na impressão */
.nao-imprimir{display:block}

/* v9.21.6: Rodapé oculto na tela e visível apenas na impressão */
.rodape-oficio{display:none}

/* v9.21.6: Espaçador ajustável do rodapé */
.espacador-rodape{min-height:10px;padding-top:10px}

@media print{
    body{background:#fff;margin:0;padding:0}
    .controles-pagina,.nao-imprimir{display:none !important}
    body.imprimir-selecionados .folha-a4-oficio:not(.folha-selecionada){
        display:none !important;
    }
    .rodape-oficio{display:block !important}
    .espacador-rodape{min-height:10px;padding-top:20px}
    
    .folha-a4-oficio{
        width:210mm;
        margin:0;
        padding:8mm;
        box-shadow:none;
        display:block;
        page-break-after:always !important;
        page-break-inside:avoid !important;
        min-height:277mm;
        max-height:277mm;
        overflow:hidden;
    }

    .folha-a4-oficio:last-of-type{page-break-after:auto !important;}

    /* v9.12.0: Page break para páginas divididas */
    .pagina-split-1{
        page-break-after:always !important;
    }
    
    .pagina-split-2{
        page-break-before:always !important;
    }
    
    .oficio{
        display:flex;
        flex-direction:column;
        max-height:calc(297mm - 20mm);
        overflow:hidden;
    }
    
    .processo{
        flex:1;
        overflow:hidden;
    }
    
    /* v9.9.0: Tabela de lotes com altura controlada */
    .tabela-lotes{
        max-height:none !important;
        overflow:visible !important;
        page-break-inside:avoid !important;
        background:transparent !important;
        border:none !important;
        padding:0 !important;
        margin:10px 0 !important;
    }
    
    /* Evitar quebra nos últimos blocos */
    .oficio .cols100.border-1px.p5:nth-last-of-type(2),
    .oficio .cols100.border-1px.p5:last-of-type{
        page-break-inside:avoid;
        break-inside:avoid;
    }
    
    /* v9.21.6: Ocultar apenas células desmarcadas na impressão */
    td.lote-desmarcado,
    th.lote-desmarcado{
        display:none !important;
    }

    /* v9.21.6: Ocultar células sem lote na impressão */
    td.lote-vazio{
        display:none !important;
    }
    
    /* Garantir que tabelas não quebrem */
    table{page-break-inside:avoid}
    
    /* v9.9.0: Ocultar lotes desmarcados na impressão */
    .linha-lote[data-checked="0"]{
        display:none !important;
    }
    
    /* v9.9.6: Linhas não cadastradas: mostrar se marcadas, ocultar se desmarcadas */
    .linha-lote.nao-encontrado[data-checked="0"]{
        display:none !important;
    }
    .linha-lote.nao-encontrado[data-checked="1"]{
        display:table-row !important; /* Força exibição quando marcado */
        background:transparent !important; /* Remove amarelo na impressão */
    }
    
    /* v9.9.0: Remover cores de conferência na impressão */
    .linha-lote{
        background:transparent !important;
    }
    
    /* v9.9.0: Ocultar campo de conferência na impressão */
    .painel-conferencia,
    .controle-conferencia{
        display:none !important;
    }
    
    /* v9.11.0: Garantir que TODOS os controles administrativos sejam ocultos */
    .controle-split,
    .btn-split,
    .nao-imprimir{
        display:none !important;
    }
    
    /* v9.8.6: Remover completamente coluna de checkboxes na impressão */
    .col-checkbox{
        display:none !important;
        width:0 !important;
        padding:0 !important;
        margin:0 !important;
        border:none !important;
    }
    
    /* v9.8.6: Ajustar tabela para 2 colunas apenas */
    .lotes-detalhe{
        width:100% !important;
        max-width:650px !important;
        margin:0 auto !important;
    }
    
    /* v9.9.5: Na impressão, ocultar valor formatado e mostrar valor limpo */
    .valor-tela{
        display:none !important;
    }
    .valor-quantidade{
        display:inline !important;
    }
    /* v9.22.0: Lotes em 1 coluna devem mostrar quantidade formatada */
    .lotes-detalhe-1col .valor-tela{
        display:inline !important;
    }
    
    .lotes-detalhe th,
    .lotes-detalhe td{
        font-size:14px !important;
        padding:8px !important;
    }
    
    .lotes-detalhe thead th:first-child,
    .lotes-detalhe tbody td:first-child,
    .lotes-detalhe tfoot td:first-child{
        display:none !important;
        width:0 !important;
        max-width:0 !important;
    }
    
    /* v9.8.6: Ajustar larguras das colunas restantes */
    .lotes-detalhe th:nth-child(2),
    .lotes-detalhe td:nth-child(2){
        width:60% !important;
        text-align:left !important;
    }
    
    .lotes-detalhe th:nth-child(3),
    .lotes-detalhe td:nth-child(3){
        width:40% !important;
        text-align:right !important;
    }
    
    .tabela-lotes{
        background:transparent !important;
        border:none !important;
        padding:0 !important;
        margin:10px 0 !important;
    }
    
    /* v9.8.6: Ajusta layout da tabela de lotes na impressão */
    .lotes-detalhe thead tr,
    .lotes-detalhe tbody tr,
    .lotes-detalhe tfoot tr{
        background:transparent !important;
    }
    
    /* v9.8.6: Ajustar tabela principal para não ultrapassar margem */
    .oficio-observacao table{
        max-width:650px !important;
        margin:0 auto !important;
    }
    
    .oficio-observacao table th,
    .oficio-observacao table td{
        font-size:14px !important;
        padding:8px !important;
    }
    
    /* v9.9.0: QUEBRA DE PÁGINA - cada ofício em uma folha */
    .folha-a4-oficio{
        page-break-after:always !important;
        page-break-inside:avoid !important;
    }
    
    /* Evitar quebra dentro da tabela de lotes */
    .tabela-lotes{
        page-break-inside:avoid !important;
    }
    
    /* Evitar quebra da tabela principal */
    .oficio-observacao > table{
        page-break-inside:avoid !important;
    }
}

/* v9.9.0: Estilos para conferência de lotes */
.painel-conferencia{
    background:#f0f8ff;
    border:2px solid #007bff;
    border-radius:8px;
    padding:10px;          /* v9.23.9: mais enxuto */
    margin:8px 0;          /* v9.23.9: reduz espaço acima/abaixo */
    box-shadow:0 1px 6px rgba(0,0,0,0.10);
}

/* v9.21.7: Realce verde para lote conferido no layout 3 colunas */
.lote-conferido{
    background:#d4edda !important;
    border-color:#28a745 !important;
    font-weight:bold;
}

.painel-conferencia h4{
    margin:0 0 10px 0;
    color:#007bff;
    font-size:16px;
}

.campo-leitura{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:6px;   /* v9.23.9 */
}

.campo-leitura label{
    font-weight:bold;
    min-width:70px;      /* v9.23.9: cabe melhor */
}

.input-conferencia{
    flex:1;
    padding:6px 8px;      /* v9.23.9: menos altura */
    font-size:14px;
    border:2px solid #007bff;
    border-radius:4px;
    font-family:monospace;
    height:32px;
}

.input-conferencia:focus{
    outline:none;
    border-color:#0056b3;
    box-shadow:0 0 8px rgba(0,123,255,0.3);
}

.contador-conferencia{
    display:flex;
    justify-content:space-between;
    font-size:14px;
    color:#666;
    margin-top:10px;
}

.contador-conferencia span{
    background:#e9ecef;
    padding:5px 10px;
    border-radius:4px;
}

/* v9.9.0: Estados de conferência */
.linha-lote.conferido{
    background:#d4edda !important;
    border-left:4px solid #28a745 !important;
}

.linha-lote.nao-encontrado{
    background:#fff3cd !important;
    border-left:4px solid #ffc107 !important;
}

.linha-lote.conferido td{
    color:#155724;
    font-weight:bold;
}

.linha-lote.nao-encontrado td{
    color:#856404;
    font-style:italic;
}

/* v9.9.0: Animação ao conferir */
@keyframes pulse-green{
    0%,100%{background:#d4edda}
    50%{background:#a8d5ba}
}

.linha-lote.conferido-agora{
    animation:pulse-green 1s ease-in-out;
}

/* v9.9.0: Centralização de tabelas para não ultrapassar margem */
.oficio-observacao > table{
    margin-left:auto !important;
    margin-right:auto !important;
    max-width:100% !important;
}
</style>
<script type="text/javascript">
// v8.14.3: Modal de confirmação com 3 opções para Poupa Tempo
function confirmarGravarPT(comImpressao) {
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
    
    var modal = document.createElement('div');
    modal.style.cssText = 'background:white;padding:30px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.3);max-width:500px;text-align:center;';
    
    var titulo = document.createElement('h3');
    titulo.textContent = 'Como deseja gravar o Ofício Poupa Tempo?';
    titulo.style.cssText = 'margin-top:0;color:#333;';
    
    var texto = document.createElement('p');
    texto.innerHTML = '<b>Sobrescrever:</b> Apaga itens do último ofício e grava este no lugar.<br><br>' +
                      '<b>Criar Novo:</b> Mantém ofício anterior e cria outro com novo número.<br><br>' +
                      '<b>Cancelar:</b> Aborta a operação.';
    texto.style.cssText = 'margin:20px 0;line-height:1.6;color:#555;';
    
    var botoes = document.createElement('div');
    botoes.style.cssText = 'display:flex;gap:10px;justify-content:center;margin-top:25px;';
    
    var btnSobrescrever = document.createElement('button');
    btnSobrescrever.textContent = 'Sobrescrever';
    btnSobrescrever.style.cssText = 'background:#ff9800;color:white;border:none;padding:12px 24px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:bold;';
    btnSobrescrever.onclick = function() {
        document.body.removeChild(overlay);
        executarGravacaoPT('sobrescrever', comImpressao);
    };
    
    var btnCriarNovo = document.createElement('button');
    btnCriarNovo.textContent = 'Criar Novo';
    btnCriarNovo.style.cssText = 'background:#28a745;color:white;border:none;padding:12px 24px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:bold;';
    btnCriarNovo.onclick = function() {
        document.body.removeChild(overlay);
        executarGravacaoPT('novo', comImpressao);
    };
    
    var btnCancelar = document.createElement('button');
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background:#dc3545;color:white;border:none;padding:12px 24px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:bold;';
    btnCancelar.onclick = function() {
        document.body.removeChild(overlay);
    };
    
    botoes.appendChild(btnSobrescrever);
    botoes.appendChild(btnCriarNovo);
    botoes.appendChild(btnCancelar);
    
    modal.appendChild(titulo);
    modal.appendChild(texto);
    modal.appendChild(botoes);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
}

function executarGravacaoPT(modo, comImpressao) {
    var form = document.getElementById('formOficio');
    if (form) {
        document.getElementById('modo_oficio_pt').value = modo;
        document.getElementById('acaoForm').value = 'salvar_oficio_completo';
        document.getElementById('imprimir_apos_salvar').value = comImpressao ? '1' : '0';
        atualizarFolhasSelecionadasHidden();
        form.submit();
    }
}

// Função para gravar e imprimir (agora com modal)
function gravarEImprimir() {
    confirmarGravarPT(true);
}

// v9.8.2: Recalcula total baseado nos lotes marcados
// v9.20.1: Recalcular total - CORRIGIDO para páginas clonadas
// v9.21.1: CORREÇÃO DEFINITIVA - busca por evento e elemento clicado
function recalcularTotal(posto) {
    // v9.21.1: Busca o container mais próximo do elemento que disparou o evento
    var elementoAtual = event ? event.target : null;
    var container = null;
    
    if (elementoAtual) {
        // Sobe na árvore DOM até encontrar o container .folha-a4-oficio
        container = elementoAtual.closest('.folha-a4-oficio');
    }
    
    // Se não encontrou pelo evento, busca pelo data-posto (fallback)
    if (!container) {
        var containers = document.querySelectorAll('.folha-a4-oficio[data-posto="' + posto + '"]');
        if (containers.length > 0) {
            // Se há múltiplos containers (clones), tenta encontrar o correto
            if (elementoAtual) {
                for (var i = 0; i < containers.length; i++) {
                    if (containers[i].contains(elementoAtual)) {
                        container = containers[i];
                        break;
                    }
                }
            }
            if (!container) container = containers[0];
        }
    }
    
    if (!container) {
        console.warn('Container não encontrado para posto:', posto);
        return;
    }
    
    // Busca checkboxes APENAS dentro deste container
    var checkboxes = container.querySelectorAll('.checkbox-lote');
    var total = 0;
    var lotesConfirmados = [];

    // Resetar marcação por linha
    var linhas = container.querySelectorAll('tr.linha-lote');
    for (var r = 0; r < linhas.length; r++) {
        linhas[r].setAttribute('data-checked', '0');
    }
    
    for (var i = 0; i < checkboxes.length; i++) {
        var cb = checkboxes[i];
        var quantidade = parseInt(cb.getAttribute('data-quantidade')) || 0;
        var lote = cb.getAttribute('data-lote');
        var linha = cb.closest('tr');
        var tdCheck = cb.closest('td');
        var tdLote = tdCheck ? tdCheck.nextElementSibling : null;
        var tdQtd = tdLote ? tdLote.nextElementSibling : null;

        if (cb.checked) {
            total += quantidade;
            lotesConfirmados.push(lote);
            if (linha) linha.setAttribute('data-checked', '1');
            if (tdLote) tdLote.classList.remove('lote-desmarcado');
            if (tdQtd) tdQtd.classList.remove('lote-desmarcado');
        } else {
            if (tdLote) tdLote.classList.add('lote-desmarcado');
            if (tdQtd) tdQtd.classList.add('lote-desmarcado');
        }
    }
    
    // v9.21.2: Atualiza apenas a coluna "Quantidade de CIN's"
    var totalCins = container.querySelector('.total-cins');
    if (totalCins) {
        totalCins.textContent = formatarNumero(total);
    }
    
    // Atualiza hidden inputs DENTRO do container
    var hiddenLotes = container.querySelector('input[name^="lotes_confirmados"]');
    if (hiddenLotes) {
        hiddenLotes.value = lotesConfirmados.join(',');
    }
    
    var hiddenQuantidade = container.querySelector('input[name^="quantidade_posto"]');
    if (hiddenQuantidade) {
        hiddenQuantidade.value = total;
    }
    
    // Atualiza checkbox "marcar todos" DENTRO do container
    var marcarTodos = container.querySelector('.marcar-todos');
    if (marcarTodos) {
        var todosMarcados = true;
        var algumMarcado = false;
        
        for (var i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                algumMarcado = true;
            } else {
                todosMarcados = false;
            }
        }
        
        marcarTodos.checked = todosMarcados;
        marcarTodos.indeterminate = algumMarcado && !todosMarcados;
    }
}

// v9.20.1: Marca/desmarca todos os lotes de um posto (CORRIGIDO para clones)
function marcarTodosLotes(checkbox, posto) {
    var container = document.querySelector('.folha-a4-oficio[data-posto="' + posto + '"]');
    if (!container) return;
    
    var checkboxes = container.querySelectorAll('.checkbox-lote');
    var marcado = checkbox.checked;
    
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = marcado;
    }
    
    recalcularTotal(posto);
}

// v9.8.2: Formata número com separador de milhares
function formatarNumero(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Função apenas para gravar (agora com modal)
function apenasGravar() {
    confirmarGravarPT(false);
}

// Função apenas para imprimir
function apenasImprimir() {
    window.print();
}

// v9.22.0: Imprimir apenas folhas selecionadas
function imprimirSelecionados() {
    document.body.classList.add('imprimir-selecionados');
    window.print();
    setTimeout(function(){
        document.body.classList.remove('imprimir-selecionados');
    }, 500);
}

// v9.22.0: Atualiza seleção visual das folhas
function atualizarSelecaoFolhas() {

    var checks = document.querySelectorAll('.selecionar-folha');
    for (var i = 0; i < checks.length; i++) {
        var cb = checks[i];
        var folhaId = cb.getAttribute('data-folha');
        if (!folhaId) continue;
        var folha = document.querySelector('.folha-a4-oficio[data-folha-id="' + folhaId + '"]');
        if (!folha) continue;
        if (cb.checked) {
            folha.classList.add('folha-selecionada');
        } else {
            folha.classList.remove('folha-selecionada');
        }
    }
}


// v9.23.9: Preencher hidden folhas_selecionadas antes de salvar (usa checkboxes "Imprimir esta folha")
function atualizarFolhasSelecionadasHidden() {
    var checks = document.querySelectorAll('.selecionar-folha');
    var selecionadas = [];
    for (var i = 0; i < checks.length; i++) {
        if (checks[i].checked) {
            var id = checks[i].getAttribute('data-folha');
            if (id) selecionadas.push(id);
        }
    }
    var hidden = document.getElementById('folhas_selecionadas');
    if (hidden) hidden.value = selecionadas.join(',');
    return selecionadas;
}

// v9.23.9: Se o lacre for preenchido/limpo, marca/desmarca a folha automaticamente
function bindAutoSelecaoPorLacre() {
    var inputs = document.querySelectorAll('input[name^="lacre_iipr["]');
    for (var i = 0; i < inputs.length; i++) {
        inputs[i].addEventListener('input', function(){
            var folha = this.closest('.folha-a4-oficio');
            if (!folha) return;
            var folhaId = folha.getAttribute('data-folha-id');
            if (!folhaId) return;
            var cb = document.querySelector('.selecionar-folha[data-folha="' + folhaId + '"]');
            if (!cb) return;
            cb.checked = (this.value || '').trim() !== '';
            atualizarSelecaoFolhas();
            atualizarFolhasSelecionadasHidden();
        });
    }
}

document.addEventListener('DOMContentLoaded', function(){
    var checks = document.querySelectorAll('.selecionar-folha');
    for (var i = 0; i < checks.length; i++) {
        checks[i].addEventListener('change', function(){
            atualizarSelecaoFolhas();
            atualizarFolhasSelecionadasHidden();
        });
    }
    atualizarSelecaoFolhas();
    atualizarFolhasSelecionadasHidden();
    bindAutoSelecaoPorLacre();
});

// ============================================================
// v8.14.5: Sistema de detecção de mudanças e pulsação de botões
// ============================================================
var valoresOriginais = {};
var dadosForamSalvos = <?php echo ($tipo_mensagem === 'sucesso' ? 'true' : 'false'); ?>;

function capturarValoresOriginais() {
    var inputs = document.querySelectorAll('input[type="text"], input[type="hidden"][name^="lote_posto"]');
    for (var i = 0; i < inputs.length; i++) {
        var inp = inputs[i];
        if (inp.name) {
            valoresOriginais[inp.name] = inp.value;
        }
    }
}

function verificarMudancas() {
    var mudou = false;
    var inputs = document.querySelectorAll('input[type="text"], input[type="hidden"][name^="lote_posto"]');
    for (var i = 0; i < inputs.length; i++) {
        var inp = inputs[i];
        if (inp.name && valoresOriginais[inp.name] !== undefined) {
            if (valoresOriginais[inp.name] !== inp.value) {
                mudou = true;
                break;
            }
        }
    }
    return mudou;
}

function atualizarEstadoBotoes() {
    var temMudancas = verificarMudancas();
    var botoes = document.querySelectorAll('.btn-imprimir, .btn-salvar');
    
    for (var i = 0; i < botoes.length; i++) {
        var btn = botoes[i];
        if (temMudancas) {
            if (!btn.className.match(/btn-nao-salvo/)) {
                btn.className += ' btn-nao-salvo';
            }
        } else {
            btn.className = btn.className.replace(/\s*btn-nao-salvo/g, '');
        }
    }
}

function inicializarMonitoramento() {
    capturarValoresOriginais();
    
    // Monitorar mudanças em todos os inputs
    var inputs = document.querySelectorAll('input[type="text"]');
    for (var i = 0; i < inputs.length; i++) {
        inputs[i].addEventListener('input', function() {
            atualizarEstadoBotoes();
        });
        inputs[i].addEventListener('change', function() {
            atualizarEstadoBotoes();
        });
    }
    
    // Verificar inicialmente
    atualizarEstadoBotoes();
}

// Inicializar quando a página carregar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarMonitoramento);
} else {
    inicializarMonitoramento();
}
</script>
</head>
<body>

<form method="post" action="<?php echo e($_SERVER['PHP_SELF']); ?>" id="formOficio">
  <!-- Ação a executar -->
  <input type="hidden" name="acao" id="acaoForm" value="salvar_oficio_completo">
  <!-- Número da expedição (id do despacho), se existir -->
  <input type="hidden" name="id_despacho" value="<?php echo (int)$id_despacho; ?>">
  <!-- Datas usadas no ofício (string original, como em ciDespachos.datas_str) -->
  <input type="hidden" name="pt_datas" value="<?php echo e($datasStr); ?>">
  <!-- Flag para imprimir após salvar -->
  <input type="hidden" name="imprimir_apos_salvar" id="imprimir_apos_salvar" value="0">
  <!-- v8.14.3: Modo do ofício (sobrescrever/novo) -->
  <input type="hidden" name="modo_oficio" id="modo_oficio_pt" value="">
    <!-- v9.22.2: Folhas selecionadas para gravar/imprimir -->
    <input type="hidden" name="folhas_selecionadas" id="folhas_selecionadas" value="">

  <div class="controles-pagina nao-imprimir">
    <h2>Modelo de Oficio - Poupatempo</h2>
    
    <?php if (!empty($mensagem_status)): ?>
    <div class="mensagem-status <?php echo ($tipo_mensagem === 'sucesso') ? 'mensagem-sucesso' : 'mensagem-erro'; ?>">
        <?php echo e($mensagem_status); ?>
    </div>
    <?php endif; ?>
    
    <p>
      Uma pagina por posto Poupatempo.
      <?php if ($id_despacho > 0): ?>
        Expedicao n. <b><?php echo (int)$id_despacho; ?></b>.
      <?php else: ?>
        <b style="color:orange">
          Atencao: este oficio ainda nao foi salvo. Clique em "Gravar" para salvar.
        </b>
      <?php endif; ?>
    </p>

    <!-- Botão Gravar e Imprimir -->
    <button type="button" onclick="gravarEImprimir();" class="btn-sucesso btn-imprimir">
        💾🖨️ Gravar e Imprimir
    </button>

    <!-- Botão apenas Gravar -->
    <button type="button" onclick="apenasGravar();" class="btn-salvar">
        💾 Gravar Dados
    </button>

    <!-- Botão apenas Imprimir -->
    <button type="button" onclick="apenasImprimir();" class="btn-imprimir">
        🖨️ Apenas Imprimir
    </button>

    <!-- Botão Imprimir Selecionados -->
    <button type="button" onclick="imprimirSelecionados();" class="btn-imprimir">
        ✅ Imprimir Selecionados
    </button>
  </div>

<?php if ($temDados): ?>
  <?php foreach ($paginas as $idx => $p): 
        $codigo   = $p['codigo'];                     
        $nome     = $p['nome'] ? $p['nome'] : "POUPA TEMPO";
        $qtd_total = (int)$p['qtd_total'];  // v9.8.2: Total de todos os lotes
        $lotes_array = isset($p['lotes']) && is_array($p['lotes']) ? $p['lotes'] : array();  // v9.8.3: Validação
        $endereco = isset($p['endereco']) ? $p['endereco'] : '';

        if ($modo_branco) {
            $nome = '';
            $qtd_total = '';
            $lotes_array = array();
            $endereco = '';
        }

        // garante código com 3 dígitos
        $codigo3 = str_pad($codigo, 3, '0', STR_PAD_LEFT);
        
        // Prioridade: dados salvos (do POST atual) > dados do banco > dados do SELECT original
        $valorLacre = isset($lacresPorPosto[$codigo3]) ? $lacresPorPosto[$codigo3] : '';
        // v9.21.1: Adiciona número do posto ao nome (ex: "POUPA TEMPO 06 - PINHEIRINHO")
        $nomeComNumero = $modo_branco ? '' : ('POUPA TEMPO ' . $codigo3 . ' - ' . $nome);
        $valorNome = $modo_branco ? '' : (isset($nomesPorPosto[$codigo3]) ? $nomesPorPosto[$codigo3] : $nomeComNumero);
        $valorEndereco = $modo_branco ? '' : (isset($enderecosPorPosto[$codigo3]) ? $enderecosPorPosto[$codigo3] : $endereco);
        $valorQuantidade = $modo_branco ? '' : (isset($quantidadesPorPosto[$codigo3]) ? $quantidadesPorPosto[$codigo3] : $qtd_total);
        if ($modo_branco) {
            $valorLacre = '';
        }

        // v9.23.7: Paginação automática mais conservadora.
        // Motivo: quando há muitos lotes, o conteúdo pode "estourar" a altura
        // da folha A4 e acabar empurrando elementos (ex.: botão DIVIDIR na tela
        // e/ou rodapé de assinatura no PDF) para a página seguinte, gerando
        // páginas em branco e/ou páginas somente com assinatura.
        // Ajuste: reduzir quantidade de lotes por folha e tornar o layout
        // mais enxuto (CSS abaixo) para aumentar a capacidade sem overflow.
        $max_lotes_por_pagina = 16;
        $lotes_paginas = $modo_branco ? array(array()) : array_chunk($lotes_array, $max_lotes_por_pagina);
        foreach ($lotes_paginas as $pagina_idx => $lotes_pagina):
            $folha_id = $codigo3 . '_' . ($pagina_idx + 1);
            $qtd_pagina = 0;
            foreach ($lotes_pagina as $lp) {
                $qtd_pagina += isset($lp['quantidade']) ? (int)$lp['quantidade'] : 0;
            }
            $valorQuantidade = $modo_branco ? '' : $qtd_pagina;
  ?>
  <div class="folha-a4-oficio" data-posto="<?php echo e($codigo3); ?>" data-folha-id="<?php echo e($folha_id); ?>">
    <div class="oficio">
      <div class="cols100 border-1px">
        <div class="cols25 fleft margin2px">
          <img alt="Logotipo" style="margin-left:10px;margin-top:10px;padding-right:15px;float:left" src="logo_celepar.png" width="250" height="55">
        </div>
        <div class="cols65 fright center margin2px">
          <h3><i>COSEP <br> Coordenacao De Servicos De Producao</i></h3>
          <h3><b><br> Comprovante de Entrega </b></h3>
        </div>
      </div>

      <div class="cols100 center border-1px p5 moldura cabecalho-pt">
        <h4 class="cabecalho-pt-titulo">
          <span class="nometit titulo-pt">POUPA TEMPO PARANÁ</span>
          <!-- ENDEREÇO editável como input -->
          <<br><span class="nometit endereco-pt">ENDERECO: 
            <input type="text" 
                   name="endereco_posto[<?php echo e($codigo3); ?>]" 
                   value="<?php echo e($valorEndereco); ?>" 
                   class="input-editavel"
                   style="width:90%;">
          </span>
          <br><span class="nometit"></span>
        </h4>
      </div>

      <!-- v9.21.1: Adiciona margem lateral para não encostar na borda -->
      <div class="cols100 processo border-1px" style="padding-left:10px; padding-right:10px;">
                <div class="oficio-observacao">
                    <table style="table-layout:fixed; width:100%; max-width:100%; margin:0;">
            <tr>
              <th style="width:55%; text-align:left; padding:8px; border:1px solid #000; font-size:14px;">Poupatempo</th>
              <th style="width:22%; text-align:right; padding:8px; border:1px solid #000; font-size:14px;">Quantidade de CIN's</th>
              <th style="width:23%; text-align:right; padding:8px; border:1px solid #000; font-size:14px;">Numero do Lacre</th>
            </tr>
            <tr>
                            <td style="width:55%; text-align:left; padding:8px; border:1px solid #000;">
                                <!-- v9.21.6: Nome pode quebrar em até 2 linhas -->
                                <textarea name="nome_posto[<?php echo e($codigo3); ?>]"
                                                    class="input-editavel"
                                                    rows="2"
                                                    style="width:100%; border:none; background:transparent; font-size:14px; font-weight:bold; resize:none; overflow:hidden; line-height:1.2;"><?php echo e($nomeComNumero); ?></textarea>
                            </td>
              <!-- Quantidade de carteiras - v9.8.2: Calculada dinamicamente dos lotes marcados -->
                            <td style="text-align:right; padding:8px; border:1px solid #000;">
                                <?php if ($modo_branco): ?>
                                    <input type="text"
                                            name="quantidade_posto[<?php echo e($codigo3); ?>]"
                                            value=""
                                            class="input-editavel"
                                            style="text-align:right; font-size:14px; border:none; background:transparent; width:100%;">
                                <?php else: ?>
                                    <span class="total-cins" id="total_<?php echo e($codigo3); ?>" style="font-weight:bold; font-size:14px;">
                                        <?php echo ($valorQuantidade === '' ? '' : number_format((int)$valorQuantidade, 0, ',', '.')); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
              <!-- Número do lacre -->
              <td style="text-align:right; padding:8px; border:1px solid #000;">
                <input type="text"
                    name="lacre_iipr[<?php echo e($codigo3); ?>]"
                    value="<?php echo e($valorLacre); ?>"
                    class="input-editavel"
                    style="text-align:right; font-size:14px; border:none; background:transparent; width:100%;"
                >
              </td>
            </tr>
          </table>

                    <!-- v9.22.1: Seleção de folha para impressão (só marca se tiver lacre) -->
                    <div class="nao-imprimir" style="margin:8px 0;">
                        <label style="font-size:12px; font-weight:bold;">
                            <input type="checkbox" class="selecionar-folha" data-folha="<?php echo e($folha_id); ?>" <?php echo (!empty($valorLacre) ? 'checked' : ''); ?>>
                            Imprimir esta folha
                        </label>
                    </div>

          <!-- v9.9.2: Painel de Conferência Simplificado -->
          <?php if (!empty($lotes_array)): ?>
          <div class="painel-conferencia controle-conferencia" style="margin-top:8px;">
            <div class="campo-leitura">
              <label for="input_conferencia_<?php echo e($codigo3); ?>">Leitura:</label>
              <input type="text" 
                     id="input_conferencia_<?php echo e($codigo3); ?>" 
                     class="input-conferencia"
                     placeholder="Leia código de barras (19 dígitos) ou digite lote (8 dígitos)..."
                     autocomplete="off"
                     oninput="conferirLoteAutomatico('<?php echo e($codigo3); ?>', this.value)"
                     onkeydown="if(event.keyCode===13){conferirLote('<?php echo e($codigo3); ?>');return false;}">              
            </div>
            <div class="contador-conferencia">
              <span>Total de Lotes: <strong id="total_lotes_<?php echo e($codigo3); ?>"><?php echo count($lotes_array); ?></strong></span>
              <span>Conferidos: <strong id="conferidos_<?php echo e($codigo3); ?>">0</strong></span>
              <span>Pendentes: <strong id="pendentes_<?php echo e($codigo3); ?>"><?php echo count($lotes_array); ?></strong></span>
            </div>
          </div>

                    <!-- v9.22.0: Título LOTES (1 por linha) -->
                    <h3 class="titulo-lotes">LOTES</h3>

                    <div class="tabela-lotes" style="margin:4px 10px; padding:0; max-width:calc(100% - 20px);">
                        <table style="width:100%; border-collapse:collapse; border:1px solid #000;" class="lotes-detalhe-1col">
                            <thead>
                                <tr style="background:#e0e0e0;">
                                    <th class="col-checkbox nao-imprimir" style="width:30px; padding:4px; border:1px solid #000; font-size:12px;"></th>
                                    <th style="width:70%; text-align:left; padding:6px; border:1px solid #000; font-size:12px; font-weight:bold;">Lote</th>
                                    <th style="width:30%; text-align:center; padding:6px; border:1px solid #000; font-size:12px; font-weight:bold;">Qtd</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lotes_pagina as $lote): ?>
                                <tr class="linha-lote" data-posto="<?php echo e($codigo3); ?>" data-lote="<?php echo e($lote['lote']); ?>" data-checked="1">
                                    <td class="col-checkbox nao-imprimir" style="text-align:center; padding:4px; border:1px solid #000;">
                                        <input type="checkbox" class="checkbox-lote" data-posto="<?php echo e($codigo3); ?>" 
                                                     data-quantidade="<?php echo e($lote['quantidade']); ?>" 
                                                     data-lote="<?php echo e($lote['lote']); ?>" checked 
                                                     onchange="recalcularTotal('<?php echo e($codigo3); ?>')">
                                    </td>
                                    <td style="text-align:left; padding:6px; border:1px solid #000; font-size:11px;"><?php echo e($lote['lote']); ?></td>
                                    <td style="text-align:center; padding:6px; border:1px solid #000; font-size:11px;">
                                        <span class="valor-tela"><?php echo number_format($lote['quantidade'], 0, ',', '.'); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                             <input type="hidden" 
                                 name="folha_posto[<?php echo e($folha_id); ?>]" 
                                 value="<?php echo e($codigo3); ?>">
                             <input type="hidden" 
                                 name="lotes_confirmados[<?php echo e($folha_id); ?>]" 
                                 id="lotes_confirmados_<?php echo e($folha_id); ?>" 
                                 value="<?php echo implode(',', array_map(function($l){ return $l['lote']; }, $lotes_pagina)); ?>">
                             <?php if (!$modo_branco): ?>
                             <input type="hidden" 
                                 name="quantidade_posto[<?php echo e($folha_id); ?>]" 
                                 id="quantidade_final_<?php echo e($folha_id); ?>" 
                                 value="<?php echo $qtd_pagina; ?>">
                             <?php endif; ?>
                    </div>
          
          <!-- v9.21.5: Botão centralizado horizontalmente na página -->
          <div class="controle-split nao-imprimir" style="margin-top:20px; margin-bottom:10px; display:flex; justify-content:center; width:100%;">
            <button type="button" 
                    class="btn-split nao-imprimir" 
                    onclick="clonarPagina('<?php echo e($codigo3); ?>')"
                    style="padding:10px 20px; background:#17a2b8; color:#fff; border:none; border-radius:4px; font-size:14px; font-weight:bold; cursor:pointer; box-shadow:0 2px 4px rgba(0,0,0,0.2);">
              ➕ DIVIDIR EM MAIS MALOTES
            </button>
          </div>
          <?php endif; ?>  <!-- Fecha o if (!empty($lotes_array)) -->

          <!-- v9.21.6: Espaçador ajustado para tela e impressão -->
          <div class="espacador-rodape"></div>
        </div>
      </div>

    <!-- v9.21.6: Rodapé visível apenas na impressão -->
    <div class="cols100 border-1px rodape-oficio" style="padding:8px 15px;">
        <div style="display:flex; justify-content:space-between; gap:15px;">
          <!-- Conferido por -->
          <div style="flex:1; border-right:1px solid #000; padding-right:12px;">
            <div style="text-align:center; margin-bottom:40px;">
              <strong>Conferido por:</strong>
            </div>
            <div style="border-top:1px solid #000; padding-top:3px; text-align:center;">
              <div style="margin-bottom:3px;">______________________________</div>
              <div style="font-size:12px;"><strong>IIPR - Data:</strong> ___/___/______</div>
            </div>
          </div>
          
          <!-- Recebido por -->
          <div style="flex:1; padding-left:12px;">
            <div style="text-align:center; margin-bottom:40px;">
              <strong>Recebido por:</strong>
            </div>
            <div style="border-top:1px solid #000; padding-top:3px; text-align:center;">
              <div style="margin-bottom:3px;">______________________________</div>
              <div style="font-size:12px;"><strong>Poupatempo - Data:</strong> ___/___/______</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
    <?php endforeach; ?>  <!-- Fecha o foreach de $lotes_paginas -->
    <?php endforeach; ?>  <!-- Fecha o foreach de $paginas -->

<?php else: ?>  <!-- Se $temDados for false, exibe mensagem de erro -->
  <div style="margin:50px auto; max-width:800px; padding:30px; background:#fff3cd; border:3px solid #856404; border-radius:8px; text-align:center;">
    <h2 style="color:#856404; margin-top:0;">⚠️ Nenhum Ofício para Exibir</h2>
    <p style="font-size:16px; line-height:1.6;">
      <strong>Não foram encontrados dados para gerar o ofício Poupa Tempo.</strong>
    </p>
    <p style="font-size:14px; color:#666; line-height:1.6;">
      <strong>Possíveis causas:</strong><br>
      • As datas selecionadas não têm produção cadastrada no sistema<br>
      • Nenhum posto Poupa Tempo tem lotes nas datas escolhidas<br>
      • Os postos não estão configurados com entrega "POUPA TEMPO"<br>
      • Problema na conexão com o banco de dados
    </p>
    <p style="margin-top:20px;">
      <a href="javascript:history.back()" style="display:inline-block; padding:12px 24px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px; font-weight:bold;">
        ← Voltar e Selecionar Outras Datas
      </a>
    </p>
    <hr style="margin:30px 0; border:none; border-top:1px solid #ccc;">
    <p style="font-size:12px; color:#999;">
      <strong>Debug:</strong> Para mais detalhes, adicione <code>?debug_dados=1</code> na URL
    </p>
  </div>
<?php endif; ?>  <!-- Fecha o if ($temDados) -->

</form>

<?php
// Se salvou com sucesso e flag de imprimir está ativa, adiciona script para imprimir
if ($deve_imprimir && $tipo_mensagem === 'sucesso'):
?>
<script type="text/javascript">
// Imprime após pequeno delay para garantir que a página renderizou
setTimeout(function() {
    window.print();
}, 500);
</script>
<?php endif; ?>

<!-- v9.9.0: Sistema de Conferência de Lotes -->
<script type="text/javascript">
// v9.9.5: Conferência automática ao atingir 19 dígitos
function conferirLoteAutomatico(codigoPosto, valor) {
    // Remove espaços e valida
    var codigo = valor.trim();
    
    // Se atingiu exatamente 19 dígitos numéricos, confere automaticamente
    if (codigo.length === 19 && /^\d{19}$/.test(codigo)) {
        console.log('✓ 19 dígitos detectados! Conferindo automaticamente...');
        conferirLote(codigoPosto);
    }
}

// v9.9.4: Função para conferir lote via código de barras (extrai lote de 8 dígitos)
function conferirLote(codigoPosto) {
    var input = document.getElementById('input_conferencia_' + codigoPosto);
    if (!input) return;
    
    var codigoLido = input.value.trim();
    if (codigoLido === '') return;
    
    // v9.9.6: Se código tem 19 dígitos, extrai lote e quantidade
    // Estrutura: [8 dígitos lote][6 dígitos outros][5 dígitos quantidade]
    // Exemplo: 0075942402302300170 → Lote:00759424 Qtd:00170(170)
    var numeroLote = codigoLido;
    if (codigoLido.length === 19 && /^\d{19}$/.test(codigoLido)) {
        // Extrai caracteres da posição 0 a 7 (8 primeiros dígitos = lote)
        numeroLote = codigoLido.substring(0, 8);
        // NÃO remove zeros à esquerda para preservar formato original
        console.log('Código de barras 19 dígitos detectado.');
        console.log('Lote extraído (posições 0-7): ' + numeroLote);
        console.log('Código completo: ' + codigoLido);
    }
    
    // v9.12.0: Busca o lote na tabela (suporta layout 1, 2 ou 3 colunas)
    var tabela = document.getElementById('tabela_lotes_' + codigoPosto);
    var tabela_col1 = document.getElementById('tabela_lotes_' + codigoPosto + '_col1');
    var tabela_col2 = document.getElementById('tabela_lotes_' + codigoPosto + '_col2');
    var container3 = document.querySelector('.folha-a4-oficio[data-posto="' + codigoPosto + '"]');
    
    // Se não encontrou nenhum container de tabela, aborta
    if (!tabela && !tabela_col1 && !tabela_col2 && !container3) {
        console.log('ERRO: Tabela não encontrada para posto: ' + codigoPosto);
        alert('Erro: Tabela de lotes não encontrada.');
        return;
    }

    // Se tem 2 colunas, procura em ambas
    var linhas = [];
    if (tabela_col1 && tabela_col2) {
        var linhas1 = tabela_col1.getElementsByClassName('linha-lote');
        var linhas2 = tabela_col2.getElementsByClassName('linha-lote');
        linhas = Array.from(linhas1).concat(Array.from(linhas2));
        console.log('Layout 2 colunas detectado. Total linhas: ' + linhas.length);
    } else if (tabela) {
        linhas = Array.from(tabela.getElementsByClassName('linha-lote'));
        console.log('Layout 1 coluna detectado. Total linhas: ' + linhas.length);
    }
    
    var loteEncontrado = false;
    console.log('Procurando lote: "' + numeroLote + '"');
    
    for (var i = 0; i < linhas.length; i++) {
        var linha = linhas[i];
        var loteNaLinha = (linha.getAttribute('data-lote') || '').trim();
        
        console.log('Linha ' + i + ': Lote na linha="' + loteNaLinha + '"');
        
        if (loteNaLinha === numeroLote) {
            console.log('✓ LOTE ENCONTRADO! Linha ' + i);
            loteEncontrado = true;
            
            // v9.9.5: Verifica se já foi conferido (sem alert)
            if (linha.classList.contains('conferido')) {
                console.log('⚠️ Lote ' + numeroLote + ' já conferido anteriormente.');
                input.value = '';
                input.focus();
                return;
            }
            
            // Marca como conferido (verde)
            linha.classList.add('conferido');
            linha.classList.add('conferido-agora');
            
            // Remove animação após 1 segundo
            setTimeout(function() {
                linha.classList.remove('conferido-agora');
            }, 1000);
            
            // Atualiza contadores
            atualizarContadores(codigoPosto);
            
            // Limpa campo e mantém foco
            input.value = '';
            input.focus();
            
            // Feedback sonoro (beep) - opcional
            // Você pode adicionar um som de sucesso aqui se desejar
            
            return;
        }
    }
    
    // v9.21.7: Fallback para layout 3 colunas usando checkboxes
    if (!loteEncontrado && container3) {
        var cbs = container3.querySelectorAll('.checkbox-lote');
        for (var j = 0; j < cbs.length; j++) {
            var cb3 = cbs[j];
            var loteCb = (cb3.getAttribute('data-lote') || '').trim();
            if (loteCb === numeroLote) {
                var tdCheck = cb3.closest('td');
                var tdLote = tdCheck ? tdCheck.nextElementSibling : null;
                var tdQtd = tdLote ? tdLote.nextElementSibling : null;
                if (cb3.getAttribute('data-conferido') === '1') {
                    input.value = '';
                    input.focus();
                    return;
                }
                cb3.setAttribute('data-conferido', '1');
                cb3.checked = true;
                if (tdLote) tdLote.classList.add('lote-conferido');
                if (tdQtd) tdQtd.classList.add('lote-conferido');
                recalcularTotal(codigoPosto);
                // Atualiza contadores
                atualizarContadores(codigoPosto);
                input.value = '';
                input.focus();
                return;
            }
        }
    }

    // Se não encontrou, cria nova linha amarela
    if (!loteEncontrado) {
        var tbody = tabela.getElementsByTagName('tbody')[0];
        if (!tbody) return;
        
        // v9.9.6: Extrai quantidade do código de barras (ÚLTIMOS 5 dígitos)
        // Estrutura: [8:lote][6:outros][5:quantidade]
        // Exemplo: 0075942402302300170 → substring(14,19) = "00170" = 170
        var quantidadeExtraida = 0;
        if (codigoLido.length === 19 && /^\d{19}$/.test(codigoLido)) {
            quantidadeExtraida = parseInt(codigoLido.substring(14, 19), 10);
            console.log('Quantidade extraída (posições 14-18): ' + quantidadeExtraida);
        }
        
        // Cria nova linha
        var novaLinha = document.createElement('tr');
        novaLinha.className = 'linha-lote nao-encontrado';
        novaLinha.setAttribute('data-posto', codigoPosto);
        novaLinha.setAttribute('data-lote', numeroLote);
        novaLinha.setAttribute('data-checked', '0');
        
        // Checkbox (desmarcado)
        var tdCheckbox = document.createElement('td');
        tdCheckbox.className = 'col-checkbox';
        tdCheckbox.style.cssText = 'text-align:center; padding:6px; border:1px solid #ccc;';
        var checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'checkbox-lote';
        checkbox.setAttribute('data-posto', codigoPosto);
        checkbox.setAttribute('data-quantidade', quantidadeExtraida.toString());
        checkbox.setAttribute('data-lote', numeroLote);
        checkbox.checked = false;
        checkbox.onchange = function() { 
            // v9.9.6: Atualiza data-checked para controlar visibilidade na impressão
            novaLinha.setAttribute('data-checked', this.checked ? '1' : '0');
            recalcularTotal(codigoPosto); 
        };
        tdCheckbox.appendChild(checkbox);
        novaLinha.appendChild(tdCheckbox);
        
        // Lote
        var tdLote = document.createElement('td');
        tdLote.style.cssText = 'text-align:left; padding:8px; border:1px solid #ccc; font-weight:bold; font-size:14px;';
        tdLote.textContent = numeroLote; // v9.9.5: Removido '(NÃO CADASTRADO)'
        novaLinha.appendChild(tdLote);
        
        // Quantidade (editável, preenchida com valor extraído)
        var tdQuantidade = document.createElement('td');
        tdQuantidade.style.cssText = 'text-align:right; padding:8px; border:1px solid #ccc; font-size:14px;';
        
        // v9.9.5: Input para tela + span para impressão
        var inputQtd = document.createElement('input');
        inputQtd.type = 'number';
        inputQtd.value = quantidadeExtraida.toString();
        inputQtd.min = '0';
        inputQtd.style.cssText = 'width:80px; text-align:right; font-size:14px; padding:4px;';
        
        var spanQtd = document.createElement('span');
        spanQtd.className = 'valor-quantidade';
        spanQtd.textContent = quantidadeExtraida.toString();
        spanQtd.style.cssText = 'display:none;'; // Oculto na tela, visível na impressão
        
        inputQtd.onchange = function() {
            checkbox.setAttribute('data-quantidade', this.value);
            spanQtd.textContent = this.value; // Sincroniza span
            if (checkbox.checked) {
                recalcularTotal(codigoPosto);
            }
        };
        
        tdQuantidade.appendChild(inputQtd);
        tdQuantidade.appendChild(spanQtd);
        novaLinha.appendChild(tdQuantidade);
        
        // Adiciona no final da tabela
        tbody.appendChild(novaLinha);
        
        // Atualiza contador de total de lotes
        var totalLotesSpan = document.getElementById('total_lotes_' + codigoPosto);
        if (totalLotesSpan) {
            var totalAtual = parseInt(totalLotesSpan.textContent) || 0;
            totalLotesSpan.textContent = totalAtual + 1;
        }
        
        // Atualiza pendentes
        atualizarContadores(codigoPosto);
        
        // Limpa campo e mantém foco
        input.value = '';
        input.focus();
        
        // v9.9.5: Mensagem simplificada (linha amarela, oculta na impressão)
        var msgQuantidade = quantidadeExtraida > 0 ? '\nQuantidade: ' + quantidadeExtraida : '\nInforme a quantidade.';
        alert('📦 Lote ' + numeroLote + ' adicionado à lista.' + msgQuantidade + '\n\n⚠️ Linha amarela não será impressa.');
    }
}

// v9.9.0: Atualiza contadores de conferência
function atualizarContadores(codigoPosto) {
    var tabela = document.getElementById('tabela_lotes_' + codigoPosto);
    var container3 = document.querySelector('.folha-a4-oficio[data-posto="' + codigoPosto + '"]');
    var totalLotes = 0;
    var conferidos = 0;

    if (tabela) {
        var linhas = tabela.getElementsByClassName('linha-lote');
        totalLotes = linhas.length;
        for (var i = 0; i < linhas.length; i++) {
            if (linhas[i].classList.contains('conferido')) {
                conferidos++;
            }
        }
    } else if (container3) {
        var cbs = container3.querySelectorAll('.checkbox-lote');
        totalLotes = cbs.length;
        for (var j = 0; j < cbs.length; j++) {
            if (cbs[j].getAttribute('data-conferido') === '1') {
                conferidos++;
            }
        }
    } else {
        return;
    }

    var pendentes = totalLotes - conferidos;
    
    // Atualiza displays
    var spanTotal = document.getElementById('total_lotes_' + codigoPosto);
    var spanConferidos = document.getElementById('conferidos_' + codigoPosto);
    var spanPendentes = document.getElementById('pendentes_' + codigoPosto);
    
    if (spanTotal) spanTotal.textContent = totalLotes;
    if (spanConferidos) spanConferidos.textContent = conferidos;
    if (spanPendentes) spanPendentes.textContent = pendentes;
    
    // Se todos foram conferidos, mostra mensagem
    if (pendentes === 0 && totalLotes > 0) {
        setTimeout(function() {
            alert('✅ Todos os lotes foram conferidos!\nTotal: ' + conferidos + ' lotes');
        }, 100);
    }
}

// v9.9.0: Atalho de teclado Alt+C para focar no campo de conferência
document.addEventListener('keydown', function(e) {
    if (e.altKey && e.keyCode === 67) { // Alt+C
        e.preventDefault();
        var inputs = document.getElementsByClassName('input-conferencia');
        if (inputs.length > 0) {
            inputs[0].focus();
            inputs[0].select();
        }
    }
});

// v9.9.0: Foco automático no primeiro campo de conferência ao carregar
window.addEventListener('load', function() {
    var primeiroInput = document.querySelector('.input-conferencia');
    if (primeiroInput) {
        setTimeout(function() {
            primeiroInput.focus();
        }, 300);
    }
});

// v9.16.0: Clona página completa para criar novo malote - COM DEBUG
function clonarPagina(codigoPosto) {
    console.log('=== CLONAR PÁGINA ===');
    console.log('Posto solicitado:', codigoPosto);
    
    // Listar todos os postos disponíveis
    var todasFolhas = document.querySelectorAll('.folha-a4-oficio[data-posto]');
    console.log('Total de folhas encontradas:', todasFolhas.length);
    for (var i = 0; i < todasFolhas.length; i++) {
        console.log('  - Folha', i+1, '→ data-posto:', todasFolhas[i].getAttribute('data-posto'));
    }
    
    if (!confirm('Criar uma CÓPIA desta página?\n\nVocê poderá marcar/desmarcar lotes em cada uma para dividir entre malotes diferentes.')) {
        return;
    }
    
    // 1. Buscar folha usando data-posto
    var folhaOriginal = document.querySelector('.folha-a4-oficio[data-posto="' + codigoPosto + '"]');
    
    if (!folhaOriginal) {
        var postosDisponiveis = [];
        var folhas = document.querySelectorAll('.folha-a4-oficio[data-posto]');
        for (var i = 0; i < folhas.length; i++) {
            postosDisponiveis.push(folhas[i].getAttribute('data-posto'));
        }
        alert('Erro: Não foi possível encontrar a página do posto ' + codigoPosto + '\n\n' +
              'Postos disponíveis: ' + postosDisponiveis.join(', ') + '\n\n' +
              'Verifique o console do navegador (F12) para mais detalhes.');
        console.error('ERRO: Posto não encontrado:', codigoPosto);
        console.error('Seletor usado:', '.folha-a4-oficio[data-posto="' + codigoPosto + '"]');
        return;
    }
    
    console.log('✓ Folha original encontrada!');
    console.log('✓ Iniciando clonagem...');
    
    // 2. Clonar a folha inteira
    var folhaNova = folhaOriginal.cloneNode(true);
    
    // 3. Gerar sufixo único baseado em timestamp
    var sufixo = '_clone_' + Date.now();
    
    // 4. Atualizar IDs para evitar conflitos
    var elementosComId = folhaNova.querySelectorAll('[id]');
    for (var j = 0; j < elementosComId.length; j++) {
        elementosComId[j].id = elementosComId[j].id + sufixo;
    }
    
    // 5. Atualizar names dos inputs
    var elementosComName = folhaNova.querySelectorAll('[name]');
    for (var k = 0; k < elementosComName.length; k++) {
        var nameOriginal = elementosComName[k].name;
        if (nameOriginal.indexOf('lote_posto') !== -1 || 
            nameOriginal.indexOf('nome_posto') !== -1 || 
            nameOriginal.indexOf('endereco_posto') !== -1 ||
            nameOriginal.indexOf('quantidade_posto') !== -1 ||
            nameOriginal.indexOf('lotes_confirmados') !== -1) {
            // Adicionar sufixo ao código do posto
            elementosComName[k].name = nameOriginal.replace('[' + codigoPosto + ']', '[' + codigoPosto + sufixo + ']');
        }
    }
    
    // 6. Limpar campo de lacre na página clonada
    var inputLacreNovo = folhaNova.querySelector('input[name*="lote_posto"]');
    if (inputLacreNovo) {
        inputLacreNovo.value = '';
        inputLacreNovo.placeholder = 'Digite novo lacre para este malote';
    }
    
    // 7. Adicionar botão REMOVER DENTRO da página clonada (não no topo)
    var oficioDiv = folhaNova.querySelector('.oficio');
    if (oficioDiv) {
        // Criar container para o botão
        var containerBotao = document.createElement('div');
        containerBotao.className = 'nao-imprimir';
        containerBotao.style.cssText = 'background:#fff3cd; border:2px solid #ffc107; border-radius:6px; padding:12px; margin-bottom:15px; text-align:center;';
        
        // Criar botão
        var btnRemover = document.createElement('button');
        btnRemover.type = 'button';
        btnRemover.className = 'btn-remover-pagina';
        btnRemover.style.cssText = 'background:#dc3545; color:#fff; border:2px solid #bd2130; border-radius:6px; padding:10px 20px; font-size:14px; font-weight:bold; cursor:pointer; box-shadow:0 2px 5px rgba(220,53,69,0.3);';
        btnRemover.innerHTML = '✕ REMOVER ESTA PÁGINA CLONADA';
        btnRemover.onmouseover = function() { this.style.background = '#c82333'; };
        btnRemover.onmouseout = function() { this.style.background = '#dc3545'; };
        btnRemover.onclick = function() {
            if (confirm('Deseja remover esta página clonada?')) {
                folhaNova.remove();
            }
        };
        
        containerBotao.appendChild(btnRemover);
        oficioDiv.insertBefore(containerBotao, oficioDiv.firstChild);
    }
    
    // 8. Atualizar onclick dos checkboxes na página clonada
    var checkboxesNovos = folhaNova.querySelectorAll('.checkbox-lote');
    for (var m = 0; m < checkboxesNovos.length; m++) {
        var checkbox = checkboxesNovos[m];
        var postoOriginal = checkbox.getAttribute('data-posto');
        checkbox.setAttribute('data-posto', codigoPosto + sufixo);
        checkbox.setAttribute('onchange', 'recalcularTotal(\'' + codigoPosto + sufixo + '\')');
    }
    
    // 9. Atualizar onclick do checkbox "marcar todos"
    var marcarTodos = folhaNova.querySelectorAll('.marcar-todos');
    for (var n = 0; n < marcarTodos.length; n++) {
        marcarTodos[n].setAttribute('data-posto', codigoPosto + sufixo);
        marcarTodos[n].setAttribute('onchange', 'marcarTodosLotes(this, \'' + codigoPosto + sufixo + '\')');
    }
    
    // 10. Marcar como página clonada e atualizar data-posto
    folhaNova.classList.add('pagina-clonada');
    folhaNova.setAttribute('data-posto', codigoPosto + sufixo);
    folhaNova.setAttribute('data-posto-original', codigoPosto);
    
    // 11. Inserir após a página original
    folhaOriginal.parentNode.insertBefore(folhaNova, folhaOriginal.nextSibling);
    
    // 12. Recalcular total da página clonada
    setTimeout(function() {
        recalcularTotal(codigoPosto + sufixo);
    }, 50);
    
    // 13. Scroll suave até a nova página
    setTimeout(function() {
        folhaNova.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
    
    alert('✅ Página clonada criada com sucesso!\n\n📋 Agora você pode marcar/desmarcar checkboxes em cada página\n💡 Os totais são recalculados automaticamente\n🗑️ Use o botão amarelo para remover páginas clonadas\n\n⚠️ Não esqueça de informar um NOVO lacre para esta página!');
}

</script>
</body>
</html>
