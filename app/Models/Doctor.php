<?php
class Doctor {
    private $conn;
    function __construct($c){ $this->conn = $c; }

    function create($uid, $d){
        $q = $this->conn->prepare(
            "INSERT INTO doctors 
            (user_id, name, degree, phone, bmdc, nid, address, chamber, available_days, available_time, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        return $q->execute([
            $uid,
            $d['name'] ?? '',
            isset($d['degree']) ? implode(',', $d['degree']) : '',
            $d['phone'] ?? '',
            $d['bmdc'] ?? '',
            $d['nid'] ?? '',
            $d['address'] ?? '',
            $d['chamber'] ?? '',
            isset($d['days']) ? implode(',', $d['days']) : '',
            $d['time'] ?? '',
            $d['desc'] ?? ''
        ]);
    }

    function all(){
        return $this->conn
            ->query("SELECT * FROM doctors")
            ->fetchAll(PDO::FETCH_ASSOC);
    }
    function getByUser($user_id){
    $q = $this->conn->prepare(
        "SELECT * FROM doctors WHERE user_id=?"
    );
    $q->execute([$user_id]);
    return $q->fetch(PDO::FETCH_ASSOC);
}

function updateAvailability($user_id, $days, $time){
    $q = $this->conn->prepare(
        "UPDATE doctors 
         SET available_days=?, available_time=? 
         WHERE user_id=?"
    );
    return $q->execute([
        implode(',', $days),
        $time,
        $user_id
    ]);
}

function toggleAvailability($user_id){
    $q = $this->conn->prepare(
        "UPDATE doctors 
         SET is_available = NOT is_available 
         WHERE user_id=?"
    );
    return $q->execute([$user_id]);
}
 public function updateInfo($user_id, $data){
        $q = $this->conn->prepare(
            "UPDATE doctors SET 
                phone = ?, 
                address = ?, 
                chamber = ? 
             WHERE user_id = ?"
        );
        return $q->execute([
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['chamber'] ?? '',
            $user_id
        ]);
    }

}
