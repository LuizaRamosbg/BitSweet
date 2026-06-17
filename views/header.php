<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>BitSweet</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css"
    integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
  <link rel="stylesheet" href="../assets/style.css">

</head>

<body>
  <header class="top-header">
    <nav class="top-nav">
      <div class="nav-links">
        <a class="btnheader" href="home.php">Início</a>
        <a class="btnheader" href="clientes.php">Clientes</a>
        <a class="btnheader" href="gerenciar_encomendas.php">Encomendas</a>
        <a class="btnheader" href="registrar_compras.php">Compras</a>
      </div>

      <div class="brand">
        <span>BitSweet</span>
        <img src="../assets/imagens/favoBitSweet.png" alt="BitSweet">
      </div>
    </nav>
  </header>
  <div id="validade-alert-banner"
    style="display:none; padding: 15px; background-color: #f8d7da; color: #721c24; text-align: center; font-weight: bold;">
    ⚠️ Há lotes com validade crítica. Verifique o controle de validade.
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', async function () {
      try {
        const response = await fetch('../api/validade.php?alertas=1');
        const data = await response.json();
        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
          const banner = document.getElementById('validade-alert-banner');
          banner.style.display = 'block';
          const expirados = data.data.filter(alerta => alerta.tipo_alerta === 'vencido').length;
          const proximos = data.data.filter(alerta => alerta.tipo_alerta === 'proximo_vencer').length;
          banner.innerHTML = `⚠️ Existem ${data.data.length} lote(s) com validade crítica: ${expirados} vencido(s) e ${proximos} próximo(s) ao vencimento. Acesse o controle de validade para ver detalhes.`;
        }
      } catch (error) {
        console.error('Erro ao carregar alertas de validade:', error);
      }
    });
  </script>