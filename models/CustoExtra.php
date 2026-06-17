<?php
/**
 * Modelo para gerenciar Custos Extras (embalagens, etiquetas, fitas)
 */

require_once __DIR__ . '/../config/database.php';

class CustoExtra {
    private $conn;
    private $table_name = "custo_extra";

    public $id;
    public $id_receita;
    public $descricao;
    public $valor;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " (id_receita, descricao, valor) VALUES (:id_receita, :descricao, :valor)";
        $stmt = $this->conn->prepare($query);

        $this->descricao = htmlspecialchars(strip_tags($this->descricao));

        $stmt->bindParam(':id_receita', $this->id_receita, PDO::PARAM_INT);
        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':valor', $this->valor);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function listarPorReceita($receita_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_receita = :id_receita ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_receita', $receita_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function somaPorReceita($receita_id) {
        $query = "SELECT SUM(valor) as total_extras FROM " . $this->table_name . " WHERE id_receita = :id_receita";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_receita', $receita_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_extras'] ?? 0;
    }

    public function excluir($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function atualizar() {
        $query = "UPDATE " . $this->table_name . " SET descricao = :descricao, valor = :valor WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));

        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':valor', $this->valor);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
?>
