<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/EncomendaController.php';

// Ensure PHP errors/exceptions are returned as JSON to avoid HTML responses
set_error_handler(function($severity, $message, $file, $line) {
	throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
	http_response_code(500);
	header('Content-Type: application/json');
	echo json_encode([
		'success' => false,
		'message' => 'Internal Server Error',
		'error' => $e->getMessage()
	], JSON_UNESCAPED_UNICODE);
	exit;
});

try {
	$database = new Database();
	$db = $database->getConnection();
	if ($db === null) {
		http_response_code(500);
		header('Content-Type: application/json');
		echo json_encode(['success' => false, 'message' => 'Falha na conexão com o banco de dados'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$controller = new EncomendaController($db);
	$controller->handle();
} catch (Exception $e) {
	// log minimal exception to file for debugging
	@file_put_contents(__DIR__ . '/../logs/encomendas_error.log', date('c') . " - " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
	http_response_code(500);
	header('Content-Type: application/json');
	echo json_encode(['success' => false, 'message' => 'Erro no servidor', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
