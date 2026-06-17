<?php
require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/Response.php';

abstract class Controller {
    protected $db;
    protected $input;
    protected $method;
    protected Request $request;
    protected Response $response;

    public function __construct($db) {
        $this->db = $db;
        $this->request = new Request();
        $this->response = new Response();
        $this->input = $this->request->allBody();
        $this->method = $this->request->getMethod();
    }

    protected function dispatch(array $handlers): void {
        $method = $this->request->getMethod();
        if (isset($handlers[$method]) && method_exists($this, $handlers[$method])) {
            $handler = $handlers[$method];
            $this->{$handler}();
            return;
        }
        $this->sendError('Método não permitido', 405);
    }

    protected function getQuery(string $key, $default = null) {
        return $this->request->getQuery($key, $default);
    }

    protected function getBody(string $key, $default = null) {
        return $this->request->getBody($key, $default);
    }

    protected function hasBody(string $key): bool {
        return $this->request->hasBody($key);
    }

    protected function fetchAll(PDOStatement $stmt): array {
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    protected function sendJson(array $data, int $status = 200): void {
        $this->response->sendJson($data, $status);
    }

    protected function sendSuccess(array $data = [], string $message = 'Operação concluída', int $status = 200): void {
        $this->response->sendSuccess($data, $message, $status);
    }

    protected function sendError(string $message = 'Erro', int $status = 400): void {
        $this->response->sendError($message, $status);
    }
}
