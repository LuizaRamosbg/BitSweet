<?php include('header.php'); ?>


<div class="dashboard">

    <?php include('sidebar.php'); ?>
    <main class="content">
        <h2>Gerenciar Receitas</h2>
        <p>Cadastrar Novas Receitas e Controle de Validade</p>

        <div class="container">
                <div class="botoes-menu" style="margin-bottom: 30px;">
                <button id="btn-nova-receita" class="btn">➕ Nova Receita</button>
                <button id="btn-produzir" class="btn">🏭 Registrar Produção</button>
                <button id="btn-validade" class="btn">📅 Controle de Validade</button>
                <button id="btn-estatisticas" class="btn">📊 Estatísticas</button>
            </div>

            <div id="alerta-validade-resumo"
                style="display:none; margin-bottom:20px; padding:15px; border-radius:8px; background-color:#fff3cd; color:#856404; border:1px solid #ffeeba;">
                <strong>⚠️ Aviso de validade:</strong> existem lotes próximos do vencimento ou vencidos. Clique em "Controle
                de Validade" para corrigir.
            </div>

            <!-- Formulário para nova receita -->
            <div id="form-nova-receita" style="display: none; margin-bottom: 30px;">
                <h3>Cadastrar Nova Receita</h3>
                <form id="form-receita" class="formulario">
                    <div class="row">
                        <div class="col">
                            <label for="nome">Nome da Receita:</label>
                            <input class="form-control" type="text" id="nome" name="nome" required>
                        </div>
                         
                        <div class="col">
                            <label for="categoria">Categoria:</label>
                            <select class="form-control" id="categoria" name="categoria">
                                <option value="">Selecione...</option>
                                <option value="Bolos">Bolos</option>
                                <option value="Cupcakes">Cupcakes</option>
                                <option value="Tortas">Tortas</option>
                                <option value="Doces">Doces</option>
                                <option value="Salgados">Salgados</option>
                            </select>

                         </div>

                    </div>
                    <div class="row">
                        <div class="col">
                            <label for="rendimento">Rendimento:</label>
                            <input class="form-control" type="number" id="rendimento" name="rendimento" step="0.01" value="1" required>

                        </div>
                         
                        <div class="col">
                            <label for="unidade_rendimento">Unidade:</label>
                            <select class="form-control" id="unidade_rendimento" name="unidade_rendimento">
                                <option value="un">Unidade</option>
                                <option value="kg">Quilograma</option>
                                <option value="L">Litro</option>
                                <option value="fatia">Fatia</option>
                                <option value="porcao">Porção</option>
                            </select>

                         </div>

                    </div>

                    <label for="descricao">Descrição:</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="2"></textarea>

                    <div class="row">
                        <div class="col">
                            <label for="tempo_preparo">Tempo (min):</label>
                            <input class="form-control" type="number" id="tempo_preparo" name="tempo_preparo" min="0">

                        </div>
                         
                        <div class="col">
                            <label for="dificuldade">Dificuldade:</label>
                            <select class="form-control" id="dificuldade" name="dificuldade">
                                <option value="facil">Fácil</option>
                                <option value="medio" selected>Médio</option>
                                <option value="dificil">Difícil</option>
                            </select>

                         </div>

                        <div class="col">
                            <label for="margem_lucro">Margem de Lucro (%):</label>
                            <input class="form-control" type="number" id="margem_lucro" name="margem_lucro" step="0.01" value="30" min="0" max="100">

                        </div>

                    </div>


                    <div id="calculos-receita"
                        style="margin-top: 15px; padding: 15px; background-color: #f0f8ff; border-radius: 8px; display: none;">
                        <h4>💰 Cálculos da Receita</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                            <div>
                                <label>Custo Total:</label>
                                <p style="font-weight: bold; color: #dc3545;">R$ <span id="custo-total-receita">0,00</span>
                                </p>
                            </div>
                            <div>
                                <label>Margem de Lucro:</label>
                                <p style="font-weight: bold; color: #28a745;"><span id="margem-lucro-receita">0</span>%</p>
                            </div>
                            <div>
                                <label>Preço de Venda:</label>
                                <p style="font-weight: bold; color: #007bff;">R$ <span id="preco-venda-receita">0,00</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <label for="instrucoes">Instruções de Preparo:</label>
                    <textarea class="form-control" id="instrucoes" name="instrucoes" rows="4"></textarea>

                    <button style="background-color: #141413;" type="submit" class="btn-enviar">Salvar Receita</button>
                    <button type="button" id="btn-cancelar-receita" class="btn">Cancelar</button>
                        
                </form>
            </div>

            <!-- Formulário para adicionar ingredientes -->
            <div id="form-ingredientes" style="display: none; margin-bottom: 30px;">
                <h3>Adicionar Ingredientes à Receita</h3>
                <form id="form-ingrediente" class="formulario">
                    <input type="hidden" id="receita_id_ingrediente" name="receita_id">

                     <div class="row">
                        <div class="col">
                            <label for="insumo_id">Insumo:</label>
                            <select class="form-control" id="insumo_id" name="insumo_id" required>
                                <option value="">Selecione um insumo...</option>
                            </select>
                        </div>
                         
                        <div class="col">
                            <label for="unidade_rendimento">Unidade:</label>
                            <select class="form-control" id="unidade_rendimento" name="unidade_rendimento">
                                <option value="un">Unidade</option>
                                <option value="kg">Quilograma</option>
                                <option value="L">Litro</option>
                                <option value="fatia">Fatia</option>
                                <option value="porcao">Porção</option>
                            </select>

                         </div>

                    </div>
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 15px;">
                        <div>
                            <label for="insumo_id">Insumo:</label>
                            <select id="insumo_id" name="insumo_id" required>
                                <option value="">Selecione um insumo...</option>
                            </select>
                        </div>
                        <div>
                            <label for="quantidade_ingrediente">Quantidade:</label>
                            <input type="number" id="quantidade_ingrediente" name="quantidade" step="0.001" required>
                        </div>
                        <div>
                            <label for="unidade_uso_ingrediente">Unidade:</label>
                            <select id="unidade_uso_ingrediente" name="unidade_uso" required>
                                <option value="kg">kg</option>
                                <option value="g">g</option>
                                <option value="L">L</option>
                                <option value="ml">ml</option>
                                <option value="un">un</option>
                                <option value="cx">cx</option>
                                <option value="pct">pct</option>
                            </select>
                        </div>
                        <div>
                            <label for="ordem">Ordem:</label>
                            <input type="number" id="ordem" name="ordem" min="1" value="1">
                        </div>
                    </div>

                    <label for="observacoes_ingrediente">Observações:</label>
                    <input type="text" id="observacoes_ingrediente" name="observacoes">

                    <div id="preview-custo-ingrediente"
                        style="margin-top:10px; display:none; background:#fff8f0; padding:10px; border-radius:6px;">
                        <strong>Pré-visualização do custo:</strong>
                        <p>Custo estimado deste ingrediente: R$ <span id="preview-custo-valor">0.00</span></p>
                    </div>

                    <button style="background-color: #141413;" type="submit" class="btn-enviar">Adicionar Ingrediente</button>
                    <button type="button" id="btn-cancelar-ingrediente" class="btn">Cancelar</button>
                       
                </form>

                <!-- Formulário para adicionar custos extras -->
                <div id="form-custo-extra" style="display: none; margin-top: 15px;">
                    <h4>➕ Adicionar Custo Extra (embalagem, etiqueta, fita)</h4>
                    <form id="form-custo-extra-form" class="formulario">
                        <input type="hidden" id="receita_id_custo" name="receita_id">
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                            <div>
                                <label for="descricao_custo">Descrição:</label>
                                <input type="text" id="descricao_custo" name="descricao" placeholder="Ex: Embalagem caixa"
                                    required>
                            </div>
                            <div>
                                <label for="valor_custo">Valor (R$):</label>
                                <input type="number" id="valor_custo" name="valor" step="0.01" value="0.00" required>
                            </div>
                        </div>
                        <button style="background-color: #141413;" type="submit" class="btn-enviar">Adicionar Custo Extra</button>
                        <button type="button" id="btn-cancelar-custo" class="btn">Cancelar</button>
                            
                    </form>
                </div>
            </div>

            <!-- Formulário para registrar produção -->
            <div id="form-producao" style="display: none; margin-bottom: 30px;">
                <h3>Registrar Produção</h3>
                <form id="form-producao-form" class="formulario">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label for="receita_id_producao">Receita:</label>
                            <select id="receita_id_producao" name="receita_id" required>
                                <option value="">Selecione uma receita...</option>
                            </select>
                        </div>
                        <div>
                            <label for="quantidade_produzida">Quantidade Produzida:</label>
                            <input type="number" id="quantidade_produzida" name="quantidade_produzida" step="0.01" required>
                        </div>
                    </div>

                    <label for="observacoes_producao">Observações:</label>
                    <textarea id="observacoes_producao" name="observacoes" rows="3"></textarea>

                    <button  style="background-color: #141413;" type="submit" class="btn-enviar">Registrar Produção</button>
                    <button type="button" id="btn-cancelar-producao" class="btn">Cancelar</button>
                       
                </form>
            </div>

            <!-- Modal de ingredientes (lista com remover) -->
            <div id="modal-ingredientes"
                style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
                <div
                    style="background:#fff; padding:20px; border-radius:8px; width:90%; max-width:800px; max-height:80%; overflow:auto;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3>Ingredientes</h3>
                        <button id="fechar-modal-ingredientes" class="btn">Fechar</button>
                    </div>
                    <div id="lista-ingredientes-modal"></div>
                </div>
            </div>

            <!-- Lista de receitas -->
            <div id="lista-receitas">
                <h3>Lista de Receitas</h3>
                <div id="receitas-container"></div>
            </div>

            <!-- Controle de validade -->
            <div id="controle-validade" style="margin-top: 30px; display: none;">
                <h3>📅 Controle de Validade</h3>
                <div class="botoes-menu" style="margin-bottom: 20px;">
                    <button id="btn-cadastrar-lote" class="btn">➕ Cadastrar Lote</button>
                    <button id="btn-verificar-validade" class="btn">🔍 Verificar Validade</button>
                </div>

                <!-- Formulário para cadastrar lote -->
                <div id="form-lote" style="display: none; margin-bottom: 30px;">
                    <h4>Cadastrar Novo Lote</h4>
                    <form id="form-lote-form" class="formulario">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label for="insumo_id_lote">Insumo:</label>
                                <select id="insumo_id_lote" name="insumo_id" required>
                                    <option value="">Selecione um insumo...</option>
                                </select>
                            </div>
                            <div>
                                <label for="lote">Número do Lote:</label>
                                <input type="text" id="lote" name="lote" placeholder="Ex: L001">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                            <div>
                                <label for="quantidade_lote">Quantidade do Lote:</label>
                                <input type="number" id="quantidade_lote" name="quantidade_lote" step="0.001" required>
                            </div>
                            <div>
                                <label for="data_fabricacao">Data de Fabricação:</label>
                                <input type="date" id="data_fabricacao" name="data_fabricacao">
                            </div>
                            <div>
                                <label for="data_validade">Data de Validade:</label>
                                <input type="date" id="data_validade" name="data_validade" required>
                            </div>
                        </div>

                        <label for="observacoes_lote">Observações:</label>
                        <textarea id="observacoes_lote" name="observacoes" rows="2"></textarea>

                        <button style="background-color: #141413;" type="submit" class="btn-enviar">Cadastrar Lote</button>
                        <button type="button" id="btn-cancelar-lote" class="btn"
                            >Cancelar</button>
                    </form>
                </div>

                <div id="lotes-container"></div>
                <div id="alertas-validade-container"></div>
            </div>

            <div id="mensagem"></div>
        </div>
       
    </main>
</div>

<script>
    let receitas = [];
    let insumos = [];
    let lotes = [];
    let alertasValidade = [];

    // Carregar receitas
    async function carregarReceitas() {
        try {
            const response = await fetch('../api/receitas.php');
            const data = await response.json();

            if (data.success) {
                receitas = data.data;
                exibirReceitas();
            }
        } catch (error) {
            console.error('Erro ao carregar receitas:', error);
        }
    }

    // Carregar insumos
    async function carregarInsumos() {
        try {
            const response = await fetch('../api/insumos.php');
            const data = await response.json();

            if (data.success) {
                insumos = data.data;
                preencherSelectInsumos();
            }
        } catch (error) {
            console.error('Erro ao carregar insumos:', error);
        }
    }

    // Preencher selects de insumos
    function preencherSelectInsumos() {
        const selects = ['insumo_id', 'insumo_id_lote'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                select.innerHTML = '<option value="">Selecione um insumo...</option>';
                insumos.forEach(insumo => {
                    const option = document.createElement('option');
                    option.value = insumo.id;
                    option.textContent = `${insumo.nome} (${insumo.unidade_compra})`;
                    select.appendChild(option);
                });
            }
        });
    }

    // Exibir receitas
    function exibirReceitas() {
        const container = document.getElementById('receitas-container');
        container.innerHTML = '';

        receitas.forEach(receita => {
            const div = document.createElement('div');
            div.className = 'receita-card';
            div.style.cssText = `
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            background-color: #fff;
        `;

            div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <h4>${receita.nome}</h4>
                    <p><strong>Categoria:</strong> ${receita.categoria || 'Não definida'}</p>
                    <p><strong>Rendimento:</strong> ${receita.rendimento} ${receita.unidade_rendimento}</p>
                    <p><strong>Tempo:</strong> ${receita.tempo_preparo} min | <strong>Dificuldade:</strong> ${receita.dificuldade}</p>
                    <p><strong>Custo Total:</strong> R$ ${parseFloat(receita.custo_total).toFixed(2)}</p>
                    <p><strong>Margem de Lucro:</strong> ${parseFloat(receita.margem_lucro).toFixed(1)}%</p>
                    <p><strong>Preço de Venda:</strong> R$ ${parseFloat(receita.preco_venda_sugerido).toFixed(2)}</p>
                </div>
                <div>
                    <button onclick="editarReceita(${receita.id})" class="btn" style="margin: 2px;">✏️ Editar</button>
                    <button onclick="adicionarIngrediente(${receita.id})" class="btn" style="margin: 2px;">➕ Ingredientes</button>
                    <button onclick="verIngredientes(${receita.id})" class="btn" style="margin: 2px;">👁️ Ver</button>
                    <button onclick="calcularPrecosReceita(${receita.id}, ${receita.margem_lucro})" class="btn" style="margin: 2px;">💰 Calcular</button>
                    <button onclick="atualizarMargemLucro(${receita.id}, prompt('Nova margem de lucro (%):', ${receita.margem_lucro}))" class="btn" style="margin: 2px;">📊 Margem</button>
                    <button onclick="excluirReceita(${receita.id})" class="btn" style="background-color: #dc3545; margin: 2px;">🗑️ Excluir</button>
                </div>
            </div>
        `;

            container.appendChild(div);
        });
    }

    // Adicionar ingrediente à receita
    async function adicionarIngrediente(receitaId) {
        document.getElementById('receita_id_ingrediente').value = receitaId;
        // Também popular campo do formulário de custos extras
        const receitaCusto = document.getElementById('receita_id_custo');
        if (receitaCusto) receitaCusto.value = receitaId;
        document.getElementById('form-ingredientes').style.display = 'block';
        // Mostrar formulário de custos extras junto quando for relevante
        const formCusto = document.getElementById('form-custo-extra');
        if (formCusto) formCusto.style.display = 'block';
    }

    // Ver detalhes da receita: ingredientes, custos extras e modo de preparo
    async function verIngredientes(receitaId) {
        try {
            const response = await fetch(`../api/receitas.php?id=${receitaId}`);
            const data = await response.json();

            if (data.success) {
                const receita = data.data;
                const lista = document.getElementById('lista-ingredientes-modal');
                lista.innerHTML = '';

                // Cabeçalho da receita
                const header = document.createElement('div');
                header.style.marginBottom = '15px';
                header.innerHTML = `
                <h3>${receita.nome}</h3>
                <p><strong>Descrição:</strong> ${receita.descricao || 'Não informada'}</p>
                <p><strong>Rendimento:</strong> ${receita.rendimento} ${receita.unidade_rendimento}</p>
            `;
                lista.appendChild(header);

                // Modo de preparo
                const modoPreparo = document.createElement('div');
                modoPreparo.style.marginBottom = '15px';
                modoPreparo.innerHTML = `
                <h4>Modo de Preparo</h4>
                <p style="white-space: pre-wrap;">${receita.instrucoes || 'Nenhuma instrução cadastrada.'}</p>
            `;
                lista.appendChild(modoPreparo);

                // Ingredientes
                const ingredientesSection = document.createElement('div');
                ingredientesSection.style.marginBottom = '15px';
                ingredientesSection.innerHTML = '<h4>Ingredientes</h4>';

                if (!receita.ingredientes || receita.ingredientes.length === 0) {
                    ingredientesSection.innerHTML += '<p>Nenhum ingrediente cadastrado.</p>';
                } else {
                    const ul = document.createElement('ul');
                    receita.ingredientes.forEach(ingrediente => {
                        const li = document.createElement('li');
                        li.style.marginBottom = '8px';
                        li.innerHTML = `${ingrediente.quantidade} ${ingrediente.unidade_uso} de <strong>${ingrediente.insumo_nome}</strong>`;
                        ul.appendChild(li);
                    });
                    ingredientesSection.appendChild(ul);
                }
                lista.appendChild(ingredientesSection);

                // Custos extras
                const extrasSection = document.createElement('div');
                extrasSection.style.marginBottom = '15px';
                extrasSection.innerHTML = '<h4>Custos Extras</h4>';

                if (!receita.custos_extras || receita.custos_extras.length === 0) {
                    const noExtras = document.createElement('p');
                    noExtras.textContent = 'Não há custos extras cadastrados.';
                    extrasSection.appendChild(noExtras);
                } else {
                    const ulExtras = document.createElement('ul');
                    let totalExtras = 0;
                    receita.custos_extras.forEach(extra => {
                        const li = document.createElement('li');
                        li.style.marginBottom = '8px';

                        const textSpan = document.createElement('span');
                        textSpan.innerHTML = `<strong>${extra.descricao}</strong>: R$ ${parseFloat(extra.valor).toFixed(2)}`;
                        li.appendChild(textSpan);

                        const editarBtn = document.createElement('button');
                        editarBtn.className = 'btn';
                        editarBtn.style.marginLeft = '10px';
                        editarBtn.textContent = 'Editar';
                        editarBtn.addEventListener('click', () => editarCustoExtra(extra, receita.id, li));
                        li.appendChild(editarBtn);

                        const removerBtn = document.createElement('button');
                        removerBtn.className = 'btn';
                        removerBtn.style.marginLeft = '10px';
                        removerBtn.style.backgroundColor = '#dc3545';
                        removerBtn.textContent = 'Remover';
                        removerBtn.addEventListener('click', () => removerCustoExtra(receita.id, extra.id));
                        li.appendChild(removerBtn);

                        ulExtras.appendChild(li);
                        totalExtras += parseFloat(extra.valor) || 0;
                    });
                    extrasSection.appendChild(ulExtras);
                    const totalExtrasParagraph = document.createElement('p');
                    totalExtrasParagraph.innerHTML = `<strong>Total de custos extras:</strong> R$ ${totalExtras.toFixed(2)}`;
                    extrasSection.appendChild(totalExtrasParagraph);
                }
                lista.appendChild(extrasSection);

                document.getElementById('modal-ingredientes').style.display = 'flex';
            }
        } catch (error) {
            console.error('Erro ao carregar detalhes da receita:', error);
            mostrarMensagem('Erro ao carregar detalhes da receita', 'error');
        }
    }

    function editarCustoExtra(extra, receitaId, listItem) {
        listItem.innerHTML = '';

        const descricaoInput = document.createElement('input');
        descricaoInput.type = 'text';
        descricaoInput.value = extra.descricao;
        descricaoInput.style.marginRight = '10px';
        descricaoInput.style.width = '35%';

        const valorInput = document.createElement('input');
        valorInput.type = 'number';
        valorInput.step = '0.01';
        valorInput.min = '0';
        valorInput.value = parseFloat(extra.valor).toFixed(2);
        valorInput.style.marginRight = '10px';
        valorInput.style.width = '120px';

        const salvarBtn = document.createElement('button');
        salvarBtn.className = 'btn';
        salvarBtn.textContent = 'Salvar';
        salvarBtn.style.marginRight = '10px';
        salvarBtn.addEventListener('click', async () => {
            const descricao = descricaoInput.value.trim();
            const valor = parseFloat(valorInput.value);
            if (!descricao || isNaN(valor) || valor <= 0) {
                mostrarMensagem('Preencha descrição e valor válidos para o custo extra.', 'error');
                return;
            }

            try {
                const response = await fetch('../api/custos_extras.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: extra.id, receita_id: receitaId, descricao, valor })
                });
                const result = await response.json();
                if (result.success) {
                    mostrarMensagem('Custo extra atualizado com sucesso!', 'success');
                    verIngredientes(receitaId);
                } else {
                    mostrarMensagem(result.message || 'Erro ao atualizar custo extra', 'error');
                }
            } catch (err) {
                console.error(err);
                mostrarMensagem('Erro ao atualizar custo extra', 'error');
            }
        });

        const cancelarBtn = document.createElement('button');
        cancelarBtn.className = 'btn';
        cancelarBtn.textContent = 'Cancelar';
        cancelarBtn.style.backgroundColor = '#6c757d';
        cancelarBtn.addEventListener('click', () => verIngredientes(receitaId));

        listItem.appendChild(descricaoInput);
        listItem.appendChild(valorInput);
        listItem.appendChild(salvarBtn);
        listItem.appendChild(cancelarBtn);
    }

    async function removerCustoExtra(receitaId, custoExtraId) {
        if (!confirm('Deseja remover este custo extra?')) return;

        try {
            const response = await fetch('../api/custos_extras.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: custoExtraId, receita_id: receitaId })
            });
            const result = await response.json();
            if (result.success) {
                mostrarMensagem('Custo extra removido com sucesso!', 'success');
                verIngredientes(receitaId);
            } else {
                mostrarMensagem(result.message || 'Erro ao remover custo extra', 'error');
            }
        } catch (err) {
            console.error(err);
            mostrarMensagem('Erro ao remover custo extra', 'error');
        }
    }

    // Fechar modal ingredientes
    document.getElementById('fechar-modal-ingredientes').addEventListener('click', function () {
        document.getElementById('modal-ingredientes').style.display = 'none';
    });

    // Remover ingrediente via API
    async function removerIngrediente(receitaId, ingredienteId) {
        if (!confirm('Remover este ingrediente?')) return;
        try {
            const response = await fetch('../api/receitas.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ remover_ingrediente: true, receita_id: receitaId, ingrediente_id: ingredienteId })
            });
            const data = await response.json();
            if (data.success) {
                mostrarMensagem('Ingrediente removido', 'success');
                verIngredientes(receitaId);
                // Recalcular preços da receita
                setTimeout(() => calcularPrecosReceita(receitaId, parseFloat(document.getElementById('margem_lucro').value || 30)), 300);
                carregarReceitas();
            } else {
                mostrarMensagem(data.message || 'Erro ao remover ingrediente', 'error');
            }
        } catch (err) {
            console.error(err);
            mostrarMensagem('Erro ao remover ingrediente', 'error');
        }
    }

    // Salvar receita
    document.getElementById('form-receita').addEventListener('submit', async function (e) {
        e.preventDefault();

        const receitaIdEdicao = document.getElementById('receita_id_edicao');
        const isEdicao = receitaIdEdicao && receitaIdEdicao.value;

        const formData = {
            nome: document.getElementById('nome').value,
            descricao: document.getElementById('descricao').value,
            categoria: document.getElementById('categoria').value,
            rendimento: document.getElementById('rendimento').value,
            unidade_rendimento: document.getElementById('unidade_rendimento').value,
            tempo_preparo: document.getElementById('tempo_preparo').value,
            dificuldade: document.getElementById('dificuldade').value,
            instrucoes: document.getElementById('instrucoes').value,
            margem_lucro: document.getElementById('margem_lucro').value
        };

        if (isEdicao) {
            // Atualizar receita existente
            formData.id = receitaIdEdicao.value;
        } else {
            // Criar nova receita
            formData.criar_receita = true;
        }

        try {
            const method = isEdicao ? 'PUT' : 'POST';
            const response = await fetch('../api/receitas.php', {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                mostrarMensagem(isEdicao ? 'Receita atualizada com sucesso!' : 'Receita criada com sucesso!', 'success');
                // Se for criação nova, abrir o formulário de ingredientes para preencher os ingredientes
                if (!isEdicao) {
                    const novoId = data.data && data.data.id ? data.data.id : null;
                    document.getElementById('form-nova-receita').style.display = 'none';
                    document.getElementById('form-receita').reset();
                    if (receitaIdEdicao) receitaIdEdicao.remove();
                    document.querySelector('#form-nova-receita h3').textContent = 'Cadastrar Nova Receita';
                    document.querySelector('#form-receita button[type="submit"]').textContent = 'Salvar Receita';
                    if (novoId) {
                        // abrir imediatamente para adicionar ingredientes
                        adicionarIngrediente(novoId);
                    }
                } else {
                    document.getElementById('form-nova-receita').style.display = 'none';
                    document.getElementById('form-receita').reset();
                    if (receitaIdEdicao) receitaIdEdicao.remove();
                    document.querySelector('#form-nova-receita h3').textContent = 'Cadastrar Nova Receita';
                    document.querySelector('#form-receita button[type="submit"]').textContent = 'Salvar Receita';
                }
                carregarReceitas();
            } else {
                mostrarMensagem(data.message, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarMensagem(isEdicao ? 'Erro ao atualizar receita' : 'Erro ao criar receita', 'error');
        }
    });

    // Adicionar ingrediente
    document.getElementById('form-ingrediente').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = {
            adicionar_ingrediente: true,
            receita_id: document.getElementById('receita_id_ingrediente').value,
            insumo_id: document.getElementById('insumo_id').value,
            quantidade: document.getElementById('quantidade_ingrediente').value,
            unidade_uso: document.getElementById('unidade_uso_ingrediente').value,
            observacoes: document.getElementById('observacoes_ingrediente').value,
            ordem: document.getElementById('ordem').value
        };

        try {
            const response = await fetch('../api/receitas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                mostrarMensagem('Ingrediente adicionado com sucesso!', 'success');
                document.getElementById('form-ingredientes').style.display = 'none';
                document.getElementById('form-ingrediente').reset();
                carregarReceitas();

                // Recalcular preços automaticamente após adicionar ingrediente
                const receitaId = formData.receita_id;
                setTimeout(() => {
                    calcularPrecosReceita(receitaId, 30); // Margem padrão de 30%
                }, 500);
            } else {
                mostrarMensagem(data.message, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao adicionar ingrediente', 'error');
        }
    });

    // Calcular custo estimado do ingrediente no formulário
    function calcularCustoIngrediente() {
        const insumoId = parseInt(document.getElementById('insumo_id').value || 0);
        const quantidade = parseFloat(document.getElementById('quantidade_ingrediente').value || 0);
        const unidadeUso = document.getElementById('unidade_uso_ingrediente').value;

        if (!insumoId || quantidade <= 0) {
            document.getElementById('preview-custo-ingrediente').style.display = 'none';
            return;
        }

        const insumo = insumos.find(i => parseInt(i.id) === insumoId);
        if (!insumo) return;

        // Conversão simples baseada em unidade_compra e fator_conversao
        let quantidadeConvertida = quantidade;
        const origem = unidadeUso;
        const destino = insumo.unidade_compra || insumo.unidade_compra;
        const fator = parseFloat(insumo.fator_conversao) || 1.0;

        if (origem === destino) {
            quantidadeConvertida = quantidade;
        } else if ((origem === 'kg' && destino === 'g') || (origem === 'L' && destino === 'ml')) {
            quantidadeConvertida = quantidade * (fator || 1000);
        } else if ((origem === 'g' && destino === 'kg') || (origem === 'ml' && destino === 'L')) {
            quantidadeConvertida = quantidade / (fator || 1000);
        } else {
            const conversoes = { 'kg': { 'g': 1000 }, 'g': { 'kg': 0.001 }, 'L': { 'ml': 1000 }, 'ml': { 'L': 0.001 } };
            if (conversoes[origem] && conversoes[origem][destino]) {
                quantidadeConvertida = quantidade * conversoes[origem][destino];
            }
        }

        const custoUnitario = parseFloat(insumo.custo_unitario_atual) || 0;
        const custoEstimado = quantidadeConvertida * custoUnitario;

        document.getElementById('preview-custo-valor').textContent = custoEstimado.toFixed(2);
        document.getElementById('preview-custo-ingrediente').style.display = 'block';
    }

    // Listeners para cálculo ao vivo
    document.getElementById('insumo_id').addEventListener('change', calcularCustoIngrediente);
    document.getElementById('quantidade_ingrediente').addEventListener('input', calcularCustoIngrediente);
    document.getElementById('unidade_uso_ingrediente').addEventListener('change', calcularCustoIngrediente);

    // Adicionar custo extra
    document.getElementById('form-custo-extra-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const receitaId = document.getElementById('receita_id_custo').value;
        const descricao = document.getElementById('descricao_custo').value;
        const valor = parseFloat(document.getElementById('valor_custo').value) || 0;

        if (!receitaId || valor <= 0 || !descricao) {
            mostrarMensagem('Preencha descrição, receita e valor válidos', 'error');
            return;
        }

        const formData = {
            receita_id: receitaId,
            descricao: descricao,
            valor: valor
        };

        try {
            const response = await fetch('../api/custos_extras.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const data = await response.json();
            if (data.success) {
                mostrarMensagem('Custo extra adicionado com sucesso!', 'success');
                document.getElementById('form-custo-extra-form').reset();
                document.getElementById('form-custo-extra').style.display = 'none';
                // Recarregar receitas para atualizar valores
                carregarReceitas();
            } else {
                mostrarMensagem(data.message || 'Erro ao adicionar custo extra', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao adicionar custo extra', 'error');
        }
    });

    document.getElementById('btn-cancelar-custo').addEventListener('click', function () {
        document.getElementById('form-custo-extra').style.display = 'none';
        document.getElementById('form-custo-extra-form').reset();
    });

    // Registrar produção
    document.getElementById('form-producao-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = {
            registrar_producao: true,
            receita_id: document.getElementById('receita_id_producao').value,
            quantidade_produzida: document.getElementById('quantidade_produzida').value,
            observacoes: document.getElementById('observacoes_producao').value
        };

        try {
            const response = await fetch('../api/receitas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                if (data.data && data.data.warning) {
                    alert(data.data.warning);
                }
                mostrarMensagem('Produção registrada com sucesso!', 'success');
                document.getElementById('form-producao').style.display = 'none';
                document.getElementById('form-producao-form').reset();
                carregarReceitas();
            } else {
                mostrarMensagem(data.message, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao registrar produção', 'error');
        }
    });

    // Cadastrar lote
    document.getElementById('form-lote-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = {
            cadastrar_lote: true,
            insumo_id: document.getElementById('insumo_id_lote').value,
            lote: document.getElementById('lote').value,
            quantidade_lote: document.getElementById('quantidade_lote').value,
            data_fabricacao: document.getElementById('data_fabricacao').value,
            data_validade: document.getElementById('data_validade').value,
            observacoes: document.getElementById('observacoes_lote').value
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

            if (data.success) {
                mostrarMensagem('Lote cadastrado com sucesso!', 'success');
                document.getElementById('form-lote').style.display = 'none';
                document.getElementById('form-lote-form').reset();
                carregarLotes();
            } else {
                mostrarMensagem(data.message, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao cadastrar lote', 'error');
        }
    });

    // Carregar lotes
    async function carregarLotes() {
        try {
            const response = await fetch('../api/validade.php');
            const data = await response.json();

            if (data.success) {
                lotes = data.data;
                exibirLotes();
            }
        } catch (error) {
            console.error('Erro ao carregar lotes:', error);
        }
    }

    // Exibir lotes
    function exibirLotes() {
        const container = document.getElementById('lotes-container');
        container.innerHTML = '<h4>Lotes Cadastrados</h4>';

        lotes.forEach(lote => {
            const div = document.createElement('div');
            div.style.cssText = `
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background-color: ${lote.status === 'vencido' ? '#f8d7da' : lote.status === 'proximo_vencer' ? '#fff3cd' : '#d4edda'};
        `;

            div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h5>${lote.insumo_nome} - Lote: ${lote.lote}</h5>
                    <p><strong>Quantidade:</strong> ${lote.quantidade_atual} ${lote.unidade_compra}</p>
                    <p><strong>Validade:</strong> ${new Date(lote.data_validade).toLocaleDateString()}</p>
                    <p><strong>Status:</strong> ${lote.status}</p>
                </div>
                <div>
                    <button onclick="excluirLote(${lote.id})" class="btn" style="background-color: #dc3545; margin: 2px;">🗑️ Excluir</button>
                </div>
            </div>
        `;

            container.appendChild(div);
        });
    }

    // Verificar alertas de validade
    async function verificarAlertasValidade() {
        try {
            const response = await fetch('../api/validade.php?verificar_alertas=1');
            const data = await response.json();

            if (data.success) {
                mostrarMensagem(data.message || `Verificação concluída! ${data.data?.alertas_gerados || 0} novos alertas gerados.`, 'success');
                carregarLotes();
                carregarAlertasValidade();
            } else {
                mostrarMensagem(data.message || 'Erro ao verificar validade', 'error');
            }
        } catch (error) {
            console.error('Erro ao verificar alertas:', error);
            mostrarMensagem('Erro ao verificar validade', 'error');
        }
    }

    // Carregar alertas de validade
    async function carregarAlertasValidade() {
        try {
            const response = await fetch('../api/validade.php?alertas=1');
            const data = await response.json();

            if (data.success) {
                alertasValidade = data.data;
                exibirAlertasValidade();
            }
        } catch (error) {
            console.error('Erro ao carregar alertas:', error);
        }
    }

    // Exibir alertas de validade
    function exibirAlertasValidade() {
        const container = document.getElementById('alertas-validade-container');
        const resumo = document.getElementById('alerta-validade-resumo');
        container.innerHTML = '<h4>Alertas de Validade</h4>';

        if (alertasValidade.length === 0) {
            resumo.style.display = 'none';
            container.innerHTML += '<p style="color: green;">✅ Nenhum alerta ativo!</p>';
            return;
        }

        const expirados = alertasValidade.filter(a => a.tipo_alerta === 'vencido').length;
        const proximos = alertasValidade.filter(a => a.tipo_alerta === 'proximo_vencer').length;
        resumo.style.display = 'block';
        resumo.innerHTML = `⚠️ Existem ${alertasValidade.length} lote(s) com validade crítica: ${expirados} vencido(s) e ${proximos} próximo(s) ao vencimento. Clique em "Controle de Validade" para ver detalhes.`;

        alertasValidade.forEach(alerta => {
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
                    <h5>⚠️ ${alerta.tipo_alerta === 'vencido' ? 'LOTE VENCIDO' : 'PRÓXIMO AO VENCIMENTO'}</h5>
                    <p><strong>Insumo:</strong> ${alerta.insumo_nome}</p>
                    <p><strong>Lote:</strong> ${alerta.lote}</p>
                    <p><strong>Validade:</strong> ${new Date(alerta.data_validade).toLocaleDateString()}</p>
                    <p><strong>Quantidade:</strong> ${alerta.quantidade_atual} ${alerta.unidade_compra}</p>
                </div>
                <div>
                    <button onclick="marcarAlertaVisualizado(${alerta.id})" class="btn">✅ Visualizado</button>
                </div>
            </div>
        `;

            container.appendChild(div);
        });
    }

    // Marcar alerta como visualizado
    async function marcarAlertaVisualizado(alertaId) {
        try {
            const response = await fetch('../api/validade.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    marcar_alerta_visualizado: true,
                    alerta_id: alertaId
                })
            });

            const data = await response.json();

            if (data.success) {
                mostrarMensagem('Alerta marcado como visualizado', 'success');
                carregarAlertasValidade();
            } else {
                mostrarMensagem(data.message, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao marcar alerta', 'error');
        }
    }

    // Editar receita
    async function editarReceita(receitaId) {
        try {
            const response = await fetch(`../api/receitas.php?id=${receitaId}`);
            const data = await response.json();

            if (data.success) {
                const receita = data.data;

                // Preencher formulário
                document.getElementById('nome').value = receita.nome;
                document.getElementById('descricao').value = receita.descricao || '';
                document.getElementById('categoria').value = receita.categoria || '';
                document.getElementById('rendimento').value = receita.rendimento;
                document.getElementById('unidade_rendimento').value = receita.unidade_rendimento;
                document.getElementById('tempo_preparo').value = receita.tempo_preparo;
                document.getElementById('dificuldade').value = receita.dificuldade;
                document.getElementById('instrucoes').value = receita.instrucoes || '';
                document.getElementById('margem_lucro').value = receita.margem_lucro || 30;

                // Adicionar campo hidden para ID da receita
                let receitaIdInput = document.getElementById('receita_id_edicao');
                if (!receitaIdInput) {
                    receitaIdInput = document.createElement('input');
                    receitaIdInput.type = 'hidden';
                    receitaIdInput.id = 'receita_id_edicao';
                    receitaIdInput.name = 'id';
                    document.getElementById('form-receita').appendChild(receitaIdInput);
                }
                receitaIdInput.value = receitaId;

                // Atualizar título e botão
                document.querySelector('#form-nova-receita h3').textContent = 'Editar Receita';
                document.querySelector('#form-receita button[type="submit"]').textContent = 'Atualizar Receita';

                // Mostrar formulário
                document.getElementById('form-nova-receita').style.display = 'block';

                // Calcular preços
                if (receita.margem_lucro) {
                    calcularPrecosReceita(receitaId, receita.margem_lucro);
                }
            }
        } catch (error) {
            console.error('Erro ao carregar receita:', error);
            mostrarMensagem('Erro ao carregar receita para edição', 'error');
        }
    }

    // Excluir receita
    async function excluirReceita(receitaId) {
        if (confirm('Tem certeza que deseja excluir esta receita? Esta ação não pode ser desfeita.')) {
            try {
                const response = await fetch('../api/receitas.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: receitaId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    mostrarMensagem('Receita excluída com sucesso!', 'success');
                    carregarReceitas();
                } else {
                    mostrarMensagem(data.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                mostrarMensagem('Erro ao excluir receita', 'error');
            }
        }
    }

    // Calcular preços da receita
    async function calcularPrecosReceita(receitaId, margemLucro) {
        if (receitaId && margemLucro >= 0) {
            try {
                const response = await fetch(`../api/receitas.php?calcular_preco=1&receita_id=${receitaId}&margem_lucro=${margemLucro}`);
                const data = await response.json();

                if (data.success) {
                    document.getElementById('custo-total-receita').textContent = data.data.custo_total.toFixed(2);
                    document.getElementById('margem-lucro-receita').textContent = data.data.margem_lucro.toFixed(1);
                    document.getElementById('preco-venda-receita').textContent = data.data.preco_venda.toFixed(2);
                    document.getElementById('calculos-receita').style.display = 'block';
                }
            } catch (error) {
                console.error('Erro ao calcular preços:', error);
            }
        }
    }

    // Atualizar margem de lucro de uma receita
    async function atualizarMargemLucro(receitaId, margemLucro) {
        try {
            const response = await fetch('../api/receitas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    atualizar_margem: true,
                    receita_id: receitaId,
                    margem_lucro: margemLucro
                })
            });

            const data = await response.json();

            if (data.success) {
                mostrarMensagem('Margem de lucro atualizada com sucesso!', 'success');
                carregarReceitas();
            } else {
                mostrarMensagem(data.message, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao atualizar margem de lucro', 'error');
        }
    }

    // Mostrar mensagem
    function mostrarMensagem(texto, tipo) {
        const container = document.getElementById('mensagem');
        const cor = tipo === 'success' ? '#d4edda' : tipo === 'error' ? '#f8d7da' : '#d1ecf1';
        const textoCor = tipo === 'success' ? '#155724' : tipo === 'error' ? '#721c24' : '#0c5460';

        container.innerHTML = `
        <div style="background-color: ${cor}; color: ${textoCor}; padding: 15px; border-radius: 8px; margin-top: 20px;">
            ${texto}
        </div>
    `;

        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }

    // Event listeners
    document.getElementById('btn-nova-receita').addEventListener('click', function () {
        document.getElementById('form-receita').reset();
        const receitaIdEdicao = document.getElementById('receita_id_edicao');
        if (receitaIdEdicao) receitaIdEdicao.remove();
        document.querySelector('#form-nova-receita h3').textContent = 'Cadastrar Nova Receita';
        document.querySelector('#form-receita button[type="submit"]').textContent = 'Salvar Receita';
        document.getElementById('form-nova-receita').style.display = 'block';
    });

    document.getElementById('btn-cancelar-receita').addEventListener('click', function () {
        document.getElementById('form-nova-receita').style.display = 'none';
        document.getElementById('form-receita').reset();
    });

    document.getElementById('btn-cancelar-ingrediente').addEventListener('click', function () {
        document.getElementById('form-ingredientes').style.display = 'none';
        document.getElementById('form-ingrediente').reset();
    });

    document.getElementById('btn-produzir').addEventListener('click', function () {
        document.getElementById('form-producao').style.display = 'block';
        // Preencher select de receitas
        const select = document.getElementById('receita_id_producao');
        select.innerHTML = '<option value="">Selecione uma receita...</option>';
        receitas.forEach(receita => {
            const option = document.createElement('option');
            option.value = receita.id;
            option.textContent = receita.nome;
            select.appendChild(option);
        });
    });

    document.getElementById('btn-cancelar-producao').addEventListener('click', function () {
        document.getElementById('form-producao').style.display = 'none';
        document.getElementById('form-producao-form').reset();
    });

    document.getElementById('btn-validade').addEventListener('click', function () {
        const container = document.getElementById('controle-validade');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            carregarLotes();
            carregarAlertasValidade();
        } else {
            container.style.display = 'none';
        }
    });

    document.getElementById('btn-cadastrar-lote').addEventListener('click', function () {
        document.getElementById('form-lote').style.display = 'block';
    });

    document.getElementById('btn-cancelar-lote').addEventListener('click', function () {
        document.getElementById('form-lote').style.display = 'none';
        document.getElementById('form-lote-form').reset();
    });

    document.getElementById('btn-verificar-validade').addEventListener('click', verificarAlertasValidade);

    // Carregar dados iniciais
    carregarReceitas();
    carregarInsumos();
    carregarAlertasValidade();

    // Event listener para calcular preços quando margem de lucro muda
    document.getElementById('margem_lucro').addEventListener('input', function () {
        const margemLucro = parseFloat(this.value) || 0;
        if (margemLucro > 0) {
            // Se há uma receita sendo editada, calcular preços
            const receitaId = document.getElementById('receita_id_ingrediente').value;
            if (receitaId) {
                calcularPrecosReceita(receitaId, margemLucro);
            }
        }
    });
</script>

<?php include('footer.php'); ?>