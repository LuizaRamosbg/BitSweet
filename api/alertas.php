<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AlertaEstoqueController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new AlertaEstoqueController($db);
$controller->handle();
