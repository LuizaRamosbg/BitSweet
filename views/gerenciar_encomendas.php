<?php include('header.php'); ?>

<div class="dashboard">
    <?php include('sidebar.php'); ?>

    <main class="content">
        <h2>Gerenciar Encomendas</h2>
        <p>Controle e Registro de Novas Encomendas</p>
        <div class="container">

            <div class="botoes-menu" style="margin-bottom: 30px;">
                <button id="btn-nova-encomenda" class="btn">➕ Nova Encomenda</button>
                <button id="btn-pendentes-hoje" class="btn">📅 Pendentes Hoje</button>
                <button id="btn-estatisticas" class="btn">📊 Estatísticas</button>
            </div>

            <!-- Formulário para nova/editar encomenda -->
            <div id="form-nova-encomenda" style="display: none; margin-bottom: 30px;">
                <h3 id="titulo-form-encomenda">Nova Encomenda</h3>
                

                <form id="form-encomenda" class="formulario rounded" style="gap: 15px;">
                    <input type="hidden" id="encomenda_id" name="id">

                    <div class="row">
                        <div class="col">
                            <select id="cliente_select_encomenda" class="form-control" name="cliente_id" placeholder="Cliente" required>
                                <option value="">Selecione um cliente...</option>
                            </select>
                        </div>
                         <div class="col">
                            <input type="text" id="cliente_telefone_display" class="form-control" disabled placeholder="(00) 00000-0000">
                         </div>
                    </div>
                     <div class="row">
                        <div class="col">    
                            <input type="email" class="form-control" id="cliente_email_display" placeholder="E-mail" disabled>
                            <input type="hidden" id="cliente_id_hidden" name="cliente_id">
                            
                        </div>
                    </div>
                       
                        <!-- Formulario - Versao 1.0 
                        <div class="row">
                            <input type="hidden" id="cliente_id" name="id">
                            <div class="col">
                                <input type="text" id="cliente_nome" name="nome" class="form-control" placeholder="Nome"
                                required>
                            </div>
                            <div class="col">
                                <input type="text" id="cliente_telefone" name="telefone" class="form-control"
                                placeholder="Telefone">
                            </div>
                        </div>
                        <div class="row">
                            <input type="hidden" id="cliente_id" name="id">
                            <div class="col">
                                <input type="email" class="form-control" id="cliente_email" name="email"
                                placeholder="E-mail">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <textarea type="text" id="cliente_endereco" name="endereco" class="form-control"
                                placeholder="Endereço"></textarea>
                            </div>
                        </div>
                        -->

                    <div class="row">
                        <div class="col">

                            <div id="itens_encomenda_section" style="margin-bottom: 15px;">
                                <label class="form-control">Itens da encomenda:</label>
                                <div id="itens_encomenda_lista"></div>
                                
                            </div>
                        </div>    
                             
                    </div>
                    
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn btn-sm " id="btn-adicionar-item" style="margin-top: 10px; padding-bottom:5px; background-color: #141413; ">➕ Adicionar item</button>
                        
                        </div>
                        
                    </div>


                    <div class="row">
                        <div class="col"> 
                            <label for="receita_id_encomenda">Receita:</label>
                            <select class="form-control" id="receita_id_encomenda" name="receita_id">
                                    <option class="form-control" value="">Selecione uma receita...</option>
                                </select>
                        </div>
                        <div class="col">
                            <label for="quantidade_encomenda">Quantidade:</label>
                            <input class="form-control" type="number" id="quantidade_encomenda" name="quantidade" step="0.01" value="1">

                        </div>
                        <div class="col"> 
                            <input class="form-control" type="hidden" id="preco_unitario_encomenda" name="preco_unitario" step="0.01">
                            <label for="preco_total_encomenda">Preço Total (R$):</label>
                            <input type="number" class="form-control" id="preco_total_encomenda" name="preco_total" step="0.01" readonly
                                style="background-color: #f0f0f0;">

                        </div>

                    </div>

                    <div class="row">
                        <div class="col">
                            <label for="data_entrega">Data de Entrega:</label>
                            <input class="form-control" type="date" id="data_entrega" name="data_entrega" required>

                        </div>
                        <div class="col">
                            <label for="status_encomenda">Status:</label>
                            <select class="form-control" id="status_encomenda" name="status">
                                <option value="pendente">Pendente</option>
                                <option value="em_producao">Em Produção</option>
                                <option value="pronta">Pronta</option>
                                <option value="entregue">Entregue</option>
                                <option value="cancelada">Cancelada</option>
                            </select>

                        </div>
                        <div class="col">
                            <label for="pago_encomenda">Pago:</label>
                            <input class="form-control" type="checkbox" id="pago_encomenda" name="pago" />
                        </div>
                    </div>

    

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                           
                        </div>
                    </div>

                    <div style="margin-top:10px;">
                        
                    </div>

                    <label for="observacoes_encomenda">Observações:</label>
                    <textarea id="observacoes_encomenda" name="observacoes" rows="3"></textarea>

                    <button type="submit" class="btn-enviar" style="background-color: #141413;" id="btn-salvar-encomenda">Salvar Encomenda</button>
                    <button type="button" id="btn-cancelar-encomenda" class="btn">Cancelar</button>
                </form>
            </div>

            <!-- Filtros -->
            <div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px;">
                <label for="filtro-status">Filtrar por Status:</label>
                <select id="filtro-status" style="margin-left: 10px; padding: 5px;">
                    <option value="">Todos</option>
                    <option value="pendente">Pendente</option>
                    <option value="em_producao">Em Produção</option>
                    <option value="pronta">Pronta</option>
                    <option value="entregue">Entregue</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>

            <!-- Lista de encomendas -->
            <div id="lista-encomendas">
                <h3>Lista de Encomendas</h3>
                <div id="encomendas-container"></div>
            </div>

            <!-- Estatísticas -->
            <div id="estatisticas-container" style="margin-top: 30px; display: none;">
                <h3>📊 Estatísticas</h3>
                <div id="estatisticas-conteudo"></div>
            </div>

            <div id="mensagem"></div>
        </div>
    </main>
</div>
<script>
    let encomendas = [];
    let receitas = [];
    let modoEdicao = false;

    function criarLinhaItemEncomenda(item = {}) {
        const container = document.getElementById('itens_encomenda_lista');
        const row = document.createElement('div');
        row.className = 'item-encomenda-row';
        row.style.cssText = 'display: grid; grid-template-columns: 1.5fr 0.8fr 0.8fr 0.3fr; gap: 10px; align-items: center; margin-bottom: 10px;';

        row.innerHTML = `
        <select class="item_receita_select" required>
            <option value="">Selecione uma receita...</option>
        </select>
        <input type="number" class="item_quantidade_input" placeholder="Quantidade" min="0.01" step="0.01" value="${item.quantidade_vendida ?? 1}" required />
        <input type="number" class="item_preco_input" placeholder="Preço unitário" min="0" step="0.01" value="${item.preco_unitario ?? ''}" required />
        <button type="button" class="btn btn-remover-item" style="background-color: #dc3545;">✖</button>
    `;

        const select = row.querySelector('.item_receita_select');
        const quantidadeInput = row.querySelector('.item_quantidade_input');
        const precoInput = row.querySelector('.item_preco_input');
        const removeButton = row.querySelector('.btn-remover-item');

        receitas.forEach(receita => {
            const option = document.createElement('option');
            option.value = receita.id;
            option.textContent = `${receita.nome} - R$ ${parseFloat(receita.preco_venda_sugerido).toFixed(2)}`;
            option.dataset.preco = receita.preco_venda_sugerido;
            if (item.receita_id && parseInt(item.receita_id) === receita.id) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        if (item.receita_id && !item.preco_unitario) {
            const option = select.options[select.selectedIndex];
            if (option && option.dataset.preco) {
                precoInput.value = parseFloat(option.dataset.preco).toFixed(2);
            }
        }

        select.addEventListener('change', function () {
            const option = this.options[this.selectedIndex];
            if (option && option.dataset.preco) {
                precoInput.value = parseFloat(option.dataset.preco).toFixed(2);
                atualizarTotalPedido();
            }
        });

        quantidadeInput.addEventListener('input', atualizarTotalPedido);
        precoInput.addEventListener('input', atualizarTotalPedido);

        removeButton.addEventListener('click', function () {
            row.remove();
            atualizarTotalPedido();
            if (document.querySelectorAll('#itens_encomenda_lista .item-encomenda-row').length === 0) {
                adicionarItemEncomenda();
            }
        });

        container.appendChild(row);
        atualizarTotalPedido();
    }

    function adicionarItemEncomenda(item = {}) {
        criarLinhaItemEncomenda(item);
    }

    function limparItensEncomenda() {
        const container = document.getElementById('itens_encomenda_lista');
        container.innerHTML = '';
    }

    function atualizarTotalPedido() {
        const rows = document.querySelectorAll('#itens_encomenda_lista .item-encomenda-row');
        let total = 0;

        rows.forEach(row => {
            const quantidade = parseFloat(row.querySelector('.item_quantidade_input').value) || 0;
            const preco = parseFloat(row.querySelector('.item_preco_input').value) || 0;
            total += quantidade * preco;
        });

        document.getElementById('preco_total_encomenda').value = total.toFixed(2);

        const firstRow = rows[0];
        if (firstRow) {
            document.getElementById('receita_id_encomenda').value = firstRow.querySelector('.item_receita_select').value;
            document.getElementById('quantidade_encomenda').value = firstRow.querySelector('.item_quantidade_input').value;
            document.getElementById('preco_unitario_encomenda').value = firstRow.querySelector('.item_preco_input').value;
        }
    }

    // Carregar receitas
    async function carregarReceitas() {
        try {
            const response = await fetch('../api/receitas.php');
            const data = await response.json();

            if (data.success) {
                receitas = data.data;
                preencherSelectReceitas();
            }
        } catch (error) {
            console.error('Erro ao carregar receitas:', error);
        }
    }

    // Carregar clientes
    async function carregarClientes() {
        try {
            const response = await fetch('../api/clientes.php');
            const data = await response.json();
            if (data.success) {
                const clientes = data.data;
                const select = document.getElementById('cliente_select_encomenda');
                select.innerHTML = '<option value="">Selecione um cliente...</option>';
                clientes.forEach(c => {
                    const option = document.createElement('option');
                    option.value = c.id;
                    option.textContent = `${c.nome}`;
                    option.dataset.telefone = c.telefone || '';
                    option.dataset.email = c.email || '';
                    select.appendChild(option);
                });
            }
        } catch (err) {
            console.error('Erro ao carregar clientes:', err);
        }
    }

    // Preencher select de receitas
    function preencherSelectReceitas() {
        const select = document.getElementById('receita_id_encomenda');
        select.innerHTML = '<option value="">Selecione uma receita...</option>';
        receitas.forEach(receita => {
            const option = document.createElement('option');
            option.value = receita.id;
            option.textContent = `${receita.nome} - R$ ${parseFloat(receita.preco_venda_sugerido).toFixed(2)}`;
            option.dataset.preco = receita.preco_venda_sugerido;
            select.appendChild(option);
        });
    }

    // Carregar encomendas
    async function carregarEncomendas(status = '') {
        try {
            const url = status ? `../api/encomendas.php?status=${status}` : '../api/encomendas.php';
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                encomendas = data.data;
                exibirEncomendas();
            }
        } catch (error) {
            console.error('Erro ao carregar encomendas:', error);
        }
    }

    // Exibir encomendas
    function exibirEncomendas() {
        const container = document.getElementById('encomendas-container');
        container.innerHTML = '';

        if (encomendas.length === 0) {
            container.innerHTML = '<p>Nenhuma encomenda encontrada.</p>';
            return;
        }

        encomendas.forEach(encomenda => {
            const div = document.createElement('div');
            div.className = 'encomenda-card';

            const statusColors = {
                'pendente': '#fff3cd',
                'em_producao': '#cfe2ff',
                'pronta': '#d1e7dd',
                'entregue': '#d4edda',
                'cancelada': '#f8d7da'
            };

            div.style.cssText = `
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background-color: ${statusColors[encomenda.status] || '#fff'};
        `;

            const itensHtml = Array.isArray(encomenda.items) && encomenda.items.length > 0
                ? `<div style="margin-top:10px;"><strong>Itens:</strong><ul style="margin: 6px 0 0 18px; padding:0; list-style: disc;">${encomenda.items.map(item => {
                    const precoTotalItem = item.preco_total !== null && item.preco_total !== undefined
                        ? parseFloat(item.preco_total).toFixed(2)
                        : (parseFloat(item.preco_unitario || 0) * parseFloat(item.quantidade_vendida || 0)).toFixed(2);
                    return `<li style="margin-bottom:4px;">${item.receita_nome} — ${parseFloat(item.quantidade_vendida).toFixed(2)} un • R$ ${precoTotalItem}</li>`;
                }).join('')}</ul></div>`
                : `<p><strong>Receita:</strong> ${encomenda.receita_nome}</p>`;

            div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <h4>${encomenda.cliente_nome}</h4>
                    ${itensHtml}
                    ${Array.isArray(encomenda.items) && encomenda.items.length > 0 ? '' : `<p><strong>Quantidade:</strong> ${encomenda.quantidade} | <strong>Preço Unitário:</strong> R$ ${parseFloat(encomenda.preco_unitario).toFixed(2)}</p>`}
                    <p><strong>Preço Total:</strong> R$ ${parseFloat(encomenda.preco_total).toFixed(2)}</p>
                    <p><strong>Data de Entrega:</strong> ${new Date(encomenda.data_entrega).toLocaleDateString()}</p>
                    <p><strong>Status:</strong> <span style="text-transform: capitalize;">${encomenda.status.replace('_', ' ')}</span></p>
                    ${encomenda.cliente_telefone ? `<p><strong>Telefone:</strong> ${encomenda.cliente_telefone}</p>` : ''}
                    <p><strong>Pago:</strong> ${encomenda.pago ? 'Sim' : 'Não'}</p>
                    ${encomenda.observacoes ? `<p><strong>Observações:</strong> ${encomenda.observacoes}</p>` : ''}
                </div>
                <div>
                    <button onclick="editarEncomenda(${encomenda.id})" class="btn" style="margin: 2px;">✏️ Editar</button>
                    <button onclick="atualizarStatusEncomenda(${encomenda.id}, '${encomenda.status}')" class="btn" style="margin: 2px;">🔄 Status</button>
                    <button onclick="excluirEncomenda(${encomenda.id})" class="btn" style="background-color: #dc3545; margin: 2px;">🗑️ Excluir</button>
                </div>
            </div>
        `;

            container.appendChild(div);
        });
    }

    // Editar encomenda
    async function editarEncomenda(id) {
        try {
            const response = await fetch(`../api/encomendas.php?id=${id}`);
            const data = await response.json();

            if (data.success) {
                const enc = data.data;
                modoEdicao = true;

                document.getElementById('encomenda_id').value = enc.id;
                // tentar selecionar cliente existente pelo nome; caso não encontre, criar opção temporária
                const selectClientes = document.getElementById('cliente_select_encomenda');
                let found = false;
                for (let i = 0; i < selectClientes.options.length; i++) {
                    if (selectClientes.options[i].textContent === enc.cliente_nome) {
                        selectClientes.value = selectClientes.options[i].value;
                        document.getElementById('cliente_id_hidden').value = selectClientes.options[i].value;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = enc.cliente_nome;
                    selectClientes.appendChild(opt);
                    selectClientes.value = '';
                    document.getElementById('cliente_id_hidden').value = '';
                }
                document.getElementById('cliente_telefone_display').value = enc.cliente_telefone || '';
                document.getElementById('cliente_email_display').value = enc.cliente_email || '';
                limparItensEncomenda();
                if (Array.isArray(enc.items) && enc.items.length > 0) {
                    enc.items.forEach(item => adicionarItemEncomenda(item));
                } else {
                    adicionarItemEncomenda({
                        receita_id: enc.receita_id,
                        quantidade_vendida: enc.quantidade,
                        preco_unitario: enc.preco_unitario
                    });
                }
                document.getElementById('preco_total_encomenda').value = enc.preco_total;
                document.getElementById('data_entrega').value = enc.data_entrega;
                document.getElementById('status_encomenda').value = enc.status;
                document.getElementById('pago_encomenda').checked = !!enc.pago;
                document.getElementById('observacoes_encomenda').value = enc.observacoes || '';

                document.getElementById('titulo-form-encomenda').textContent = 'Editar Encomenda';
                document.getElementById('btn-salvar-encomenda').textContent = 'Atualizar Encomenda';
                document.getElementById('form-nova-encomenda').style.display = 'block';
            }
        } catch (error) {
            console.error('Erro ao carregar encomenda:', error);
            mostrarMensagem('Erro ao carregar encomenda para edição', 'error');
        }
    }

    // Excluir encomenda
    async function excluirEncomenda(id) {
        if (!confirm('Tem certeza que deseja excluir esta encomenda?')) {
            return;
        }

        try {
            const response = await fetch('../api/encomendas.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            });

            const data = await response.json();

            if (data.success) {
                mostrarMensagem('Encomenda excluída com sucesso!', 'success');
                carregarEncomendas();
            } else {
                mostrarMensagem(data.message, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao excluir encomenda', 'error');
        }
    }

    // Atualizar status da encomenda
    async function atualizarStatusEncomenda(id, statusAtual) {
        const statuses = ['pendente', 'em_producao', 'pronta', 'entregue', 'cancelada'];
        const indexAtual = statuses.indexOf(statusAtual);
        const proximoStatus = statuses[indexAtual + 1] || statuses[0];

        if (!confirm(`Alterar status de "${statusAtual.replace('_', ' ')}" para "${proximoStatus.replace('_', ' ')}"?`)) {
            return;
        }

        try {
            const response = await fetch('../api/encomendas.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    atualizar_status: true,
                    id: id,
                    status: proximoStatus
                })
            });

            const data = await response.json();

            if (data.success) {
                mostrarMensagem('Status atualizado com sucesso!', 'success');
                carregarEncomendas();
            } else {
                mostrarMensagem(data.message, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao atualizar status', 'error');
        }
    }

    // Calcular preço total
    function calcularPrecoTotal() {
        atualizarTotalPedido();
    }

    // Salvar encomenda
    document.getElementById('form-encomenda').addEventListener('submit', async function (e) {
        e.preventDefault();

        const clienteIdVal = document.getElementById('cliente_id_hidden').value;
        const itemRows = document.querySelectorAll('#itens_encomenda_lista .item-encomenda-row');
        const itens = [];

        itemRows.forEach(row => {
            const receita_id = row.querySelector('.item_receita_select').value;
            const quantidade_vendida = parseFloat(row.querySelector('.item_quantidade_input').value) || 0;
            const preco_unitario = parseFloat(row.querySelector('.item_preco_input').value) || 0;
            if (receita_id && quantidade_vendida > 0) {
                itens.push({
                    receita_id: receita_id,
                    quantidade_vendida: quantidade_vendida,
                    preco_unitario: preco_unitario,
                    preco_total: quantidade_vendida * preco_unitario
                });
            }
        });

        const formData = {
            cliente_id: clienteIdVal || null,
            cliente_nome: clienteIdVal ? null : (document.getElementById('cliente_select_encomenda').selectedOptions[0]?.textContent || ''),
            cliente_telefone: document.getElementById('cliente_telefone_display').value || '',
            cliente_email: document.getElementById('cliente_email_display').value || '',
            receita_id: document.getElementById('receita_id_encomenda').value,
            quantidade: document.getElementById('quantidade_encomenda').value,
            preco_unitario: document.getElementById('preco_unitario_encomenda').value,
            preco_total: document.getElementById('preco_total_encomenda').value,
            data_entrega: document.getElementById('data_entrega').value,
            status: document.getElementById('status_encomenda').value,
            observacoes: document.getElementById('observacoes_encomenda').value,
            items: itens
        };
        // incluir info de pagamento
        formData.pago = document.getElementById('pago_encomenda').checked ? 1 : 0;

        if (modoEdicao) {
            formData.id = document.getElementById('encomenda_id').value;
        }

        try {
            const method = modoEdicao ? 'PUT' : 'POST';
            const response = await fetch('../api/encomendas.php', {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                mostrarMensagem(modoEdicao ? 'Encomenda atualizada com sucesso!' : 'Encomenda criada com sucesso!', 'success');
                // se backend retornou insumos faltantes, mostrar alerta detalhado
                if (data.data && data.data.missing_insumos && data.data.missing_insumos.length) {
                    let msg = 'ATENÇÃO: Insumos insuficientes para preparar a encomenda. Faltam:\n';
                    data.data.missing_insumos.forEach(item => {
                        msg += `- ${item.nome}: ${parseFloat(item.missing).toFixed(2)} ${item.unidade || ''} (necessário ${parseFloat(item.required).toFixed(2)}, disponível ${parseFloat(item.available).toFixed(2)})\n`;
                    });
                    alert(msg);
                }
                document.getElementById('form-nova-encomenda').style.display = 'none';
                document.getElementById('form-encomenda').reset();
                modoEdicao = false;
                document.getElementById('titulo-form-encomenda').textContent = 'Nova Encomenda';
                document.getElementById('btn-salvar-encomenda').textContent = 'Salvar Encomenda';
                carregarEncomendas();
            } else {
                mostrarMensagem(data.message, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao salvar encomenda', 'error');
        }
    });

    // Carregar estatísticas
    async function carregarEstatisticas() {
        try {
            const response = await fetch('../api/encomendas.php?estatisticas=1');
            const data = await response.json();

            if (data.success) {
                const stats = data.data;
                const container = document.getElementById('estatisticas-conteudo');

                container.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div style="background-color: #e3f2fd; padding: 15px; border-radius: 8px;">
                        <h4>Total</h4>
                        <p style="font-size: 24px; font-weight: bold;">${stats.total_encomendas}</p>
                    </div>
                    <div style="background-color: #fff3e0; padding: 15px; border-radius: 8px;">
                        <h4>Pendentes</h4>
                        <p style="font-size: 24px; font-weight: bold;">${stats.encomendas_pendentes}</p>
                    </div>
                    <div style="background-color: #cfe2ff; padding: 15px; border-radius: 8px;">
                        <h4>Em Produção</h4>
                        <p style="font-size: 24px; font-weight: bold;">${stats.encomendas_em_producao}</p>
                    </div>
                    <div style="background-color: #d1e7dd; padding: 15px; border-radius: 8px;">
                        <h4>Prontas</h4>
                        <p style="font-size: 24px; font-weight: bold;">${stats.encomendas_prontas}</p>
                    </div>
                    <div style="background-color: #d4edda; padding: 15px; border-radius: 8px;">
                        <h4>Entregues</h4>
                        <p style="font-size: 24px; font-weight: bold;">${stats.encomendas_entregues}</p>
                    </div>
                    <div style="background-color: #f8d7da; padding: 15px; border-radius: 8px;">
                        <h4>Canceladas</h4>
                        <p style="font-size: 24px; font-weight: bold;">${stats.encomendas_canceladas}</p>
                    </div>
                    <div style="background-color: #d1ecf1; padding: 15px; border-radius: 8px;">
                        <h4>Valor Total</h4>
                        <p style="font-size: 24px; font-weight: bold;">R$ ${parseFloat(stats.valor_total).toFixed(2)}</p>
                    </div>
                    <div style="background-color: #d4edda; padding: 15px; border-radius: 8px;">
                        <h4>Valor Entregue</h4>
                        <p style="font-size: 24px; font-weight: bold;">R$ ${parseFloat(stats.valor_entregue).toFixed(2)}</p>
                    </div>
                </div>
            `;
            }
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
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
    document.getElementById('btn-nova-encomenda').addEventListener('click', function () {
        modoEdicao = false;
        document.getElementById('form-encomenda').reset();
        document.getElementById('encomenda_id').value = '';
        limparItensEncomenda();
        adicionarItemEncomenda();
        document.getElementById('titulo-form-encomenda').textContent = 'Nova Encomenda';
        document.getElementById('btn-salvar-encomenda').textContent = 'Salvar Encomenda';
        document.getElementById('form-nova-encomenda').style.display = 'block';
    });

    document.getElementById('btn-cancelar-encomenda').addEventListener('click', function () {
        document.getElementById('form-nova-encomenda').style.display = 'none';
        document.getElementById('form-encomenda').reset();
        modoEdicao = false;
    });

    document.getElementById('btn-pendentes-hoje').addEventListener('click', function () {
        carregarEncomendas('pendente');
    });

    document.getElementById('btn-estatisticas').addEventListener('click', function () {
        const container = document.getElementById('estatisticas-container');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            carregarEstatisticas();
        } else {
            container.style.display = 'none';
        }
    });

    document.getElementById('filtro-status').addEventListener('change', function () {
        const status = this.value;
        carregarEncomendas(status);
    });

    document.getElementById('btn-adicionar-item').addEventListener('click', function () {
        adicionarItemEncomenda();
    });

    function calcularPrecoTotal() {
        atualizarTotalPedido();
    }

    // Quando selecionar cliente, preencher telefone/email ocultos
    document.getElementById('cliente_select_encomenda').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        document.getElementById('cliente_telefone_display').value = option.dataset.telefone || '';
        document.getElementById('cliente_email_display').value = option.dataset.email || '';
        document.getElementById('cliente_id_hidden').value = option.value || '';
    });

    // Calcular preço total quando quantidade ou preço unitário mudar
    document.getElementById('quantidade_encomenda').addEventListener('input', calcularPrecoTotal);
    document.getElementById('preco_unitario_encomenda').addEventListener('input', calcularPrecoTotal);

    // Carregar dados iniciais
    carregarReceitas();
    carregarEncomendas();
    carregarClientes();
</script>

<?php include('footer.php'); ?>