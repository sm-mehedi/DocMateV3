<?php
class Booking {
    private $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    public function hasBooking($doctor_id, $patient_id){
        $q = $this->conn->prepare(
            "SELECT * FROM bookings WHERE doctor_id=? AND patient_id=? AND status='booked'"
        );
        $q->execute([$doctor_id, $patient_id]);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    public function book($doctor_id, $patient_id, $preferred_day){
        $existing = $this->hasBooking($doctor_id, $patient_id);
        if ($existing) return false;

        $q = $this->conn->prepare(
            "SELECT id FROM bookings WHERE doctor_id=? AND patient_id=? AND status='cancelled'"
        );
        $q->execute([$doctor_id, $patient_id]);
        $cancelled = $q->fetch(PDO::FETCH_ASSOC);

        if ($cancelled) {
            $q = $this->conn->prepare(
                "UPDATE bookings 
                 SET status='booked', doctor_cancelled=0, preferred_day=?
                 WHERE id=?"
            );
            return $q->execute([$preferred_day, $cancelled['id']]);
        }

        $q = $this->conn->prepare(
            "INSERT INTO bookings 
             (doctor_id, patient_id, preferred_day, status, doctor_cancelled)
             VALUES (?, ?, ?, 'booked', 0)"
        );
        return $q->execute([$doctor_id, $patient_id, $preferred_day]);
    }

    public function unbook($doctor_id, $patient_id){
        $q = $this->conn->prepare(
            "UPDATE bookings SET status='cancelled', patient_unbooked=1 WHERE doctor_id=? AND patient_id=? AND status='booked'"
        );
        return $q->execute([$doctor_id, $patient_id]);
    }

    public function doctorCancel($doctor_id, $patient_id){
        $q = $this->conn->prepare(
            "UPDATE bookings SET status='cancelled', doctor_cancelled=1 WHERE doctor_id=? AND patient_id=? AND status='booked'"
        );
        return $q->execute([$doctor_id, $patient_id]);
    }

    // DELETE booking (for "Mark as Seen")
    public function deleteBooking($booking_id, $patient_id) {
        $q = $this->conn->prepare(
            "DELETE FROM bookings 
             WHERE patient_id = ? AND doctor_id = (SELECT doctor_id FROM bookings WHERE id = ?)"
        );
        return $q->execute([$patient_id, $booking_id]);
    }

    // NEW METHOD: Delete booking by patient_id and doctor_user_id (for doctor marking as seen)
    // In Booking model - FIXED deleteByDoctor method
public function deleteByDoctor($patient_id, $doctor_user_id) {
    $q = $this->conn->prepare(
        "DELETE b FROM bookings b
         INNER JOIN doctors d ON b.doctor_id = d.id
         WHERE b.patient_id = ? 
         AND d.user_id = ? 
         AND b.status = 'booked'"
    );
    return $q->execute([$patient_id, $doctor_user_id]);
}

    // Only active bookings (status='booked')
    public function myActiveBookings($patient_id){
        $q = $this->conn->prepare(
            "SELECT d.*, 
                    b.status, 
                    b.doctor_cancelled, 
                    b.preferred_day,
                    b.id AS booking_id
             FROM bookings b
             JOIN doctors d ON b.doctor_id = d.id
             WHERE b.patient_id=? AND b.status='booked'"
        );
        $q->execute([$patient_id]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    // All bookings (active + cancelled)
    public function myAllBookings($patient_id){
        $q = $this->conn->prepare(
            "SELECT d.*, 
                    b.status, 
                    b.doctor_cancelled, 
                    b.preferred_day,
                    b.id AS booking_id
             FROM bookings b
             JOIN doctors d ON b.doctor_id = d.id
             WHERE b.patient_id=?"
        );
        $q->execute([$patient_id]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function myAllBookingsWithCancelInfo($patient_id){
        $q = $this->conn->prepare(
            "SELECT d.*, 
                    b.status, 
                    b.doctor_cancelled, 
                    b.patient_unbooked,
                    b.preferred_day,
                    b.id AS booking_id
             FROM bookings b
             JOIN doctors d ON b.doctor_id = d.id
             WHERE b.patient_id = ? 
             ORDER BY 
               CASE b.status 
                 WHEN 'booked' THEN 1
                 WHEN 'cancelled' THEN 2
                 ELSE 3
               END,
               b.id DESC"
        );
        $q->execute([$patient_id]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function forDoctor($user_id){
        $q = $this->conn->prepare(
            "SELECT p.*, 
                    b.status, 
                    b.doctor_cancelled,
                    b.preferred_day
             FROM bookings b
             JOIN patients p ON b.patient_id = p.id
             JOIN doctors d ON b.doctor_id = d.id
             WHERE d.user_id = ? AND b.status = 'booked'"
        );
        $q->execute([$user_id]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function unbookByBookingId($booking_id){
        $q = $this->conn->prepare(
            "UPDATE bookings SET status='cancelled', patient_unbooked=1 WHERE id=? AND status='booked'"
        );
        return $q->execute([$booking_id]);
    }
}
?>