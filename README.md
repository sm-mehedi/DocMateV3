# 🏥 DocMate – Healthcare Management System

## 📌 Project Overview
DocMate is a comprehensive healthcare management system designed to facilitate seamless interactions between patients, doctors, and administrators.  
It provides role-based access control with three user types, each having specialized functionalities.

---

## ✨ Features

### 👑 Admin Dashboard
- Add, view, update, and delete patients, doctors, and admins  
- Book, cancel, update, and delete appointments  
- Manage medicine inventory with full CRUD and search  
- Update admin profile and credentials  
- View system statistics (patients, doctors, appointments)

### 👨‍⚕️ Doctor Portal
- View assigned patients and appointment details  
- Toggle availability (online / offline)  
- Mark patients as seen or cancel appointments  
- Update professional profile  
- Real-time updates using AJAX  

### 👤 Patient Portal
- Browse doctors with search and filters  
- Book appointments with preferred doctors and days  
- Update personal and health information  
- View appointment history and status  
- Search medicines from the directory  

---

## 🛠 Technology Stack

### Backend
- PHP 7.4+ (PDO for database operations)  
- MySQL / MariaDB  
- Session-based authentication  
- MVC-like architecture  

### Frontend
- HTML5  
- CSS3 (Responsive design)  
- Vanilla JavaScript with AJAX  
- JSON for data exchange  

---

## 🗄 Database Schema

### Core Tables
- `users` – authentication and role management  
- `patients` – patient personal and health information  
- `doctors` – doctor professional information  
- `bookings` – appointment management  
- `medicines` (JSON file) – medicine inventory  

### Relationships
- `users.id` → `patients.user_id` (One-to-One)  
- `users.id` → `doctors.user_id` (One-to-One)  
- `doctors.id` → `bookings.doctor_id` (One-to-Many)  
- `patients.id` → `bookings.patient_id` (One-to-Many)  

---

## ⚙️ Installation & Setup

### Prerequisites
- Apache / Nginx  
- PHP 7.4 or higher  
- MySQL / MariaDB  
- Modern web browser  

### Step 1 – Clone the project
```bash
git clone [https://github.com/sm-mehedi/DocMateV3] docmate
