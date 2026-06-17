<?php include('header.php'); ?>


<div class="dashboard">

    <?php include('sidebar.php'); ?>

    <!-- CONTEÚDO -->
    <main class="content">

        <h2>Início</h2>
        <p>Sistema de Gestão da Doceria</p>

        <!-- CARDS -->
        <div class="cards">

            <div class="card-dashboard">
                <span>Receita este mês</span>
                <h2>R$ 7.400</h2>
                <small>+18%</small>
            </div>

            <div class="card-dashboard">
                <span>Encomendas Ativas</span>
                <h2>12</h2>
                <small>4 para hoje</small>
            </div>

            <div class="card-dashboard">
                <span>Clientes Cadastrados</span>
                <h2>48</h2>
                <small>+3 este mês</small>
            </div>

            <div class="card-dashboard">
                <span>Insumos em Falta</span>
                <h2>3</h2>
                <small>Atenção!</small>
            </div>

        </div>

        <!-- GRÁFICO -->
        <div class="grafico">

            <h4>Receita Mensal</h4>

            <div class="grafico-fake">
                Área do gráfico
            </div>

        </div>

        <!-- TABELA -->
        <div class="tabela-dashboard">

            <h4>Encomendas Recentes</h4>

            <table class="table">

                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Produto</th>
                        <th>Status</th>
                        <th>Valor</th>
                    </tr>
                </thead>

                <tbody>

                    <tr>
                        <td>E001</td>
                        <td>Ana Souza</td>
                        <td>Bolo de Brigadeiro</td>
                        <td>Pendente</td>
                        <td>R$180</td>
                    </tr>

                    <tr>
                        <td>E002</td>
                        <td>Carlos Lima</td>
                        <td>Torta de Morango</td>
                        <td>Confirmado</td>
                        <td>R$240</td>
                    </tr>

                </tbody>

            </table>

        </div>

    </main>

</div>

<!--<div class="container-fluid p-0">
    <div class="row no-gutters dashboard">
        <div class="col-md-3 col-lg-2 sidebar shadow-sm">

            <a href="clientes.php" class="menu-item">👩‍💼 Gerenciar Clientes</a>
            
            <a href="gerenciar_encomendas.php" class="menu-item">🧾 Nova Encomenda</a>
                 
            <a href="registrar_compras.php" class="menu-item"> 🛒 Registrar Compras</a>
               
            <a href="gerenciar_insumos.php" class="menu-item">📦 Gerenciar Insumos</a>                      

            <a href="gerenciar_receitas.php" class="menu-item">👨‍🍳 Gerenciar Receitas</a>
            
            <a href="gerenciar_encomendas.php" class="menu-item">📋 Gerenciar Encomendas</a>

        </div>

        <div class="col content-area shadow-sm" style="align-items: center; justify-items: center;">
            <h1>Sistema de Gestão LeFerFe</h1>
            <p>Bem-vinda ao seu painel de controle!</p>
        </div>

    </div>
</div>


<?php include('footer.php'); ?>