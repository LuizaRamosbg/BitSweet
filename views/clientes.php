<?php include('header.php'); ?>

<div class="dashboard">
    <?php include('sidebar.php'); ?>

    <main class="content">
        <h2>Gerenciar Clientes</h2>
         <p>Gerenciamento e Cadastro de Clientes</p>
        
        <div class="card-dashboard">

            <div class="row mb-3">
                <div class="col-auto">
                    <button id="btn-novo-cliente" class="btn">➕ Novo Cliente</button>
                </div>
            </div>


            <table class="table">

                <div id="lista-clientes">
                    <h3>Lista de Clientes</h3>
                    <div id="clientes-container"></div>
                </div>

                <div id="mensagem"></div>

            </table>

            
            <div id="form-cliente" style="display:none; margin-bottom: 20px;">
                <h3 id="titulo-form-cliente">Novo Cliente</h3>
               
               
                <!-- Form atual - 2.0  -->
                <form id="cliente-formular" class="formulario rounded" style="align-itens:left;">
                    <div class="row">
                        <input type="hidden" id="cliente_id" name="id">
                        <div class="col">
                            <input type="text" id="cliente_nome" name="nome" class="form-control" placeholder="Nome" required>
                        </div>
                        <div class="col">
                            <input type="text" id="cliente_telefone" name="telefone" class="form-control" placeholder="Telefone">
                        </div>
                    </div>
                     <div class="row">
                        <input type="hidden" id="cliente_id" name="id">
                        <div class="col">
                            <input type="email" class="form-control" id="cliente_email" name="email" placeholder="E-mail">
                        </div>
                    </div>
                     <div class="row">
                        <div class="col">
                            <textarea type="text" id="cliente_endereco" name="endereco" class="form-control" placeholder="Endereço"></textarea>
                        </div>
                    </div>

                    <!-- Form antigo gui - 1.0
                    <div class="form-group">
                        <label for="cliente_nome">Nome:</label>
                        <input type="text" id="cliente_nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="cliente_telefone">Telefone:</label>
                        <input type="text" id="cliente_telefone" name="telefone">
                    </div>
                    <div class="form-group">
                        <label for="cliente_email">E-mail:</label>
                        <input type="email" class="form-control" id="cliente_email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="cliente_endereco">Endereço:</label>
                        <textarea id="cliente_endereco" name="endereco" rows="2"></textarea>
                    </div>
                    -->
                    <div style="margin-top:10px;">
                        <button type="submit" class="btn-enviar" id="btn-salvar-cliente">Salvar</button>
                        <button type="button" id="btn-cancelar-cliente" class="btn"
                            style="background-color:#141413;">Cancelar</button>

                    </div>
                </form>
            </div>

        </div>

    </main>
</div>
<script>
    let clientes = [];
    let modoEdicaoCliente = false;

    async function carregarClientes() {
        try {
            const res = await fetch('../api/clientes.php');
            const data = await res.json();
            if (data.success) {
                clientes = data.data;
                exibirClientes();
            }
        } catch (err) {
            console.error('Erro ao carregar clientes', err);
        }
    }

    function exibirClientes() {
        const container = document.getElementById('clientes-container');
        container.innerHTML = '';
        if (clientes.length === 0) {
            container.innerHTML = '<p>Nenhum cliente encontrado.</p>';
            return;
        }
        clientes.forEach(c => {
            const div = document.createElement('div');
            div.className = 'cliente-card';
            div.style.cssText = 'border:1px solid #ddd;border-radius:8px;padding:10px;margin-bottom:8px;';
            div.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h4>${c.nome}</h4>
                    <p>${c.telefone ? '<strong>Tel:</strong> ' + c.telefone : ''} ${c.email ? '<strong>E-mail:</strong> ' + c.email : ''}</p>
                    ${c.endereco ? '<p><strong>Endereço:</strong> ' + c.endereco + '</p>' : ''}
                </div>
                <div>
                    <button onclick="editarCliente(${c.id})" class="btn">✏️ Editar</button>
                    <button onclick="excluirCliente(${c.id})" class="btn" style="background-color:#dc3545;">🗑️ Excluir</button>
                </div>
            </div>
        `;
            container.appendChild(div);
        });
    }

    function mostrarMensagem(texto, tipo = 'success') {
        const container = document.getElementById('mensagem');
        const cor = tipo === 'success' ? '#d4edda' : '#f8d7da';
        const textoCor = tipo === 'success' ? '#155724' : '#721c24';
        container.innerHTML = `<div style="background-color:${cor};color:${textoCor};padding:12px;border-radius:8px;margin-top:10px;">${texto}</div>`;
        setTimeout(() => { container.innerHTML = ''; }, 3000);
    }

    document.getElementById('btn-novo-cliente').addEventListener('click', () => {
        modoEdicaoCliente = false;
        document.getElementById('cliente-formular').reset();
        document.getElementById('cliente_id').value = '';
        document.getElementById('titulo-form-cliente').textContent = 'Novo Cliente';
        document.getElementById('btn-salvar-cliente').textContent = 'Salvar';
        document.getElementById('form-cliente').style.display = 'block';
    });

    document.getElementById('btn-cancelar-cliente').addEventListener('click', () => {
        document.getElementById('form-cliente').style.display = 'none';
    });

    async function excluirCliente(id) {
        if (!confirm('Excluir cliente?')) return;
        try {
            const res = await fetch('../api/clientes.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (data.success) {
                mostrarMensagem('Cliente excluído', 'success');
                carregarClientes();
            } else mostrarMensagem(data.message || 'Erro', 'error');
        } catch (err) { console.error(err); mostrarMensagem('Erro ao excluir', 'error'); }
    }

    async function editarCliente(id) {
        try {
            const res = await fetch(`../api/clientes.php?id=${id}`);
            const data = await res.json();
            if (data.success) {
                const c = data.data;
                modoEdicaoCliente = true;
                document.getElementById('cliente_id').value = c.id;
                document.getElementById('cliente_nome').value = c.nome;
                document.getElementById('cliente_telefone').value = c.telefone || '';
                document.getElementById('cliente_email').value = c.email || '';
                document.getElementById('cliente_endereco').value = c.endereco || '';
                document.getElementById('titulo-form-cliente').textContent = 'Editar Cliente';
                document.getElementById('btn-salvar-cliente').textContent = 'Atualizar';
                document.getElementById('form-cliente').style.display = 'block';
            }
        } catch (err) { console.error(err); mostrarMensagem('Erro ao carregar cliente', 'error'); }
    }

    document.getElementById('cliente-formular').addEventListener('submit', async function (e) {
        e.preventDefault();
        const id = document.getElementById('cliente_id').value;
        const payload = {
            nome: document.getElementById('cliente_nome').value,
            telefone: document.getElementById('cliente_telefone').value,
            email: document.getElementById('cliente_email').value,
            endereco: document.getElementById('cliente_endereco').value
        };
        try {
            const method = id ? 'PUT' : 'POST';
            if (id) payload.id = id;
            const res = await fetch('../api/clientes.php', {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                mostrarMensagem(id ? 'Cliente atualizado' : 'Cliente criado', 'success');
                document.getElementById('form-cliente').style.display = 'none';
                carregarClientes();
            } else mostrarMensagem(data.message || 'Erro', 'error');
        } catch (err) { console.error(err); mostrarMensagem('Erro ao salvar cliente', 'error'); }
    });

    // load
    carregarClientes();
</script>

<?php include('footer.php'); ?>