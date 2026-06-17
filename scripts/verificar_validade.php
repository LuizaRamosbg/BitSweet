<?php
/**
 * Script para verificação automática de validade de insumos
 * Sistema de Gestão da Doceria
 * 
 * Este script deve ser executado via cron job diariamente
 * Exemplo de cron: 0 8 * * * /usr/bin/php /caminho/para/verificar_validade.php
 */

require_once 'config/database.php';
require_once 'models/ControleValidade.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $controle = new ControleValidade($db);
    
    // Verificar e gerar alertas de validade
    $alertas_gerados = $controle->verificarAlertasValidade();
    
    // Obter estatísticas
    $estatisticas = $controle->obterEstatisticasValidade();
    
    // Log da execução
    $log_message = date('Y-m-d H:i:s') . " - Verificação de validade concluída. ";
    $log_message .= "Novos alertas: {$alertas_gerados}. ";
    $log_message .= "Lotes próximos ao vencimento: {$estatisticas['lotes_proximos_vencer']}. ";
    $log_message .= "Lotes vencidos: {$estatisticas['lotes_vencidos']}";
    
    error_log($log_message);
    
    // Se houver lotes vencidos, enviar notificação
    if($estatisticas['lotes_vencidos'] > 0) {
        error_log("ALERTA CRÍTICO: {$estatisticas['lotes_vencidos']} lotes vencidos encontrados!");
    }
    
    echo "Verificação de validade concluída com sucesso!\n";
    echo "Alertas gerados: {$alertas_gerados}\n";
    echo "Lotes próximos ao vencimento: {$estatisticas['lotes_proximos_vencer']}\n";
    echo "Lotes vencidos: {$estatisticas['lotes_vencidos']}\n";
    
} catch(Exception $e) {
    error_log("Erro na verificação de validade: " . $e->getMessage());
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
