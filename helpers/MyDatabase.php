<?php

class MyDatabase {

    private $conexion;

    public function __construct($hostname, $username, $password, $database, $port = null) {
        if ($port) {
            $this->conexion = new mysqli($hostname, $username, $password, $database, $port);
        } else {
            $this->conexion = new mysqli($hostname, $username, $password, $database);
        }
         $this->conexion->set_charset("utf8mb4");
    }

    public function query($sql, $params = []) {
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function execute($sql, $params = []) {
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $this->conexion->affected_rows;
    }

    public function __destruct() {
        $this->conexion->close();
    }
}

?>