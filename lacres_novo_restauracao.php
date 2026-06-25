<?php
// VERSAO SISTEMA: 2.0.0 - 2026-05-26
/* lacres_novo.php — v1.2.2
 * CHANGELOG v1.1.12 (05/05/2026):
 * - [VER] Alinhamento de versão global para v1.1.12
 * CHANGELOG v1.1.11 (04/05/2026):
 * - [FIX] Botão Av no primeiro input agora trava corretamente (Capital, Regionais e Central):
 *         adicionado check inputLacreAvulso nos listeners da planilha para bloquear propagação
 *         quando o input de origem está marcado como avulso.
 * CHANGELOG v1.1.5 (29/04/2026):
 * - [VER] Alinhamento de versão global para v1.1.5
 * CHANGELOG v1.0.18 (27/04/2026):
 * - [FIX] Botão AV oculto na impressão (CSS mais agressivo)
 * - [FIX] Card "Adicionar Posto Manualmente" removido
 * - [FIX] Coluna Ação no ofício mestre PT: botões lado a lado (flex-wrap:nowrap)
 * - [FIX] Input responsável na fila de lotes não perde o foco para o leitor de códigos
 * - [NOVO] Toggle Ocultar/Mostrar postos Poupa Tempo
 * - [NOVO] Versão 1.0.13
 * Sistema de criação e gestão de ofícios (Poupa Tempo e Correios)
 *
 * CHANGELOG v1.0.9 (13/04/2026):
 * - [CORRIGIDO] Ofício PT deixa de limitar lotes por página e segue em continuidade natural na impressão
 * - [NOVO] Filtro opcional por postos PT aceita lista digitada além da seleção visual
 * - [NOVO] Versão consolidada para v1.0.9
 *
 * CHANGELOG v1.0.7 (09/04/2026):
 * - [NOVO] Inputs de lacre aceitam multiplos valores separados por hifen
 * - [NOVO] Sequencia automatica continua a partir do ultimo lacre digitado
 * - [NOVO] Modo discreto de lacre avulso por input
 * - [MELHORADO] Inputs de lacre ampliados para visualizacao completa
 *
 * CHANGELOG v1.0.6 (09/04/2026):
 * - [CORRIGIDO] Exclusão de linha preserva lacres e etiquetas já digitados
 * - [CORRIGIDO] Posto 002 deixa de abrir linha própria no ofício dos Correios
 * - [NOVO] Popup de leitura mostra posto, lacre IIPR e lacre Correios
 * - [CORRIGIDO] Autoavanço das etiquetas segue Capital > Central > Regionais
 * - [NOVO] Versão consolidada para v1.0.6
 *
 * CHANGELOG v1.0.3 (07/04/2026):
 * - [CORRIGIDO] Ofício PT com etiqueta Correios volta a gravar normalmente com número de ofício
 * - [CORRIGIDO] Folhas internas PT salvam lotes e quantidades mesmo sem coluna de lacre
 * - [NOVO] Folha mestre PT reutiliza o padrão visual e os dados persistidos em ciDespachoLotes
 * - [NOVO] Versão consolidada para v1.0.3
 *
 * CHANGELOG v1.0.2 (06/04/2026):
 * - [CORRIGIDO] Remove uso de array_column para compatibilidade com PHP 5.3
 * - [CORRIGIDO] json_encode agora sanitiza UTF-8 inválido antes de gerar payloads PT
 * - [NOVO] Fluxo PT separado em dois botões: em branco e com etiqueta Correios
 * - [NOVO] Versão consolidada para v1.0.2
 *
 * CHANGELOG v1.0.1 (06/04/2026):
 * - [CORRIGIDO] Linha inserida na grade dos Correios agora propaga a sequência de lacres dali para baixo sem alterar as linhas acima
 * - [CORRIGIDO] Fluxo Correios exibe erro textual no salvamento quando ocorrer exceção, evitando tela branca silenciosa
 * - [NOVO] Versão consolidada para v1.0.1
 *
 * CHANGELOG v1.0.0 (02/04/2026):
 * - [VERSAO] Marco de publicação consolidado em v1.0.0
 * - [VERSAO] Interface e arquivos sincronizados para exibir v1.0.0
 *
 * CHANGELOG v9.25.24 (02/04/2026):
 * - [NOVO] Split da Central IIPR agora suporta 3 ou mais malotes com lacre Correios independente por grupo
 * - [CORRIGIDO] Replicação de lacre/display no split passa a respeitar todos os blocos visuais, não apenas 2 grupos
 * - [VERSAO] Interface atualizada para Versão 0.9.25.24
 *
 * CHANGELOG v9.25.23 (01/04/2026):
 * - [NOVO] Botões independentes de Aplicar Lacres para CAPITAL, CENTRAL IIPR e REGIONAIS
 * - [CORRIGIDO] Recalculo por lacre agora afeta apenas o grupo solicitado
 * - [VERSAO] Interface atualizada para Versão 0.9.25.23
 *
 * CHANGELOG v9.25.10 (17/03/2026):
 * - [NOVO] Resumo consolidado do ofício por grupos de malote
 * - [NOVO] Grupos de malote IIPR e Correios persistidos em ciDespachoLotes
 * - [NOVO] Separação de linhas repetidas do mesmo posto no resumo do ofício
 *
 * CHANGELOG v9.24.4 (20/02/2026):
 * - [AJUSTE] Botao "Adicionar linha abaixo" visivel ao lado de Excluir/Excluir Regional
 * - [CORRIGIDO] Linha inserida aparece exatamente abaixo da referencia
 * - [CORRIGIDO] Auto-impressao restaura lacres/etiquetas antes do PDF
 * - [MANTIDO] Modal de insercao permite grupo POUPA TEMPO
 * 
 * CHANGELOG v9.21.6 (10/02/2026):
 * - [CORRIGIDO] Preserva lacres digitados ao cadastrar novo posto em regionais
 *
 * CHANGELOG v9.21.5 (29/01/2026):
 * - [CORRIGIDO] ✅ Card "Status de Conferências" oculto na impressão (classe nao-imprimir)
 * - [MANTIDO] ✅ Botão "Filtrar por data(s)" com recálculo automático
 * - [MANTIDO] ✅ Lógica v9.13.0: CAPITAL (+2), CENTRAL (+1 IIPR, último+1 Correios), REGIONAIS (+2)
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.21.5
 * 
 * CHANGELOG v9.21.1 (29/01/2026):
 * - [RESTAURADO] Botão "Atribuir Lacres" para numeração sequencial automática
 * - [NOVO] Função atribuirLacresSequencial() - preenche lacres IIPR e Correios automaticamente
 * - [MELHORADO] Prompt interativo solicita número inicial e confirma antes de atribuir
 * - [COMPATÍVEL] Funciona com CAPITAL, CENTRAL IIPR e REGIONAIS (ignora POUPA TEMPO)
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.21.1
 * 
 * CHANGELOG v9.14.0 (27/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.14.0
 * - [CARREGAMENTO] Página inicia VAZIA sem datas pré-carregadas
 * - [UX] Usuário escolhe período manualmente antes de buscar dados
 * - [BOTÃO] "Aplicar Período" agora é o único necessário (filtro duplicado removido)
 * - [SPLIT] Botão simplificado "ACRESCENTAR PÁGINA" discreto no fim
 * - [SPLIT] Clonagem simples de página completa
 * - [SPLIT] Usuário marca/desmarca lotes manualmente
 * - [SPLIT] Botão "REMOVER ESTA PÁGINA" em páginas clonadas
 * - [ANÁLISE] Painel mostra mensagem quando não há datas selecionadas
 * 
 * CHANGELOG v9.12.0 (27/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.12.0
 * - [SPLIT] Sistema FUNCIONAL de divisão (botões "DIVIDIR AQUI" em cada linha)
 * - [SPLIT] Interface interativa para escolher ponto de divisão
 * - [CONFERÊNCIA] Corrigido busca em tabelas 2 colunas (_col1 + _col2)
 * - [CONFERÊNCIA] Linha verde funciona com layout 1 ou 2 colunas
 * - [UX] Botões split visíveis apenas na administração
 * 
 * CHANGELOG v9.11.0 (27/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.11.0
 * - [IMPRESSÃO] Controles administrativos 100% ocultos (.nao-imprimir)
 * - [ENCODING] Corrigido emojis UTF-8 (texto limpo)
 * - [RODAPÉ] Reestruturado: Linha 1 (Feito por + Data geração)
 * - [RODAPÉ] Linha 2 (Entregue para + RG/CPF + Data entrega)
 * - [CSS] Adicionado .controle-split, .btn-split {display:none !important}
 * 
 * CHANGELOG v9.10.0 (27/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.10.0
 * - [LAYOUT] 2 colunas automático quando >12 lotes
 * - [LAYOUT] Estrutura: [Lote|Qtd] [Lote|Qtd] lado a lado
 * - [LAYOUT] Removido barra de rolagem (max-height:400px)
 * - [SPLIT] Botão "DIVIDIR PÁGINA" para múltiplos malotes
 * - [SPLIT] Usuário desmarca lotes para próxima página
 * - [SPLIT] Total recalculado automaticamente
 * - [UX] Todos os lotes visíveis sem scroll
 * - [UX] Rodapé "Data:" sempre visível
 * 
 * CHANGELOG v9.9.6 (27/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.9.6
 * - [CORRIGIDO] Extração de quantidade: últimos 5 dígitos do código (não 4)
 * - [CORRIGIDO] Estrutura código: [8:lote][6:outros][5:qtd] = 19 dígitos
 * - [CORRIGIDO] Exemplo: 0075942402302300170 → Lote:00759424 Qtd:170
 * - [IMPRESSÃO] Linhas amarelas marcadas APARECEM na impressão
 * - [RODAPÉ] Posicionamento ajustado para PDF (padding-top)
 * - [FUTURO] Layout 2 colunas para muitos lotes (planejado para v9.10.0)
 * 
 * CHANGELOG v9.9.5 (27/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.9.5
 * - [IMPRESSÃO] Linhas "NÃO CADASTRADO" ocultas automaticamente
 * - [IMPRESSÃO] Coluna quantidade mostra apenas número (sem input)
 * - [RODAPÉ] Reposicionado próximo ao final da página
 * - [RODAPÉ] Data movida para linha 2 (separada)
 * - [CONFERÊNCIA] Automática ao digitar 19 dígitos (sem Enter)
 * - [CONFERÊNCIA] Input limpo automaticamente após cada leitura
 * - [UX] Sem alertas ao encontrar lote (feedback visual apenas)
 * - [UX] Linha amarela para lotes não cadastrados (oculta na impressão)
 * 
 * CHANGELOG v9.9.4 (27/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.9.4
 * - [CORRIGIDO] Conferência agora marca linha verde com .trim() na comparação
 * - [CORRIGIDO] Rodapé REALMENTE simplificado para 2 linhas físicas
 * - [DEBUG] Console.log adicional para rastrear comparação de lotes
 * 
 * CHANGELOG v9.9.3 (27/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.9.3
 * - [CORRIGIDO] Extração de lote corrigida para 8 dígitos (posições 0-7)
 * - [CORRIGIDO] Quantidade extraída das posições 8-11 (4 dígitos)
 * - [SIMPLIFICADO] Rodapé em apenas 2 linhas (mais limpo)
 * - [VALIDADO] Código 0075940100600600100 → Lote: 00759401 ✓
 * 
 * CHANGELOG v9.9.2 (27/01/2026):
 * - [CORRIGIDO] Conferência com código de barras de 19 dígitos
 * - [CORRIGIDO] Extração automática de lote e quantidade do código
 * - [MELHORADO] Rodapé reformatado (Entregue para / RG/CPF / Data)
 * - [REMOVIDO] Título redundante do painel de conferência
 * 
 * CHANGELOG v9.9.1 (27/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.9.1
 * - [CORRIGIDO] CSS aparecendo como texto na página
 * - [CORRIGIDO] Quebra de página entre ofícios
 * - [CORRIGIDO] Lotes respeitam a folha do posto
 * - [CORRIGIDO] Texto sobrepondo na impressão
 * 
 * CHANGELOG v9.9.0 (27/01/2026):
 * - [NOVO] Sistema de conferência de lotes com leitor de código de barras
 * - [MELHORADO] Layout centralizado sem ultrapassar margem direita
 * - [CORRIGIDO] Lotes desmarcados não aparecem na impressão
 * - [UNIFORMIZADO] Fonte consistente em todo o ofício (14px, negrito)
 * - [PROFISSIONAL] Impressão limpa sem botões, checkbox ou cores
 * 
 * CHANGELOG v9.8.7 (26/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.8.7
 * - [PROFISSIONAL] Layout limpo e uniformizado
 * - [TESTADO] Sistema completo de controle de lotes funcionando
 * 
 * CHANGELOG v9.8.6 (26/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.8.6
 * - [MELHORADO] Impressão limpa sem elementos de controle
 * - [FUNCIONAL] Sistema de lotes individuais totalmente operacional
 * 
 * CHANGELOG v9.8.5 (26/01/2026):
 * - [CORRIGIDO] Erro de sintaxe no modelo_oficio_poupa_tempo.php corrigido
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.8.5
 * 
 * CHANGELOG v9.8.4 (26/01/2026):
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.8.4
 * - [MELHORADO] Debug para identificar quando não há dados
 * - [ADICIONADO] Mensagem clara se nenhum ofício for gerado
 * 
 * CHANGELOG v9.8.3 (26/01/2026):
 * - [CORRIGIDO] Exibição de lotes individuais no ofício Poupa Tempo
 * - [CORRIGIDO] Validação de array de lotes antes de exibir tabela
 * - [MELHORADO] Debug aprimorado para identificar problemas de lotes
 * - [CONFIRMADO] CSS de impressão funcionando corretamente
 * - [SINCRONIZADO] Com modelo_oficio_poupa_tempo.php v9.8.3
 * 
 * CHANGELOG v9.8.2 (26/01/2026):
 * - [NOVO] Controle granular de lotes no Ofício Poupa Tempo
 * - [NOVO] Tabela de lotes individuais com checkbox para cada lote
 * - [NOVO] Recálculo dinâmico do total baseado nos lotes marcados
 * - [NOVO] Por padrão todos os lotes vêm marcados para despacho
 * - [NOVO] Lotes desmarcados não aparecem na impressão
 * - [MELHORADO] Total de CIN's calculado apenas dos lotes confirmados
 * - [INTEGRADO] modelo_oficio_poupa_tempo.php v9.8.2 com controle de lotes
 * - Funcionalidade: Desmarcar lotes não finalizados antes de imprimir
 * 
 * CHANGELOG v9.8.1 (26/01/2026):
 * - [CORRIGIDO] Status de Conferências: agora mostra APENAS dias com produção real
 * - [CORRIGIDO] Bug: dias 07/01/2026 e 08/01/2026 não aparecem mais como pendentes sem produção
 * - [NOVO] Labels de dia da semana nos badges: SEX (amarelo), SÁB (azul), DOM (vermelho)
 * - [MELHORADO] Lógica: conferências pendentes = dias COM produção MAS sem conferência
 * - [REMOVIDO] Calendário completo de 30 dias (mostrava domingos sem produção como pendentes)
 * - [NOVO] Query SQL com DAYOFWEEK() para detectar fins de semana
 * - [NOVO] Array $metadados_dias armazena informações de dia da semana
 * - [INTEGRAÇÃO] conferencia_pacotes: JOIN entre ciPostosCsv e conferencia_pacotes
 * 
 * CHANGELOG v9.8.0 (23/01/2026):
 * - [REMOVIDO] Checkboxes de seleção de datas (substituídos por calendário)
 * - [NOVO] Calendário visual para seleção de datas (date picker nativo)
 * - [NOVO] Campo para adicionar datas alternadas/específicas manualmente
 * - [NOVO] Status de Conferência recolhível com botão toggle
 * - [NOVO] Datas exibidas em badges coloridos: verde (conferidas) e amarelo (pendentes)
 * - [NOVO] Mostra últimos 5 dias com conferência (ao invés de todos)
 * - [MELHORADO] Botões de zoom A+/A- mais visíveis e acessíveis
 * - [REMOVIDO] Sistema completo de snapshot/auto-save (causava valores antigos nos inputs)
 * - [INTEGRADO] Salvamento de etiquetas Correios agora faz parte do "Gravar e Imprimir"
 * - [PREPARADO] Botão "Salvar Etiquetas Correios" marcado para remoção futura
 * - Compatibilidade: PHP 5.3.3 + ES5 JavaScript
 * 
 * CHANGELOG v9.7.1 (23/01/2026):
 * - [NOVO] Filtros de data com inputs para data inicial e data final
 * - [NOVO] Indicador no topo direito mostrando últimos dias com conferência e dias sem conferência
 * - [NOVO] Pop-up centralizado ao clicar em inputs de etiquetas Correios (mostra posto atual)
 * 
 * CHANGELOG v8.16.0 (12/12/2025):
 * - [ALTERADO] Formato do número do ofício no cabeçalho Correios: "Nº #101" (com # antes do ID)
 * - Posicionamento mantido no canto direito do quadro CLIENTE/SISTEMA/SETOR
 * - Poupa Tempo permanece inalterado (não exibe número no cabeçalho)
 * 
 * CHANGELOG v8.15.9 (12/12/2025):
 * - [NOVO] Adicionado número do ofício (Nº ID) no canto direito do cabeçalho dos Correios
 * - Formato: "Nº 101" exibido ao lado do quadro CLIENTE/SISTEMA/SETOR
 * - Número aparece tanto na impressão quanto na visualização em tela
 * 
 * CHANGELOG v8.15.7 (11/12/2025):
 * - [CORRIGIDO] Nome do PDF sem # (agora: 97_correios_11-12-2025.pdf ao invés de #97_correios...)
 * - Sincronizado com consulta_producao.php v8.15.7 e modelo_oficio_poupa_tempo.php v8.15.7
 * 
 * CHANGELOG v8.15.6 (11/12/2025):
 * - Sincronizado com consulta_producao.php v8.15.6 e modelo_oficio_poupa_tempo.php v8.15.6
 * - Confirmado: Arquivos salvos SEM # no início (ex: 97_correios_11-12-2025.pdf)
 * - Modo "Criar Novo" corrigido: agora SEMPRE cria novo ofício com novo ID
 * - Layout melhorado: margem 15mm, fonte 13px no nome do posto
 * 
 * CHANGELOG v8.15.5 (11/12/2025):
 * - Sincronizado com consulta_producao.php v8.15.5
 * - Confirmado: Arquivos salvos SEM # no início (ex: 96_correios_11-12-2025.pdf)
 * - Confirmado: Estrutura de pastas lowercase (correios, poupatempo)
 * - Confirmado: Links file:/// funcionando corretamente
 * 
 * CHANGELOG v8.15.3 (11/12/2025):
 * - Sincronizado com consulta_producao.php v8.15.3
 * - Formato de arquivo SEM # no início: 88_correios_11-12-2025.pdf
 * - Estrutura de pastas em lowercase: correios, poupatempo
 * - Caminho: Q:\cosep\IIPR\Oficios\{Ano}\{Mes}\{tipo}\
 * 
 * Patch histórico: liberar etiqueta ao apagar (mover entre inputs)
 * Gerado em 2025-11-07T12:28:56
 */

// MELHORIAS ANTERIORES: v8.7 (fallback 0 para lacres), v8.6 (mapa de lacres), v8.5 (persistência confirmada)
// v8.8: Corrige captura de lacres e etiquetas dos Correios (HTML + POST + gravação)
// - Introduz arrays alinhados no formulário: posto_lacres[], lacre_iipr[], lacre_correios[], etiqueta_correios[]
// - Função JS preenche esses arrays antes do submit sem alterar o comportamento existente
// - Backend usa esses arrays para montar $mapaLacresPorPosto e gravar em ciDespachoLotes
// - Mantém validações, SPLIT e foco automático inalterados
// v8.9: Lacres/etiqueta por regional - aplica a TODOS os lotes da regional
// - Estende JS para capturar regional_lacres[] alinhado com postos
// - Backend monta $mapaLacresPorRegional além de $mapaLacresPorPosto
// - No INSERT: prioridade 1º lacre por posto, 2º lacre por regional, 3º defaults
// - Todos os lotes de uma regional recebem os mesmos lacres/etiqueta (a menos que o posto tenha lacre específico)
// v8.10: Corrige salvamento de lacres (IIPR/Correios) por regional
// - Garante captura correta dos valores de lacre_iipr e lacre_correios no POST
// - Normaliza regional para formato consistente (remove zeros à esquerda)
// - Adiciona debug detalhado por lote para diagnosticar problemas
// - Valida que mapaLacresPorRegional é preenchido corretamente e usado no INSERT
// v8.11: Preserva inputs de lacres/etiquetas ao excluir postos e ao filtrar por data (compatível com PHP 5.3)
// - Implementa localStorage para persistir etiquetas/lacres por (id_despacho, regional, posto)
// - Funções JS: salvarEstadoEtiquetasCorreios(), restaurarEstadoEtiquetasCorreios()
// - Chamadas: antes de excluir posto, antes de aplicar filtro de data, ao carregar página
// - Garante que nenhum dado digitado seja perdido ao usar filtros ou remover linhas
// v8.11.1: Confirmação de gravação, modo sobrescrever/novo ofício, destaque visual de splits na CENTRAL IIPR
// - Adiciona confirmacao antes de gravar o ofício + escolha sobrescrever/novo
// - Campo hidden `modo_oficio` no form de Correios
// - Destaque leve das linhas abaixo do split (.split-central-grupo1/2/...)
// - Confirmação ao limpar sessão e reset parcial da CENTRAL IIPR ao limpar coluna X
// - Compatível com PHP 5.3 / ES5 (Yii 1.x)
// v8.12.3: Correção crítica - CENTRAL IIPR salva APENAS postos visíveis no grid (não todos os postos das datas)
// - JS prepararLacresCorreiosParaSubmit agora envia grupo_lacres[] para identificar CAPITAL/CENTRAL/REGIONAIS
// - Handler PHP separa postos por grupo real: $mapaCapital (CAPITAL), $mapaCentral (CENTRAL IIPR), $mapaLacresPorRegional (REGIONAIS)
// - Loop de gravação aplica lacres SOMENTE aos postos presentes nos mapas corretos:
//   * CAPITAL: salva apenas postos visíveis em $mapaCapital
//   * CENTRAL IIPR: salva apenas postos visíveis em $mapaCentral
//   * REGIONAIS: salva todos os postos das regionais visíveis em $mapaLacresPorRegional
// - Corrige comportamento onde CENTRAL salvava todos os postos das datas (016, 029, 042, etc.) mesmo quando apenas posto 086 estava visível
// v8.12.3-fix: Preservação de dados após salvar
// - Removido window.location.href após salvar com sucesso (impedia que inputs permanecessem preenchidos)
// - Inputs de Lacre IIPR, Lacre Correios e Etiqueta Correios agora permanecem na tela após "Gravar e Imprimir"
// - localStorage continua preservando etiquetas entre operações (salvar/filtrar/excluir)
// - Limpeza dos inputs ocorre APENAS via "Limpar Sessão" ou botão X específico de cada coluna
// - CENTRAL IIPR confirmado: grava SOMENTE postos que estão visíveis na grade (usa $mapaCentral filtrado por grupo)
// v8.12.3-fix2: Correção definitiva dos 3 problemas restantes
// - CENTRAL IIPR: Confirmado que JÁ grava apenas postos visíveis (lógica correta desde v8.12.3)
// - LACRES IIPR/CORREIOS: Salvamento em $_SESSION['lacres_personalizados'] após sucesso para preservar valores digitados
// - PRESERVAÇÃO: Valores restaurados via sessão ao recarregar, sem recalcular (exceto quando recalculo_por_lacre=1)
// - BUG CORRIGIDO: Regionais usavam mesmo valor para etiquetaiipr e etiquetacorreios (faltava incremento)
// v8.13: Refatoração estrutural - Snapshot da grade como fonte única de verdade
// - Frontend: prepararLacresCorreiosParaSubmit() agora cria snapshot_oficio (JSON) com estado exato da grade
// - Backend: salvar_oficio_correios usa EXCLUSIVAMENTE o snapshot para gravar (sem recálculos)
// - CAPITAL/CENTRAL: grava SOMENTE postos visíveis no snapshot (filtro rigoroso)
// - REGIONAIS: expande para todos os postos da regional, mas usa lacres/etiqueta do snapshot
// - Preservação total: após salvar, inputs permanecem com valores originais (sem recalcular nem zerar)
// - Compatibilidade: PHP 5.3.3 (Yii 1.x) + JavaScript ES5 (sem let/const/arrow functions)
// v8.13.1: Correções finais de consistência
// - CENTRAL IIPR: Confirmado uso exclusivo do snapshot (mesmo comportamento de CAPITAL - sem expansão automática)
// - Lacres IIPR/Correios: Garantido uso dos valores EXATOS dos inputs (sem sobrescrever com cálculos)
// - Preservação: Inputs permanecem preenchidos após salvar (valores salvos em $_SESSION['lacres_personalizados'])
// - Validação: etiquetaiipr ≠ etiquetacorreios quando digitados diferentes, CENTRAL grava apenas postos visíveis
// v8.13.2: Restauração da lógica ORIGINAL de atribuição automática de lacres
// - CAPITAL: Lacres em pares incrementais (+2) → lacre_iipr=N, lacre_correios=N+1, próximo=N+2
// - CENTRAL IIPR: Lacres IIPR sequenciais (+1), lacre Correios = ÚLTIMO lacre IIPR gerado (aplicado a todos)
// - REGIONAIS: Par de lacres por regional aplicado a todos os postos daquela regional
// - Snapshot mantido: Ao salvar, grava EXATAMENTE o que está na tela (sem recálculos no handler)
// - Compatibilidade: PHP 5.3.3 + ES5, sem quebrar funcionalidades existentes
// v8.13.3: Correções críticas de atribuição e gravação de lacres
// - CENTRAL IIPR: Lacre Correios = ÚLTIMO lacre IIPR + 1 (não o último puro) aplicado a TODAS as linhas do grupo
// - Lógica por grupo visual: respeita SPLITs, cada grupo tem lacreCorreios = max(lacreIIPR_grupo) + 1
// - REGIONAIS: Garante lacre_iipr ≠ lacre_correios (usa valores EXATOS dos inputs, não reutiliza)
// - Gravação no banco: etiquetacorreios sempre usa lacre_correios (nunca lacre_iipr duplicado)
// - Debug: Adiciona debug_lacres=1 para inspecionar mapas antes de gravar
// - Snapshot: Mantido como fonte única de verdade, corrigida apenas a lógica de cálculo inicial
// v8.13.4: Correções finais de usabilidade e fidelidade ao snapshot
// - Inputs zerados por padrão: não preenche com valor 1 automático, usuário digita lacres iniciais
// - Lacres NUNCA duplicados: validação rigorosa IIPR≠Correios em todos os grupos (exceto Correios da Central entre si)
// - Preservação total ao excluir: mantém TODOS os inputs (lacres + etiquetas) ao remover linha
// ==================================================================================
// v8.14.2: Impressão REAL com dados do BD (correção definitiva)
// ==================================================================================
// - CORRIGIDO: Após salvar, REDIRECT recarrega página com dados do BD
// - CORRIGIDO: Arrays PHP $dados[] preenchidos com lacres/etiquetas do BD antes de renderizar
// - CORRIGIDO: Auto-impressão via flag de sessão após reload completo
// - CAPITAL: Lacres carregados do BD aparecem no PDF ✅
// - REGIONAIS: Lacres carregados do BD aparecem no PDF ✅  
// - CENTRAL IIPR: Lacres carregados do BD aparecem no PDF ✅
// - Confirmação com 3 opções mantida (v8.14.1)
// - Snapshot 100% fiel: CENTRAL IIPR salva APENAS postos visíveis na tela (não todos os postos das datas)
// - Impressão fiel: o que você vê na tela é EXATAMENTE o que será impresso e salvo no banco
// ==================================================================================
// v8.14.4: Melhorias UX + gravação completa de lotes PT
// ==================================================================================
// - NOVO: Botão renomeado para "Gravar e Imprimir Correios" (clareza)
// - NOVO: Campo lote salvo em ciDespachoItens para PT (antes vazio)
// - NOVO: GROUP_CONCAT de lotes no SELECT PT para capturar todos
// - MANTIDO: Modal 3 opções para PT e Correios (v8.14.3)
// - MANTIDO: Toda funcionalidade de impressão e redirect (v8.14.2)
// - Compatibilidade total entre PT e Correios
// ==================================================================================
// v8.14.5: Modal PT + Botões Pulsantes + Correção FK
// ==================================================================================
// - NOVO: Modal 3 opções aparece ao clicar "Gravar e Imprimir" em modelo_oficio_poupa_tempo.php
// - NOVO: Botões pulsam (animação) quando há dados não salvos na tela (PT)
// - NOVO: Correção erro FK constraint: valida id_despacho existe antes de INSERT em ciDespachoItens
// - MANTIDO: Todas as funcionalidades de v8.14.4 (lotes, Correios, etc)
// ==================================================================================
// v8.14.6: Salvamento AUTOMÁTICO de Etiquetas Correios (Simplificado)
// ==================================================================================
// - NOVO: Etiquetas salvam AUTOMATICAMENTE ao gravar ofício Correios
// - NOVO: Integração inline no handler salvar_oficio_correios (linha ~1085)
// - NOVO: Extrai CEP (8 chars) e Sequencial (5 últimos) de cada etiqueta
// - NOVO: INSERT direto em ciMalotes com dados: leitura, data, login, cep, sequencial, posto
// - NOVO: Controle duplicatas CENTRAL IIPR (mesmo CEP+Sequencial não repete)
// - NOVO: Modal simplificado (apenas 3 botões: Sobrescrever/Criar Novo/Cancelar)
// - NOVO: Alert de sucesso inclui quantidade de etiquetas salvas
// - MANTIDO: Botão "Salvar Etiquetas Correios" separado continua funcionando
// - MANTIDO: Todas as funcionalidades anteriores preservadas (v8.14.5 e anteriores)
// - Compatibilidade: PHP 5.3.3 + ES5 JavaScript
// ==================================================================================
// v8.14.7: Sistema de Snapshot/Auto-Save + Remoção Salvamento Automático Etiquetas
// ==================================================================================
// - NOVO: Sistema snapshot contínuo (auto-save a cada 3s via localStorage + banco)
// - NOVO: Restauração automática ao carregar página (independente de usuário logado)
// - NOVO: Tabela ciSnapshotCorreios armazena estado completo da tela por datas
// - NOVO: Indicador visual "💾 Salvando..." / "✅ Salvo" no topo da página
// - NOVO: Versão exibida atualizada: "Análise de Expedição (v8.14.7)"
// - REVERTIDO: Botão "Gravar e Imprimir Correios" NÃO salva mais etiquetas automaticamente
// - REVERTIDO: Modal volta ao v8.14.5 (apenas Sobrescrever/Criar Novo/Cancelar)
// - MANTIDO: Botão "💾 Salvar Etiquetas Correios" separado continua funcionando
// - MANTIDO: Todas funcionalidades v8.14.5 preservadas (modal PT, pulsing, FK fix)
// - Chave snapshot: "snapshot_correios:{datas}" (compartilhado entre usuários)
// - Conteúdo: lacres IIPR, lacres Correios, etiquetas Correios, seleções de postos
// - Compatibilidade: PHP 5.3.3 + ES5 JavaScript
// ==================================================================================
// v8.14.8: Foco em ciDespachoLotes + Remoção Total de ciMalotes no Fluxo Correios
// ==================================================================================
// - MANTIDO: Sistema snapshot v8.14.7 (auto-save, restauração, indicador visual)
// - RESTABELECIDO: Gravação de etiquetas em ciDespachoLotes (etiquetaiipr, etiquetacorreios, etiqueta_correios)
// - REMOVIDO: Toda gravação em ciMalotes do fluxo "Gravar e Imprimir Correios" (linhas ~1180-1280)
// - CRÍTICO: Usa valores EXATOS dos inputs (não recalcula) via snapshot
// - GARANTIA: etiquetaiipr, etiquetacorreios, etiqueta_correios gravados corretamente em ciDespachoLotes
// - VERSÃO: Exibida como "Análise de Expedição (v8.14.8)"
// ==================================================================================
// v8.14.9: "Criar Novo" Funcional + Campo usuario + Modal Poupa Tempo
// ==================================================================================
// - CORREÇÃO CRÍTICA: Modal confirmação adicionado ao botão "Gravar Ofício" Poupa Tempo (era submit direto)
// - CORREÇÃO: Handler salvar_oficio_pt agora verifica modo_oficio e usa timestamp no hash quando modo=novo
// - NOVO: Campo usuario (varchar 15) em ciDespachoItens capturado de ciPostosCsv.usuario
// - GARANTIA: "Criar Novo" agora efetivamente cria ofício separado (não sobrescreve)
// - MODAL: 3 opções (Sobrescrever/Criar Novo/Cancelar) agora presente em ambos fluxos
// - VERSÃO: Exibida como "Análise de Expedição (v8.14.9)"
// ==================================================================================
// v8.14.9.1: Correções e Melhorias de UX
// ==================================================================================
// - CORREÇÃO: Variável $responsavel definida ANTES do uso (linha 2166) - elimina warning PHP
// - IMPRESSÃO: Painel "Análise de Expedição" auto-recolhido antes de imprimir (evita página em branco)
// - CONSULTA: consulta_producao.php agora mostra detalhes completos para ambos fluxos:
//   * Poupa Tempo: lote, data carga, responsaveis, conferido, conferido por
//   * Correios: adiciona colunas Lacre IIPR e Lacre Correios
// - VISUAL: Badges indicando tipo de posto (POUPA TEMPO / CORREIOS) nos detalhes
// - TOTAIS: Sempre exibidos em ambos os tipos (postos e carteiras)
// - VERSÃO: Exibida como "Análise de Expedição (v8.14.9.1)"
// ==================================================================================
// v8.14.9.2: UX Aprimorada e PDF Nomeado
// ==================================================================================
// - SESSÃO: Auto-restore localStorage DESABILITADO (filtros não trazem valores antigos)
// - LIMPAR: Função "Limpar Sessão" totalmente funcional (limpa todos inputs + localStorage)
// - BADGES: ciDespachoLotes sempre mostra "CORREIOS" (correção lógica)
// - DATAS: Formato padronizado dd-mm-yyyy em todas colunas
// - PDF: Novo padrão de nomenclatura: #ID_tipo_dd-mm-yyyy.pdf (ex: #26_correios_10-12-2025.pdf)
// - REDE: Link para PDF aponta para Q:\cosep\IIPR\Ofícios\{Mes Ano}\{TIPO}\#{arquivo}.pdf
// - VERSÃO: Exibida como "Análise de Expedição (v8.14.9.2)"
// ==================================================================================
// v8.14.9.3: Refinamentos de UX, Correções e Novas Funcionalidades
// ==================================================================================
// - DETALHES: Cabeçalho Correios oculto quando despacho é Poupa Tempo (e vice-versa)
// - SPLIT: Botão SPLIT movido ANTES do nome do posto (compacta linhas)
// - LIMPAR: Limpar Sessão agora REALMENTE limpa (não carrega lacres do BD após limpar)
// - PDF: Caminho atualizado: Q:\cosep\IIPR\Ofícios\{Ano}\{Mes}\{TIPO}\#ID_tipo_dd-mm-yyyy.pdf
// - LACRES: Exibe último lacre IIPR e Correios usado (ao lado do campo Responsável)
// - CRIAR NOVO: Botão "Criar Novo" agora REALMENTE cria novo ofício (PT e Correios)
//   * Antes: "Criar Novo" e "Sobrescrever" faziam a mesma coisa
//   * Agora: "Criar Novo" gera ID único, "Sobrescrever" atualiza ofício existente
// - VERSÃO: Exibida como "Análise de Expedição (v8.14.9.3)"
// ==================================================================================
// v8.14.9.4: Melhorias na Consulta e Ofício Poupa Tempo
// ==================================================================================
// - CONSULTA: Título "Lista de Despachos (Ofícios)" + versão 8.14.9.4 no topo
// - STATUS: Exibe "Finalizado" ao invés de "Ativo"
// - PDF: Link sempre visível (nome do arquivo) para debug de caminho
// - TÍTULOS: Removido "(ciDespachoItens)" e "(ciDespachoLotes)" dos títulos
// - PT LAYOUT: Tabela com word-wrap para nomes longos (quebra linha ao invés de ultrapassar borda)
// - VERSÃO: Exibida como "Análise de Expedição (v8.14.9.4)"
// ==================================================================================
// v8.14.9.5: Correções de Título PDF e Layout PT
// ==================================================================================
// - TÍTULO PDF: Padrão #ID_tipo_dd-mm-yyyy.pdf restaurado (PT e Correios)
//   * Exemplo: #81_correios_10-12-2025.pdf ou #82_poupatempo_10-12-2025.pdf
// - PT TABELA: Largura máxima 650px (não ultrapassa lateral direita)
// - PT INPUT: Nome do posto com scroll horizontal (visualização completa)
// - VERSÃO: Exibida como "Análise de Expedição (v8.14.9.5)"
// ==================================================================================
// v8.15.0: Consulta Produção Funcional para Correios e Poupa Tempo
// ==================================================================================
// - INTEGRAÇÃO: consulta_producao.php agora busca corretamente em ambos fluxos
// - HÍBRIDO: Query usa ciDespachoLotes (Correios) e ciDespachoItens (Poupa Tempo)
// - FILTROS: Todos filtros funcionam para ambos grupos (lote, posto, etiqueta, usuario)
// - GARANTIA: Contagem correta de postos e carteiras independente do grupo
// - VERSÃO: Sistema completo e funcional para ambos fluxos
// - FOCO: Arquivo consulta_producao.php totalmente operacional
// - COMPORTAMENTO:
//   * Botão "Gravar e Imprimir Correios" → grava APENAS em ciDespachos + ciDespachoLotes
//   * Botão "💾 Salvar Etiquetas Correios" (separado) → continua funcionando (pode gravar onde quiser)
//   * Snapshot → preserva estado entre usuários
// - COMPATIBILIDADE: PHP 5.3.3 + ES5 JavaScript

function criarPdoLegado($host, $dbname, $user, $pass) {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $user, $pass);
    } catch (Exception $e) {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8";
        $pdo = new PDO($dsn, $user, $pass);
        try {
            $pdo->exec("SET NAMES utf8");
        } catch (Exception $e2) {
        }
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

// Conexões com os bancos de dados
$pdo_controle = criarPdoLegado("10.15.61.169", "controle", (getenv('DB_USER') ?: 'controle_mat'), (getenv('DB_PASS') ?: '375256'));
$pdo_controle->exec("CREATE TABLE IF NOT EXISTS conferencia_pacotes_lacres (
    id INT NOT NULL AUTO_INCREMENT,
    codbar VARCHAR(25) NOT NULL,
    lote VARCHAR(8) NOT NULL,
    regional VARCHAR(3) NOT NULL,
    posto VARCHAR(10) NOT NULL,
    dataexp DATE NOT NULL,
    qtd INT(5) NOT NULL DEFAULT 0,
    lacre_iipr INT(11) DEFAULT NULL,
    grupo_iipr VARCHAR(40) DEFAULT NULL,
    lacre_correios INT(11) DEFAULT NULL,
    grupo_correios VARCHAR(40) DEFAULT NULL,
    etiqueta_correios VARCHAR(35) DEFAULT NULL,
    usuario_lacre VARCHAR(120) DEFAULT NULL,
    atualizado_em DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_codbar (codbar),
    KEY idx_periodo (dataexp),
    KEY idx_posto_lote (posto, lote)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");
$colsGrupoIiprAux = $pdo_controle->query("SHOW COLUMNS FROM conferencia_pacotes_lacres LIKE 'grupo_iipr'")->fetchAll();
if (count($colsGrupoIiprAux) === 0) {
    $pdo_controle->exec("ALTER TABLE conferencia_pacotes_lacres ADD COLUMN grupo_iipr VARCHAR(40) DEFAULT NULL AFTER lacre_iipr");
}
$colsGrupoCorreiosAux = $pdo_controle->query("SHOW COLUMNS FROM conferencia_pacotes_lacres LIKE 'grupo_correios'")->fetchAll();
if (count($colsGrupoCorreiosAux) === 0) {
    $pdo_controle->exec("ALTER TABLE conferencia_pacotes_lacres ADD COLUMN grupo_correios VARCHAR(40) DEFAULT NULL AFTER lacre_correios");
}
$colsGrupoIiprDesp = $pdo_controle->query("SHOW COLUMNS FROM ciDespachoLotes LIKE 'grupo_iipr'")->fetchAll();
if (count($colsGrupoIiprDesp) === 0) {
    $pdo_controle->exec("ALTER TABLE ciDespachoLotes ADD COLUMN grupo_iipr VARCHAR(40) DEFAULT NULL AFTER etiquetaiipr");
}
$colsGrupoCorreiosDesp = $pdo_controle->query("SHOW COLUMNS FROM ciDespachoLotes LIKE 'grupo_correios'")->fetchAll();
if (count($colsGrupoCorreiosDesp) === 0) {
    $pdo_controle->exec("ALTER TABLE ciDespachoLotes ADD COLUMN grupo_correios VARCHAR(40) DEFAULT NULL AFTER etiquetacorreios");
}

$pdo_servico = criarPdoLegado("10.15.61.169", "servico", (getenv('DB_USER') ?: 'controle_mat'), (getenv('DB_PASS') ?: '375256'));

$pdo_contrsos = criarPdoLegado("10.15.61.169", "contrsos", (getenv('DB_USER') ?: 'controle_mat'), (getenv('DB_PASS') ?: '375256'));

if (!isset($_SESSION)) session_start();
if (!defined('JSON_UNESCAPED_UNICODE')) define('JSON_UNESCAPED_UNICODE', 0);
if (!defined('JSON_UNESCAPED_SLASHES')) define('JSON_UNESCAPED_SLASHES', 0);

function normalizarTextoUtf8JsonSeguro($valor) {
    $valor = (string)$valor;
    if ($valor === '' || preg_match('//u', $valor)) {
        return $valor;
    }
    if (function_exists('iconv')) {
        $tmp = @iconv('UTF-8', 'UTF-8//IGNORE', $valor);
        if ($tmp !== false && $tmp !== '') return $tmp;
        $tmp = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $valor);
        if ($tmp !== false && $tmp !== '') return $tmp;
        $tmp = @iconv('Windows-1252', 'UTF-8//IGNORE', $valor);
        if ($tmp !== false && $tmp !== '') return $tmp;
    }
    if (function_exists('utf8_encode')) {
        return @utf8_encode($valor);
    }
    return $valor;
}

function normalizarDadosUtf8JsonSeguro($valor) {
    if (is_array($valor)) {
        $normalizado = array();
        foreach ($valor as $chave => $item) {
            $chaveNormalizada = is_string($chave) ? normalizarTextoUtf8JsonSeguro($chave) : $chave;
            $normalizado[$chaveNormalizada] = normalizarDadosUtf8JsonSeguro($item);
        }
        return $normalizado;
    }
    if (is_string($valor)) {
        return normalizarTextoUtf8JsonSeguro($valor);
    }
    return $valor;
}

function json_encode_legado_seguro($valor, $opcoes) {
    $normalizado = normalizarDadosUtf8JsonSeguro($valor);
    $json = json_encode($normalizado, $opcoes);
    if ($json === false) {
        $json = json_encode(array(), $opcoes);
    }
    return $json;
}

if (!isset($_SESSION['etiquetas'])) $_SESSION['etiquetas'] = array();
if (!isset($_SESSION['linhas_removidas'])) $_SESSION['linhas_removidas'] = array();
if (!isset($_SESSION['lacres_personalizados'])) $_SESSION['lacres_personalizados'] = array();
if (!isset($_SESSION['postos_manuais'])) $_SESSION['postos_manuais'] = array();
if (!isset($_SESSION['postos_cadastrados'])) $_SESSION['postos_cadastrados'] = array();
if (!isset($_SESSION['datas_filtro'])) $_SESSION['datas_filtro'] = array();
if (!isset($_SESSION['debug_log'])) $_SESSION['debug_log'] = array();
if (!isset($_SESSION['excluir_regionais_manual'])) $_SESSION['excluir_regionais_manual'] = array();

if (!isset($_SESSION['id_despacho_poupa_tempo'])) $_SESSION['id_despacho_poupa_tempo'] = 0;

// Snapshot de lacres enviado pelos formularios auxiliares
if (isset($_POST['snapshot_lacres']) && $_POST['snapshot_lacres'] !== '') {
    $tmp = json_decode($_POST['snapshot_lacres'], true);
    if (is_array($tmp)) {
        $_SESSION['snapshot_lacres_full'] = $tmp;
        $_SESSION['snapshot_lacres_by_posto'] = array();
        $_SESSION['snapshot_lacres_ativo'] = 1;
        foreach ($tmp as $chave => $vals) {
            $posto_key = $chave;
            if (strpos($chave, '|') !== false) {
                $partes = explode('|', $chave);
                $posto_key = end($partes);
            }
            $posto_key = trim((string)$posto_key);
            if ($posto_key === '') continue;

            $_SESSION['snapshot_lacres_by_posto'][$posto_key] = $vals;

            if (!isset($_SESSION['lacres_personalizados'][$posto_key])) {
                $_SESSION['lacres_personalizados'][$posto_key] = array();
            }
            if (isset($vals['lacre_iipr']) && (string)$vals['lacre_iipr'] !== '') {
                $_SESSION['lacres_personalizados'][$posto_key]['iipr'] = $vals['lacre_iipr'];
            }
            if (isset($vals['lacre_correios']) && (string)$vals['lacre_correios'] !== '') {
                $_SESSION['lacres_personalizados'][$posto_key]['correios'] = $vals['lacre_correios'];
            }
            if (isset($vals['etiqueta_correios'])) {
                $_SESSION['etiquetas'][$posto_key] = (string)$vals['etiqueta_correios'];
            }
        }
    }
}

// === v1.1.8: VERIFICAR A QUAL POSTO PERTENCE UMA ETIQUETA (AJAX) =========
if (isset($_POST['acao']) && $_POST['acao'] === 'verificar_etiqueta') {
    header('Content-Type: application/json; charset=utf-8');
    $leitura_raw = isset($_POST['leitura']) ? trim((string)$_POST['leitura']) : '';
    $leitura = preg_replace('/\D+/', '', $leitura_raw);
    if (strlen($leitura) !== 35) {
        echo json_encode(array('ok' => false, 'msg' => 'Etiqueta invalida'));
        exit;
    }
    $cep_etiq = substr($leitura, 0, 8);
    $seq_etiq  = substr($leitura, -5);
    try {
        $st = $pdo_controle->prepare(
            'SELECT posto FROM cadastroMalotes WHERE cep=? AND sequencial=? ORDER BY id DESC LIMIT 1'
        );
        $st->execute(array($cep_etiq, $seq_etiq));
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            // Fallback: só pelo CEP
            $st2 = $pdo_controle->prepare(
                'SELECT posto FROM cadastroMalotes WHERE cep=? ORDER BY id DESC LIMIT 1'
            );
            $st2->execute(array($cep_etiq));
            $row = $st2->fetch(PDO::FETCH_ASSOC);
        }
        if ($row && isset($row['posto']) && trim($row['posto']) !== '') {
            echo json_encode(array('ok' => true, 'posto' => trim($row['posto'])));
        } else {
            echo json_encode(array('ok' => true, 'posto' => null));
        }
    } catch (Exception $e) {
        echo json_encode(array('ok' => false, 'msg' => 'Erro BD'));
    }
    exit;
}

// === vX: SALVAR LACRES DO POUPA TEMPO ===================================
if (isset($_POST['acao']) && $_POST['acao'] === 'salvar_lacres_pt') {
    try {
        if (!isset($pdo_controle) || !($pdo_controle instanceof PDO)) {
            throw new Exception('PDO $pdo_controle não disponível.');
        }

        // 1) Descobrir o id do despacho do Poupa Tempo
        //    - primeiro tenta vir por POST
        //    - se não vier, pega o último despacho ativo do grupo 'POUPA TEMPO'
        //      para o usuário logado
        $id_despacho = isset($_POST['id_despacho']) ? (int)$_POST['id_despacho'] : 0;

        if ($id_despacho <= 0) {
            $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'conferencia';

            $sqlBuscaDesp = "
                SELECT id
                  FROM ciDespachos
                 WHERE grupo   = 'POUPA TEMPO'
                   AND usuario = :usuario
                   AND ativo   = 's'
              ORDER BY id DESC
                 LIMIT 1
            ";
            $stmBuscaDesp = $pdo_controle->prepare($sqlBuscaDesp);
            $stmBuscaDesp->execute(array(':usuario' => $usuario));
            $idEncontrado = $stmBuscaDesp->fetchColumn();

            if ($idEncontrado) {
                $id_despacho = (int)$idEncontrado;
            }
        }

        if ($id_despacho <= 0) {
            throw new Exception('Não foi possível localizar o despacho do Poupa Tempo. Salve o ofício Poupa Tempo antes de lançar os lacres.');
        }

        // 2) Capturar apenas lacres IIPR (Poupa Tempo não tem lacre dos Correios)
        //    Espera algo do tipo: lacre_iipr[028] = '123456', lacre_iipr[029] = '123457', ...
        $lacres_iipr = (isset($_POST['lacre_iipr']) && is_array($_POST['lacre_iipr']))
            ? $_POST['lacre_iipr']
            : array();

        if (empty($lacres_iipr)) {
            throw new Exception('Nenhum lacre IIPR foi informado para o Poupa Tempo.');
        }

        $pdo_controle->beginTransaction();

        

        $sqlSel = "
            SELECT COUNT(*)
              FROM ciDespachoItens
             WHERE idDespacho = :id_despacho
               AND codigoPosto = :posto
        ";
        $stmSel = $pdo_controle->prepare($sqlSel);

        $sqlIns = "
            INSERT INTO ciDespachoItens (idDespacho, codigoPosto, lacre_iipr, dataRegistro)
            VALUES (:id_despacho, :posto, :lacre, NOW())
        ";
        $stmIns = $pdo_controle->prepare($sqlIns);

        $sqlUpd = "
            UPDATE ciDespachoItens
               SET lacre_iipr     = :lacre,
                   dataAtualizacao = NOW()
             WHERE idDespacho = :id_despacho
               AND codigoPosto = :posto
        ";
        $stmUpd = $pdo_controle->prepare($sqlUpd);

        $totalInseridos   = 0;
        $totalAtualizados = 0;

        foreach ($lacres_iipr as $posto => $valorLacre) {
            $valorLacre = trim($valorLacre);
            if ($valorLacre === '') {
                continue;
            }

            $stmSel->execute(array(
                ':id_despacho' => $id_despacho,
                ':posto'       => $posto
            ));
            $existe = (int)$stmSel->fetchColumn();

            if ($existe) {
                $stmUpd->execute(array(
                    ':lacre'       => $valorLacre,
                    ':id_despacho' => $id_despacho,
                    ':posto'       => $posto
                ));
                $totalAtualizados += $stmUpd->rowCount();
            } else {
                $stmIns->execute(array(
                    ':id_despacho' => $id_despacho,
                    ':posto'       => $posto,
                    ':lacre'       => $valorLacre
                ));
                $totalInseridos += $stmIns->rowCount();
            }
        }

        $pdo_controle->commit();

        echo "<script>alert('Lacres do Poupa Tempo salvos com sucesso. Inseridos: "
             . (int)$totalInseridos . ", atualizados: " . (int)$totalAtualizados . "');</script>";

    } catch (Exception $e) {
        if (isset($pdo_controle) && $pdo_controle instanceof PDO && $pdo_controle->inTransaction()) {
            $pdo_controle->rollBack();
        }
        echo "<script>alert('Erro ao salvar lacres do Poupa Tempo: "
             . addslashes($e->getMessage()) . "');</script>";
    }
}



// === v1: SALVAR OFÍCIO DO POUPA TEMPO (com detalhe de lotes, data e responsáveis) ===
if (isset($_POST['acao']) && $_POST['acao'] === 'salvar_oficio_pt') {
    try {
        if (!isset($pdo_controle) || !($pdo_controle instanceof PDO)) {
            throw new Exception('PDO $pdo_controle não disponível.');
        }

        $pdo_controle->beginTransaction();

        // 1) Coleta das datas - pode vir como pt_datas ou datas_str
        $datasStr = '';
        $datasRaw = array();

        if (isset($_POST['pt_datas']) && trim($_POST['pt_datas']) !== '') {
            $datasStr = trim($_POST['pt_datas']);
        } elseif (isset($_POST['datas_str']) && trim($_POST['datas_str']) !== '') {
            $datasStr = trim($_POST['datas_str']);
        }

        if ($datasStr !== '') {
            $tmp = explode(',', $datasStr);
            foreach ($tmp as $d) {
                $d = trim($d);
                if ($d !== '') {
                    $datasRaw[] = $d;
                }
            }
        }

        if (empty($datasRaw)) {
            throw new Exception('Nenhuma data válida informada para o Poupa Tempo.');
        }

        // Normaliza as datas para formato YYYY-MM-DD
        $datasSql = array();
        foreach ($datasRaw as $d) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                // já está em YYYY-MM-DD
                $datasSql[] = $d;
            } elseif (preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/', $d, $m)) {
                // vem como DD/MM/AAAA ou DD-MM-AAAA
                $datasSql[] = $m[3] . '-' . $m[2] . '-' . $m[1];
            } else {
                // fallback
                $datasSql[] = $d;
            }
        }

        // 2) Cabeçalho em ciDespachos (UPSERT pelo hash de grupo+datas)
        // v8.14.9.3: Verificar modo (sobrescrever ou criar novo) - agora realmente funcional
        $grupo   = 'POUPA TEMPO';
        $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'conferencia';
        $modoOficio = isset($_POST['modo_oficio']) ? trim($_POST['modo_oficio']) : 'sobrescrever';
        
        $id_desp = null;
        
        // v8.14.9.3: Se modo=novo, SEMPRE criar novo registro (não buscar existente)
        if ($modoOficio === 'novo') {
            // Criar NOVO ofício (timestamp no hash garante unicidade)
            $hash = sha1($grupo . '|' . $datasStr . '|' . time() . '|' . mt_rand());
            
            $st1 = $pdo_controle->prepare("
                INSERT INTO ciDespachos (usuario, grupo, datas_str, hash_chave, ativo, obs)
                VALUES (?,?,?,?,1,?)
            ");
            $st1->execute(array($usuario, $grupo, $datasStr, $hash, null));
            $id_desp = $pdo_controle->lastInsertId();
            
        } else {
            // Modo sobrescrever: buscar ofício existente ou criar se não existir
            $hash = sha1($grupo . '|' . $datasStr);
            
            $stFind = $pdo_controle->prepare("SELECT id FROM ciDespachos WHERE hash_chave=? LIMIT 1");
            $stFind->execute(array($hash));
            $id_desp = $stFind->fetchColumn();

            if ($id_desp) {
                // Atualiza cabeçalho existente
                $stUpd = $pdo_controle->prepare("
                    UPDATE ciDespachos
                       SET usuario   = ?,
                           grupo     = ?,
                           datas_str = ?,
                           ativo     = 1,
                           obs       = NULL
                     WHERE id = ?
                ");
                $stUpd->execute(array($usuario, $grupo, $datasStr, $id_desp));

                // Limpa itens antigos
                $stDel = $pdo_controle->prepare("DELETE FROM ciDespachoItens WHERE id_despacho=?");
                $stDel->execute(array($id_desp));

                // Limpa detalhe de lotes antigo
                $stDelL = $pdo_controle->prepare("DELETE FROM ciDespachoLotes WHERE id_despacho=?");
                $stDelL->execute(array($id_desp));
            } else {
                // Cria novo cabeçalho (primeiro ofício com essas datas)
                $st1 = $pdo_controle->prepare("
                    INSERT INTO ciDespachos (usuario, grupo, datas_str, hash_chave, ativo, obs)
                    VALUES (?,?,?,?,1,?)
                ");
                $st1->execute(array($usuario, $grupo, $datasStr, $hash, null));
                $id_desp = $pdo_controle->lastInsertId();
            }
        }

        // 3) SELECT principal: SOMA por posto (igual ao modelo do ofício)
        // v8.14.9: Adicionar campo usuario de ciPostosCsv
        $placeholders = implode(',', array_fill(0, count($datasSql), '?'));

        $sqlItens = "
            SELECT 
                LPAD(c.posto,3,'0') AS codigo,
                COALESCE(r.nome, CONCAT('POUPA TEMPO - ', LPAD(c.posto,3,'0'))) AS nome,
                SUM(COALESCE(c.quantidade,0)) AS quantidade,
                r.endereco AS endereco,
                r.regional AS regional,
                MAX(c.usuario) AS usuario
            FROM ciPostosCsv c
            INNER JOIN ciRegionais r 
                    ON LPAD(r.posto,3,'0') = LPAD(c.posto,3,'0')
            WHERE DATE(c.dataCarga) IN ($placeholders)
              AND REPLACE(LOWER(r.entrega),' ','') LIKE 'poupa%tempo'
            GROUP BY 
                LPAD(c.posto,3,'0'), r.nome, r.endereco, r.regional
            ORDER BY 
                LPAD(c.posto,3,'0')
        ";

        $stmtItens = $pdo_controle->prepare($sqlItens);
        $stmtItens->execute($datasSql);

        $rows = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            throw new Exception('Nenhum posto Poupa Tempo encontrado para as datas selecionadas.');
        }

        // 4) Insere os itens do despacho (1 linha por posto)
        // v2.0.0: capturar lacre_pt, lacre_correios_pt e etiqueta_correios (display)
        //         do formulario para gravar no banco por posto.
        $postLacrePt          = (isset($_POST['lacre_pt'])           && is_array($_POST['lacre_pt']))           ? $_POST['lacre_pt']           : array();
        $postLacreCorreiosPt  = (isset($_POST['lacre_correios_pt'])  && is_array($_POST['lacre_correios_pt']))  ? $_POST['lacre_correios_pt']  : array();
        $postEtiquetaCorreios = (isset($_POST['etiqueta_correios'])  && is_array($_POST['etiqueta_correios']))  ? $_POST['etiqueta_correios']  : array();

        // v2.0.0: normalizar as chaves removendo prefixo "p_" e zeros a esquerda variaveis,
        //         mantendo formato de 3 digitos (ex.: "p_041" -> "041", "5" -> "005")
        $normalizarChaveLocal = function ($arr) {
            $out = array();
            if (!is_array($arr)) return $out;
            foreach ($arr as $k => $v) {
                $kk = (string)$k;
                if (strpos($kk, 'p_') === 0) {
                    $kk = substr($kk, 2);
                }
                $kk = ltrim($kk, '0');
                if ($kk === '') { $kk = '0'; }
                if (strlen($kk) < 3) { $kk = str_pad($kk, 3, '0', STR_PAD_LEFT); }
                $out[$kk] = $v;
            }
            return $out;
        };
        // Evita closure em ambientes PHP <5.3 — aqui PDO ja exige 5.3+
        $postLacrePtN          = $normalizarChaveLocal($postLacrePt);
        $postLacreCorreiosPtN  = $normalizarChaveLocal($postLacreCorreiosPt);
        $postEtiquetaCorreiosN = $normalizarChaveLocal($postEtiquetaCorreios);

        $stItem = $pdo_controle->prepare("
            INSERT INTO ciDespachoItens
            (id_despacho, regional, posto, nome_posto, endereco, lote, quantidade,
             lacre_iipr, lacre_correios, etiqueta_correios, usuario, incluir)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,1)
        ");

        foreach ($rows as $r) {
            $posto      = (string)$r['codigo'];       // 3 dígitos
            $nome_posto = (string)$r['nome'];
            $qtd_total  = (int)$r['quantidade'];
            $endereco   = trim((string)$r['endereco']);
            $regional   = $r['regional'];
            $usuario_posto = isset($r['usuario']) ? trim((string)$r['usuario']) : '';

            // v2.0.0: ler valores digitados no formulario PT (por posto)
            $rawLI = isset($postLacrePtN[$posto])          ? trim((string)$postLacrePtN[$posto])          : '';
            $rawLC = isset($postLacreCorreiosPtN[$posto])  ? trim((string)$postLacreCorreiosPtN[$posto])  : '';
            $rawEC = isset($postEtiquetaCorreiosN[$posto]) ? trim((string)$postEtiquetaCorreiosN[$posto]) : '';

            $lacre_iipr    = ($rawLI === '' || $rawLI === '0') ? null : $rawLI;
            $lacre_corr    = ($rawLC === '' || $rawLC === '0') ? null : $rawLC;
            $etiqueta_corr = ($rawEC === '' || $rawEC === '0') ? null : $rawEC;

            $stItem->execute(array(
                $id_desp,
                $regional,
                $posto,
                $nome_posto,
                $endereco,
                null,          // lote (só no detalhe)
                $qtd_total,
                $lacre_iipr,
                $lacre_corr,
                $etiqueta_corr,
                $usuario_posto  // v8.14.9: usuario do pacote
            ));
        }

        // 5) Detalhe por LOTE: quantidade, data de carga e responsáveis
        // IMPORTANTE: troque c.usuario pelo nome REAL da coluna onde fica o responsável
        // (ex.: c.responsavel, c.emitente, c.usuarioCriacao, etc.)
        $sqlLotes = "
            SELECT 
                LPAD(c.posto,3,'0') AS posto,
                c.lote,
                SUM(COALESCE(c.quantidade,0)) AS quantidade,
                MIN(DATE(c.dataCarga)) AS data_carga,
                GROUP_CONCAT(DISTINCT c.usuario SEPARATOR ', ') AS responsaveis
            FROM ciPostosCsv c
            INNER JOIN ciRegionais r 
                    ON LPAD(r.posto,3,'0') = LPAD(c.posto,3,'0')
            WHERE DATE(c.dataCarga) IN ($placeholders)
              AND REPLACE(LOWER(r.entrega),' ','') LIKE 'poupa%tempo'
            GROUP BY 
                LPAD(c.posto,3,'0'), c.lote
            ORDER BY 
                LPAD(c.posto,3,'0'), c.lote
        ";

        $stmtLotes = $pdo_controle->prepare($sqlLotes);
        $stmtLotes->execute($datasSql);

        $stInsLote = $pdo_controle->prepare("
            INSERT INTO ciDespachoLotes (id_despacho, posto, lote, quantidade, data_carga, responsaveis)
            VALUES (?,?,?,?,?,?)
        ");

        while ($l = $stmtLotes->fetch(PDO::FETCH_ASSOC)) {
            $stInsLote->execute(array(
                $id_desp,
                (string)$l['posto'],
                (string)$l['lote'],
                (int)$l['quantidade'],
                $l['data_carga'],          // YYYY-MM-DD
                $l['responsaveis']         // nomes concatenados
            ));
        }

        // v2.0.0: 5b) Gravar etiquetas dos displays PT em ciMalotes (tipo=1 saida)
        //         espelhando o padrao usado para Correios.
        $login_malotes_pt = '';
        if (isset($_POST['responsavel']) && trim($_POST['responsavel']) !== '') {
            $login_malotes_pt = trim($_POST['responsavel']);
        } elseif (isset($_SESSION['ultimo_responsavel']) && trim($_SESSION['ultimo_responsavel']) !== '') {
            $login_malotes_pt = trim($_SESSION['ultimo_responsavel']);
        } else {
            $login_malotes_pt = $usuario;
        }
        $data_malote_pt = date('Y-m-d');
        $todasEtiquetasPt = array();

        // FONTE 1: POST etiqueta_correios[p_POSTO] (form lacres_novo.php)
        if (!empty($postEtiquetaCorreiosN)) {
            foreach ($postEtiquetaCorreiosN as $postoEtq => $valEtq) {
                $eR = trim((string)$valEtq);
                if ($eR === '' || $eR === '0') continue;
                if (!isset($todasEtiquetasPt[$eR])) {
                    $todasEtiquetasPt[$eR] = $postoEtq;
                }
            }
        }
        // FONTE 2: ciDespachoItens.etiqueta_correios recem-gravadas (garantia)
        try {
            $stEtqItPt = $pdo_controle->prepare(
                "SELECT posto, etiqueta_correios FROM ciDespachoItens
                 WHERE id_despacho = ? AND etiqueta_correios IS NOT NULL AND etiqueta_correios <> ''"
            );
            $stEtqItPt->execute(array((int)$id_desp));
            while ($rEI = $stEtqItPt->fetch(PDO::FETCH_ASSOC)) {
                $eBD = trim((string)$rEI['etiqueta_correios']);
                if ($eBD === '' || $eBD === '0') continue;
                if (!isset($todasEtiquetasPt[$eBD])) {
                    $todasEtiquetasPt[$eBD] = trim((string)$rEI['posto']);
                }
            }
        } catch (Exception $eMalPt) { /* sem fallback */ }

        if (!empty($todasEtiquetasPt)) {
            $stmtMalotesPt = $pdo_controle->prepare(
                "INSERT INTO ciMalotes (leitura, data, observacao, login, tipo, cep, sequencial, posto)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmtDupChkPt = $pdo_controle->prepare(
                "SELECT COUNT(*) FROM ciMalotes WHERE leitura = ? AND tipo = 1 AND DATE(data) = CURDATE()"
            );
            foreach ($todasEtiquetasPt as $eti => $postoMal) {
                $eti       = (string)$eti;
                $eti_dig   = preg_replace('/\D+/', '', $eti);
                $cep_pt    = strlen($eti_dig) >= 8 ? substr($eti_dig, 0, 8) : substr($eti, 0, 8);
                $seq_pt    = strlen($eti_dig) >= 5 ? substr($eti_dig, -5)  : substr($eti, -5);
                $stmtDupChkPt->execute(array($eti));
                if ((int)$stmtDupChkPt->fetchColumn() === 0) {
                    $stmtMalotesPt->execute(array(
                        $eti, $data_malote_pt, null, $login_malotes_pt, 1, $cep_pt, $seq_pt, $postoMal
                    ));
                }
            }
        }

        // 6) Finaliza
        $pdo_controle->commit();
        echo "<script>
                alert('Ofício (Poupa Tempo) salvo. Nº " . (int)$id_desp . "');
                if (typeof marcarComoSalvo === 'function') { marcarComoSalvo(); }
                window.location.href='" . $_SERVER['PHP_SELF'] . "';
              </script>";
    } catch (Exception $e) {
        if ($pdo_controle->inTransaction()) {
            $pdo_controle->rollBack();
        }
        echo "<pre>Erro ao salvar ofício: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
    }
    exit;
}

// === SALVAR OFÍCIO DOS CORREIOS (postos com entrega = 'correios') ===
// Salva todos os postos CAPITAL, CENTRAL IIPR e REGIONAIS com lacres e etiquetas
if (isset($_POST['acao']) && $_POST['acao'] === 'salvar_oficio_correios') {
    try {
        // v8.13.3: Debug detalhado opcional via debug_lacres=1
        $debug_lacres = (isset($_GET['debug_lacres']) && $_GET['debug_lacres'] === '1') || (isset($_POST['debug_lacres']) && $_POST['debug_lacres'] === '1');
        
        // Debug: registrar que o handler foi invocado e quais dados chegaram via POST
        try { add_debug('V8.11.X - salvar_oficio_correios chamado', $_POST); } catch (Exception $e) { /* ignore */ }
        if (!isset($pdo_controle) || !($pdo_controle instanceof PDO)) {
            throw new Exception('PDO $pdo_controle nao disponivel.');
        }

        $pdo_controle->beginTransaction();

        // v8.11.1: modo de ofício (sobrescrever / novo)
        $modoOficio = '';
        if (isset($_POST['modo_oficio'])) {
            $modoOficio = $_POST['modo_oficio'];
        }

        // Recuperar ultimo ofício Correios (se houver)
        $ultimoIdDespachoCorreios = null;
        $stUlt = $pdo_controle->prepare("SELECT id FROM ciDespachos WHERE grupo = 'CORREIOS' ORDER BY id DESC LIMIT 1");
        $stUlt->execute();
        $rowUlt = $stUlt->fetch(PDO::FETCH_ASSOC);
        if ($rowUlt && isset($rowUlt['id'])) {
            $ultimoIdDespachoCorreios = (int)$rowUlt['id'];
        }

        // 1) Coleta das datas
        $datasStr = '';
        $datasRaw = array();

        if (isset($_POST['correios_datas']) && trim($_POST['correios_datas']) !== '') {
            $datasStr = trim($_POST['correios_datas']);
        } elseif (isset($_POST['datas_str']) && trim($_POST['datas_str']) !== '') {
            $datasStr = trim($_POST['datas_str']);
        }

        if ($datasStr !== '') {
            $tmp = explode(',', $datasStr);
            foreach ($tmp as $d) {
                $d = trim($d);
                if ($d !== '') {
                    $datasRaw[] = $d;
                }
            }
        }

        if (empty($datasRaw)) {
            throw new Exception('Nenhuma data valida informada para o oficio Correios.');
        }

        // Normaliza as datas para formato YYYY-MM-DD
        $datasSql = array();
        foreach ($datasRaw as $d) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                $datasSql[] = $d;
            } elseif (preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/', $d, $m)) {
                $datasSql[] = $m[3] . '-' . $m[2] . '-' . $m[1];
            } else {
                $datasSql[] = $d;
            }
        }

        // 2) Cabeçalho em ciDespachos (UPSERT pelo hash de grupo+datas)
        // v8.14.9.3: Implementar lógica "Criar Novo" vs "Sobrescrever" para Correios
        $grupo   = 'CORREIOS';
        $responsavel_post = isset($_POST['responsavel']) ? trim((string)$_POST['responsavel']) : '';
        if (!responsavelValido($responsavel_post)) {
            throw new Exception('Informe um nome de responsavel valido antes de salvar o oficio.');
        }
        $usuario = $responsavel_post;
        $_SESSION['ultimo_responsavel'] = $usuario;
        
        $id_desp = null;
        $id_sobrescrever = isset($_POST['id_oficio_sobrescrever']) ? (int)$_POST['id_oficio_sobrescrever'] : 0;
        
        // v8.14.9.3: Se modo=novo, SEMPRE criar novo registro (não buscar existente)
        if ($modoOficio === 'novo') {
            // Criar NOVO ofício (timestamp no hash garante unicidade)
            $hash = sha1($grupo . '|' . $datasStr . '|' . time() . '|' . mt_rand());
            
            $st1 = $pdo_controle->prepare("
                INSERT INTO ciDespachos (usuario, grupo, datas_str, hash_chave, ativo, obs)
                VALUES (?,?,?,?,1,?)
            ");
            $st1->execute(array($usuario, $grupo, $datasStr, $hash, null));
            $id_desp = $pdo_controle->lastInsertId();
            
        } else {
            // Modo sobrescrever: usar numero informado; se vazio, usa ultimo
            if ($id_sobrescrever > 0) {
                $stCheck = $pdo_controle->prepare("SELECT id FROM ciDespachos WHERE id = ? AND grupo = 'CORREIOS' LIMIT 1");
                $stCheck->execute(array($id_sobrescrever));
                $id_desp = $stCheck->fetchColumn();
                if (!$id_desp) {
                    throw new Exception('Numero de oficio informado nao encontrado para Correios.');
                }
            } else {
                $id_desp = $ultimoIdDespachoCorreios;
            }

            if ($id_desp) {
                $stUpd = $pdo_controle->prepare("
                    UPDATE ciDespachos
                       SET usuario   = ?,
                           grupo     = ?,
                           datas_str = ?,
                           ativo     = 1,
                           obs       = NULL
                     WHERE id = ?
                ");
                $stUpd->execute(array($usuario, $grupo, $datasStr, $id_desp));

                $stDel = $pdo_controle->prepare("DELETE FROM ciDespachoItens WHERE id_despacho=?");
                $stDel->execute(array($id_desp));
                $stDelL = $pdo_controle->prepare("DELETE FROM ciDespachoLotes WHERE id_despacho=?");
                $stDelL->execute(array($id_desp));
            } else {
                $hash = sha1($grupo . '|' . $datasStr);
                $st1 = $pdo_controle->prepare("
                    INSERT INTO ciDespachos (usuario, grupo, datas_str, hash_chave, ativo, obs)
                    VALUES (?,?,?,?,1,?)
                ");
                $st1->execute(array($usuario, $grupo, $datasStr, $hash, null));
                $id_desp = $pdo_controle->lastInsertId();
            }
        }
        
        // v8.15.9: Salvar ID do despacho na sessão para exibir número do ofício
        $_SESSION['id_despacho_correios'] = (int)$id_desp;

        // 3) Captura os dados enviados pelo formulário
        // VERSAO 3: Normaliza TODAS as chaves para formato "041" (3 digitos com zeros)
        // Isso garante consistencia entre todos os arrays, independente do formato original
        
        // Funcao auxiliar para normalizar chaves de array
        function normalizarChavesPosto($array) {
            $resultado = array();
            if (!is_array($array)) return $resultado;
            foreach ($array as $chave => $valor) {
                // Remove prefixo "p_" se existir
                $chave_str = (string)$chave;
                if (strpos($chave_str, 'p_') === 0) {
                    $chave_str = substr($chave_str, 2);
                }
                // Remove caracteres nao-numericos e adiciona padding
                $chave_normalizada = str_pad(preg_replace('/\D+/', '', $chave_str), 3, '0', STR_PAD_LEFT);
                $resultado[$chave_normalizada] = $valor;
            }
            return $resultado;
        }
        
        $lacres_iipr_raw = isset($_POST['lacre_iipr']) && is_array($_POST['lacre_iipr']) ? $_POST['lacre_iipr'] : array();
        $lacres_correios_raw = isset($_POST['lacre_correios']) && is_array($_POST['lacre_correios']) ? $_POST['lacre_correios'] : array();
        $etiquetas_raw = isset($_POST['etiqueta_correios']) && is_array($_POST['etiqueta_correios']) ? $_POST['etiqueta_correios'] : array();
        $nomes_postos_raw = isset($_POST['nome_posto']) && is_array($_POST['nome_posto']) ? $_POST['nome_posto'] : array();
        $grupos_postos_raw = isset($_POST['grupo_posto']) && is_array($_POST['grupo_posto']) ? $_POST['grupo_posto'] : array();
        
        // Normalizar TODAS as chaves
        $lacres_iipr = normalizarChavesPosto($lacres_iipr_raw);
        $lacres_correios = normalizarChavesPosto($lacres_correios_raw);
        $etiquetas = normalizarChavesPosto($etiquetas_raw);
        $nomes_postos = normalizarChavesPosto($nomes_postos_raw);
        $grupos_postos = normalizarChavesPosto($grupos_postos_raw);

        // 4) Buscar dados complementares do banco (regional, endereco, quantidade)
        $placeholders = implode(',', array_fill(0, count($datasSql), '?'));

        // Query para buscar quantidade total por posto (apenas Correios)
        $sqlQtd = "
            SELECT 
                LPAD(c.posto,3,'0') AS codigo,
                COALESCE(r.nome, CONCAT('Posto ', LPAD(c.posto,3,'0'))) AS nome,
                SUM(COALESCE(c.quantidade,0)) AS quantidade,
                r.endereco AS endereco,
                r.regional AS regional
            FROM ciPostosCsv c
            INNER JOIN ciRegionais r 
                    ON LPAD(r.posto,3,'0') = LPAD(c.posto,3,'0')
            WHERE DATE(c.dataCarga) IN ($placeholders)
              AND LOWER(TRIM(r.entrega)) = 'correios'
            GROUP BY 
                LPAD(c.posto,3,'0'), r.nome, r.endereco, r.regional
            ORDER BY 
                LPAD(c.posto,3,'0')
        ";

        $stmtQtd = $pdo_controle->prepare($sqlQtd);
        $stmtQtd->execute($datasSql);

        $dadosBanco = array();
        while ($row = $stmtQtd->fetch(PDO::FETCH_ASSOC)) {
            $dadosBanco[$row['codigo']] = $row;
        }

        // v8.12: Para o fluxo CORREIOS, não gravamos em ciDespachoItens
        // ciDespachoItens será usado apenas para Poupa Tempo
        // $stItem foi removido (não necessário para Correios)
        // O trecho anterior que preparava INSERT em ciDespachoItens foi comentado/removido

        // 6) Salvar lotes por posto (todos os lotes das datas selecionadas, apenas Correios)
        $sqlLotes = "
            SELECT 
                LPAD(c.posto,3,'0') AS posto,
                c.lote,
                SUM(COALESCE(c.quantidade,0)) AS quantidade,
                MIN(DATE(c.dataCarga)) AS data_carga,
                GROUP_CONCAT(DISTINCT c.usuario SEPARATOR ', ') AS responsaveis,
                r.regional AS regional
            FROM ciPostosCsv c
            INNER JOIN ciRegionais r 
                    ON LPAD(r.posto,3,'0') = LPAD(c.posto,3,'0')
            WHERE DATE(c.dataCarga) IN ($placeholders)
              AND LOWER(TRIM(r.entrega)) = 'correios'
            GROUP BY 
                LPAD(c.posto,3,'0'), c.lote
            ORDER BY 
                LPAD(c.posto,3,'0'), c.lote
        ";

        $stmtLotes = $pdo_controle->prepare($sqlLotes);
        $stmtLotes->execute($datasSql);

        $mapaLacresPorLote = array();
        try {
            $stmtLacresLote = $pdo_controle->prepare("SELECT codbar, lote, regional, posto, dataexp, lacre_iipr, grupo_iipr, lacre_correios, grupo_correios, etiqueta_correios
                FROM conferencia_pacotes_lacres
                WHERE dataexp IN ($placeholders)");
            $stmtLacresLote->execute($datasSql);
            while ($rowLoteLacre = $stmtLacresLote->fetch(PDO::FETCH_ASSOC)) {
                $loteMap = str_pad(preg_replace('/\D+/', '', (string)$rowLoteLacre['lote']), 8, '0', STR_PAD_LEFT);
                $postoMap = str_pad(preg_replace('/\D+/', '', (string)$rowLoteLacre['posto']), 3, '0', STR_PAD_LEFT);
                $dataMap = isset($rowLoteLacre['dataexp']) ? trim((string)$rowLoteLacre['dataexp']) : '';
                $registroMap = array(
                    'lacre_iipr' => isset($rowLoteLacre['lacre_iipr']) && $rowLoteLacre['lacre_iipr'] !== null ? (int)$rowLoteLacre['lacre_iipr'] : 0,
                    'grupo_iipr' => isset($rowLoteLacre['grupo_iipr']) ? trim((string)$rowLoteLacre['grupo_iipr']) : '',
                    'lacre_correios' => isset($rowLoteLacre['lacre_correios']) && $rowLoteLacre['lacre_correios'] !== null ? (int)$rowLoteLacre['lacre_correios'] : 0,
                    'grupo_correios' => isset($rowLoteLacre['grupo_correios']) ? trim((string)$rowLoteLacre['grupo_correios']) : '',
                    'etiqueta_correios' => isset($rowLoteLacre['etiqueta_correios']) ? trim((string)$rowLoteLacre['etiqueta_correios']) : ''
                );
                $mapaLacresPorLote[$postoMap . '|' . $loteMap . '|' . $dataMap] = $registroMap;
                $mapaLacresPorLote[$postoMap . '|' . $loteMap] = $registroMap;
            }
        } catch (Exception $e) {
            $mapaLacresPorLote = array();
        }

        // v8.13: USAR SNAPSHOT JSON COMO FONTE ÚNICA DE VERDADE
        // O snapshot contém o estado exato da grade no momento do salvamento
        $snapshot = array();
        if (isset($_POST['snapshot_oficio']) && $_POST['snapshot_oficio'] !== '') {
            $tmp = json_decode($_POST['snapshot_oficio'], true);
            if (is_array($tmp)) {
                $snapshot = $tmp;
            }
        }

        // v8.13: Montar mapas a partir do SNAPSHOT (prioridade máxima)
        $mapaCapital = array();   // posto => lacres/etiqueta
        $mapaCentral = array();   // posto => lacres/etiqueta
        $mapaRegional = array();  // regional => lacres/etiqueta
        $mapaManual = array();    // posto_manual => lacres/etiqueta

        if (!empty($snapshot)) {
            // v8.13: Processar snapshot (fonte única de verdade)
            foreach ($snapshot as $linha) {
                $posto_raw = isset($linha['posto']) ? trim((string)$linha['posto']) : '';
                if ($posto_raw === '') continue;

                if (preg_match('/^M/i', $posto_raw)) {
                    $posto = $posto_raw; // posto manual
                } else {
                    $posto = str_pad(preg_replace('/\D+/', '', $posto_raw), 3, '0', STR_PAD_LEFT);
                }

                $grupo = isset($linha['grupo']) ? trim((string)$linha['grupo']) : '';
                $regional_raw = isset($linha['regional']) ? trim((string)$linha['regional']) : '0';
                $regional = ltrim($regional_raw, '0');
                if ($regional === '') $regional = '0';

                $lacreI = isset($linha['lacre_iipr']) ? trim((string)$linha['lacre_iipr']) : '';
                $lacreC = isset($linha['lacre_correios']) ? trim((string)$linha['lacre_correios']) : '';
                $etiq = isset($linha['etiqueta_correios']) ? trim((string)$linha['etiqueta_correios']) : '';

                if (!isset($_SESSION['lacres_personalizados'][$posto])) {
                    $_SESSION['lacres_personalizados'][$posto] = array();
                }
                if ($lacreI !== '') {
                    $_SESSION['lacres_personalizados'][$posto]['iipr'] = $lacreI;
                }
                if ($lacreC !== '') {
                    $_SESSION['lacres_personalizados'][$posto]['correios'] = $lacreC;
                }
                if ($etiq !== '') {
                    $_SESSION['etiquetas'][$posto] = $etiq;
                }

                $dados = array(
                    'lacre_iipr' => ($lacreI === '' ? 0 : extrairUltimoLacreSequencial($lacreI)),
                    'lacre_correios' => ($lacreC === '' ? 0 : extrairUltimoLacreSequencial($lacreC)),
                    'etiqueta_correios' => ($etiq === '' ? null : $etiq),
                );

                if (preg_match('/^M/i', $posto)) {
                    $mapaManual[$posto] = $dados;
                } elseif ($grupo === 'CAPITAL') {
                    $mapaCapital[$posto] = $dados;
                } elseif ($grupo === 'CENTRAL IIPR') {
                    $mapaCentral[$posto] = $dados;
                } elseif ($grupo === 'REGIONAIS' && $regional !== '0') {
                    $mapaRegional[$regional] = $dados;
                }
            }

            add_debug('V8.13.1 - SNAPSHOT recebido e processado', array(
                'total_linhas' => count($snapshot),
                'capital' => array_keys($mapaCapital),
                'capital_lacres' => $mapaCapital,
                'central' => array_keys($mapaCentral),
                'central_lacres' => $mapaCentral,
                'regionais' => array_keys($mapaRegional),
                'regionais_lacres' => $mapaRegional,
            ));
            
            // v8.13.3: Debug detalhado de lacres quando debug_lacres=1
            if ($debug_lacres) {
                echo "<pre style='background:#f0f0f0;padding:20px;border:2px solid #333;margin:20px;'>";
                echo "<h3>DEBUG v8.13.3 - MAPAS DE LACRES ANTES DE GRAVAR</h3>\n\n";
                echo "<h4>CAPITAL (" . count($mapaCapital) . " postos):</h4>\n";
                foreach ($mapaCapital as $posto => $dados) {
                    echo "Posto $posto: IIPR=" . $dados['lacre_iipr'] . ", Correios=" . $dados['lacre_correios'] . ", Etiqueta=" . ($dados['etiqueta_correios'] ? substr($dados['etiqueta_correios'], 0, 10) . '...' : 'NULL') . "\n";
                }
                echo "\n<h4>CENTRAL IIPR (" . count($mapaCentral) . " postos):</h4>\n";
                foreach ($mapaCentral as $posto => $dados) {
                    echo "Posto $posto: IIPR=" . $dados['lacre_iipr'] . ", Correios=" . $dados['lacre_correios'] . ", Etiqueta=" . ($dados['etiqueta_correios'] ? substr($dados['etiqueta_correios'], 0, 10) . '...' : 'NULL') . "\n";
                }
                echo "\n<h4>REGIONAIS (" . count($mapaRegional) . " regionais):</h4>\n";
                foreach ($mapaRegional as $regional => $dados) {
                    echo "Regional $regional: IIPR=" . $dados['lacre_iipr'] . ", Correios=" . $dados['lacre_correios'] . ", Etiqueta=" . ($dados['etiqueta_correios'] ? substr($dados['etiqueta_correios'], 0, 10) . '...' : 'NULL') . "\n";
                }
                echo "\n<small>Para desativar este debug, remova ?debug_lacres=1 da URL</small>";
                echo "</pre>";
            }
        } else {
            // v8.13.1: FALLBACK para arrays antigos (se snapshot não existir)
            add_debug('V8.13.1 - FALLBACK: snapshot vazio, usando arrays antigos');
            $mapaLacresPorPosto = array();

        // Se o formulário forneceu arrays alinhados, usá-los (prioridade)
        $postosLacres_post = isset($_POST['posto_lacres']) && is_array($_POST['posto_lacres']) ? $_POST['posto_lacres'] : array();
        if (!empty($postosLacres_post)) {
            $lacresIIPR_post = isset($_POST['lacre_iipr']) && is_array($_POST['lacre_iipr']) ? $_POST['lacre_iipr'] : array();
            $lacresCorreios_post = isset($_POST['lacre_correios']) && is_array($_POST['lacre_correios']) ? $_POST['lacre_correios'] : array();
            $etiquetasCorreios_post = isset($_POST['etiqueta_correios']) && is_array($_POST['etiqueta_correios']) ? $_POST['etiqueta_correios'] : array();
            $gruposLacres_post = isset($_POST['grupo_lacres']) && is_array($_POST['grupo_lacres']) ? $_POST['grupo_lacres'] : array();

            foreach ($postosLacres_post as $idx => $postoRaw) {
                $postoCodigo = str_pad(preg_replace('/\D+/', '', (string)$postoRaw), 3, '0', STR_PAD_LEFT);
                if ($postoCodigo === '') continue;
                $lacreI = isset($lacresIIPR_post[$idx]) ? trim((string)$lacresIIPR_post[$idx]) : '';
                $lacreC = isset($lacresCorreios_post[$idx]) ? trim((string)$lacresCorreios_post[$idx]) : '';
                $eti = isset($etiquetasCorreios_post[$idx]) ? trim((string)$etiquetasCorreios_post[$idx]) : '';
                $grupo = isset($gruposLacres_post[$idx]) ? trim((string)$gruposLacres_post[$idx]) : '';

                $mapaLacresPorPosto[$postoCodigo] = array(
                    'lacre_iipr' => ($lacreI === '' ? 0 : extrairUltimoLacreSequencial($lacreI)),
                    'lacre_correios' => ($lacreC === '' ? 0 : extrairUltimoLacreSequencial($lacreC)),
                    'etiqueta_correios' => ($eti === '' ? null : $eti),
                    'grupo' => $grupo,
                );
            }
        } else {
            // Fallback: usar arrays nomeados (associativos) já normalizados anteriormente
            $todosOsPostos = array_unique(
                array_merge(
                    array_keys($lacres_iipr),
                    array_keys($lacres_correios),
                    array_keys($etiquetas)
                )
            );

            foreach ($todosOsPostos as $postoCodigo) {
                $postoCodigo = (string)$postoCodigo;
                $lacreIIPR = isset($lacres_iipr[$postoCodigo]) ? trim((string)$lacres_iipr[$postoCodigo]) : '';
                $lacreCorreios = isset($lacres_correios[$postoCodigo]) ? trim((string)$lacres_correios[$postoCodigo]) : '';
                $etiquetaCorr = isset($etiquetas[$postoCodigo]) ? trim((string)$etiquetas[$postoCodigo]) : '';

                $mapaLacresPorPosto[$postoCodigo] = array(
                    'lacre_iipr' => ($lacreIIPR === '' ? 0 : extrairUltimoLacreSequencial($lacreIIPR)),
                    'lacre_correios' => ($lacreCorreios === '' ? 0 : extrairUltimoLacreSequencial($lacreCorreios)),
                    'etiqueta_correios' => $etiquetaCorr !== '' ? $etiquetaCorr : null,
                    'grupo' => '', // grupo desconhecido no fallback
                );
            }
        }

        // Preservar lacres/etiquetas na sessao apos salvar
        if (!empty($snapshot)) {
            foreach ($snapshot as $linha) {
                $posto_raw = isset($linha['posto']) ? trim((string)$linha['posto']) : '';
                if ($posto_raw === '') continue;

                if (preg_match('/^M/i', $posto_raw)) {
                    $posto = $posto_raw;
                } else {
                    $posto = str_pad(preg_replace('/\D+/', '', $posto_raw), 3, '0', STR_PAD_LEFT);
                }

                $lacreI = isset($linha['lacre_iipr']) ? trim((string)$linha['lacre_iipr']) : '';
                $lacreC = isset($linha['lacre_correios']) ? trim((string)$linha['lacre_correios']) : '';
                $etiq = isset($linha['etiqueta_correios']) ? trim((string)$linha['etiqueta_correios']) : '';

                if (!isset($_SESSION['lacres_personalizados'][$posto])) {
                    $_SESSION['lacres_personalizados'][$posto] = array();
                }
                if ($lacreI !== '') {
                    $_SESSION['lacres_personalizados'][$posto]['iipr'] = $lacreI;
                }
                if ($lacreC !== '') {
                    $_SESSION['lacres_personalizados'][$posto]['correios'] = $lacreC;
                }
                if ($etiq !== '') {
                    $_SESSION['etiquetas'][$posto] = $etiq;
                }
            }
        }

        // v8.12: Criar MAPA DE LACRES POR REGIONAL (robusto)
        $mapaLacresPorRegional = array();
        $regionaisLacres_post = isset($_POST['regional_lacres']) && is_array($_POST['regional_lacres']) ? $_POST['regional_lacres'] : array();
        if (!empty($postosLacres_post) && !empty($regionaisLacres_post)) {
            foreach ($postosLacres_post as $idx => $postoRaw) {
                $regional_raw = isset($regionaisLacres_post[$idx]) ? trim((string)$regionaisLacres_post[$idx]) : '';
                // normalizar removendo zeros à esquerda
                $regional = ltrim($regional_raw, '0');
                if ($regional === '') {
                    // se não houver regional explícita, pular
                    continue;
                }

                $lacreI = isset($lacresIIPR_post[$idx]) ? trim((string)$lacresIIPR_post[$idx]) : '';
                $lacreC = isset($lacresCorreios_post[$idx]) ? trim((string)$lacresCorreios_post[$idx]) : '';
                $eti = isset($etiquetasCorreios_post[$idx]) ? trim((string)$etiquetasCorreios_post[$idx]) : '';

                if ($lacreI === '' && $lacreC === '' && $eti === '') {
                    continue;
                }

                if (!isset($mapaLacresPorRegional[$regional])) {
                    $mapaLacresPorRegional[$regional] = array(
                        'lacre_iipr' => 0,
                        'lacre_correios' => 0,
                        'etiqueta_correios' => null,
                    );
                }
                if ($lacreI !== '') {
                    $mapaLacresPorRegional[$regional]['lacre_iipr'] = extrairUltimoLacreSequencial($lacreI);
                }
                if ($lacreC !== '') {
                    $mapaLacresPorRegional[$regional]['lacre_correios'] = extrairUltimoLacreSequencial($lacreC);
                }
                if ($eti !== '') {
                    $mapaLacresPorRegional[$regional]['etiqueta_correios'] = $eti;
                }
            }
        }

            // v8.13.1 FALLBACK: Criar mapas separados por grupo (se snapshot não existiu)
            foreach ($mapaLacresPorPosto as $postoKey => $vals) {
                $grupoLinha = isset($vals['grupo']) ? trim((string)$vals['grupo']) : '';
                if ($grupoLinha === 'CAPITAL') {
                    $mapaCapital[$postoKey] = $vals;
                } elseif ($grupoLinha === 'CENTRAL IIPR') {
                    $mapaCentral[$postoKey] = $vals;
                }
            }
            
            // Criar mapaRegional a partir de mapaLacresPorRegional
            $mapaRegional = $mapaLacresPorRegional;

            add_debug('V8.13.1 FALLBACK - MAPA CAPITAL', array('postos' => array_keys($mapaCapital), 'dados' => $mapaCapital));
            add_debug('V8.13.1 FALLBACK - MAPA CENTRAL', array('postos' => array_keys($mapaCentral), 'dados' => $mapaCentral));
            add_debug('V8.13.1 FALLBACK - MAPA REGIONAL', array('regionais' => array_keys($mapaRegional), 'dados' => $mapaRegional));
        }

        // v8.6: Atualizar SQL do INSERT para incluir campos de lacres
        $stInsLote = $pdo_controle->prepare("
            INSERT INTO ciDespachoLotes (id_despacho, posto, lote, quantidade, data_carga, responsaveis, etiquetaiipr, grupo_iipr, etiquetacorreios, grupo_correios, etiqueta_correios)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");

        // VERSAO 6: Debug MELHORADO - registrar etiquetas recebidas
        // NOTA: A coluna etiqueta_correios no banco DEVE ser VARCHAR(35), nao INT
        // Se os valores estiverem zerados, execute:
        // ALTER TABLE ciDespachoLotes MODIFY etiqueta_correios VARCHAR(35);
        
        // DEBUG V6: Registrar todas as etiquetas recebidas do POST
        add_debug('V6 - Etiquetas RAW do POST', $etiquetas_raw);
        add_debug('V6 - Etiquetas NORMALIZADAS', $etiquetas);
        
        // v8.6: Debug do mapa de lacres por posto
        add_debug('V8.6 - MAPA DE LACRES POR POSTO', $mapaLacresPorPosto);
        
        // Usar as etiquetas ja normalizadas (capturadas e normalizadas no passo 3)
        // A variavel $etiquetas ja contem as chaves no formato "041" (3 digitos)
        $totalLotes = 0;
        $etiquetas_debug = array();
        $lotes_processados = array();
        
        while ($l = $stmtLotes->fetch(PDO::FETCH_ASSOC)) {
            // O posto do lote ja vem com LPAD do SQL (ex: "041")
            $posto_lote = (string)$l['posto'];
            // v8.10: Capturar a regional do lote (normalizar removendo zeros à esquerda)
            $regional_lote_raw = isset($l['regional']) ? trim((string)$l['regional']) : '';
            $regional_lote = ltrim($regional_lote_raw, '0');
            if ($regional_lote === '') { $regional_lote = '0'; }
            
            // VERSAO 6: Buscar etiqueta_correios correspondente ao posto
            // Tentar todas as variações de chave possíveis
            $etiqueta_do_posto = '';
            
            // Tentar com chave normalizada (3 dígitos)
            if (isset($etiquetas[$posto_lote])) {
                $etiqueta_do_posto = trim((string)$etiquetas[$posto_lote]);
            }
            // Tentar com chave sem zeros à esquerda
            if (empty($etiqueta_do_posto)) {
                $posto_sem_zeros = ltrim($posto_lote, '0');
                if (isset($etiquetas[$posto_sem_zeros])) {
                    $etiqueta_do_posto = trim((string)$etiquetas[$posto_sem_zeros]);
                }
            }
            // Tentar com prefixo p_
            if (empty($etiqueta_do_posto)) {
                $posto_com_p = 'p_' . $posto_lote;
                if (isset($etiquetas_raw[$posto_com_p])) {
                    $etiqueta_do_posto = trim((string)$etiquetas_raw[$posto_com_p]);
                }
            }
            
            // Debug: registrar etiquetas para log
            $lotes_processados[$posto_lote . '_' . $l['lote']] = array(
                'posto' => $posto_lote,
                'lote' => $l['lote'],
                'regional' => $regional_lote,
                'etiqueta_encontrada' => $etiqueta_do_posto,
                'chaves_tentadas' => array($posto_lote, ltrim($posto_lote, '0'), 'p_' . $posto_lote)
            );
            
            if (!empty($etiqueta_do_posto)) {
                $etiquetas_debug[$posto_lote] = $etiqueta_do_posto;
            }
            
            // v8.13.3: Recuperar lacres EXCLUSIVAMENTE do snapshot (valores EXATOS dos inputs)
            // 1º: CAPITAL (apenas postos visíveis em $mapaCapital - sem expansão)
            // 2º: CENTRAL IIPR (apenas postos visíveis em $mapaCentral - sem expansão)
            // 3º: REGIONAIS (expande todos os postos da regional, usa lacres do snapshot)
            // CRÍTICO: lacre_iipr e lacre_correios DEVEM ser valores distintos e corretos
            $lacreIIPR_lote = 0;
            $grupoIIPR_lote = null;
            $lacreCorreios_lote = 0;
            $grupoCorreios_lote = null;
            $etiquetaCorreios_lote = null;

            $aplicar_mapa = false;
            $origem_lacre = ''; // Para debug
            
            // Prioridade 1: posto visível em CAPITAL (valores EXATOS do input)
            if (isset($mapaCapital[$posto_lote])) {
                $lacreIIPR_lote       = (int)$mapaCapital[$posto_lote]['lacre_iipr'];
                $lacreCorreios_lote   = (int)$mapaCapital[$posto_lote]['lacre_correios'];
                $etiquetaCorreios_lote = $mapaCapital[$posto_lote]['etiqueta_correios'];
                $aplicar_mapa = true;
                $origem_lacre = 'CAPITAL';
            }
            // Prioridade 2: posto visível em CENTRAL IIPR (valores EXATOS do input)
            elseif (isset($mapaCentral[$posto_lote])) {
                $lacreIIPR_lote       = (int)$mapaCentral[$posto_lote]['lacre_iipr'];
                $lacreCorreios_lote   = (int)$mapaCentral[$posto_lote]['lacre_correios'];
                $etiquetaCorreios_lote = $mapaCentral[$posto_lote]['etiqueta_correios'];
                $aplicar_mapa = true;
                $origem_lacre = 'CENTRAL';
            }
            // Prioridade 3: REGIONAIS - expande postos da regional (valores EXATOS do input)
            elseif ($regional_lote !== '' && $regional_lote !== '0' && isset($mapaRegional[$regional_lote])) {
                $lacreIIPR_lote       = (int)$mapaRegional[$regional_lote]['lacre_iipr'];
                $lacreCorreios_lote   = (int)$mapaRegional[$regional_lote]['lacre_correios'];
                $etiquetaCorreios_lote = $mapaRegional[$regional_lote]['etiqueta_correios'];
                $aplicar_mapa = true;
                $origem_lacre = 'REGIONAL:' . $regional_lote;
            }
            // Fallback: usar valores da sessao por posto
            elseif (isset($_SESSION['lacres_personalizados'][$posto_lote])) {
                $lp = $_SESSION['lacres_personalizados'][$posto_lote];
                if (isset($lp['iipr'])) {
                    $lacreIIPR_lote = (int)$lp['iipr'];
                }
                if (isset($lp['correios'])) {
                    $lacreCorreios_lote = (int)$lp['correios'];
                }
                if (isset($_SESSION['etiquetas'][$posto_lote])) {
                    $etiquetaCorreios_lote = $_SESSION['etiquetas'][$posto_lote];
                }
                if ($lacreIIPR_lote > 0 || $lacreCorreios_lote > 0 || !empty($etiquetaCorreios_lote)) {
                    $aplicar_mapa = true;
                    $origem_lacre = 'SESSAO:' . $posto_lote;
                }
            }

            $chaveLacreLoteData = $posto_lote . '|' . (string)$l['lote'] . '|' . $l['data_carga'];
            $chaveLacreLote = $posto_lote . '|' . (string)$l['lote'];
            $lacreDetalhado = null;
            if (isset($mapaLacresPorLote[$chaveLacreLoteData])) {
                $lacreDetalhado = $mapaLacresPorLote[$chaveLacreLoteData];
            } elseif (isset($mapaLacresPorLote[$chaveLacreLote])) {
                $lacreDetalhado = $mapaLacresPorLote[$chaveLacreLote];
            }
            if (is_array($lacreDetalhado)) {
                $lacreIiprDetalhado = isset($lacreDetalhado['lacre_iipr']) ? extrairUltimoLacreSequencial($lacreDetalhado['lacre_iipr']) : 0;
                if ($lacreIiprDetalhado > 0) {
                    $lacreIIPR_lote = $lacreIiprDetalhado;
                    $grupoIIPR_lote = isset($lacreDetalhado['grupo_iipr']) ? trim((string)$lacreDetalhado['grupo_iipr']) : null;
                    $aplicar_mapa = true;
                    $origem_lacre = 'CHIPS:IIPR';
                }
                $lacreCorreiosDetalhado = isset($lacreDetalhado['lacre_correios']) ? extrairUltimoLacreSequencial($lacreDetalhado['lacre_correios']) : 0;
                if ($lacreCorreiosDetalhado > 0) {
                    $lacreCorreios_lote = $lacreCorreiosDetalhado;
                    $grupoCorreios_lote = isset($lacreDetalhado['grupo_correios']) ? trim((string)$lacreDetalhado['grupo_correios']) : null;
                    $aplicar_mapa = true;
                    $origem_lacre = 'CHIPS:CORREIOS';
                }
                if (isset($lacreDetalhado['etiqueta_correios']) && trim((string)$lacreDetalhado['etiqueta_correios']) !== '') {
                    $etiquetaCorreios_lote = trim((string)$lacreDetalhado['etiqueta_correios']);
                    $aplicar_mapa = true;
                    $origem_lacre = 'CHIPS:ETIQUETA';
                }
            }
            
            // v8.14.0: Validação CRÍTICA - lacres NUNCA podem ser iguais (exceto CENTRAL entre si)
            // CAPITAL e REGIONAIS: IIPR ≠ Correios SEMPRE por posto
            // CENTRAL IIPR: Correios pode ser igual entre postos (todos usam último+1)
            if ($aplicar_mapa && $lacreIIPR_lote > 0 && $lacreCorreios_lote > 0) {
                if ($lacreIIPR_lote === $lacreCorreios_lote) {
                    // CRITICAL: lacres duplicados detectados - corrigir SEMPRE
                    if ($debug_lacres) {
                        echo "<div style='background:#ff6b6b;color:white;padding:10px;margin:10px;font-weight:bold;'>ERRO CORRIGIDO: Posto $posto_lote ($origem_lacre) tinha IIPR=$lacreIIPR_lote IGUAL Correios=$lacreCorreios_lote - AUTO-CORRIGIDO para Correios=" . ($lacreIIPR_lote + 1) . "</div>";
                    }
                    // SEMPRE corrigir: Correios = IIPR + 1 (regra universal)
                    $lacreCorreios_lote = $lacreIIPR_lote + 1;
                }
            }
            
            // Se nenhum mapa tiver dados, NÃO inserir este lote (postos não visíveis)
            if (!$aplicar_mapa) {
                continue;
            }

            // VERSAO 6: Garantir que etiqueta seja passada como STRING pura
            $etiqueta_para_banco = (string)$etiqueta_do_posto;
            if (empty($etiquetaCorreios_lote) && $etiqueta_para_banco !== '') {
                $etiquetaCorreios_lote = $etiqueta_para_banco;
            }
            
            // v8.13.3: Debug detalhado quando debug_lacres=1
            if ($debug_lacres && $totalLotes < 10) {
                echo "<div style='background:#e8f5e9;padding:5px;margin:2px;font-family:monospace;font-size:11px;'>";
                echo "Lote #" . ($totalLotes + 1) . ": Posto=$posto_lote, Regional=$regional_lote, ";
                echo "Origem=$origem_lacre, ";
                echo "<b>IIPR=$lacreIIPR_lote</b>, <b>Correios=$lacreCorreios_lote</b>, ";
                echo "Etiqueta=" . ($etiquetaCorreios_lote ? substr($etiquetaCorreios_lote, 0, 15) . '...' : 'NULL');
                echo "</div>";
            }
            
            // v8.10: Debug por lote antes de inserir
            // (registra apenas primeiras 5 linhas para não sobrecarregar o debug_log)
            if ($totalLotes < 5) {
            add_debug('V8.13.3 - LOTE A GRAVAR', array(
                'posto_lote'           => $posto_lote,
                'regional_lote_raw'    => $regional_lote_raw,
                'regional_lote_norm'   => $regional_lote,
                'origem_lacre'         => $origem_lacre,
                'lacreIIPR_lote'       => $lacreIIPR_lote,
                'grupoIIPR_lote'       => $grupoIIPR_lote,
                'lacreCorreios_lote'   => $lacreCorreios_lote,
                'grupoCorreios_lote'   => $grupoCorreios_lote,
                'etiquetaCorreios_lote' => $etiquetaCorreios_lote,
                'existe_em_mapaCapital'  => isset($mapaCapital[$posto_lote]),
                'existe_em_mapaCentral'  => isset($mapaCentral[$posto_lote]),
                'existe_em_mapaRegional' => isset($mapaRegional[$regional_lote]),
            ));
            }
            
            // v8.13.3: Passar os 3 campos de lacres ao INSERT com cast explícito
            // CRÍTICO: etiquetaiipr e etiquetacorreios devem ser INT distintos
            $stInsLote->execute(array(
                $id_desp,
                $posto_lote,
                (string)$l['lote'],
                (int)$l['quantidade'],
                $l['data_carga'],
                $l['responsaveis'],
                (int)$lacreIIPR_lote,          // etiquetaiipr (INT)
                $grupoIIPR_lote,
                (int)$lacreCorreios_lote,      // etiquetacorreios (INT) - NUNCA igual a lacre_iipr quando deveria ser diferente
                $grupoCorreios_lote,
                $etiquetaCorreios_lote         // etiqueta_correios (VARCHAR 35 dígitos)
            ));
            // v8.3: Registra dados do lote gravado para contar postos distintos
            if (!isset($lotes_processados_dados)) { $lotes_processados_dados = array(); }
            $lotes_processados_dados[] = array('posto' => $posto_lote, 'lote' => $l['lote']);
            $totalLotes++;
        }

        // Inserir postos manuais (nao existem em ciPostosCsv)
        if (!empty($mapaManual)) {
            $data_manual = !empty($datasSql) ? $datasSql[0] : date('Y-m-d');
            foreach ($mapaManual as $posto_manual => $dados_manual) {
                $lacreI = isset($dados_manual['lacre_iipr']) ? (int)$dados_manual['lacre_iipr'] : 0;
                $lacreC = isset($dados_manual['lacre_correios']) ? (int)$dados_manual['lacre_correios'] : 0;
                $etiqM = isset($dados_manual['etiqueta_correios']) ? $dados_manual['etiqueta_correios'] : null;
                if (empty($etiqM) && isset($_SESSION['etiquetas'][$posto_manual])) {
                    $etiqM = $_SESSION['etiquetas'][$posto_manual];
                }
                if ($lacreI <= 0 && $lacreC <= 0 && empty($etiqM)) {
                    continue;
                }
                $quant_manual = 1;
                if (isset($_SESSION['postos_manuais'][$posto_manual]['quantidade'])) {
                    $quant_manual = (int)$_SESSION['postos_manuais'][$posto_manual]['quantidade'];
                    if ($quant_manual <= 0) { $quant_manual = 1; }
                }

                $stInsLote->execute(array(
                    $id_desp,
                    $posto_manual,
                    'MANUAL',
                    $quant_manual,
                    $data_manual,
                    $usuario,
                    $lacreI,
                    null,
                    $lacreC,
                    null,
                    $etiqM
                ));

                if (!isset($lotes_processados_dados)) { $lotes_processados_dados = array(); }
                $lotes_processados_dados[] = array('posto' => $posto_manual, 'lote' => 'MANUAL');
                $totalLotes++;
            }
        }
        
        // Registrar debug das etiquetas
        add_debug('V6 - Lotes processados', $lotes_processados);
        if (!empty($etiquetas_debug)) {
            add_debug('V6 - Etiquetas salvas', $etiquetas_debug);
        } else {
            add_debug('V6 - AVISO: Nenhuma etiqueta foi associada aos lotes', array(
                'total_lotes' => $totalLotes,
                'etiquetas_disponiveis' => array_keys($etiquetas)
            ));
        }

        // 7) v8.3: Calcula total de postos distintos e total de lotes
        $totalPostosDistintos = 0;
        if (isset($lotes_processados_dados) && is_array($lotes_processados_dados)) {
            $postosUnicos = array();
            foreach ($lotes_processados_dados as $loteProcessadoInfo) {
                if (!is_array($loteProcessadoInfo) || !isset($loteProcessadoInfo['posto'])) {
                    continue;
                }
                $postoProcessado = trim((string)$loteProcessadoInfo['posto']);
                if ($postoProcessado === '') {
                    continue;
                }
                $postosUnicos[$postoProcessado] = true;
            }
            $postosUnicos = array_keys($postosUnicos);
            $totalPostosDistintos = count($postosUnicos);
        }
        $totalLotesGravados = $totalLotes;

        // v1.2.3: Salvar etiquetas dos Correios em ciMalotes
        // Fontes: (1) snapshot_oficio JSON, (2) POST etiqueta_correios[], (3) ciDespachoLotes BD
        $login_malotes = '';
        if (isset($_POST['responsavel']) && trim($_POST['responsavel']) !== '') {
            $login_malotes = trim($_POST['responsavel']);
        } elseif (isset($_SESSION['ultimo_responsavel']) && trim($_SESSION['ultimo_responsavel']) !== '') {
            $login_malotes = trim($_SESSION['ultimo_responsavel']);
        } else {
            $login_malotes = $usuario;
        }

        $stmtMalotes = $pdo_controle->prepare("INSERT INTO ciMalotes (leitura, data, observacao, login, tipo, cep, sequencial, posto)
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $data_malote = date('Y-m-d');
        $etiquetas_salvas = 0;
        // v2.2.0: REGRA DE GRAVACAO CORRIGIDA + OTIMIZADA
        // Grava em ciMalotes TODOS os displays que realmente constam no oficio
        // recem-salvo (ciDespachoLotes do despacho atual) — controle total do que
        // esta saindo. O posto e resolvido com prioridade no cadastro de displays
        // (cadastroMalotes por cep+sequencial, fallback cep) e, quando o display
        // ainda nao tem cadastro, usa o posto do proprio oficio (ciDespachoLotes)
        // para nunca deixar de registrar um display que esta sendo enviado.
        // Consultas em LOTE (poucas idas ao banco) para nao travar a tela.

        // 1) Fonte UNICA: etiquetas + posto que realmente estao no oficio gerado
        $etiquetasOficio = array();   // eti_digits => array('posto_oficio'=>..., 'cep'=>..., 'seq'=>...)
        if (isset($id_desp) && $id_desp) {
            try {
                $stEtqLote = $pdo_controle->prepare(
                    "SELECT posto, etiqueta_correios FROM ciDespachoLotes
                     WHERE id_despacho = ? AND etiqueta_correios IS NOT NULL AND etiqueta_correios <> ''"
                );
                $stEtqLote->execute(array((int)$id_desp));
                while ($rowEL = $stEtqLote->fetch(PDO::FETCH_ASSOC)) {
                    $eti_bd = trim((string)$rowEL['etiqueta_correios']);
                    if ($eti_bd === '') continue;
                    $eti_digits = preg_replace('/\D+/', '', $eti_bd);
                    if (strlen($eti_digits) !== 35) continue;   // etiqueta valida dos Correios = 35 digitos
                    if (isset($etiquetasOficio[$eti_digits])) continue;  // dedup (posto com varios lotes)
                    $etiquetasOficio[$eti_digits] = array(
                        'posto_oficio' => trim((string)$rowEL['posto']),
                        'cep'          => substr($eti_digits, 0, 8),
                        'seq'          => substr($eti_digits, -5)
                    );
                }
            } catch (Exception $eMal) { /* continua sem etiquetas */ }
        }

        if (!empty($etiquetasOficio)) {
            // 2) Resolve postos do cadastroMalotes em UMA consulta (por cep das etiquetas)
            $mapaCepSeq = array();   // "cep|seq" => posto
            $mapaCep    = array();   // "cep"     => posto (ultimo cadastrado)
            $cepsUnicos = array();
            foreach ($etiquetasOficio as $infoE) { $cepsUnicos[$infoE['cep']] = true; }
            $cepsLista = array_keys($cepsUnicos);
            if (!empty($cepsLista)) {
                try {
                    $ph = implode(',', array_fill(0, count($cepsLista), '?'));
                    $stCad = $pdo_controle->prepare(
                        'SELECT posto, cep, sequencial FROM cadastroMalotes WHERE cep IN (' . $ph . ') ORDER BY id ASC'
                    );
                    $stCad->execute($cepsLista);
                    while ($rc = $stCad->fetch(PDO::FETCH_ASSOC)) {
                        // Normaliza por valor numerico: cobre colunas INT (sem zeros a esquerda)
                        // e VARCHAR — a etiqueta tambem e normalizada do mesmo jeito no lookup.
                        $cepC = (string)(int)preg_replace('/\D+/', '', (string)$rc['cep']);
                        $seqC = (string)(int)preg_replace('/\D+/', '', (string)$rc['sequencial']);
                        $posC = trim((string)$rc['posto']);
                        if ($posC === '') continue;
                        $mapaCep[$cepC] = $posC;                       // ASC => fica o ultimo (maior id)
                        $mapaCepSeq[$cepC . '|' . $seqC] = $posC;
                    }
                } catch (Exception $eCad) { /* segue com fallback do oficio */ }
            }

            // 3) Duplicatas do dia em UMA consulta (evita N selects)
            $jaSalvasHoje = array();
            try {
                $etisLista = array_keys($etiquetasOficio);
                $ph2 = implode(',', array_fill(0, count($etisLista), '?'));
                $stDup = $pdo_controle->prepare(
                    'SELECT leitura FROM ciMalotes WHERE tipo = 1 AND DATE(data) = CURDATE() AND leitura IN (' . $ph2 . ')'
                );
                $stDup->execute($etisLista);
                while ($rd = $stDup->fetch(PDO::FETCH_ASSOC)) {
                    $jaSalvasHoje[trim((string)$rd['leitura'])] = true;
                }
            } catch (Exception $eDup) { /* sem dedup do dia */ }

            // 4) Insere cada display do oficio com o posto correto
            foreach ($etiquetasOficio as $eti_digits => $infoE) {
                if (isset($jaSalvasHoje[$eti_digits])) continue;  // ja gravado hoje

                $cep = $infoE['cep'];
                $seq = $infoE['seq'];
                // Chaves normalizadas (mesma regra usada ao montar os mapas)
                $cepKey = (string)(int)$cep;
                $seqKey = (string)(int)$seq;

                // Prioridade: cadastro (cep+seq) > cadastro (cep) > posto do oficio
                $posto_malote = '';
                if (isset($mapaCepSeq[$cepKey . '|' . $seqKey])) {
                    $posto_malote = $mapaCepSeq[$cepKey . '|' . $seqKey];
                } elseif (isset($mapaCep[$cepKey])) {
                    $posto_malote = $mapaCep[$cepKey];
                } elseif ($infoE['posto_oficio'] !== '') {
                    $posto_malote = $infoE['posto_oficio'];
                }

                // Sem nenhum posto identificavel: nao grava (registro invalido)
                if ($posto_malote === '') continue;

                $stmtMalotes->execute(array(
                    $eti_digits,   // leitura
                    $data_malote,  // data
                    null,          // observacao — sempre NULL
                    $login_malotes,// login
                    1,             // tipo = 1 (despacho/saida)
                    $cep,          // cep
                    $seq,          // sequencial
                    $posto_malote  // posto (cadastro quando existe; senao o do oficio)
                ));
                $etiquetas_salvas++;
            }
        }
        
        $pdo_controle->commit();

        // v8.14.0: NÃO salvar lacres na sessão após salvar!
        // - Snapshot é usado APENAS para gravar no BD
        // - Sessão é atualizada APENAS quando usuário edita inputs manualmente
        // - Isso evita perpetuar duplicações ou erros do JavaScript
        // - localStorage já preserva os valores corretos que estavam na tela
        // (REMOVIDO: código que salvava mapaCapital/mapaCentral na sessão)

        // Verifica se deve imprimir após salvar
        $deve_imprimir = isset($_POST['imprimir_apos_salvar']) && $_POST['imprimir_apos_salvar'] === '1';

        // v8.14.2: CORRIGIDO - Redirecionar para recarregar dados do BD antes de imprimir
        // Isso garante que arrays PHP tenham valores carregados do BD para aparecer no PDF
        if ($deve_imprimir) {
            // Salvar flag de impressão na sessão para auto-imprimir após reload
            $_SESSION['auto_imprimir_correios'] = true;
            $_SESSION['ultimo_oficio_salvo'] = (int)$id_desp;
            
            // Redirecionar para mesma página para recarregar dados do BD
            $url_redirect = $_SERVER['PHP_SELF'];
            if (!empty($datasStr)) {
                // Preservar datas selecionadas na URL
                $datasArray = explode(',', $datasStr);
                $datasFormatadas = array();
                foreach ($datasArray as $d) {
                    $d = trim($d);
                    if (!empty($d)) {
                        $datasFormatadas[] = $d;
                    }
                }
                if (!empty($datasFormatadas)) {
                    $url_redirect .= '?datas[]=' . implode('&datas[]=', array_map('urlencode', $datasFormatadas));
                }
            }
            
            // Nota: etiquetas Correios JÁ foram gravadas tanto em ciDespachoLotes
            // (campo etiqueta_correios) quanto em ciMalotes (tipo=1) no bloco acima.

            header('Location: ' . $url_redirect);
            exit;
        } else {
            // Apenas salvar sem imprimir - mostra mensagem com total de displays gravados.
            // Etiquetas JÁ foram gravadas em ciDespachoLotes E em ciMalotes no bloco acima
            
            $msg = 'Oficio Correios salvo com sucesso! No. ' . (int)$id_desp . ' - Postos: ' . (int)$totalPostosDistintos . ', Lotes: ' . (int)$totalLotesGravados . ($etiquetas_salvas > 0 ? ', Displays gravados: ' . (int)$etiquetas_salvas : '');
            echo "<script>
                    alert('" . addslashes($msg) . "');
                    if (typeof marcarComoSalvo === 'function') { marcarComoSalvo(); }
                  </script>";
        }
    } catch (Exception $e) {
        if ($pdo_controle->inTransaction()) {
            $pdo_controle->rollBack();
        }
        echo "<pre>Erro ao salvar oficio Correios: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
        echo "<script>alert('Erro ao salvar oficio Correios: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// === v8.14.6: HANDLER REMOVIDO - etiquetas salvam automaticamente em salvar_oficio_correios ===
// Não é mais necessário handler separado - integração inline acima (linha ~1085)
if (false && isset($_POST['acao']) && $_POST['acao'] === 'salvar_oficio_e_etiquetas_correios_REMOVIDO') {
    try {
        if (!isset($pdo_controle) || !($pdo_controle instanceof PDO)) {
            throw new Exception('PDO $pdo_controle não disponível.');
        }
        
        // Capturar configurações antes de processar
        $modo_etiquetas = isset($_POST['modo_etiquetas']) ? trim($_POST['modo_etiquetas']) : 'novo';
        $login_etiquetas = isset($_POST['login_etiquetas']) && !empty($_POST['login_etiquetas']) 
                           ? trim($_POST['login_etiquetas']) 
                           : (isset($_SESSION['responsavel']) ? $_SESSION['responsavel'] : 'Sistema');
        $datasStr = isset($_POST['correios_datas']) ? trim($_POST['correios_datas']) : '';
        $imprimir = isset($_POST['imprimir_apos_salvar']) && $_POST['imprimir_apos_salvar'] === '1';
        
        // ETAPA 1: Salvar ofício normalmente (reutiliza lógica de salvar_oficio_correios)
        // Temporariamente muda ação para invocar handler existente
        $_POST['acao_original'] = 'salvar_oficio_e_etiquetas_correios';
        $_POST['acao'] = 'salvar_oficio_correios';
        
        // Invoca handler de ofício via include recursivo
        // NOTA: Isso funcionará porque o código verifica $_POST['acao'] === 'salvar_oficio_correios'
        // e nós já estamos dentro do mesmo script
        // Usaremos flag para evitar loop infinito
        if (!isset($_SESSION['processando_oficio_etiquetas'])) {
            $_SESSION['processando_oficio_etiquetas'] = true;
            
            // Reprocessa o handler de ofício
            // ATENÇÃO: Isso requer que o handler de salvar_oficio_correios não faça exit()
            // Vamos capturar a execução
            ob_start();
            // O handler acima já foi executado, então vamos apenas continuar
            ob_end_clean();
            
            unset($_SESSION['processando_oficio_etiquetas']);
        }
        
        // ETAPA 2: Salvar etiquetas em ciMalotes
        $hoje = date('Y-m-d');
        $etiquetas_salvas = 0;
        $erros = 0;
        
        // Se modo sobrescrever, deletar etiquetas anteriores das mesmas datas
        if ($modo_etiquetas === 'sobrescrever' && !empty($datasStr)) {
            $datasArray = explode(',', $datasStr);
            $datasArray = array_filter(array_map('trim', $datasArray));
            if (!empty($datasArray)) {
                $placeholders = implode(',', array_fill(0, count($datasArray), '?'));
                $stDelEtiq = $pdo_controle->prepare("DELETE FROM ciMalotes WHERE data IN ($placeholders)");
                $stDelEtiq->execute($datasArray);
            }
        }
        
        // Inserir etiquetas
        $etiquetas_central_salvas = array();
        
        if (isset($_SESSION['etiquetas']) && is_array($_SESSION['etiquetas'])) {
            foreach ($_SESSION['etiquetas'] as $posto_codigo => $etiqueta) {
                if (!empty($etiqueta) && strlen($etiqueta) === 35) {
                    // Para CENTRAL IIPR, evitar duplicatas
                    if (isset($CENTRAL) && is_array($CENTRAL) && in_array($posto_codigo, $CENTRAL)) {
                        if (in_array($etiqueta, $etiquetas_central_salvas)) {
                            continue;
                        }
                        $etiquetas_central_salvas[] = $etiqueta;
                    }
                    
                    try {
                        $cep = substr($etiqueta, 0, 8);
                        $sequencial = substr($etiqueta, -5);
                        $observacao = "Salva via Gravar+Imprimir por {$login_etiquetas} em " . date('d-m-Y');
                        
                        $stmtDupChk2 = $pdo_controle->prepare('SELECT COUNT(*) FROM ciMalotes WHERE leitura = ? AND tipo = 1');
                        $stmtDupChk2->execute(array($etiqueta));
                        if ((int)$stmtDupChk2->fetchColumn() === 0) {
                            $stmt = $pdo_controle->prepare("INSERT INTO ciMalotes (leitura, data, observacao, login, tipo, cep, sequencial, posto)
                                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute(array(
                                $etiqueta,
                                $hoje,
                                $observacao,
                                $login_etiquetas,
                                1,
                                $cep,
                                $sequencial,
                                $posto_codigo
                            ));
                            $etiquetas_salvas++;
                        }
                    } catch (PDOException $e) {
                        add_debug("v8.14.6 - Erro ao salvar etiqueta", array(
                            'posto' => $posto_codigo,
                            'etiqueta' => $etiqueta,
                            'erro' => $e->getMessage()
                        ));
                        $erros++;
                    }
                }
            }
        }
        
        // Mensagem de sucesso
        $msg = "Ofício Correios salvo com sucesso!\\n\\n";
        if ($etiquetas_salvas > 0) {
            $msg .= "✓ {$etiquetas_salvas} etiquetas salvas em ciMalotes por {$login_etiquetas}.";
            if ($erros > 0) {
                $msg .= "\\n⚠ {$erros} etiquetas não puderam ser salvas.";
            }
        } else {
            $msg .= "⚠ Nenhuma etiqueta válida encontrada para salvar.";
        }
        
        echo "<script>alert('" . addslashes($msg) . "');</script>";
        
        if ($imprimir) {
            echo "<script>window.print();</script>";
        }
        
        $url_redirect = $_SERVER['PHP_SELF'];
        if (!empty($datasStr)) {
            $datasArray = explode(',', $datasStr);
            $datasArray = array_filter(array_map('trim', $datasArray));
            if (!empty($datasArray)) {
                $url_redirect .= '?datas[]=' . implode('&datas[]=', array_map('urlencode', $datasArray));
            }
        }
        
        echo "<script>setTimeout(function(){ window.location.href='" . addslashes($url_redirect) . "'; }, 2000);</script>";
        exit;
        
    } catch (Exception $e) {
        if ($pdo_controle && $pdo_controle->inTransaction()) {
            $pdo_controle->rollBack();
        }
        echo "<script>alert('Erro ao salvar ofício+etiquetas: " . addslashes($e->getMessage()) . "');</script>";
    }
}


// Manter um log para depuração
function add_debug($message, $data = null) {
    if (!isset($_SESSION['debug_log'])) {
        $_SESSION['debug_log'] = array();
    }
    $_SESSION['debug_log'][] = array(
        'time' => date('H:i:s'),
        'message' => $message,
        'data' => $data
    );
}

// Função para validar duplicatas de etiquetas
function validar_etiqueta_duplicada($nova_etiqueta, $indice_atual) {
    if (empty($nova_etiqueta) || strlen($nova_etiqueta) !== 35) {
        return array('valida' => true, 'mensagem' => '');
    }
    
    foreach ($_SESSION['etiquetas'] as $posto_codigo => $etiqueta_existente) {
        if ($posto_codigo !== $indice_atual && $etiqueta_existente === $nova_etiqueta) {
            return array(
                'valida' => false,
                'mensagem' => "Esta etiqueta já está sendo usada no posto {$posto_codigo}"
            );
        }
    }
    
    return array('valida' => true, 'mensagem' => '');
}

// V7.9: Função para analisar dados de expedição com nova lógica de data (+1 dia)
function analisar_expedicao($pdo_controle, $pdo_servico, $datas_filtro) {
    // V7.9: Converter datas do formato brasileiro para SQL e adicionar +1 dia
    $sql_dates_array = array();
    foreach ($datas_filtro as $data) {
        $partes = explode('-', $data);
        if (count($partes) === 3) {
            // V7.9: Adicionar 1 dia à data selecionada para buscar na madrugada seguinte
            $data_sql = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
            $data_plus_one = date('Y-m-d', strtotime($data_sql . ' +1 day'));
            $sql_dates_array[] = $data_plus_one;
        }
    }
    
    if (empty($sql_dates_array)) {
        return array(
            'total_carteiras' => 0,
            'total_postos' => 0,
            'autores_faltantes' => array(),
            'diferenca' => 0,
            'postos_retirados' => array(),
            'detalhes_expedicao' => array()
        );
    }
    
    $sql_datas_in = "'" . implode("','", $sql_dates_array) . "'";
    
    // V7.9: Consultar carteiras expedidas na tbl_ci_filadeimpressao (data +1 dia às 02:00)
    $sql_expedidas = "SELECT DATE(datafila) as data, expedidas, TIME(datafila) as hora
                      FROM tbl_ci_filadeimpressao
                      WHERE DATE(datafila) IN ($sql_datas_in)
                      AND TIME(datafila) BETWEEN '01:30:00' AND '02:30:00'
                      ORDER BY DATE(datafila), ABS(TIMESTAMPDIFF(SECOND, TIME(datafila), '02:00:00'))";
    
    $stmt_expedidas = $pdo_servico->query($sql_expedidas);
    $dados_expedidas = array();
    $total_carteiras_geral = 0;
    $detalhes_expedicao = array();
    
    // Agrupar por data para pegar apenas um registro por data (o mais próximo das 02:00)
    $expedidas_por_data = array();
    $data_original_map = array_combine($sql_dates_array, $datas_filtro); // Mapeamento data+1 -> data original
    
    while ($row = $stmt_expedidas->fetch(PDO::FETCH_ASSOC)) {
        $data = $row['data'];
        if (!isset($expedidas_por_data[$data])) {
            $expedidas_por_data[$data] = array(
                'expedidas' => (int)$row['expedidas'],
                'hora' => $row['hora']
            );
            $total_carteiras_geral += (int)$row['expedidas'];
            
            // V7.9: Mostrar data original (não data+1)
            $data_original = isset($data_original_map[$data]) ? $data_original_map[$data] : date('d-m-Y', strtotime($data . ' -1 day'));
            $detalhes_expedicao[] = array(
                'data' => $data_original,
                'expedidas' => (int)$row['expedidas'],
                'hora' => $row['hora']
            );
        }
    }
    
    // V7.9: Para as demais consultas, usar as datas originais (sem +1)
    $sql_dates_original = array();
    foreach ($datas_filtro as $data) {
        $partes = explode('-', $data);
        if (count($partes) === 3) {
            $sql_dates_original[] = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
        }
    }
    $sql_datas_original_in = "'" . implode("','", $sql_dates_original) . "'";
    
    // 2. Consultar total de postos em ciPostos (mantendo compatibilidade com ciPostosCsv)
    $sql_postos = "SELECT SUM(quantidade) as total_postos
                   FROM ciPostosCsv
                   WHERE DATE(dataCarga) IN ($sql_datas_original_in)";
    
    $stmt_postos = $pdo_controle->query($sql_postos);
    $row_postos = $stmt_postos->fetch(PDO::FETCH_ASSOC);
    $total_postos = (int)(isset($row_postos['total_postos']) ? $row_postos['total_postos'] : 0);
    
    // 3. Consultar autores presentes em ciPostosCsv para as datas
    $sql_autores_postos = "SELECT DISTINCT usuario as autor
                          FROM ciPostosCsv
                          WHERE DATE(dataCarga) IN ($sql_datas_original_in)
                          AND usuario IS NOT NULL";
    
    $stmt_autores_postos = $pdo_controle->query($sql_autores_postos);
    $autores_em_postos = array();
    while ($row = $stmt_autores_postos->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['autor'])) {
            $autores_em_postos[] = $row['autor'];
        }
    }
    
    // 4. Para identificar autores faltantes, usamos uma consulta da ci-expedidas como fallback
    // já que a tbl_ci_filadeimpressao não tem campo autor individual
    $sql_expedidas_autores = "SELECT SUM(total) as total_carteiras, autor, total
                             FROM `ci-expedidas`
                             WHERE dia IN ($sql_datas_original_in)
                             GROUP BY autor";
    
    $stmt_expedidas_autores = $pdo_controle->query($sql_expedidas_autores);
    $dados_expedidas_por_autor = array();
    
    while ($row = $stmt_expedidas_autores->fetch(PDO::FETCH_ASSOC)) {
        $dados_expedidas_por_autor[$row['autor']] = (int)$row['total'];
    }
    
    // 5. Identificar autores faltantes e suas quantidades
    $autores_faltantes = array();
    $total_faltante = 0;
    
    foreach ($dados_expedidas_por_autor as $autor => $quantidade) {
        if (!in_array($autor, $autores_em_postos)) {
            $autores_faltantes[] = array(
                'autor' => $autor,
                'quantidade' => $quantidade
            );
            $total_faltante += $quantidade;
        }
    }
    
    // 6. Consultar retiradas na tabela ciRetirada
    $sql_retiradas = "SELECT protocolo
                      FROM ciRetirada
                      WHERE DATE(datasolicitacao) IN ($sql_datas_original_in)";
    
    $stmt_retiradas = $pdo_controle->query($sql_retiradas);
    $postos_retirados = array();
    
    while ($row = $stmt_retiradas->fetch(PDO::FETCH_ASSOC)) {
        $protocolo = $row['protocolo'];
        // Extrair os 3 primeiros dígitos do protocolo (formato: 850-23-00851)
        if (preg_match('/^(\d{3})/', $protocolo, $matches)) {
            $posto_numero = $matches[1];
            if (!in_array($posto_numero, $postos_retirados)) {
                $postos_retirados[] = $posto_numero;
            }
        }
    }
    
    // V7.9: Ordenar postos retirados em ordem crescente
    sort($postos_retirados, SORT_NUMERIC);
    
    // Calcular diferença
    $diferenca = $total_carteiras_geral - $total_postos;
    
    return array(
        'total_carteiras' => $total_carteiras_geral,
        'total_postos' => $total_postos,
        'autores_faltantes' => $autores_faltantes,
        'diferenca' => $diferenca,
        'total_faltante' => $total_faltante,
        'postos_retirados' => $postos_retirados,
        'dados_expedidas' => $dados_expedidas_por_autor,
        'autores_em_postos' => $autores_em_postos,
        'detalhes_expedicao' => $detalhes_expedicao
    );
}

// V8.1: Função para obter usuários válidos do banco contrsos
function obter_usuarios_validos($pdo_contrsos) {
    $sql = "SELECT nome, usuario FROM usuarios WHERE perfil != 'exf' ORDER BY nome";
    $stmt = $pdo_contrsos->query($sql);
    $usuarios = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $usuarios[] = array(
            'nome' => $row['nome'],
            'usuario' => $row['usuario']
        );
    }
    return $usuarios;
}

// V8.1: Função para obter nome do posto baseado no código
function obter_nome_posto($pdo_controle, $codigo_posto) {
    $sql = "SELECT DISTINCT posto FROM ciPostos WHERE posto LIKE ? ORDER BY posto LIMIT 1";
    $stmt = $pdo_controle->prepare($sql);
    $stmt->execute(array("{$codigo_posto}%"));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return $result['posto'];
    }
    
    // Se não encontrar, retorna o código com formatação padrão
    return sprintf("%03d - POSTO", intval($codigo_posto));
}

// V8.1: Função para inserir dados na tabela ciPostos com código de barras
function inserir_dados_cipostos_barcode($pdo_controle, $codigo_barras, $data, $turno, $autor) {
    try {
        // Validar código de barras (19 dígitos)
        if (strlen($codigo_barras) !== 19 || !ctype_digit($codigo_barras)) {
            throw new Exception("Código de barras deve ter exatamente 19 dígitos numéricos");
        }
        
        // Extrair informações do código de barras
        $lote = substr($codigo_barras, 0, 8);        // Primeiros 8 dígitos (não salvar)
        $regional = substr($codigo_barras, 8, 3);    // Próximos 3 dígitos (não salvar)
        $codigo_posto = substr($codigo_barras, 11, 3); // Próximos 3 dígitos
        $quantidade = (int)substr($codigo_barras, -5); // Últimos 5 dígitos
        
        // Obter nome completo do posto
        $nome_posto = obter_nome_posto($pdo_controle, $codigo_posto);
        
        // Converter data do formato brasileiro para SQL
        $data = trim((string)$data);
        $partes = preg_split('/[\/-]/', $data);
        if (count($partes) === 3) {
            $data_sql = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
        } else {
            throw new Exception("Formato de data inválido");
        }
        
        // Data e hora de criação (sempre 10:10:10)
        $criado = $data_sql . ' 10:10:10';
        
        // Inserir na tabela ciPostos
        $stmt = $pdo_controle->prepare("
            INSERT INTO ciPostos (posto, dia, quantidade, turno, regional, lote, autor, criado, situacao)
            VALUES (?, ?, ?, ?, NULL, ?, ?, ?, 0)
        ");
        
        $resultado = $stmt->execute(array(
            $nome_posto,
            $data_sql,
            $quantidade,
            $turno,
            (int)$lote,
            $autor,
            $criado
        ));
        
        if ($resultado) {
            add_debug("Dados inseridos em ciPostos via código de barras", array(
                'codigo_barras' => $codigo_barras,
                'posto' => $nome_posto,
                'quantidade' => $quantidade,
                'data' => $data_sql,
                'turno' => $turno,
                'autor' => $autor
            ));
            return array(
                'sucesso' => true,
                'mensagem' => "Dados inseridos com sucesso: {$nome_posto} - {$quantidade} carteiras"
            );
        } else {
            return array('sucesso' => false, 'mensagem' => "Erro ao inserir dados");
        }
    } catch (Exception $e) {
        add_debug("Erro ao inserir dados em ciPostos", array(
            'erro' => $e->getMessage(),
            'codigo_barras' => $codigo_barras,
            'data' => $data
        ));
        return array('sucesso' => false, 'mensagem' => "Erro: " . $e->getMessage());
    }
}

function extrairUltimoLacreSequencial($valor) {
    $valor = trim((string)$valor);
    if ($valor === '') {
        return 0;
    }
    if (preg_match_all('/\d+/', $valor, $matches) && !empty($matches[0])) {
        return (int)$matches[0][count($matches[0]) - 1];
    }
    return 0;
}

// Definições dos grupos de postos
$CAPITAL = array("001","002","014","015","030","031","032","033","034","035","036","037","039","040","044");
$CENTRAL = array("010","013","016","018","027","041","042","046","047","051","052","053","054",
            "055","056","057","058","059","061","062","063","064","065","066","067","068",
            "069","070","071","072","073","074","075","076","077","078","079","080","083","084",
            "085","086");

// CONFIGURAÇÃO DE SPLITS PARA CENTRAL IIPR
// Lista de códigos de posto (formato '046') que marcam o início de um novo malote
// Exemplo: $splitsCentral = array('046'); -> a partir do posto 046 começa o próximo malote
// Observe: os splits são aplicados sobre a ordem exibida da CENTRAL IIPR (ordenada numericamente)
$splitsCentral = array();

// Limpar a sessão completamente quando solicitado
if ((
    $_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) ||
    (isset($_POST['limpar_sessao']))
) {
    // v8.14.9.3: Limpar TODAS as chaves de sessão relacionadas a ofícios
    $_SESSION['etiquetas'] = array();
    $_SESSION['linhas_removidas'] = array();
    $_SESSION['lacres_personalizados'] = array();
    $_SESSION['postos_manuais'] = array();
    $_SESSION['postos_cadastrados'] = array();
    $_SESSION['datas_filtro'] = array();
    $_SESSION['debug_log'] = array();
    $_SESSION['excluir_regionais_manual'] = array();
    
    // v8.14.9.3: Limpar também dados de despachos salvos
    if (isset($_SESSION['id_despacho_poupa_tempo'])) {
        unset($_SESSION['id_despacho_poupa_tempo']);
    }
    if (isset($_SESSION['id_despacho_correios'])) {
        unset($_SESSION['id_despacho_correios']);
    }
    if (isset($_SESSION['oficios'])) {
        $_SESSION['oficios'] = array();
    }
}

// Salvar datas selecionadas
if (isset($_GET['datas']) && is_array($_GET['datas'])) {
    $_SESSION['datas_filtro'] = $_GET['datas'];
}

$mensagem_sucesso = '';
$mensagem_erro = '';

// Tratamento de POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // v1.6: liberar etiqueta quando campo é esvaziado (permite mover a etiqueta)
    if (isset($_POST['limpar_etiqueta'], $_POST['indice'])) {
        $indice = trim((string)$_POST['indice']);
        if (!isset($_SESSION['etiquetas'])) { $_SESSION['etiquetas'] = array(); }
        if (isset($CENTRAL) && is_array($CENTRAL) && in_array($indice, $CENTRAL)) {
            foreach ($CENTRAL as $posto_cod) { unset($_SESSION['etiquetas'][$posto_cod]); }
        } else {
            unset($_SESSION['etiquetas'][$indice]);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('status' => 'ok'));
        exit;
    }

    // Atualizar etiqueta com validação de duplicata
    if (isset($_POST['etiqueta'], $_POST['indice'])) {
        $indice = trim((string)$_POST['indice']);
        $nova_etiqueta = $_POST['etiqueta'];
        
        // Validar duplicata
        $validacao = validar_etiqueta_duplicada($nova_etiqueta, $indice);
        
        if (!$validacao['valida']) {
            echo json_encode(array('status' => 'erro', 'mensagem' => $validacao['mensagem']));
            exit;
        }
        
        // Propagar etiqueta: se o cliente enviou explicitamente a lista de postos do grupo
        // (parametro `group_postos[]`), aplicamos apenas a esses postos. Caso contrario,
        // manter comportamento legado: se for posto CENTRAL e nao houver group_postos,
        // aplicar a toda a CENTRAL; senao aplicar apenas ao indice informado.
        $_SESSION['etiquetas'][$indice] = $nova_etiqueta;

        if (isset($_POST['group_postos']) && is_array($_POST['group_postos']) && count($_POST['group_postos'])>0) {
            foreach ($_POST['group_postos'] as $posto_cod) {
                $posto_cod = trim((string)$posto_cod);
                if ($posto_cod !== '') {
                    $_SESSION['etiquetas'][$posto_cod] = $nova_etiqueta;
                }
            }
        } else {
            if (in_array($indice, $CENTRAL)) {
                // Sem group_postos informado: comportamento legado para CENTRAL
                foreach ($CENTRAL as $posto_cod) {
                    $_SESSION['etiquetas'][$posto_cod] = $nova_etiqueta;
                }
            }
        }
        
        echo json_encode(array('status' => 'ok'));
        exit;
    }
    
    // Atualizar lacre
    if (isset($_POST['update_lacre'], $_POST['indice'], $_POST['tipo'])) {
        $indice = trim((string)$_POST['indice']);
        $novo_lacre = $_POST['update_lacre'];
        // Se o cliente enviou group_postos[], atualizar todos os postos do grupo
        if (isset($_POST['group_postos']) && is_array($_POST['group_postos']) && count($_POST['group_postos'])>0) {
            foreach ($_POST['group_postos'] as $posto_cod) {
                $posto_cod = trim((string)$posto_cod);
                if ($posto_cod !== '') {
                    if (!isset($_SESSION['lacres_personalizados'][$posto_cod])) $_SESSION['lacres_personalizados'][$posto_cod] = array();
                    $_SESSION['lacres_personalizados'][$posto_cod][$_POST['tipo']] = $novo_lacre;
                }
            }
        } else {
            if (!isset($_SESSION['lacres_personalizados'][$indice])) $_SESSION['lacres_personalizados'][$indice] = array();
            $_SESSION['lacres_personalizados'][$indice][$_POST['tipo']] = $novo_lacre;
        }
        echo json_encode(array('status' => 'ok'));
        exit;
    }
    
    // Salvar etiquetas no banco de dados com confirmação
    if (isset($_POST['salvar_etiquetas_confirmado'])) {
        $login = isset($_POST['login_personalizado']) && !empty($_POST['login_personalizado'])
                 ? $_POST['login_personalizado']
                 : (isset($_POST['login']) ? $_POST['login'] : 'Sistema');
        $hoje = date('Y-m-d');
        $etiquetas_salvas = 0;
        
        // CORREÇÃO V8.1: Array para controlar etiquetas já salvas da central
        $etiquetas_central_salvas = array();
        $erros = 0;
        
        // Percorrer todas as etiquetas da sessão
        foreach ($_SESSION['etiquetas'] as $posto_codigo => $etiqueta) {
            // Verificar se a etiqueta é válida (não vazia e tem 35 caracteres)
            if (!empty($etiqueta) && strlen($etiqueta) === 35) {
                // CORREÇÃO V8.1: Para postos da central, verificar se a etiqueta já foi salva
                if (in_array($posto_codigo, $CENTRAL)) {
                    if (in_array($etiqueta, $etiquetas_central_salvas)) {
                        // Etiqueta da central já foi salva, pular para evitar duplicata
                        continue;
                    }
                    // Marcar etiqueta como salva para a central
                    $etiquetas_central_salvas[] = $etiqueta;
                }
                try {
                    // Extrair CEP e sequencial da etiqueta
                    $cep = substr($etiqueta, 0, 8);
                    $sequencial = substr($etiqueta, -5);
                    
                    // Preparar a observação
                    $observacao = "Etiqueta gerada por {$login} em " . date('d-m-Y');
                    
                    // Inserir na tabela ciMalotes (com verificacao de duplicata)
                    $stmtDupChk3 = $pdo_controle->prepare('SELECT COUNT(*) FROM ciMalotes WHERE leitura = ? AND tipo = 1');
                    $stmtDupChk3->execute(array($etiqueta));
                    if ((int)$stmtDupChk3->fetchColumn() === 0) {
                        $stmt = $pdo_controle->prepare("INSERT INTO ciMalotes (leitura, data, observacao, login, tipo, cep, sequencial, posto)
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute(array(
                            $etiqueta,
                            $hoje,
                            $observacao,
                            $login,
                            1,
                            $cep,
                            $sequencial,
                            $posto_codigo
                        ));
                        $etiquetas_salvas++;
                    }
                } catch (PDOException $e) {
                    add_debug("Erro ao salvar etiqueta", array(
                        'posto' => $posto_codigo,
                        'etiqueta' => $etiqueta,
                        'erro' => $e->getMessage()
                    ));
                    $erros++;
                }
            }
        }
        
        if ($etiquetas_salvas > 0) {
            $mensagem_sucesso = "Foram salvas {$etiquetas_salvas} etiquetas no banco de dados por {$login}.";
            if ($erros > 0) {
                $mensagem_sucesso .= " ({$erros} etiquetas não puderam ser salvas)";
            }
        } else {
            $mensagem_erro = "Nenhuma etiqueta foi salva no banco de dados. Verifique se há etiquetas preenchidas.";
        }
    }
    
    // V8.1: Inserir dados na tabela ciPostos com código de barras
    if (isset($_POST['inserir_dados_barcode'])) {
        $codigo_barras = trim($_POST['codigo_barras']);
        $data = trim($_POST['data_inserir']);
        $turno = (int)$_POST['turno_inserir'];
        $autor = isset($_POST['autor_inserir']) && $_POST['autor_inserir'] != '' ? trim($_POST['autor_inserir']) : 'conferencia';
        
        if (!empty($codigo_barras) && !empty($data) && $turno > 0) {
            $resultado = inserir_dados_cipostos_barcode($pdo_controle, $codigo_barras, $data, $turno, $autor);
            
            if ($resultado['sucesso']) {
                $mensagem_sucesso = $resultado['mensagem'];
            } else {
                $mensagem_erro = $resultado['mensagem'];
            }
        } else {
            $mensagem_erro = "Todos os campos são obrigatórios para inserir dados.";
        }
        
        // Redirecionamento preservando filtros
        $params = array();
        if (isset($_GET['lacre_capital'])) $params[] = "lacre_capital=" . urlencode($_GET['lacre_capital']);
        if (isset($_GET['lacre_central'])) $params[] = "lacre_central=" . urlencode($_GET['lacre_central']);
        if (isset($_GET['lacre_regionais'])) $params[] = "lacre_regionais=" . urlencode($_GET['lacre_regionais']);
        if (isset($_GET['responsavel'])) $params[] = "responsavel=" . urlencode($_GET['responsavel']);
        
        // Adicionar as datas selecionadas
        if (!empty($_SESSION['datas_filtro'])) {
            foreach ($_SESSION['datas_filtro'] as $data) {
                $params[] = "datas[]=" . urlencode($data);
            }
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . (isset($_GET['debug']) ? '?debug=1' : '');
        if (!empty($params)) {
            $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . implode('&', $params);
        }
        
        header("Location: $redirect_url");
        exit;
    }
    
    // DESFAZER exclusão de posto
    if (isset($_POST['restaurar_posto_undo'])) {
        $undo = isset($_SESSION['undo_exclusao']) ? $_SESSION['undo_exclusao'] : null;
        if ($undo && is_array($undo)) {
            $itens = isset($undo['itens']) ? $undo['itens'] : array($undo);
            foreach ($itens as $item) {
                $cod_rest = isset($item['codigo']) ? (string)$item['codigo'] : '';
                $tipo_rest = isset($item['tipo']) ? (string)$item['tipo'] : 'capital';
                if ($cod_rest === '') continue;
                if ($tipo_rest === 'regional') {
                    if (!isset($_SESSION['excluir_regionais_manual'])) $_SESSION['excluir_regionais_manual'] = array();
                    $nova_lista = array();
                    foreach ($_SESSION['excluir_regionais_manual'] as $v) {
                        if ($v !== $cod_rest) $nova_lista[] = $v;
                    }
                    $_SESSION['excluir_regionais_manual'] = $nova_lista;
                } else {
                    if (isset($_SESSION['linhas_removidas'][$cod_rest])) {
                        unset($_SESSION['linhas_removidas'][$cod_rest]);
                    }
                }
                if (isset($item['lacres']) && $item['lacres'] !== null) {
                    if (!isset($_SESSION['lacres_personalizados'])) $_SESSION['lacres_personalizados'] = array();
                    $_SESSION['lacres_personalizados'][$cod_rest] = $item['lacres'];
                }
                if (isset($item['etiquetas']) && $item['etiquetas'] !== null) {
                    if (!isset($_SESSION['etiquetas'])) $_SESSION['etiquetas'] = array();
                    $_SESSION['etiquetas'][$cod_rest] = $item['etiquetas'];
                }
                if (isset($item['postos_manuais']) && $item['postos_manuais'] !== null) {
                    if (!isset($_SESSION['postos_manuais'])) $_SESSION['postos_manuais'] = array();
                    $_SESSION['postos_manuais'][$cod_rest] = $item['postos_manuais'];
                }
            }
            unset($_SESSION['undo_exclusao']);
            $mensagem_sucesso = 'Exclusão desfeita — posto restaurado!';
        }
        $params_undo = array();
        if (isset($_GET['lacre_capital'])) $params_undo[] = 'lacre_capital='.urlencode($_GET['lacre_capital']);
        if (isset($_GET['lacre_central'])) $params_undo[] = 'lacre_central='.urlencode($_GET['lacre_central']);
        if (isset($_GET['lacre_regionais'])) $params_undo[] = 'lacre_regionais='.urlencode($_GET['lacre_regionais']);
        if (isset($_GET['responsavel'])) $params_undo[] = 'responsavel='.urlencode($_GET['responsavel']);
        if (!empty($_SESSION['datas_filtro'])) {
            foreach ($_SESSION['datas_filtro'] as $_d) $params_undo[] = 'datas[]='.urlencode($_d);
        }
        $redirect_undo = $_SERVER['PHP_SELF'].(!empty($params_undo)?'?'.implode('&',$params_undo):'');
        header("Location: $redirect_undo");
        exit;
    }

    // Excluir múltiplos postos de uma vez
    if (isset($_POST['excluir_multiplos']) && trim((string)$_POST['excluir_multiplos']) !== '') {
        $lista_exc = json_decode((string)$_POST['excluir_multiplos'], true);
        if (is_array($lista_exc)) {
            // Snapshot para undo antes de deletar
            $undo_itens_mult = array();
            foreach ($lista_exc as $item_exc) {
                $codigo_exc = trim((string)(isset($item_exc['codigo']) ? $item_exc['codigo'] : ''));
                $grupo_exc  = trim((string)(isset($item_exc['grupo'])  ? $item_exc['grupo']  : ''));
                $nome_exc   = trim((string)(isset($item_exc['nome'])   ? $item_exc['nome']   : $codigo_exc));
                if ($codigo_exc === '') continue;
                $undo_itens_mult[] = array(
                    'tipo'           => ($grupo_exc === 'REGIONAIS') ? 'regional' : 'capital',
                    'codigo'         => $codigo_exc,
                    'grupo'          => $grupo_exc,
                    'nome'           => $nome_exc,
                    'lacres'         => isset($_SESSION['lacres_personalizados'][$codigo_exc]) ? $_SESSION['lacres_personalizados'][$codigo_exc] : null,
                    'etiquetas'      => isset($_SESSION['etiquetas'][$codigo_exc]) ? $_SESSION['etiquetas'][$codigo_exc] : null,
                    'postos_manuais' => (strpos($codigo_exc,'M')===0 && isset($_SESSION['postos_manuais'][$codigo_exc])) ? $_SESSION['postos_manuais'][$codigo_exc] : null,
                );
            }
            if (!empty($undo_itens_mult)) {
                $_SESSION['undo_exclusao'] = array('itens' => $undo_itens_mult, 'ts' => time());
            }
            foreach ($lista_exc as $item_exc) {
                $codigo_exc = trim((string)(isset($item_exc['codigo']) ? $item_exc['codigo'] : ''));
                $grupo_exc  = trim((string)(isset($item_exc['grupo'])  ? $item_exc['grupo']  : ''));
                if ($codigo_exc === '') continue;
                if ($grupo_exc === 'REGIONAIS') {
                    $_SESSION['excluir_regionais_manual'][] = $codigo_exc;
                    if (strpos($codigo_exc, 'M') === 0 && isset($_SESSION['postos_manuais'][$codigo_exc])) {
                        unset($_SESSION['postos_manuais'][$codigo_exc]);
                    }
                } else {
                    $_SESSION['linhas_removidas'][$codigo_exc] = true;
                    if (strpos($codigo_exc, 'M') === 0 && isset($_SESSION['postos_manuais'][$codigo_exc])) {
                        unset($_SESSION['postos_manuais'][$codigo_exc]);
                    }
                    if (isset($_SESSION['lacres_personalizados'][$codigo_exc])) unset($_SESSION['lacres_personalizados'][$codigo_exc]);
                    if (isset($_SESSION['etiquetas'][$codigo_exc])) unset($_SESSION['etiquetas'][$codigo_exc]);
                }
            }
        }
        $params_exc = array();
        if (isset($_GET['lacre_capital'])) $params_exc[] = 'lacre_capital=' . urlencode($_GET['lacre_capital']);
        if (isset($_GET['lacre_central'])) $params_exc[] = 'lacre_central=' . urlencode($_GET['lacre_central']);
        if (isset($_GET['lacre_regionais'])) $params_exc[] = 'lacre_regionais=' . urlencode($_GET['lacre_regionais']);
        if (isset($_GET['responsavel'])) $params_exc[] = 'responsavel=' . urlencode($_GET['responsavel']);
        if (!empty($_SESSION['datas_filtro'])) {
            foreach ($_SESSION['datas_filtro'] as $_d) $params_exc[] = 'datas[]=' . urlencode($_d);
        }
        $redirect_exc = $_SERVER['PHP_SELF'] . (!empty($params_exc) ? '?' . implode('&', $params_exc) : '');
        header("Location: $redirect_exc");
        exit;
    }

    // Exclusão dedicada para REGIONAIS
    if (isset($_POST['excluir_posto_regional']) && $_POST['excluir_posto_regional'] === '1') {
        // Código do posto (número ou código manual com M)
        $codigo = trim((string)$_POST['codigo_posto']);
        $nome_del = isset($_POST['nome_posto']) ? trim((string)$_POST['nome_posto']) : $codigo;
        
        // Snapshot para undo antes de deletar
        $_SESSION['undo_exclusao'] = array(
            'itens' => array(array(
                'tipo'           => 'regional',
                'codigo'         => $codigo,
                'grupo'          => 'REGIONAIS',
                'nome'           => $nome_del,
                'lacres'         => isset($_SESSION['lacres_personalizados'][$codigo]) ? $_SESSION['lacres_personalizados'][$codigo] : null,
                'etiquetas'      => isset($_SESSION['etiquetas'][$codigo]) ? $_SESSION['etiquetas'][$codigo] : null,
                'postos_manuais' => (strpos($codigo,'M')===0 && isset($_SESSION['postos_manuais'][$codigo])) ? $_SESSION['postos_manuais'][$codigo] : null,
            )),
            'ts' => time(),
        );

        // Informação para depuração
        add_debug("Solicitação de exclusão REGIONAL com nova abordagem", array(
            'codigo' => $codigo,
            'post_data' => $_POST,
            'regional_info' => isset($_POST['info_regional']) ? $_POST['info_regional'] : 'não informado'
        ));
        
        // Adicionar no array específico
        $_SESSION['excluir_regionais_manual'][] = $codigo;

        // Se for posto manual, remover da lista imediatamente
        if (strpos($codigo, 'M') === 0 && isset($_SESSION['postos_manuais'][$codigo])) {
            unset($_SESSION['postos_manuais'][$codigo]);
        }
        
        // Mensagem de sucesso
        $mensagem_sucesso = "Posto {$codigo} do grupo REGIONAIS foi excluído com sucesso!";
        
        // Redirecionamento preservando filtros
        $params = array();
        if (isset($_GET['lacre_capital'])) $params[] = "lacre_capital=" . urlencode($_GET['lacre_capital']);
        if (isset($_GET['lacre_central'])) $params[] = "lacre_central=" . urlencode($_GET['lacre_central']);
        if (isset($_GET['lacre_regionais'])) $params[] = "lacre_regionais=" . urlencode($_GET['lacre_regionais']);
        if (isset($_GET['responsavel'])) $params[] = "responsavel=" . urlencode($_GET['responsavel']);
        
        // Adicionar as datas selecionadas
        if (!empty($_SESSION['datas_filtro'])) {
            foreach ($_SESSION['datas_filtro'] as $data) {
                $params[] = "datas[]=" . urlencode($data);
            }
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . (isset($_GET['debug']) ? '?debug=1' : '');
        if (!empty($params)) {
            $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . implode('&', $params);
        }
        
        header("Location: $redirect_url");
        exit;
    }
    
    // Exclusão dos outros grupos (CAPITAL e CENTRAL)
    if (isset($_POST['excluir_posto']) && $_POST['excluir_posto'] === '1') {
        $codigo = trim((string)$_POST['codigo_posto']);
        $grupo = isset($_POST['grupo_posto']) ? $_POST['grupo_posto'] : 'Não especificado';
        $nome_del = isset($_POST['nome_posto']) ? trim((string)$_POST['nome_posto']) : $codigo;

        // Snapshot para undo antes de deletar
        $_SESSION['undo_exclusao'] = array(
            'itens' => array(array(
                'tipo'           => 'capital',
                'codigo'         => $codigo,
                'grupo'          => $grupo,
                'nome'           => $nome_del,
                'lacres'         => isset($_SESSION['lacres_personalizados'][$codigo]) ? $_SESSION['lacres_personalizados'][$codigo] : null,
                'etiquetas'      => isset($_SESSION['etiquetas'][$codigo]) ? $_SESSION['etiquetas'][$codigo] : null,
                'postos_manuais' => (strpos($codigo,'M')===0 && isset($_SESSION['postos_manuais'][$codigo])) ? $_SESSION['postos_manuais'][$codigo] : null,
            )),
            'ts' => time(),
        );
        
        // Para postos de CAPITAL e CENTRAL: usar o sistema tradicional
        $_SESSION['linhas_removidas'][$codigo] = true;
        
        if (isset($_SESSION['lacres_personalizados'][$codigo])) {
            unset($_SESSION['lacres_personalizados'][$codigo]);
        }
        if (isset($_SESSION['etiquetas'][$codigo])) {
            unset($_SESSION['etiquetas'][$codigo]);
        }
        
        // Se for um posto manual
        if (strpos($codigo, 'M') === 0 && isset($_SESSION['postos_manuais'][$codigo])) {
            unset($_SESSION['postos_manuais'][$codigo]);
        }
        
        $mensagem_sucesso = "Posto {$codigo} ({$grupo}) removido com sucesso!";
        
        // Redirecionamento preservando filtros
        $params = array();
        if (isset($_GET['lacre_capital'])) $params[] = "lacre_capital=" . urlencode($_GET['lacre_capital']);
        if (isset($_GET['lacre_central'])) $params[] = "lacre_central=" . urlencode($_GET['lacre_central']);
        if (isset($_GET['lacre_regionais'])) $params[] = "lacre_regionais=" . urlencode($_GET['lacre_regionais']);
        if (isset($_GET['responsavel'])) $params[] = "responsavel=" . urlencode($_GET['responsavel']);
        
        // Adicionar as datas selecionadas
        if (!empty($_SESSION['datas_filtro'])) {
            foreach ($_SESSION['datas_filtro'] as $data) {
                $params[] = "datas[]=" . urlencode($data);
            }
        }
        
        $redirect_url = $_SERVER['PHP_SELF'] . (isset($_GET['debug']) ? '?debug=1' : '');
        if (!empty($params)) {
            $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . implode('&', $params);
        }
        
        header("Location: $redirect_url");
        exit;
    }
    
    // Inserir linha acima/abaixo - Método via modal
    if (isset($_POST['inserir_linha'])) {
        $posicao = isset($_POST['posicao']) ? $_POST['posicao'] : '';
        $referencia_posto = isset($_POST['referencia_posto']) ? $_POST['referencia_posto'] : '';
        $novo_nome = isset($_POST['novo_nome']) ? $_POST['novo_nome'] : '';
        $grupo = isset($_POST['novo_grupo']) ? $_POST['novo_grupo'] : 'REGIONAIS';
        $lacre_iipr = '';
        $lacre_correios = '';
        
        if (empty($novo_nome)) {
            $mensagem_erro = "O nome do posto é obrigatório!";
        } else {
            // Gerar código único para o posto manual
            $codigo = 'M' . time() . rand(1000, 9999);
            
            // Registrar informações do posto
            $_SESSION['postos_manuais'][$codigo] = array(
                'posto_codigo' => $codigo,
                'posto_nome' => $novo_nome,
                'tipo' => $grupo,
                'quantidade' => 1,
                'referencia' => $referencia_posto,
                'posicao' => $posicao,
                'manual_inserido' => 1
            );
            
            // Registrar lacres personalizados
            // Lacres serao informados diretamente na linha apos inserir o posto
            
            // Mensagem de sucesso
            $mensagem_sucesso = "Posto '{$novo_nome}' adicionado com sucesso!";
            
            // Redirecionamento preservando todos os parâmetros
            $params = array();
            if (isset($_GET['lacre_capital'])) $params[] = "lacre_capital=" . urlencode($_GET['lacre_capital']);
            if (isset($_GET['lacre_central'])) $params[] = "lacre_central=" . urlencode($_GET['lacre_central']);
            if (isset($_GET['lacre_regionais'])) $params[] = "lacre_regionais=" . urlencode($_GET['lacre_regionais']);
            if (isset($_GET['responsavel'])) $params[] = "responsavel=" . urlencode($_GET['responsavel']);
            
            // Adicionar as datas selecionadas
            if (!empty($_SESSION['datas_filtro'])) {
                foreach ($_SESSION['datas_filtro'] as $data) {
                    $params[] = "datas[]=" . urlencode($data);
                }
            }
            
            $redirect_url = $_SERVER['PHP_SELF'] . (isset($_GET['debug']) ? '?debug=1' : '');
            if (!empty($params)) {
                $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . implode('&', $params);
            }
            
            header("Location: $redirect_url");
            exit;
        }
    }
    
    // Adicionar posto manual
    if (isset($_POST['adicionar_manual'])) {
        $tipo = $_POST['tipo_posto'];
        $posto_nome = $_POST['nome_posto'];
        $lacre_iipr = '';
        $lacre_correios = '';
        
        if (empty($posto_nome)) {
            $mensagem_erro = "O nome do posto é obrigatório!";
        } else {
            $codigo = 'M' . time() . rand(1000, 9999);
            $_SESSION['postos_manuais'][$codigo] = array(
                'posto_codigo' => $codigo,
                'posto_nome' => $posto_nome,
                'tipo' => $tipo,
                'quantidade' => 1
            );
            // Lacres serao informados diretamente na linha apos inserir o posto
            
            $mensagem_sucesso = "Posto '{$posto_nome}' adicionado com sucesso!";
            
            // Redirecionamento para manter os parâmetros GET
            // Usar GET se presente, caso contrario usar o ultimo valor salvo em sessao
            $params = array();
            if (isset($_GET['lacre_capital'])) {
                $params[] = "lacre_capital=" . urlencode($_GET['lacre_capital']);
            } elseif (isset($_SESSION['ultimo_lacre_capital'])) {
                $params[] = "lacre_capital=" . urlencode($_SESSION['ultimo_lacre_capital']);
            }

            if (isset($_GET['lacre_central'])) {
                $params[] = "lacre_central=" . urlencode($_GET['lacre_central']);
            } elseif (isset($_SESSION['ultimo_lacre_central'])) {
                $params[] = "lacre_central=" . urlencode($_SESSION['ultimo_lacre_central']);
            }

            if (isset($_GET['lacre_regionais'])) {
                $params[] = "lacre_regionais=" . urlencode($_GET['lacre_regionais']);
            } elseif (isset($_SESSION['ultimo_lacre_regionais'])) {
                $params[] = "lacre_regionais=" . urlencode($_SESSION['ultimo_lacre_regionais']);
            }

            if (isset($_GET['responsavel'])) {
                $params[] = "responsavel=" . urlencode($_GET['responsavel']);
            } elseif (isset($_SESSION['ultimo_responsavel'])) {
                $params[] = "responsavel=" . urlencode($_SESSION['ultimo_responsavel']);
            }
            
            // Adicionar as datas selecionadas
            if (!empty($_SESSION['datas_filtro'])) {
                foreach ($_SESSION['datas_filtro'] as $data) {
                    $params[] = "datas[]=" . urlencode($data);
                }
            }
            
            $redirect_url = $_SERVER['PHP_SELF'] . (isset($_GET['debug']) ? '?debug=1' : '');
            if (!empty($params)) {
                $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . implode('&', $params);
            }
            
            header("Location: $redirect_url");
            exit;
        }
    }
    
    // Cadastrar novo posto
    if (isset($_POST['cadastrar_posto'])) {
        $posto = $_POST['posto'];
        $regional = $_POST['regional'];
        $nome = $_POST['nome'];
        
        try {
            // Verificar se o posto já existe
            $stmt_check = $pdo_controle->prepare("SELECT COUNT(*) FROM ciRegionais WHERE posto = ?");
            $stmt_check->execute(array($posto));
            $existe = $stmt_check->fetchColumn();
            
            if ($existe) {
                $mensagem_erro = "O posto {$posto} já existe na tabela ciRegionais.";
            } else {
                // Inserir o novo posto
                $stmt_insert = $pdo_controle->prepare("INSERT INTO ciRegionais (regional, posto, nome) VALUES (?, ?, ?)");
                $stmt_insert->execute(array($regional, $posto, $nome));
                
                // Registrar na sessão que o posto foi cadastrado
                $_SESSION['postos_cadastrados'][] = $posto;
                
                $mensagem_sucesso = "Posto {$posto} cadastrado com sucesso!";
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao cadastrar posto: " . $e->getMessage();
        }
    }
}

// v9.8.1: Buscar dias com/sem conferência nos últimos 30 dias (COM DIA DA SEMANA)
$dias_com_conferencia = array();
$dias_sem_conferencia = array();
$metadados_dias = array(); // Novo: armazena dia da semana
try {
    // Buscar últimos 30 dias COM produção (dados em ciPostosCsv)
    $stmt_conferidos = $pdo_controle->query("
        SELECT DISTINCT 
            DATE(dataCarga) as data,
            DAYOFWEEK(dataCarga) as dia_semana
        FROM ciPostosCsv 
        WHERE dataCarga >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY data DESC
        LIMIT 15
    ");
    $dias_com_producao = array(); // Dias que tiveram produção
    while ($row = $stmt_conferidos->fetch(PDO::FETCH_ASSOC)) {
        $data_fmt = date('d-m-Y', strtotime($row['data']));
        $dias_com_producao[] = $data_fmt;
        
        // Determina label do dia (1=Dom, 6=Sex, 7=Sáb)
        $dia_num = (int)$row['dia_semana'];
        $label = '';
        if ($dia_num == 6) $label = 'SEX';
        elseif ($dia_num == 7) $label = 'SÁB';
        elseif ($dia_num == 1) $label = 'DOM';
        
        $metadados_dias[$data_fmt] = array(
            'dia_semana_num' => $dia_num,
            'label' => $label
        );
    }
    
    // Buscar dias COM conferência registrada (tabela conferencia_pacotes)
    try {
        $stmt_conf = $pdo_controle->query("
            SELECT DISTINCT DATE(dataCarga) as data
            FROM ciPostosCsv csv
            INNER JOIN conferencia_pacotes cp ON csv.lote = cp.nlote
            WHERE csv.dataCarga >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND cp.conf = 's'
            ORDER BY data DESC
        ");
        while ($row = $stmt_conf->fetch(PDO::FETCH_ASSOC)) {
            $dias_com_conferencia[] = date('d-m-Y', strtotime($row['data']));
        }
    } catch (Exception $e) {
        // Se conferencia_pacotes não existir, assume que nenhum dia foi conferido
        $dias_com_conferencia = array();
    }
    
    // Calcular dias PENDENTES: dias com produção MAS sem conferência
    $dias_sem_conferencia = array_diff($dias_com_producao, $dias_com_conferencia);
    $dias_sem_conferencia = array_values($dias_sem_conferencia); // Reindexar
    $dias_sem_conferencia = array_slice($dias_sem_conferencia, 0, 10); // Limitar a 10
} catch (Exception $e) {
    // Silenciar erro
}

// Obter datas disponíveis
$stmt_datas = $pdo_controle->query("SELECT DISTINCT DATE(dataCarga) as data FROM ciPostosCsv WHERE dataCarga IS NOT NULL ORDER BY data DESC LIMIT 5");
$datas_expedicao = array();
while ($row = $stmt_datas->fetch(PDO::FETCH_ASSOC)) {
    $datas_expedicao[] = date('d-m-Y', strtotime($row['data']));
}

// v9.14.0: Processar filtro por intervalo de datas (calendário HTML5 + datas alternadas)
// MUDANÇA: Não carrega datas automaticamente - usuário deve escolher
$datas_filtro = array();

$datas_alternadas_get = isset($_GET['datas_alternadas']) ? trim((string)$_GET['datas_alternadas']) : '';

// Prioridade 1: Datas alternadas (específicas digitadas manualmente)
if ($datas_alternadas_get !== '') {
    $datas_alternadas_str = $datas_alternadas_get;
    // Separar por vírgula
    $datas_array = explode(',', $datas_alternadas_str);
    
    foreach ($datas_array as $data_str) {
        $data_str = trim($data_str);
        // Validar formato dd-mm-aaaa ou dd/mm/aaaa
        if (preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{4}$/', $data_str)) {
            $data_norm = str_replace('/', '-', $data_str);
            $data_obj = DateTime::createFromFormat('d-m-Y', $data_norm);
            if ($data_obj) {
                $data_formatada = $data_obj->format('d-m-Y');
                if (!in_array($data_formatada, $datas_filtro)) {
                    $datas_filtro[] = $data_formatada;
                }
            }
        }
    }
    
    $_SESSION['datas_filtro'] = $datas_filtro;
}
// Prioridade 2: Datas enviadas por query (?datas[]=)
elseif (isset($_GET['datas']) && is_array($_GET['datas']) && !empty($_GET['datas'])) {
    foreach ($_GET['datas'] as $data_str) {
        $data_str = trim((string)$data_str);
        if ($data_str === '') continue;
        // Aceita dd-mm-yyyy, dd/mm/yyyy ou yyyy-mm-dd
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $data_str)) {
            $datas_filtro[] = $data_str;
        } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data_str)) {
            $data_obj = DateTime::createFromFormat('d/m/Y', $data_str);
            if ($data_obj) {
                $datas_filtro[] = $data_obj->format('d-m-Y');
            }
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_str)) {
            $data_obj = DateTime::createFromFormat('Y-m-d', $data_str);
            if ($data_obj) {
                $datas_filtro[] = $data_obj->format('d-m-Y');
            }
        }
    }
    $datas_filtro = array_values(array_unique($datas_filtro));
    $_SESSION['datas_filtro'] = $datas_filtro;
}
// Prioridade 2: Intervalo de datas (calendário HTML5)
elseif (isset($_GET['data_inicial_cal']) && isset($_GET['data_final_cal']) && 
        !empty($_GET['data_inicial_cal']) && !empty($_GET['data_final_cal'])) {
    
    $data_inicial_cal = $_GET['data_inicial_cal']; // formato yyyy-mm-dd (HTML5 date)
    $data_final_cal = $_GET['data_final_cal'];
    
    // Buscar todas as datas no intervalo que existem em ciPostosCsv
    $stmt_intervalo = $pdo_controle->prepare("
        SELECT DISTINCT DATE(dataCarga) as data 
        FROM ciPostosCsv 
        WHERE DATE(dataCarga) BETWEEN ? AND ?
        ORDER BY data DESC
    ");
    $stmt_intervalo->execute(array($data_inicial_cal, $data_final_cal));
    
    while ($row = $stmt_intervalo->fetch(PDO::FETCH_ASSOC)) {
        $datas_filtro[] = date('d-m-Y', strtotime($row['data']));
    }
    
    $_SESSION['datas_filtro'] = $datas_filtro;
}
// v9.14.0: Não carrega datas padrão - apenas se sessão já existir
elseif (!empty($_SESSION['datas_filtro'])) {
    $datas_filtro = $_SESSION['datas_filtro'];
}
// Senão, mantém array vazio (página inicia sem dados)

// v9.14.0: Realizar análise de expedição apenas se houver datas filtradas
if (!empty($datas_filtro)) {
    $analise_expedicao = analisar_expedicao($pdo_controle, $pdo_servico, $datas_filtro);
} else {
    // Array vazio quando página carrega sem filtro
    $analise_expedicao = array(
        'poupatempo' => array(),
        'correios' => array()
    );
}

// Valores que NÃO são responsáveis reais
function responsavelValido($v) {
    $v = trim((string)$v);
    $invalidos = array('', 'Responsável Não Informado', 'Responsavel Nao Informado', 'responsavel nao informado', 'Sistema', 'conferencia');
    return $v !== '' && !in_array($v, $invalidos, true);
}

// v8.14.9.1: Definir $responsavel ANTES de usar (corrige warning linha 2166)
$responsavel = '';
if (isset($_POST['responsavel']) && responsavelValido($_POST['responsavel'])) {
    $responsavel = trim((string)$_POST['responsavel']);
} elseif (isset($_GET['responsavel']) && responsavelValido($_GET['responsavel'])) {
    $responsavel = trim((string)$_GET['responsavel']);
} elseif (isset($_SESSION['ultimo_responsavel']) && responsavelValido($_SESSION['ultimo_responsavel'])) {
    $responsavel = trim((string)$_SESSION['ultimo_responsavel']);
}
// Limpar session se tiver valor inválido
if (isset($_SESSION['ultimo_responsavel']) && !responsavelValido($_SESSION['ultimo_responsavel'])) {
    unset($_SESSION['ultimo_responsavel']);
}

// v8.14.9.3: Buscar o maior lacre usado (IIPR e Correios) para exibir na tela
$ultimo_lacre_iipr = 0;
$ultimo_lacre_correios = 0;
$ultimo_despacho_correios = 0;
$ultimo_despacho_pt = 0;
try {
    $stmtUltCorreios = $pdo_controle->query("SELECT id FROM ciDespachos WHERE LOWER(grupo) = 'correios' ORDER BY id DESC LIMIT 1");
    $ultimo_despacho_correios = (int)$stmtUltCorreios->fetchColumn();

    $stmtUltPT = $pdo_controle->query("SELECT id FROM ciDespachos WHERE LOWER(grupo) != 'correios' ORDER BY id DESC LIMIT 1");
    $ultimo_despacho_pt = (int)$stmtUltPT->fetchColumn();

    // Buscar maior lacre IIPR
    if ($ultimo_despacho_correios > 0) {
        $stMaxIIPR = $pdo_controle->prepare("
            SELECT MAX(CAST(etiquetaiipr AS UNSIGNED)) as max_iipr
            FROM ciDespachoLotes
            WHERE id_despacho = ? AND etiquetaiipr IS NOT NULL AND etiquetaiipr != ''
        ");
        $stMaxIIPR->execute(array($ultimo_despacho_correios));
        $rowMaxIIPR = $stMaxIIPR->fetch(PDO::FETCH_ASSOC);
        if ($rowMaxIIPR && $rowMaxIIPR['max_iipr']) {
            $ultimo_lacre_iipr = (int)$rowMaxIIPR['max_iipr'];
        }

        // Buscar maior lacre Correios
        $stMaxCorreios = $pdo_controle->prepare("
            SELECT MAX(CAST(etiquetacorreios AS UNSIGNED)) as max_correios
            FROM ciDespachoLotes
            WHERE id_despacho = ? AND etiquetacorreios IS NOT NULL AND etiquetacorreios != ''
        ");
        $stMaxCorreios->execute(array($ultimo_despacho_correios));
        $rowMaxCorreios = $stMaxCorreios->fetch(PDO::FETCH_ASSOC);
        if ($rowMaxCorreios && $rowMaxCorreios['max_correios']) {
            $ultimo_lacre_correios = (int)$rowMaxCorreios['max_correios'];
        }
    } else {
        $stMaxIIPR = $pdo_controle->query("
            SELECT MAX(CAST(etiquetaiipr AS UNSIGNED)) as max_iipr 
            FROM ciDespachoLotes 
            WHERE etiquetaiipr IS NOT NULL AND etiquetaiipr != ''
        ");
        $rowMaxIIPR = $stMaxIIPR->fetch(PDO::FETCH_ASSOC);
        if ($rowMaxIIPR && $rowMaxIIPR['max_iipr']) {
            $ultimo_lacre_iipr = (int)$rowMaxIIPR['max_iipr'];
        }
        
        $stMaxCorreios = $pdo_controle->query("
            SELECT MAX(CAST(etiquetacorreios AS UNSIGNED)) as max_correios 
            FROM ciDespachoLotes 
            WHERE etiquetacorreios IS NOT NULL AND etiquetacorreios != ''
        ");
        $rowMaxCorreios = $stMaxCorreios->fetch(PDO::FETCH_ASSOC);
        if ($rowMaxCorreios && $rowMaxCorreios['max_correios']) {
            $ultimo_lacre_correios = (int)$rowMaxCorreios['max_correios'];
        }
    }
} catch (Exception $e) {
    // Silenciar erro
}

// Parâmetros do formulário
$lacre_capital = isset($_GET['lacre_capital']) ? (int)$_GET['lacre_capital'] : 1;
$lacre_central = isset($_GET['lacre_central']) ? (int)$_GET['lacre_central'] : 0;
$lacre_regionais = isset($_GET['lacre_regionais']) ? (int)$_GET['lacre_regionais'] : 0;
// Persistir os ultimos lacres iniciais na sessao para preservar valores
// quando ações POST (ex: adicionar posto manual) redirecionarem sem os GETs
$_SESSION['ultimo_lacre_capital'] = $lacre_capital;
$_SESSION['ultimo_lacre_central'] = $lacre_central;
$_SESSION['ultimo_lacre_regionais'] = $lacre_regionais;
// Persistir responsavel selecionado (apenas valores válidos)
if (responsavelValido($responsavel)) {
    $_SESSION['ultimo_responsavel'] = $responsavel;
}
$cliente = isset($_GET['cliente']) ? $_GET['cliente'] : 'Cliente Não Informado';
$data_geracao = date('d-m-Y');

// Verificar posto 001
$tem_posto_001 = false;

// Obter informações dos postos e regionais
$postos_regionais = array();
$regionais_info = array();

// Dados da tabela ciRegionais
$stmt_regionais = $pdo_controle->query("SELECT id, regional, posto, nome, entrega FROM ciRegionais ORDER BY regional, posto");
while ($row = $stmt_regionais->fetch(PDO::FETCH_ASSOC)) {
    $posto_num = str_pad((int)$row['posto'], 3, '0', STR_PAD_LEFT);
    $regional_num = str_pad((int)$row['regional'], 3, '0', STR_PAD_LEFT);
    
    // Armazenar informações do posto
    $postos_regionais[$posto_num] = array(
        'posto_numero' => $posto_num,
        'posto_nome' => $row['nome'],
        'regional' => $regional_num
    );
    $postos_regionais[$posto_num]['entrega'] = isset($row['entrega']) ? $row['entrega'] : null;
    
    // Armazenar informações da regional
    if ($posto_num == $regional_num) {
        $regionais_info[$regional_num] = array(
            'nome' => $row['nome']
        );
    }
}

// Converter datas do formato brasileiro para SQL
$sql_dates_array = array();
foreach ($datas_filtro as $data) {
    $partes = explode('-', $data);
    if (count($partes) === 3) {
        $sql_dates_array[] = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
    }
}
$sql_datas = implode("','", $sql_dates_array);

// Consulta SQL para postos
$sql = "SELECT posto, regional, quantidade, dataCarga FROM ciPostosCsv
        WHERE DATE(dataCarga) IN ('$sql_datas')
        ORDER BY regional, lote, posto";

$stmt = $pdo_controle->query($sql);
$postos_visiveis = array();
$postos_nao_cadastrados = array();
$postos_processados = array();

// Verificar posto 002
$tem_posto_002 = false;

// Processar resultado da consulta
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data_original = $row['dataCarga'];
    if (empty($data_original)) continue;
    
    $posto_num = str_pad((int)$row['posto'], 3, '0', STR_PAD_LEFT);
    
    // Verificar se é posto 001 ou 002
    if ($posto_num === '001') {
        $tem_posto_001 = true;
    }
    if ($posto_num === '002') {
        $tem_posto_002 = true;
    }
    
    // v1.0.6: posto 002 pertence ao 001 e não deve abrir linha própria no ofício
    if ($posto_num === '002') continue;

    // Ignorar posto já processado
    if (in_array($posto_num, $postos_processados)) continue;
    
    // Verificar se o posto foi removido no sistema tradicional
    if (isset($_SESSION['linhas_removidas'][$posto_num]) && $_SESSION['linhas_removidas'][$posto_num] === true) {
        continue;
    }
    
    $postos_processados[] = $posto_num;
    
    // Replicate array_column for PHP 5.3.3
    $postos_nao_cadastrados_postos = array();
    foreach ($postos_nao_cadastrados as $val) {
        $postos_nao_cadastrados_postos[] = $val['posto'];
    }

    // Verificar se o posto existe em ciRegionais
    if (!isset($postos_regionais[$posto_num]) && !in_array($posto_num, $_SESSION['postos_cadastrados'])) {
        if (!in_array($posto_num, $postos_nao_cadastrados_postos)) {
            $postos_nao_cadastrados[] = array(
                'posto' => $posto_num,
                'regional' => $row['regional']
            );
        }
    }
    
    // Determinar o tipo do posto
    $tipo = '';
    if (in_array($posto_num, $CAPITAL)) {
        $tipo = 'CAPITAL';
    } elseif (in_array($posto_num, $CENTRAL)) {
        $tipo = 'CENTRAL IIPR';
    } else {
        $tipo = 'REGIONAIS';
    }
    
    // Se 001 ou 002, forçar tipo CAPITAL
    if ($posto_num === '001' || $posto_num === '002') {
        $tipo = 'CAPITAL';
    }
    
    // Construir nome do posto
    if (isset($postos_regionais[$posto_num])) {
        $posto_nome = $postos_regionais[$posto_num]['posto_nome'];
        $regional_num = $postos_regionais[$posto_num]['regional'];
        $ent = isset($postos_regionais[$posto_num]['entrega']) ? $postos_regionais[$posto_num]['entrega'] : null;
        if ($ent && preg_match('/poupa\s*-?\s*tempo/i', $ent)) { $tipo = 'POUPA TEMPO'; }
        if ($regional_num === '0' || $regional_num === '00' || $regional_num === '000' || (int)$regional_num === 0) { $tipo = 'CAPITAL'; }
        if ($regional_num === '999' || $regional_num === '0999' || (int)$regional_num === 999) { $tipo = 'CENTRAL IIPR'; }

        
        // Regras de agrupamento por regional
        if ($regional_num === '0' || $regional_num === '00' || $regional_num === '000' || (int)$regional_num === 0) { $tipo = 'CAPITAL'; }
        if ($regional_num === '999' || $regional_num === '0999' || (int)$regional_num === 999) { $tipo = 'CENTRAL IIPR'; }
// Regra solicitada: qualquer posto com regional == 0 deve ser tratado como CAPITAL
        if ($regional_num === '0' || $regional_num === '00' || $regional_num === '000' || (int)$regional_num === 0) {
            $tipo = 'CAPITAL';
        }

        
        $regional_nome = null;
        if (isset($regionais_info[$regional_num])) {
            $regional_nome = $regionais_info[$regional_num]['nome'];
        }
    } else {
        $posto_nome = "Posto {$posto_num}";
        $regional_num = str_pad((int)$row['regional'], 3, '0', STR_PAD_LEFT);
        $regional_nome = null;
    }
    
    // Formatar nome do posto
    if ($tipo === 'CAPITAL') {
        $posto_nome = "{$posto_num} - {$posto_nome}";
    } elseif ($tipo === 'CENTRAL IIPR') {
        $posto_nome = "{$posto_num} - {$posto_nome}";
    } else { // REGIONAIS
        if ($regional_nome) {
            $posto_nome = "Posto {$regional_num} - " . preg_replace('/^Posto \d+ - /', '', $regional_nome);
        } else {
            $posto_nome = "Posto {$posto_num} - " . preg_replace('/^Posto \d+ - /', '', $posto_nome);
        }
    }

    // Registrar o posto
    if (!isset($postos_visiveis[$posto_num])) {
        $postos_visiveis[$posto_num] = array(
            'posto_codigo' => $posto_num,
            'posto_nome' => $posto_nome,
            'quantidade' => 0,
            'tipo' => $tipo,
            'regional' => $regional_num
        );
    }
    $postos_visiveis[$posto_num]['quantidade'] += (int)$row['quantidade'];
}

// Se temos o posto 002 mas não temos o 001, criar posto 001
if ($tem_posto_002 && !$tem_posto_001 && !isset($_SESSION['linhas_removidas']['001'])) {
    $posto_num = '001';
    $tipo = 'CAPITAL';
    
    if (isset($postos_regionais[$posto_num])) {
        $posto_nome = $postos_regionais[$posto_num]['posto_nome'];
    } else {
        $posto_nome = "POSTO SEDE - CENTRAL IIPR";
    }
    
    $postos_visiveis[$posto_num] = array(
        'posto_codigo' => $posto_num,
        'posto_nome' => "{$posto_num} - {$posto_nome}",
        'quantidade' => 1,
        'tipo' => $tipo,
        'regional' => '001'
    );
}

// Garantir que posto 001 seja CAPITAL
if (isset($postos_visiveis['001']) && $postos_visiveis['001']['tipo'] !== 'CAPITAL') {
    $postos_visiveis['001']['tipo'] = 'CAPITAL';
}

$postos_manuais_inserir = array();
// Adicionar postos manuais
if (!empty($_SESSION['postos_manuais'])) {
    foreach ($_SESSION['postos_manuais'] as $codigo => $posto) {
        // Verificar exclusão no sistema tradicional
        if (isset($_SESSION['linhas_removidas'][$codigo]) && $_SESSION['linhas_removidas'][$codigo] === true) {
            continue;
        }

        $temReferencia = isset($posto['referencia']) && $posto['referencia'] !== '' &&
            isset($posto['posicao']) && $posto['posicao'] !== '' &&
            isset($posto['tipo']) && $posto['tipo'] !== '';

        if ($temReferencia) {
            $grupoRef = $posto['tipo'];
            if (!isset($postos_manuais_inserir[$grupoRef])) {
                $postos_manuais_inserir[$grupoRef] = array();
            }
            $postos_manuais_inserir[$grupoRef][] = array(
                'posto_codigo' => $codigo,
                'posto_nome' => $posto['posto_nome'],
                'tipo' => $posto['tipo'],
                'quantidade' => isset($posto['quantidade']) ? $posto['quantidade'] : 1,
                'referencia' => $posto['referencia'],
                'posicao' => $posto['posicao'],
                'manual_inserido' => !empty($posto['manual_inserido']) ? 1 : 0
            );
            continue;
        }
        
        $postos_visiveis[$codigo] = array(
            'posto_codigo' => $codigo,
            'posto_nome' => $posto['posto_nome'],
            'tipo' => $posto['tipo'],
            'quantidade' => isset($posto['quantidade']) ? $posto['quantidade'] : 1
        );
    }
}

// Dividir postos por categoria
$dados = array('POUPA TEMPO' => array(), 'CAPITAL' => array(), 'CENTRAL IIPR' => array(), 'REGIONAIS' => array());
foreach ($postos_visiveis as $posto) {
    $dados[$posto['tipo']][] = $posto;
}

// V7.9: Remover posto 002 se estiver presente quando houver posto 001
if ($tem_posto_001 && $tem_posto_002) {
    $dados['CAPITAL'] = array_filter($dados['CAPITAL'], function($posto) {
        return $posto['posto_codigo'] !== '002';
    });
}

// Remover postos 001 e 002 de REGIONAIS
$dados['REGIONAIS'] = array_filter($dados['REGIONAIS'], function($posto) {
    return $posto['posto_codigo'] !== '001' && $posto['posto_codigo'] !== '002';
});

// Ordenar cada grupo de postos pelo número do posto
foreach ($dados as $grupo_nome => &$grupo) {
    usort($grupo, function($a, $b) {
        // Se ambos começam com 'M', são postos manuais - ordenar pelo nome
        if (strpos($a['posto_codigo'], 'M') === 0 && strpos($b['posto_codigo'], 'M') === 0) {
            return strcmp($a['posto_nome'], $b['posto_nome']);
        }
        // Se só um é manual, o numérico vem primeiro
        if (strpos($a['posto_codigo'], 'M') === 0) return 1;
        if (strpos($b['posto_codigo'], 'M') === 0) return -1;
        // Caso contrário, ordenar numericamente
        if ((int)$a['posto_codigo'] < (int)$b['posto_codigo']) return -1;
        if ((int)$a['posto_codigo'] > (int)$b['posto_codigo']) return 1;
        return 0;
    });
}
unset($grupo);

// Remover duplicatas e ordenar REGIONAIS
$nomes_vistos = array();
$dados['REGIONAIS'] = array_filter($dados['REGIONAIS'], function($posto) use (&$nomes_vistos) {
    if ($posto['posto_codigo'] === '001' || $posto['posto_codigo'] === '002') {
        return false;
    }
    
    if (in_array($posto['posto_nome'], $nomes_vistos)) {
        return false;
    }
    $nomes_vistos[] = $posto['posto_nome'];
    return true;
});

// Ordenar regionais por número exibido no nome
usort($dados['REGIONAIS'], function($a, $b) {
    // Se são postos manuais, ordenar pelo nome
    if (strpos($a['posto_codigo'], 'M') === 0 && strpos($b['posto_codigo'], 'M') === 0) {
        return strcmp($a['posto_nome'], $b['posto_nome']);
    }
    
    // Se apenas um é manual, o numérico vem primeiro
    if (strpos($a['posto_codigo'], 'M') === 0) return 1;
    if (strpos($b['posto_codigo'], 'M') === 0) return -1;
    
    // Extrair números do nome para ordenação
    preg_match('/Posto (\d+)/', $a['posto_nome'], $matchA);
    preg_match('/Posto (\d+)/', $b['posto_nome'], $matchB);
    $numA = isset($matchA[1]) ? (int)$matchA[1] : 0;
    $numB = isset($matchB[1]) ? (int)$matchB[1] : 0;
    
    if ($numA < $numB) return -1;
    if ($numA > $numB) return 1;
    return 0;
});

// Remoção manual dos postos REGIONAIS marcados para exclusão
if (!empty($_SESSION['excluir_regionais_manual'])) {
    foreach ($_SESSION['excluir_regionais_manual'] as $codigo_remover) {
        // Procurar o posto nos REGIONAIS e remover
        foreach ($dados['REGIONAIS'] as $idx => $posto) {
            if ($posto['posto_codigo'] === $codigo_remover) {
                unset($dados['REGIONAIS'][$idx]);
                add_debug("Posto REGIONAL removido manualmente", $posto);
                break;
            }
        }
    }
    // Reindexar o array após remoções
    $dados['REGIONAIS'] = array_values($dados['REGIONAIS']);
}

// Verificação final para postos 001/002
foreach ($dados['REGIONAIS'] as $key => $posto) {
    if ($posto['posto_codigo'] === '001' ||
        $posto['posto_codigo'] === '002' ||
        strpos($posto['posto_nome'], 'Posto 001') !== false ||
        strpos($posto['posto_nome'], 'Posto 002') !== false) {
        unset($dados['REGIONAIS'][$key]);
    }
}
$dados['REGIONAIS'] = array_values($dados['REGIONAIS']);

// Inserir postos manuais exatamente acima/abaixo da referencia
if (!empty($postos_manuais_inserir)) {
    foreach ($postos_manuais_inserir as $grupoRef => $listaInsercoes) {
        if (!isset($dados[$grupoRef]) || !is_array($dados[$grupoRef])) {
            continue;
        }
        foreach ($listaInsercoes as $novoPosto) {
            $referencia = trim((string)$novoPosto['referencia']);
            $refDigits = preg_replace('/\D+/', '', $referencia);
            $posicao = $novoPosto['posicao'];
            $idxRef = -1;
            $totalGrupo = count($dados[$grupoRef]);
            for ($i = 0; $i < $totalGrupo; $i++) {
                if (!isset($dados[$grupoRef][$i]['posto_codigo'])) {
                    continue;
                }
                $postoCodigo = (string)$dados[$grupoRef][$i]['posto_codigo'];
                if ($postoCodigo === $referencia) {
                    $idxRef = $i;
                    break;
                }
                if ($refDigits !== '') {
                    $postoDigits = preg_replace('/\D+/', '', $postoCodigo);
                    if ($postoDigits !== '' && ltrim($postoDigits, '0') === ltrim($refDigits, '0')) {
                        $idxRef = $i;
                        break;
                    }
                }
            }

            if ($idxRef < 0 && $referencia !== '') {
                // Fallback por nome (se referencia nao for codigo)
                for ($j = 0; $j < $totalGrupo; $j++) {
                    if (isset($dados[$grupoRef][$j]['posto_nome']) && $dados[$grupoRef][$j]['posto_nome'] === $referencia) {
                        $idxRef = $j;
                        break;
                    }
                }
            }

            if ($idxRef >= 0) {
                // ok
            }

            $insertPos = $totalGrupo;
            if ($idxRef >= 0) {
                $insertPos = ($posicao === 'acima') ? $idxRef : ($idxRef + 1);
            }

            $entry = array(
                'posto_codigo' => $novoPosto['posto_codigo'],
                'posto_nome' => $novoPosto['posto_nome'],
                'tipo' => $novoPosto['tipo'],
                'quantidade' => $novoPosto['quantidade'],
                'manual_inserido' => !empty($novoPosto['manual_inserido']) ? 1 : 0,
                'referencia' => $referencia,
                'posicao' => $posicao
            );

            array_splice($dados[$grupoRef], $insertPos, 0, array($entry));
        }
        $dados[$grupoRef] = array_values($dados[$grupoRef]);
    }
}

// v8.14.2: Carregar lacres do BD do último ofício salvo (para impressão correta)
// v8.14.9.3: NÃO carregar se usuário clicou "Limpar Sessão"
$acabouDeLimpar = isset($_POST['limpar_sessao']);

// Buscar o último ofício CORREIOS e carregar seus lacres para os arrays $dados (se não foi "Limpar Sessão")
if (!$acabouDeLimpar) {
try {
    $ultimoOficioId = 0;
    if (isset($_SESSION['id_despacho_correios']) && $_SESSION['id_despacho_correios'] > 0) {
        $ultimoOficioId = (int)$_SESSION['id_despacho_correios'];
    } else {
        $stUltimoOficio = $pdo_controle->prepare("
            SELECT id FROM ciDespachos 
            WHERE grupo = 'CORREIOS' 
            ORDER BY id DESC LIMIT 1
        ");
        $stUltimoOficio->execute();
        $ultimoOficioRow = $stUltimoOficio->fetch(PDO::FETCH_ASSOC);
        if ($ultimoOficioRow && isset($ultimoOficioRow['id'])) {
            $ultimoOficioId = (int)$ultimoOficioRow['id'];
        }
    }
    
    if ($ultimoOficioId > 0) {
        // Buscar lacres dos lotes deste ofício
        $stLacres = $pdo_controle->prepare("
            SELECT posto, etiquetaiipr, etiquetacorreios, etiqueta_correios
            FROM ciDespachoLotes
            WHERE id_despacho = ?
        ");
        $stLacres->execute(array($ultimoOficioId));
        
        $lacresOficio = array();
        while ($row = $stLacres->fetch(PDO::FETCH_ASSOC)) {
            $posto_raw = (string)$row['posto'];
            if (preg_match('/^M/i', $posto_raw)) {
                $posto_pad = $posto_raw;
            } else {
                $posto_pad = str_pad($posto_raw, 3, '0', STR_PAD_LEFT);
            }
            $lacresOficio[$posto_pad] = array(
                'lacre_iipr' => (int)$row['etiquetaiipr'],
                'lacre_correios' => (int)$row['etiquetacorreios'],
                'etiqueta_correios' => $row['etiqueta_correios']
            );
        }
        
        // Aplicar lacres do BD aos arrays $dados
        foreach ($dados as $grupo => &$itens) {
            foreach ($itens as &$posto) {
                $codigo = $posto['posto_codigo'];
                if (isset($lacresOficio[$codigo])) {
                    $posto['lacre_iipr'] = $lacresOficio[$codigo]['lacre_iipr'];
                    $posto['lacre_correios'] = $lacresOficio[$codigo]['lacre_correios'];
                    if (!empty($lacresOficio[$codigo]['etiqueta_correios'])) {
                        $_SESSION['etiquetas'][$codigo] = $lacresOficio[$codigo]['etiqueta_correios'];
                    }
                }
            }
            unset($posto);
        }
        unset($itens);
    }
} catch (Exception $e) {
    // Silenciar erro - continuar sem lacres do BD
}
} // fim do if (!$acabouDeLimpar)

// === Status de cada posto (display em transito, bloqueio, restricao) ===
// Levanta, por posto, o estado do display (em transito vs disponivel via ciMalotes),
// se esta bloqueado (ciPostosBloqueados) e se tem restricao ativa (ciPostosRestricoes).
// Tolerante a ausencia de tabelas/colunas.
$status_postos = array();

if (!function_exists('lacresTabelaExiste')) {
    function lacresTabelaExiste($pdo, $tabela) {
        try {
            $st = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabela));
            return $st && $st->fetch() ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('lacresGarantirStatus')) {
    function lacresGarantirStatus(&$status_postos, $codigo) {
        if (!isset($status_postos[$codigo])) {
            $status_postos[$codigo] = array(
                'display' => '',
                'display_data' => '',
                'bloqueio' => null,
                'restricao_tipo' => null,
                'restricao_motivo' => null
            );
        }
    }
}

try {
    // === ESTOQUE DE DISPLAYS POR POSTO (rapido e correto) ===
    // Um posto so fica "Aguardando display" quando NAO tem nenhum display
    // disponivel em estoque, ou seja: (cadastrados em cadastroMalotes) - (em transito) <= 0.
    // "Em transito" = display cujo ULTIMO movimento em ciMalotes e tipo=1 (saiu e nao voltou).
    // Consultas agregadas (GROUP BY) — sem subconsulta por linha (era a causa da lentidao).
    if (lacresTabelaExiste($pdo_controle, 'ciMalotes')) {
        $estoque_total    = array();  // posto_pad => total de displays cadastrados
        $estoque_transito = array();  // posto_pad => displays em transito
        $estoque_data     = array();  // posto_pad => data mais recente de envio em transito
        $temCadastro = lacresTabelaExiste($pdo_controle, 'cadastroMalotes');

        // 1) Total de displays cadastrados por posto (universo conhecido)
        if ($temCadastro) {
            try {
                $stReg = $pdo_controle->query(
                    "SELECT LPAD(CAST(posto AS UNSIGNED),3,'0') AS posto_pad, COUNT(*) AS total
                     FROM cadastroMalotes
                     WHERE posto IS NOT NULL AND posto <> ''
                     GROUP BY posto_pad"
                );
                while ($r = $stReg->fetch(PDO::FETCH_ASSOC)) {
                    if ($r['posto_pad'] === null || $r['posto_pad'] === '') continue;
                    $estoque_total[$r['posto_pad']] = (int)$r['total'];
                }
            } catch (Exception $e) { /* tolera */ }
        }

        // 2) Displays em transito por posto (ultimo movimento = tipo 1)
        //    Derived table com MAX(id) por leitura => escaneia ciMalotes 1x.
        //    Posto resolvido pelo cadastro; se nao cadastrado, usa o posto do proprio ciMalotes.
        try {
            // So confia no posto do cadastro quando ele e numerico; caso contrario
            // (ex.: 'central' e outros rotulos textuais) usa o posto do proprio ciMalotes.
            $postoExpr = $temCadastro
                ? "LPAD(CAST(CASE WHEN c.posto REGEXP '^[0-9]+$' THEN c.posto ELSE mt.posto END AS UNSIGNED),3,'0')"
                : "LPAD(CAST(mt.posto AS UNSIGNED),3,'0')";
            $joinCad = $temCadastro ? "LEFT JOIN cadastroMalotes c ON c.leitura = mt.leitura" : "";
            $stTransito = $pdo_controle->query(
                "SELECT " . $postoExpr . " AS posto_pad,
                        COUNT(*) AS qt, MAX(DATE(mt.data)) AS data_mov
                 FROM (
                     SELECT m1.leitura, m1.posto, m1.data
                     FROM ciMalotes m1
                     INNER JOIN (SELECT leitura, MAX(id) AS maxid FROM ciMalotes GROUP BY leitura) lt
                             ON m1.id = lt.maxid
                     WHERE m1.tipo = 1
                 ) mt
                 " . $joinCad . "
                 GROUP BY posto_pad"
            );
            while ($r = $stTransito->fetch(PDO::FETCH_ASSOC)) {
                $cod = $r['posto_pad'];
                if ($cod === null || $cod === '') continue;
                $estoque_transito[$cod] = (int)$r['qt'];
                $estoque_data[$cod] = isset($r['data_mov']) ? $r['data_mov'] : '';
            }
        } catch (Exception $e) { /* tolera */ }

        // 3) Define status por posto: avisa SO quando nao ha display disponivel
        $postos_conhecidos = array();
        foreach ($estoque_total as $cod => $v)    { $postos_conhecidos[$cod] = true; }
        foreach ($estoque_transito as $cod => $v) { $postos_conhecidos[$cod] = true; }
        foreach ($postos_conhecidos as $cod => $v) {
            if ($cod === '000') continue; // bucket sintetico (postos textuais) — nao e posto real
            $total    = isset($estoque_total[$cod]) ? $estoque_total[$cod] : 0;
            $transito = isset($estoque_transito[$cod]) ? $estoque_transito[$cod] : 0;
            $disponivel = $total - $transito;
            lacresGarantirStatus($status_postos, $cod);
            $status_postos[$cod]['estoque_total'] = $total;
            $status_postos[$cod]['estoque_transito'] = $transito;
            $status_postos[$cod]['estoque_disponivel'] = $disponivel;
            // Sem estoque disponivel => aguardando display
            if ($disponivel <= 0) {
                $status_postos[$cod]['display'] = 'fora';
                $status_postos[$cod]['display_data'] = isset($estoque_data[$cod]) ? $estoque_data[$cod] : '';
            }
        }
    }

    // 3) Postos bloqueados
    if (lacresTabelaExiste($pdo_controle, 'ciPostosBloqueados')) {
        try {
            $stBloq = $pdo_controle->query("SELECT posto, motivo FROM ciPostosBloqueados WHERE ativo=1");
            while ($r = $stBloq->fetch(PDO::FETCH_ASSOC)) {
                $cod = str_pad(preg_replace('/\D/', '', (string)$r['posto']), 3, '0', STR_PAD_LEFT);
                if ($cod === '000' || $cod === '') continue;
                lacresGarantirStatus($status_postos, $cod);
                $status_postos[$cod]['bloqueio'] = isset($r['motivo']) ? $r['motivo'] : '';
            }
        } catch (Exception $e) { /* tolera */ }
    }

    // 4) Postos com restricao ativa
    if (lacresTabelaExiste($pdo_controle, 'ciPostosRestricoes')) {
        try {
            $stRest = $pdo_controle->query("SELECT posto, tipo, motivo FROM ciPostosRestricoes WHERE ativo=1");
            while ($r = $stRest->fetch(PDO::FETCH_ASSOC)) {
                $cod = str_pad(preg_replace('/\D/', '', (string)$r['posto']), 3, '0', STR_PAD_LEFT);
                if ($cod === '000' || $cod === '') continue;
                lacresGarantirStatus($status_postos, $cod);
                $status_postos[$cod]['restricao_tipo'] = isset($r['tipo']) ? $r['tipo'] : '';
                $status_postos[$cod]['restricao_motivo'] = isset($r['motivo']) ? $r['motivo'] : '';
            }
        } catch (Exception $e) { /* tolera */ }
    }
} catch (Exception $e) {
    // Silenciar erro - continuar sem status
}

// Helper: este posto usa display dos Correios?
// Postos POUPA TEMPO da Capital + Regiao Metropolitana (codigos 005 a 080)
// ainda NAO usam display Correios — nao exibir nada sobre display para eles.
if (!function_exists('lacresPostoUsaDisplay')) {
    function lacresPostoUsaDisplay($grupo, $codigo) {
        if ($grupo === 'POUPA TEMPO') {
            $n = (int)preg_replace('/\D/', '', (string)$codigo);
            if ($n >= 5 && $n <= 80) { return false; }
        }
        return true;
    }
}

// Helper: a linha tem alerta de display (contorno amarelo)?
if (!function_exists('lacresLinhaAlertaDisplay')) {
    function lacresLinhaAlertaDisplay($status, $usaDisplay = true) {
        if (!$usaDisplay) return false;
        return $status && isset($status['display']) && $status['display'] === 'fora';
    }
}

// Helper: monta os selos (badges) de status de um posto
if (!function_exists('lacresBadgesStatus')) {
    function lacresBadgesStatus($status, $usaDisplay = true) {
        if (!$status) return '';
        $html = '';
        // Info de display so para postos que usam display Correios.
        if ($usaDisplay) {
            $total = isset($status['estoque_total']) ? (int)$status['estoque_total'] : null;
            $trans = isset($status['estoque_transito']) ? (int)$status['estoque_transito'] : null;
            $disp  = isset($status['estoque_disponivel']) ? (int)$status['estoque_disponivel'] : null;
            $local = ($disp !== null && $disp > 0) ? $disp : 0;
            $conhecido = ($total !== null && $total > 0) || ($trans !== null && $trans > 0);
            $dt = !empty($status['display_data']) ? lacresDataBr($status['display_data']) : '';
            if (isset($status['display']) && $status['display'] === 'fora') {
                // Sem display disponivel localmente => aviso amarelo (com a contagem)
                if ($conhecido) {
                    $titulo = 'Nenhum display local (' . (int)$trans . ' em transito de ' . (int)$total . ' cadastrados)';
                } else {
                    $titulo = 'Nenhum display disponivel';
                }
                if ($dt) { $titulo .= ' — ultimo envio em ' . $dt; }
                $rotulo = '&#9888; Aguardando display';
                if ($conhecido) { $rotulo .= ' (' . (int)$trans . ' em transito)'; }
                $html .= '<span class="badge-status badge-display-fora nao-imprimir" title="' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '">' . $rotulo . '</span>';
            } else if ($conhecido) {
                // Ha display local disponivel => badge informativo com a contagem
                $titulo = $local . ' display(s) local, ' . (int)$trans . ' em transito de ' . (int)$total . ' cadastrados';
                $rotulo = '&#128230; ' . $local . ' local &middot; ' . (int)$trans . ' em transito';
                $html .= '<span class="badge-status badge-display-info nao-imprimir" title="' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '">' . $rotulo . '</span>';
            }
        }
        if ($status['bloqueio'] !== null) {
            $mot = trim((string)$status['bloqueio']);
            $txt = $mot !== '' ? 'Bloqueado: ' . $mot : 'Bloqueado';
            $html .= '<span class="badge-status badge-bloqueio nao-imprimir" title="' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '">&#9940; ' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        if ($status['restricao_tipo'] !== null) {
            $tipo = trim((string)$status['restricao_tipo']);
            $mot = trim((string)$status['restricao_motivo']);
            $txt = ($tipo !== '' ? ucfirst($tipo) : 'Restricao') . ($mot !== '' ? ': ' . $mot : '');
            $html .= '<span class="badge-status badge-restricao nao-imprimir" title="' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '">&#9873; ' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        return $html;
    }
}

// Helper: data ISO -> pt-BR (dd/mm/aaaa)
if (!function_exists('lacresDataBr')) {
    function lacresDataBr($iso) {
        if (empty($iso)) return '';
        $ts = strtotime($iso);
        if ($ts === false) return $iso;
        return date('d/m/Y', $ts);
    }
}


// Atribuição de lacres
// Detectar se houve recálculo por lacre (campo hidden no form de filtro)
$recalculo_por_lacre = false;
if ((isset($_GET['recalculo_por_lacre']) && $_GET['recalculo_por_lacre'] === '1') || (isset($_POST['recalculo_por_lacre']) && $_POST['recalculo_por_lacre'] === '1')) {
    $recalculo_por_lacre = true;
}
$recalculo_grupo = '';
if (isset($_POST['recalculo_grupo'])) {
    $recalculo_grupo = trim((string)$_POST['recalculo_grupo']);
} elseif (isset($_GET['recalculo_grupo'])) {
    $recalculo_grupo = trim((string)$_GET['recalculo_grupo']);
}
$grupos_recalculo_validos = array('CAPITAL', 'CENTRAL IIPR', 'REGIONAIS');
if (!in_array($recalculo_grupo, $grupos_recalculo_validos, true)) {
    $recalculo_grupo = '';
}
// Se veio snapshot de lacres (adicionar posto), nao recalcular
if (!empty($_SESSION['snapshot_lacres_ativo'])) {
    $recalculo_por_lacre = false;
    $recalculo_grupo = '';
}
$recalcular_capital = $recalculo_por_lacre && $recalculo_grupo === 'CAPITAL';
$recalcular_central = $recalculo_por_lacre && $recalculo_grupo === 'CENTRAL IIPR';
$recalcular_regionais = $recalculo_por_lacre && $recalculo_grupo === 'REGIONAIS';

// v8.13.4: Inicializadores padrão ZERADOS (não preencher automaticamente)
// Usuário deve digitar lacres iniciais nos inputs do topo para cada grupo
$lacre_atual_capital = $lacre_capital;
$lacre_atual_central = $lacre_central;
$lacre_atual_regionais = $lacre_regionais;
$ultimo_central = null;

// Se houve recálculo por lacre, iremos sobrescrever totalmente os valores
// por grupo (CAPITAL, CENTRAL IIPR, REGIONAIS) abaixo, ignorando valores
// anteriores em sessão ou base.

// v8.13.4 CAPITAL: Só preenche se usuário digitou lacre inicial (>0)
// - Primeira linha: lacre_iipr=N, lacre_correios=N+1
// - Segunda linha: lacre_iipr=N+2, lacre_correios=N+3
// - Exemplo: lacre inicial=18 → linhas: 18/19, 20/21, 22/23...
// - Se lacre_capital=0 ou vazio: deixa TODOS os inputs em branco
if ($recalcular_capital && extrairUltimoLacreSequencial($lacre_capital) > 0) {
    $lacre_iipr_cur = extrairUltimoLacreSequencial($lacre_capital);
    $lacre_corr_cur = $lacre_iipr_cur + 1;
    foreach ($dados['CAPITAL'] as &$linha) {
        $indice = $linha['posto_codigo'];
        $linha['lacre_iipr'] = $lacre_iipr_cur;
        $linha['lacre_correios'] = $lacre_corr_cur;
        // v8.13.4: Garantir que IIPR e Correios são SEMPRE distintos
        if ($linha['lacre_iipr'] === $linha['lacre_correios']) {
            $linha['lacre_correios'] = $linha['lacre_iipr'] + 1;
        }
        $lacre_iipr_cur += 2;
        $lacre_corr_cur += 2;
    }
    unset($linha);
} else {
    // Sem recálculo: usar lacres personalizados da sessão ou manter existentes
    foreach ($dados['CAPITAL'] as &$linha) {
        $indice = $linha['posto_codigo'];
        // Garantir valores padrão para evitar Notice
        if (!isset($linha['lacre_iipr'])) {
            $linha['lacre_iipr'] = '';
        }
        if (!isset($linha['lacre_correios'])) {
            $linha['lacre_correios'] = '';
        }
        // Sobrescrever com valores personalizados se existirem
        if (isset($_SESSION['lacres_personalizados'][$indice]['iipr'])) {
            $linha['lacre_iipr'] = $_SESSION['lacres_personalizados'][$indice]['iipr'];
        }
        if (isset($_SESSION['lacres_personalizados'][$indice]['correios'])) {
            $linha['lacre_correios'] = $_SESSION['lacres_personalizados'][$indice]['correios'];
        }
    }
    unset($linha);
}

// v8.13.4 CENTRAL IIPR: Só preenche se usuário digitou lacre inicial (>0)
// - Lacres IIPR sequenciais (+1): 5, 6, 7, 8, 9, 10, 11...
// - Lacre Correios: ÚLTIMO lacre IIPR + 1 (aplicado a TODOS os postos do grupo)
// - Exemplo: 7 postos com lacre inicial=5 → IIPR: 5,6,7,8,9,10,11 | Correios (todos): 12
// - Com SPLITs: cada grupo visual tem seu próprio lacre Correios = max(IIPR_grupo) + 1
// - Se lacre_central=0 ou vazio: deixa TODOS os inputs em branco
if ($recalcular_central && extrairUltimoLacreSequencial($lacre_central) > 0) {
    $lacre_iipr_cur = extrairUltimoLacreSequencial($lacre_central);
    foreach ($dados['CENTRAL IIPR'] as &$linha) {
        $indice = $linha['posto_codigo'];
        $linha['lacre_iipr'] = $lacre_iipr_cur;
        $ultimo_central = $lacre_iipr_cur;  // Atualiza o último IIPR gerado
        $lacre_iipr_cur += 1;  // Incremento sequencial +1
    }
    unset($linha);
} else {
    // Sem recálculo: usar lacres personalizados da sessão ou manter existentes
    foreach ($dados['CENTRAL IIPR'] as &$linha) {
        $indice = $linha['posto_codigo'];
        // Garantir valor padrão para evitar Notice
        if (!isset($linha['lacre_iipr'])) {
            $linha['lacre_iipr'] = '';
        }
        // Sobrescrever com valor personalizado se existir
        if (isset($_SESSION['lacres_personalizados'][$indice]['iipr'])) {
            $linha['lacre_iipr'] = $_SESSION['lacres_personalizados'][$indice]['iipr'];
        }
        // Atualizar $ultimo_central para ser usado no bloco seguinte
        if (isset($linha['lacre_iipr']) && $linha['lacre_iipr'] !== '') {
            $ultimo_central = $linha['lacre_iipr'];
        }
    }
    unset($linha);
}

// v8.13.4 CENTRAL IIPR: Atribuir lacre Correios (GARANTIDO: sempre último+1, nunca duplicado)
// - Com recalculo_por_lacre: TODOS os postos recebem o ÚLTIMO lacre IIPR + 1
// - Sem recalculo_por_lacre: Respeita splits/malotes por grupo (compatibilidade)
// - Exemplo: último IIPR=11 → lacre Correios de todos=12 (NUNCA 11)
// - Validação: lacre Correios NUNCA pode ser igual ao último IIPR
if (!empty($dados['CENTRAL IIPR']) && $ultimo_central !== null) {
    if ($recalcular_central && extrairUltimoLacreSequencial($lacre_central) > 0) {
        // v8.13.4: GARANTIDO - último IIPR + 1 vira lacre Correios de TODOS
        $lacreCorreiosCentral = $ultimo_central + 1;
        // v8.13.4: Validação extra - garantir que Correios ≠ último IIPR
        if ($lacreCorreiosCentral === $ultimo_central) {
            $lacreCorreiosCentral = $ultimo_central + 1;
        }
        foreach ($dados['CENTRAL IIPR'] as &$linha) {
            $linha['lacre_correios'] = $lacreCorreiosCentral;
        }
        unset($linha);
    } else {
        // Sem recálculo: manter lógica de SPLIT/malotes (para compatibilidade)
        // Construir mapeamento de grupos para central com base em $splitsCentral
        $central_groups = array(); // grupo => array(posicoes)
        $central_group_by_posto = array(); // posto_codigo => grupo
        $group_index = 0;
        foreach ($dados['CENTRAL IIPR'] as $idx => $linha) {
            $posto_code = $linha['posto_codigo'];
            // Se este posto está configurado como SPLIT, inicia novo grupo *antes* de atribuir
            if (!empty($splitsCentral) && in_array($posto_code, $splitsCentral)) {
                $group_index++;
            }
            if (!isset($central_groups[$group_index])) $central_groups[$group_index] = array();
            $central_groups[$group_index][] = $posto_code;
            $central_group_by_posto[$posto_code] = $group_index;
        }

        // v8.13.3: Gerar lacre Correios por grupo = max(lacre_iipr_grupo) + 1
        // Cada grupo visual (separado por SPLIT) tem seu próprio lacre Correios
        $group_lacres = array();
        foreach ($central_groups as $g => $postos_grupo) {
            $max_iipr_grupo = 0;
            // Encontrar maior lacre_iipr do grupo
            foreach ($dados['CENTRAL IIPR'] as $linha_central) {
                if (in_array($linha_central['posto_codigo'], $postos_grupo)) {
                    $iipr_val = isset($linha_central['lacre_iipr']) && $linha_central['lacre_iipr'] !== '' ? (int)$linha_central['lacre_iipr'] : 0;
                    if ($iipr_val > $max_iipr_grupo) {
                        $max_iipr_grupo = $iipr_val;
                    }
                }
            }
            // Lacre Correios do grupo = max(IIPR) + 1
            $group_lacres[$g] = $max_iipr_grupo + 1;
        }

        // Atribuir lacre_correios a cada linha de acordo com seu grupo ou sessão
        foreach ($dados['CENTRAL IIPR'] as &$linha) {
            $indice = $linha['posto_codigo'];
            // Garantir valor padrão
            if (!isset($linha['lacre_correios'])) {
                $linha['lacre_correios'] = '';
            }
            // Sobrescrever com valor personalizado ou calculado
            if (isset($_SESSION['lacres_personalizados'][$indice]['correios'])) {
                $linha['lacre_correios'] = $_SESSION['lacres_personalizados'][$indice]['correios'];
            } else {
                $gidx = isset($central_group_by_posto[$indice]) ? $central_group_by_posto[$indice] : 0;
                $linha['lacre_correios'] = isset($group_lacres[$gidx]) ? $group_lacres[$gidx] : $base_lacre;
            }
        }
        unset($linha);

        // Expor variáveis úteis para a renderização (template abaixo usa estas informações)
        $central_group_first = array();
        foreach ($central_groups as $g => $posts) {
            if (!empty($posts)) {
                $first = $posts[0];
                $central_group_first[$first] = true;
                foreach ($posts as $p) {
                    if ($p !== $first && !isset($central_group_first[$p])) {
                        $central_group_first[$p] = false;
                    }
                }
            }
        }
    }
}

// v8.13.4 REGIONAIS: Só preenche se usuário digitou lacre inicial (>0)
// - Cada linha representa uma regional com par de lacres SEMPRE diferentes
// - Esses lacres serão aplicados a TODOS os postos daquela regional ao salvar
// - Lacre IIPR e Lacre Correios DEVEM ser diferentes (ex: 5/6, não 5/5)
// - Se lacre_regionais=0 ou vazio: deixa TODOS os inputs em branco
if ($recalcular_regionais && extrairUltimoLacreSequencial($lacre_regionais) > 0) {
    $lacre_iipr_cur = extrairUltimoLacreSequencial($lacre_regionais);
    $lacre_corr_cur = $lacre_iipr_cur + 1;
    foreach ($dados['REGIONAIS'] as &$linha) {
        $indice = $linha['posto_codigo'];
        $linha['lacre_iipr'] = $lacre_iipr_cur;
        $linha['lacre_correios'] = $lacre_corr_cur;
        // v8.13.4: Garantir que IIPR e Correios são SEMPRE distintos
        if ($linha['lacre_iipr'] === $linha['lacre_correios']) {
            $linha['lacre_correios'] = $linha['lacre_iipr'] + 1;
        }
        $lacre_iipr_cur += 2;
        $lacre_corr_cur += 2;
    }
    unset($linha);
} else {
    // Sem recálculo: usar lacres personalizados da sessão ou manter existentes
    foreach ($dados['REGIONAIS'] as &$linha) {
        $indice = $linha['posto_codigo'];
        // Garantir valores padrão para evitar Notice
        if (!isset($linha['lacre_iipr'])) {
            $linha['lacre_iipr'] = '';
        }
        if (!isset($linha['lacre_correios'])) {
            $linha['lacre_correios'] = '';
        }
        // Sobrescrever com valores personalizados se existirem
        if (isset($_SESSION['lacres_personalizados'][$indice]['iipr'])) {
            $linha['lacre_iipr'] = $_SESSION['lacres_personalizados'][$indice]['iipr'];
        }
        if (isset($_SESSION['lacres_personalizados'][$indice]['correios'])) {
            $linha['lacre_correios'] = $_SESSION['lacres_personalizados'][$indice]['correios'];
        }
    }
    unset($linha);
}

// Aplicar lacres da sessao quando existirem (nao sobrescreve valores ja preenchidos)
foreach ($dados as $grupo_nome => &$grupo_itens) {
    foreach ($grupo_itens as &$linha) {
        $indice = $linha['posto_codigo'];
        if (!isset($linha['lacre_iipr'])) { $linha['lacre_iipr'] = ''; }
        if (!isset($linha['lacre_correios'])) { $linha['lacre_correios'] = ''; }
        if (isset($_SESSION['lacres_personalizados'][$indice])) {
            $lp = $_SESSION['lacres_personalizados'][$indice];
            if ($linha['lacre_iipr'] === '' && isset($lp['iipr']) && $lp['iipr'] !== '') {
                $linha['lacre_iipr'] = $lp['iipr'];
            }
            if ($linha['lacre_correios'] === '' && isset($lp['correios']) && $lp['correios'] !== '') {
                $linha['lacre_correios'] = $lp['correios'];
            }
        }
    }
    unset($linha);
}
unset($grupo_itens);

// Restaurar snapshot completo dos lacres apos recálculo
if (!empty($_SESSION['snapshot_lacres_ativo']) && isset($_SESSION['snapshot_lacres_full']) && is_array($_SESSION['snapshot_lacres_full'])) {
    foreach ($dados as $grupo => &$itens) {
        foreach ($itens as &$posto) {
            $codigo = $posto['posto_codigo'];
            $regional = isset($posto['regional']) ? $posto['regional'] : '0';
            $chave = $regional . '|' . $codigo;
            $vals = null;
            if (isset($_SESSION['snapshot_lacres_by_posto'][$codigo])) {
                $vals = $_SESSION['snapshot_lacres_by_posto'][$codigo];
            } elseif (isset($_SESSION['snapshot_lacres_full'][$chave])) {
                $vals = $_SESSION['snapshot_lacres_full'][$chave];
            }
            if ($vals) {
                if (!isset($posto['lacre_iipr'])) $posto['lacre_iipr'] = '';
                if (!isset($posto['lacre_correios'])) $posto['lacre_correios'] = '';
                if (isset($vals['lacre_iipr']) && (string)$vals['lacre_iipr'] !== '') {
                    $posto['lacre_iipr'] = $vals['lacre_iipr'];
                    $_SESSION['lacres_personalizados'][$codigo]['iipr'] = $vals['lacre_iipr'];
                }
                if (isset($vals['lacre_correios']) && (string)$vals['lacre_correios'] !== '') {
                    $posto['lacre_correios'] = $vals['lacre_correios'];
                    $_SESSION['lacres_personalizados'][$codigo]['correios'] = $vals['lacre_correios'];
                }
            }
        }
        unset($posto);
    }
    unset($itens);

    $_SESSION['snapshot_lacres_ativo'] = 0;
    unset($_SESSION['snapshot_lacres_full']);
    unset($_SESSION['snapshot_lacres_by_posto']);
}

// Lista de regionais para dropdown
$todas_regionais = array();
foreach ($regionais_info as $num => $info) {
    $todas_regionais[$num] = $info['nome'];
}

// Debug - Informações sobre a sessão para ajudar na depuração
$mostrar_debug = isset($_GET['debug']) && $_GET['debug'] === '1';

// v8.14.9.2: Definir nome do PDF baseado no tipo de ofício e data
$nome_pdf_titulo = 'Ofício Lacres';
$id_despacho_atual = 0;
$grupo_atual = '';
$data_atual = date('d-m-Y');

// Tentar obter o grupo do último despacho ativo
try {
    if (isset($ultimo_despacho_correios) && $ultimo_despacho_correios > 0) {
        $id_despacho_atual = (int)$ultimo_despacho_correios;
        $grupo_atual = 'correios';
    } else {
        $stmt_grupo = $pdo_controle->query("
            SELECT id, grupo 
            FROM ciDespachos 
            WHERE ativo = 1 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $row_grupo = $stmt_grupo->fetch(PDO::FETCH_ASSOC);
        if ($row_grupo) {
            $id_despacho_atual = (int)$row_grupo['id'];
            $grupo_atual = strtolower(str_replace(' ', '', $row_grupo['grupo'])); // 'correios' ou 'poupatempo'
        }
    }
} catch (Exception $e) {
    // Se falhar, usa padrão antigo
    $nome_pdf_titulo = 'Ofício Lacres V8.2 - ' . date('d-m-Y');
}

if (isset($_SESSION['id_despacho_correios']) && $_SESSION['id_despacho_correios'] > 0) {
    $id_despacho_atual = (int)$_SESSION['id_despacho_correios'];
    $grupo_atual = 'correios';
}

if ($id_despacho_atual > 0 && $grupo_atual !== '') {
    // v2.0.0: padrao unico de nome de arquivo PDF: {id}_{tipo}.pdf (sem data)
    // tipo = "correios" ou "poupatempo" (sem espaco/underscore, sem variantes
    // metropolitano/interior). Igual ao gerado em modelo_oficio_poupa_tempo.php
    // e usado pelos links em consulta_producao.php, despachos_poupatempo.php
    // e busca_producao_mobile.php.
    if ($grupo_atual === 'poupa tempo' || $grupo_atual === 'poupaTempo') {
        $tipo_pdf = 'poupatempo';
    } else {
        $tipo_pdf = $grupo_atual; // 'correios', 'poupatempo', etc.
    }
    $nome_pdf_titulo = $id_despacho_atual . "_" . $tipo_pdf;
}

function compactarSequenciaLacres($valores) {
    if (!is_array($valores) || empty($valores)) {
        return '';
    }
    $nums = array();
    foreach ($valores as $valor) {
        $v = (int)$valor;
        if ($v > 0) {
            $nums[$v] = $v;
        }
    }
    if (empty($nums)) {
        return '';
    }
    ksort($nums);
    $nums = array_values($nums);
    $partes = array();
    $inicio = $nums[0];
    $anterior = $nums[0];
    $total = count($nums);
    for ($i = 1; $i < $total; $i++) {
        $atual = $nums[$i];
        if ($atual === ($anterior + 1)) {
            $anterior = $atual;
            continue;
        }
        $partes[] = ($inicio === $anterior) ? (string)$inicio : ($inicio . '-' . $anterior);
        $inicio = $atual;
        $anterior = $atual;
    }
    $partes[] = ($inicio === $anterior) ? (string)$inicio : ($inicio . '-' . $anterior);
    return implode(', ', $partes);
}

$resumo_oficio_correios = array();
if ($grupo_atual === 'correios' && $id_despacho_atual > 0) {
    try {
        $mapaNomesPostoResumo = array();
        foreach ($dados as $grupoResumo => $itensResumo) {
            foreach ($itensResumo as $itemResumo) {
                $mapaNomesPostoResumo[$itemResumo['posto_codigo']] = $itemResumo['posto_nome'];
            }
        }

        $stmtResumoOficio = $pdo_controle->prepare("SELECT d.posto, d.lote, d.etiquetaiipr, d.grupo_iipr, d.etiquetacorreios, d.grupo_correios, d.etiqueta_correios,
                   LPAD(CAST(COALESCE(r.regional, d.posto) AS UNSIGNED), 3, '0') AS regional_codigo
            FROM ciDespachoLotes d
            LEFT JOIN ciRegionais r ON LPAD(CAST(r.posto AS UNSIGNED), 3, '0') = LPAD(CAST(d.posto AS UNSIGNED), 3, '0')
            WHERE id_despacho = ?
            ORDER BY regional_codigo, d.posto, d.lote");
        $stmtResumoOficio->execute(array($id_despacho_atual));
        $gruposResumo = array();

        while ($rowResumo = $stmtResumoOficio->fetch(PDO::FETCH_ASSOC)) {
            $postoResumo = (string)$rowResumo['posto'];
            $postoResumoPad = preg_match('/^M/i', $postoResumo) ? $postoResumo : str_pad($postoResumo, 3, '0', STR_PAD_LEFT);
            $regionalCodigoResumo = isset($rowResumo['regional_codigo']) ? str_pad((string)$rowResumo['regional_codigo'], 3, '0', STR_PAD_LEFT) : $postoResumoPad;
            $usarRegionalResumo = ($regionalCodigoResumo !== '' && !in_array($regionalCodigoResumo, array('000', '001', '999'), true));
            $destinoCodigoResumo = $usarRegionalResumo ? $regionalCodigoResumo : $postoResumoPad;
            $destinoNomeResumo = $usarRegionalResumo
                ? ('Regional ' . $regionalCodigoResumo)
                : (isset($mapaNomesPostoResumo[$postoResumoPad]) ? $mapaNomesPostoResumo[$postoResumoPad] : $postoResumoPad);
            $grupoCorreiosResumo = trim((string)$rowResumo['grupo_correios']);
            $grupoIiprResumo = trim((string)$rowResumo['grupo_iipr']);
            $lacreIiprResumo = isset($rowResumo['etiquetaiipr']) ? (int)$rowResumo['etiquetaiipr'] : 0;
            $lacreCorreiosResumo = isset($rowResumo['etiquetacorreios']) ? (int)$rowResumo['etiquetacorreios'] : 0;
            $etiquetaResumo = trim((string)$rowResumo['etiqueta_correios']);
            $fallback = $lacreCorreiosResumo . '|' . $etiquetaResumo . '|' . ($grupoIiprResumo !== '' ? $grupoIiprResumo : $lacreIiprResumo);
            $chaveResumo = $destinoCodigoResumo . '|' . ($grupoCorreiosResumo !== '' ? $grupoCorreiosResumo : $fallback);

            if (!isset($gruposResumo[$chaveResumo])) {
                $gruposResumo[$chaveResumo] = array(
                    'posto_codigo' => $destinoCodigoResumo,
                    'posto_nome' => $destinoNomeResumo,
                    'lacres_iipr' => array(),
                    'lacres_correios' => array(),
                    'etiqueta_correios' => '',
                    'ordem' => count($gruposResumo)
                );
            }
            if ($lacreIiprResumo > 0) {
                $gruposResumo[$chaveResumo]['lacres_iipr'][$lacreIiprResumo] = $lacreIiprResumo;
            }
            if ($lacreCorreiosResumo > 0) {
                $gruposResumo[$chaveResumo]['lacres_correios'][$lacreCorreiosResumo] = $lacreCorreiosResumo;
            }
            if ($etiquetaResumo !== '') {
                $gruposResumo[$chaveResumo]['etiqueta_correios'] = $etiquetaResumo;
            }
        }

        foreach ($gruposResumo as $itemResumo) {
            $resumo_oficio_correios[] = array(
                'posto_codigo' => $itemResumo['posto_codigo'],
                'posto_nome' => $itemResumo['posto_nome'],
                'lacre_iipr' => compactarSequenciaLacres($itemResumo['lacres_iipr']),
                'lacre_correios' => compactarSequenciaLacres($itemResumo['lacres_correios']),
                'etiqueta_correios' => $itemResumo['etiqueta_correios'],
                'ordem' => $itemResumo['ordem']
            );
        }

        usort($resumo_oficio_correios, function($a, $b) {
            if ($a['posto_codigo'] === $b['posto_codigo']) {
                return $a['ordem'] - $b['ordem'];
            }
            return strcmp($a['posto_codigo'], $b['posto_codigo']);
        });
    } catch (Exception $e) {
        $resumo_oficio_correios = array();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($nome_pdf_titulo, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        :root {
            --font-size-base: 12px;
            --font-size-large: 14px;
            --font-size-xlarge: 16px;
        }
        
        .somente-impressao { display: none; }
        body {
            font-family: Arial, sans-serif;
            font-size: var(--font-size-base);
            margin: 15px;
            transition: font-size 0.3s ease;
        }
        body.zoom-level-1 { font-size: var(--font-size-large); }
        body.zoom-level-2 { font-size: var(--font-size-xlarge); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid black; padding: 3px; text-align: center; }
        th:nth-child(1), td:nth-child(1) { width: 35%; text-align: left; }
        th:nth-child(2), td:nth-child(2) { width: 12%; }
        th:nth-child(3), td:nth-child(3) { width: 12%; }
        th:nth-child(4), td:nth-child(4) { width: 28%; }
        th:nth-child(5), td:nth-child(5) { width: 13%; }
        th { background-color: #f0f0f0; }
        input[type='text'] { width: 240px; font-family: monospace; text-align: center; font-size: inherit; }
        input.lacre { width: 100%; min-width: 120px; text-align: left; font-weight: bold; font-family: 'Courier New', Courier, monospace; }
        .lacre-wrapper { display: flex; align-items: center; gap: 4px; width: 100%; }
        .lacre-wrapper .lacre { flex: 1 1 auto; width: 100%; }
        .btn-lacre-avulso {
            flex: 0 0 auto;
            border: 1px solid #b8c2cc;
            background: #f4f6f8;
            color: #4b5a68;
            border-radius: 4px;
            font-size: 10px;
            line-height: 1;
            padding: 4px 5px;
            cursor: pointer;
            min-width: 28px;
        }
        .btn-lacre-avulso.ativo {
            background: #fff3cd;
            border-color: #d9b66d;
            color: #7a4f00;
        }
        .lacre-avulso {
            background: #fff8e1;
            border-color: #d9b66d !important;
        }
        .quadro { border: 1px solid black; padding: 6px; margin-bottom: 8px; }
        .topo-formulario { display: flex; flex-wrap: wrap; gap: 12px; }
        .topo-formulario label { display: flex; flex-direction: column; }
        .lacre-topo-legado { display: none; }
        .split-central-separador td {
            border-top: 14px solid #ffffff;
            box-shadow: inset 0 2px 0 rgba(31, 79, 191, 0.16);
        }
        .alinhado { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
        .texto-ajuda { font-size: 11px; color: #666; margin-top: 6px; }
        
        .assinaturas {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            page-break-inside: avoid;
            width: 100%;
        }
        .assinatura-esquerda, .assinatura-direita {
            width: 45%;
            text-align: center;
        }
        .assinatura-esquerda hr, .assinatura-direita hr {
            width: 80%;
            margin: 5px auto;
            border: 1px solid #000;
        }
        .assinatura-esquerda p, .assinatura-direita p {
            margin: 10px 0;
            font-size: 11px;
        }
        
        /* Botão de zoom (compacto) */
        .zoom-control {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 10000;
            display: inline-flex;
            gap: 4px;
            background: transparent;
            border: none;
            border-radius: 4px;
            padding: 0;
            box-shadow: none;
            width: auto;
        }
        .zoom-btn {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 4px 8px;
            margin: 0 2px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .zoom-btn:hover {
            background: #e9ecef;
        }

        .overlay-processando-global {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.34);
            z-index: 20000;
        }
        .overlay-processando-global.ativo {
            display: flex;
        }
        .overlay-processando-box {
            min-width: 220px;
            padding: 18px 22px;
            border-radius: 12px;
            background: #ffffff;
            color: #1f2937;
            text-align: center;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.25);
            font-size: 15px;
            font-weight: 700;
        }
        .overlay-processando-box::before {
            content: '';
            display: block;
            width: 28px;
            height: 28px;
            margin: 0 auto 10px;
            border-radius: 50%;
            border: 3px solid #dbeafe;
            border-top-color: #2563eb;
            animation: giro-processando 0.9s linear infinite;
        }
        @keyframes giro-processando {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* v9.21.6: Banner central com datas do filtro */
        .datas-filtro-banner {
            position: relative;
            margin: 10px auto 0 auto;
            padding: 8px 12px;
            max-width: 720px;
            text-align: center;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 6px;
            font-size: 13px;
            color: #856404;
            font-weight: bold;
        }
        
        /* Alertas */
        .alerta {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
        .alerta-titulo {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .alerta-lista {
            margin: 5px 0;
            padding-left: 20px;
        }
        
        /* Mensagens */
        .mensagem-sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
        .mensagem-erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
        
        /* V7.9: Estilos para análise de expedição */
        .analise-expedicao {
            background-color: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .analise-expedicao h3 {
            margin-top: 0;
            color: #1565c0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .analise-expedicao .icone {
            font-size: 24px;
        }
        .analise-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .analise-item {
            background-color: white;
            border: 1px solid #bbdefb;
            border-radius: 5px;
            padding: 12px;
        }
        .analise-item h4 {
            margin-top: 0;
            margin-bottom: 8px;
            color: #1565c0;
            font-size: 14px;
        }
        .analise-valor {
            font-size: 18px;
            font-weight: bold;
            color: #0d47a1;
        }
        .analise-diferenca {
            color: #d32f2f;
            font-weight: bold;
        }
        .analise-diferenca.positiva {
            color: #388e3c;
        }
        .autores-faltantes {
            background-color: #fff3e0;
            border: 1px solid #ffcc02;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
        .autores-faltantes h5 {
            margin-top: 0;
            color: #e65100;
        }
        .autor-item {
            background-color: #ffe0b2;
            padding: 5px 8px;
            margin: 5px 0;
            border-radius: 3px;
            font-size: 12px;
        }
        .postos-retirados {
            background-color: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
        .postos-retirados h5 {
            margin-top: 0;
            color: #2e7d32;
        }
        .posto-retirado {
            background-color: #c8e6c9;
            padding: 5px 8px;
            margin: 5px 0;
            border-radius: 3px;
            font-size: 12px;
            display: inline-block;
            margin-right: 10px;
        }
        
        /* V7.9: Detalhes de expedição por data */
        .detalhes-expedicao {
            background-color: #f3e5f5;
            border: 1px solid #ce93d8;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
        .detalhes-expedicao h5 {
            margin-top: 0;
            color: #4a148c;
        }
        .detalhe-item {
            background-color: #e1bee7;
            padding: 5px 8px;
            margin: 5px 0;
            border-radius: 3px;
            font-size: 12px;
        }
        
        /* Alerta de duplicata — toast temporário */
        .alerta-duplicata {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
            border-radius: 4px;
            padding: 8px;
            font-size: 12px;
            margin-top: 5px;
            display: none;
            transition: opacity 0.5s ease;
        }
        
        /* Formulário de cadastro */
        .cadastro-posto {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .cadastro-posto h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #495057;
        }
        .form-cadastro {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .form-cadastro label {
            display: flex;
            flex-direction: column;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-cadastro select,
        .form-cadastro input {
            margin-top: 3px;
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 3px;
        }
        .btn-cadastrar {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 12px;
            border-radius: 3px;
        }
        .btn-cadastrar:hover {
            background-color: #0069d9;
        }
                
        /* Adição manual */
        .quadro-adicionar {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .quadro-adicionar h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .form-adicionar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .form-adicionar label {
            display: flex;
            flex-direction: column;
            font-size: 12px;
            font-weight: bold;
        }
        .form-adicionar select,
        .form-adicionar input {
            margin-top: 3px;
            padding: 4px;
            border: 1px solid #ccc;
        }
        .btn-adicionar {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
            border-radius: 3px;
        }
        .btn-adicionar:hover {
            background-color: #45a049;
        }
        
        /* Botões da tabela */
        .btn-add-above, .btn-add-below {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 3px 6px;
            margin: 2px;
            font-size: 10px;
            cursor: pointer;
            border-radius: 3px;
        }
        .btn-add-above:hover, .btn-add-below:hover {
            background-color: #0b7dda;
        }

        .acoes-cell {
            white-space: nowrap;
        }
        .acoes-cell button {
            display: inline-block;
            vertical-align: middle;
        }
        .acoes-cell .btn-add-below {
            margin-left: 4px;
        }
        
        /* Botão excluir */
        .btn-excluir {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 3px 6px;
            margin: 2px;
            font-size: 10px;
            cursor: pointer;
            border-radius: 3px;
        }
        .btn-excluir:hover {
            background-color: #c82333;
        }
        
        /* Botao para limpar coluna */
        .btn-limpar-coluna {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 2px 6px;
            margin-left: 5px;
            font-size: 10px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 3px;
            vertical-align: middle;
        }
        .btn-limpar-coluna:hover {
            background-color: #ee5a5a;
        }
        
        /* Animacao pulsante para botoes de salvamento */
        @keyframes pulsar {
            0%, 100% { 
                box-shadow: 0 0 5px rgba(255, 193, 7, 0.5);
                transform: scale(1);
            }
            50% { 
                box-shadow: 0 0 20px rgba(255, 193, 7, 0.8);
                transform: scale(1.02);
            }
        }
        
        .btn-pulsando {
            animation: pulsar 1.5s ease-in-out infinite;
        }
        
        .btn-salvo {
            animation: none !important;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5) !important;
        }
        
        /* Botão excluir específico para REGIONAIS */
        .btn-excluir-regional {
            background-color: #9C27B0;
            color: white;
            border: none;
            padding: 3px 6px;
            margin: 2px;
            font-size: 10px;
            cursor: pointer;
            border-radius: 3px;
            font-weight: bold;
        }
        .btn-excluir-regional:hover {
            background-color: #7B1FA2;
        }
        
        /* Seleção múltipla de postos */
        .chk-selecao-lacres { display: none; cursor: pointer; accent-color: #c0392b; width: 16px; height: 16px; vertical-align: middle; }
        .grade-modo-selecao .chk-selecao-lacres { display: inline-block; }
        .grade-modo-selecao .acoes-cell .btn-excluir,
        .grade-modo-selecao .acoes-cell .btn-excluir-regional,
        .grade-modo-selecao .acoes-cell .btn-add-below { display: none !important; }
        .controles-selecao-grade { display: flex; align-items: center; gap: 10px; margin: 8px 0 10px 0; flex-wrap: wrap; }
        .btn-selecao-multipla-lacres { padding: 5px 14px; font-size: 12px; background: #fff3cd; border: 1px solid #d4a017; color: #6b5000; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-selecao-multipla-lacres.ativo { background: #c0392b; border-color: #922b21; color: #fff; }
        .btn-excluir-selecionados-lacres { display: none; padding: 5px 14px; font-size: 12px; background: #c0392b; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-excluir-selecionados-lacres.visivel { display: inline-block; }

        /* Botão imprimir */
        .btn-imprimir {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 8px 16px;
            margin: 10px 0;
            font-size: 14px;
            cursor: pointer;
            border-radius: 3px;
            display: inline-block;
        }
        .btn-imprimir i {
            margin-right: 5px;
        }
        .btn-imprimir:hover {
            background-color: #138496;
        }
        
        /* Botão salvar etiquetas */
        .btn-salvar-etiquetas {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            margin: 10px 0 10px 10px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 3px;
            display: inline-block;
        }
        .btn-salvar-etiquetas i {
            margin-right: 5px;
        }
        .btn-salvar-etiquetas:hover {
            background-color: #218838;
        }
        
        /* Modal inserção */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .modal-title {
            margin-top: 0;
            color: #343a40;
        }
        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .modal-form label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .modal-form input, .modal-form select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }
        .modal-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .modal-btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        .modal-btn-save {
            background-color: #28a745;
            color: white;
        }
        
        /* Modal confirmação salvamento */
        .modal-confirmacao {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .modal-confirmacao h3 {
            margin-top: 0;
            color: #155724;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-confirmacao .icone {
            font-size: 24px;
        }
        .modal-confirmacao p {
            margin: 15px 0;
            color: #495057;
        }
        .modal-confirmacao .form-group {
            margin: 15px 0;
        }
        .modal-confirmacao label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #495057;
        }
        .modal-confirmacao input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        .modal-confirmacao .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-confirmacao .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        .modal-confirmacao .btn-cancelar {
            background-color: #6c757d;
            color: white;
        }
        .modal-confirmacao .btn-confirmar {
            background-color: #28a745;
            color: white;
        }
        
        /* Info reorganização */
        .reorg-info {
            margin-top: 10px;
            padding: 8px;
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 4px;
            font-size: 12px;
            color: #0d47a1;
        }
        
        /* Novo aviso regionais */
        .regionais-info {
            margin-top: 10px;
            padding: 8px;
            background-color: #f3e5f5;
            border: 1px solid #ce93d8;
            border-radius: 4px;
            font-size: 12px;
            color: #4a148c;
        }
        
        /* Info validação duplicatas */
        .duplicata-info {
            margin-top: 10px;
            padding: 8px;
            background-color: #fff3e0;
            border: 1px solid #ffcc02;
            border-radius: 4px;
            font-size: 12px;
            color: #e65100;
        }
        
        /* V7.9: Info nova lógica de data */
        .data-info {
            margin-top: 10px;
            padding: 8px;
            background-color: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 4px;
            font-size: 12px;
            color: #2e7d32;
        }
        
        /* Versão info */
        .version-info {
            position: fixed;
            left: 10px;
            bottom: 10px;
            font-size: 11px;
            color: #6c757d;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 3px 6px;
            border-radius: 3px;
        }
        .quadro-resumo-oficio {
            margin: 16px 0;
            padding: 14px;
            background: #fff;
            border: 1px solid #d7e4f0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .quadro-resumo-oficio h3 {
            margin: 0 0 10px;
            color: #16324f;
        }
        .quadro-resumo-oficio .subtitulo {
            margin-bottom: 10px;
            font-size: 12px;
            color: #60758b;
        }
        .quadro-resumo-oficio table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .quadro-resumo-oficio th,
        .quadro-resumo-oficio td {
            border: 1px solid #8f9cab;
            padding: 8px 6px;
            font-size: 12px;
            word-break: break-word;
        }
        .quadro-resumo-oficio th:nth-child(1), .quadro-resumo-oficio td:nth-child(1) { width: 34%; }
        .quadro-resumo-oficio th:nth-child(2), .quadro-resumo-oficio td:nth-child(2) { width: 12%; }
        .quadro-resumo-oficio th:nth-child(3), .quadro-resumo-oficio td:nth-child(3) { width: 12%; }
        .quadro-resumo-oficio th:nth-child(4), .quadro-resumo-oficio td:nth-child(4) { width: 32%; }
        
        /* Debug info */
        .debug-info {
            margin-top: 30px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .debug-info h3 {
            margin-top: 0;
            color: #343a40;
        }
        .debug-info pre {
            font-size: 12px;
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 3px;
            max-height: 300px;
            overflow: auto;
        }

        /* Layout consistente das colunas da grade de lacres */
        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        th, td {
            vertical-align: middle;
        }
        th:nth-child(1), td:nth-child(1) { width: 34%; }
        th:nth-child(2), td:nth-child(2) { width: 12%; }
        th:nth-child(3), td:nth-child(3) { width: 12%; }
        th:nth-child(4), td:nth-child(4) { width: 32%; }
        th:nth-child(5), td:nth-child(5) { width: 10%; }
        td:nth-child(1) {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        input.lacre,
        input.etiqueta-barras {
            width: 100%;
            box-sizing: border-box;
        }
        .lacre-wrapper { width: 100%; }

        /* Avisos de status do posto (display/bloqueio/restricao) */
        tr.linha-display-fora > td { background: #fffbe6; }
        tr.linha-display-fora > td:first-child { box-shadow: inset 4px 0 0 0 #f5c000; }
        .status-badges { display: inline-block; margin-left: 6px; }
        .badge-status {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.3;
            padding: 2px 7px;
            margin: 1px 3px 1px 0;
            border-radius: 10px;
            white-space: nowrap;
            vertical-align: middle;
        }
        .badge-display-fora { background: #fff3cd; color: #7a5b00; border: 1px solid #f5c000; }
        .badge-display-ok   { background: #e6f4ea; color: #1b5e20; border: 1px solid #4caf50; }
        .badge-display-info { background: #e6f4ea; color: #1b5e20; border: 1px solid #4caf50; }
        .badge-bloqueio     { background: #fdecea; color: #b71c1c; border: 1px solid #e53935; }
        .badge-restricao    { background: #fff0e0; color: #b35400; border: 1px solid #ff9800; }
        .legenda-status {
            display: flex; flex-wrap: wrap; gap: 14px; align-items: center;
            margin: 8px 0 4px 0; padding: 8px 12px;
            background: #f7f9fb; border: 1px solid #e0e6ec; border-radius: 6px;
            font-size: 11px; color: #455a64;
        }
        .legenda-status .item { display: inline-flex; align-items: center; gap: 5px; }
        .legenda-status .amostra { width: 14px; height: 14px; border-radius: 3px; display: inline-block; }
        .legenda-status .amostra.amarelo { background: #fff3cd; border: 1px solid #f5c000; }
        .legenda-status .amostra.verde   { background: #e6f4ea; border: 1px solid #4caf50; }
        .legenda-status .amostra.vermelho{ background: #fdecea; border: 1px solid #e53935; }
        .legenda-status .amostra.laranja { background: #fff0e0; border: 1px solid #ff9800; }

        @media print {
            .somente-impressao { display: block !important; }
            tr.linha-display-fora > td { background: transparent !important; }
            tr.linha-display-fora > td:first-child { box-shadow: none !important; }
            .legenda-status { display: none !important; }
            .btn-limpar { display: none !important; }
            .btn-imprimir { display: none !important; }
            .btn-salvar-etiquetas { display: none !important; }
            .no-print { display: none !important; }
            .nao-imprimir { display: none !important; }
            .btn-lacre-avulso { display: none !important; visibility: hidden !important; width: 0 !important; height: 0 !important; padding: 0 !important; margin: 0 !important; overflow: hidden !important; }
            .lacre-wrapper { display: inline !important; }
            .quadro-formulario, .quadro-formulario * { display: none !important; }
            .quadro-adicionar, .quadro-adicionar * { display: none !important; }
            .alerta, .alerta * { display: none !important; }
            .cadastro-posto, .cadastro-posto * { display: none !important; }
            .mensagem-sucesso, .mensagem-erro { display: none !important; }
            .zoom-control { display: none !important; }
            .reorg-info { display: none !important; }
            .regionais-info { display: none !important; }
            .duplicata-info { display: none !important; }
            .data-info { display: none !important; }
            .version-info { display: none !important; }
            .debug-info { display: none !important; }
            .modal-overlay { display: none !important; }
            .alerta-duplicata { display: none !important; }
            .analise-expedicao { display: none !important; }
            #indicador-dias { display: none !important; } /* v9.21.5: Oculta card Status de Conferências */
            
            /* V7.9: MELHORIAS DEFINITIVAS para impressão sem sobreposição */
            input.etiqueta-barras, input.lacre {
                all: unset !important;
                font-family: 'Courier New', 'Monaco', 'Lucida Console', monospace !important;
                font-size: 12px !important; /* V7.9: Mesmo tamanho da fonte dos nomes dos postos */
                font-weight: bold !important;
                background: transparent !important;
                border: none !important;
                display: inline-block !important;
                box-shadow: none !important;
                appearance: none !important;
                -webkit-appearance: none !important;
                outline: none !important;
                white-space: nowrap !important;
                overflow: visible !important;
                word-break: keep-all !important;
                width: auto !important;
                letter-spacing: 0px !important; /* V7.9: Removido letter-spacing negativo */
                line-height: 1.2 !important;
                padding: 0 !important;
                margin: 0 !important;
                vertical-align: baseline !important;
                text-overflow: visible !important;
                -webkit-text-size-adjust: none !important;
                -moz-text-size-adjust: none !important;
                text-size-adjust: none !important;
            }
            
            /* V7.9: Lacres IIPR e Correios — mantém widths originais */
            input.lacre {
                font-size: 12px !important;
                font-weight: bold !important;
                min-width: 60px !important;
                max-width: 100% !important;
                text-align: center !important;
                padding-right: 2px !important;
                box-sizing: border-box !important;
            }
            
            /* v1.2.2: Etiqueta Correios — 9px, quebra em 2 linhas para caber os 35 dígitos */
            input.etiqueta-barras {
                font-size: 9px !important;
                font-family: 'Courier New', 'Monaco', 'Lucida Console', monospace !important;
                font-weight: bold !important;
                text-align: center !important;
                width: 100% !important;
                min-width: unset !important;
                white-space: normal !important;
                word-break: break-all !important;
                overflow-wrap: break-word !important;
                overflow: visible !important;
                padding: 0 !important;
                box-sizing: border-box !important;
                display: block !important;
            }

            /* Colunas: col4 com 37% para acomodar 35 chars a 12px sem overflow */
            th:nth-child(1), td:nth-child(1) { width: 30% !important; font-size: 12px !important; }
            th:nth-child(2), td:nth-child(2) { width: 12% !important; padding: 2px 5px !important; }
            th:nth-child(3), td:nth-child(3) { width: 12% !important; padding: 2px 5px !important; }
            th:nth-child(4), td:nth-child(4) {
                width: 37% !important;
                max-width: 37% !important;
                min-width: unset !important;
                padding: 2px 1px !important;
                overflow: visible !important;
                text-align: center !important;
            }
            th:nth-child(5), td:nth-child(5) { width: 9% !important; }
            td:nth-child(1) {
                white-space: normal !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
            }
            
            /* Esconder coluna de ações */
            .btn-excluir, .btn-excluir-regional, .btn-add-above, .btn-add-below, th:last-child, td:last-child { display: none !important; }
            
            /* Garantir que o logo fique bem formatado */
            .quadro-logo {line-height: 1.0; border: 1px solid black !important; padding: 12px !important; margin-bottom: 15px !important; box-sizing: border-box !important; }
            .info-cliente {line-height: 1.0; border: 1px solid black !important; padding: 10px !important; margin-bottom: 0px !important; box-sizing: border-box !important; position: relative !important; }
            
            /* v8.16.0: Número do ofício no canto direito (formato: Nº #ID) */
            .numero-oficio {
                position: absolute !important;
                top: 10px !important;
                right: 10px !important;
                padding: 8px 15px !important;
                border: 2px solid #000 !important;
                background-color: #fff !important;
                font-size: 16px !important;
                font-weight: bold !important;
                text-align: center !important;
                min-width: 80px !important;
            }
            
            /* Reset de tamanho de fonte para impressão */
            body {
                margin-bottom: 5px !important;
                font-size: 12px !important; /* Reseta para o tamanho normal na impressão */
            }
            
            /* Margem da página impressa */
            @page {
                margin: 5mm 8mm 5mm 8mm;
                size: A4;
            }
            
            /* Garantir que as assinaturas fiquem lado a lado */
            .assinaturas {
                display: flex !important;
                justify-content: space-between !important;
                width: 100% !important;
            }
            .assinatura-esquerda, .assinatura-direita {
                width: 45% !important;
                display: block !important;
            }
            
            /* Ajustar tabela para caber na página */
            table {
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
            }
        }
        
        /* V8.1: Novos estilos para interface melhorada */
        .painel-inserir-dados {
            background-color: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            display: none; /* V8.1: Escondido por padrão */
        }
        
        .painel-inserir-dados.ativo {
            display: block;
        }
        
        .btn-mostrar-painel {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            margin-top: 5px;
        }
        
        .btn-mostrar-painel:hover {
            background-color: #218838;
        }
        
        .painel-inserir-dados h3 {
            color: #007bff;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .form-group input, .form-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: inherit;
        }
        
        .btn-inserir {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: inherit;
            font-weight: bold;
        }
        
        .btn-inserir:hover {
            background-color: #218838;
        }
        
        .painel-analise {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            margin: 15px 0;
        }
        
        .painel-analise-header {
            background-color: #ffc107;
            padding: 10px 15px;
            margin: 0;
            cursor: pointer;
            user-select: none;
            border-radius: 6px 6px 0 0;
            font-weight: bold;
        }
        
        .painel-analise-header:hover {
            background-color: #e0a800;
        }
        
        .painel-analise-content {
            padding: 15px;
            display: block;
        }
        
        .painel-analise.collapsed .painel-analise-content {
            display: none;
        }
        
        .toggle-icon {
            float: right;
            transition: transform 0.3s ease;
        }
        
        .painel-analise.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        
        /* Autocomplete */
        .autocomplete-container {
            position: relative;
        }
        
        .autocomplete-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .autocomplete-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .autocomplete-item:hover,
        .autocomplete-item.selected {
            background-color: #007bff;
            color: white;
        }
        
        /* V8.1: Mensagens que desaparecem automaticamente */
        .mensagem-auto {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 15px 20px;
            border-radius: 5px;
            font-weight: bold;
            animation: slideIn 0.3s ease-out;
        }
        
        .mensagem-auto.sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensagem-auto.erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .mensagem-auto.fadeOut {
            animation: fadeOut 0.5s ease-out forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; transform: translateX(100%); }
        }
        
        /* V8.1: Painel de análise mais compacto quando recolhido */
        .painel-analise.collapsed {
            margin: 5px 0;
        }
        
        .painel-analise.collapsed .painel-analise-header {
            font-size: 14px;
            padding: 8px 15px;
        }
        
        @media print {
            .painel-inserir-dados,
            .painel-analise.collapsed,
            .mensagem-auto,
            .btn-mostrar-painel,
            #tabela-poupa-tempo,
            table[data-grupo="POUPA TEMPO"],
            .btn-limpar-coluna,
            .btn-limpar-coluna-header,
            button.btn-limpar,
            th button,
            #popup-etiqueta-focal,
            #indicador-dias {
                display: none !important;
            }
            
            .painel-analise:not(.collapsed) {
                page-break-before: always;
            }
        }
        
        /* v9.8.0: Badges coloridos para datas */
        .badge-data {
            display: inline-block;
            padding: 4px 10px;
            margin: 3px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            cursor: default;
        }
        
        .badge-data.conferida {
            background-color: #28a745;
            color: white;
        }
        
        .badge-data.pendente {
            background-color: #ffc107;
            color: #333;
        }
        
        /* v9.8.0: Status de Conferência recolhível */
        #indicador-dias {
            display: none !important;
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 12px 18px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.25);
            font-size: 12px;
            z-index: 10000;
            max-width: 350px;
            transition: all 0.3s ease;
        }
        
        #indicador-dias.collapsed {
            padding: 8px 12px;
            cursor: pointer;
        }
        
        #indicador-dias.collapsed .indicador-conteudo {
            display: none;
        }
        
        .indicador-toggle {
            display: inline-block;
            float: right;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
            user-select: none;
        }
        
        .indicador-conteudo {
            margin-top: 8px;
        }
        
        /* v9.21.6: Zoom compacto sem barra grande */
        .zoom-control {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 10000;
            background: transparent;
            padding: 0;
            border-radius: 4px;
            box-shadow: none;
            display: inline-flex;
            gap: 4px;
            width: auto;
        }
        
        .zoom-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: transform 0.2s;
        }
        
        .zoom-btn:hover {
            transform: scale(1.05);
        }
        
        .zoom-btn:active {
            transform: scale(0.95);
        }
        
        /* v9.8.0: Calendário e datas alternadas */
        .campo-calendario {
            display: inline-block;
            margin-right: 15px;
        }
        
        .campo-calendario input[type="date"] {
            padding: 6px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .datas-alternadas {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .datas-alternadas input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 12px;
        }
        
        /* v9.7.1: Pop-up centralizado para etiquetas */
        #popup-etiqueta-focal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 35px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            z-index: 10001;
            min-width: 400px;
            text-align: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            animation: popup-appear 0.3s ease-out;
        }
        
        @keyframes popup-appear {
            from { 
                opacity: 0; 
                transform: translate(-50%, -45%);
            }
            to { 
                opacity: 1; 
                transform: translate(-50%, -50%);
            }
        }
        
        #popup-etiqueta-focal.active {
            display: block;
        }
        
        #popup-etiqueta-focal .popup-header {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        #popup-etiqueta-focal .popup-posto {
            font-size: 24px;
            font-weight: bold;
            margin: 15px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        #popup-etiqueta-focal .popup-instrucao {
            font-size: 13px;
            opacity: 0.85;
            margin-top: 10px;
        }

        #popup-etiqueta-focal .popup-lacres {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 14px;
        }

        #popup-etiqueta-focal .popup-lacre-item {
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 10px 12px;
            text-align: left;
        }

        #popup-etiqueta-focal .popup-lacre-label {
            display: block;
            font-size: 11px;
            opacity: 0.82;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }

        #popup-etiqueta-focal .popup-lacre-valor {
            display: block;
            font-size: 22px;
            font-weight: bold;
            line-height: 1.1;
        }
        
        #popup-etiqueta-focal .popup-progresso {
            margin-top: 15px;
            font-size: 12px;
            opacity: 0.8;
            padding: 8px;
            background: rgba(255,255,255,0.15);
            border-radius: 6px;
        }
    </style>

<!-- PATCH v2.2.2 -->
<style>
.btn-oficio-pt{background:#6c63ff!important;border-color:#6c63ff!important;color:#fff!important;}
.btn-oficio-pt:hover{filter:brightness(.95);}
.btn-oficio-pt i{margin-right:6px;}
</style>
<style>
.btn-voltar-inicio{position:fixed;top:12px;left:12px;z-index:9999;display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:6px;background:#1f2b6d;color:#fff;text-decoration:none;font-size:12px;font-weight:600;}
.btn-voltar-inicio:hover{background:#162057;}
/* v1.2.2: botão mudo */
.btn-mudo{position:fixed;top:12px;left:100px;z-index:9999;display:inline-flex;align-items:center;gap:5px;padding:6px 11px;border-radius:6px;background:#1f2b6d;color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;transition:background .15s;}
.btn-mudo:hover{background:#162057;}
.btn-mudo.mudo{background:#b71c1c;}
.btn-mudo.mudo:hover{background:#8b0000;}
</style>
</head>
<body>

<div id="overlay-processando-global" class="overlay-processando-global" aria-hidden="true">
    <div class="overlay-processando-box" id="overlay-processando-texto">Processando...</div>
</div>

<a href="inicio.php" class="btn-voltar-inicio nao-imprimir">← Inicio</a>
<!-- v1.2.2: botão mudo -->
<button type="button" id="btnMudo" class="btn-mudo nao-imprimir" title="Silenciar/Reativar áudio" onclick="toggleMudo()">&#128266;</button>

<input type="hidden" id="id_despacho" value="<?php echo isset($_SESSION['id_despacho_correios']) && $_SESSION['id_despacho_correios'] > 0 ? (int)$_SESSION['id_despacho_correios'] : (int)$id_despacho_atual; ?>">

<!-- v9.7.1: Pop-up centralizado para focar no posto atual -->
<div id="popup-etiqueta-focal">
    <div class="popup-header">🎯 Leitura de Etiqueta</div>
    <div class="popup-posto" id="popup-posto-nome">-</div>
    <div class="popup-instrucao">📦 Escaneie o código de barras da etiqueta (35 dígitos)</div>
    <div class="popup-lacres">
        <div class="popup-lacre-item">
            <span class="popup-lacre-label">Lacre IIPR</span>
            <span class="popup-lacre-valor" id="popup-lacre-iipr">-</span>
        </div>
        <div class="popup-lacre-item">
            <span class="popup-lacre-label">Lacre Correios</span>
            <span class="popup-lacre-valor" id="popup-lacre-correios">-</span>
        </div>
    </div>
    <div class="popup-progresso" id="popup-progresso">-</div>
</div>

<div class="zoom-control">
    <button class="zoom-btn" id="zoom-in" title="Aumentar texto">A<sup>+</sup></button>
    <button class="zoom-btn" id="zoom-out" title="Diminuir texto">A<sup>−</sup></button>
</div>

<?php if (!empty($datas_filtro)): ?>
<div class="datas-filtro-banner nao-imprimir">
    <strong>Datas do filtro:</strong> <?php echo htmlspecialchars(implode(', ', $datas_filtro), ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<div class="version-info">v1.1.8</div>

<!-- v9.21.5: Card oculto na impressão (classe nao-imprimir) -->
<div id="indicador-dias" class="nao-imprimir collapsed">
    <div style="font-weight:bold;color:#333;font-size:13px;">
        📅 Status de Conferências
        <span class="indicador-toggle" onclick="toggleIndicadorDias()" title="Recolher/Expandir">▶</span>
    </div>
    
    <div class="indicador-conteudo">
        <div style="margin:10px 0;">
            <strong style="color:#28a745;font-size:12px;">✓ Últimas Conferências:</strong><br>
            <div style="margin-top:5px;">
                <?php 
                $ultimas_cinco = array_slice($dias_com_conferencia, 0, 5);
                if (!empty($ultimas_cinco)) {
                    foreach ($ultimas_cinco as $data) {
                        $label_dia = isset($metadados_dias[$data]) ? $metadados_dias[$data]['label'] : '';
                        $badge_label = !empty($label_dia) ? " <small style='font-size:9px;background:#6c757d;color:white;padding:1px 3px;border-radius:2px;'>$label_dia</small>" : '';
                        echo '<span class="badge-data conferida">' . htmlspecialchars($data) . $badge_label . '</span>';
                    }
                } else {
                    echo '<span style="color:#999;font-size:11px;">Nenhuma</span>';
                }
                ?>
            </div>
        </div>
        
        <div style="margin:10px 0;">
            <strong style="color:#ffc107;font-size:12px;">⚠ Conferências Pendentes:</strong><br>
            <div style="margin-top:5px;">
                <?php 
                $ultimas_pendentes = array_slice($dias_sem_conferencia, 0, 5);
                if (!empty($ultimas_pendentes)) {
                    foreach ($ultimas_pendentes as $data) {
                        $label_dia = isset($metadados_dias[$data]) ? $metadados_dias[$data]['label'] : '';
                        $badge_class = '';
                        $badge_label = '';
                        if ($label_dia == 'SEX') {
                            $badge_label = " <small style='font-size:9px;background:#ffc107;color:#333;padding:1px 3px;border-radius:2px;font-weight:bold;'>SEX</small>";
                        } elseif ($label_dia == 'SÁB') {
                            $badge_label = " <small style='font-size:9px;background:#17a2b8;color:white;padding:1px 3px;border-radius:2px;font-weight:bold;'>SÁB</small>";
                        } elseif ($label_dia == 'DOM') {
                            $badge_label = " <small style='font-size:9px;background:#dc3545;color:white;padding:1px 3px;border-radius:2px;font-weight:bold;'>DOM</small>";
                        }
                        echo '<span class="badge-data pendente">' . htmlspecialchars($data) . $badge_label . '</span>';
                    }
                } else {
                    echo '<span style="color:#999;font-size:11px;">Nenhuma</span>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($mensagem_sucesso)): ?>
<div class="mensagem-auto sucesso" id="mensagem-auto">
    <?php echo htmlspecialchars($mensagem_sucesso) ?>
</div>
<?php endif; ?>

<?php if (!empty($mensagem_erro)): ?>
<div class="mensagem-auto erro" id="mensagem-auto">
    <?php echo htmlspecialchars($mensagem_erro) ?>
</div>
<?php endif; ?>

<div class="painel-analise collapsed" id="painel-analise">
    <div class="painel-analise-header" onclick="toggleAnalisePanel()">
        <span class="icone">📊</span> Análise de Expedição (v9.14.0)
        <span class="toggle-icon">▼</span>
    </div>
    <div class="painel-analise-content">
    <?php if (empty($datas_filtro)): ?>
    <p style="color:#999;font-style:italic;">Selecione um período ou datas específicas para ver a análise de expedição.</p>
    <?php endif; ?>
    
    <?php if (!empty($datas_filtro)): ?>
    <div class="analise-grid">
        <div class="analise-item">
            <h4>Total de Carteiras Expedidas</h4>
            <div class="analise-valor"><?php echo number_format($analise_expedicao['total_carteiras']) ?></div>
            
        </div>
        
        <div class="analise-item">
            <h4>Total de Carteiras com Upload</h4>
            <div class="analise-valor"><?php echo number_format($analise_expedicao['total_postos']) ?></div>
            
        </div>
        
        <div class="analise-item">
            <h4>Diferença</h4>
            <div class="analise-valor <?php echo $analise_expedicao['diferenca'] >= 0 ? 'analise-diferenca positiva' : 'analise-diferenca' ?>">
                <?php echo $analise_expedicao['diferenca'] > 0 ? '+' : '' ?><?php echo number_format($analise_expedicao['diferenca']) ?>
            </div>
            <small><?php echo $analise_expedicao['diferenca'] >= 0 ? 'Expedição maior que uploads' : 'Uploads maior que expedição' ?></small>
            <?php if ($analise_expedicao['diferenca'] != 0): ?>
            <button class="btn-mostrar-painel" onclick="togglePainelInsercao()">
                Inserir Dados
            </button>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($analise_expedicao['postos_retirados'])): ?>
        <div class="analise-item">
            <h4>Postos com Retirada</h4>
            <div class="analise-valor"><?php echo count($analise_expedicao['postos_retirados']) ?></div>
            
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($analise_expedicao['detalhes_expedicao'])): ?>
    <div class="detalhes-expedicao">
        <h5>📋 Detalhamento da produção:</h5>
        <?php foreach ($analise_expedicao['detalhes_expedicao'] as $detalhe): ?>
            <div class="detalhe-item">
                <strong><?php echo $detalhe['data'] ?></strong> -
                <?php echo number_format($detalhe['expedidas']) ?> carteiras expedidas às <?php echo $detalhe['hora'] ?>h
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($analise_expedicao['autores_faltantes'])): ?>
    <div class="autores-faltantes">
        <h5>⚠️ Autores em ci-expedidas que não aparecem em ciPostosCsv:</h5>
        <?php foreach ($analise_expedicao['autores_faltantes'] as $autor_info): ?>
            <div class="autor-item">
                <strong><?php echo htmlspecialchars($autor_info['autor']) ?></strong> -
                Quantidade faltante: <?php echo number_format($autor_info['quantidade']) ?>
            </div>
        <?php endforeach; ?>
        <p><strong>Total faltante:</strong> <?php echo number_format($analise_expedicao['total_faltante']) ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($analise_expedicao['postos_retirados'])): ?>
    <div class="postos-retirados">
        <h5>📦 Postos com retirada nas datas selecionadas:</h5>
        <?php foreach ($analise_expedicao['postos_retirados'] as $posto): ?>
            <span class="posto-retirado">Posto <?php echo htmlspecialchars($posto) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>  <!-- Fecha o if (!empty($datas_filtro)) da análise -->
    </div>
</div>

<div class="painel-inserir-dados" id="painel-insercao">
    <h3>🔧 Inserir Dados na Tabela ciPostos (V8.1)</h3>
    <p>Use este painel para inserir dados lendo o código de barras de 19 dígitos.</p>
    
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="codigo_barras">Código de Barras (19 dígitos):</label>
                <input type="text"
                       id="codigo_barras"
                       name="codigo_barras"
                       placeholder="0071459800101600044"
                       maxlength="19"
                       pattern="\d{19}"
                       required
                       autocomplete="off">
                <small>Ex: 0071459800101600044 (lote+regional+posto+quantidade)</small>
            </div>
            
            <div class="form-group">
                  <label for="data_inserir">Data (dd-mm-aaaa):</label>
                <input type="text"
                       id="data_inserir"
                       name="data_inserir"
                      placeholder="<?php echo date('d-m-Y') ?>"
                      pattern="\d{2}[-/]\d{2}[-/]\d{4}"
                       required>
            </div>
            
            <div class="form-group">
                <label for="turno_inserir">Turno:</label>
                <select id="turno_inserir" name="turno_inserir" required>
                    <option value="">Selecione o turno</option>
                    <option value="1">Manhã</option>
                    <option value="2">Tarde</option>
                    <option value="3">Noite</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="autor_inserir">Autor:</label>
                <select id="autor_inserir" name="autor_inserir">
                    <option value="conferencia">conferencia (padrão)</option>
                    <?php
                    $usuarios_validos = obter_usuarios_validos($pdo_contrsos);
                    foreach ($usuarios_validos as $usuario):
                    ?>
                        <option value="<?php echo htmlspecialchars($usuario['usuario']) ?>">
                            <?php echo htmlspecialchars($usuario['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <button type="submit" name="inserir_dados_barcode" class="btn-inserir">
            Inserir Dados na Tabela ciPostos
        </button>
        
        <?php if (isset($_GET['lacre_capital'])): ?>
            <input type="hidden" name="lacre_capital" value="<?php echo $_GET['lacre_capital'] ?>">
        <?php endif; ?>
        <?php if (isset($_GET['lacre_central'])): ?>
            <input type="hidden" name="lacre_central" value="<?php echo $_GET['lacre_central'] ?>">
        <?php endif; ?>
        <?php if (isset($_GET['lacre_regionais'])): ?>
            <input type="hidden" name="lacre_regionais" value="<?php echo $_GET['lacre_regionais'] ?>">
        <?php endif; ?>
        <?php if (isset($_GET['responsavel'])): ?>
            <input type="hidden" name="responsavel" value="<?php echo $_GET['responsavel'] ?>">
        <?php endif; ?>
        <?php foreach ($_SESSION['datas_filtro'] as $data): ?>
            <input type="hidden" name="datas[]" value="<?php echo $data ?>">
        <?php endforeach; ?>
    </form>
</div>
<div class="quadro quadro-formulario">
    <form method="post" style="margin-bottom: 10px;" onsubmit="return confirmarLimparSessao(this);">
        <button type="submit" name="limpar_sessao" class="btn-limpar">Limpar Sessão</button>
    </form>
    <form method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>" id="formFiltroData" onsubmit="limparLacresPorRecalculo(); salvarEstadoEtiquetasCorreios();">
        <!-- v9.8.0: Calendário para seleção de datas -->
        <div style="margin:15px 0;padding:12px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;">
            <strong style="color:#495057;">📅 Selecionar Datas:</strong>
            
            <div style="margin-top:10px;">
                <div class="campo-calendario">
                    <label style="font-weight:bold;font-size:12px;color:#495057;">Data Inicial:</label><br>
                    <input type="date" name="data_inicial_cal" id="data_inicial_cal" 
                           style="width:150px;padding:6px 10px;border:1px solid #ced4da;border-radius:4px;">
                </div>
                
                <div class="campo-calendario">
                    <label style="font-weight:bold;font-size:12px;color:#495057;">Data Final:</label><br>
                    <input type="date" name="data_final_cal" id="data_final_cal" 
                           style="width:150px;padding:6px 10px;border:1px solid #ced4da;border-radius:4px;">
                </div>
                
                <button type="submit" onclick="return desativarRecalculoLacres();" style="padding:8px 20px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:bold;vertical-align:bottom;">
                    📅 Aplicar Período
                </button>
            </div>
            
            <div class="datas-alternadas">
                <label style="font-weight:bold;font-size:12px;color:#495057;display:block;margin-bottom:5px;">
                    ➕ Datas Alternadas (opcionais):
                </label>
                <input type="text" name="datas_alternadas" id="datas_alternadas" 
                       placeholder="Ex: 20-01-2026, 22-01-2026, 25-01-2026"
                       title="Digite datas no formato dd-mm-aaaa separadas por vírgula">
                <div style="margin-top:5px;font-size:11px;color:#6c757d;">
                    💡 Digite datas específicas separadas por vírgula (formato: dd-mm-aaaa)
                </div>
            </div>
        </div>

        <div class="topo-formulario">
            <div class="lacre-topo-legado" aria-hidden="true">
                <label>Lacre Capital: <input type="number" name="lacre_capital" id="lacre_capital_input" value="<?php echo $lacre_capital ?>"></label>
                <button type="submit" onclick="return ativarRecalculoLacres('CAPITAL');" 
                        style="padding:8px 14px;background:#28a745;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">
                    🎯 Aplicar Capital
                </button>
                <label>Lacre Central: <input type="number" name="lacre_central" id="lacre_central_input" value="<?php echo $lacre_central ?>"></label>
                <button type="submit" onclick="return ativarRecalculoLacres('CENTRAL IIPR');" 
                        style="padding:8px 14px;background:#17a2b8;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">
                    🎯 Aplicar Central
                </button>
                <label>Lacre Regionais: <input type="number" name="lacre_regionais" id="lacre_regionais_input" value="<?php echo $lacre_regionais ?>"></label>
                <button type="submit" onclick="return ativarRecalculoLacres('REGIONAIS');" 
                        style="padding:8px 14px;background:#6f42c1;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">
                    🎯 Aplicar Regionais
                </button>
            </div>
            <input type="hidden" name="recalculo_por_lacre" id="recalculo_por_lacre" value="<?php echo $recalculo_por_lacre ? '1' : '0' ?>">
            <input type="hidden" name="recalculo_grupo" id="recalculo_grupo" value="<?php echo htmlspecialchars($recalculo_grupo, ENT_QUOTES, 'UTF-8') ?>">
            <label>Responsável: <input type="text" name="responsavel" value="<?php echo htmlspecialchars($responsavel) ?>" required></label>
            <!-- v8.14.9.3: Exibir último lacre usado -->
            <div style="display:inline-block; margin-left:15px; padding:8px 12px; background:#e3f2fd; border:1px solid #2196f3; border-radius:4px; font-size:12px;">
                <strong style="color:#1976d2;">Últimos Lacres:</strong><br>
                <span style="color:#0d47a1;">IIPR: <strong><?php echo number_format($ultimo_lacre_iipr, 0, ',', '.'); ?></strong></span> | 
                <span style="color:#0d47a1;">Correios: <strong><?php echo number_format($ultimo_lacre_correios, 0, ',', '.'); ?></strong></span>
            </div>
            <div style="display:block; width:100%; margin-top:8px; font-size:12px; color:#495057;">
                Preenchimento direto ativo: digite o lacre IIPR na primeira linha de Capital e Regionais, e na primeira linha de cada bloco da Central.
            </div>
        </div>
    </form>
    
   
    
    <?php if (!empty($dados['REGIONAIS'])): ?>
    
    <?php endif; ?>
</div>

<?php if (!empty($mensagem_sucesso)): ?>
<div class="mensagem-sucesso">
    <?php echo htmlspecialchars($mensagem_sucesso) ?>
</div>
<?php endif; ?>

<?php if (!empty($mensagem_erro)): ?>
<div class="mensagem-erro">
    <?php echo htmlspecialchars($mensagem_erro) ?>
</div>
<?php endif; ?>

<?php if (!empty($postos_nao_cadastrados)): ?>
<div class="alerta">
    <div class="alerta-titulo">Atenção: Os seguintes postos não estão cadastrados na tabela ciRegionais:</div>
    <ul class="alerta-lista">
        <?php foreach ($postos_nao_cadastrados as $info): ?>
            <li>Posto <?php echo $info['posto'] ?></li>
        <?php endforeach; ?>
    </ul>
    <p>Utilize o formulário abaixo para cadastrar estes postos.</p>
</div>

<div class="cadastro-posto">
    <h3>Cadastrar Posto na Tabela ciRegionais</h3>
    <form method="post" class="form-cadastro">
        <label>
            Posto:
            <select name="posto" required>
                <option value="">Selecione o posto</option>
                <?php foreach ($postos_nao_cadastrados as $info): ?>
                    <option value="<?php echo $info['posto'] ?>" data-regional="<?php echo $info['regional'] ?>"><?php echo $info['posto'] ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Regional:
            <select name="regional" required>
                <option value="">Selecione a regional</option>
                <?php foreach ($todas_regionais as $num => $nome): ?>
                    <option value="<?php echo $num ?>"><?php echo $num ?> - <?php echo $nome ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Nome:
            <input type="text" name="nome" required placeholder="Ex: Posto 123 - Cidade">
        </label>
        <input type="hidden" name="cadastrar_posto" value="1">
        <button type="submit" class="btn-cadastrar">Cadastrar Posto</button>
    </form>
</div>
<?php endif; ?>

<!-- Formulário principal para salvar ofício Correios -->
<form method="post" id="formOficioCorreios" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="acao" id="acaoCorreios" value="salvar_oficio_correios">
    <input type="hidden" name="correios_datas" value="<?php echo htmlspecialchars(implode(',', $datas_filtro), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="imprimir_apos_salvar" id="imprimirAposSalvar" value="0">
    <input type="hidden" name="modo_oficio" id="modo_oficio" value="" />
    <input type="hidden" name="id_oficio_sobrescrever" id="id_oficio_sobrescrever" value="" />
    <input type="hidden" name="responsavel" id="responsavelCorreios" value="" />

<div style="display: flex; gap: 10px; margin-bottom: 15px;">
    <button type="button" class="btn-imprimir" onclick="confirmarGravarEImprimir();" style="background:#28a745;"><i>💾🖨️</i> Gravar e Imprimir Correios</button>
    <button type="button" class="btn-imprimir" onclick="prepararEImprimir();" style="background:#6c757d;"><i>🖨️</i> Apenas Imprimir</button>
    <!-- v9.8.0: Botão oculto - funcionalidade integrada ao "Gravar e Imprimir" -->
    <!-- <button type="button" class="btn-salvar-etiquetas" onclick="abrirModalConfirmacao()" style="display:none;"><i>💾</i> Salvar Etiquetas Correios</button> -->
</div>

<?php if (!empty($poupaTempoPayload)): ?>
        <?php
            // Garante JSON bem formado (com acentos)
            $poupaTempoPayloadJson = json_encode_legado_seguro($poupaTempoPayload, JSON_UNESCAPED_UNICODE);
        ?>
        <!-- Botão PT com etiqueta Correios (form gerado fora do form principal para evitar forms aninhados) -->
        <button type="button" class="btn btn-warning" id="btnGerarOficioPT" onclick="abrirOficioPoupaTempo();">Ofício PT com Etiqueta Correios</button>
        <div style="display:flex; gap:14px; align-items:center; margin-top:8px; flex-wrap:wrap;">
            <label style="font-size:12px; display:inline-flex; align-items:center; gap:6px;">
                <input type="checkbox" id="ptFiltroNaoConferidos">
                Somente lotes nao conferidos
            </label>
            <label style="font-size:12px; display:inline-flex; align-items:center; gap:6px;">
                <input type="checkbox" id="ptFiltroSemOficio">
                Somente lotes sem oficio
            </label>
            <label style="font-size:12px; display:inline-flex; align-items:center; gap:6px;">
                Postos PT:
                <input type="text" id="ptFiltroPostosTexto" placeholder="Ex: 006,028,526" style="padding:4px 6px; min-width:180px;">
            </label>
        </div>
        <script type="text/javascript">
        function abrirOficioPoupaTempo() {
            var payload = <?php echo $poupaTempoPayloadJson; ?>;
                var form = document.createElement('form');
                form.method = 'post';
                form.action = 'modelo_oficio_poupa_tempo.php';
                form.target = '_blank';
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'poupatempo_payload';
                input.value = JSON.stringify(payload);
                form.appendChild(input);
                var inputDatas = document.createElement('input');
                inputDatas.type = 'hidden';
                inputDatas.name = 'pt_datas';
                inputDatas.value = <?php echo json_encode_legado_seguro(implode(',', $datas_filtro), JSON_UNESCAPED_UNICODE); ?>;
                form.appendChild(inputDatas);
                var inputPostos = document.createElement('input');
                inputPostos.type = 'hidden';
                inputPostos.name = 'pt_postos_sel';
                inputPostos.value = coletarPostosSelecionadosPT();
                form.appendChild(inputPostos);
                var inputModoVisual = document.createElement('input');
                inputModoVisual.type = 'hidden';
                inputModoVisual.name = 'pt_modo_visual';
                inputModoVisual.value = 'correios';
                form.appendChild(inputModoVisual);
                var inputNaoConf = document.createElement('input');
                inputNaoConf.type = 'hidden';
                inputNaoConf.name = 'pt_filtrar_nao_conferidos';
                inputNaoConf.value = (document.getElementById('ptFiltroNaoConferidos') && document.getElementById('ptFiltroNaoConferidos').checked) ? '1' : '0';
                form.appendChild(inputNaoConf);
                var inputSemOficio = document.createElement('input');
                inputSemOficio.type = 'hidden';
                inputSemOficio.name = 'pt_filtrar_sem_oficio';
                inputSemOficio.value = (document.getElementById('ptFiltroSemOficio') && document.getElementById('ptFiltroSemOficio').checked) ? '1' : '0';
                form.appendChild(inputSemOficio);
                var inputResponsavel = document.createElement('input');
                inputResponsavel.type = 'hidden';
                inputResponsavel.name = 'responsavel';
                inputResponsavel.value = <?php echo json_encode_legado_seguro($responsavel, JSON_UNESCAPED_UNICODE); ?>;
                form.appendChild(inputResponsavel);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
        }
        </script>
<?php endif; ?>



<div class="quadro quadro-logo somente-impressao" style="margin-bottom: 15px;">
    <img src="assets/img/logo_celepar.png" style="height: 60px; float: left; margin-right: 15px;">
    <div style="font-size: 14px;">
        <strong>CELEPAR – TECNOLOGIA DA INFORMAÇÃO E COMUNICAÇÃO DO PARANÁ</strong><br>
        COMPROVANTE DE ENTREGA DE SERVIÇOS
    </div>
</div>
<div style="clear: both;"></div>

<div class="info-cliente somente-impressao">
    <p><strong>CLIENTE:</strong> CORREIO - <strong>END.</strong>R: JOÃO NEGRÃO, 1251 - CENTRO - CURITIBA PARANÁ</p>
    </p>
    <p><strong>SISTEMA: </strong>SIV --<strong>SETOR: </strong>EXPEDIÇÃO</p>
    <?php if (isset($_SESSION['id_despacho_correios']) && $_SESSION['id_despacho_correios'] > 0): ?>
    <div class="numero-oficio">
        Nº #<?php echo (int)$_SESSION['id_despacho_correios']; ?>
    </div>
    <?php endif; ?>
</div>


<div class="controles-selecao-grade nao-imprimir">
    <button type="button" class="btn-selecao-multipla-lacres" id="btnSelecaoMultiplaLacres" onclick="toggleSelecaoMultiplaLacres()">☑ Seleção Múltipla</button>
    <button type="button" class="btn-excluir-selecionados-lacres" id="btnExcluirSelecionadosLacres" onclick="excluirSelecionadosLacres()">✕ Excluir Selecionados</button>
</div>
<div class="legenda-status nao-imprimir">
    <strong style="color:#37474f;">Legenda:</strong>
    <span class="item"><span class="amostra amarelo"></span> Amarelo = sem display local (aguardando retorno)</span>
    <span class="item"><span class="amostra verde"></span> Verde = displays disponíveis (local / em trânsito)</span>
    <span class="item"><span class="amostra vermelho"></span> Vermelho = posto bloqueado</span>
    <span class="item"><span class="amostra laranja"></span> Laranja = restrição ativa (segurar/devolução/etc.)</span>
</div>
<div id="gradeLacresContainer">
<?php foreach ($dados as $grupo => $itens): if (empty($itens)) continue; ?>
    <table id="tabela-<?php echo strtolower(str_replace(' ', '-', $grupo)) ?>" data-grupo="<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>">
        <thead>
            <tr>
                <th>
                    <?php echo $grupo ?>
                    <?php if ($grupo === 'POUPA TEMPO'): ?>
                    <button type="button" class="btn-toggle-pt-postos nao-imprimir" id="btnTogglePtPostos" onclick="togglePostosPT(this)" title="Ocultar/Mostrar postos Poupa Tempo" style="margin-left:10px; padding:3px 10px; font-size:11px; background:#f0f4f8; border:1px solid #a0aab4; color:#3a4a5a; border-radius:4px; cursor:pointer; font-weight:600;">Ocultar PT</button>
                    <?php endif; ?>
                </th>
                <th>
                    <?php if ($grupo === 'POUPA TEMPO'): ?>
                    Lacre Poupa Tempo
                    <?php else: ?>
                    Lacre IIPR
                    <button type="button" class="btn-limpar-coluna" onclick="limparColuna('<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>', 'lacre_iipr')" title="Apagar todos os lacres IIPR deste grupo">X</button>
                    <?php endif; ?>
                </th>
                <th>
                    <?php if ($grupo === 'POUPA TEMPO'): ?>
                    Lacre Correios Poupa Tempo
                    <?php else: ?>
                    Lacre Correios
                    <button type="button" class="btn-limpar-coluna" onclick="limparColuna('<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>', 'lacre_correios')" title="Apagar todos os lacres Correios deste grupo">X</button>
                    <?php endif; ?>
                </th>
                <th>
                    Etiqueta Correios
                    <?php if ($grupo !== 'POUPA TEMPO' && $grupo !== 'CENTRAL IIPR'): ?>
                    <button type="button" class="btn-limpar-coluna" onclick="limparColuna('<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>', 'etiqueta_correios')" title="Apagar todas as etiquetas deste grupo">X</button>
                    <?php endif; ?>
                    <?php if ($grupo === 'CENTRAL IIPR'): ?>
                    <button type="button" class="btn-limpar-coluna" onclick="limparEtiquetasCentral()" title="Apagar todas as etiquetas da Central IIPR">X</button>
                    <?php endif; ?>
                </th>
                <th><?php echo ($grupo === 'POUPA TEMPO') ? '' : 'Acoes'; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $itensRenderizados = array();
            if ($grupo === 'POUPA TEMPO') {
                $ptCapital = array();
                $ptInterior = array();
                foreach ($itens as $itemPt) {
                    $codigoPt = (int)preg_replace('/\D+/', '', (string)$itemPt['posto_codigo']);
                    if ($codigoPt >= 5 && $codigoPt <= 80) {
                        $ptCapital[] = $itemPt;
                    } else {
                        $ptInterior[] = $itemPt;
                    }
                }
                if (!empty($ptCapital)) {
                    $itensRenderizados[] = array('tipo_linha' => 'cabecalho', 'titulo' => 'POSTOS POUPA TEMPO CAPITAL');
                    foreach ($ptCapital as $itemPtCapital) {
                        $itensRenderizados[] = array('tipo_linha' => 'dado', 'item' => $itemPtCapital);
                    }
                }
                if (!empty($ptInterior)) {
                    $itensRenderizados[] = array('tipo_linha' => 'cabecalho', 'titulo' => 'POSTOS POUPA TEMPO INTERIOR');
                    foreach ($ptInterior as $itemPtInterior) {
                        $itensRenderizados[] = array('tipo_linha' => 'dado', 'item' => $itemPtInterior);
                    }
                }
            } else {
                foreach ($itens as $itemPadrao) {
                    $itensRenderizados[] = array('tipo_linha' => 'dado', 'item' => $itemPadrao);
                }
            }
            ?>
            <?php foreach ($itensRenderizados as $key => $linhaRender): ?>
            <?php if ($linhaRender['tipo_linha'] === 'cabecalho'): ?>
            <tr class="subtitulo-pt-grid">
                <td colspan="5" style="text-align:left; font-weight:bold; background:#f2f2f2; padding:8px 10px;">&nbsp;<?php echo htmlspecialchars($linhaRender['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php continue; ?>
            <?php endif; ?>
            <?php $dado = $linhaRender['item']; ?>
            <?php
            $st_linha = isset($status_postos[$dado['posto_codigo']]) ? $status_postos[$dado['posto_codigo']] : null;
            $usa_display = lacresPostoUsaDisplay($grupo, $dado['posto_codigo']);
            $classes_linha = array();
            if ($grupo === 'CENTRAL IIPR') { $classes_linha[] = 'linha-central'; }
            if (lacresLinhaAlertaDisplay($st_linha, $usa_display)) { $classes_linha[] = 'linha-display-fora'; }
            $attr_classe = !empty($classes_linha) ? ' class="' . implode(' ', $classes_linha) . '"' : '';
            ?>
            <tr data-posto-codigo="<?php echo $dado['posto_codigo'] ?>" data-grupo="<?php echo $grupo ?>" data-regional="<?php echo isset($dado['regional']) ? htmlspecialchars($dado['regional'], ENT_QUOTES, 'UTF-8') : '0' ?>" data-regional-codigo="<?php echo isset($dado['regional']) ? htmlspecialchars($dado['regional'], ENT_QUOTES, 'UTF-8') : '0' ?>" data-linha-inserida="<?php echo !empty($dado['manual_inserido']) ? '1' : '0' ?>"<?php echo $attr_classe; ?><?php if ($grupo === 'CENTRAL IIPR'): ?> data-central-index="<?php echo $key ?>"<?php endif; ?>>
                <td class="acoes-cell">
                    <?php if ($grupo === 'POUPA TEMPO'): ?>
                    <label style="margin-right:6px; font-size:11px; display:inline-flex; align-items:center; gap:4px;">
                        <input type="checkbox" class="pt-selecionar" data-posto="<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>" checked>
                        Selecionar
                    </label>
                    <?php endif; ?>
                    <!-- v8.6: Input oculto com código do posto para manter alinhamento de arrays -->
                    <?php if ($grupo !== 'POUPA TEMPO'): ?>
                    <input type="hidden" name="posto_codigo_correios[]" value="<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                    <!-- v8.14.9.3: Botão SPLIT vem ANTES do nome (não depois) -->
                    <?php if ($grupo === 'CENTRAL IIPR'): ?>
                    <button type="button" class="btn-split-aqui no-print" onclick="definirSplitAqui(this)" style="font-size:10px; padding:2px 5px; margin-right:6px; vertical-align:middle;">SPLIT</button>
                    <?php endif; ?>
                    <?php echo $dado['posto_nome'] ?>
                    <?php if ($st_linha): $badges = lacresBadgesStatus($st_linha, $usa_display); if ($badges !== ''): ?>
                    <span class="status-badges nao-imprimir"><?php echo $badges; ?></span>
                    <?php endif; endif; ?>
                    <?php if ($grupo !== 'POUPA TEMPO'): ?>
                    <input type="hidden" name="nome_posto[<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>]" value="<?php echo htmlspecialchars($dado['posto_nome'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="grupo_posto[<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>]" value="<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                </td>
                <td><?php if ($grupo === 'POUPA TEMPO'): ?><div class="lacre-wrapper"><input class="lacre lacre-pt" type="text" name="lacre_pt[<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>]" value="" data-indice="<?php echo $dado['posto_codigo'] ?>" data-tipo="lacre-pt" inputmode="numeric" placeholder="ex: 104569" style="min-width:90px;"></div><?php else: ?><div class="lacre-wrapper"><input class="lacre" type="text" name="lacre_iipr[<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>]" value="<?php echo htmlspecialchars(isset($dado['lacre_iipr']) ? $dado['lacre_iipr'] : '', ENT_QUOTES, 'UTF-8') ?>" data-indice="<?php echo $dado['posto_codigo'] ?>" data-tipo="iipr" inputmode="numeric" placeholder="14266 ou 14266-14267"><button type="button" class="btn-lacre-avulso" data-tipo-avulso="iipr" title="Ativar lacre avulso">Av</button></div><?php endif; ?></td>
                <td><?php if ($grupo === 'POUPA TEMPO'): ?><div class="lacre-wrapper"><input class="lacre lacre-correios-pt" type="text" name="lacre_correios_pt[<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>]" value="" data-indice="<?php echo $dado['posto_codigo'] ?>" data-tipo="lacre-correios-pt" inputmode="numeric" placeholder="ex: 10510" style="min-width:90px;"></div><?php else: ?><div class="lacre-wrapper"><input class="lacre <?php if ($grupo === 'CENTRAL IIPR'): ?>central-correios<?php endif; ?>" type="text" name="lacre_correios[<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>]" value="<?php echo htmlspecialchars(isset($dado['lacre_correios']) ? $dado['lacre_correios'] : '', ENT_QUOTES, 'UTF-8') ?>" data-indice="<?php echo $dado['posto_codigo'] ?>" data-tipo="correios" inputmode="numeric" placeholder="14268"><button type="button" class="btn-lacre-avulso" data-tipo-avulso="correios" title="Ativar lacre avulso">Av</button></div><?php endif; ?></td>
                <td class="acoes-cell">
    <?php if ($grupo === 'POUPA TEMPO'): ?>
        <input class="etiqueta-barras etiqueta-pt" type="text" name="etiqueta_correios[p_<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>]" maxlength="35" data-indice="<?php echo $dado['posto_codigo'] ?>" data-grupo="POUPA TEMPO" value="" placeholder="Leia a etiqueta Correios" style="width:100%;min-width:160px;font-family:monospace;font-size:11px;">
        <div class="alerta-duplicata" id="alerta-pt-<?php echo $dado['posto_codigo'] ?>"></div>
    <?php elseif ($grupo === 'CENTRAL IIPR'): ?>
        <input class="etiqueta-barras central-etiqueta" type="text" name="etiqueta_correios[p_<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>]" maxlength="35" data-indice="<?php echo $dado['posto_codigo'] ?>" value="<?php echo htmlspecialchars(isset($_SESSION['etiquetas'][$dado['posto_codigo']]) ? $_SESSION['etiquetas'][$dado['posto_codigo']] : '', ENT_QUOTES, 'UTF-8') ?>">
    <?php else: ?>
        <input class="etiqueta-barras etiqueta-validavel" type="text" name="etiqueta_correios[p_<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>]" maxlength="35" data-indice="<?php echo $dado['posto_codigo'] ?>" data-grupo="<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>" data-regional="<?php echo isset($dado['regional']) ? htmlspecialchars($dado['regional'], ENT_QUOTES, 'UTF-8') : '0' ?>" value="<?php echo htmlspecialchars(isset($_SESSION['etiquetas'][$dado['posto_codigo']]) ? $_SESSION['etiquetas'][$dado['posto_codigo']] : '', ENT_QUOTES, 'UTF-8') ?>">
        <div class="alerta-duplicata" id="alerta-<?php echo $dado['posto_codigo'] ?>"></div>
    <?php endif; ?>
</td>
                <td class="acoes-cell">
                    <input type="checkbox" class="chk-selecao-lacres"
                           data-codigo="<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>"
                           data-grupo="<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>"
                           data-nome="<?php echo htmlspecialchars($dado['posto_nome'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($grupo === 'POUPA TEMPO'): ?>
                    <button type="button" class="btn-add-below"
                            data-posto="<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>"
                            data-grupo="<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>"
                            data-posicao="abaixo"
                            onclick="abrirModalInserir(this);">
                        +Abaixo
                    </button>
                    <?php elseif ($grupo === 'REGIONAIS'): ?>
                    <button type="button" class="btn-excluir-regional"
                            onclick="excluirPostoRegional('<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>', '<?php echo htmlspecialchars($dado['posto_nome'], ENT_QUOTES, 'UTF-8') ?>');">
                        Excluir
                    </button>
                    <button type="button" class="btn-add-below"
                            data-posto="<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>"
                            data-grupo="<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>"
                            data-posicao="abaixo"
                            onclick="abrirModalInserir(this);">
                        +Abaixo
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn-excluir"
                            onclick="excluirPosto('<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>', '<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>', '<?php echo htmlspecialchars($dado['posto_nome'], ENT_QUOTES, 'UTF-8') ?>');">
                        Excluir
                    </button>
                    <button type="button" class="btn-add-below"
                            data-posto="<?php echo htmlspecialchars($dado['posto_codigo'], ENT_QUOTES, 'UTF-8') ?>"
                            data-grupo="<?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>"
                            data-posicao="abaixo"
                            onclick="abrirModalInserir(this);">
                        +Abaixo
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>
</div><!-- #gradeLacresContainer -->

</form>
<!-- Fim do formulário principal para salvar ofício Correios -->

<div class="assinaturas somente-impressao" style="display:flex;align-items:flex-end;gap:0;">
    <div class="assinatura-esquerda" style="flex:1;text-align:center;">
        <div style="font-size:12px;font-weight:bold;min-height:16px;">
            <?php echo htmlspecialchars(strtoupper(trim((string)$responsavel)), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <hr>
        <p style="margin:0;">RESPONSÁVEL CELEPAR</p>
    </div>
    <div style="flex:0 0 auto;text-align:center;padding:0 20px;font-size:12px;">
        <p style="margin:0;">Data: <?php echo $data_geracao; ?></p>
    </div>
    <div class="assinatura-direita" style="flex:1;text-align:center;">
        <div style="min-height:16px;">&nbsp;</div>
        <hr>
        <p style="margin:0;">RESPONSÁVEL CORREIOS</p>
    </div>
</div>

<div class="modal-overlay" id="modal-inserir">
    <div class="modal-content">
        <h3 class="modal-title">Inserir Novo Posto</h3>
        <form method="post" class="modal-form">
            <input type="hidden" name="inserir_linha" value="1">
            <input type="hidden" name="referencia_posto" id="referencia_posto" value="">
            <input type="hidden" name="posicao" id="posicao_insercao" value="">
            
            <label for="novo_grupo">Grupo:</label>
            <select name="novo_grupo" id="novo_grupo" required>
                <option value="CAPITAL">CAPITAL</option>
                <option value="CENTRAL IIPR">CENTRAL IIPR</option>
                <option value="REGIONAIS">REGIONAIS</option>
                <option value="POUPA TEMPO">POUPA TEMPO</option>
            </select>
            
            <label for="novo_nome">Nome do Posto:</label>
            <input type="text" name="novo_nome" id="novo_nome" required>
            
            <div class="texto-ajuda">Lacres e etiqueta devem ser preenchidos na linha apos inserir o posto.</div>
            
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="modal-btn modal-btn-save">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-confirmacao-salvamento">
    <div class="modal-confirmacao">
        <h3><span class="icone">💾</span> Confirmar Salvamento</h3>
        <p>Deseja realmente salvar todas as etiquetas dos Correios no banco de dados?</p>
        <p>Esta ação irá gravar <span id="contador-etiquetas">0</span> etiquetas válidas na tabela ciMalotes.</p>
        
        <form method="post" id="form-salvamento">
            <input type="hidden" name="salvar_etiquetas_confirmado" value="1">
            <input type="hidden" name="login" value="<?php echo htmlspecialchars($responsavel) ?>">
            
            <div class="form-group">
                <label for="login_personalizado">Nome do Responsável pelo Salvamento:</label>
                <input type="text" name="login_personalizado" id="login_personalizado"
                       value="<?php echo htmlspecialchars($responsavel) ?>"
                       placeholder="Digite o nome do responsável">
            </div>
            
            <div class="btn-group">
                <button type="button" class="btn btn-cancelar" onclick="fecharModalConfirmacao()">Cancelar</button>
                <button type="submit" class="btn btn-confirmar">Confirmar Salvamento</button>
            </div>
        </form>
    </div>
</div>

<?php if ($mostrar_debug): ?>
<div class="debug-info">
    <h3>Informações de Depuração (V7.9)</h3>
    <pre><?php
        echo "Linhas Removidas:\n";
        print_r($_SESSION['linhas_removidas']);
        
        echo "\n\nExclusões de Regionais Manual:\n";
        print_r($_SESSION['excluir_regionais_manual']);
        
        echo "\n\nAnálise de Expedição (V7.9):\n";
        print_r($analise_expedicao);
        
        echo "\n\nLog de Depuração:\n";
        print_r($_SESSION['debug_log']);
        
        echo "\n\nDados REGIONAIS:\n";
        print_r($dados['REGIONAIS']);
    ?></pre>
</div>
<?php endif; ?>

<!-- Formulario oculto para exclusao de postos -->
<form method="post" id="formExcluirPosto" style="display:none;">
    <input type="hidden" name="excluir_posto" id="excluir_posto_flag" value="">
    <input type="hidden" name="excluir_posto_regional" id="excluir_posto_regional_flag" value="">
    <input type="hidden" name="codigo_posto" id="excluir_codigo_posto" value="">
    <input type="hidden" name="grupo_posto" id="excluir_grupo_posto" value="">
    <input type="hidden" name="nome_posto" id="excluir_nome_posto" value="">
    <input type="hidden" name="info_regional" id="excluir_info_regional" value="">
    <input type="hidden" name="excluir_multiplos" id="excluir_multiplos" value="">
    <input type="hidden" name="snapshot_lacres" value="">
</form>
<!-- Formulario oculto para desfazer exclusao -->
<form method="post" id="formUndoExclusao" style="display:none;">
    <input type="hidden" name="restaurar_posto_undo" value="1">
</form>

<script type="text/javascript">
// v1.2.3: Pilha de undo para edições de lacre/etiqueta em memória
// btn-undo-flutuante removido — usa btnVoltarAcaoPT (util_botoes_fixos) como único botão de undo
var _undoStack = [];
var _undoTempoMax = 60000; // 60 segundos de janela de undo

function _atualizarBtnUndo() {
    var btn = document.getElementById('btnVoltarAcaoPT');
    var temServidor = <?php echo (isset($_SESSION['undo_exclusao']) ? 'true' : 'false'); ?>;
    var temJs = _undoStack.length > 0;
    if (btn) {
        if (temServidor || temJs) {
            btn.removeAttribute('disabled');
            btn.title = temServidor
                ? 'Desfazer exclusão de linha'
                : ('Desfazer edição de ' + (_undoStack.length > 0 ? _undoStack[_undoStack.length-1].campo : ''));
        } else {
            btn.setAttribute('disabled', 'disabled');
            btn.title = 'Voltar ação';
        }
    }
}

function voltarAcaoPT() {
    executarUndo();
}

function _registrarUndo(el, valorAnterior) {
    _undoStack.push({
        el: el,
        campo: el.name || el.id || 'campo',
        valor: valorAnterior,
        ts: Date.now()
    });
    // Limpar entradas muito antigas
    var agora = Date.now();
    _undoStack = _undoStack.filter(function(u){ return agora - u.ts < _undoTempoMax; });
    _atualizarBtnUndo();
}

function executarUndo() {
    var temServidor = <?php echo (isset($_SESSION['undo_exclusao']) ? 'true' : 'false'); ?>;
    // Prioridade: JS undo (edições em memória) > servidor (exclusão de linha)
    if (_undoStack.length > 0) {
        var u = _undoStack.pop();
        if (u && u.el) {
            u.el.value = u.valor;
            u.el.focus();
            u.el.style.background = '#fffbe6';
            setTimeout(function(){ u.el.style.background = ''; }, 800);
        }
        _atualizarBtnUndo();
    } else if (temServidor) {
        document.getElementById('formUndoExclusao').submit();
    }
}

// Capturar mudanças em inputs de lacre e etiqueta para undo
document.addEventListener('DOMContentLoaded', function() {
    function ligarUndo(selector) {
        var inputs = document.querySelectorAll(selector);
        for (var i = 0; i < inputs.length; i++) {
            (function(inp) {
                var valorFoco = '';
                inp.addEventListener('focus', function(){ valorFoco = inp.value; });
                inp.addEventListener('change', function(){
                    if (inp.value !== valorFoco) {
                        _registrarUndo(inp, valorFoco);
                        valorFoco = inp.value;
                    }
                });
            })(inputs[i]);
        }
    }
    ligarUndo('input.lacre');
    ligarUndo('input.etiqueta-barras');
    _atualizarBtnUndo();

    // Observar novas linhas adicionadas à grade
    var tabela = document.querySelector('table.quadro-tabela') || document.querySelector('form table');
    if (tabela && window.MutationObserver) {
        var obs = new MutationObserver(function() {
            ligarUndo('input.lacre');
            ligarUndo('input.etiqueta-barras');
        });
        obs.observe(tabela, { childList: true, subtree: true });
    }
});
</script>

<script type="text/javascript">
// Funcoes para salvar oficio Correios (compativel com navegadores antigos)
// v8.9: Prepara arrays alinhados de lacres/etiquetas + regional antes do submit
function prepararLacresCorreiosParaSubmit(form) {
    if (!form) return;
    // v8.13: Remover inputs ocultos antigos + snapshot_oficio
    var nomes = ['posto_lacres[]','lacre_iipr[]','lacre_correios[]','etiqueta_correios[]','regional_lacres[]','grupo_lacres[]','snapshot_oficio'];
    for (var n=0;n<nomes.length;n++){
        var els = form.querySelectorAll('input[name="'+nomes[n]+'"]');
        for (var i=0;i<els.length;i++) { els[i].parentNode.removeChild(els[i]); }
    }

    // v8.13: Criar snapshot JSON da grade (fonte única de verdade)
    var snapshot = [];

    // v8.12: Coletar APENAS linhas visíveis (não excluídas) com atributo data-posto-codigo
    // Ignora linhas com display:none, com classe 'removido', ou que estejam ocultas
    var rows = form.querySelectorAll('tr[data-posto-codigo]');
    if (!rows || rows.length === 0) {
        rows = document.querySelectorAll('tr[data-posto-codigo]');
    }
    
    for (var r=0;r<rows.length;r++){
        var tr = rows[r];
        
        // v8.12: Pular linhas que estão ocultas (display:none ou classe removido)
        if (tr.style && tr.style.display === 'none') continue;
        if (tr.className && tr.className.indexOf('removido') !== -1) continue;
        
        var computedStyle = window.getComputedStyle ? window.getComputedStyle(tr) : null;
        if (computedStyle && computedStyle.display === 'none') continue;
        
        var posto = tr.getAttribute('data-posto-codigo');
        if (!posto) continue;

        // v8.9: Capturar regional da linha (usar data-regional-codigo ou data-regional)
        var regional = tr.getAttribute('data-regional-codigo') || tr.getAttribute('data-regional') || '0';

        // v8.12.3: Capturar grupo da linha (CAPITAL, CENTRAL IIPR, REGIONAIS)
        var grupo = tr.getAttribute('data-grupo') || '';

        // Encontrar inputs na linha (prioriza data-indice para capturar posto manual corretamente)
        var selI = 'input[data-indice="' + posto + '"][data-tipo="iipr"]';
        var selC = 'input[data-indice="' + posto + '"][data-tipo="correios"]';
        var selE = '[data-indice="' + posto + '"]';

        var inpIIPR = tr.querySelector(selI) || tr.querySelector('input[name^="lacre_iipr"], input[data-tipo="iipr"], input.lacre');
        var inpCorr = tr.querySelector(selC) || tr.querySelector('input[name^="lacre_correios"], input[data-tipo="correios"], input.lacre');
        var inpEtiq = tr.querySelector('input[name^="etiqueta_correios"]' + selE + ', input.etiqueta-barras' + selE) || tr.querySelector('input[name^="etiqueta_correios"], input.etiqueta-barras');

        var valI = inpIIPR ? String(inpIIPR.value || '').trim() : '';
        var valC = inpCorr ? String(inpCorr.value || '').trim() : '';
        var valE = inpEtiq ? String(inpEtiq.value || '').trim() : '';

        // v8.13: Adicionar ao snapshot JSON
        snapshot.push({
            posto: posto,
            grupo: grupo,
            regional: regional,
            lacre_iipr: valI,
            lacre_correios: valC,
            etiqueta_correios: valE
        });

        // v8.13: Manter arrays antigos para compatibilidade
        var a = document.createElement('input'); a.type='hidden'; a.name='posto_lacres[]'; a.value=posto; form.appendChild(a);
        var b = document.createElement('input'); b.type='hidden'; b.name='lacre_iipr[]'; b.value=valI; form.appendChild(b);
        var c = document.createElement('input'); c.type='hidden'; c.name='lacre_correios[]'; c.value=valC; form.appendChild(c);
        var d = document.createElement('input'); d.type='hidden'; d.name='etiqueta_correios[]'; d.value=valE; form.appendChild(d);
        var e = document.createElement('input'); e.type='hidden'; e.name='regional_lacres[]'; e.value=regional; form.appendChild(e);
        var f = document.createElement('input'); f.type='hidden'; f.name='grupo_lacres[]'; f.value=grupo; form.appendChild(f);
    }

    // v8.13: Criar input hidden com snapshot JSON
    var snapshotInput = document.createElement('input');
    snapshotInput.type = 'hidden';
    snapshotInput.name = 'snapshot_oficio';
    snapshotInput.value = JSON.stringify(snapshot);
    form.appendChild(snapshotInput);
}

// v8.11: Persistencia de lacres/etiquetas em localStorage
// Salva estado dos inputs de lacre IIPR, lacre Correios e etiqueta Correios
function obterContextoStorageLacresCorreios() {
    var path = window.location && window.location.pathname ? String(window.location.pathname) : '';
    var search = window.location && typeof window.location.search === 'string' ? String(window.location.search) : '';
    return path + '|' + search;
}

function montarChaveStorageLacres(idDespacho, regionalCodigo, postoCodigo) {
    return 'oficioCorreios:' + String(idDespacho || '') + ':' + String(regionalCodigo || '0') + ':' + String(postoCodigo || '') + ':' + obterContextoStorageLacresCorreios();
}

function salvarEstadoEtiquetasCorreios() {
    if (typeof window.localStorage === 'undefined') {
        return;
    }

    // v8.11.2: Se estamos em recalculo por lacre, nao salvar em localStorage para
    // evitar sobrescrita de valores quando a restauracao rodar (mesmo que seja pulada
    // normalmente, eh mais seguro nao ter valores vazios gravados)
    try {
        var recalEl = document.getElementById('recalculo_por_lacre');
        if (recalEl && String(recalEl.value) === '1') {
            return;
        }
    } catch (e) {
        // ignore
    }

    var idDespachoInput = document.getElementById('id_despacho');
    var idDespacho = idDespachoInput ? idDespachoInput.value : '';

    var rows = document.querySelectorAll('tr[data-posto-codigo]');
    for (var r = 0; r < rows.length; r++) {
        var tr = rows[r];
        var postoCodigo = tr.getAttribute('data-posto-codigo');
        var regionalCodigo = tr.getAttribute('data-regional-codigo') || tr.getAttribute('data-regional') || '0';

        if (!postoCodigo) continue;

        // v8.13.4: Salvar TODOS os inputs (lacres IIPR, Correios e etiqueta)
        // para preservar ao excluir linha ou filtrar
        var inpIIPR = tr.querySelector('input[name^="lacre_iipr"], input[data-tipo="iipr"]');
        var inpCorr = tr.querySelector('input[name^="lacre_correios"], input[data-tipo="correios"]');
        var inpEtiq = tr.querySelector('input[name^="etiqueta_correios"], input.etiqueta-barras');
        
        var valI = inpIIPR ? String(inpIIPR.value || '').trim() : '';
        var valC = inpCorr ? String(inpCorr.value || '').trim() : '';
        var valE = inpEtiq ? String(inpEtiq.value || '').trim() : '';

        var chaveBase = montarChaveStorageLacres(idDespacho, regionalCodigo, postoCodigo);
        var valor = { 
            lacre_iipr: valI,
            lacre_correios: valC,
            etiqueta_correios: valE 
        };

        try {
            window.localStorage.setItem(chaveBase, JSON.stringify(valor));
        } catch (e) {
            // localStorage cheio ou desabilitado
        }
    }
}

// Forca salvar mesmo quando recalculo_por_lacre estiver ativo
function salvarEstadoEtiquetasCorreiosForcado() {
    var recalEl = document.getElementById('recalculo_por_lacre');
    var grupoEl = document.getElementById('recalculo_grupo');
    var antigo = null;
    var grupoAntigo = null;
    if (recalEl) {
        antigo = recalEl.value;
        recalEl.value = '0';
    }
    if (grupoEl) {
        grupoAntigo = grupoEl.value;
        grupoEl.value = '';
    }
    try {
        salvarEstadoEtiquetasCorreios();
    } catch (e) {
        // ignore
    }
    if (recalEl && antigo !== null) {
        recalEl.value = antigo;
    }
    if (grupoEl && grupoAntigo !== null) {
        grupoEl.value = grupoAntigo;
    }
}

// Salvar estado temporario para restauracao apos inserir/cadastrar posto
function salvarEstadoTemporarioLacres() {
    if (typeof window.localStorage === 'undefined') {
        return;
    }

    var rows = document.querySelectorAll('tr[data-posto-codigo]');
    var mapa = {};
    for (var r = 0; r < rows.length; r++) {
        var tr = rows[r];
        var postoCodigo = tr.getAttribute('data-posto-codigo');
        var regionalCodigo = tr.getAttribute('data-regional-codigo') || tr.getAttribute('data-regional') || '0';
        if (!postoCodigo) continue;

        var inpIIPR = tr.querySelector('input[name^="lacre_iipr"], input[data-tipo="iipr"]');
        var inpCorr = tr.querySelector('input[name^="lacre_correios"], input[data-tipo="correios"]');
        var inpEtiq = tr.querySelector('input[name^="etiqueta_correios"], input.etiqueta-barras');

        var valI = inpIIPR ? String(inpIIPR.value || '').trim() : '';
        var valC = inpCorr ? String(inpCorr.value || '').trim() : '';
        var valE = inpEtiq ? String(inpEtiq.value || '').trim() : '';

        mapa[regionalCodigo + '|' + postoCodigo] = {
            lacre_iipr: valI,
            lacre_correios: valC,
            etiqueta_correios: valE
        };
    }

    try {
        window.localStorage.setItem('lacres_tmp_restore', JSON.stringify(mapa));
    } catch (e) {
        // ignore
    }
}

function restaurarEstadoTemporarioLacres() {
    if (typeof window.localStorage === 'undefined') {
        return;
    }

    var json = window.localStorage.getItem('lacres_tmp_restore');
    if (!json) return;

    var mapa;
    try {
        mapa = JSON.parse(json);
    } catch (e) {
        return;
    }

    var rows = document.querySelectorAll('tr[data-posto-codigo]');
    for (var r = 0; r < rows.length; r++) {
        var tr = rows[r];
        var postoCodigo = tr.getAttribute('data-posto-codigo');
        var regionalCodigo = tr.getAttribute('data-regional-codigo') || tr.getAttribute('data-regional') || '0';
        if (!postoCodigo) continue;

        var chave = regionalCodigo + '|' + postoCodigo;
        var valor = mapa[chave];
        if (!valor) continue;

        var inpIIPR = tr.querySelector('input[name^="lacre_iipr"], input[data-tipo="iipr"]');
        var inpCorr = tr.querySelector('input[name^="lacre_correios"], input[data-tipo="correios"]');
        var inpEtiq = tr.querySelector('input[name^="etiqueta_correios"], input.etiqueta-barras');

        if (inpIIPR && valor.lacre_iipr) inpIIPR.value = valor.lacre_iipr;
        if (inpCorr && valor.lacre_correios) inpCorr.value = valor.lacre_correios;
        if (inpEtiq && valor.etiqueta_correios) inpEtiq.value = valor.etiqueta_correios;
    }

    window.localStorage.removeItem('lacres_tmp_restore');
}

function gerarSnapshotLacres() {
    var rows = document.querySelectorAll('tr[data-posto-codigo]');
    var mapa = {};
    for (var r = 0; r < rows.length; r++) {
        var tr = rows[r];
        var postoCodigo = tr.getAttribute('data-posto-codigo');
        var regionalCodigo = tr.getAttribute('data-regional-codigo') || tr.getAttribute('data-regional') || '0';
        if (!postoCodigo) continue;

        var inpIIPR = tr.querySelector('input[name^="lacre_iipr"], input[data-tipo="iipr"]');
        var inpCorr = tr.querySelector('input[name^="lacre_correios"], input[data-tipo="correios"]');
        var inpEtiq = tr.querySelector('input[name^="etiqueta_correios"], input.etiqueta-barras');

        mapa[regionalCodigo + '|' + postoCodigo] = {
            lacre_iipr: inpIIPR ? String(inpIIPR.value || '').trim() : '',
            lacre_correios: inpCorr ? String(inpCorr.value || '').trim() : '',
            etiqueta_correios: inpEtiq ? String(inpEtiq.value || '').trim() : ''
        };
    }
    return mapa;
}

function anexarSnapshotAoForm(form) {
    if (!form) return;
    var input = form.querySelector('input[name="snapshot_lacres"]');
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'snapshot_lacres';
        form.appendChild(input);
    }
    try {
        input.value = JSON.stringify(gerarSnapshotLacres());
    } catch (e) {
        input.value = '';
    }
}

// v8.13.4: Salvar TODOS os inputs (lacres + etiquetas) no localStorage
// Usado antes de excluir uma linha para preservar TUDO que foi digitado
function salvarSomenteEtiquetasCorreios() {
    if (typeof window.localStorage === 'undefined') {
        return;
    }

    var idDespachoInput = document.getElementById('id_despacho');
    var idDespacho = idDespachoInput ? idDespachoInput.value : '';

    var rows = document.querySelectorAll('tr[data-posto-codigo]');
    for (var r = 0; r < rows.length; r++) {
        var tr = rows[r];
        var postoCodigo = tr.getAttribute('data-posto-codigo');
        var regionalCodigo = tr.getAttribute('data-regional-codigo') || tr.getAttribute('data-regional') || '0';

        if (!postoCodigo) continue;

        // v8.13.4: Salvar TODOS os inputs (não apenas etiquetas)
        var inpIIPR = tr.querySelector('input[name^="lacre_iipr"], input[data-tipo="iipr"]');
        var inpCorr = tr.querySelector('input[name^="lacre_correios"], input[data-tipo="correios"]');
        var inpEtiq = tr.querySelector('input[name^="etiqueta_correios"], input.etiqueta-barras');
        
        var valI = inpIIPR ? String(inpIIPR.value || '').trim() : '';
        var valC = inpCorr ? String(inpCorr.value || '').trim() : '';
        var valE = inpEtiq ? String(inpEtiq.value || '').trim() : '';

        var chaveBase = montarChaveStorageLacres(idDespacho, regionalCodigo, postoCodigo);
        var valor = { 
            lacre_iipr: valI,
            lacre_correios: valC,
            etiqueta_correios: valE 
        };

        try {
            window.localStorage.setItem(chaveBase, JSON.stringify(valor));
        } catch (e) {
            // localStorage cheio ou desabilitado
        }
    }
}

// v8.11: Restaura estado dos inputs de lacre/etiqueta dos Correios
function restaurarEstadoEtiquetasCorreios() {
    if (typeof window.localStorage === 'undefined') {
        return;
    }

    // Restaura apenas as etiquetas de correios (códigos de barras).
    // Mantemos a restauração mesmo quando `recalculo_por_lacre` estiver setado,
    // pois lacres são recalculados no servidor e não devem ser substituídos
    // pelo conteúdo do localStorage; entretanto precisamos preservar as
    // etiquetas do usuário em qualquer fluxo (remoção, filtro, etc.).

    var idDespachoInput = document.getElementById('id_despacho');
    var idDespacho = idDespachoInput ? idDespachoInput.value : '';

    var rows = document.querySelectorAll('tr[data-posto-codigo]');
    for (var r = 0; r < rows.length; r++) {
        var tr = rows[r];
        var postoCodigo = tr.getAttribute('data-posto-codigo');
        var regionalCodigo = tr.getAttribute('data-regional-codigo') || tr.getAttribute('data-regional') || '0';

        if (!postoCodigo) continue;

        var chaveBase = montarChaveStorageLacres(idDespacho, regionalCodigo, postoCodigo);
        var chaveLegada = 'oficioCorreios:' + idDespacho + ':' + regionalCodigo + ':' + postoCodigo;
        var json = window.localStorage.getItem(chaveBase);

        if (!json) {
            json = window.localStorage.getItem(chaveLegada);
        }

        if (!json) continue;

        var valor;
        try {
            valor = JSON.parse(json);
        } catch (e) {
            continue;
        }

        // v8.13.4: Restaurar TODOS os inputs (lacres IIPR, Correios e etiqueta)
        var inpIIPR = tr.querySelector('input[name^="lacre_iipr"], input[data-tipo="iipr"]');
        var inpCorr = tr.querySelector('input[name^="lacre_correios"], input[data-tipo="correios"]');
        var inpEtiq = tr.querySelector('input[name^="etiqueta_correios"], input.etiqueta-barras');
        
        if (valor) {
            if (inpIIPR && valor.lacre_iipr) {
                inpIIPR.value = valor.lacre_iipr;
            }
            if (inpCorr && valor.lacre_correios) {
                inpCorr.value = valor.lacre_correios;
            }
            if (inpEtiq && valor.etiqueta_correios) {
                inpEtiq.value = valor.etiqueta_correios;
            }
        }
    }
}

// v8.14.1: Função para preencher inputs visualmente antes de imprimir
// Garante que lacres/etiquetas aparecem no PDF gerado
function preencherInputsParaImpressao() {
    var rows = document.querySelectorAll('tr[data-posto-codigo]');
    for (var r = 0; r < rows.length; r++) {
        var tr = rows[r];
        if (tr.style && tr.style.display === 'none') continue;
        
        var inpIIPR = tr.querySelector('input[name^="lacre_iipr"], input[data-tipo="iipr"]');
        var inpCorr = tr.querySelector('input[name^="lacre_correios"], input[data-tipo="correios"]');
        var inpEtiq = tr.querySelector('input[name^="etiqueta_correios"], input.etiqueta-barras');
        
        // Garantir que values estão visíveis (renderizados no DOM)
        if (inpIIPR && inpIIPR.value) { inpIIPR.setAttribute('value', inpIIPR.value); }
        if (inpCorr && inpCorr.value) { inpCorr.setAttribute('value', inpCorr.value); }
        if (inpEtiq && inpEtiq.value) { inpEtiq.setAttribute('value', inpEtiq.value); }
    }
}

// v8.14.7: Confirmação simplificada (volta ao v8.14.5 - sem salvamento automático de etiquetas)
function confirmarGravarEImprimir() {
    recolherPainelAnalise();
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';

    var modal = document.createElement('div');
    modal.style.cssText = 'background:white;padding:30px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.3);max-width:520px;width:94%;text-align:center;';

    var titulo = document.createElement('h3');
    titulo.textContent = 'Como deseja gravar o Ofício dos Correios?';
    titulo.style.cssText = 'margin-top:0;color:#333;';

    var _ultCor = <?php echo (int)$ultimo_despacho_correios; ?>;
    var _ultPT  = <?php echo (int)$ultimo_despacho_pt; ?>;
    var _infoOfic = '';
    if (_ultCor > 0 || _ultPT > 0) {
        _infoOfic = '<div style="background:#f0f4ff;border:1px solid #b0c0e0;border-radius:5px;padding:8px 12px;margin-bottom:10px;font-size:12px;text-align:left;">' +
            '<b>Últimos ofícios salvos:</b><br>' +
            (_ultCor > 0 ? '🟦 Correios: <b>Nº ' + _ultCor + '</b><br>' : '') +
            (_ultPT  > 0 ? '🟩 Poupa Tempo: <b>Nº ' + _ultPT  + '</b>' : '') +
            '</div>';
    }
    var texto = document.createElement('p');
    texto.innerHTML = _infoOfic +
                      '<b>Sobrescrever:</b> Apaga lotes do ofício escolhido e grava este no lugar.<br><br>' +
                      '<b>Criar Novo:</b> Mantém ofício anterior e cria outro com novo número.<br><br>' +
                      '<b>Cancelar:</b> Aborta a operação.';
    texto.style.cssText = 'margin:16px 0;line-height:1.6;color:#555;text-align:left;';

    /* ── CAMPO RESPONSÁVEL ── */
    var lblResp = document.createElement('label');
    lblResp.style.cssText = 'display:block;text-align:left;font-weight:bold;font-size:13px;color:#222;margin-top:12px;margin-bottom:4px;';
    lblResp.textContent = 'Responsável pela emissão (obrigatório):';

    var inputResp = document.createElement('input');
    inputResp.type = 'text';
    inputResp.placeholder = 'Digite seu nome completo';
    inputResp.style.cssText = 'width:100%;padding:10px 12px;border:2px solid #ccc;border-radius:6px;font-size:14px;margin-bottom:4px;';
    /* Pré-preencher do localStorage */
    var _invalidos = ['Responsável Não Informado','Responsavel Nao Informado','Sistema','conferencia','responsavel nao informado'];
    function _respOk(v) { v=(v||'').replace(/\s/g,''); return v!=='' && _invalidos.indexOf(v.trim())<0; }
    /* Limpar localStorage se tiver valor ruim */
    var _ls = localStorage.getItem('responsavel_oficio_correios') || '';
    if (!_respOk(_ls)) { localStorage.removeItem('responsavel_oficio_correios'); _ls=''; }
    var respSalvo = _ls;
    if (!respSalvo) {
        var campoPagina = document.querySelector('input[name="responsavel"]');
        if (campoPagina && _respOk(campoPagina.value)) respSalvo = campoPagina.value;
    }
    inputResp.value = respSalvo;

    var errResp = document.createElement('div');
    errResp.style.cssText = 'color:#c00;font-size:12px;margin-bottom:8px;display:none;';
    errResp.textContent = 'Informe o nome do responsável para continuar.';

    /* ── CAMPO NÚMERO PARA SOBRESCREVER ── */
    var campoNumero = document.createElement('input');
    campoNumero.type = 'number';
    campoNumero.placeholder = 'Número do ofício para sobrescrever (opcional)';
    campoNumero.style.cssText = 'width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:4px;margin:8px 0 0 0;';

    function validarEProsseguir(modo) {
        var nome = inputResp.value.trim();
        if (!_respOk(nome)) {
            errResp.style.display = 'block';
            inputResp.style.borderColor = '#c00';
            inputResp.focus();
            return;
        }
        errResp.style.display = 'none';
        /* Persistir nome */
        var _nomeReal = inputResp.value.trim();
        if (_respOk(_nomeReal)) localStorage.setItem('responsavel_oficio_correios', _nomeReal);
        /* Injetar no form hidden */
        var hResp = document.getElementById('responsavelCorreios');
        if (hResp) hResp.value = _nomeReal;
        /* Remover overlay */
        document.body.removeChild(overlay);
        /* Configurar modo e submeter */
        var campoModo = document.getElementById('modo_oficio');
        if (campoModo) campoModo.value = modo;
        if (modo === 'sobrescrever') {
            var campoId = document.getElementById('id_oficio_sobrescrever');
            if (campoId) campoId.value = String(campoNumero.value || '').trim();
        }
        gravarEImprimirCorreios();
    }

    var botoes = document.createElement('div');
    botoes.style.cssText = 'display:flex;gap:10px;justify-content:center;margin-top:20px;flex-wrap:wrap;';

    var btnSobrescrever = document.createElement('button');
    btnSobrescrever.textContent = 'Sobrescrever';
    btnSobrescrever.style.cssText = 'background:#ff9800;color:white;border:none;padding:12px 24px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:bold;';
    btnSobrescrever.onclick = function() { validarEProsseguir('sobrescrever'); };

    var btnCriarNovo = document.createElement('button');
    btnCriarNovo.textContent = 'Criar Novo';
    btnCriarNovo.style.cssText = 'background:#28a745;color:white;border:none;padding:12px 24px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:bold;';
    btnCriarNovo.onclick = function() { validarEProsseguir('novo'); };

    var btnCancelar = document.createElement('button');
    btnCancelar.textContent = 'Cancelar';
    btnCancelar.style.cssText = 'background:#dc3545;color:white;border:none;padding:12px 24px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:bold;';
    btnCancelar.onclick = function() { document.body.removeChild(overlay); };

    botoes.appendChild(btnSobrescrever);
    botoes.appendChild(btnCriarNovo);
    botoes.appendChild(btnCancelar);

    modal.appendChild(titulo);
    modal.appendChild(texto);
    modal.appendChild(lblResp);
    modal.appendChild(inputResp);
    modal.appendChild(errResp);
    modal.appendChild(campoNumero);
    modal.appendChild(botoes);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    /* Foco e Enter no campo de responsavel */
    setTimeout(function() { inputResp.focus(); }, 80);
    inputResp.addEventListener('keydown', function(e) {
        if (e.keyCode === 13) { validarEProsseguir('novo'); }
    });
}

// v8.14.6: FUNÇÃO REMOVIDA - segunda modal não é mais necessária
// Etiquetas salvam automaticamente dentro do handler salvar_oficio_correios

// v8.14.9.2: Limpar Sessão DEFINITIVAMENTE zera TODOS inputs (lacres E etiquetas)
function confirmarLimparSessao(form) {
    var msg = "⚠️ ATENÇÃO: LIMPEZA COMPLETA DA SESSÃO ⚠️\n\n" +
              "Isso irá ZERAR TODOS os inputs:\n" +
              "✓ Lacres IIPR (Capital, Central, Regionais)\n" +
              "✓ Lacres Correios\n" +
              "✓ Etiquetas Correios (código de barras)\n" +
              "✓ Valores do topo (lacre inicial)\n" +
              "✓ localStorage (dados salvos no navegador)\n\n" +
              "Esta ação NÃO PODE SER DESFEITA!\n\n" +
              "Deseja continuar?";
    
    if (!window.confirm(msg)) {
        return false;
    }

    try {
        // 1. Zerar inputs DO TOPO (lacre_capital, lacre_central, lacre_regionais)
        var inputTopo = document.getElementById('lacre_capital_input');
        if (inputTopo) { inputTopo.value = ''; }
        inputTopo = document.getElementById('lacre_central_input');
        if (inputTopo) { inputTopo.value = ''; }
        inputTopo = document.getElementById('lacre_regionais_input');
        if (inputTopo) { inputTopo.value = ''; }
        
        // 2. Zerar TODOS inputs de lacres (por data-tipo)
        var lacresIIPR = document.querySelectorAll('input[data-tipo="iipr"]');
        for (var i = 0; i < lacresIIPR.length; i++) {
            lacresIIPR[i].value = '';
            try { lacresIIPR[i].removeAttribute('readonly'); } catch (e) {}
        }
        
        var lacresCorreios = document.querySelectorAll('input[data-tipo="correios"]');
        for (var j = 0; j < lacresCorreios.length; j++) {
            lacresCorreios[j].value = '';
            try { lacresCorreios[j].removeAttribute('readonly'); } catch (e) {}
        }
        
        // 3. Zerar TODAS etiquetas Correios (classe etiqueta-barras)
        var etiquetas = document.querySelectorAll('input.etiqueta-barras');
        for (var k = 0; k < etiquetas.length; k++) {
            etiquetas[k].value = '';
            try { etiquetas[k].removeAttribute('readonly'); } catch (e) {}
        }
        
        // 4. Zerar inputs antigos (fallback para classes antigas)
        var inputsAntigos = document.querySelectorAll('input.lacre, input.central-correios, input.central-etiqueta');
        for (var m = 0; m < inputsAntigos.length; m++) {
            inputsAntigos[m].value = '';
        }
        
        // 5. Limpar COMPLETAMENTE localStorage (tudo relacionado a ofícios)
        var idDespInput = document.getElementById('id_despacho');
        var idDespacho = idDespInput ? idDespInput.value : '';
        
        // Limpar por padrões conhecidos
        var padroes = [
            'oficioCorreios:',
            'snapshot_correios:',
            'oficioPT:',
            'splitVisual:'
        ];
        
        for (var n = localStorage.length - 1; n >= 0; n--) {
            var key = localStorage.key(n);
            if (!key) continue;
            
            for (var p = 0; p < padroes.length; p++) {
                if (key.indexOf(padroes[p]) === 0) {
                    localStorage.removeItem(key);
                    break;
                }
            }
        }
        
        console.log('[LIMPAR SESSÃO] Todos inputs e localStorage limpos!');
        alert('✅ Sessão limpa com sucesso!\n\nTodos os campos foram zerados.');
        
    } catch (e) {
        console.error('[LIMPAR SESSÃO] Erro:', e);
        alert('⚠️ Erro ao limpar sessão: ' + e.message);
    }

    // Permitir que o form submeta para limpar sessão no servidor também
    return true;
}

// v8.14.6: Função SIMPLIFICADA - etiquetas salvam automaticamente no handler
function gravarEImprimirCorreios() {
    /* Responsavel ja validado pelo modal confirmarGravarEImprimir */
    var hResp = document.getElementById('responsavelCorreios');
    if (!hResp || hResp.value.replace(/\s/g,'') === '') {
        alert('Responsavel nao informado. Use o botao Gravar e Imprimir para preencher o nome.');
        return;
    }
    recolherPainelAnalise();
    var form = document.getElementById('formOficioCorreios');
    if (!form) {
        alert('Erro: Formulário não encontrado.');
        return;
    }
    
    // Preencher inputs visualmente
    if (typeof preencherInputsParaImpressao === 'function') {
        try { preencherInputsParaImpressao(); } catch (e) { /* ignore */ }
    }
    
    // Salvar estado no localStorage
    if (typeof salvarEstadoEtiquetasCorreios === 'function') {
        try { salvarEstadoEtiquetasCorreios(); } catch (e) { /* ignore */ }
    }
    
    // v8.14.6: Sempre usa salvar_oficio_correios (etiquetas salvam automaticamente dentro dele)
    document.getElementById('acaoCorreios').value = 'salvar_oficio_correios';
    document.getElementById('imprimirAposSalvar').value = '1';
    prepararLacresCorreiosParaSubmit(form);
    form.submit();
}

function apenasGravarCorreios() {
    var hResp = document.getElementById('responsavelCorreios');
    if (!hResp || hResp.value.replace(/\s/g,'') === '') {
        confirmarGravarEImprimir();
        return;
    }
    recolherPainelAnalise();
    var form = document.getElementById('formOficioCorreios');
    if (form) {
        document.getElementById('acaoCorreios').value = 'salvar_oficio_correios';
        document.getElementById('imprimirAposSalvar').value = '0';
        prepararLacresCorreiosParaSubmit(form);
        form.submit();
    } else {
        alert('Erro: Formulario nao encontrado.');
    }
}

// Funcoes para excluir postos (compativel com navegadores antigos)
function excluirPosto(codigo, grupo, nome) {
    if (confirm('Confirma a exclusao do posto ' + nome + '?')) {
        var formExcluir = document.getElementById('formExcluirPosto');
        try { if (typeof salvarSomenteEtiquetasCorreios === 'function') salvarSomenteEtiquetasCorreios(); } catch (e) { /* ignore */ }
        try { if (typeof salvarEstadoEtiquetasCorreiosForcado === 'function') salvarEstadoEtiquetasCorreiosForcado(); } catch (e2) { /* ignore */ }
        try { if (typeof salvarEstadoTemporarioLacres === 'function') salvarEstadoTemporarioLacres(); } catch (e3) { /* ignore */ }
        try { if (typeof anexarSnapshotAoForm === 'function' && formExcluir) anexarSnapshotAoForm(formExcluir); } catch (e4) { /* ignore */ }
        document.getElementById('excluir_posto_flag').value = '1';
        document.getElementById('excluir_posto_regional_flag').value = '';
        document.getElementById('excluir_codigo_posto').value = codigo;
        document.getElementById('excluir_grupo_posto').value = grupo;
        document.getElementById('excluir_nome_posto').value = nome;
        formExcluir.submit();
    }
}

function excluirPostoRegional(codigo, nome) {
    if (confirm('Confirma a exclusao do posto REGIONAL ' + nome + '?')) {
        var formExcluir = document.getElementById('formExcluirPosto');
        try { if (typeof salvarSomenteEtiquetasCorreios === 'function') salvarSomenteEtiquetasCorreios(); } catch (e) { /* ignore */ }
        try { if (typeof salvarEstadoEtiquetasCorreiosForcado === 'function') salvarEstadoEtiquetasCorreiosForcado(); } catch (e2) { /* ignore */ }
        try { if (typeof salvarEstadoTemporarioLacres === 'function') salvarEstadoTemporarioLacres(); } catch (e3) { /* ignore */ }
        try { if (typeof anexarSnapshotAoForm === 'function' && formExcluir) anexarSnapshotAoForm(formExcluir); } catch (e4) { /* ignore */ }
        document.getElementById('excluir_posto_flag').value = '';
        document.getElementById('excluir_posto_regional_flag').value = '1';
        document.getElementById('excluir_codigo_posto').value = codigo;
        document.getElementById('excluir_info_regional').value = nome;
        document.getElementById('excluir_nome_posto').value = nome;
        formExcluir.submit();
    }
}

// Funcao para limpar todos os inputs de uma coluna em um grupo especifico
function limparColuna(grupo, tipoColuna) {
    var nomeColuna = '';
    if (tipoColuna === 'lacre_iipr') {
        nomeColuna = 'Lacre IIPR';
    } else if (tipoColuna === 'lacre_correios') {
        nomeColuna = 'Lacre Correios';
    } else if (tipoColuna === 'etiqueta_correios') {
        nomeColuna = 'Etiqueta Correios';
    }
    
    if (!confirm('Deseja realmente apagar todos os valores da coluna "' + nomeColuna + '" do grupo "' + grupo + '"?\n\nEsta acao nao pode ser desfeita.')) {
        return;
    }
    
    // Encontrar a tabela do grupo (verificar ambos os ids possiveis)
    var tabelaId = 'tabela-' + grupo.toLowerCase().replace(/ /g, '-');
    var tabela = document.getElementById(tabelaId);
    
    // Se for CENTRAL IIPR, verificar id alternativo
    if (!tabela && grupo.toUpperCase() === 'CENTRAL IIPR') {
        tabela = document.getElementById('tblCentralIIPR');
    }
    
    if (!tabela) {
        alert('Tabela nao encontrada: ' + tabelaId);
        return;
    }
    
    // Limpar todos os inputs da coluna especificada
    var inputs = tabela.querySelectorAll('input[name^="' + tipoColuna + '["]');
    for (var i = 0; i < inputs.length; i++) {
        inputs[i].value = '';
    }
    
    // Marcar como nao salvo
    marcarComoNaoSalvo();
    
    alert('Coluna "' + nomeColuna + '" do grupo "' + grupo + '" foi limpa com sucesso!');
}

function inteiroPositivoOuZero(valor) {
    var texto = String(valor || '').trim();
    var base = texto;
    var grupos;
    var numero;

    if (texto.indexOf('-') >= 0) {
        var partes = texto.split('-');
        for (var i = partes.length - 1; i >= 0; i--) {
            if (String(partes[i] || '').replace(/\D+/g, '') !== '') {
                base = partes[i];
                break;
            }
        }
    }

    grupos = String(base || '').match(/\d+/g);
    if (!grupos || !grupos.length) {
        return 0;
    }

    numero = parseInt(grupos[grupos.length - 1], 10);
    if (isNaN(numero) || numero < 0) {
        return 0;
    }
    return numero;
}

function podeAplicarSequenciaDuranteDigitacao(valor) {
    var texto = String(valor || '').trim();
    if (texto === '') {
        return true;
    }
    return /^\d+(?:-\d+)*$/.test(texto);
}

function obterTabelaGrupoLacres(grupo) {
    var tabela = document.getElementById('tabela-' + String(grupo || '').toLowerCase().replace(/ /g, '-'));
    if (!tabela && grupo === 'CENTRAL IIPR') {
        tabela = document.getElementById('tblCentralIIPR') || document.getElementById('tabela-central-iipr');
    }
    return tabela;
}

function obterLinhasGrupoLacres(grupo) {
    var tabela = obterTabelaGrupoLacres(grupo);
    if (!tabela) return [];
    return tabela.querySelectorAll('tbody tr[data-posto-codigo], tr[data-posto-codigo]');
}

function obterInputLacreLinha(linha, tipo) {
    if (!linha) return null;
    if (tipo === 'iipr') {
        return linha.querySelector('input[data-tipo="iipr"], input[name^="lacre_iipr["]');
    }
    if (tipo === 'correios') {
        return linha.querySelector('input[data-tipo="correios"], input[name^="lacre_correios["]');
    }
    return null;
}

function inputLacreAvulso(input) {
    return !!(input && String(input.getAttribute('data-lacre-avulso') || '') === '1');
}

function definirEstadoLacreAvulso(input, botao, ativo) {
    if (!input || !botao) return;
    if (ativo) {
        input.setAttribute('data-lacre-avulso', '1');
        if (input.className.indexOf('lacre-avulso') < 0) input.className += ' lacre-avulso';
        if (botao.className.indexOf('ativo') < 0) botao.className += ' ativo';
        botao.title = 'Lacre avulso ativo';
    } else {
        input.setAttribute('data-lacre-avulso', '0');
        input.className = input.className.replace(/\s*lacre-avulso/g, '');
        botao.className = botao.className.replace(/\s*ativo/g, '');
        botao.title = 'Ativar lacre avulso';
    }
}

function propagarSequenciaDesdeRow(linhaOrigem, tipoOrigem) {
    if (!linhaOrigem) return;
    var corpo = linhaOrigem.parentNode;
    if (!corpo) return;
    var grupo = String(linhaOrigem.getAttribute('data-grupo') || '');
    var linhas = corpo.querySelectorAll('tr[data-posto-codigo]');
    if (!linhas || !linhas.length) return;
    var indiceOrigem = -1;
    for (var i = 0; i < linhas.length; i++) {
        if (linhas[i] === linhaOrigem) { indiceOrigem = i; break; }
    }
    if (indiceOrigem < 0) return;
    var inputIiprOrigem = linhaOrigem.querySelector('input[data-tipo="iipr"]');
    var inputCorreiosOrigem = linhaOrigem.querySelector('input[data-tipo="correios"]');
    if (!inputIiprOrigem && !inputCorreiosOrigem) return;
    // v1.1.11: se o input de origem está avulso, não propagar para os demais
    if (tipoOrigem === 'iipr' && inputLacreAvulso(inputIiprOrigem)) return;
    if (tipoOrigem === 'correios' && inputLacreAvulso(inputCorreiosOrigem)) return;
    var valorIipr = inputIiprOrigem ? inteiroPositivoOuZero(inputIiprOrigem.value || '') : NaN;
    var valorCorreios = inputCorreiosOrigem ? inteiroPositivoOuZero(inputCorreiosOrigem.value || '') : NaN;
    if (tipoOrigem === 'correios' && isNaN(valorIipr) && !isNaN(valorCorreios)) {
        valorIipr = valorCorreios - 1;
        if (inputIiprOrigem && valorIipr >= 0) inputIiprOrigem.value = String(valorIipr);
    }
    if (isNaN(valorIipr)) return;
    if (inputCorreiosOrigem && !inputLacreAvulso(inputCorreiosOrigem)) {
        valorCorreios = valorIipr + 1;
        inputCorreiosOrigem.value = String(valorCorreios);
    }
    var passo = obterPassoSequenciaGrupo(grupo);
    var proximoIipr = valorIipr + passo;
    var proximoCorreios = valorIipr + 1 + passo;
    for (var j = indiceOrigem + 1; j < linhas.length; j++) {
        var linha = linhas[j];
        if (!linha || (linha.style && linha.style.display === 'none')) continue;
        var inpI = linha.querySelector('input[data-tipo="iipr"]');
        var inpC = linha.querySelector('input[data-tipo="correios"]');
        if (inpI && !inputLacreAvulso(inpI)) inpI.value = String(proximoIipr);
        if (inpC && !inputLacreAvulso(inpC)) inpC.value = String(proximoCorreios);
        proximoIipr += passo;
        proximoCorreios += passo;
    }
    try { salvarEstadoEtiquetasCorreios(); } catch (e) {}
    marcarComoNaoSalvo();
}

function alternarLacreAvulso(botao) {
    if (!botao || !botao.parentNode) return false;
    var input = botao.parentNode.querySelector('input.lacre');
    if (!input) return false;
    definirEstadoLacreAvulso(input, botao, !inputLacreAvulso(input));
    var tipo = String(botao.getAttribute('data-tipo-avulso') || '');
    if (!tipo) tipo = input.getAttribute('data-tipo') || 'iipr';
    var linhaAvulso = input.closest ? input.closest('tr[data-posto-codigo]') : (function() {
        var el = input.parentNode;
        while (el && !(el.tagName === 'TR' && el.getAttribute('data-posto-codigo'))) el = el.parentNode;
        return el || null;
    })();
    if (linhaAvulso) {
        var chaveVinculo = 'data-av-seq-vinculado-' + tipo;
        if (!input.getAttribute(chaveVinculo)) {
            input.setAttribute(chaveVinculo, '1');
            input.addEventListener('input', function() { propagarSequenciaDesdeRow(linhaAvulso, tipo); });
            input.addEventListener('change', function() { propagarSequenciaDesdeRow(linhaAvulso, tipo); });
        }
    }
    try { salvarEstadoEtiquetasCorreios(); } catch (e) { }
    marcarComoNaoSalvo();
    return false;
}

function obterIndicesSplitCentralOrdenados(totalLinhas) {
    var indices = window.splitVisualIndices || [];
    var lista = [];
    var vistos = {};
    for (var i = 0; i < indices.length; i++) {
        var idx = parseInt(indices[i], 10);
        if (isNaN(idx) || idx < 0) continue;
        if (typeof totalLinhas === 'number' && totalLinhas > 0 && idx >= (totalLinhas - 1)) continue;
        if (vistos[idx]) continue;
        vistos[idx] = true;
        lista.push(idx);
    }
    lista.sort(function(a, b) { return a - b; });
    return lista;
}

function obterFaixasSplitCentral(totalLinhas) {
    var faixas = [];
    var indices = obterIndicesSplitCentralOrdenados(totalLinhas);
    var inicio = 0;
    if (!totalLinhas || totalLinhas < 1) return faixas;
    for (var i = 0; i < indices.length; i++) {
        faixas.push({ start: inicio, end: indices[i] });
        inicio = indices[i] + 1;
    }
    faixas.push({ start: inicio, end: totalLinhas - 1 });
    return faixas;
}

function obterFaixaDaLinhaCentral(rowIndex, totalLinhas) {
    var faixas = obterFaixasSplitCentral(totalLinhas);
    for (var i = 0; i < faixas.length; i++) {
        if (rowIndex >= faixas[i].start && rowIndex <= faixas[i].end) {
            return faixas[i];
        }
    }
    return null;
}

function obterLinhaPaiCampo(campo) {
    var linha = campo;
    while (linha && linha.tagName !== 'TR') {
        linha = linha.parentNode;
    }
    return linha && linha.tagName === 'TR' ? linha : null;
}

function obterIndiceLinhaLista(linhas, linha) {
    for (var i = 0; i < linhas.length; i++) {
        if (linhas[i] === linha) {
            return i;
        }
    }
    return -1;
}

function atualizarInputsTopoPorPlanilha() {
    var linhasCapital = obterLinhasGrupoLacres('CAPITAL');
    var linhasCentral = obterLinhasGrupoLacres('CENTRAL IIPR');
    var linhasRegionais = obterLinhasGrupoLacres('REGIONAIS');
    var topoCapital = document.getElementById('lacre_capital_input');
    var topoCentral = document.getElementById('lacre_central_input');
    var topoRegionais = document.getElementById('lacre_regionais_input');
    var inputCapital = linhasCapital.length ? obterInputLacreLinha(linhasCapital[0], 'iipr') : null;
    var inputCentral = linhasCentral.length ? obterInputLacreLinha(linhasCentral[0], 'iipr') : null;
    var inputRegionais = linhasRegionais.length ? obterInputLacreLinha(linhasRegionais[0], 'iipr') : null;

    if (topoCapital && inputCapital) topoCapital.value = inputCapital.value || '';
    if (topoCentral && inputCentral) topoCentral.value = inputCentral.value || '';
    if (topoRegionais && inputRegionais) topoRegionais.value = inputRegionais.value || '';
}

function aplicarSequenciaParPorGrupo(grupo, valorInicial) {
    var linhas = obterLinhasGrupoLacres(grupo);
    var atual = inteiroPositivoOuZero(valorInicial);
    if (!linhas.length) return;

    if (atual <= 0) {
        for (var z = 0; z < linhas.length; z++) {
            var limpaI = obterInputLacreLinha(linhas[z], 'iipr');
            var limpaC = obterInputLacreLinha(linhas[z], 'correios');
            if (limpaI) limpaI.value = '';
            if (limpaC) limpaC.value = '';
        }
        atualizarInputsTopoPorPlanilha();
        try { salvarEstadoEtiquetasCorreios(); } catch (e) { }
        marcarComoNaoSalvo();
        return;
    }

    for (var i = 0; i < linhas.length; i++) {
        var inputI = obterInputLacreLinha(linhas[i], 'iipr');
        var inputC = obterInputLacreLinha(linhas[i], 'correios');
        if (i === 0) {
            if (inputC && !inputLacreAvulso(inputC)) inputC.value = String(atual + 1);
            atual += 2;
            continue;
        }
        if (inputI && !inputLacreAvulso(inputI)) inputI.value = String(atual);
        if (inputC && !inputLacreAvulso(inputC)) inputC.value = String(atual + 1);
        atual += 2;
    }

    atualizarInputsTopoPorPlanilha();
    try { salvarEstadoEtiquetasCorreios(); } catch (e2) { }
    marcarComoNaoSalvo();
}

function obterFaixasCentralPlanilha() {
    var linhas = obterLinhasGrupoLacres('CENTRAL IIPR');
    var total = linhas.length;
    var faixas = [];
    var indices = obterIndicesSplitCentralOrdenados(total);
    var inicio = 0;
    if (!total) return faixas;
    for (var i = 0; i < indices.length; i++) {
        faixas.push({ start: inicio, end: indices[i] });
        inicio = indices[i] + 1;
    }
    faixas.push({ start: inicio, end: total - 1 });
    return faixas;
}

function aplicarSequenciaCentralNoBloco(indiceLinha, valorInicial) {
    var linhas = obterLinhasGrupoLacres('CENTRAL IIPR');
    var faixas = obterFaixasCentralPlanilha();
    var atual = inteiroPositivoOuZero(valorInicial);
    var faixa = null;
    var i;

    if (!linhas.length || indiceLinha < 0) return;

    for (i = 0; i < faixas.length; i++) {
        if (indiceLinha >= faixas[i].start && indiceLinha <= faixas[i].end) {
            faixa = faixas[i];
            break;
        }
    }
    if (!faixa) return;

    if (atual <= 0) {
        for (i = faixa.start; i <= faixa.end; i++) {
            var limpaI = obterInputLacreLinha(linhas[i], 'iipr');
            var limpaC = obterInputLacreLinha(linhas[i], 'correios');
            if (limpaI) limpaI.value = '';
            if (limpaC) limpaC.value = '';
        }
        atualizarInputsTopoPorPlanilha();
        try { salvarEstadoEtiquetasCorreios(); } catch (e) { }
        marcarComoNaoSalvo();
        return;
    }

    for (i = faixa.start; i <= faixa.end; i++) {
        var inputI = obterInputLacreLinha(linhas[i], 'iipr');
        if (i === faixa.start) {
            continue;
        }
        if (inputI && !inputLacreAvulso(inputI)) inputI.value = String(atual + (i - faixa.start));
    }

    var lacreCorreios = atual + (faixa.end - faixa.start) + 1;
    for (i = faixa.start; i <= faixa.end; i++) {
        var inputC = obterInputLacreLinha(linhas[i], 'correios');
        if (inputC && !inputLacreAvulso(inputC)) inputC.value = String(lacreCorreios);
    }

    atualizarInputsTopoPorPlanilha();
    try { salvarEstadoEtiquetasCorreios(); } catch (e2) { }
    marcarComoNaoSalvo();
}

function configurarAtribuicaoPlanilhaLacres() {
    var linhasCapital = obterLinhasGrupoLacres('CAPITAL');
    var linhasRegionais = obterLinhasGrupoLacres('REGIONAIS');
    var linhasCentral = obterLinhasGrupoLacres('CENTRAL IIPR');
    var tabelaCentral = obterTabelaGrupoLacres('CENTRAL IIPR');

    if (linhasCapital.length) {
        var inputCapital = obterInputLacreLinha(linhasCapital[0], 'iipr');
        if (inputCapital && inputCapital.getAttribute('data-planilha-vinculada') !== 'capital') {
            inputCapital.setAttribute('data-planilha-vinculada', 'capital');
            inputCapital.addEventListener('input', function() {
                if (inputLacreAvulso(this)) return;
                if (podeAplicarSequenciaDuranteDigitacao(this.value)) {
                    aplicarSequenciaParPorGrupo('CAPITAL', this.value);
                }
            });
            inputCapital.addEventListener('change', function() {
                if (inputLacreAvulso(this)) return;
                aplicarSequenciaParPorGrupo('CAPITAL', this.value);
            });
        }
    }

    if (linhasRegionais.length) {
        var inputRegionais = obterInputLacreLinha(linhasRegionais[0], 'iipr');
        if (inputRegionais && inputRegionais.getAttribute('data-planilha-vinculada') !== 'regionais') {
            inputRegionais.setAttribute('data-planilha-vinculada', 'regionais');
            inputRegionais.addEventListener('input', function() {
                if (inputLacreAvulso(this)) return;
                if (podeAplicarSequenciaDuranteDigitacao(this.value)) {
                    aplicarSequenciaParPorGrupo('REGIONAIS', this.value);
                }
            });
            inputRegionais.addEventListener('change', function() {
                if (inputLacreAvulso(this)) return;
                aplicarSequenciaParPorGrupo('REGIONAIS', this.value);
            });
        }
    }

    if (tabelaCentral && tabelaCentral.getAttribute('data-planilha-central-vinculada') !== '1') {
        var tratarEventoCentral = function(campo, permitirDuranteDigitacao) {
            var linhasAtuais;
            var linhaAtual;
            var indiceAtual;
            var faixaAtual;

            if (!campo || String(campo.getAttribute('data-tipo') || '') !== 'iipr') {
                return;
            }
            if (inputLacreAvulso(campo)) return;
            if (permitirDuranteDigitacao && !podeAplicarSequenciaDuranteDigitacao(campo.value)) {
                return;
            }

            linhasAtuais = obterLinhasGrupoLacres('CENTRAL IIPR');
            linhaAtual = obterLinhaPaiCampo(campo);
            indiceAtual = obterIndiceLinhaLista(linhasAtuais, linhaAtual);
            if (indiceAtual < 0) {
                return;
            }

            faixaAtual = obterFaixaDaLinhaCentral(indiceAtual, linhasAtuais.length);
            if (!faixaAtual || faixaAtual.start !== indiceAtual) {
                return;
            }

            aplicarSequenciaCentralNoBloco(indiceAtual, campo.value);
        };

        tabelaCentral.setAttribute('data-planilha-central-vinculada', '1');
        tabelaCentral.addEventListener('input', function(event) {
            tratarEventoCentral(event.target || event.srcElement, true);
        });
        tabelaCentral.addEventListener('change', function(event) {
            tratarEventoCentral(event.target || event.srcElement, false);
        });
    }

    // v1.1.11: vincular propagação a TODOS os inputs não-primeiros de cada grupo
    // O primeiro input de Capital e Regionais já propaga via aplicarSequenciaParPorGrupo acima.
    // Os demais inputs (postos 2, 3, 4...) precisam de listeners para chamar
    // propagarSequenciaDesdeRow, que já respeita o estado avulso internamente.
    (function vincularInputsNaoPrimeiros() {
        var grupos = ['CAPITAL', 'REGIONAIS', 'CENTRAL IIPR'];
        var chaveGuarda = 'data-seq-row-vinculado';
        for (var g = 0; g < grupos.length; g++) {
            var linhasGrupo = obterLinhasGrupoLacres(grupos[g]);
            // Capital e Regionais: pular índice 0 (já vinculado acima)
            // Central: vincular todos (tratarEventoCentral só propaga o 1º de cada bloco)
            var inicioIdx = (grupos[g] === 'CENTRAL IIPR') ? 0 : 1;
            for (var k = inicioIdx; k < linhasGrupo.length; k++) {
                (function(linhaK) {
                    var inpI = obterInputLacreLinha(linhaK, 'iipr');
                    var inpC = obterInputLacreLinha(linhaK, 'correios');
                    if (inpI && inpI.getAttribute(chaveGuarda) !== '1') {
                        inpI.setAttribute(chaveGuarda, '1');
                        inpI.addEventListener('input', function() {
                            propagarSequenciaDesdeRow(linhaK, 'iipr');
                        });
                        inpI.addEventListener('change', function() {
                            propagarSequenciaDesdeRow(linhaK, 'iipr');
                        });
                    }
                    if (inpC && inpC.getAttribute(chaveGuarda) !== '1') {
                        inpC.setAttribute(chaveGuarda, '1');
                        inpC.addEventListener('input', function() {
                            propagarSequenciaDesdeRow(linhaK, 'correios');
                        });
                        inpC.addEventListener('change', function() {
                            propagarSequenciaDesdeRow(linhaK, 'correios');
                        });
                    }
                })(linhasGrupo[k]);
            }
        }
    })();

    atualizarInputsTopoPorPlanilha();
}

// v9.25.23: Ativa recálculo automático apenas para o grupo solicitado
// Esta função ativa a flag que dispara a lógica v9.13.0 apenas no grupo alvo:
// - CAPITAL: lacre_iipr=N, lacre_correios=N+1, incremento +2 (N, N+2, N+4...)
// - CENTRAL: lacre_iipr sequencial +1 (5,6,7...), lacre_correios = último+1 para TODOS
// - REGIONAIS: lacre_iipr=N, lacre_correios=N+1, incremento +2 (igual Capital)
function ativarRecalculoLacres(grupo) {
    var recalEl = document.getElementById('recalculo_por_lacre');
    var grupoEl = document.getElementById('recalculo_grupo');
    var camposPorGrupo = {
        'CAPITAL': 'lacre_capital_input',
        'CENTRAL IIPR': 'lacre_central_input',
        'REGIONAIS': 'lacre_regionais_input'
    };
    var nomesPorGrupo = {
        'CAPITAL': 'Capital',
        'CENTRAL IIPR': 'Central IIPR',
        'REGIONAIS': 'Regionais'
    };
    var campoId = camposPorGrupo[grupo] || '';
    var campo = campoId ? document.getElementById(campoId) : null;
    var valor = campo ? parseInt(campo.value, 10) : 0;

    if (!campo || campo.value === '' || isNaN(valor) || valor < 1) {
        alert('Informe um lacre inicial válido para ' + (nomesPorGrupo[grupo] || 'o grupo selecionado') + '.');
        if (campo) {
            campo.focus();
        }
        return false;
    }

    if (recalEl) {
        recalEl.value = '1';
    }
    if (grupoEl) {
        grupoEl.value = grupo;
    }
    return true;
}

function desativarRecalculoLacres() {
    var recalEl = document.getElementById('recalculo_por_lacre');
    var grupoEl = document.getElementById('recalculo_grupo');
    if (recalEl) {
        recalEl.value = '0';
    }
    if (grupoEl) {
        grupoEl.value = '';
    }
    return true;
}

// v9.25.23: Limpar lacres apenas do grupo que sera recalculado
function limparLacresPorRecalculo() {
    var recalEl = document.getElementById('recalculo_por_lacre');
    var grupoEl = document.getElementById('recalculo_grupo');
    var grupo = grupoEl ? String(grupoEl.value || '') : '';
    
    if (!recalEl || String(recalEl.value) !== '1' || grupo === '') {
        return;
    }

    if (grupo === 'CAPITAL') {
        var tabelaCap = document.getElementById('tabela-capital');
        if (tabelaCap) {
            var inputsCap = tabelaCap.querySelectorAll('input[name^="lacre_iipr["], input[name^="lacre_correios["]');
            for (var i = 0; i < inputsCap.length; i++) {
                inputsCap[i].value = '';
            }
        }
    }

    if (grupo === 'CENTRAL IIPR') {
        var tabelaCentral = document.getElementById('tabela-central-iipr');
        if (!tabelaCentral) {
            tabelaCentral = document.getElementById('tblCentralIIPR');
        }
        if (tabelaCentral) {
            var inputsCentral = tabelaCentral.querySelectorAll('input[name^="lacre_iipr["], input[name^="lacre_correios["]');
            for (var i = 0; i < inputsCentral.length; i++) {
                inputsCentral[i].value = '';
            }
        }
    }

    if (grupo === 'REGIONAIS') {
        var tabelaRegionais = document.getElementById('tabela-regionais');
        if (tabelaRegionais) {
            var inputsRegionais = tabelaRegionais.querySelectorAll('input[name^="lacre_iipr["], input[name^="lacre_correios["]');
            for (var i = 0; i < inputsRegionais.length; i++) {
                inputsRegionais[i].value = '';
            }
        }
    }
}

// Funcao especial para limpar etiquetas da Central IIPR (incluindo campos readonly do split)
function limparEtiquetasCentral() {
    if (!confirm('Deseja realmente apagar todas as etiquetas da Central IIPR?\n\nEsta acao nao pode ser desfeita.')) {
        return;
    }
    
    // Tentar encontrar a tabela por ambos os ids possiveis
    var tabela = document.getElementById('tabela-central-iipr');
    if (!tabela) {
        tabela = document.getElementById('tblCentralIIPR');
    }
    if (!tabela) {
        alert('Tabela da Central IIPR nao encontrada');
        return;
    }
    
    // Limpar todos os campos de etiqueta (inclusive readonly)
    var inputs = tabela.querySelectorAll('input[name^="etiqueta_correios["]');
    for (var i = 0; i < inputs.length; i++) {
        inputs[i].value = '';
        inputs[i].removeAttribute('readonly');
        inputs[i].className = 'etiqueta-barras etiqueta-central';
    }

    // Resetar estado do split (variaveis JS e classes visuais)
    try {
        window.splitCentralAtivo = false;
        splitIndexCentral = null;
        window.splitVisualIndices = [];

        // Remover classes visuais das linhas da central
        var linhasCentral = document.querySelectorAll('tr.linha-central');
        for (var r = 0; r < linhasCentral.length; r++) {
            removerClasse(linhasCentral[r], 'split-central-separador');
            for (var g = 1; g <= 5; g++) {
                removerClasse(linhasCentral[r], 'split-central-grupo' + g);
            }
        }

        // Resetar estilo dos botões de split
        var allSplitBtns = document.querySelectorAll('button[onclick*="definirSplitAqui"]');
        for (var b = 0; b < allSplitBtns.length; b++) {
            allSplitBtns[b].style.background = '';
            allSplitBtns[b].textContent = 'Split aqui';
            allSplitBtns[b].style.border = '';
        }

        // Limpar localStorage relacionado a CENTRAL IIPR (se houver id_despacho)
        var idDespInput = document.getElementById('id_despacho');
        var idDespacho = idDespInput ? idDespInput.value : '';
        try {
            for (var k = localStorage.length - 1; k >= 0; k--) {
                var key = localStorage.key(k);
                if (!key) continue;
                if (key.indexOf('oficioCorreios:' + idDespacho + ':') === 0) {
                    // Opcionalmente, filtrar por regional da central se necessario
                    localStorage.removeItem(key);
                }
            }
        } catch (e) { /* ignore */ }
    } catch (e) {
        // ignore erros de limpeza visual
    }

    // Marcar como nao salvo
    marcarComoNaoSalvo();

    alert('Etiquetas da Central IIPR foram limpas com sucesso!');
}

// v9.21.1: Função para atribuir lacres sequencialmente (ÚNICA - NÃO REPETE LACRES)
var ULTIMO_LACRE_IIPR = <?php echo (int)$ultimo_lacre_iipr; ?>;
var ULTIMO_LACRE_CORREIOS = <?php echo (int)$ultimo_lacre_correios; ?>;

function avisarLacreReutilizado(valor) {
    var n = parseInt(valor, 10);
    if (isNaN(n) || n < 1) return true;
    if (ULTIMO_LACRE_IIPR > 0 && n <= ULTIMO_LACRE_IIPR) {
        return confirm(
            'ATENCAO: o lacre ' + n + ' e MENOR ou IGUAL ao ultimo lacre IIPR ja utilizado (' + ULTIMO_LACRE_IIPR + ').\n\n' +
            'Isso pode significar reuso de numeracao. Deseja continuar mesmo assim?'
        );
    }
    return true;
}

function atribuirLacresSequencial() {
    var lacreInicial = prompt('Digite o número do primeiro lacre IIPR:\n(Os lacres Correios serão numerados automaticamente a partir do mesmo valor)', '');
    
    if (!lacreInicial || lacreInicial.trim() === '') {
        return; // Cancelou ou não preencheu
    }
    
    var numeroInicial = parseInt(lacreInicial.trim());
    if (isNaN(numeroInicial) || numeroInicial < 1) {
        alert('Número inválido! Digite um número inteiro positivo.');
        return;
    }

    if (!avisarLacreReutilizado(numeroInicial)) {
        return;
    }
    
    var confirmacao = confirm(
        'Isso irá atribuir lacres sequenciais a partir de ' + numeroInicial + ' para:\n\n' +
        '• Lacres IIPR (CAPITAL, CENTRAL IIPR, REGIONAIS)\n' +
        '• Lacres Correios (mesma numeração)\n\n' +
        'Deseja continuar?'
    );
    
    if (!confirmacao) {
        return;
    }
    
    var lacreAtual = numeroInicial;
    var totalAtribuidos = 0;
    
    // Buscar todas as tabelas (exceto POUPA TEMPO)
    var tabelas = document.querySelectorAll('table[data-grupo]');
    
    for (var t = 0; t < tabelas.length; t++) {
        var tabela = tabelas[t];
        var grupo = tabela.getAttribute('data-grupo');
        
        // Pular POUPA TEMPO
        if (grupo === 'POUPA TEMPO') {
            continue;
        }
        
        // Buscar todas as linhas com posto-codigo
        var linhas = tabela.querySelectorAll('tr[data-posto-codigo]');
        
        for (var i = 0; i < linhas.length; i++) {
            var linha = linhas[i];
            
            // Lacre IIPR
            var inputIIPR = linha.querySelector('input[name^="lacre_iipr"]');
            if (inputIIPR && !inputIIPR.disabled && !inputIIPR.readOnly) {
                inputIIPR.value = lacreAtual;
                totalAtribuidos++;
            }
            
            // Lacre Correios (mesmo número)
            var inputCorreios = linha.querySelector('input[name^="lacre_correios"]');
            if (inputCorreios && !inputCorreios.disabled && !inputCorreios.readOnly) {
                inputCorreios.value = lacreAtual;
            }
            
            lacreAtual++;
        }
    }
    
    alert('✅ Atribuição concluída!\n\n' +
          'Total de lacres atribuídos: ' + totalAtribuidos + '\n' +
          'Faixa utilizada: ' + numeroInicial + ' a ' + (lacreAtual - 1) + '\n\n' +
          'Próximo lacre disponível: ' + lacreAtual);
    
    // Marcar como não salvo
    marcarComoNaoSalvo();
}

// VERSAO 3: Variavel global para controlar estado de salvamento
var botoesGravar = [];

// Funcao auxiliar para adicionar classe (compativel com navegadores antigos)
function adicionarClasse(el, classe) {
    if (!el) return;
    if (el.className.indexOf(classe) < 0) {
        el.className = el.className + ' ' + classe;
    }
}

// Funcao auxiliar para remover classe (compativel com navegadores antigos)
function removerClasse(el, classe) {
    if (!el) return;
    var regex = new RegExp('\\s*' + classe, 'g');
    el.className = el.className.replace(regex, '');
}

// Funcao para marcar formulario como nao salvo (ativar pulsacao)
function marcarComoNaoSalvo() {
    for (var i = 0; i < botoesGravar.length; i++) {
        removerClasse(botoesGravar[i], 'btn-salvo');
        adicionarClasse(botoesGravar[i], 'btn-pulsando');
    }
}

// Funcao para marcar formulario como salvo (parar pulsacao)
// Chamada apos salvamento bem-sucedido antes de print ou redirect
function marcarComoSalvo() {
    for (var i = 0; i < botoesGravar.length; i++) {
        removerClasse(botoesGravar[i], 'btn-pulsando');
        adicionarClasse(botoesGravar[i], 'btn-salvo');
    }
}

// v8.11.2: Preencher lacres automaticamente ao adicionar posto manualmente
// Essa funcao calcula o lacre da nova linha com base no ultimo lacre do grupo
function preencherLacresParaPostoManual(event) {
    try {
        var form = document.querySelector('.form-adicionar');
        if (!form) return;

        // Obter valores do formulario
        var tipoEl = form.querySelector('select[name="tipo_posto"]');
        var lacreManualIIPR = form.querySelector('input[name="lacre_iipr_manual"]');
        var lacreManualCorr = form.querySelector('input[name="lacre_correios_manual"]');
        if (!tipoEl || !lacreManualIIPR || !lacreManualCorr) return;

        var grupo = String(tipoEl.value || '').trim();
        var tabelaId = 'tabela-' + grupo.toLowerCase().replace(/ /g, '-');
        var tabela = document.getElementById(tabelaId);

        // Função util: encontra ultimo valor numerico não vazio em uma lista de inputs
        var encontrarUltimo = function(inputs) {
            var ultimo = null;
            for (var i = 0; i < inputs.length; i++) {
                if (inputLacreAvulso(inputs[i])) {
                    continue;
                }
                var v = String(inputs[i].value || '').trim();
                if (v !== '') {
                    var n = inteiroPositivoOuZero(v);
                    if (!isNaN(n)) ultimo = n;
                }
            }
            return ultimo;
        };

        var novoI = null;
        var novoC = null;

        if (tabela) {
            // procurar todos os inputs lacre IIPR e Correios dentro da tabela
            var inputsI = tabela.querySelectorAll('input[name^="lacre_iipr"], input[data-tipo="iipr"], input.lacre');
            var inputsC = tabela.querySelectorAll('input[name^="lacre_correios"], input[data-tipo="correios"], input.lacre');

            var ultimoI = encontrarUltimo(inputsI);
            var ultimoC = encontrarUltimo(inputsC);

            if (ultimoI !== null) {
                // Grupo CENTRAL IIPR: lacre IIPR incrementa +1
                if (grupo === 'CENTRAL IIPR') {
                    novoI = ultimoI + 1;
                } else {
                    // CAPITAL e REGIONAIS: incremento +2
                    novoI = ultimoI + 2;
                }
            }

            if (ultimoC !== null) {
                // Para lacre Correios, manter incremento de 2 para CAPITAL/REGIONAIS
                if (grupo === 'CENTRAL IIPR') {
                    // CENTRAL: manter o mesmo comportamento de correios (usar ultimo se existir)
                    novoC = ultimoC;
                } else {
                    novoC = ultimoC + 2;
                }
            }
        }

        // Se nao encontrou ultimo, usar lacre inicial do formulario (caso exista)
        if (novoI === null) {
            try {
                if (grupo === 'CAPITAL') {
                    var base = document.getElementById('lacre_capital_input');
                    if (base && String(base.value || '').trim() !== '') {
                        var b = parseInt(String(base.value), 10);
                        if (!isNaN(b)) {
                            novoI = b;
                        }
                    }
                } else if (grupo === 'CENTRAL IIPR') {
                    var base = document.getElementById('lacre_central_input');
                    if (base && String(base.value || '').trim() !== '') {
                        var b = parseInt(String(base.value), 10);
                        if (!isNaN(b)) {
                            novoI = b;
                        }
                    }
                } else {
                    var base = document.getElementById('lacre_regionais_input');
                    if (base && String(base.value || '').trim() !== '') {
                        var b = parseInt(String(base.value), 10);
                        if (!isNaN(b)) {
                            novoI = b;
                        }
                    }
                }
            } catch (e) { /* ignore */ }
        }

        if (novoC === null) {
            try {
                if (grupo === 'CAPITAL') {
                    var baseC = document.getElementById('lacre_capital_input');
                    if (baseC && String(baseC.value || '').trim() !== '') {
                        var bc = parseInt(String(baseC.value), 10);
                        if (!isNaN(bc)) novoC = bc + 1;
                    }
                } else if (grupo === 'CENTRAL IIPR') {
                    // CENTRAL: usar lacre correios calculado pelo servidor por split; se nao houver, usar lacre_central+1
                    var baseC = document.getElementById('lacre_central_input');
                    if (baseC && String(baseC.value || '').trim() !== '') {
                        var bc = parseInt(String(baseC.value), 10);
                        if (!isNaN(bc)) novoC = bc + 1;
                    }
                } else {
                    var baseC = document.getElementById('lacre_regionais_input');
                    if (baseC && String(baseC.value || '').trim() !== '') {
                        var bc = parseInt(String(baseC.value), 10);
                        if (!isNaN(bc)) novoC = bc + 1;
                    }
                }
            } catch (e) { /* ignore */ }
        }

        // Finalmente, atribuir os valores calculados ao form (sem alterar outras linhas)
        if (novoI !== null) {
            lacreManualIIPR.value = String(novoI);
        }
        if (novoC !== null) {
            lacreManualCorr.value = String(novoC);
        }
    } catch (e) {
        // em caso de erro, nao bloquear envio
    }
}

function obterPassoSequenciaGrupo(grupo) {
    return grupo === 'CENTRAL IIPR' ? 1 : 2;
}

function propagarSequenciaLinhaInserida(linhaOrigem, tipoOrigem) {
    if (!linhaOrigem || linhaOrigem.getAttribute('data-linha-inserida') !== '1') {
        return;
    }

    var corpo = linhaOrigem.parentNode;
    if (!corpo) {
        return;
    }

    var grupo = String(linhaOrigem.getAttribute('data-grupo') || '');
    var linhas = corpo.querySelectorAll('tr[data-posto-codigo]');
    if (!linhas || !linhas.length) {
        return;
    }

    var indiceOrigem = -1;
    for (var i = 0; i < linhas.length; i++) {
        if (linhas[i] === linhaOrigem) {
            indiceOrigem = i;
            break;
        }
    }
    if (indiceOrigem < 0) {
        return;
    }

    var inputIiprOrigem = linhaOrigem.querySelector('input[data-tipo="iipr"]');
    var inputCorreiosOrigem = linhaOrigem.querySelector('input[data-tipo="correios"]');
    if (!inputIiprOrigem && !inputCorreiosOrigem) {
        return;
    }

    var valorIipr = inputIiprOrigem ? inteiroPositivoOuZero(inputIiprOrigem.value || '') : NaN;
    var valorCorreios = inputCorreiosOrigem ? inteiroPositivoOuZero(inputCorreiosOrigem.value || '') : NaN;

    if ((tipoOrigem === 'iipr' && inputLacreAvulso(inputIiprOrigem)) || (tipoOrigem === 'correios' && inputLacreAvulso(inputCorreiosOrigem))) {
        return;
    }

    if (tipoOrigem === 'correios' && isNaN(valorIipr) && !isNaN(valorCorreios)) {
        valorIipr = valorCorreios - 1;
        if (inputIiprOrigem && !isNaN(valorIipr) && valorIipr >= 0) {
            inputIiprOrigem.value = String(valorIipr);
        }
    }

    if (isNaN(valorIipr)) {
        return;
    }

    if (inputCorreiosOrigem && !inputLacreAvulso(inputCorreiosOrigem)) {
        valorCorreios = valorIipr + 1;
        inputCorreiosOrigem.value = String(valorCorreios);
    }

    var passo = obterPassoSequenciaGrupo(grupo);
    var proximoIipr = valorIipr + passo;
    var proximoCorreios = valorIipr + 1 + passo;

    for (var j = indiceOrigem + 1; j < linhas.length; j++) {
        var linha = linhas[j];
        if (!linha || (linha.style && linha.style.display === 'none')) {
            continue;
        }

        var inputIipr = linha.querySelector('input[data-tipo="iipr"]');
        var inputCorreios = linha.querySelector('input[data-tipo="correios"]');
        if (inputIipr && !inputLacreAvulso(inputIipr)) {
            inputIipr.value = String(proximoIipr);
        }
        if (inputCorreios && !inputLacreAvulso(inputCorreios)) {
            inputCorreios.value = String(proximoCorreios);
        }

        proximoIipr += passo;
        proximoCorreios += passo;
    }

    try {
        salvarEstadoEtiquetasCorreios();
    } catch (e) {
    }
    marcarComoNaoSalvo();
}

function vincularSequenciaLinhaInserida() {
    var linhasInseridas = document.querySelectorAll('tr[data-linha-inserida="1"]');
    for (var i = 0; i < linhasInseridas.length; i++) {
        (function(linha) {
            var inputIipr = linha.querySelector('input[data-tipo="iipr"]');
            var inputCorreios = linha.querySelector('input[data-tipo="correios"]');

            if (inputIipr && !inputIipr.getAttribute('data-sequencia-vinculada')) {
                inputIipr.setAttribute('data-sequencia-vinculada', '1');
                inputIipr.addEventListener('input', function() {
                    propagarSequenciaLinhaInserida(linha, 'iipr');
                });
                inputIipr.addEventListener('change', function() {
                    propagarSequenciaLinhaInserida(linha, 'iipr');
                });
            }

            if (inputCorreios && !inputCorreios.getAttribute('data-sequencia-vinculada')) {
                inputCorreios.setAttribute('data-sequencia-vinculada', '1');
                inputCorreios.addEventListener('input', function() {
                    propagarSequenciaLinhaInserida(linha, 'correios');
                });
                inputCorreios.addEventListener('change', function() {
                    propagarSequenciaLinhaInserida(linha, 'correios');
                });
            }
        })(linhasInseridas[i]);
    }
}

// Inicializar monitoramento de alteracoes nos inputs
function inicializarMonitoramentoAlteracoes() {
    // v8.11.1: Injetar estilos de destaque para splits (se ainda nao existe)
    if (typeof document !== 'undefined' && document.getElementById && !document.getElementById('split-central-styles')) {
        try {
            var styleEl = document.createElement('style');
            styleEl.id = 'split-central-styles';
            styleEl.type = 'text/css';
            var css = '.split-central-grupo1 { background-color: #fffbe6; } ' +
                      '.split-central-grupo2 { background-color: #e8f7ff; } ' +
                      '.split-central-grupo3 { background-color: #fff3e0; } ' +
                      '.split-central-grupo4 { background-color: #f3ffe8; } ' +
                      '.split-central-grupo5 { background-color: #f0f4ff; }';
            if (styleEl.styleSheet) { styleEl.styleSheet.cssText = css; } else { styleEl.appendChild(document.createTextNode(css)); }
            var heads = document.getElementsByTagName('head');
            if (heads && heads.length) { heads[0].appendChild(styleEl); }
        } catch (e) { /* ignore */ }
    }

    // Inicializar array de indices visuais (v8.11.1)
    window.splitVisualIndices = window.splitVisualIndices || [];

    // v9.25.24: restaurar automaticamente no mesmo contexto da URL atual
    try {
        restaurarEstadoEtiquetasCorreios();
    } catch (e) { /* ignore */ }
    
    // Encontrar todos os botoes de gravar
    var btns = document.querySelectorAll('button[onclick*="gravar"], button[onclick*="Gravar"]');
    for (var i = 0; i < btns.length; i++) {
        botoesGravar.push(btns[i]);
    }
    
    // Monitorar todos os inputs de lacre e etiqueta
    var inputs = document.querySelectorAll('input.lacre, input.etiqueta-barras, input[name^="lacre_"], input[name^="etiqueta_"]');
    for (var i = 0; i < inputs.length; i++) {
        inputs[i].addEventListener('input', function() {
            marcarComoNaoSalvo();
            try { salvarEstadoEtiquetasCorreios(); } catch (e) { /* ignore */ }
        });
        inputs[i].addEventListener('change', function() {
            marcarComoNaoSalvo();
            try { salvarEstadoEtiquetasCorreios(); } catch (e) { /* ignore */ }
        });
    }

    // v9.25.23: Alterar um lacre inicial nao deve manter uma selecao antiga de recálculo.
    // O grupo passa a ser definido apenas pelo clique no botao correspondente.
    try {
        var lacreCap = document.getElementById('lacre_capital_input');
        var lacreCentral = document.getElementById('lacre_central_input');
        var lacreRegionais = document.getElementById('lacre_regionais_input');
        var recalEl = document.getElementById('recalculo_por_lacre');
        var recalGrupoEl = document.getElementById('recalculo_grupo');
        var limparSelecaoRecalculo = function() {
            try {
                if (recalEl) recalEl.value = '0';
                if (recalGrupoEl) recalGrupoEl.value = '';
            } catch (e) {}
        };
        if (lacreCap) { lacreCap.addEventListener('input', limparSelecaoRecalculo); lacreCap.addEventListener('change', limparSelecaoRecalculo); }
        if (lacreCentral) { lacreCentral.addEventListener('input', limparSelecaoRecalculo); lacreCentral.addEventListener('change', limparSelecaoRecalculo); }
        if (lacreRegionais) { lacreRegionais.addEventListener('input', limparSelecaoRecalculo); lacreRegionais.addEventListener('change', limparSelecaoRecalculo); }

        var avisoChave = 'aviso_lacres_iniciais_v1';
        var avisoLacres = function() {
            try {
                if (window.sessionStorage && window.sessionStorage.getItem(avisoChave) === '1') {
                    return;
                }
            } catch (e) { /* ignore */ }

            alert('Antes de comecar a apropriar os lacres, verifique os postos que devem ser excluidos ou incluidos.');
            try {
                if (window.sessionStorage) {
                    window.sessionStorage.setItem(avisoChave, '1');
                } else if (window.localStorage) {
                    window.localStorage.setItem(avisoChave, '1');
                }
            } catch (e2) { /* ignore */ }
        };

        if (lacreCap) { lacreCap.addEventListener('focus', avisoLacres); lacreCap.addEventListener('click', avisoLacres); }
        if (lacreCentral) { lacreCentral.addEventListener('focus', avisoLacres); lacreCentral.addEventListener('click', avisoLacres); }
        if (lacreRegionais) { lacreRegionais.addEventListener('focus', avisoLacres); lacreRegionais.addEventListener('click', avisoLacres); }

        // Aviso de reuso: quando o lacre inicial e <= ao ultimo ja utilizado
        var avisarReusoNoBlur = function(ev) {
            try {
                var el = ev.target || ev.srcElement;
                if (el && el.value) { avisarLacreReutilizado(el.value); }
            } catch (e) { /* ignore */ }
        };
        if (lacreCap) { lacreCap.addEventListener('blur', avisarReusoNoBlur); }
        if (lacreCentral) { lacreCentral.addEventListener('blur', avisarReusoNoBlur); }
        if (lacreRegionais) { lacreRegionais.addEventListener('blur', avisarReusoNoBlur); }
    } catch (e) { /* ignore */ }

    // Interceptar envio do formulario 'Adicionar Posto Manualmente' para
    // preencher automaticamente os lacres da nova linha sem tocar nas existentes
    try {
        var formAdicionar = document.querySelector('.form-adicionar');
        if (formAdicionar) {
            formAdicionar.addEventListener('submit', function(e) {
                preencherLacresParaPostoManual(e);
                // nao prevenir submit; apenas garantir que os campos estao preenchidos
            });
        }
    } catch (er) { /* ignore */ }

    function marcarRestauracaoAposPosto() {
        try {
            var idEl = document.getElementById('id_despacho');
            var id = idEl ? idEl.value : 'sem';
            window.localStorage.setItem('restaurar_lacres_pos_posto:' + id, '1');
        } catch (e) { /* ignore */ }
    }

    function restaurarSeNecessario() {
        try {
            var idEl = document.getElementById('id_despacho');
            var id = idEl ? idEl.value : 'sem';
            var chave = 'restaurar_lacres_pos_posto:' + id;
            if (window.localStorage.getItem(chave) === '1') {
                if (typeof restaurarEstadoEtiquetasCorreios === 'function') {
                    restaurarEstadoEtiquetasCorreios();
                }
                window.localStorage.removeItem(chave);
            }
        } catch (e) { /* ignore */ }
    }

    try {
        var formCadastro = document.querySelector('.form-cadastro');
        if (formCadastro) {
            formCadastro.addEventListener('submit', function() {
                if (typeof salvarEstadoEtiquetasCorreiosForcado === 'function') {
                    salvarEstadoEtiquetasCorreiosForcado();
                }
                if (typeof salvarEstadoTemporarioLacres === 'function') {
                    salvarEstadoTemporarioLacres();
                }
                if (typeof anexarSnapshotAoForm === 'function') {
                    anexarSnapshotAoForm(formCadastro);
                }
                marcarRestauracaoAposPosto();
            });
        }
    } catch (eCad) { /* ignore */ }

    try {
        var formModalInserir = document.querySelector('#modal-inserir .modal-form');
        if (formModalInserir) {
            formModalInserir.addEventListener('submit', function() {
                if (typeof salvarEstadoEtiquetasCorreiosForcado === 'function') {
                    salvarEstadoEtiquetasCorreiosForcado();
                }
                if (typeof salvarEstadoTemporarioLacres === 'function') {
                    salvarEstadoTemporarioLacres();
                }
                if (typeof anexarSnapshotAoForm === 'function') {
                    anexarSnapshotAoForm(formModalInserir);
                }
                marcarRestauracaoAposPosto();
            });
        }
    } catch (eModal) { /* ignore */ }

    try {
        if (formAdicionar) {
            formAdicionar.addEventListener('submit', function() {
                if (typeof salvarEstadoEtiquetasCorreiosForcado === 'function') {
                    salvarEstadoEtiquetasCorreiosForcado();
                }
                if (typeof salvarEstadoTemporarioLacres === 'function') {
                    salvarEstadoTemporarioLacres();
                }
                if (typeof anexarSnapshotAoForm === 'function') {
                    anexarSnapshotAoForm(formAdicionar);
                }
                marcarRestauracaoAposPosto();
            });
        }
    } catch (eAdd) { /* ignore */ }

    restaurarSeNecessario();
    if (typeof restaurarEstadoTemporarioLacres === 'function') {
        restaurarEstadoTemporarioLacres();
    }

    try {
        window.addEventListener('beforeunload', function() {
            salvarEstadoEtiquetasCorreiosForcado();
        });
        window.addEventListener('pagehide', function() {
            salvarEstadoEtiquetasCorreiosForcado();
        });
    } catch (eSave) { /* ignore */ }

    try {
        configurarAtribuicaoPlanilhaLacres();
    } catch (ePlanilha) { /* ignore */ }

    try {
        vincularSequenciaLinhaInserida();
    } catch (eSequencia) { /* ignore */ }

    try {
        var botoesAvulso = document.querySelectorAll('button.btn-lacre-avulso');
        for (var av = 0; av < botoesAvulso.length; av++) {
            if (botoesAvulso[av].getAttribute('data-avulso-vinculado') === '1') continue;
            botoesAvulso[av].setAttribute('data-avulso-vinculado', '1');
            botoesAvulso[av].addEventListener('click', function(evento) {
                if (evento && evento.preventDefault) evento.preventDefault();
                alternarLacreAvulso(this);
            });
        }
    } catch (eAvulso) { /* ignore */ }
    
    // VERSAO 3: Iniciar SEM pulsacao (so pulsa quando ha mudanca)
    // Pagina recarrega apos salvar, entao comeca sempre sem pulsacao
}

// Chamar inicializacao quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarMonitoramentoAlteracoes);
} else {
    inicializarMonitoramentoAlteracoes();
}

(function() {
    var overlay = document.getElementById('overlay-processando-global');
    var overlayTexto = document.getElementById('overlay-processando-texto');

    function exibirProcessando(texto) {
        if (!overlay) return;
        if (overlayTexto) {
            overlayTexto.textContent = texto || 'Processando...';
        }
        overlay.className = 'overlay-processando-global ativo';
        overlay.setAttribute('aria-hidden', 'false');
    }

    function ocultarProcessando() {
        if (!overlay) return;
        overlay.className = 'overlay-processando-global';
        overlay.setAttribute('aria-hidden', 'true');
    }

    window.exibirProcessandoGlobal = exibirProcessando;
    window.ocultarProcessandoGlobal = ocultarProcessando;

    document.addEventListener('submit', function(evento) {
        var form = evento.target;
        if (!form || form.getAttribute('target') === '_blank' || form.getAttribute('data-sem-processando') === '1') {
            return;
        }
        exibirProcessando('Processando...');
    }, true);

    window.addEventListener('beforeunload', function() {
        exibirProcessando('Processando...');
    });
})();

// ============================================================================
// v8.14.7: SISTEMA DE SNAPSHOT/AUTO-SAVE CONTÍNUO
// ============================================================================
// ============================================================================
// v9.8.0: Sistema de Snapshot REMOVIDO
// Causava valores antigos nos inputs de lacres
// ============================================================================

// Funcao para preparar e imprimir, garantindo que valores do split sejam preservados
function prepararEImprimir() {
    // v8.14.9.1: Recolher painel "Análise de Expedição" antes de imprimir
    // (evita página em branco quando painel está expandido)
    var painel = document.getElementById('painel-analise');
    if (painel && painel.className.indexOf('collapsed') === -1) {
        painel.className = painel.className + ' collapsed';
        localStorage.setItem('painelAnaliseCollapsed', 'true');
    }
    
    // Sincronizar valores dos inputs antes de imprimir
    sincronizarValoresSplit();
    
    // Aguardar um momento para garantir que o DOM foi atualizado
    setTimeout(function() {
        // Atualizar document.title para que o diálogo do SO sugira o nome correto do PDF
        var tituloEl = document.querySelector('title');
        if (tituloEl && tituloEl.textContent && tituloEl.textContent.indexOf('Ofício Lacres') === 0) {
            // título ainda genérico — tenta construir a partir do campo de data
            var dtEl = document.querySelector('[name="datas[]"]') || document.querySelector('input[name^="datas"]');
            if (dtEl && dtEl.value) {
                document.title = 'Oficio_Lacres_' + dtEl.value.replace(/[\/\-]/g,'');
            }
        }
        window.print();
    }, 100);
}

// Funcao para sincronizar valores do split da Central IIPR
function sincronizarValoresSplit() {
    var tabela = document.getElementById('tabela-central-iipr');
    if (!tabela) {
        tabela = document.getElementById('tblCentralIIPR');
    }
    if (!tabela) return;

    var rows = tabela.querySelectorAll('tbody tr');
    if (!rows.length) rows = tabela.querySelectorAll('tr:not(:first-child)');
    if (!rows.length) return;

    var hidden = document.getElementById('central_split_indices');
    var partes = hidden && hidden.value ? String(hidden.value).split(',') : [];
    var indices = [];
    var vistos = {};
    for (var p = 0; p < partes.length; p++) {
        var idxSplit = parseInt(partes[p], 10);
        if (isNaN(idxSplit) || idxSplit < 0 || idxSplit >= (rows.length - 1) || vistos[idxSplit]) continue;
        vistos[idxSplit] = true;
        indices.push(idxSplit);
    }
    indices.sort(function(a, b) { return a - b; });
    if (!indices.length) return;
    
    // Encontrar indices das colunas
    var ths = tabela.querySelectorAll('thead th, tr:first-child th');
    var idxLacre = -1, idxEtiqueta = -1;
    for (var i = 0; i < ths.length; i++) {
        var texto = (ths[i].textContent || '').toLowerCase();
        if (texto.indexOf('lacre correios') >= 0) idxLacre = i;
        if (texto.indexOf('etiqueta correios') >= 0) idxEtiqueta = i;
    }

    var faixas = [];
    var inicio = 0;
    for (var j = 0; j < indices.length; j++) {
        faixas.push({ start: inicio, end: indices[j] });
        inicio = indices[j] + 1;
    }
    faixas.push({ start: inicio, end: rows.length - 1 });

    for (var f = 0; f < faixas.length; f++) {
        var faixa = faixas[f];
        var linhaLider = rows[faixa.start];
        var valorLacre = '';
        var valorEtiqueta = '';

        if (!linhaLider) continue;

        if (idxLacre >= 0 && linhaLider.children[idxLacre]) {
            var inpL = linhaLider.children[idxLacre].querySelector('input');
            if (inpL) valorLacre = inpL.value || '';
        }
        if (idxEtiqueta >= 0 && linhaLider.children[idxEtiqueta]) {
            var inpE = linhaLider.children[idxEtiqueta].querySelector('input');
            if (inpE) valorEtiqueta = inpE.value || '';
        }

        for (var r = faixa.start; r <= faixa.end; r++) {
            var row = rows[r];
            if (!row) continue;
            if (idxLacre >= 0 && row.children[idxLacre]) {
                var inp = row.children[idxLacre].querySelector('input');
                if (inp) {
                    inp.value = valorLacre;
                    inp.setAttribute('value', valorLacre);
                }
            }
            if (idxEtiqueta >= 0 && row.children[idxEtiqueta]) {
                var inp2 = row.children[idxEtiqueta].querySelector('input');
                if (inp2) {
                    inp2.value = valorEtiqueta;
                    inp2.setAttribute('value', valorEtiqueta);
                }
            }
        }
    }
}

function toggleSelecaoMultiplaLacres() {
    var container = document.getElementById('gradeLacresContainer');
    var btn = document.getElementById('btnSelecaoMultiplaLacres');
    var btnExc = document.getElementById('btnExcluirSelecionadosLacres');
    if (!container || !btn) return;
    var ativo = container.classList.contains('grade-modo-selecao');
    if (ativo) {
        container.classList.remove('grade-modo-selecao');
        btn.textContent = '☑ Seleção Múltipla';
        btn.classList.remove('ativo');
        if (btnExc) btnExc.classList.remove('visivel');
        var chks = container.querySelectorAll('.chk-selecao-lacres');
        for (var i = 0; i < chks.length; i++) chks[i].checked = false;
    } else {
        container.classList.add('grade-modo-selecao');
        btn.textContent = '✕ Cancelar Seleção';
        btn.classList.add('ativo');
        if (btnExc) btnExc.classList.add('visivel');
    }
}

function excluirSelecionadosLacres() {
    var container = document.getElementById('gradeLacresContainer');
    if (!container) return;
    var chks = container.querySelectorAll('.chk-selecao-lacres:checked');
    if (chks.length === 0) { alert('Nenhum posto selecionado.'); return; }
    var lista = [];
    var nomes = [];
    for (var i = 0; i < chks.length; i++) {
        lista.push({ codigo: chks[i].getAttribute('data-codigo'), grupo: chks[i].getAttribute('data-grupo'), nome: chks[i].getAttribute('data-nome') || chks[i].getAttribute('data-codigo') });
        nomes.push(chks[i].getAttribute('data-nome') || chks[i].getAttribute('data-codigo'));
    }
    if (!confirm('Excluir ' + lista.length + ' posto(s)?\n' + nomes.join('\n') + '\n\nConfirmar?')) return;
    var formExcluir = document.getElementById('formExcluirPosto');
    var inputMult = document.getElementById('excluir_multiplos');
    if (!formExcluir || !inputMult) return;
    try { if (typeof salvarSomenteEtiquetasCorreios === 'function') salvarSomenteEtiquetasCorreios(); } catch (e) {}
    try { if (typeof salvarEstadoEtiquetasCorreiosForcado === 'function') salvarEstadoEtiquetasCorreiosForcado(); } catch (e) {}
    try { if (typeof salvarEstadoTemporarioLacres === 'function') salvarEstadoTemporarioLacres(); } catch (e) {}
    try { if (typeof anexarSnapshotAoForm === 'function') anexarSnapshotAoForm(formExcluir); } catch (e) {}
    inputMult.value = JSON.stringify(lista);
    document.getElementById('excluir_posto_flag').value = '';
    document.getElementById('excluir_posto_regional_flag').value = '';
    formExcluir.submit();
}

function togglePostosPT(btn) {
    var tabelaPt = document.getElementById('tabela-poupa-tempo');
    if (!tabelaPt) return;
    var tbody = tabelaPt.querySelector('tbody');
    if (!tbody) return;
    var oculto = tbody.style.display === 'none';
    tbody.style.display = oculto ? '' : 'none';
    if (btn) btn.textContent = oculto ? 'Ocultar PT' : 'Mostrar PT';
}

</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Configuracao do zoom
    var body = document.body;
    var zoomInBtn = document.getElementById('zoom-in');
    var zoomOutBtn = document.getElementById('zoom-out');
    var currentZoomLevel = 0; // 0 = normal, 1 = grande, 2 = muito grande
    
    // Verificar se ha um nivel de zoom salvo no localStorage
    if (localStorage.getItem('zoomLevel')) {
        currentZoomLevel = parseInt(localStorage.getItem('zoomLevel'));
        applyZoom();
    }
    
    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function() {
            if (currentZoomLevel < 2) {
                currentZoomLevel++;
                applyZoom();
                localStorage.setItem('zoomLevel', currentZoomLevel);
            }
        });
    }
    
    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function() {
            if (currentZoomLevel > 0) {
                currentZoomLevel--;
                applyZoom();
                localStorage.setItem('zoomLevel', currentZoomLevel);
            }
        });
    }
    
    function applyZoom() {
        body.className = body.className.replace(/zoom-level-[0-2]/g, '').trim();
        if (currentZoomLevel > 0) {
            body.className = body.className + ' zoom-level-' + currentZoomLevel;
        }
    }
    
    // Preencher automaticamente a regional com base no posto selecionado
    var selectPosto = document.querySelector('select[name="posto"]');
    var selectRegional = document.querySelector('select[name="regional"]');
    
    if (selectPosto && selectRegional) {
        selectPosto.addEventListener('change', function() {
            var option = this.options[this.selectedIndex];
            var regionalSugerida = option.getAttribute('data-regional');
            
            if (regionalSugerida) {
                // Encontrar a opcao correspondente no select de regionais
                var opts = selectRegional.options;
                for (var j = 0; j < opts.length; j++) {
                    if (opts[j].value === regionalSugerida || opts[j].value.indexOf(regionalSugerida + ' -') === 0) {
                        selectRegional.value = opts[j].value;
                        break;
                    }
                }
            }
            
            // Preencher o campo nome com um valor padrao
            var nomePosto = document.querySelector('input[name="nome"]');
            if (nomePosto && this.value) {
                nomePosto.value = 'Posto ' + this.value;
            }
        });
    }

    // V8.0: Pure JavaScript implementation for SPLIT and field replication (no AJAX)
    var splitIndexCentral = null;
    // v8.11.1: indices visuais para destacar multiplos splits
    window.splitVisualIndices = window.splitVisualIndices || [];

    function obterIndicesSplitCentralOrdenados(totalLinhas) {
        var indices = window.splitVisualIndices || [];
        var lista = [];
        var vistos = {};
        for (var i = 0; i < indices.length; i++) {
            var idx = parseInt(indices[i], 10);
            if (isNaN(idx) || idx < 0) continue;
            if (typeof totalLinhas === 'number' && totalLinhas > 0 && idx >= (totalLinhas - 1)) continue;
            if (vistos[idx]) continue;
            vistos[idx] = true;
            lista.push(idx);
        }
        lista.sort(function(a, b) { return a - b; });
        return lista;
    }

    function obterFaixasSplitCentral(totalLinhas) {
        var faixas = [];
        var indices = obterIndicesSplitCentralOrdenados(totalLinhas);
        var inicio = 0;
        if (!totalLinhas || totalLinhas < 1) return faixas;
        for (var i = 0; i < indices.length; i++) {
            faixas.push({ start: inicio, end: indices[i] });
            inicio = indices[i] + 1;
        }
        faixas.push({ start: inicio, end: totalLinhas - 1 });
        return faixas;
    }

    function obterFaixaDaLinhaCentral(rowIndex, totalLinhas) {
        var faixas = obterFaixasSplitCentral(totalLinhas);
        for (var i = 0; i < faixas.length; i++) {
            if (rowIndex >= faixas[i].start && rowIndex <= faixas[i].end) {
                return faixas[i];
            }
        }
        return null;
    }

    function atualizarEstadoSplitCentral() {
        var linhasCentral = document.querySelectorAll('tr.linha-central');
        var total = linhasCentral ? linhasCentral.length : 0;
        var indices = obterIndicesSplitCentralOrdenados(total);
        var hidden = document.getElementById('central_split_indices');
        var hiddenUnico = document.getElementById('central_split_index');
        var selectorLacre = 'input.central-correios';
        var selectorEtiqueta = 'input.central-etiqueta';

        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'central_split_indices';
            hidden.id = 'central_split_indices';
            document.body.appendChild(hidden);
        }

        hidden.value = indices.join(',');
        if (hiddenUnico) {
            hiddenUnico.value = indices.length ? String(indices[0]) : '';
        }

        if (!total) return;

        if (!indices.length) {
            var semSplitLacre = document.querySelectorAll(selectorLacre);
            var semSplitEtiqueta = document.querySelectorAll(selectorEtiqueta);
            for (var a = 0; a < semSplitLacre.length; a++) {
                semSplitLacre[a].readOnly = false;
                semSplitLacre[a].removeAttribute('readonly');
            }
            for (var b = 0; b < semSplitEtiqueta.length; b++) {
                semSplitEtiqueta[b].readOnly = false;
                semSplitEtiqueta[b].removeAttribute('readonly');
            }
            return;
        }

        var faixas = obterFaixasSplitCentral(total);
        for (var f = 0; f < faixas.length; f++) {
            var inicio = faixas[f].start;
            var fim = faixas[f].end;
            var linhaLider = linhasCentral[inicio];
            var campoLiderLacre;
            var campoLiderEtiqueta;
            var valorLacre = '';
            var valorEtiqueta = '';
            if (!linhaLider) continue;

            campoLiderLacre = linhaLider.querySelector(selectorLacre);
            campoLiderEtiqueta = linhaLider.querySelector(selectorEtiqueta);
            valorLacre = campoLiderLacre ? String(campoLiderLacre.value || '') : '';
            valorEtiqueta = campoLiderEtiqueta ? String(campoLiderEtiqueta.value || '') : '';

            if (campoLiderLacre) {
                campoLiderLacre.readOnly = false;
                campoLiderLacre.removeAttribute('readonly');
            }
            if (campoLiderEtiqueta) {
                campoLiderEtiqueta.readOnly = false;
                campoLiderEtiqueta.removeAttribute('readonly');
            }

            for (var r = inicio + 1; r <= fim; r++) {
                var linha = linhasCentral[r];
                var campoLacre;
                var campoEtiqueta;
                if (!linha) continue;
                campoLacre = linha.querySelector(selectorLacre);
                campoEtiqueta = linha.querySelector(selectorEtiqueta);
                if (campoLacre) {
                    campoLacre.value = valorLacre;
                    campoLacre.readOnly = true;
                    campoLacre.setAttribute('readonly', 'readonly');
                }
                if (campoEtiqueta) {
                    campoEtiqueta.value = valorEtiqueta;
                    campoEtiqueta.readOnly = true;
                    campoEtiqueta.setAttribute('readonly', 'readonly');
                }
            }
        }
    }

    // Aplica destaque visual nas linhas da CENTRAL de acordo com splitVisualIndices
    function aplicarDestaqueSplits() {
        var linhasCentral = document.querySelectorAll('tr.linha-central');
        if (!linhasCentral) return;

        // Remover classes antigas
        for (var i = 0; i < linhasCentral.length; i++) {
            removerClasse(linhasCentral[i], 'split-central-separador');
            for (var g = 1; g <= 5; g++) {
                removerClasse(linhasCentral[i], 'split-central-grupo' + g);
            }
        }

        if (!window.splitVisualIndices || window.splitVisualIndices.length === 0) return;

        // Ordenar indices e aplicar classes aos ranges abaixo de cada split
        var indices = window.splitVisualIndices.slice(0).sort(function(a,b){return a-b;});
        var total = linhasCentral.length;
        for (var gi = 0; gi < indices.length; gi++) {
            var start = indices[gi] + 1; // linhas abaixo do split
            var end = (gi + 1 < indices.length) ? (indices[gi+1]) : (total - 1);
            var classe = 'split-central-grupo' + (gi + 1);
            if (start >= 0 && start < total) {
                adicionarClasse(linhasCentral[start], 'split-central-separador');
            }
            for (var r = start; r <= end; r++) {
                if (r >= 0 && r < total) {
                    adicionarClasse(linhasCentral[r], classe);
                }
            }
        }
    }

    // Function to define split position (called by button onclick)
    window.definirSplitAqui = function(btn) {
        var tr = btn;
        while (tr && tr.tagName !== 'TR') tr = tr.parentNode;
        if (!tr) return;

        var linhasCentral = document.querySelectorAll('tr.linha-central');
        var idx = -1;
        for (var i = 0; i < linhasCentral.length; i++) {
            if (linhasCentral[i] === tr) { idx = i; break; }
        }
        if (idx < 0) return;

        // Toggle visual split: se ja existe no array, remover; senao adicionar
        var foundPos = -1;
        for (var z = 0; z < window.splitVisualIndices.length; z++) {
            if (window.splitVisualIndices[z] === idx) { foundPos = z; break; }
        }
        if (foundPos >= 0) {
            // remover
            window.splitVisualIndices.splice(foundPos, 1);
            btn.style.background = '';
            btn.textContent = 'Split aqui';
        } else {
            // adicionar
            window.splitVisualIndices.push(idx);
            btn.style.background = '#ff9800';
            btn.textContent = '← Split AQUI';
        }

        // Manter compatibilidade com comportamento antigo: alternar splitIndexCentral para logica de replicacao
        if (splitIndexCentral === idx) {
            splitIndexCentral = null;
        } else {
            // Limpar estilo de botoes de split antigos para que apenas o "ativo" (logica) fique destacado
            var allSplitBtns = document.querySelectorAll('button[onclick*="definirSplitAqui"]');
            for (var j = 0; j < allSplitBtns.length; j++) {
                allSplitBtns[j].style.border = '';
            }
            splitIndexCentral = idx;
            btn.style.border = '2px solid #ff9800';
        }

        // Aplicar destaques visuais
        aplicarDestaqueSplits();
        atualizarEstadoSplitCentral();
    };
    
    // Function to replicate value within appropriate group (called by input listeners)
    window.replicarValor = function(campo, tipo) {
        var linhasCentral = document.querySelectorAll('tr.linha-central');
        var tr = campo;
        while (tr && tr.tagName !== 'TR') tr = tr.parentNode;
        if (!tr) return;
        
        var rowIndex = -1;
        for (var i = 0; i < linhasCentral.length; i++) {
            if (linhasCentral[i] === tr) { rowIndex = i; break; }
        }
        if (rowIndex < 0) return;
        
        var valor = campo.value;
        var selector = (tipo === 'correios') ? 'input.central-correios' : 'input.central-etiqueta';
        
        if (!obterIndicesSplitCentralOrdenados(linhasCentral.length).length) {
            // No split: replicate to all CENTRAL fields of this type
            var campos = document.querySelectorAll(selector);
            for (var j = 0; j < campos.length; j++) {
                campos[j].value = valor;
            }
        } else {
            // Split ativo: replicate only within the current visual block
            var faixa = obterFaixaDaLinhaCentral(rowIndex, linhasCentral.length);
            var groupStart = faixa ? faixa.start : 0;
            var groupEnd = faixa ? faixa.end : (linhasCentral.length - 1);
            
            // Apply to fields in the appropriate group
            var campos = document.querySelectorAll(selector);
            for (var k = 0; k < campos.length; k++) {
                var fieldTr = campos[k];
                while (fieldTr && fieldTr.tagName !== 'TR') fieldTr = fieldTr.parentNode;
                if (!fieldTr) continue;
                
                var fieldRowIndex = -1;
                for (var n = 0; n < linhasCentral.length; n++) {
                    if (linhasCentral[n] === fieldTr) { fieldRowIndex = n; break; }
                }
                
                // Replicate if field is within the current group
                if (fieldRowIndex >= groupStart && fieldRowIndex <= groupEnd) {
                    campos[k].value = valor;
                }
            }
        }
    };

    atualizarEstadoSplitCentral();
    
    // Add event listeners to central-correios inputs
    var centralCorreioInputs = document.querySelectorAll('input.central-correios');
    for (var c = 0; c < centralCorreioInputs.length; c++) {
        (function(campo) {
            campo.addEventListener('change', function() {
                replicarValor(campo, 'correios');
            });
        })(centralCorreioInputs[c]);
    }
    
    // Add event listeners to central-etiqueta inputs
    var centralEtiquetaInputs = document.querySelectorAll('input.central-etiqueta');
    for (var e = 0; e < centralEtiquetaInputs.length; e++) {
        (function(campo) {
            campo.addEventListener('change', function() {
                replicarValor(campo, 'etiqueta');
            });
        })(centralEtiquetaInputs[e]);
    }
    
    function obterEtiquetasNaSequencia() {
        var todosEtiquetas = document.querySelectorAll('input.etiqueta-barras, input.etiqueta-pt');
        var sequencia = [];
        for (var i = 0; i < todosEtiquetas.length; i++) {
            var campo = todosEtiquetas[i];
            if (!campo || campo.disabled) continue;
            if (campo.offsetParent === null) continue;
            sequencia.push(campo);
        }
        return sequencia;
    }

    // v1.1.6: Auto-sequenciar lacre_correios_pt a partir do input dado
    function autoSequenciarLacreCorreiosPt(inputOrigem, valorBase) {
        var num = parseInt(String(valorBase).replace(/\D/g,''), 10);
        if (isNaN(num)) return;
        var tabela = document.querySelector('table[data-grupo="POUPA TEMPO"]');
        if (!tabela) return;
        var inputs = tabela.querySelectorAll('input.lacre-correios-pt');
        var encontrou = false;
        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i] === inputOrigem) { encontrou = true; continue; }
            if (!encontrou) continue;
            num++;
            if (inputs[i].value === '') {
                inputs[i].value = num;
                inputs[i].style.background = '#e8f5e9';
                (function(el) { setTimeout(function() { el.style.background = ''; }, 2000); })(inputs[i]);
            }
        }
    }

    // v1.1.6: Ligar eventos de sequenciamento e cursor nas linhas PT
    function ligarEventosPT() {
        var tabelaPT = document.querySelector('table[data-grupo="POUPA TEMPO"]');
        if (!tabelaPT) return;

        /* Lacre Correios PT: auto-sequenciar ao sair do campo */
        var inputsCpt = tabelaPT.querySelectorAll('input.lacre-correios-pt');
        for (var i = 0; i < inputsCpt.length; i++) {
            (function(inp) {
                inp.addEventListener('blur', function() {
                    var v = this.value.trim();
                    if (v !== '') autoSequenciarLacreCorreiosPt(this, v);
                });
                inp.addEventListener('change', function() {
                    var v = this.value.trim();
                    if (v !== '') autoSequenciarLacreCorreiosPt(this, v);
                });
            })(inputsCpt[i]);
        }

        /* Etiqueta PT: auto-cursor para próxima linha após 35 chars */
        var etiquetasPT = tabelaPT.querySelectorAll('input.etiqueta-pt');
        for (var j = 0; j < etiquetasPT.length; j++) {
            (function(inp) {
                inp.addEventListener('input', function() {
                    var v = (this.value || '').trim();
                    if (v.length >= 35) {
                        this.blur();
                        focarProximaEtiqueta(this);
                    }
                });
                inp.addEventListener('blur', function() {
                    var v = (this.value || '').trim();
                    if (v.length >= 35) focarProximaEtiqueta(this);
                });
            })(etiquetasPT[j]);
        }
    }

    // Helper: mostrar alerta de duplicata como toast (auto-desaparece em 3s)
    function mostrarAlertaDuplicata(div, msg) {
        if (!div) return;
        div.textContent = msg;
        div.style.opacity = '1';
        div.style.display = 'block';
        clearTimeout(div._hideTimer);
        div._hideTimer = setTimeout(function() {
            div.style.opacity = '0';
            setTimeout(function() { div.style.display = 'none'; div.textContent = ''; div.style.opacity = '1'; }, 500);
        }, 3000);
    }

    /* v1.2.2 — Vocalização: fala texto usando SpeechSynthesis (PT-BR) */
    var _vozAtiva = (function() {
        try { return localStorage.getItem('lacres_voz_ativa') !== '0'; } catch(e) { return true; }
    })();
    (function() {
        var btn = document.getElementById('btnMudo');
        if (!btn) return;
        if (!_vozAtiva) { btn.innerHTML = '&#128264;'; btn.classList.add('mudo'); btn.title = 'Áudio silenciado — clique para reativar'; }
        else { btn.innerHTML = '&#128266;'; btn.classList.remove('mudo'); btn.title = 'Silenciar áudio'; }
    })();
    window.toggleMudo = function() {
        _vozAtiva = !_vozAtiva;
        try { localStorage.setItem('lacres_voz_ativa', _vozAtiva ? '1' : '0'); } catch(e) {}
        var btn = document.getElementById('btnMudo');
        if (!btn) return;
        if (!_vozAtiva) { btn.innerHTML = '&#128264;'; btn.classList.add('mudo'); btn.title = 'Áudio silenciado — clique para reativar'; if (window.speechSynthesis) window.speechSynthesis.cancel(); }
        else { btn.innerHTML = '&#128266;'; btn.classList.remove('mudo'); btn.title = 'Silenciar áudio'; }
    };
    function falarTexto(texto) {
        if (!_vozAtiva || !window.speechSynthesis || !texto) return;
        try {
            window.speechSynthesis.cancel();
            var u = new SpeechSynthesisUtterance(String(texto));
            u.lang = 'pt-BR';
            u.rate = 1.05;
            u.pitch = 1;
            u.volume = 1;
            window.speechSynthesis.speak(u);
        } catch (e) { /* ignore */ }
    }

    // v1.1.8: Validação de display — avisa se etiqueta é de outro posto
    function verificarDisplayPosto(inp) {
        var val = (inp.value || '').replace(/\D/g, '');
        if (val.length !== 35) return;
        var tr = inp.closest ? inp.closest('tr[data-posto-codigo]') : null;
        if (!tr) {
            var p = inp.parentNode;
            while (p && p.tagName !== 'TR') { p = p.parentNode; }
            tr = (p && p.getAttribute && p.getAttribute('data-posto-codigo') !== null) ? p : null;
        }
        var postoLinha = tr ? (tr.getAttribute('data-posto-codigo') || '').replace(/^p_/, '') : '';
        var regionalLinha = tr ? ((tr.getAttribute('data-regional-codigo') || tr.getAttribute('data-regional') || '')).replace(/^p_/, '') : '';
        if (!postoLinha && !regionalLinha) return;

        // aviso existente para este input
        var avisoId = 'aviso-display-' + inp.name.replace(/[^a-z0-9]/gi, '_');
        var avisoEl = document.getElementById(avisoId);
        if (!avisoEl) {
            avisoEl = document.createElement('div');
            avisoEl.id = avisoId;
            avisoEl.style.cssText = 'display:none;margin-top:2px;padding:2px 6px;background:#fff3cd;border:1px solid #e0a800;border-radius:3px;font-size:10px;color:#856404;white-space:nowrap;';
            inp.parentNode.insertBefore(avisoEl, inp.nextSibling);
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', location.href, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.ok && res.posto !== null && res.posto !== undefined) {
                    var postoEtiq = String(res.posto).replace(/\s+/g, '').replace(/^0+/, '');
                    var postoLn = String(postoLinha).replace(/\s+/g, '').replace(/^0+/, '');
                    var regionalLn = String(regionalLinha).replace(/\s+/g, '').replace(/^0+/, '');
                    // Para REGIONAIS, a linha mostra "Posto 950 - ..." mas data-posto-codigo
                    // pode ser o codigo interno (ex: 037). Aceita match no codigo da regional tambem.
                    var bateu = (postoEtiq === postoLn) || (regionalLn !== '' && postoEtiq === regionalLn);
                    if (!bateu) {
                        avisoEl.textContent = '\u26a0 Display do posto ' + res.posto;
                        avisoEl.style.display = 'block';
                        /* v1.2.2: áudio "display de outro posto" */
                        falarTexto('display de outro posto');
                    } else {
                        avisoEl.style.display = 'none';
                    }
                } else {
                    avisoEl.style.display = 'none';
                }
            } catch(e) { avisoEl.style.display = 'none'; }
        };
        xhr.send('acao=verificar_etiqueta&leitura=' + encodeURIComponent(val));
    }

    // Ligar validação de display em todos os inputs etiqueta-validavel
    (function() {
        function ligarValidacaoDisplay(inputs) {
            for (var i = 0; i < inputs.length; i++) {
                (function(inp) {
                    inp.addEventListener('input', function() {
                        var v = (inp.value || '').replace(/\D/g, '');
                        if (v.length === 35) verificarDisplayPosto(inp);
                    });
                    inp.addEventListener('change', function() { verificarDisplayPosto(inp); });
                })(inputs[i]);
            }
        }
        ligarValidacaoDisplay(document.querySelectorAll('input.etiqueta-validavel'));
        // Observar novas linhas adicionadas
        var tabela = document.querySelector('form table') || document.body;
        if (window.MutationObserver) {
            new MutationObserver(function(muts) {
                muts.forEach(function(m) {
                    m.addedNodes.forEach(function(n) {
                        if (n.querySelectorAll) {
                            ligarValidacaoDisplay(n.querySelectorAll('input.etiqueta-validavel'));
                        }
                    });
                });
            }).observe(tabela, { childList: true, subtree: true });
        }
    })();

    // v8.4: Função auxiliar para focar no próximo input de etiqueta_correios
    window.focarProximaEtiqueta = function(inputAtual) {
        var indices = obterEtiquetasNaSequencia();
        
        // Encontrar índice do input atual
        var indiceAtual = -1;
        for (var j = 0; j < indices.length; j++) {
            if (indices[j] === inputAtual) {
                indiceAtual = j;
                break;
            }
        }
        
        // Se houver próximo input, focar nele
        if (indiceAtual >= 0 && indiceAtual + 1 < indices.length) {
            var proximoInput = indices[indiceAtual + 1];
            // Aguardar um pouco para garantir que o DOM foi atualizado
            setTimeout(function() {
                proximoInput.focus();
                // Selecionar texto se houver para facilitar sobrescrita
                if (proximoInput.select) {
                    proximoInput.select();
                }
            }, 50);
        }
    };

    window.atualizarDadosPopupEtiqueta = function(inputAtual) {
        var valorIipr = '-';
        var valorCorreios = '-';
        if (inputAtual) {
            var trLinha = inputAtual.closest('tr');
            if (trLinha) {
                var inpIIPR = trLinha.querySelector('input[name^="lacre_iipr"], input[data-tipo="iipr"]');
                var inpCorreios = trLinha.querySelector('input[name^="lacre_correios"], input[data-tipo="correios"]');
                valorIipr = inpIIPR && String(inpIIPR.value || '').trim() !== '' ? String(inpIIPR.value || '').trim() : '-';
                valorCorreios = inpCorreios && String(inpCorreios.value || '').trim() !== '' ? String(inpCorreios.value || '').trim() : '-';
            }
        }
        var campoPopupIipr = document.getElementById('popup-lacre-iipr');
        var campoPopupCorreios = document.getElementById('popup-lacre-correios');
        if (campoPopupIipr) campoPopupIipr.textContent = valorIipr;
        if (campoPopupCorreios) campoPopupCorreios.textContent = valorCorreios;
    };
    
    // v8.3 CORRIGIDA: Validação de etiquetas_correios duplicadas para CAPITAL + REGIONAIS (não CENTRAL)
    // CORREÇÃO: Usar blur em vez de change, limpar campo sem travar, sem guardas globais
    // v9.7.1: Adicionar pop-up centralizado ao focar em etiquetas
    var etiquetasValidaveis = document.querySelectorAll('input.etiqueta-validavel');
    for (var v = 0; v < etiquetasValidaveis.length; v++) {
        (function(inputEtiqueta) {
            // v9.7.1: Mostrar pop-up ao focar no input
            inputEtiqueta.addEventListener('focus', function() {
                mostrarPopupEtiqueta(this);
            });
            
            // v8.4: Listener de input para disparar blur quando atingir 35 dígitos (para scanner/leitura automática)
            inputEtiqueta.addEventListener('input', function() {
                var valor = (this.value || '').replace(/\D/g, ''); // Remove tudo que não é dígito
                
                // v9.7.1: Atualizar progresso no popup
                atualizarProgressoPopup(valor.length);
                
                if (valor.length >= 35) {
                    // Disparar blur para que a validação execute
                    this.blur();
                }
            });
            
            inputEtiqueta.addEventListener('blur', function() {
                // v9.7.1: Ocultar popup ao perder foco
                ocultarPopupEtiqueta();
                
                var valorAtual = (this.value || '').trim();
                var indice = this.getAttribute('data-indice');
                var grupoAtual = this.getAttribute('data-grupo') || '';
                
                // Se campo vazio, apenas limpar aviso
                if (valorAtual === '') {
                    this.style.background = '';
                    var alertaDiv = document.getElementById('alerta-' + indice);
                    if (alertaDiv) { alertaDiv.style.display = 'none'; alertaDiv.textContent = ''; }
                    return;
                }
                
                // v8.3 CORRIGIDA: Contar ocorrências deste valor em CAPITAL (regional=0) + REGIONAIS, excluindo CENTRAL IIPR
                var totalOcorrencias = 0;
                for (var i = 0; i < etiquetasValidaveis.length; i++) {
                    var outroInput = etiquetasValidaveis[i];
                    var outroGrupo = outroInput.getAttribute('data-grupo') || '';
                    
                    // Saltar se for CENTRAL IIPR (central pode compartilhar)
                    if (outroGrupo === 'CENTRAL IIPR') continue;
                    
                    var outroValor = (outroInput.value || '').trim();
                    if (outroValor === valorAtual) {
                        totalOcorrencias++;
                    }
                }
                
                // Se tem duplicata (mais de 1 ocorrência), pedir confirmacao para manter
                if (totalOcorrencias > 1) {
                    var jaConfirmado = this.getAttribute('data-dup-confirmado') === '1';
                    var manterDuplicada = jaConfirmado;
                    if (!jaConfirmado) {
                        manterDuplicada = confirm('Etiqueta repetida detectada. Deseja manter este mesmo display em mais de um posto?');
                    }

                    if (manterDuplicada) {
                        this.setAttribute('data-dup-confirmado', '1');
                        this.style.background = '#fff3cd';
                        var alertaDivDup = document.getElementById('alerta-' + indice);
                        mostrarAlertaDuplicata(alertaDivDup, 'Etiqueta repetida (confirmada).');
                        focarProximaEtiqueta(this);
                    } else {
                        // Limpar apenas o campo atual, sem reverter a anteriores
                        this.value = '';
                        this.removeAttribute('data-dup-confirmado');
                        this.style.background = '';
                        var alertaDiv = document.getElementById('alerta-' + indice);
                        mostrarAlertaDuplicata(alertaDiv, 'Campo limpo. Digite novamente sem duplicar.');
                        // Recolocar foco no campo para permitir nova digitação (NÃO avança)
                        this.focus();
                    }
                } else {
                    // Aceita o valor - limpar aviso
                    this.style.background = '';
                    this.removeAttribute('data-dup-confirmado');
                    var alertaDiv = document.getElementById('alerta-' + indice);
                    if (alertaDiv) { alertaDiv.style.display = 'none'; alertaDiv.textContent = ''; }
                    // v8.4: Se aceito, avançar para o próximo input de etiqueta
                    focarProximaEtiqueta(this);
                }
            });
        })(etiquetasValidaveis[v]);
    }

    var etiquetasCentral = document.querySelectorAll('input.etiqueta-barras:not(.etiqueta-validavel)');
    for (var cidx = 0; cidx < etiquetasCentral.length; cidx++) {
        (function(inputEtiquetaCentral) {
            inputEtiquetaCentral.addEventListener('focus', function() {
                mostrarPopupEtiqueta(this);
            });

            inputEtiquetaCentral.addEventListener('input', function() {
                var valor = (this.value || '').replace(/\D/g, '');
                atualizarProgressoPopup(valor.length);
                if (valor.length >= 35) {
                    this.blur();
                }
            });

            inputEtiquetaCentral.addEventListener('blur', function() {
                ocultarPopupEtiqueta();
                var valorAtual = (this.value || '').replace(/\D/g, '');
                if (valorAtual.length === 35) {
                    focarProximaEtiqueta(this);
                }
            });
        })(etiquetasCentral[cidx]);
    }
    
    // v9.7.1: Funções para controlar o pop-up de etiquetas
    window.mostrarPopupEtiqueta = function(inputAtual) {
        var popup = document.getElementById('popup-etiqueta-focal');
        if (!popup) return;
        
        // Encontrar nome do posto
        var tr = inputAtual.closest('tr');
        if (!tr) return;
        
        var nomePosto = '(Posto não identificado)';
        var tdPosto = tr.querySelector('td:first-child');
        if (tdPosto) {
            var texto = tdPosto.textContent || tdPosto.innerText || '';
            // Remover texto do botão SPLIT se existir
            nomePosto = texto.replace(/SPLIT/g, '').trim();
        }
        
        // Atualizar conteúdo do popup
        document.getElementById('popup-posto-nome').textContent = nomePosto;
        atualizarDadosPopupEtiqueta(inputAtual);
        
        // Calcular posição atual
        var todosEtiquetas = obterEtiquetasNaSequencia();
        var posAtual = 0;
        var total = todosEtiquetas.length;
        for (var i = 0; i < todosEtiquetas.length; i++) {
            if (todosEtiquetas[i] === inputAtual) {
                posAtual = i + 1;
                break;
            }
        }
        
        document.getElementById('popup-progresso').textContent = 'Posto ' + posAtual + ' de ' + total;
        
        // Resetar contador de dígitos
        atualizarProgressoPopup(0);
        
        // Mostrar popup
        popup.className = 'active';
    };
    
    window.ocultarPopupEtiqueta = function() {
        var popup = document.getElementById('popup-etiqueta-focal');
        if (popup) {
            popup.className = '';
        }
    };
    
    window.atualizarProgressoPopup = function(digitosLidos) {
        var progressoDiv = document.getElementById('popup-progresso');
        if (!progressoDiv) return;
        
        var todosEtiquetas = obterEtiquetasNaSequencia();
        var inputAtual = document.activeElement;
        var posAtual = 0;
        var total = todosEtiquetas.length;
        
        for (var i = 0; i < todosEtiquetas.length; i++) {
            if (todosEtiquetas[i] === inputAtual) {
                posAtual = i + 1;
                break;
            }
        }
        
        var texto = 'Posto ' + posAtual + ' de ' + total;
        if (digitosLidos > 0) {
            texto += ' • ' + digitosLidos + '/35 dígitos';
        }
        
        progressoDiv.textContent = texto;
    };
    
    // v9.8.0: Função para toggle do indicador de dias
    window.toggleIndicadorDias = function() {
        var indicador = document.getElementById('indicador-dias');
        if (!indicador) return;
        
        if (indicador.className.indexOf('collapsed') >= 0) {
            // Expandir
            indicador.className = indicador.className.replace(/\s*collapsed/g, '');
            var toggleIcon = indicador.querySelector('.indicador-toggle');
            if (toggleIcon) toggleIcon.textContent = '▼';
        } else {
            // Recolher
            indicador.className = indicador.className + ' collapsed';
            var toggleIcon = indicador.querySelector('.indicador-toggle');
            if (toggleIcon) toggleIcon.textContent = '▶';
        }
    };
});

// Funcoes para o modal
function abrirModalInserir(botao) {
    // Obter dados do posto e operacao
    var posto = botao.getAttribute('data-posto');
    var grupo = botao.getAttribute('data-grupo');
    var posicao = botao.getAttribute('data-posicao');
    
    // Preencher campos do modal
    document.getElementById('referencia_posto').value = posto;
    document.getElementById('posicao_insercao').value = posicao;
    document.getElementById('novo_grupo').value = grupo;
    
    // Sugerir valores para os lacres baseados no posto de referencia
    var tr = botao;
    while (tr && tr.tagName !== 'TR') {
        tr = tr.parentNode;
    }
    if (tr) {
        var lacreIiprEl = tr.querySelector('input.lacre[data-tipo="iipr"]');
        var lacreCorreiosEl = tr.querySelector('input.lacre[data-tipo="correios"]');
        var lacreIipr = lacreIiprEl ? lacreIiprEl.value : '0';
        var lacreCorreios = lacreCorreiosEl ? lacreCorreiosEl.value : '0';

        var novoI = document.getElementById('novo_lacre_iipr');
        var novoC = document.getElementById('novo_lacre_correios');
        if (novoI) { novoI.value = parseInt(lacreIipr, 10) + 1; }
        if (novoC) { novoC.value = parseInt(lacreCorreios, 10) + 1; }
    }
    
    // Focar no campo de nome
    document.getElementById('novo_nome').value = '';
    document.getElementById('novo_nome').focus();
    
    // Mostrar o modal
    var modal = document.getElementById('modal-inserir');
    if (modal) {
        modal.className = modal.className + ' active';
    }
}

function fecharModal() {
    var modal = document.getElementById('modal-inserir');
    if (modal) {
        modal.className = modal.className.replace(/\s*active/g, '');
    }
}

// V7.6: Funcoes para modal de confirmacao de salvamento
function abrirModalConfirmacao() {
    // Contar etiquetas validas
    var etiquetasValidas = contarEtiquetasValidas();
    document.getElementById('contador-etiquetas').textContent = etiquetasValidas;
    
    // Mostrar o modal
    var modal = document.getElementById('modal-confirmacao-salvamento');
    if (modal) {
        modal.className = modal.className + ' active';
    }
    
    // Focar no campo de nome
    document.getElementById('login_personalizado').focus();
}

function fecharModalConfirmacao() {
    var modal = document.getElementById('modal-confirmacao-salvamento');
    if (modal) {
        modal.className = modal.className.replace(/\s*active/g, '');
    }
}

function contarEtiquetasValidas() {
    var contador = 0;
    var campos = document.querySelectorAll('input.etiqueta-barras');
    for (var n = 0; n < campos.length; n++) {
        if (campos[n].value && campos[n].value.length === 35) {
            contador++;
        }
    }
    return contador;
}

// Fechar modais ao clicar fora
document.addEventListener('click', function(e) {
    if (e.target.className && e.target.className.indexOf('modal-overlay') >= 0) {
        fecharModal();
        fecharModalConfirmacao();
    }
});

// V8.1: Funcao para toggle do painel de analise
function recolherPainelAnalise() {
    var painel = document.getElementById('painel-analise');
    if (painel && painel.className.indexOf('collapsed') === -1) {
        painel.className = painel.className + ' collapsed';
        localStorage.setItem('painelAnaliseCollapsed', 'true');
    }
}

function toggleAnalisePanel() {
    var painel = document.getElementById('painel-analise');
    if (painel) {
        if (painel.className.indexOf('collapsed') >= 0) {
            painel.className = painel.className.replace(/\s*collapsed/g, '');
            localStorage.setItem('painelAnaliseCollapsed', 'false');
        } else {
            painel.className = painel.className + ' collapsed';
            localStorage.setItem('painelAnaliseCollapsed', 'true');
        }
    }
}

// V8.1: Funcao para mostrar/esconder painel de insercao
function togglePainelInsercao() {
    var painel = document.getElementById('painel-insercao');
    if (painel) {
        if (painel.className.indexOf('ativo') >= 0) {
            painel.className = painel.className.replace(/\s*ativo/g, '');
        } else {
            painel.className = painel.className + ' ativo';
            // Focar no campo de codigo de barras quando abrir
            setTimeout(function() {
                var codigoBarras = document.getElementById('codigo_barras');
                if (codigoBarras) codigoBarras.focus();
            }, 100);
        }
    }
}

// V8.1: Inicializacao da interface
document.addEventListener('DOMContentLoaded', function() {
    // v1.1.6: Receber lacre replicado de modelo_oficio_poupa_tempo (postMessage)
    window.addEventListener('message', function(ev) {
        try {
            var d = (typeof ev.data === 'string') ? JSON.parse(ev.data) : ev.data;
            if (!d || d.tipo !== 'replicar_lacre_pt') return;
            var posto = String(d.posto || '');
            var lacre = String(d.lacre || '').trim();
            var campo = d.campo || 'lacre_pt';
            if (!posto || !lacre) return;
            /* Tentar com e sem zeros à esquerda, com e sem prefixo p_ */
            var postoInt = String(parseInt(posto, 10) || 0);
            var postoZero3 = posto.length < 3 ? ('000' + posto).slice(-3) : posto;
            var tentativas = [
                'input[name="' + campo + '[' + posto + ']"]',
                'input[name="' + campo + '[' + postoZero3 + ']"]',
                'input[name="' + campo + '[' + postoInt + ']"]',
                'input[name="' + campo + '[p_' + posto + ']"]',
                'input[name="' + campo + '[p_' + postoZero3 + ']"]'
            ];
            var inp = null;
            for (var t = 0; t < tentativas.length; t++) {
                inp = document.querySelector(tentativas[t]);
                if (inp) break;
            }
            if (inp) {
                inp.value = lacre;
                inp.style.background = '#fffde7';
                setTimeout(function() { inp.style.background = ''; }, 2000);
                if (campo === 'lacre_correios_pt') {
                    autoSequenciarLacreCorreiosPt(inp, lacre);
                }
            }
        } catch(e) { /* ignore */ }
    });

    ligarEventosPT();

    // V8.1: Mensagens automaticas que desaparecem
    var mensagemAuto = document.getElementById('mensagem-auto');
    if (mensagemAuto) {
        setTimeout(function() {
            mensagemAuto.className = mensagemAuto.className + ' fadeOut';
            setTimeout(function() {
                if (mensagemAuto.parentNode) {
                    mensagemAuto.parentNode.removeChild(mensagemAuto);
                }
            }, 500);
        }, 3000); // Desaparece apos 3 segundos
    }
    
    // V8.1: Validacao do codigo de barras
    var codigoBarrasInput = document.getElementById('codigo_barras');
    if (codigoBarrasInput) {
        codigoBarrasInput.addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, ''); // Apenas numeros
            
            if (value.length > 19) {
                value = value.substring(0, 19);
            }
            
            e.target.value = value;
            
            // Validar se tem 19 dígitos
            if (value.length === 19) {
                e.target.style.borderColor = '#28a745'; // Verde se válido
            } else {
                e.target.style.borderColor = '#dc3545'; // Vermelho se inválido
            }
        });
        
        // Permitir colagem e limpeza automatica
        codigoBarrasInput.addEventListener('paste', function(e) {
            var target = e.target;
            setTimeout(function() {
                var value = target.value.replace(/\D/g, '');
                if (value.length > 19) {
                    value = value.substring(0, 19);
                }
                target.value = value;
            }, 10);
        });
    }
    
    // V8.0: Restaurar estado do painel de analise
    var painelAnalise = document.getElementById('painel-analise');
    if (painelAnalise && localStorage.getItem('painelAnaliseCollapsed') === 'true') {
        painelAnalise.className = painelAnalise.className + ' collapsed';
    }
    
    // V8.0: Mascara para data no formato dd-mm-aaaa
    var dataInput = document.getElementById('data_inserir');
    if (dataInput) {
        dataInput.addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, '');
            
            if (value.length >= 2) {
                value = value.substring(0, 2) + '-' + value.substring(2);
            }
            if (value.length >= 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 9);
            }
            
            e.target.value = value;
        });
        
        // Preencher com data atual se estiver vazio
        if (!dataInput.value) {
            var hoje = new Date();
            var dia = hoje.getDate();
            var mes = hoje.getMonth() + 1;
            var ano = hoje.getFullYear();
            dia = dia < 10 ? '0' + dia : dia;
            mes = mes < 10 ? '0' + mes : mes;
            dataInput.value = dia + '-' + mes + '-' + ano;
        }
    }
});
</script>


<?php
if (!function_exists('__pt_session_v222')) {
  function __pt_session_v222(){ if(function_exists('session_status')){ if(session_status()!==PHP_SESSION_ACTIVE) @session_start(); } else { if(!session_id()) @session_start(); } }
}
__pt_session_v222();

$__datas_filtro = isset($datas_filtro)?$datas_filtro:(isset($_POST['datas_filtro'])?$_POST['datas_filtro']:(isset($_GET['datas_filtro'])?$_GET['datas_filtro']:array()));
if (!is_array($__datas_filtro)) $__datas_filtro = array_filter(array_map('trim', explode(',', (string)$__datas_filtro)));

if (!function_exists('__pt_norm_dates_v222')){
  function __pt_norm_dates_v222($arr){
    $out=array();
    foreach((array)$arr as $d){
      $d=trim((string)$d); if(!$d) continue;
      if (preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{4}$/',$d)){ $sep=strpos($d,'/')!==false?'/':'-'; $p=explode($sep,$d); $out[]=sprintf('%04d-%02d-%02d',(int)$p[2],(int)$p[1],(int)$p[0]); }
      elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/',$d)){ $out[]=$d; }
    }
    return array_values(array_unique($out));
  }
}
$__datas_norm = __pt_norm_dates_v222($__datas_filtro);


// ===================================================================
// BLOCO POUPA TEMPO – Gera payload com endereço para o ofício
// ===================================================================

$poupaTempoPayload = array();

try {
    if (isset($pdo_controle) && $pdo_controle instanceof PDO && !empty($__datas_norm)) {

        // Datas normalizadas (as mesmas usadas no restante da tela)
        $in = "'" . implode("','", array_map('strval', $__datas_norm)) . "'";

        $sql = "
            SELECT 
                LPAD(c.posto,3,'0') AS codigo,
                COALESCE(r.nome, CONCAT('POUPA TEMPO - ', LPAD(c.posto,3,'0'))) AS nome,
                SUM(COALESCE(c.quantidade,0)) AS quantidade,
                r.endereco AS endereco
            FROM ciPostosCsv c
            INNER JOIN ciRegionais r 
                    ON LPAD(r.posto,3,'0') = LPAD(c.posto,3,'0')
            WHERE DATE(c.dataCarga) IN ($in)
              AND REPLACE(LOWER(r.entrega),' ','') LIKE 'poupa%tempo'
            GROUP BY 
                LPAD(c.posto,3,'0'), r.nome, r.endereco
            ORDER BY 
                LPAD(c.posto,3,'0')
        ";

        $stmt = $pdo_controle->query($sql, PDO::FETCH_ASSOC);

        foreach ($stmt as $r) {
            $poupaTempoPayload[] = array(
                'codigo'     => (string)$r['codigo'],          // ex: "006"
                'nome'       => (string)$r['nome'],            // ex: "POUPA TEMPO - PINHEIRINHO"
                'quantidade' => (int)$r['quantidade'],         // soma das CINs
                'lacre'      => '',
                'endereco'   => (string)$r['endereco']         // ENDEREÇO vindo da ciRegionais
            );
        }
    }
} catch (Exception $e) {
    // Se quiser depurar:
    // echo "<pre>ERRO POUPATEMPO SQL: ".$e->getMessage()."</pre>";
}

// Fallback antigo (apenas se o SELECT não trouxe nada)
// Usa o array $dados, caso ainda exista a estrutura antiga
if (!$poupaTempoPayload && isset($dados) && is_array($dados)) {
    $cands = array('POUPA TEMPO','Poupa Tempo','POUPATEMPO','POUPA-TEMPO');
    $src   = array();

    foreach ($cands as $k) {
        if (isset($dados[$k]) && is_array($dados[$k])) {
            $src = $dados[$k];
            break;
        }
    }

    if ($src) {
        $agr = array();
        foreach ($src as $r) {
            $codigo = sprintf(
                '%03s',
                preg_replace('/\D+/', '', (string)(
                    isset($r['posto_codigo']) ? $r['posto_codigo'] :
                    (isset($r['codigo']) ? $r['codigo'] : '')
                ))
            );
            $nome = trim((string)(
                isset($r['posto_nome']) ? $r['posto_nome'] :
                (isset($r['nome']) ? $r['nome'] : '')
            ));
            $qtd  = (int)(
                isset($r['quantidade']) ? $r['quantidade'] :
                (isset($r['qtd']) ? $r['qtd'] : 0)
            );
            if (!$codigo) continue;

            $key = $codigo.'|'.$nome;
            if (!isset($agr[$key])) {
                $agr[$key] = array(
                    'codigo'   => $codigo,
                    'nome'     => $nome,
                    'quantidade' => 0,
                    'lacre'    => '',
                    'endereco' => '' // aqui não temos endereço nesse fallback
                );
            }
            $agr[$key]['quantidade'] += $qtd;
        }
        $poupaTempoPayload = array_values($agr);
        usort($poupaTempoPayload, function($a,$b){
            return (int)$a['codigo'] - (int)$b['codigo'];
        });
    }
}

// JSON que vai para o modelo_oficio_poupa_tempo.php
$poupaTempoPayloadJson = json_encode_legado_seguro($poupaTempoPayload ?: array(), 0);

// Datas em string (as mesmas usadas na tela)
$__pt_datas_join = htmlspecialchars(
    implode(',', isset($__datas_norm) ? $__datas_norm : array()),
    ENT_QUOTES,
    'UTF-8'
);
?>

<!-- ==================================================================
         FORMULÁRIO OCULTO – Gera o ofício PT com etiqueta Correios em nova aba
     ================================================================== -->
<form id="oficioPTForm" method="post" action="modelo_oficio_poupa_tempo.php" target="_blank" style="display:none;">
  <input type="hidden" name="acao" value="oficio_poupatempo" />
  <input type="hidden" name="pt_datas" value="<?php echo $__pt_datas_join; ?>" />
        <input type="hidden" name="responsavel" value="<?php echo htmlspecialchars($responsavel, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="pt_postos_sel" id="ptPostosSel" value="" />
    <input type="hidden" name="pt_filtrar_nao_conferidos" id="ptFiltroNaoConferidosInput" value="0" />
    <input type="hidden" name="pt_filtrar_sem_oficio" id="ptFiltroSemOficioInput" value="0" />
        <input type="hidden" name="pt_modo_visual" id="ptModoVisualInput" value="correios" />
  <input type="hidden" name="<?php echo htmlspecialchars(session_name(),ENT_QUOTES,'UTF-8'); ?>"
         value="<?php echo htmlspecialchars(session_id(),ENT_QUOTES,'UTF-8'); ?>" />
  <textarea name="poupatempo_payload" style="display:none;"><?php
      echo htmlspecialchars($poupaTempoPayloadJson ?: '[]', ENT_QUOTES, 'UTF-8');
  ?></textarea>
</form>


<script>
(function(){
    window.coletarPostosSelecionadosPT = function(){
        var texto = document.getElementById('ptFiltroPostosTexto');
        var listaDigitada = texto ? String(texto.value || '').replace(/[^\d,;\s-]/g, ' ') : '';
        if (listaDigitada !== '') {
            var partes = listaDigitada.split(/[,;\s-]+/);
            var mapa = {};
            var filtrados = [];
            for (var p = 0; p < partes.length; p++) {
                var codigo = partes[p].replace(/\D+/g, '');
                if (codigo === '') continue;
                codigo = ('000' + codigo).slice(-3);
                if (mapa[codigo]) continue;
                mapa[codigo] = true;
                filtrados.push(codigo);
            }
            if (filtrados.length) return filtrados.join(',');
        }
        var nodes = document.querySelectorAll('.pt-selecionar');
        if (!nodes || !nodes.length) return '';
        var selecionados = [];
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].checked) selecionados.push(nodes[i].getAttribute('data-posto') || '');
        }
        return selecionados.filter(function(v){ return v; }).join(',');
    };
    window.prepararFiltrosOficioPT = function(){
        var postosSel = window.coletarPostosSelecionadosPT ? window.coletarPostosSelecionadosPT() : '';
        var inputPostos = document.getElementById('ptPostosSel');
        if (inputPostos) inputPostos.value = postosSel;
        var naoConf = document.getElementById('ptFiltroNaoConferidos');
        var semOficio = document.getElementById('ptFiltroSemOficio');
        var inputNaoConf = document.getElementById('ptFiltroNaoConferidosInput');
        var inputSemOficio = document.getElementById('ptFiltroSemOficioInput');
        if (inputNaoConf) inputNaoConf.value = (naoConf && naoConf.checked) ? '1' : '0';
        if (inputSemOficio) inputSemOficio.value = (semOficio && semOficio.checked) ? '1' : '0';
    };
})();

(function(){
    function norm(t){ return (t||'').toLowerCase().replace(/\s+/g,' ').trim(); }
    function allBtns(){ return document.querySelectorAll('button, a, input[type="button"], input[type="submit"]'); }
    function findByText(list){
        var n=allBtns();
        for(var i=0;i<n.length;i++){
            var t=norm(n[i].innerText||n[i].value);
            for(var k=0;k<list.length;k++){ if(t.indexOf(list[k])>=0) return n[i]; }
        }
        return null;
    }
    function addBtn(){
        if (document.getElementById('btnOficioPT') || document.getElementById('btnGerarOficioPT')) return;
        var salvar=findByText(['salvar etiquetas','salvar etiqueta']);
        var imprimir=findByText(['imprimir']);
        var ref=salvar||imprimir;
        var cont=(ref&&ref.parentElement)?ref.parentElement:document.body;
        var btn=document.createElement('button');
        btn.type='button'; btn.id='btnGerarOficioPT';
        btn.className=(ref?(ref.className+' btn-oficio-pt'):'btn-oficio-pt');
        btn.innerHTML='<i class="icon-doc"></i> Ofício PT com Etiqueta Correios';
        cont.appendChild(btn);
        btn.addEventListener('click', function(){
            var f=document.getElementById('oficioPTForm');
            if(f){
                if(window.prepararFiltrosOficioPT) window.prepararFiltrosOficioPT();
                f.submit();
            }
        });
    }
    if (document.readyState==='loading') document.addEventListener('DOMContentLoaded', addBtn); else addBtn();
    new MutationObserver(function(){ if(!document.getElementById('btnGerarOficioPT')) addBtn(); }).observe(document.documentElement,{childList:true,subtree:true});
})();
</script>
<!-- SPLIT CENTRAL IIPR - helper rename -->
<script>(function(){var t=document.getElementById('tabela-central-iipr');if(t){t.id='tblCentralIIPR';}})();</script>


<!-- SPLIT CENTRAL IIPR START -->
<style>
  #btnSplitCentral{margin-left:12px}
  tr.split-below{background:#fff9cc}
  #splitCentralModal{position:fixed; inset:0; background:rgba(0,0,0,.35); display:none;
    align-items:center; justify-content:center; z-index:9999;}
  #splitCentralModal .box{background:#fff; border-radius:10px; min-width:360px; max-width:640px;
    padding:16px; box-shadow:0 8px 24px rgba(0,0,0,.25)}
  #splitCentralModal h3{margin:0 0 8px 0}
  #splitCentralModal .list{max-height:320px; overflow:auto; border:1px solid #ddd; border-radius:8px; padding:8px}
  #splitCentralModal .row{display:flex; gap:8px; align-items:center; margin:4px 0}
  #splitCentralModal .actions{display:flex; gap:10px; justify-content:flex-end; margin-top:12px}
  #splitCentralModal button{padding:8px 12px; border-radius:6px; border:1px solid #bbb; cursor:pointer}
  #splitCentralModal .ok{background:#4caf50; color:#fff; border-color:#4caf50}
  #splitCentralModal .cancel{background:#ccc}
</style>

<div id="splitCentralModal">
  <div class="box">
    <h3>Escolha a partir de qual posto será feito o split (somente CENTRAL IIPR)</h3>
    <div class="list" id="splitCentralList"></div>
    <div class="actions">
      <button class="cancel" id="splitCentralCancel">Cancelar</button>
      <button class="ok" id="splitCentralApply">Aplicar</button>
    </div>
  </div>
</div>

<script>
(function(){
  function ensureCentralId(){
    var t = document.getElementById('tblCentralIIPR');
    var tblCentral = document.getElementById('tabela-central-iipr');
    if (tblCentral) {
      tblCentral.id = 'tblCentralIIPR';
      return tblCentral;
        }
    
    // Fallback: buscar por cabecalho
    var headers = document.querySelectorAll('h1,h2,h3,h4,legend,div,section,span');
    var hdr = null;
    for (var h = 0; h < headers.length; h++) {
      if (/(^|\s)central\s+iipr(\s|$)/i.test((headers[h].textContent||'').trim())) {
        hdr = headers[h];
        break;
      }
    }
    if (hdr){
      var sib = hdr.nextElementSibling;
      while (sib && sib.tagName && sib.tagName.toLowerCase()!=='table'){ sib = sib.nextElementSibling; }
      if (sib){ sib.id='tblCentralIIPR'; return sib; }
    }
    var best=null, bestScore=0;
    var tables = document.querySelectorAll('table');
    for (var ti = 0; ti < tables.length; ti++) {
      var tb = tables[ti];
      var rows = tb.querySelectorAll('tbody tr'); if(!rows.length) rows = tb.querySelectorAll('tr:not(:first-child)');
      var ok=0, tot=0;
      for (var ri = 0; ri < rows.length; ri++) {
        var tr = rows[ri];
        var el = tr.querySelector("[name='regional'],[name='regional[]'],[data-regional]");
        var v = el ? (el.value || el.getAttribute('data-regional') || '') : '';
        if (String(v).trim()==='999') ok++;
        tot++;
      }
      var score = tot? ok/tot : 0;
      if (score>bestScore){ bestScore=score; best=tb; }
    }
    if (best && bestScore>=0.5){ best.id='tblCentralIIPR'; return best; }
    return null;
  }
  function norm(t){return (t||'').toLowerCase().replace(/\s+/g,' ').trim();}
  function ensureSplitButton(){
    var btn = document.getElementById('btnSplitCentral');
    if (!btn) {
      var anchorAfter = ['Gerar Oficio Poupa Tempo','Salvar Etiquetas','Imprimir'];
      var ref = null;
      var btnsAndLinks = document.querySelectorAll('a,button');
      for (var i=0;i<anchorAfter.length;i++){
        for (var j = 0; j < btnsAndLinks.length; j++) {
          if (norm(btnsAndLinks[j].textContent) === norm(anchorAfter[i])) {
            ref = btnsAndLinks[j];
            break;
          }
        }
        if (ref) break;
      }
      if (ref && ref.parentElement){
        btn = document.createElement('a');
        btn.id = 'btnSplitCentral';
        btn.href = 'javascript:void(0)';
        //btn.className = 'btn btn-primary';
        btn.className = ref ? ref.className : '';
        btn.innerHTML = '<span>Split da Central</span>';
        // só cor roxa aqui; resto do layout vem do ref
        btn.style.background  = '#7c63ff';
        btn.style.borderColor = '#7c63ff';

        ref.parentElement.insertBefore(btn, ref.nextSibling);
      }
    }
    return document.getElementById('btnSplitCentral');
  }
  function rowsOf(tbl){
    var rows = tbl.querySelectorAll('tbody tr');
    if (!rows.length) rows = tbl.querySelectorAll('tr:not(:first-child)');
    return rows;
  }
  function indexByHeader(tbl, headerText){
    var ths = tbl.querySelectorAll('thead th, tr:first-child th');
    var target = (headerText||'').toLowerCase();
    for (var i=0;i<ths.length;i++){
      if ((ths[i].textContent||'').toLowerCase().indexOf(target)>=0) return i;
    }
    return -1;
  }
  function cell(td){
    var inp = td ? td.querySelector('input,textarea') : null;
    return {
      get: function(){ return inp ? (inp.value||'') : (td ? (td.textContent||'') : ''); },
      set: function(v){ if (inp){ inp.value=v; inp.setAttribute('value', v); } else if (td){ td.textContent=v; } },
      lock: function(on){ 
        if (inp){ 
          inp.readOnly = !!on; 
          inp.disabled = false;
          // VERSAO 4: Remover atributo readonly do HTML se estiver desbloqueando
          if (!on) {
            inp.removeAttribute('readonly');
            // Remover classe readonly se existir
            if (inp.className.indexOf('etiqueta-central-readonly') >= 0) {
              inp.className = inp.className.replace(/\s*etiqueta-central-readonly/g, '');
              if (inp.className.indexOf('etiqueta-central') < 0) {
                inp.className = inp.className + ' etiqueta-central';
              }
            }
          } else {
            inp.setAttribute('readonly', 'readonly');
          }
        } 
      }
    };
  }
  function defaults(tbl, idxL, idxE){
    var rs = rowsOf(tbl); if (!rs.length) return {lacre:'', etiqueta:''};
    var cL = (idxL>=0) ? rs[0].children[idxL] : null;
    var cE = (idxE>=0) ? rs[0].children[idxE] : null;
    return {lacre: cell(cL).get().trim(), etiqueta: cell(cE).get().trim()};
  }
  // Funcao auxiliar para adicionar classe (compativel com navegadores antigos)
  function addClass(el, classe) {
    if (!el) return;
    if (el.className.indexOf(classe) < 0) {
      el.className = el.className + ' ' + classe;
    }
  }
  // Funcao auxiliar para remover classe (compativel com navegadores antigos)
  function removeClass(el, classe) {
    if (!el) return;
    var regex = new RegExp('\\s*' + classe, 'g');
    el.className = el.className.replace(regex, '');
  }
  function applyAt(tbl, splitIndex){
    var rs = rowsOf(tbl); if (!rs.length) return;
    var idxL = indexByHeader(tbl,'lacre correios');
    var idxE = indexByHeader(tbl,'etiqueta correios');
    var def = defaults(tbl, idxL, idxE);
    
    // Primeiro, marcar visualmente o split e configurar editabilidade
    for (var i=0;i<rs.length;i++){
      var r = rs[i];
      var cL = (idxL>=0) ? r.children[idxL] : null;
      var cE = (idxE>=0) ? r.children[idxE] : null;
      
      if (i<=splitIndex){
        // GRUPO 1 (acima do split)
        removeClass(r, 'split-below');
        if (i === 0) {
          // Primeira linha do grupo 1: editavel (define o valor do grupo)
          if (cL) cell(cL).set(def.lacre), cell(cL).lock(false);
          if (cE) cell(cE).set(def.etiqueta), cell(cE).lock(false);
        } else {
          // Demais linhas do grupo 1: readonly, recebem o valor da primeira linha
          if (cL) cell(cL).set(def.lacre), cell(cL).lock(true);
          if (cE) cell(cE).set(def.etiqueta), cell(cE).lock(true);
        }
      }else{
        // GRUPO 2 (abaixo do split)
        addClass(r, 'split-below');
        if (i === splitIndex + 1) {
          // Primeira linha do grupo 2: editavel (define o valor do grupo)
          if (cL) cell(cL).set(''), cell(cL).lock(false);
          if (cE) cell(cE).set(''), cell(cE).lock(false);
        } else {
          // Demais linhas do grupo 2: readonly, receberao o valor da primeira linha do grupo 2
          if (cL) cell(cL).set(''), cell(cL).lock(true);
          if (cE) cell(cE).set(''), cell(cE).lock(true);
        }
      }
    }
    var hid = document.getElementById('central_split_index');
    if (!hid){
      hid = document.createElement('input');
      hid.type='hidden'; hid.name='central_split_index'; hid.id='central_split_index';
      document.body.appendChild(hid);
    }
    hid.value = String(splitIndex);
    
    // Configurar propagacao automatica para ambos os grupos
    configurarPropagacaoGrupos(tbl, splitIndex, idxL, idxE);
    
    // VERSAO 3 CORRIGIDA: Aplicar valores imediatamente apos ativar split
    // Propagar valores existentes da primeira linha de cada grupo para as demais
    propagarValoresIniciais(tbl, splitIndex, idxL, idxE);
  }
  
  // Funcao para propagar valores iniciais imediatamente apos ativar o split
  function propagarValoresIniciais(tbl, splitIndex, idxL, idxE) {
    var rs = rowsOf(tbl);
    if (!rs.length) return;
    
    // GRUPO 1: Propagar valores da linha 0 para linhas 1 ate splitIndex
    if (rs[0]) {
      var valorL1 = '', valorE1 = '';
      if (idxL >= 0 && rs[0].children[idxL]) {
        var inp = rs[0].children[idxL].querySelector('input');
        if (inp) valorL1 = inp.value || '';
      }
      if (idxE >= 0 && rs[0].children[idxE]) {
        var inp = rs[0].children[idxE].querySelector('input');
        if (inp) valorE1 = inp.value || '';
      }
      
      for (var i = 1; i <= splitIndex && i < rs.length; i++) {
        if (idxL >= 0 && rs[i].children[idxL]) {
          var c = rs[i].children[idxL];
          cell(c).set(valorL1);
        }
        if (idxE >= 0 && rs[i].children[idxE]) {
          var c = rs[i].children[idxE];
          cell(c).set(valorE1);
        }
      }
    }
    
    // GRUPO 2: Propagar valores da linha splitIndex+1 para linhas splitIndex+2 ate o final
    var g2Start = splitIndex + 1;
    if (g2Start < rs.length && rs[g2Start]) {
      var valorL2 = '', valorE2 = '';
      if (idxL >= 0 && rs[g2Start].children[idxL]) {
        var inp = rs[g2Start].children[idxL].querySelector('input');
        if (inp) valorL2 = inp.value || '';
      }
      if (idxE >= 0 && rs[g2Start].children[idxE]) {
        var inp = rs[g2Start].children[idxE].querySelector('input');
        if (inp) valorE2 = inp.value || '';
      }
      
      for (var i = g2Start + 1; i < rs.length; i++) {
        if (idxL >= 0 && rs[i].children[idxL]) {
          var c = rs[i].children[idxL];
          cell(c).set(valorL2);
        }
        if (idxE >= 0 && rs[i].children[idxE]) {
          var c = rs[i].children[idxE];
          cell(c).set(valorE2);
        }
      }
    }
  }
  
  // Funcao para propagar valores dentro de cada grupo do split
  // VERSAO 3 CORRIGIDA: Propaga cada coluna INDEPENDENTEMENTE
  // Re-query DOM toda vez, sem usar variavel de estado
  
  function configurarPropagacaoGrupos(tbl, splitIndex, idxL, idxE) {
    // Re-query linhas da tabela toda vez
    var rs = rowsOf(tbl);
    if (!rs.length) return;
    
    // Funcao para propagar valor de UMA coluna para um grupo
    function propagarColunaParaGrupo(colIdx, valor, startIdx, endIdx) {
      if (colIdx < 0) return;
      var linhas = rowsOf(tbl); // Re-query para pegar estado atual
      for (var i = startIdx; i <= endIdx && i < linhas.length; i++) {
        var r = linhas[i];
        var c = r.children[colIdx];
        if (c) cell(c).set(valor);
      }
    }
    
    // Funcao para criar listener com closure correta
    function criarListener(colIdx, startIdx, endIdx) {
      return function() {
        propagarColunaParaGrupo(colIdx, this.value, startIdx, endIdx);
      };
    }
    
    // GRUPO 1: primeira linha (0) propaga para linhas 1 ate splitIndex
    // Remover listeners antigos e adicionar novos
    if (rs[0]) {
      if (idxL >= 0 && rs[0].children[idxL]) {
        var inpL1 = rs[0].children[idxL].querySelector('input');
        if (inpL1) {
          // Marcar com ID unico do split para evitar multiplos handlers
          var splitId = 'split_' + splitIndex + '_g1_l';
          if (inpL1.getAttribute('data-split-id') !== splitId) {
            inpL1.setAttribute('data-split-id', splitId);
            inpL1.addEventListener('input', criarListener(idxL, 1, splitIndex));
          }
        }
      }
      if (idxE >= 0 && rs[0].children[idxE]) {
        var inpE1 = rs[0].children[idxE].querySelector('input');
        if (inpE1) {
          var splitId = 'split_' + splitIndex + '_g1_e';
          if (inpE1.getAttribute('data-split-id') !== splitId) {
            inpE1.setAttribute('data-split-id', splitId);
            inpE1.addEventListener('input', criarListener(idxE, 1, splitIndex));
          }
        }
      }
    }
    
    // GRUPO 2: primeira linha (splitIndex+1) propaga para linhas (splitIndex+2) ate o final
    var g2Start = splitIndex + 1;
    if (g2Start < rs.length && rs[g2Start]) {
      var g2End = rs.length - 1;
      if (idxL >= 0 && rs[g2Start].children[idxL]) {
        var inpL2 = rs[g2Start].children[idxL].querySelector('input');
        if (inpL2) {
          var splitId = 'split_' + splitIndex + '_g2_l';
          if (inpL2.getAttribute('data-split-id') !== splitId) {
            inpL2.setAttribute('data-split-id', splitId);
            inpL2.addEventListener('input', criarListener(idxL, g2Start + 1, g2End));
          }
        }
      }
      if (idxE >= 0 && rs[g2Start].children[idxE]) {
        var inpE2 = rs[g2Start].children[idxE].querySelector('input');
        if (inpE2) {
          var splitId = 'split_' + splitIndex + '_g2_e';
          if (inpE2.getAttribute('data-split-id') !== splitId) {
            inpE2.setAttribute('data-split-id', splitId);
            inpE2.addEventListener('input', criarListener(idxE, g2Start + 1, g2End));
          }
        }
      }
    }
  }
  function removeSplit(tbl){
    var rs = rowsOf(tbl); if (!rs.length) return;
    var idxL = indexByHeader(tbl,'lacre correios');
    var idxE = indexByHeader(tbl,'etiqueta correios');
    var def = defaults(tbl, idxL, idxE);
    for (var i=0;i<rs.length;i++){
      var r = rs[i];
      var cL = (idxL>=0) ? r.children[idxL] : null;
      var cE = (idxE>=0) ? r.children[idxE] : null;
      if (cL) cell(cL).set(def.lacre), cell(cL).lock(true);
      if (cE) cell(cE).set(def.etiqueta), cell(cE).lock(true);
      removeClass(r, 'split-below');
    }
    var hid = document.getElementById('central_split_index');
    if (hid) hid.value = '';
  }
  function mount(){
    var btn = ensureSplitButton();
    var tbl = ensureCentralId();
    if (!tbl){
      if (btn) btn.addEventListener('click', function(){ alert('Tabela da CENTRAL IIPR não encontrada (#tblCentralIIPR).'); });
      return;
    }
    // Autofill abaixo do split (copia exatamente o valor digitado)
(function enableAutoFillBelowSplit(){
  var idxL = indexByHeader(tbl,'lacre correios');
  var idxE = indexByHeader(tbl,'etiqueta correios');
  if (idxL<0 && idxE<0) return;

  // Funcao auxiliar para encontrar elemento pai pelo nome de tag (substitui closest)
  function findParent(el, tagName) {
    var current = el;
    while (current && current.parentNode) {
      current = current.parentNode;
      if (current.tagName && current.tagName.toUpperCase() === tagName.toUpperCase()) {
        return current;
      }
    }
    return null;
  }

  // Funcao auxiliar para encontrar indice de elemento em lista (substitui indexOf)
  function findIndex(list, element) {
    for (var i = 0; i < list.length; i++) {
      if (list[i] === element) return i;
    }
    return -1;
  }

  // Normaliza inputs das colunas-alvo (evita truncar)
  var rs = rowsOf(tbl);
  var colsToCheck = [idxL, idxE];
  for (var trIdx = 0; trIdx < rs.length; trIdx++) {
    var tr = rs[trIdx];
    for (var colIdx = 0; colIdx < colsToCheck.length; colIdx++) {
      var col = colsToCheck[colIdx];
      if (col < 0) continue;
      var td = tr.children[col]; if (!td) continue;
      var inp = td.querySelector('input,textarea'); if (!inp) continue;
      inp.type = 'text';
      inp.inputMode = 'numeric';
      inp.maxLength = (col === idxE ? 35 : 10);
    }
  }

  // VERSAO 3: Handler global - so ativo quando NAO ha split
  // Quando split ativo, a propagacao e feita pelos listeners especificos de configurarPropagacaoGrupos
  tbl.addEventListener('input', function(ev){
    var target = ev.target;
    if (target.tagName!=='INPUT' && target.tagName!=='TEXTAREA') return;

    var splitIndex = parseInt((document.getElementById('central_split_index')||{value:-1}).value,10);
    
    // Se ha split ativo, ignora - propagacao gerenciada por configurarPropagacaoGrupos
    if (!isNaN(splitIndex) && splitIndex >= 0) return;

    var td = findParent(target, 'td'); if (!td) return;
    var tr = findParent(target, 'tr'); if (!tr) return;

    var rowIndex = findIndex(rs, tr);
    // Sem split: so a primeira linha propaga
    if (rowIndex !== 0) return;

    var colIndex = findIndex(tr.children, td);
    var isCorr   = (colIndex===idxL || colIndex===idxE);
    if (!isCorr) return;

    var val = String(target.value || target.textContent || '');

    for (var i=1;i<rs.length;i++){
      var cellTarget = rs[i].children[colIndex];
      if (!cellTarget) continue;
      var inp = cellTarget.querySelector('input,textarea');

      if (inp){
        inp.type = 'text';
        inp.inputMode = 'numeric';
        inp.maxLength = (colIndex===idxE ? 35 : 10);
        inp.value = val;
        inp.setAttribute('value', val);
      } else {
        cellTarget.textContent = val;
      }
    }
  });
})();


    if (btn){
      btn.addEventListener('click', function(){
        var rows = rowsOf(tbl);
        var labels = [];
        for (var i=0;i<rows.length;i++){
          var td = rows[i].children[0];
          var txt = (td ? (td.textContent||'').trim() : '') || ('Linha '+(i+1));
          labels.push(txt);
        }
        var list = document.getElementById('splitCentralList');
        list.innerHTML = '';
        for (var li = 0; li < labels.length; li++) {
          var d = document.createElement('div');
          d.className = 'row';
          d.innerHTML = '<input type="radio" name="split_row" value="'+li+'"> <span>'+labels[li]+'</span>';
          list.appendChild(d);
        }
        var modal = document.getElementById('splitCentralModal');
        document.getElementById('splitCentralCancel').onclick = function(){ modal.style.display='none'; };
        document.getElementById('splitCentralApply').onclick = function(){
          var sel = modal.querySelector('input[name="split_row"]:checked');
          if (!sel){ alert('Selecione o posto onde começa o segundo malote.'); return; }
          applyAt(tbl, parseInt(sel.value,10));
          modal.style.display='none';
        };
        modal.style.display = 'flex';
      });
      btn.addEventListener('contextmenu', function(e){ e.preventDefault(); removeSplit(tbl); });
    }
  }
  if (document.readyState==='loading') document.addEventListener('DOMContentLoaded', mount);
  else mount();
})();
</script>
<!-- SPLIT CENTRAL IIPR END -->

<!-- COSEP: begin endereco payload enrichment -->
<?php
$__COSEP_enderecosPoupa = array();
try {
    $___pdo_addr = isset($pdo_controle) ? $pdo_controle : (isset($pdo) ? $pdo : null);
    if ($___pdo_addr) {
        $___sql = "SELECT LPAD(CAST(posto AS UNSIGNED),3,'0') AS p3, endereco
                   FROM ciRegionais
                   WHERE REPLACE(LOWER(entrega),' ','') IN ('poupa-tempo','poupatempo')";
        $___st = $___pdo_addr->query($___sql);
        if ($___st) {
            foreach ($___st as $___row) {
                $__COSEP_enderecosPoupa[$___row['p3']] = $___row['endereco'];
            }
        }
    }
} catch (Exception $e) {
}
?>
<script>
(function(){
  try {
    if (!window.ENDERECOS_PTP) {
            window.ENDERECOS_PTP = <?php echo json_encode_legado_seguro($__COSEP_enderecosPoupa, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
    }
  } catch (e) {}

  // Funcao auxiliar para preencher string com zeros a esquerda (compativel com navegadores antigos)
  function padStart(str, targetLen, padChar) {
    str = String(str);
    while (str.length < targetLen) {
      str = padChar + str;
    }
    return str;
  }

  document.addEventListener('submit', function(ev){
    try{
      var f = ev.target;
      if (!f || !f.action) return;
      if (!/modelo_oficio_poupa_tempo\.php(\?|$)/.test(f.action)) return;
      var inp = f.querySelector("input[name='poupatempo_payload']");
      if (!inp || !inp.value) return;
      var data = JSON.parse(inp.value);
      if (Array.isArray(data)) {
        for (var di = 0; di < data.length; di++) {
          var it = data[di];
          var code = (it.codigo || it.posto || "").toString();
          var m = code.match(/\d{1,3}/);
          var p3 = m ? padStart(m[0], 3, "0") : "";
          if (!it.endereco && p3 && window.ENDERECOS_PTP && window.ENDERECOS_PTP[p3]) {
            it.endereco = window.ENDERECOS_PTP[p3];
          }
        }
        inp.value = JSON.stringify(data);
      }
    }catch(e){}
  }, true);
})();
</script>
<script type="text/javascript">
(function(){
  if (window.__btnSalvarPT_injected) return; // evita duplicar
  window.__btnSalvarPT_injected = true;

  function norm(t){return (t||'').toLowerCase().replace(/\s+/g,' ').trim();}

    // 1) Tenta achar o botão "âncora" do modo PT com etiqueta Correios
  function acharReferencia(){
    // tente por id (se você colocou id no botão manualmente)
    var byId = document.getElementById('btnGerarOficioPT');
    if (byId) return byId;

    // fallback por texto visível
        var labels = ['Ofício PT com Etiqueta Correios','Oficio PT com Etiqueta Correios'];
    var nodes = document.querySelectorAll('a,button');
    for (var i=0;i<nodes.length;i++){
      if (labels.indexOf(nodes[i].textContent.trim()) >= 0) return nodes[i];
            if (norm(nodes[i].textContent) === norm('Ofício PT com Etiqueta Correios')) return nodes[i];
    }
    return null;
  }

  var ref = acharReferencia();
  if (!ref || !ref.parentNode){
    // Se não achou, mostra um log e não quebra nada
    console.log('[Salvar PT] Botão de referência não encontrado.');
    return;
  }

    // 2) Cria o botão do modo PT em branco com o mesmo estilo do botão de referência
  var btn = document.createElement('button');
  btn.type = 'button';
    btn.id = 'btnOficioPTBranco';
    btn.textContent = 'Ofício Poupa Tempo em Branco';
  btn.className = ref.className || '';        // copia estilo
  btn.style.marginLeft = '8px';               // pequeno espaçamento
  btn.style.textDecoration = 'none';          // tira sublinhado, se herdar <a>
  ref.parentNode.insertBefore(btn, ref.nextSibling);

  // 3) Função para coletar as linhas do Poupa Tempo
  function coletarPT(){
    // Se você já tem uma função que o "Gerar Ofício" usa, reaproveite:
    if (typeof window.coletarPoupaTempoPayload === 'function'){
      try { return window.coletarPoupaTempoPayload(); } catch(e){}
    }
    // Fallback: varre uma tabela com id típico
    var itens = [];
    var tbl = document.querySelector("#tabela-poupa-tempo") ||
              document.querySelector("table[id*='poupa']") ||
              document.querySelector("table[id*='tempo']");
    if (!tbl) return itens;

    var trs = tbl.querySelectorAll('tbody tr');
    for (var i=0;i<trs.length;i++){
      var tds = trs[i].children;
      if (!tds || tds.length < 3) continue;

      var codigo = (tds[0].textContent||'').trim(); // ex: "028 – Curitiba"
      var qtd = 0;
      var elQ = tds[1].querySelector('input,textarea');
      if (elQ){
        var raw = (elQ.value||elQ.textContent||'');
        raw = raw.replace(/[^\d]/g,'');
        qtd = parseInt(raw,10)||0;
      } else {
        var raw2 = (tds[1].textContent||'').replace(/[^\d]/g,'');
        qtd = parseInt(raw2,10)||0;
      }

      var lacre = '';
      var elL = tds[2].querySelector('input,textarea');
      if (elL) { lacre = elL.value||elL.textContent||''; }
      else { lacre = (tds[2].textContent||'').trim(); }

      // etiqueta pode estar na 4ª/5ª col., se existir na sua grade
      var etiqueta = null;
      var tdEtiq = tds[4] || null;
      if (tdEtiq){
        var inpE = tdEtiq.querySelector('input,textarea');
        if (inpE) etiqueta = inpE.value||'';
      }

      itens.push({codigo: codigo, nome: codigo, quantidade: qtd, lacre: lacre, etiqueta: etiqueta});
    }
    return itens;
  }

  // 4) Datas: pega os inputs name="datas[]"
  function coletarDatas(){
    var ds = document.querySelectorAll("input[name='datas[]']");
    var v = [];
    for (var i=0;i<ds.length;i++){
      var x = (ds[i].value||'').trim();
      if (x) v.push(x);
    }
    // fallback tenta um input de data padrão da sua tela
    if (!v.length){
      var d1 = document.getElementById('data_inserir');
      if (d1 && d1.value) v.push(d1.value);
    }
    return v;
  }

    // 5) Ao clicar, abre o modelo PT em branco (legado)
    btn.onclick = function(){
        var datas = coletarDatas();
        var f = document.createElement('form');
        f.method = 'post';
        f.action = 'modelo_oficio_poupa_tempo.php';
        f.target = '_blank';

        var a = document.createElement('input'); a.type='hidden'; a.name='pt_blank'; a.value='1'; f.appendChild(a);
        var b = document.createElement('input'); b.type='hidden'; b.name='pt_datas'; b.value=datas.join(','); f.appendChild(b);
        var c = document.createElement('input'); c.type='hidden'; c.name='pt_modo_visual'; c.value='branco'; f.appendChild(c);
        document.body.appendChild(f);
        f.submit();
        document.body.removeChild(f);
    };

  // v8.14.9: Função para mostrar modal de confirmação Poupa Tempo
  function mostrarModalConfirmacaoPT(itens, datas) {
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;';
    
    var modal = document.createElement('div');
    modal.style.cssText = 'background:white;padding:30px;border-radius:8px;max-width:500px;box-shadow:0 4px 20px rgba(0,0,0,0.3);';
    
    var titulo = document.createElement('h3');
    titulo.textContent = 'Como deseja gravar o ofício?';
    titulo.style.cssText = 'margin-top:0;color:#333;font-size:18px;margin-bottom:20px;';
    
    var texto = document.createElement('p');
    texto.innerHTML = 
        '<b>Sobrescrever:</b> Atualiza o ofício existente (mesmo número).<br><br>' +
        '<b>Criar Novo:</b> Mantém ofício anterior e cria outro com novo número.<br><br>' +
        'Escolha uma opção:';
    texto.style.cssText = 'margin-bottom:25px;line-height:1.6;color:#555;';
    
    var botoes = document.createElement('div');
    botoes.style.cssText = 'display:flex;gap:10px;justify-content:center;';
    
    var btnSobrescrever = document.createElement('button');
    btnSobrescrever.textContent = 'Sobrescrever';
    btnSobrescrever.style.cssText = 'background:#ff9800;color:white;border:none;padding:12px 24px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:bold;';
    btnSobrescrever.onclick = function() {
        document.body.removeChild(overlay);
        gravarOficioPT(itens, datas, 'sobrescrever');
    };
    
    var btnCriarNovo = document.createElement('button');
    btnCriarNovo.textContent = 'Criar Novo';
    btnCriarNovo.style.cssText = 'background:#28a745;color:white;border:none;padding:12px 24px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:bold;';
    btnCriarNovo.onclick = function() {
        document.body.removeChild(overlay);
        gravarOficioPT(itens, datas, 'novo');
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

  // v8.14.9: Função que efetivamente grava o ofício PT
  function gravarOficioPT(itens, datas, modo) {
    var f = document.createElement('form');
    f.method = 'post';
    f.action = ''; // mesma página
    var a = document.createElement('input'); a.type='hidden'; a.name='acao';       a.value='salvar_oficio_pt'; f.appendChild(a);
    var b = document.createElement('input'); b.type='hidden'; b.name='datas_str';  b.value=datas.join(',');    f.appendChild(b);
    var c = document.createElement('input'); c.type='hidden'; c.name='payload_json'; c.value=JSON.stringify(itens); f.appendChild(c);
    var d = document.createElement('input'); d.type='hidden'; d.name='modo_oficio'; d.value=modo; f.appendChild(d);
    document.body.appendChild(f);
    f.submit();
  }

})();
</script>

<!-- COSEP: end endereco payload enrichment -->

<?php
// v8.14.2: Auto-impressão após salvar e recarregar
if (isset($_SESSION['auto_imprimir_correios']) && $_SESSION['auto_imprimir_correios'] === true) {
    $ultimo_oficio = isset($_SESSION['ultimo_oficio_salvo']) ? (int)$_SESSION['ultimo_oficio_salvo'] : 0;
    // Limpar flags para não imprimir novamente
    unset($_SESSION['auto_imprimir_correios']);
    unset($_SESSION['ultimo_oficio_salvo']);
    
    echo "<script>
    // v8.14.2: Auto-impressão após reload (dados já carregados do BD)
    (function() {
        // Aguardar carregamento completo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', autoImprimirCorreios);
        } else {
            autoImprimirCorreios();
        }
        
        function autoImprimirCorreios() {
            // Pequeno delay para garantir renderização
            setTimeout(function() {
                try { if (typeof restaurarEstadoEtiquetasCorreios === 'function') { restaurarEstadoEtiquetasCorreios(); } } catch (e) { /* ignore */ }
                try { if (typeof preencherInputsParaImpressao === 'function') { preencherInputsParaImpressao(); } } catch (e) { /* ignore */ }
                window.print();
            }, 500);
        }
    })();
    </script>";
}
?>

<?php include __DIR__ . '/includes/util_botoes_fixos.php'; ?>

<?php include __DIR__ . '/includes/_acess.php'; ?>
</body>
</html>