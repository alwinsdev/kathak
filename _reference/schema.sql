-- schema.sql
CREATE DATABASE IF NOT EXISTS kathak_therapy CHARACTER SET utf8mb4;
USE kathak_therapy;

DROP TABLE IF EXISTS completions;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS mudras;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('patient','doctor') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE patients (
  user_id INT PRIMARY KEY,
  age INT,
  gender VARCHAR(20),
  phone VARCHAR(20),
  condition_notes TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE mudras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  benefits TEXT
);

CREATE TABLE assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  mudra_id INT NOT NULL,
  doctor_id INT NOT NULL,
  scheduled_time TIME NOT NULL,
  duration_min INT DEFAULT 10,
  notes TEXT,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (mudra_id) REFERENCES mudras(id),
  FOREIGN KEY (doctor_id) REFERENCES users(id)
);

CREATE TABLE completions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assignment_id INT NOT NULL,
  completed_date DATE NOT NULL,
  notes TEXT,
  confidence DECIMAL(4,3) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_assign_date (assignment_id, completed_date),
  FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE
);

-- Seed: a default doctor (password: doctor123)
INSERT INTO users (name, email, password, role) VALUES
('Dr. Anjali Sharma', 'doctor@kathak.com',
 '$2y$10$N1IhLZsJxsmYbEz6mUz0E.D8Pj7N5g0KvFvJ7XKqTd9oRZxFm0bGq', 'doctor');

-- Seed: common Siddha mudras
INSERT INTO mudras (name, description, benefits) VALUES
('Pataka',  'Open palm, fingers extended and held together, thumb bent.', 'Improves wrist flexibility & finger coordination.'),
('Tripataka','Like Pataka but ring finger bent.', 'Strengthens fine motor control.'),
('Ardhapataka','Like Tripataka but little finger also bent.', 'Improves finger isolation & dexterity.'),
('Kartarimukha','Index and middle finger stretched apart, others folded.', 'Relieves tension in palm muscles.'),
('Mayura',  'Ring finger touches thumb tip, others extended.', 'Calming, improves focus.'),
('Ardhachandra','Hand in crescent shape, thumb stretched out.', 'Stretches palm & opens chest posture.'),
('Arala',   'Index curved, others slightly bent.', 'Loosens stiff fingers.'),
('Shukatunda','Like Arala but ring finger also curved.', 'Targets joint stiffness.'),
('Mushti',  'Closed fist with thumb on top.', 'Builds grip strength.'),
('Shikhara','Closed fist, thumb pointing up.', 'Stabilizes wrist, builds strength.');
