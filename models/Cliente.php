<?php
require_once __DIR__ . '/../config/database.php';

class Cliente {
    private $conn;
    private $table_name = 'clientes';

    public $id;
    public $nome;
    public $telefone;
    public $email;
    public $endereco;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " (nome, telefone, email, endereco) VALUES (:nome, :telefone, :email, :endereco)";
        $stmt = $this->conn->prepare($query);
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->telefone = htmlspecialchars(strip_tags($this->telefone));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->endereco = htmlspecialchars(strip_tags($this->endereco));
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':telefone', $this->telefone);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':endereco', $this->endereco);
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function listar($limite = 100) {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nome ASC LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->telefone = $row['telefone'];
            $this->email = $row['email'];
            $this->endereco = $row['endereco'];
            return true;
        }
        return false;
    }

    public function atualizar() {
        $query = "UPDATE " . $this->table_name . " SET nome = :nome, telefone = :telefone, email = :email, endereco = :endereco WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->telefone = htmlspecialchars(strip_tags($this->telefone));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->endereco = htmlspecialchars(strip_tags($this->endereco));
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':telefone', $this->telefone);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':endereco', $this->endereco);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function excluir() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}

?>
