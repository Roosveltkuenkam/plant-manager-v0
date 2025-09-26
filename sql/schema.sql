-- Base: plant_manager
CREATE DATABASE IF NOT EXISTS plant_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE plant_manager;


-- Table simple des utilisateurs (v1: multi-utilisateur, gestion complète)
CREATE TABLE IF NOT EXISTS users (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(255) DEFAULT NULL,
email VARCHAR(255) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
photo VARCHAR(255) DEFAULT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table pour les plantes
CREATE TABLE IF NOT EXISTS plants (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
name VARCHAR(255) NOT NULL,
species VARCHAR(255) DEFAULT NULL,
purchase_date DATE DEFAULT NULL,
image_path VARCHAR(255) DEFAULT NULL,
water_amount VARCHAR(50) DEFAULT NULL, -- exemple: '500ml' ou '1L'
water_interval_days INT DEFAULT 7, -- fréquence (en jours)
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
last_watered DATETIME DEFAULT NULL,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- Historique des arrosages
CREATE TABLE IF NOT EXISTS watering_history (
id INT AUTO_INCREMENT PRIMARY KEY,
plant_id INT NOT NULL,
watered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
amount VARCHAR(50) DEFAULT NULL,
note TEXT DEFAULT NULL,
FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
) ENGINE=InnoDB;




-- Insérer un utilisateur de test (optionnel)
INSERT INTO users (email) VALUES (NULL);