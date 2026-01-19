<?php
class Patient {
    private $conn;
    function __construct($c){ $this->conn = $c; }

    function create($uid, $d){
        $q = $this->conn->prepare(
            "INSERT INTO patients 
            (user_id, name, phone, address, health_issues, emergency, nid) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $health_issues = isset($d['health_issues']) ? implode(',', $d['health_issues']) : '';

        return $q->execute([
            $uid,
            $d['name'] ?? '',
            $d['phone'] ?? '',
            $d['address'] ?? '',
            $health_issues,  
            $d['emergency'] ?? '',
            $d['nid'] ?? ''
        ]);
    }

    function all(){
        return $this->conn->query("SELECT * FROM patients")->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>