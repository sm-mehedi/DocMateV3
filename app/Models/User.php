<?php
class User {
    private $conn;
    function __construct($c){ $this->conn = $c; }

    function login($email, $pass){
        $q = $this->conn->prepare("SELECT * FROM users WHERE email=?");
        $q->execute([$email]);
        $u = $q->fetch(PDO::FETCH_ASSOC);

        // Simple plain text comparison for ALL users
        if($u && $u['password'] === $pass){
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['role'] = $u['role'];
            return true;
        }
        return false;
    }

    function create($email, $pass, $role){
        // Check for duplicate email
        $q = $this->conn->prepare("SELECT id FROM users WHERE email=?");
        $q->execute([$email]);
        if($q->fetch()) return false;

        // Store Pass as plain text 
        $q = $this->conn->prepare("INSERT INTO users VALUES(null, ?, ?, ?)");
        return $q->execute([$email, $pass, $role]);
    }
}
?>