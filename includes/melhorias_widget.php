<?php
/* melhorias_widget.php — v1.2.2 */
$melhoriasWidgetPagina = basename(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'pagina');
$melhoriasWidgetPagina = htmlspecialchars($melhoriasWidgetPagina, ENT_QUOTES, 'UTF-8');
?>
<style type="text/css">
.melhorias-widget-botao {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 9998;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border: 0;
    border-radius: 999px;
    background: #184e77;
    color: #fff;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.22);
    cursor: pointer;
    font-size: 11px;
    font-weight: bold;
}
.melhorias-widget-botao:hover {
    background: #0f3550;
}
.melhorias-widget-contador {
    display: inline-block;
    min-width: 22px;
    padding: 2px 6px;
    border-radius: 999px;
    background: #f6bd60;
    color: #1d1d1d;
    text-align: center;
    font-size: 11px;
}
.melhorias-widget-fundo {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    background: rgba(0, 0, 0, 0.45);
}
.melhorias-widget-fundo.aberto {
    display: block;
}
.melhorias-widget-painel {
    position: absolute;
    right: 18px;
    bottom: 72px;
    width: 380px;
    max-width: calc(100vw - 24px);
    max-height: calc(100vh - 110px);
    overflow: hidden;
    border-radius: 18px;
    background: #f7f4ea;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
    color: #1e293b;
}
.melhorias-widget-topo {
    padding: 16px 18px 12px 18px;
    background: linear-gradient(135deg, #184e77 0%, #1e6091 100%);
    color: #fff;
}
.melhorias-widget-topo h3 {
    margin: 0;
    font-size: 18px;
}
.melhorias-widget-topo p {
    margin: 6px 0 0 0;
    font-size: 12px;
    opacity: 0.92;
}
.melhorias-widget-corpo {
    padding: 14px 18px 18px 18px;
    overflow: auto;
    max-height: calc(100vh - 220px);
}
.melhorias-widget-resumo {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;
}
.melhorias-widget-card {
    flex: 1;
    padding: 10px 12px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid #dbe7f0;
}
.melhorias-widget-card strong {
    display: block;
    font-size: 18px;
    color: #184e77;
}
.melhorias-widget-card span {
    font-size: 11px;
    color: #475569;
}
.melhorias-widget-form label {
    display: block;
    margin-bottom: 10px;
    font-size: 12px;
    font-weight: bold;
    color: #334155;
}
.melhorias-widget-form input,
.melhorias-widget-form textarea,
.melhorias-widget-form select {
    width: 100%;
    margin-top: 4px;
    padding: 8px 10px;
    border: 1px solid #b9c8d6;
    border-radius: 8px;
    box-sizing: border-box;
    font-size: 12px;
    background: #fff;
    color: #0f172a;
}
.melhorias-widget-form textarea {
    min-height: 72px;
    resize: vertical;
}
.melhorias-widget-acoes {
    display: flex;
    gap: 8px;
    margin: 10px 0 16px 0;
}
.melhorias-widget-acoes button,
.melhorias-widget-item button,
.melhorias-widget-item select {
    font-size: 12px;
}
.melhorias-widget-acoes button {
    border: 0;
    border-radius: 8px;
    padding: 9px 12px;
    cursor: pointer;
}
.melhorias-widget-salvar {
    background: #2a9d8f;
    color: #fff;
}
.melhorias-widget-fechar {
    background: #dbe7f0;
    color: #234;
}
.melhorias-widget-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.melhorias-widget-item {
    padding: 12px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid #dbe7f0;
}
.melhorias-widget-item-topo {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: flex-start;
}
.melhorias-widget-item-titulo {
    margin: 0;
    font-size: 13px;
    color: #0f172a;
}
.melhorias-widget-item-meta {
    margin: 4px 0 0 0;
    font-size: 11px;
    color: #64748b;
}
.melhorias-widget-item-desc {
    margin: 10px 0;
    font-size: 12px;
    color: #334155;
    white-space: pre-wrap;
}
.melhorias-widget-item-rodape {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    align-items: center;
}
.melhorias-widget-item-rodape button {
    border: 0;
    border-radius: 8px;
    padding: 7px 10px;
    background: #f4d35e;
    color: #1d1d1d;
    cursor: pointer;
}
.melhorias-widget-vazio {
    padding: 16px;
    border: 1px dashed #b9c8d6;
    border-radius: 12px;
    background: #fff;
    color: #64748b;
    font-size: 12px;
    text-align: center;
}
.melhorias-widget-erro {
    margin-bottom: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    background: #fdecea;
    color: #9f2d20;
    font-size: 12px;
    display: none;
}
@media (max-width: 640px) {
    .melhorias-widget-painel {
        right: 12px;
        left: 12px;
        width: auto;
        bottom: 70px;
    }
    .melhorias-widget-botao {
        right: 12px;
        bottom: 12px;
    }
    .melhorias-widget-resumo,
    .melhorias-widget-acoes,
    .melhorias-widget-item-rodape {
        display: block;
    }
    .melhorias-widget-card,
    .melhorias-widget-acoes button,
    .melhorias-widget-item-rodape select,
    .melhorias-widget-item-rodape button {
        width: 100%;
        margin-bottom: 8px;
    }
}
@media print {
    .melhorias-widget-botao,
    .melhorias-widget-fundo {
        display: none !important;
    }
}
</style>

<button type="button" id="melhoriasWidgetBotao" class="melhorias-widget-botao" aria-haspopup="dialog" aria-controls="melhoriasWidgetPainel">
    Melhorias
    <span id="melhoriasWidgetContador" class="melhorias-widget-contador">0</span>
</button>

<div id="melhoriasWidgetFundo" class="melhorias-widget-fundo" aria-hidden="true">
    <div id="melhoriasWidgetPainel" class="melhorias-widget-painel" role="dialog" aria-modal="true" aria-labelledby="melhoriasWidgetTitulo">
        <div class="melhorias-widget-topo">
            <h3 id="melhoriasWidgetTitulo">Backlog de Melhorias</h3>
            <p>Use este painel para registrar pendencias e marcar o que ja foi implementado.</p>
        </div>
        <div class="melhorias-widget-corpo">
            <div class="melhorias-widget-resumo">
                <div class="melhorias-widget-card">
                    <strong id="melhoriasResumoPendentes">0</strong>
                    <span>Pendentes</span>
                </div>
                <div class="melhorias-widget-card">
                    <strong id="melhoriasResumoConcluidas">0</strong>
                    <span>Implementadas</span>
                </div>
            </div>

            <form id="melhoriasWidgetForm" class="melhorias-widget-form">
                <label>
                    Titulo
                    <input type="text" id="melhoriasTitulo" maxlength="120" placeholder="Ex.: destacar lotes conferidos na consulta">
                </label>
                <label>
                    Descricao
                    <textarea id="melhoriasDescricao" placeholder="Descreva a melhoria desejada"></textarea>
                </label>
                <label>
                    Status
                    <select id="melhoriasStatus">
                        <option value="pendente">Pendente</option>
                        <option value="implementado">Implementado</option>
                    </select>
                </label>
                <label>
                    Pagina
                    <input type="text" id="melhoriasPagina" value="<?php echo $melhoriasWidgetPagina; ?>">
                </label>
                <div class="melhorias-widget-acoes">
                    <button type="submit" class="melhorias-widget-salvar">Salvar item</button>
                    <button type="button" id="melhoriasWidgetFechar" class="melhorias-widget-fechar">Fechar</button>
                </div>
            </form>

            <div id="melhoriasWidgetErro" class="melhorias-widget-erro"></div>
            <div id="melhoriasWidgetLista" class="melhorias-widget-lista"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function() {
    if (window.__melhoriasWidgetInicializado) {
        return;
    }
    window.__melhoriasWidgetInicializado = true;

    var apiUrl = 'api/melhorias_widget_api.php';
    var botao = document.getElementById('melhoriasWidgetBotao');
    var fundo = document.getElementById('melhoriasWidgetFundo');
    var lista = document.getElementById('melhoriasWidgetLista');
    var form = document.getElementById('melhoriasWidgetForm');
    var titulo = document.getElementById('melhoriasTitulo');
    var descricao = document.getElementById('melhoriasDescricao');
    var status = document.getElementById('melhoriasStatus');
    var pagina = document.getElementById('melhoriasPagina');
    var contador = document.getElementById('melhoriasWidgetContador');
    var resumoPendentes = document.getElementById('melhoriasResumoPendentes');
    var resumoConcluidas = document.getElementById('melhoriasResumoConcluidas');
    var botaoFechar = document.getElementById('melhoriasWidgetFechar');
    var erroBox = document.getElementById('melhoriasWidgetErro');
    var itensCache = [];

    function mostrarErro(texto) {
        if (!erroBox) return;
        if (!texto) {
            erroBox.style.display = 'none';
            erroBox.innerHTML = '';
            return;
        }
        erroBox.style.display = 'block';
        erroBox.innerHTML = escapar(texto);
    }

    function requisicao(acao, dados, callback) {
        var xhr = new XMLHttpRequest();
        var payload = [];
        var chave;
        xhr.open('POST', apiUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.onreadystatechange = function() {
            var resposta;
            if (xhr.readyState !== 4) {
                return;
            }
            try {
                resposta = JSON.parse(xhr.responseText);
            } catch (e) {
                callback(false, { erro: 'Resposta invalida da API de melhorias.' });
                return;
            }
            if (xhr.status >= 200 && xhr.status < 300 && resposta && resposta.success) {
                callback(true, resposta);
                return;
            }
            callback(false, resposta || { erro: 'Falha ao acessar API de melhorias.' });
        };
        payload.push('acao=' + encodeURIComponent(acao));
        dados = dados || {};
        for (chave in dados) {
            if (Object.prototype.hasOwnProperty.call(dados, chave)) {
                payload.push(encodeURIComponent(chave) + '=' + encodeURIComponent(dados[chave]));
            }
        }
        xhr.send(payload.join('&'));
    }

    function carregarItens(callback) {
        requisicao('listar', {}, function(ok, resposta) {
            if (ok) {
                itensCache = resposta.itens || [];
                mostrarErro('');
                if (callback) callback(true, itensCache);
                return;
            }
            mostrarErro(resposta && resposta.erro ? resposta.erro : 'Nao foi possivel carregar as melhorias.');
            if (callback) callback(false, []);
        });
    }

    function atualizarResumo(itens) {
        var pendentes = 0;
        var concluidas = 0;
        var i;
        for (i = 0; i < itens.length; i++) {
            if (itens[i].status === 'implementado') {
                concluidas++;
            } else {
                pendentes++;
            }
        }
        contador.innerHTML = String(itens.length);
        resumoPendentes.innerHTML = String(pendentes);
        resumoConcluidas.innerHTML = String(concluidas);
    }

    function renderizar(itens) {
        var html = '';
        var i;
        itens = itens || itensCache || [];
        atualizarResumo(itens);

        if (!itens.length) {
            lista.innerHTML = '<div class="melhorias-widget-vazio">Nenhuma melhoria registrada ainda.</div>';
            return;
        }

        itens.sort(function(a, b) {
            return (b.atualizado_em || '').localeCompare(a.atualizado_em || '');
        });

        for (i = 0; i < itens.length; i++) {
            html += '' +
                '<div class="melhorias-widget-item" data-id="' + itens[i].id + '">' +
                    '<div class="melhorias-widget-item-topo">' +
                        '<div>' +
                            '<p class="melhorias-widget-item-titulo">' + escapar(itens[i].titulo) + '</p>' +
                            '<p class="melhorias-widget-item-meta">Pagina: ' + escapar(itens[i].pagina || '-') + ' | Atualizado: ' + escapar(formatarData(itens[i].atualizado_em)) + '</p>' +
                        '</div>' +
                        '<span class="melhorias-widget-contador">' + escapar(itens[i].status === 'implementado' ? 'OK' : 'P') + '</span>' +
                    '</div>' +
                    '<div class="melhorias-widget-item-desc">' + escapar(itens[i].descricao || '') + '</div>' +
                    '<div class="melhorias-widget-item-rodape">' +
                        '<select data-acao="status">' +
                            '<option value="pendente"' + (itens[i].status === 'pendente' ? ' selected' : '') + '>Pendente</option>' +
                            '<option value="implementado"' + (itens[i].status === 'implementado' ? ' selected' : '') + '>Implementado</option>' +
                        '</select>' +
                        '<button type="button" data-acao="excluir">Excluir</button>' +
                    '</div>' +
                '</div>';
        }

        lista.innerHTML = html;
    }

    function escapar(texto) {
        return String(texto || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/\n/g, '<br>');
    }

    function formatarData(valor) {
        if (!valor) {
            return '-';
        }
        var data = new Date(valor);
        if (isNaN(data.getTime())) {
            return valor;
        }
        return data.toLocaleString('pt-BR');
    }

    function abrir() {
        fundo.className = 'melhorias-widget-fundo aberto';
        fundo.setAttribute('aria-hidden', 'false');
        if (titulo) {
            titulo.focus();
        }
    }

    function fechar() {
        fundo.className = 'melhorias-widget-fundo';
        fundo.setAttribute('aria-hidden', 'true');
    }

    botao.onclick = abrir;
    if (botaoFechar) {
        botaoFechar.onclick = fechar;
    }

    fundo.onclick = function(event) {
        if (event.target === fundo) {
            fechar();
        }
    };

    if (form) {
        form.onsubmit = function(event) {
            var itens;
            var novoItem;
            event.preventDefault();
            if (!titulo.value || titulo.value.replace(/^\s+|\s+$/g, '') === '') {
                titulo.focus();
                return false;
            }

            mostrarErro('');
            requisicao('criar', {
                titulo: titulo.value.replace(/^\s+|\s+$/g, ''),
                descricao: descricao.value.replace(/^\s+|\s+$/g, ''),
                status: status.value,
                pagina: pagina.value.replace(/^\s+|\s+$/g, '')
            }, function(ok, resposta) {
                if (!ok) {
                    mostrarErro(resposta && resposta.erro ? resposta.erro : 'Falha ao salvar melhoria.');
                    return;
                }
                titulo.value = '';
                descricao.value = '';
                status.value = 'pendente';
                carregarItens(function(okLista, itensLista) {
                    if (okLista) {
                        renderizar(itensLista);
                        fechar();
                    }
                });
            });
            return false;
        };
    }

    if (lista) {
        lista.onchange = function(event) {
            var alvo = event.target;
            var item;
            var itens;
            var i;
            if (!alvo || alvo.getAttribute('data-acao') !== 'status') {
                return;
            }
            item = alvo;
            while (item && !item.getAttribute('data-id')) {
                item = item.parentNode;
            }
            if (!item) {
                return;
            }
            requisicao('atualizar_status', {
                id: item.getAttribute('data-id'),
                status: alvo.value
            }, function(ok, resposta) {
                if (!ok) {
                    mostrarErro(resposta && resposta.erro ? resposta.erro : 'Falha ao atualizar status.');
                    carregarItens(function(okLista, itensLista) {
                        if (okLista) {
                            renderizar(itensLista);
                        }
                    });
                    return;
                }
                carregarItens(function(okLista, itensLista) {
                    if (okLista) {
                        renderizar(itensLista);
                    }
                });
            });
        };

        lista.onclick = function(event) {
            var alvo = event.target;
            var item;
            if (!alvo || alvo.getAttribute('data-acao') !== 'excluir') {
                return;
            }
            item = alvo;
            while (item && !item.getAttribute('data-id')) {
                item = item.parentNode;
            }
            if (!item) {
                return;
            }
            requisicao('excluir', {
                id: item.getAttribute('data-id')
            }, function(ok, resposta) {
                if (!ok) {
                    mostrarErro(resposta && resposta.erro ? resposta.erro : 'Falha ao excluir melhoria.');
                    return;
                }
                carregarItens(function(okLista, itensLista) {
                    if (okLista) {
                        renderizar(itensLista);
                    }
                });
            });
        };
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            fechar();
        }
    });

    carregarItens(function(okLista, itensLista) {
        if (okLista) {
            renderizar(itensLista);
        } else {
            renderizar([]);
        }
    });
})();
</script>