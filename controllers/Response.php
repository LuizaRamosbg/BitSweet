<?php

class Response {
    public function sendJson(array $data, int $status = 200): void {
        if (ob_get_length() !== false && ob_get_length() > 0) {
            ob_clean();
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function sendSuccess(array $data = [], string $message = 'Operação concluída', int $status = 200): void {
        $payload = ['success' => true, 'message' => $message];
        if (!empty($data)) {
            if (array_key_exists('data', $data) && count($data) === 1) {
                $payload['data'] = $data['data'];
            } else {
                $payload['data'] = $data;
            }
        }
        $this->sendJson($payload, $status);
    }

    public function sendError(string $message = 'Erro', int $status = 400): void {
        $this->sendJson(['success' => false, 'message' => $message], $status);
    }
}
