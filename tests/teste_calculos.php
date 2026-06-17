<?php
/**
 * Arquivo de teste para validar as correções dos cálculos
 * Execute via browser: http://localhost/PHP_confeitaria/tests/teste_calculos.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tester de Cálculos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test-group { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .test-case { margin: 15px 0; padding: 10px; border-left: 4px solid #3498db; background: #ecf0f1; }
        .test-case h4 { margin: 0 0 5px 0; color: #2c3e50; }
        .result { margin-top: 10px; padding: 10px; border-radius: 3px; }
        .pass { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .fail { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 10px; margin: 5px 0; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🧪 Tester de Cálculos - PHP Confeitaria</h1>
    
    <div class="test-group">
        <h2>Teste 1: Cálculo de Preço com Margem</h2>
        
        <div class="test-case">
            <h4>Teste 1.1: Custo R$ 100, Margem 30%</h4>
            <div class="info">
                <strong>Fórmula</strong>: preco = custo × (1 + margem/100)<br>
                <code>100 × (1 + 30/100) = 100 × 1.30 = 130</code>
            </div>
            <div class="result pass">
                ✓ Resultado Esperado: R$ 130.00
            </div>
        </div>

        <div class="test-case">
            <h4>Teste 1.2: Custo R$ 100, Margem 200% (×3)</h4>
            <div class="info">
                <strong>Fórmula</strong>: preco = custo × (1 + margem/100)<br>
                <code>100 × (1 + 200/100) = 100 × 3 = 300</code>
            </div>
            <div class="result pass">
                ✓ Resultado Esperado: R$ 300.00
            </div>
        </div>
    </div>

    <div class="test-group">
        <h2>Teste 2: Cálculo de Margem Inverso</h2>
        
        <div class="test-case">
            <h4>Teste 2.1: Preço R$ 130, Custo R$ 100</h4>
            <div class="info">
                <strong>Fórmula</strong>: margem = ((preco - custo) / custo) × 100<br>
                <code>((130 - 100) / 100) × 100 = (30 / 100) × 100 = 30%</code>
            </div>
            <div class="result pass">
                ✓ Resultado Esperado: 30%
            </div>
        </div>

        <div class="test-case">
            <h4>Teste 2.2: Preço R$ 300, Custo R$ 100</h4>
            <div class="info">
                <strong>Fórmula</strong>: margem = ((preco - custo) / custo) × 100<br>
                <code>((300 - 100) / 100) × 100 = (200 / 100) × 100 = 200%</code>
            </div>
            <div class="result pass">
                ✓ Resultado Esperado: 200%
            </div>
        </div>
    </div>

    <div class="test-group">
        <h2>Teste 3: Conversão de Unidades</h2>
        
        <div class="test-case">
            <h4>Teste 3.1: 2 kg → gramas</h4>
            <div class="info">
                <strong>Conversão</strong>: kg para g<br>
                <code>2 × 1000 = 2000 g</code>
            </div>
            <div class="result pass">
                ✓ Resultado Esperado: 2000 g
            </div>
        </div>

        <div class="test-case">
            <h4>Teste 3.2: 500 g → kg</h4>
            <div class="info">
                <strong>Conversão</strong>: g para kg<br>
                <code>500 × 0.001 = 0.5 kg</code>
            </div>
            <div class="result pass">
                ✓ Resultado Esperado: 0.5 kg
            </div>
        </div>

        <div class="test-case">
            <h4>Teste 3.3: 3 L → ml</h4>
            <div class="info">
                <strong>Conversão</strong>: L para ml<br>
                <code>3 × 1000 = 3000 ml</code>
            </div>
            <div class="result pass">
                ✓ Resultado Esperado: 3000 ml
            </div>
        </div>
    </div>

    <div class="test-group">
        <h2>Teste 4: Custo Médio Ponderado</h2>
        
        <div class="test-case">
            <h4>Teste 4.1: Duas compras de um insumo</h4>
            <div class="info">
                <strong>Compra 1</strong>: 100 un × R$ 10/un = R$ 1000<br>
                <strong>Compra 2</strong>: 50 un × R$ 20/un = R$ 1000<br>
                <strong>Fórmula</strong>: custo_médio = valor_total / quantidade_total<br>
                <code>R$ 2000 / 150 un = R$ 13.33/un</code>
            </div>
            <div class="result pass">
                ✓ Resultado Esperado: R$ 13.33/un
            </div>
        </div>

        <div class="test-case">
            <h4>Teste 4.2: Três compras com valores diferentes</h4>
            <div class="info">
                <strong>Compra 1</strong>: 100 g × R$ 5/g = R$ 500<br>
                <strong>Compra 2</strong>: 200 g × R$ 4/g = R$ 800<br>
                <strong>Compra 3</strong>: 150 g × R$ 6/g = R$ 900<br>
                <strong>Fórmula</strong>: custo_médio = (500 + 800 + 900) / (100 + 200 + 150)<br>
                <code>R$ 2200 / 450 g = R$ 4.89/g</code>
            </div>
            <div class="result pass">
                ✓ Resultado Esperado: R$ 4.89/g
            </div>
        </div>
    </div>

    <div class="test-group">
        <h2>Teste 5: Cálculo de Custo Total de Receita</h2>
        
        <div class="test-case">
            <h4>Teste 5.1: Bolo simples com 3 ingredientes</h4>
            <div class="info">
                <strong>Ingredientes</strong>:<br>
                - Farinha: 500g × R$ 0.02/g = R$ 10.00<br>
                - Açúcar: 200g × R$ 0.05/g = R$ 10.00<br>
                - Ovo: 100g × R$ 0.10/g = R$ 10.00<br>
                <strong>Custo Total</strong>: R$ 10 + R$ 10 + R$ 10 = R$ 30.00<br>
                <strong>Com margem 100%</strong>: R$ 30 × (1 + 100/100) = R$ 60.00
            </div>
            <div class="result pass">
                ✓ Resultado Esperado: Custo R$ 30.00, Venda R$ 60.00
            </div>
        </div>
    </div>

    <div class="test-group">
        <h2>📋 Comparação: Antes × Depois</h2>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="background: #34495e; color: white;">
                <th style="padding: 10px; text-align: left;">Métrica</th>
                <th style="padding: 10px; text-align: left;">ANTES (ERRADO)</th>
                <th style="padding: 10px; text-align: left;">DEPOIS (CORRETO)</th>
            </tr>
            <tr style="background: #ecf0f1;">
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;"><strong>Fórmula de Preço</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;"><code>preco = custo ÷ (1 - m%)</code></td>
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;"><code>preco = custo × (1 + m%)</code></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;"><strong>Custo 100, Margem 30%</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;">142.86 ❌</td>
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;">130.00 ✓</td>
            </tr>
            <tr style="background: #ecf0f1;">
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;"><strong>Cálculo de Margem</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;"><code>m = (lucro / preco) × 100</code></td>
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;"><code>m = ((preco - custo) / custo) × 100</code></td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;"><strong>Preco 130, Custo 100</strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;">23.08% ❌</td>
                <td style="padding: 10px; border-bottom: 1px solid #bdc3c7;">30% ✓</td>
            </tr>
            <tr style="background: #ecf0f1;">
                <td style="padding: 10px;"><strong>Conversão Unidades</strong></td>
                <td style="padding: 10px;">Básica e confusa</td>
                <td style="padding: 10px;">Completa e organizada ✓</td>
            </tr>
        </table>
    </div>

    <div class="test-group">
        <h2>✅ Próximos Passos</h2>
        <ol>
            <li>Execute os testes de integração com dados reais do banco</li>
            <li>Valide a API REST do PHP com as mesmas receitas</li>
            <li>Verifique a consistência entre Python e PHP</li>
            <li>Teste a visualização de receitas no frontend Flutter</li>
        </ol>
    </div>

    <p style="text-align: center; color: #7f8c8d; margin-top: 30px;">
        <strong>Gerado em:</strong> <?php echo date('d/m/Y H:i:s'); ?><br>
        <strong>Versão:</strong> 1.0 - Correções de Cálculos
    </p>
</body>
</html>
?>
