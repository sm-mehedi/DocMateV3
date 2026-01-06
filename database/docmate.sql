CREATE DATABASE docmate;
USE docmate;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','doctor','patient') NOT NULL
);
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

VALUES ('admin@docmate.com', 'admin123', 'admin')
ON DUPLICATE KEY UPDATE 
    email = 'admin@docmate.com', 
    password = 'admin123';
<<<<<<< HEAD
<<<<<<< HEAD
CREATE DATABASE docmate;
USE docmate;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','doctor','patient') NOT NULL
);
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
=======
>>>>>>> 49836ad91490b605f7b878e38a6bf9ea7a2191b4
=======
>>>>>>> 62f3d2434fcb2b8ff274ca93565fcb0ab0de0a7b
