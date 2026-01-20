CREATE DATABASE docmate;
USE docmate;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','doctor','patient') NOT NULL
);

-- Patients table
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    health_issues VARCHAR(255),
    emergency VARCHAR(20),
    nid VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Doctors table
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    degree VARCHAR(50),
    phone VARCHAR(20),
    bmdc VARCHAR(20),
    nid VARCHAR(20),
    address VARCHAR(255),
    chamber VARCHAR(255),
    available_days VARCHAR(50),
    description TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    status ENUM('booked','unbooked') DEFAULT 'booked',
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);



INSERT INTO users (email, password, role) VALUES
('rahim@gmail.com', '123456', 'patient'),
('karim@gmail.com', '123456', 'patient'),
('salma@gmail.com', '123456', 'patient'),
('nabila@gmail.com', '123456', 'patient'),
('arif@gmail.com', '123456', 'patient'),
('tanvir@gmail.com', '123456', 'patient'),
('sadia@gmail.com', '123456', 'patient'),
('farhan@gmail.com', '123456', 'patient'),
('nusrat@gmail.com', '123456', 'patient'),
('imran@gmail.com', '123456', 'patient');

INSERT INTO patients (user_id, name, phone, address, health_issues, emergency, nid) VALUES
((SELECT id FROM users WHERE email='rahim@gmail.com'), 'Rahim Uddin', '01711111111', 'Dhaka', 'diabetes', '01811111111', '1234567890'),
((SELECT id FROM users WHERE email='karim@gmail.com'), 'Karim Mia', '01722222222', 'Gazipur', 'bp', '01822222222', '1234567891'),
((SELECT id FROM users WHERE email='salma@gmail.com'), 'Salma Akter', '01733333333', 'Narayanganj', 'asthma', '01833333333', '1234567892'),
((SELECT id FROM users WHERE email='nabila@gmail.com'), 'Nabila Islam', '01744444444', 'Dhaka', 'allergy', '01844444444', '1234567893'),
((SELECT id FROM users WHERE email='arif@gmail.com'), 'Arif Hossain', '01755555555', 'Sylhet', 'diabetes,bp', '01855555555', '1234567894'),
((SELECT id FROM users WHERE email='tanvir@gmail.com'), 'Tanvir Ahmed', '01766666666', 'Cumilla', '', '01866666666', '1234567895'),
((SELECT id FROM users WHERE email='sadia@gmail.com'), 'Sadia Rahman', '01777777777', 'Rajshahi', 'bp', '01877777777', '1234567896'),
((SELECT id FROM users WHERE email='farhan@gmail.com'), 'Farhan Kabir', '01788888888', 'Khulna', '', '01888888888', '1234567897'),
((SELECT id FROM users WHERE email='nusrat@gmail.com'), 'Nusrat Jahan', '01799999999', 'Barishal', 'allergy', '01899999999', '1234567898'),
((SELECT id FROM users WHERE email='imran@gmail.com'), 'Imran Khan', '01611111111', 'Rangpur', 'asthma', '01911111111', '1234567899');


INSERT INTO users (email, password, role) VALUES
('dr.amin@gmail.com', '123456', 'doctor'),
('dr.sultana@gmail.com', '123456', 'doctor'),
('dr.hassan@gmail.com', '123456', 'doctor'),
('dr.faria@gmail.com', '123456', 'doctor'),
('dr.kamal@gmail.com', '123456', 'doctor'),
('dr.tania@gmail.com', '123456', 'doctor'),
('dr.rahman@gmail.com', '123456', 'doctor'),
('dr.mahmuda@gmail.com', '123456', 'doctor'),
('dr.jawed@gmail.com', '123456', 'doctor'),
('dr.mim@gmail.com', '123456', 'doctor');

INSERT INTO doctors (user_id, name, degree, phone, bmdc, nid, address, chamber, available_days, description) VALUES
((SELECT id FROM users WHERE email='dr.amin@gmail.com'), 'Dr Aminul Islam', 'MBBS', '01811112222', 'BMDC1001', '9876543210', 'Dhaka', 'Dhanmondi', 'Sun,Mon,Wed', 'General physician'),
((SELECT id FROM users WHERE email='dr.sultana@gmail.com'), 'Dr Sultana Akter', 'MBBS,FCPS', '01822223333', 'BMDC1002', '9876543211', 'Dhaka', 'Mirpur', 'Tue,Thu', 'Gynecologist'),
((SELECT id FROM users WHERE email='dr.hassan@gmail.com'), 'Dr Hassan Ali', 'MBBS', '01833334444', 'BMDC1003', '9876543212', 'Chattogram', 'Panchlaish', 'Sun,Wed', 'Medicine specialist'),
((SELECT id FROM users WHERE email='dr.faria@gmail.com'), 'Dr Faria Khan', 'MBBS,MPhil', '01844445555', 'BMDC1004', '9876543213', 'Sylhet', 'Zindabazar', 'Mon,Thu', 'Cardiology'),
((SELECT id FROM users WHERE email='dr.kamal@gmail.com'), 'Dr Kamal Uddin', 'MBBS', '01855556666', 'BMDC1005', '9876543214', 'Rajshahi', 'Kazla', 'Sun,Mon', 'Child specialist'),
((SELECT id FROM users WHERE email='dr.tania@gmail.com'), 'Dr Tania Rahman', 'MBBS,FCPS', '01866667777', 'BMDC1006', '9876543215', 'Khulna', 'Sonadanga', 'Wed,Fri', 'Dermatology'),
((SELECT id FROM users WHERE email='dr.rahman@gmail.com'), 'Dr Rahman Sheikh', 'MBBS', '01877778888', 'BMDC1007', '9876543216', 'Barishal', 'Nathullabad', 'Tue,Thu', 'ENT'),
((SELECT id FROM users WHERE email='dr.mahmuda@gmail.com'), 'Dr Mahmuda Begum', 'MBBS', '01888889999', 'BMDC1008', '9876543217', 'Rangpur', 'Jahaj Company', 'Sun,Wed', 'Medicine'),
((SELECT id FROM users WHERE email='dr.jawed@gmail.com'), 'Dr Jawed Iqbal', 'MBBS', '01911112222', 'BMDC1009', '9876543218', 'Cumilla', 'Kandirpar', 'Mon,Thu', 'Orthopedic'),
((SELECT id FROM users WHERE email='dr.mim@gmail.com'), 'Dr Mim Akter', 'MBBS', '01922223333', 'BMDC1010', '9876543219', 'Mymensingh', 'Town Hall', 'Fri,Sat', 'General physician');

ALTER TABLE doctors
ADD available_time VARCHAR(50) AFTER available_days;

UPDATE doctors SET available_time='10:00 AM - 2:00 PM' WHERE id=1;
UPDATE doctors SET available_time='3:00 PM - 7:00 PM' WHERE id=3;
UPDATE doctors SET available_time='9:00 AM - 1:00 PM' WHERE id=4;
UPDATE doctors SET available_time='5:00 PM - 9:00 PM' WHERE id=5;
UPDATE doctors SET available_time='10:00 AM - 1:00 PM' WHERE id=6;
UPDATE doctors SET available_time='4:00 PM - 8:00 PM' WHERE id=7;
UPDATE doctors SET available_time='9:00 AM - 12:00 PM' WHERE id=8;
UPDATE doctors SET available_time='6:00 PM - 10:00 PM' WHERE id=9;
UPDATE doctors SET available_time='3:00 PM - 7:00 PM' WHERE id=10;

ALTER TABLE doctors
ADD is_available TINYINT(1) DEFAULT 1 AFTER available_time;
CREATE TABLE community_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('patient','doctor','admin') NOT NULL,
    topic VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
