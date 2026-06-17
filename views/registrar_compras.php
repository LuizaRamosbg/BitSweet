<?php include('header.php'); ?>

<div class="dashboard">
    <?php include('sidebar.php'); ?>
    
    <main class="content">
        <h2>Registrar Compra de Insumos</h2>
        <p>Adiciona Preços Atualizados da Nova Compra</p>
        
            <div class="container">

                
                <form id="form-compras" class="formulario" method="POST" action="">
                    <label for="insumo_id">Insumo:</label>
                    <select class="form-control" id="insumo_id" name="insumo_id" required>
                        <option value="">Selecione um insumo...</option>
                    </select>
                    
                    <div class="row">
                        <div class="col">

                            <label for="quantidade">Quantidade:</label>
                            <input class="form-control" type="number" id="quantidade" name="quantidade" step="0.001" required>
                        </div>
                        <div class="col">
    
                            <label for="preco_total">Preço Total (R$):</label>
                            <input class="form-control" type="number" id="preco_total" name="preco_total" step="0.01" required>
                        </div>
                    </div>
                    
                    <label for="fornecedor">Fornecedor:</label>
                    <input class="form-control" type="text" id="fornecedor" name="fornecedor">
                    
                    <label for="lote">Lote (opcional):</label>
                    <input class="form-control" type="text" id="lote" name="lote" placeholder="Ex: L001">
                    
                    <div class="row">
                        <div class="col">

                            <label for="data_fabricacao">Data de Fabricação:</label>
                            <input class="form-control" type="date" id="data_fabricacao" name="data_fabricacao">
                        </div>
                        <div class="col">

                            <label for="data_validade">Data de Validade:</label>
                            <input class="form-control" type="date" id="data_validade" name="data_validade">
                        </div>
                        <div class="col">

                            <label for="data_compra">Data da Compra:</label>
                            <input class="form-control" type="date" id="data_compra" name="data_compra" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <label for="observacoes">Observações:</label>
                    <textarea id="observacoes" name="observacoes" rows="3"></textarea>
                    
                    <button type="submit" class="btn-enviar">Registrar Compra</button>
                </form>
            </div>
        </div>
        
        <div id="mensagem"></div>
        <div id="custo-unitario" style="margin-top: 20px; padding: 15px; background-color: #f0f8ff; border-radius: 8px; display: none;">
            <h3>💰 Cálculo de Custo</h3>
            <p><strong>Custo Unitário:</strong> R$ <span id="custo-unitario-valor">0,00</span></p>
        </div>
    </main>
</div>

<script>
// Carregar lista de insumos
async function carregarInsumos() {
    try {
        const response = await fetch('../api/insumos.php');
        const data = await response.json();
        
        if(data.success) {
            const select = document.getElementById('insumo_id');
            data.data.forEach(insumo => {
                const option = document.createElement('option');
                option.value = insumo.id;
                const unidadeExibicao = insumo.unidade_compra === 'kg' && insumo.fator_conversao === 1000 ? 'kg' : 
                                       insumo.unidade_compra === 'L' && insumo.fator_conversao === 1000 ? 'L' : 
                                       insumo.unidade_compra;
                const estoqueExibicao = insumo.unidade_compra === 'kg' && insumo.fator_conversao === 1000 ? 
                                       (insumo.estoque_atual / 1000).toFixed(3) :
                                       insumo.unidade_compra === 'L' && insumo.fator_conversao === 1000 ?
                                       (insumo.estoque_atual / 1000).toFixed(3) :
                                       insumo.estoque_atual;
                option.textContent = `${insumo.nome} (${unidadeExibicao}) - Estoque: ${estoqueExibicao}`;
                select.appendChild(option);
            });
        }
    } catch(error) {
        console.error('Erro ao carregar insumos:', error);
    }
}

// Calcular custo unitário em tempo real
function calcularCustoUnitario() {
    const quantidade = parseFloat(document.getElementById('quantidade').value) || 0;
    const precoTotal = parseFloat(document.getElementById('preco_total').value) || 0;
    
    if(quantidade > 0 && precoTotal > 0) {
        const custoUnitario = precoTotal / quantidade;
        document.getElementById('custo-unitario-valor').textContent = custoUnitario.toFixed(2);
        document.getElementById('custo-unitario').style.display = 'block';
    } else {
        document.getElementById('custo-unitario').style.display = 'none';
    }
}

// Registrar compra
document.getElementById("form-compras").addEventListener("submit", async function(e) {
    e.preventDefault();
    
    const formData = {
        insumo_id: document.getElementById('insumo_id').value,
        quantidade: document.getElementById('quantidade').value,
        preco_total: document.getElementById('preco_total').value,
        fornecedor: document.getElementById('fornecedor').value,
        lote: document.getElementById('lote').value,
        data_fabricacao: document.getElementById('data_fabricacao').value,
        data_validade: document.getElementById('data_validade').value,
        data_compra: document.getElementById('data_compra').value,
        observacoes: document.getElementById('observacoes').value
    };
    
    try {
        const response = await fetch('../api/compras.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if(data.success) {
            const custoUnitario = data.data && typeof data.data.custo_unitario === 'number'
                ? data.data.custo_unitario
                : parseFloat(data.data?.custo_unitario) || 0;

            document.getElementById("mensagem").innerHTML = `
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-top: 20px;">
                    <h3>✅ Compra registrada com sucesso!</h3>
                    <p><strong>Custo Unitário Calculado:</strong> R$ ${custoUnitario.toFixed(2)}</p>
                    <p><strong>ID da Compra:</strong> ${data.data.id}</p>
                </div>
            `;
            
            // Limpar formulário
            document.getElementById("form-compras").reset();
            document.getElementById('data_compra').value = new Date().toISOString().split('T')[0];
            document.getElementById('custo-unitario').style.display = 'none';
            
            // Recarregar lista de insumos para atualizar estoque
            carregarInsumos();
        } else {
            document.getElementById("mensagem").innerHTML = `
                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-top: 20px;">
                    <h3>❌ Erro ao registrar compra</h3>
                    <p>${data.message}</p>
                </div>
            `;
        }
    } catch(error) {
        console.error('Erro:', error);
        document.getElementById("mensagem").innerHTML = `
            <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-top: 20px;">
                <h3>❌ Erro de conexão</h3>
                <p>Não foi possível conectar com o servidor.</p>
            </div>
        `;
    }
});

// Event listeners para cálculo em tempo real
document.getElementById('quantidade').addEventListener('input', calcularCustoUnitario);
document.getElementById('preco_total').addEventListener('input', calcularCustoUnitario);

// Carregar insumos ao inicializar a página
carregarInsumos();
</script>

<?php include('footer.php'); ?>
