<?php include('header.php'); ?>

<div class="dashboard">
    <?php include('sidebar.php'); ?>

    <main class="container">
        <h2 class="titulo">📦 Gerenciar Insumos</h2>
        
        <div class="botoes-menu" style="margin-bottom: 30px;">
            <button id="btn-novo-insumo" class="btn">➕ Novo Insumo</button>
            <button id="btn-verificar-alertas" class="btn">⚠️ Verificar Alertas</button>
            <button id="btn-estatisticas" class="btn">📊 Estatísticas</button>
        </div>

        <!-- Formulário para novo/editar insumo -->
        <div id="form-novo-insumo" style="display: none; margin-bottom: 30px;">
            <h3 id="titulo-form">Cadastrar Novo Insumo</h3>
            <form id="form-insumo" class="formulario">
                <input type="hidden" id="insumo_id" name="id">
                
                <label for="nome">Nome do Insumo:</label>
                <input type="text" id="nome" name="nome" required>

                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="3"></textarea>

                <label for="unidade_compra">Unidade de Compra:</label>
                <select id="unidade_compra" name="unidade_compra" required>
                    <option value="">Selecione...</option>
                    <option value="kg">Quilograma (kg) - será armazenado em gramas</option>
                    <option value="g">Grama (g)</option>
                    <option value="L">Litro (L) - será armazenado em mililitros</option>
                    <option value="ml">Mililitro (ml)</option>
                    <option value="un">Unidade (un)</option>
                    <option value="cx">Caixa (cx)</option>
                    <option value="pct">Pacote (pct)</option>
                </select>
                <small style="color: #666;">Nota: Insumos em kg serão armazenados em gramas, e em L serão armazenados em ml</small>

                <label for="estoque_atual">Estoque Atual (na unidade de compra):</label>
                <input type="number" id="estoque_atual" name="estoque_atual" step="0.001" value="0">

                <label for="estoque_minimo">Estoque Mínimo (na unidade de compra):</label>
                <input type="number" id="estoque_minimo" name="estoque_minimo" step="0.001" value="0">

                <label for="custo_unitario_atual">Custo Unitário Atual (R$):</label>
                <input type="number" id="custo_unitario_atual" name="custo_unitario_atual" step="0.01" value="0">
                <small style="color: #666;">Preço por unidade de compra (ex: R$/kg, R$/L, R$/un)</small>

                <label for="categoria">Categoria:</label>
                <input type="text" id="categoria" name="categoria" placeholder="Ex: Ingredientes Básicos">

                <label for="fornecedor">Fornecedor:</label>
                <input type="text" id="fornecedor" name="fornecedor">

                <hr style="margin: 20px 0;">
                <h4>📦 Dados do Lote Inicial (Opcional)</h4>
                <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">Preencha para cadastrar automaticamente um lote com controle de validade para este insumo.</p>

                <div id="campos-lote-inicial" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <label for="lote_inicial">Número do Lote:</label>
                        <input type="text" id="lote_inicial" name="lote" placeholder="Ex: L001">
                    </div>
                    <div>
                        <label for="quantidade_lote_inicial">Quantidade do Lote:</label>
                        <input type="number" id="quantidade_lote_inicial" name="quantidade_lote" step="0.001" placeholder="Mesmo que estoque atual">
                    </div>
                    <div>
                        <label for="data_fabricacao_inicial">Data de Fabricação:</label>
                        <input type="date" id="data_fabricacao_inicial" name="data_fabricacao">
                    </div>
                    <div>
                        <label for="data_validade_inicial">Data de Validade:</label>
                        <input type="date" id="data_validade_inicial" name="data_validade">
                    </div>
                </div>
                <label for="observacoes_lote_inicial" style="margin-top: 15px;">Observações do Lote:</label>
                <textarea id="observacoes_lote_inicial" name="observacoes" rows="2"></textarea>

                <button type="submit" class="btn-enviar" id="btn-salvar">Salvar Insumo</button>
                <button type="button" id="btn-cancelar" class="btn" style="background-color: #6c757d;">Cancelar</button>
            </form>
        </div>

        <!-- Lista de insumos -->
        <div id="lista-insumos">
            <h3>Lista de Insumos</h3>
            <div id="insumos-container"></div>
        </div>

        <!-- Alertas -->
        <div id="alertas-container" style="margin-top: 30px;">
            <h3>⚠️ Alertas de Estoque</h3>
            <div id="alertas-lista"></div>
        </div>

        <!-- Controle de Validade por Insumo -->
        <div id="controle-validade-insumos" style="margin-top: 30px; display:none;">
            <h3>📦 Controle de Validade</h3>
            <div id="lotes-insumo-container"></div>
        </div>

        <div id="modal-lotes-insumo" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#fff; padding:20px; border-radius:8px; width:90%; max-width:900px; max-height:90%; overflow:auto;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3 id="titulo-lotes-insumo">Lotes do Insumo</h3>
                    <button id="fechar-modal-lotes" class="btn">Fechar</button>
                </div>
                <div id="conteudo-lotes-insumo"></div>
                <hr style="margin:20px 0;">
                <h4>Adicionar novo lote</h4>
                <form id="form-lote-insumo" class="formulario">
                    <input type="hidden" id="insumo_id_lote_insumo" name="insumo_id">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
                        <div>
                            <label for="lote_insumo">Número do lote:</label>
                            <input type="text" id="lote_insumo" name="lote" placeholder="Ex: L001">
                        </div>
                        <div>
                            <label for="quantidade_lote_insumo">Quantidade do lote:</label>
                            <input type="number" id="quantidade_lote_insumo" name="quantidade_lote" step="0.001" required>
                        </div>
                        <div>
                            <label for="data_fabricacao_insumo">Data de fabricação:</label>
                            <input type="date" id="data_fabricacao_insumo" name="data_fabricacao">
                        </div>
                        <div>
                            <label for="data_validade_insumo">Data de validade:</label>
                            <input type="date" id="data_validade_insumo" name="data_validade" required>
                        </div>
                    </div>
                    <label for="observacoes_lote_insumo">Observações:</label>
                    <textarea id="observacoes_lote_insumo" name="observacoes" rows="2"></textarea>
                    <button type="submit" class="btn-enviar">Cadastrar lote</button>
                    <button type="button" id="btn-cancelar-lote-insumo" class="btn" style="background-color:#6c757d;">Cancelar</button>
                </form>
            </div>
        </div>

        <!-- Estatísticas -->
        <div id="estatisticas-container" style="margin-top: 30px; display: none;">
            <h3>📊 Estatísticas</h3>
            <div id="estatisticas-conteudo"></div>
        </div>

        <div id="mensagem"></div>
    </main>
</div>
<script>
let insumos = [];
let alertas = [];
let lotesInsumo = [];
let insumoSelecionadoParaLotes = null;
let modoEdicao = false;

// Função para converter valor de exibição (g/ml para kg/L)
function converterParaExibicao(valor, unidade_compra, fator_conversao) {
    if(unidade_compra === 'kg' && fator_conversao === 1000) {
        return (valor / 1000).toFixed(3);
    } else if(unidade_compra === 'L' && fator_conversao === 1000) {
        return (valor / 1000).toFixed(3);
    }
    return parseFloat(valor).toFixed(3);
}

// Função para obter unidade de exibição
function obterUnidadeExibicao(unidade_compra, fator_conversao) {
    if(unidade_compra === 'kg' && fator_conversao === 1000) {
        return 'kg';
    } else if(unidade_compra === 'L' && fator_conversao === 1000) {
        return 'L';
    }
    return unidade_compra;
}

// Carregar insumos
async function carregarInsumos() {
    try {
        const response = await fetch('../api/insumos.php');
        const data = await response.json();
        
        if(data.success) {
            insumos = data.data;
            exibirInsumos();
        }
    } catch(error) {
        console.error('Erro ao carregar insumos:', error);
    }
}

// Exibir insumos na tela
function exibirInsumos() {
    const container = document.getElementById('insumos-container');
    container.innerHTML = '';

    insumos.forEach(insumo => {
        const div = document.createElement('div');
        div.className = 'insumo-card';
        
        // Converter valores para exibição
        const estoqueExibicao = converterParaExibicao(insumo.estoque_atual, insumo.unidade_compra, insumo.fator_conversao);
        const minimoExibicao = converterParaExibicao(insumo.estoque_minimo, insumo.unidade_compra, insumo.fator_conversao);
        const unidadeExibicao = obterUnidadeExibicao(insumo.unidade_compra, insumo.fator_conversao);
        
        div.style.cssText = `
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background-color: ${parseFloat(insumo.estoque_atual) <= parseFloat(insumo.estoque_minimo) ? '#fff3cd' : '#fff'};
        `;
        
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h4>${insumo.nome}</h4>
                    <p><strong>Categoria:</strong> ${insumo.categoria || 'Não definida'}</p>
                    <p><strong>Estoque:</strong> ${estoqueExibicao} ${unidadeExibicao}</p>
                    <p><strong>Mínimo:</strong> ${minimoExibicao} ${unidadeExibicao}</p>
                    <p><strong>Custo Unitário:</strong> R$ ${parseFloat(insumo.custo_unitario_atual).toFixed(2)}/${unidadeExibicao}</p>
                    <p><strong>Fornecedor:</strong> ${insumo.fornecedor || 'Não informado'}</p>
                </div>
                <div>
                    <button onclick="editarInsumo(${insumo.id})" class="btn" style="margin: 2px;">✏️ Editar</button>
                    <button onclick="abrirLotesInsumo(${insumo.id}, '${insumo.nome.replace("'", "\'")}')" class="btn" style="background-color: #ffc107; color:#000; margin: 2px;">📦 Lotes</button>
                    <button onclick="excluirInsumo(${insumo.id})" class="btn" style="background-color: #dc3545; margin: 2px;">🗑️ Excluir</button>
                </div>
            </div>
        `;
        
        container.appendChild(div);
    });
}

// Editar insumo
async function editarInsumo(id) {
    try {
        const response = await fetch(`../api/insumos.php?id=${id}`);
        const data = await response.json();
        
        if(data.success) {
            const insumo = data.data;
            modoEdicao = true;
            
            // Preencher formulário
            document.getElementById('insumo_id').value = insumo.id;
            document.getElementById('nome').value = insumo.nome;
            document.getElementById('descricao').value = insumo.descricao || '';
            document.getElementById('unidade_compra').value = insumo.unidade_compra;
            document.getElementById('custo_unitario_atual').value = insumo.custo_unitario_atual;
            document.getElementById('categoria').value = insumo.categoria || '';
            document.getElementById('fornecedor').value = insumo.fornecedor || '';
            
            // Converter valores para exibição
            const estoqueExibicao = converterParaExibicao(insumo.estoque_atual, insumo.unidade_compra, insumo.fator_conversao);
            const minimoExibicao = converterParaExibicao(insumo.estoque_minimo, insumo.unidade_compra, insumo.fator_conversao);
            
            document.getElementById('estoque_atual').value = estoqueExibicao;
            document.getElementById('estoque_minimo').value = minimoExibicao;
            
            document.getElementById('titulo-form').textContent = 'Editar Insumo';
            document.getElementById('btn-salvar').textContent = 'Atualizar Insumo';
            document.getElementById('form-novo-insumo').style.display = 'block';
            // Esconder campos de lote em modo de edição
            document.querySelector('#form-novo-insumo h4').style.display = 'none';
            document.querySelector('#form-novo-insumo p').style.display = 'none';
            document.getElementById('campos-lote-inicial').style.display = 'none';
            document.getElementById('observacoes_lote_inicial').parentElement.style.display = 'none';
        }
    } catch(error) {
        console.error('Erro ao carregar insumo:', error);
        mostrarMensagem('Erro ao carregar insumo para edição', 'error');
    }
}

// Excluir insumo
async function excluirInsumo(id) {
    if(!confirm('Tem certeza que deseja excluir este insumo? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        const response = await fetch('../api/insumos.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        
        if(data.success) {
            mostrarMensagem('Insumo excluído com sucesso!', 'success');
            carregarInsumos();
        } else {
            mostrarMensagem(data.message, 'error');
        }
    } catch(error) {
        console.error('Erro:', error);
        mostrarMensagem('Erro ao excluir insumo', 'error');
    }
}

// Verificar alertas
async function verificarAlertas() {
    try {
        const response = await fetch('../api/alertas.php?verificar_alertas=1');
        const data = await response.json();
        
        if(data.success) {
            mostrarMensagem(`Verificação concluída! ${data.data.alertas_gerados} novos alertas gerados.`, 'success');
            carregarAlertas();
        }
    } catch(error) {
        console.error('Erro ao verificar alertas:', error);
        mostrarMensagem('Erro ao verificar alertas', 'error');
    }
}

// Carregar alertas
async function carregarAlertas() {
    try {
        const response = await fetch('../api/alertas.php?nao_visualizados=1');
        const data = await response.json();
        
        if(data.success) {
            alertas = data.data;
            exibirAlertas();
        }
    } catch(error) {
        console.error('Erro ao carregar alertas:', error);
    }
}

async function abrirLotesInsumo(insumoId, insumoNome) {
    insumoSelecionadoParaLotes = { id: insumoId, nome: insumoNome };
    document.getElementById('titulo-lotes-insumo').textContent = `Lotes do insumo: ${insumoNome}`;
    document.getElementById('insumo_id_lote_insumo').value = insumoId;
    document.getElementById('quantidade_lote_insumo').value = '';
    document.getElementById('data_fabricacao_insumo').value = '';
    document.getElementById('data_validade_insumo').value = '';
    document.getElementById('observacoes_lote_insumo').value = '';
    document.getElementById('modal-lotes-insumo').style.display = 'flex';
    await carregarLotesPorInsumo(insumoId);
}

async function carregarLotesPorInsumo(insumoId) {
    try {
        const response = await fetch(`../api/validade.php?por_insumo=1&insumo_id=${insumoId}`);
        const data = await response.json();
        if(data.success) {
            lotesInsumo = data.data;
            exibirLotesInsumo();
        }
    } catch(error) {
        console.error('Erro ao carregar lotes do insumo:', error);
        mostrarMensagem('Erro ao carregar lotes do insumo', 'error');
    }
}

function exibirLotesInsumo() {
    const container = document.getElementById('conteudo-lotes-insumo');
    container.innerHTML = '';

    if(lotesInsumo.length === 0) {
        container.innerHTML = '<p>Nenhum lote cadastrado para este insumo.</p>';
        return;
    }

    lotesInsumo.forEach(lote => {
        const card = document.createElement('div');
        card.style.cssText = `
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background-color: ${lote.status === 'vencido' ? '#f8d7da' : lote.status === 'proximo_vencer' ? '#fff3cd' : '#d4edda'};
        `;

        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                <div>
                    <h4>Lote: ${lote.lote || 'Sem código'}</h4>
                    <p><strong>Status:</strong> ${lote.status}</p>
                    <p><strong>Validade:</strong> ${new Date(lote.data_validade).toLocaleDateString()}</p>
                    <p><strong>Quantidade atual:</strong> ${lote.quantidade_atual} ${lote.unidade_compra || ''}</p>
                    <p><strong>Fabricado em:</strong> ${lote.data_fabricacao ? new Date(lote.data_fabricacao).toLocaleDateString() : 'Não informado'}</p>
                </div>
                <div style="display:flex; flex-direction:column; gap:10px; align-items:flex-end;">
                    <button onclick="excluirLoteInsumo(${lote.id})" class="btn" style="background-color:#dc3545;">Excluir lote</button>
                </div>
            </div>
        `;

        container.appendChild(card);
    });
}

async function excluirLoteInsumo(loteId) {
    if(!confirm('Deseja realmente excluir este lote?')) return;

    try {
        const response = await fetch('../api/validade.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: loteId })
        });
        const data = await response.json();
        if(data.success) {
            mostrarMensagem('Lote excluído com sucesso', 'success');
            if(insumoSelecionadoParaLotes) {
                carregarLotesPorInsumo(insumoSelecionadoParaLotes.id);
            }
        } else {
            mostrarMensagem(data.message, 'error');
        }
    } catch(error) {
        console.error('Erro ao excluir lote:', error);
        mostrarMensagem('Erro ao excluir lote', 'error');
    }
}

document.getElementById('form-lote-insumo').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = {
        cadastrar_lote: true,
        insumo_id: document.getElementById('insumo_id_lote_insumo').value,
        lote: document.getElementById('lote_insumo').value,
        quantidade_lote: document.getElementById('quantidade_lote_insumo').value,
        data_fabricacao: document.getElementById('data_fabricacao_insumo').value,
        data_validade: document.getElementById('data_validade_insumo').value,
        observacoes: document.getElementById('observacoes_lote_insumo').value
    };

    try {
        const response = await fetch('../api/validade.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();
        if(data.success) {
            mostrarMensagem('Lote cadastrado com sucesso!', 'success');
            carregarLotesPorInsumo(formData.insumo_id);
            document.getElementById('form-lote-insumo').reset();
        } else {
            mostrarMensagem(data.message, 'error');
        }
    } catch(error) {
        console.error('Erro ao cadastrar lote:', error);
        mostrarMensagem('Erro ao cadastrar lote', 'error');
    }
});

document.getElementById('fechar-modal-lotes').addEventListener('click', function() {
    document.getElementById('modal-lotes-insumo').style.display = 'none';
});

document.getElementById('btn-cancelar-lote-insumo').addEventListener('click', function() {
    document.getElementById('modal-lotes-insumo').style.display = 'none';
});

// Exibir alertas
function exibirAlertas() {
    const container = document.getElementById('alertas-lista');
    
    if(alertas.length === 0) {
        container.innerHTML = '<p style="color: green;">✅ Nenhum alerta ativo!</p>';
        return;
    }
    
    container.innerHTML = '';
    
    alertas.forEach(alerta => {
        const div = document.createElement('div');
        div.style.cssText = `
            border: 1px solid #dc3545;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background-color: #f8d7da;
        `;
        
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h4>⚠️ ${alerta.tipo_alerta === 'estoque_zerado' ? 'ESTOQUE ZERADO' : 'ESTOQUE MÍNIMO'}</h4>
                    <p><strong>Insumo:</strong> ${alerta.insumo_nome}</p>
                    <p><strong>Estoque Atual:</strong> ${alerta.quantidade_atual} ${alerta.unidade_compra || 'un'}</p>
                    <p><strong>Estoque Mínimo:</strong> ${alerta.quantidade_minima} ${alerta.unidade_compra || 'un'}</p>
                </div>
            </div>
        `;
        
        container.appendChild(div);
    });
}

// Carregar estatísticas
async function carregarEstatisticas() {
    try {
        const response = await fetch('../api/alertas.php?estatisticas=1');
        const data = await response.json();
        
        if(data.success) {
            const stats = data.data;
            const container = document.getElementById('estatisticas-conteudo');
            
            container.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div style="background-color: #e3f2fd; padding: 15px; border-radius: 8px;">
                        <h4>Total de Alertas</h4>
                        <p style="font-size: 24px; font-weight: bold;">${stats.total_alertas}</p>
                    </div>
                    <div style="background-color: #fff3e0; padding: 15px; border-radius: 8px;">
                        <h4>Não Visualizados</h4>
                        <p style="font-size: 24px; font-weight: bold;">${stats.alertas_nao_visualizados}</p>
                    </div>
                    <div style="background-color: #fce4ec; padding: 15px; border-radius: 8px;">
                        <h4>Estoque Zerado</h4>
                        <p style="font-size: 24px; font-weight: bold;">${stats.alertas_estoque_zerado}</p>
                    </div>
                    <div style="background-color: #f3e5f5; padding: 15px; border-radius: 8px;">
                        <h4>Estoque Mínimo</h4>
                        <p style="font-size: 24px; font-weight: bold;">${stats.alertas_estoque_minimo}</p>
                    </div>
                </div>
            `;
        }
    } catch(error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
}

// Salvar insumo
document.getElementById('form-insumo').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const unidadeCompra = document.getElementById('unidade_compra').value;
    let estoqueAtual = parseFloat(document.getElementById('estoque_atual').value) || 0;
    let estoqueMinimo = parseFloat(document.getElementById('estoque_minimo').value) || 0;
    
    // Converter para armazenamento (kg -> g, L -> ml)
    if(unidadeCompra === 'kg') {
        estoqueAtual = estoqueAtual * 1000;
        estoqueMinimo = estoqueMinimo * 1000;
    } else if(unidadeCompra === 'L') {
        estoqueAtual = estoqueAtual * 1000;
        estoqueMinimo = estoqueMinimo * 1000;
    }
    
    const formData = {
        nome: document.getElementById('nome').value,
        descricao: document.getElementById('descricao').value,
        unidade_compra: unidadeCompra,
        estoque_atual: estoqueAtual,
        estoque_minimo: estoqueMinimo,
        custo_unitario_atual: document.getElementById('custo_unitario_atual').value,
        categoria: document.getElementById('categoria').value,
        fornecedor: document.getElementById('fornecedor').value
    };

    // Adicionar dados de lote se fornecidos (apenas para novos insumos)
    if (!modoEdicao) {
        const lote = document.getElementById('lote_inicial').value;
        const quantidadeLote = document.getElementById('quantidade_lote_inicial').value;
        const dataFabricacao = document.getElementById('data_fabricacao_inicial').value;
        const dataValidade = document.getElementById('data_validade_inicial').value;
        const observacoesLote = document.getElementById('observacoes_lote_inicial').value;

        if (lote || quantidadeLote || dataValidade) {
            formData.lote = lote || '';
            formData.quantidade_lote = quantidadeLote || estoqueAtual;
            formData.data_fabricacao = dataFabricacao || null;
            formData.data_validade = dataValidade || null;
            formData.observacoes = observacoesLote || '';
        }
    }

    // Se estiver editando, adicionar ID e usar PUT
    if(modoEdicao) {
        formData.id = document.getElementById('insumo_id').value;
    }
    
    try {
        const method = modoEdicao ? 'PUT' : 'POST';
        const response = await fetch('../api/insumos.php', {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if(data.success) {
            mostrarMensagem(modoEdicao ? 'Insumo atualizado com sucesso!' : 'Insumo cadastrado com sucesso!', 'success');
            document.getElementById('form-novo-insumo').style.display = 'none';
            document.getElementById('form-insumo').reset();
            modoEdicao = false;
            document.getElementById('titulo-form').textContent = 'Cadastrar Novo Insumo';
            document.getElementById('btn-salvar').textContent = 'Salvar Insumo';
            carregarInsumos();
        } else {
            mostrarMensagem(data.message, 'error');
        }
    } catch(error) {
        console.error('Erro:', error);
        mostrarMensagem('Erro ao salvar insumo', 'error');
    }
});

// Marcar alerta como visualizado
async function marcarAlertaVisualizado(alertaId) {
    try {
        const response = await fetch('../api/alertas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                marcar_visualizado: true,
                alerta_id: alertaId
            })
        });
        
        const data = await response.json();
        
        if(data.success) {
            mostrarMensagem('Alerta marcado como visualizado', 'success');
            carregarAlertas();
        } else {
            mostrarMensagem(data.message, 'error');
        }
    } catch(error) {
        console.error('Erro:', error);
        mostrarMensagem('Erro ao marcar alerta', 'error');
    }
}

// Mostrar mensagem
function mostrarMensagem(texto, tipo) {
    const container = document.getElementById('mensagem');
    const cor = tipo === 'success' ? '#d4edda' : '#f8d7da';
    const textoCor = tipo === 'success' ? '#155724' : '#721c24';
    
    container.innerHTML = `
        <div style="background-color: ${cor}; color: ${textoCor}; padding: 15px; border-radius: 8px; margin-top: 20px;">
            ${texto}
        </div>
    `;
    
    setTimeout(() => {
        container.innerHTML = '';
    }, 3000);
}

// Event listeners
document.getElementById('btn-novo-insumo').addEventListener('click', function() {
    modoEdicao = false;
    document.getElementById('form-insumo').reset();
    document.getElementById('insumo_id').value = '';
    document.getElementById('titulo-form').textContent = 'Cadastrar Novo Insumo';
    document.getElementById('btn-salvar').textContent = 'Salvar Insumo';
    document.getElementById('form-novo-insumo').style.display = 'block';
    // Mostrar campos de lote para novos insumos
    document.querySelector('#form-novo-insumo h4').style.display = 'block';
    document.querySelector('#form-novo-insumo p').style.display = 'block';
    document.getElementById('campos-lote-inicial').style.display = 'grid';
    document.getElementById('observacoes_lote_inicial').parentElement.style.display = 'block';
});

document.getElementById('btn-cancelar').addEventListener('click', function() {
    document.getElementById('form-novo-insumo').style.display = 'none';
    document.getElementById('form-insumo').reset();
    modoEdicao = false;
});

document.getElementById('btn-verificar-alertas').addEventListener('click', verificarAlertas);

document.getElementById('btn-estatisticas').addEventListener('click', function() {
    const container = document.getElementById('estatisticas-container');
    if(container.style.display === 'none') {
        container.style.display = 'block';
        carregarEstatisticas();
    } else {
        container.style.display = 'none';
    }
});

// Carregar dados iniciais
carregarInsumos();
carregarAlertas();
</script>

<?php include('footer.php'); ?>
