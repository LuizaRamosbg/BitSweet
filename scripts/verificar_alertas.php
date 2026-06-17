<?php
/**
 * Script para verificação automática de alertas de estoque
 * Sistema de Gestão da Doceria
 * 
 * Este script deve ser executado via cron job diariamente
 * Exemplo de cron: 0 9 * * * /usr/bin/php /caminho/para/verificar_alertas.php
 */

require_once 'config/database.php';
require_once 'models/AlertaEstoque.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $alerta = new AlertaEstoque($db);
    
    // Verificar alertas do estoque a partir do estado atual do sistema
    $alertas = $alerta->obterAlertasAtuais();
    
    // Obter estatísticas atuais
    $estatisticas = $alerta->obterEstatisticasAtuais();
    
    // Log da execução
    $log_message = date('Y-m-d H:i:s') . " - Verificação de alertas concluída. ";
    $log_message .= "Alertas encontrados: " . count($alertas) . ". ";
    $log_message .= "Alertas críticos: {$estatisticas['alertas_estoque_zerado']}";
    
    error_log($log_message);
    
    echo "Verificação concluída com sucesso!\n";
    echo "Alertas encontrados: " . count($alertas) . "\n";
    echo "Alertas críticos: {$estatisticas['alertas_estoque_zerado']}\n";
    
} catch(Exception $e) {
    error_log("Erro na verificação de alertas: " . $e->getMessage());
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
